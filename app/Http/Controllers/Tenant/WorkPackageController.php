<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PartReservation;
use App\Models\Service;
use App\Models\ServiceWorkPackage;
use App\Services\WorkshopFlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkPackageController extends Controller
{
    public function __construct(protected WorkshopFlowService $flow) {}

    /**
     * Create a work package from a finding (or manual). Update-in-place is
     * guaranteed for drafts by the service layer.
     */
    public function store(Request $request, Service $service): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('work-packages.create'), 403, 'Tidak punya izin membuat work package.');

        [$data, $items] = $this->validated($request);

        $package = $this->flow->saveWorkPackage($service, $data, $items);

        return redirect()
            ->to(route('services.show', $service->id).'#tab-findings')
            ->with('success', "Rencana Pekerjaan \"{$package->title}\" tersimpan (draft).");
    }

    /** Update a DRAFT work package — keeps the same primary key. */
    public function update(Request $request, ServiceWorkPackage $package): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('work-packages.update'), 403, 'Tidak punya izin mengubah work package.');

        [$data, $items] = $this->validated($request);
        $service = $package->service()->first() ?? $package->service;
        \assert($service instanceof Service);

        $this->flow->saveWorkPackage($service, $data, $items, $package);

        return redirect()
            ->to(route('services.show', $package->service_id).'#tab-findings')
            ->with('success', "Rencana Pekerjaan \"{$package->title}\" diperbarui.");
    }

    /** Cancel a draft/proposed package and release its reservations safely. */
    public function destroy(ServiceWorkPackage $package): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('work-packages.update'), 403, 'Tidak punya izin mengubah work package.');

        DB::transaction(function () use ($package) {
            $locked = ServiceWorkPackage::query()->whereKey($package->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isEditable(), 422, 'Hanya draft/proposal yang bisa dibatalkan.');

            $locked->forceFill(['status' => ServiceWorkPackage::STATUS_CANCELLED])->save();

            // Reservations created by this package (notes tag WP#<id>) are
            // released safely with an audit trail.
            $released = PartReservation::where('service_id', $locked->service_id)
                ->where('status', 'reserved')
                ->where('notes', 'like', 'WP#'.$locked->id.'%')
                ->update(['status' => 'released']);

            ActivityLog::record('work_package.updated', $locked, "Work Package \"{$locked->title}\" dibatalkan; {$released} reservasi parts dilepas.", [
                'old_status' => $package->getOriginal('status'),
            ]);
        });

        return redirect()
            ->to(route('services.show', $package->service_id).'#tab-work')
            ->with('success', 'Work package dibatalkan.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:2000',
            'service_finding_id' => 'nullable|integer|exists:service_findings,id',
            'standard_minutes' => 'nullable|integer|min:0|max:2880',
        ]);

        $items = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:labor,part,other',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.standard_minutes' => 'nullable|integer|min:0|max:2880',
        ])['items'];

        return [$data, $items];
    }
}

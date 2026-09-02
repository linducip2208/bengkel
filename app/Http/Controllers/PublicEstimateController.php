<?php

namespace App\Http\Controllers;

use App\Models\ServiceEstimate;
use App\Services\EstimateService;
use App\Services\WorkshopFlowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Public (token-based) estimate view + approval.
 * No auth: the token is the credential. Sequential IDs are never exposed.
 */
class PublicEstimateController extends Controller
{
    public function __construct(protected EstimateService $estimates) {}

    public function show(string $token)
    {
        $estimate = ServiceEstimate::withoutGlobalScopes()
            ->with(['items.product', 'service', 'customer', 'vehicle.vehicleBrand', 'vehicle.vehicleType'])
            ->where('public_token', $token)
            ->firstOrFail();

        abort_if($estimate->status === ServiceEstimate::STATUS_DRAFT, 404);

        return view('public.estimate', [
            'estimate' => $estimate,
            'company' => $estimate->snapshotCompany(),
            'customer' => $estimate->snapshotCustomer(),
            'vehicle' => $estimate->snapshotVehicle(),
            'service' => $estimate->snapshotService(),
            'approvable' => in_array($estimate->status, ServiceEstimate::APPROVABLE_STATUSES, true) && ! $estimate->isExpiredByDate(),
        ]);
    }

    public function pdf(string $token)
    {
        $estimate = ServiceEstimate::withoutGlobalScopes()
            ->with('items.product')
            ->where('public_token', $token)
            ->firstOrFail();

        abort_if($estimate->status === ServiceEstimate::STATUS_DRAFT, 404);

        $pdf = Pdf::loadView('estimates.pdf', [
            'estimate' => $estimate,
            'company' => $estimate->snapshotCompany(),
            'customer' => $estimate->snapshotCustomer(),
            'vehicle' => $estimate->snapshotVehicle(),
            'service' => $estimate->snapshotService(),
        ])->setPaper('a4');

        return $pdf->stream("estimasi-{$estimate->estimate_number}.pdf");
    }

    public function approve(Request $request, string $token)
    {
        $estimate = ServiceEstimate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();

        abort_if($estimate->status === ServiceEstimate::STATUS_DRAFT, 404);

        // Grouped estimates use the per-work-package decision flow.
        abort_if($estimate->groups()->exists(), 409, 'Gunakan keputusan per pekerjaan.');

        $this->estimates->approve($estimate, 'public_link');

        return redirect()
            ->route('public.estimate.show', $token)
            ->with('success', 'Terima kasih! Estimasi telah disetujui.');
    }

    /**
     * Customer submits per-work-package decisions (SETUJUI / TOLAK per group).
     * Idempotent: resubmitting after a final decision never rewrites history.
     */
    public function decide(Request $request, string $token)
    {
        $estimate = ServiceEstimate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();

        abort_if($estimate->status === ServiceEstimate::STATUS_DRAFT, 404);

        abort_unless($estimate->groups()->exists(), 409, 'Estimasi ini tidak memiliki keputusan per pekerjaan.');

        $validated = $request->validate([
            'decisions' => 'required|array|min:1',
            'decisions.*.group_id' => 'required|integer',
            'decisions.*.decision' => 'required|in:approved,rejected',
            'decisions.*.reason' => 'nullable|string|max:255',
        ]);

        $groups = $estimate->groups()->pluck('id');
        foreach ($validated['decisions'] as $decision) {
            abort_unless($groups->contains((int) $decision['group_id']), 422, 'Keputusan berisi grup yang tidak ada pada estimasi ini.');
        }

        app(WorkshopFlowService::class)->submitGroupDecisions($estimate, $validated['decisions'], 'public_link');

        return redirect()
            ->route('public.estimate.show', $token)
            ->with('success', 'Terima kasih! Keputusan Anda telah kami terima.');
    }

    public function reject(Request $request, string $token)
    {
        $estimate = ServiceEstimate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();

        abort_if($estimate->status === ServiceEstimate::STATUS_DRAFT, 404);

        $reason = trim((string) $request->input('reason', ''));

        $this->estimates->reject($estimate, $reason !== '' ? $reason : 'Ditolak customer via tautan estimasi');

        return redirect()
            ->route('public.estimate.show', $token)
            ->with('success', 'Estimasi telah ditolak. Kami akan menghubungi Anda untuk revisi.');
    }
}

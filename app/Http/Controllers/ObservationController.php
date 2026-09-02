<?php

namespace App\Http\Controllers;

use App\Models\ObservationType;
use App\Models\Service;
use App\Services\ObservationService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class ObservationController extends Controller
{
    public function __construct(
        protected ObservationService $observationService
    ) {}

    /** Who may open (and print) the checklist: service/checklist viewers. */
    protected function authorizeView(Service $service): void
    {
        abort_unless((bool) auth()->user()?->can('service.view') || (bool) auth()->user()?->can('jobcard.view'), 403, 'Tidak punya izin melihat checklist.');
    }

    /** Who may write checklist state: service.edit OR findings.create. */
    protected function authorizeUpdate(Service $service): void
    {
        $user = auth()->user();
        abort_unless((bool) $user?->can('service.edit') || (bool) $user?->can('findings.create'), 403, 'Tidak punya izin mengisi checklist.');
    }

    public function checklist(Service $service)
    {
        $this->authorizeView($service);

        $service->load(['repairCategory', 'serviceObservationPoints']);
        $points = $this->observationService->getPointsForService();

        $checkResults = $service->serviceObservationPoints->keyBy('observation_point_id');

        $groupedPoints = $points->groupBy(fn ($p) => $p->observationType->observation_type ?? 'Lainnya');

        $canUpdate = (bool) auth()->user()?->can('service.edit') || (bool) auth()->user()?->can('findings.create');

        return view('observations.checklist', compact('service', 'groupedPoints', 'checkResults', 'canUpdate'));
    }

    /**
     * Read-only printable checklist. Renders only already-saved observation
     * results — no data is created, updated, or transitioned here.
     */
    public function printChecklist(Service $service)
    {
        $this->authorizeView($service);

        $service->load([
            'customer',
            'vehicle.vehicleBrand',
            'vehicle.vehicleType',
            'repairCategory',
            'serviceAdvisor',
            'jobcardDetail',
            'serviceObservationPoints.observationPoint.observationType',
        ]);

        $points = $this->observationService->getPointsForService();
        $checkResults = $service->serviceObservationPoints->keyBy('observation_point_id');
        $groupedPoints = $points->groupBy(fn ($p) => $p->observationType->observation_type ?? 'Lainnya');
        $company = app(SettingsService::class)->getCompanyInfo();

        return view('observations.checklist-print', compact('service', 'groupedPoints', 'checkResults', 'company'));
    }

    /**
     * Save checklist state, then sync findings — one committed transaction.
     * action=draft  → back to the checklist (work in place).
     * action=continue → continue to the Findings/Estimate flow.
     * The checklist is saved in BOTH cases — no state can be lost.
     */
    public function saveChecklist(Request $request, Service $service)
    {
        $this->authorizeUpdate($service);

        $request->validate([
            'points' => 'required|array',
        ]);

        $this->observationService->saveCheckResults($service, $request->input('points', []));

        $action = $request->input('action', 'draft');

        if ($action === 'continue') {
            return redirect()
                ->to(route('services.show', $service->id).'#tab-findings')
                ->with('success', 'Checklist tersimpan — lanjut ke Temuan / Estimasi.');
        }

        return redirect()
            ->to($action === 'stay' ? route('observations.checklist', $service) : route('services.show', $service->id))
            ->with('success', 'Hasil observasi berhasil disimpan.');
    }

    public function getByType(ObservationType $type)
    {
        abort_unless((bool) auth()->user()?->can('service.view') || (bool) auth()->user()?->can('jobcard.view'), 403, 'Tidak punya izin.');

        $points = $type->observationPoints()
            ->get(['id', 'observation_point']);

        return response()->json($points);
    }
}

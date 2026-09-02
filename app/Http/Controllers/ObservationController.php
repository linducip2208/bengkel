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

    public function checklist(Service $service)
    {
        $service->load(['repairCategory', 'serviceObservationPoints']);
        $points = $this->observationService->getPointsForService();

        $checkResults = $service->serviceObservationPoints->keyBy('observation_point_id');

        $groupedPoints = $points->groupBy(fn ($p) => $p->observationType->observation_type ?? 'Lainnya');

        return view('observations.checklist', compact('service', 'groupedPoints', 'checkResults'));
    }

    /**
     * Read-only printable checklist. Renders only already-saved observation
     * results — no data is created, updated, or transitioned here.
     */
    public function printChecklist(Service $service)
    {
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

    public function saveChecklist(Request $request, Service $service)
    {
        $request->validate([
            'points' => 'required|array',
        ]);

        $this->observationService->saveCheckResults($service, $request->input('points', []));

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Hasil observasi berhasil disimpan.');
    }

    public function getByType(ObservationType $type)
    {
        $points = $type->observationPoints()
            ->get(['id', 'observation_point']);

        return response()->json($points);
    }
}

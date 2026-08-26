<?php

namespace App\Http\Controllers;

use App\Models\ObservationType;
use App\Models\Service;
use App\Services\ObservationService;
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

        $groupedPoints = $points->groupBy(fn ($p) => $p->observationType?->observation_type ?? 'Lainnya');

        return view('observations.checklist', compact('service', 'groupedPoints', 'checkResults'));
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

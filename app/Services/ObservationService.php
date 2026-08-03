<?php

namespace App\Services;

use App\Models\ObservationPoint;
use App\Models\Service;
use App\Models\ServiceObservationPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ObservationService
{
    public function getPointsForService(): Collection
    {
        return ObservationPoint::with('observationType')
            ->orderBy('observation_type_id')
            ->get();
    }

    public function saveCheckResults(Service $service, array $points): void
    {
        DB::transaction(function () use ($service, $points) {
            foreach ($points as $pointId => $data) {
                ServiceObservationPoint::updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'observation_point_id' => $pointId,
                    ],
                    [
                        'checked' => !empty($data['is_checked']),
                        'comment' => $data['comment'] ?? null,
                    ]
                );
            }
        });
    }

    public function createDefaultChecklist(Service $service): void
    {
        $points = $this->getPointsForService();

        foreach ($points as $point) {
            ServiceObservationPoint::firstOrCreate([
                'service_id' => $service->id,
                'observation_point_id' => $point->id,
            ], [
                'checked' => false,
            ]);
        }
    }
}

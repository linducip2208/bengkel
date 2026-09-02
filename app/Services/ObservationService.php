<?php

namespace App\Services;

use App\Models\ObservationPoint;
use App\Models\Service;
use App\Models\ServiceObservationPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ObservationService
{
    public function __construct(protected WorkshopFlowService $flow) {}

    public function getPointsForService(): Collection
    {
        return ObservationPoint::with('observationType')
            ->orderBy('observation_type_id')
            ->get();
    }

    /**
     * Persist checklist results. Repeated saves are idempotent (one row per
     * service+point) and keep the legacy `checked` flag in sync with the
     * richer condition_status. Findings are then synced from the new state.
     */
    public function saveCheckResults(Service $service, array $points): void
    {
        DB::transaction(function () use ($service, $points) {
            foreach ($points as $pointId => $data) {
                $condition = $data['condition_status'] ?? null;

                // Legacy clients send only is_checked — map to ok/not_checked.
                if ($condition === null) {
                    $condition = ! empty($data['is_checked']) || filter_var($data['is_checked'] ?? false, FILTER_VALIDATE_BOOLEAN)
                        ? ServiceObservationPoint::CONDITION_OK
                        : ServiceObservationPoint::CONDITION_NOT_CHECKED;
                }

                if (! in_array($condition, ServiceObservationPoint::CONDITIONS, true)) {
                    $condition = ServiceObservationPoint::CONDITION_NOT_CHECKED;
                }

                $checked = $condition !== ServiceObservationPoint::CONDITION_NOT_CHECKED;
                $measurement = isset($data['measurement_value']) && $data['measurement_value'] !== ''
                    ? round((float) $data['measurement_value'], 3)
                    : null;

                ServiceObservationPoint::updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'observation_point_id' => $pointId,
                    ],
                    [
                        'checked' => $checked,
                        'condition_status' => $condition,
                        'measurement_value' => $measurement,
                        'measurement_unit' => $measurement !== null ? ($data['measurement_unit'] ?? null) : null,
                        'comment' => $data['comment'] ?? null,
                    ]
                );
            }
        });

        // Checklist state now drives the Finding domain (idempotent sync).
        $this->flow->syncFindingsFromChecklist($service);
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
                'condition_status' => ServiceObservationPoint::CONDITION_NOT_CHECKED,
            ]);
        }
    }
}

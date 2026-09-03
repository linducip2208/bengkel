<?php

namespace Tests\Feature;

use App\Models\ServiceObservationPoint;
use App\Services\WorkshopProgressService;

class WorkshopProgressServiceTest extends WorkshopFlowTestCase
{
    public function test_walk_in_service_uses_canonical_stage_projection(): void
    {
        $service = $this->makeService(['workflow_status' => 0]);

        $progress = app(WorkshopProgressService::class)->calculate($service);

        $this->assertSame('walk-in', $progress['steps']['source']['data']);
        $this->assertSame('check_in', $progress['current_step']);
        $this->assertSame('Lanjutkan Check-In', $progress['next_action']['label']);
    }

    public function test_incomplete_checklist_is_warning_and_does_not_unlock_estimate(): void
    {
        $service = $this->makeService(['workflow_status' => 1]);
        [$point] = $this->makeChecklistData();
        ServiceObservationPoint::create([
            'service_id' => $service->id,
            'observation_point_id' => $point->id,
            'checked' => false,
            'condition_status' => ServiceObservationPoint::CONDITION_NOT_CHECKED,
        ]);

        $progress = app(WorkshopProgressService::class)->calculate($service);

        $this->assertSame(WorkshopProgressService::PENDING, $progress['steps']['checklist']['state']);
        $this->assertSame(WorkshopProgressService::BLOCKED, $progress['steps']['work_package']['state']);
        $this->assertSame('checklist', $progress['next_action']['key']);
    }
}

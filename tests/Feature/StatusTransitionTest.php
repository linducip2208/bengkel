<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_transition_map_rejects_invalid_jump(): void
    {
        $service = new Service(['workflow_status' => 0]);

        $this->assertTrue($service->canTransitionTo(1));
        $this->assertFalse($service->canTransitionTo(10));
        $this->assertFalse($service->canTransitionTo(12));
    }
}

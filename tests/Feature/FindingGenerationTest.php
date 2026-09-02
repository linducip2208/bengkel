<?php

namespace Tests\Feature;

use App\Models\ServiceFinding;
use App\Models\ServiceObservationPoint;
use App\Services\ObservationService;

/**
 * A. Checklist save twice → one active Finding only (id 25 stays 25).
 */
class FindingGenerationTest extends WorkshopFlowTestCase
{
    public function test_repair_required_creates_finding_with_measurement(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'repair_required', 'comment' => 'Perlu ganti', 'measurement_value' => '2.4', 'measurement_unit' => 'mm'],
        ]);

        $finding = ServiceFinding::where('service_id', $service->id)->first();
        $this->assertNotNull($finding);
        $this->assertSame(ServiceFinding::SEVERITY_REPAIR_REQUIRED, $finding->severity);
        $this->assertEqualsWithDelta(2.4, (float) $finding->measurement_value, 0.001);
        $this->assertSame(ServiceFinding::STATUS_OPEN, $finding->status);
    }

    public function test_ok_condition_creates_no_finding(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'ok'],
        ]);

        $this->assertSame(0, ServiceFinding::count());
    }

    public function test_not_checked_condition_creates_no_finding(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'not_checked'],
        ]);

        $this->assertSame(0, ServiceFinding::count());
    }

    public function test_finding_is_never_commercial(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);

        $finding = ServiceFinding::where('service_id', $service->id)->first();
        // A finding has no price fields at all — pricing lives on work packages.
        $this->assertArrayNotHasKey('unit_price', $finding->getAttributes());
        $this->assertArrayNotHasKey('grand_total', $finding->getAttributes());
    }

    public function test_finding_follows_severity_changes_from_checklist(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'attention'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->first();
        $this->assertSame(ServiceFinding::SEVERITY_ATTENTION, $finding->severity);

        // Same checklist now says repair_required → same finding updated.
        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'repair_required'],
        ]);

        $finding->refresh();
        $this->assertSame(ServiceFinding::SEVERITY_REPAIR_REQUIRED, $finding->severity);
        $this->assertSame(1, ServiceFinding::count());
    }

    public function test_multiple_points_generate_independent_findings(): void
    {
        [$oil, $radiator, $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'ok'],
            $radiator->id => ['condition_status' => 'attention'],
            $pad->id => ['condition_status' => 'critical'],
        ]);

        $this->assertSame(2, ServiceFinding::count());
        $this->assertSame(0, ServiceFinding::where('service_observation_point_id', ServiceObservationPoint::where('observation_point_id', $oil->id)->where('service_id', $service->id)->first()->id)->count());
    }
}

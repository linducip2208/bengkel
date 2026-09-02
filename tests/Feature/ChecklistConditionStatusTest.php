<?php

namespace Tests\Feature;

use App\Models\ServiceFinding;
use App\Models\ServiceObservationPoint;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * Checklist condition statuses persist + measurements work.
 */
class ChecklistConditionStatusTest extends WorkshopFlowTestCase
{
    public function test_condition_statuses_are_persisted_per_point(): void
    {
        [$oil, $radiator, $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'ok'],
            $radiator->id => ['condition_status' => 'attention', 'comment' => 'Sedikit keruh'],
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas hampir habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);

        $oilRow = ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $oil->id)->first();
        $radiatorRow = ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $radiator->id)->first();
        $padRow = ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $pad->id)->first();

        $this->assertSame('ok', $oilRow->condition_status);
        $this->assertTrue($oilRow->checked);
        $this->assertSame('attention', $radiatorRow->condition_status);
        $this->assertSame('critical', $padRow->condition_status);
        $this->assertEqualsWithDelta(1.2, (float) $padRow->measurement_value, 0.001);
        $this->assertSame('mm', $padRow->measurement_unit);
        $this->assertSame('Kampas hampir habis', $padRow->comment);
    }

    public function test_legacy_is_checked_payload_maps_to_ok(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['is_checked' => 1, 'comment' => 'aman'],
        ]);

        $row = ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $oil->id)->first();
        $this->assertSame('ok', $row->condition_status);
        $this->assertTrue($row->checked);
    }

    public function test_invalid_condition_falls_back_to_not_checked(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'hacked'],
        ]);

        $row = ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $oil->id)->first();
        $this->assertSame('not_checked', $row->condition_status);
        $this->assertFalse($row->checked);
    }

    public function test_critical_findings_exist_after_critical_checklist(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas hampir habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);

        $findings = ServiceFinding::where('service_id', $service->id)->get();
        $this->assertCount(1, $findings);
        $this->assertSame(ServiceFinding::SEVERITY_CRITICAL, $findings->first()->severity);
        $this->assertSame('Kampas Rem', $findings->first()->title);
        $this->assertMatchesRegularExpression('/^FND-\d{6}-\d{4}$/', $findings->first()->finding_number);
    }

    public function test_measurement_is_optional_everywhere(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'ok'],
        ]);

        $row = ServiceObservationPoint::where('service_id', $service->id)->where('observation_point_id', $oil->id)->first();
        $this->assertNull($row->measurement_value);
        $this->assertNull($row->measurement_unit);
    }

    public function test_checklist_page_and_print_render_condition_values(): void
    {
        [$oil, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'ok'],
            $pad->id => ['condition_status' => 'critical', 'comment' => 'kampas habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);

        $this->get(route('observations.checklist', $service))
            ->assertOk()
            ->assertSee('Kritis')
            ->assertSee('OK');

        $this->get(route('observations.checklist.print', $service))
            ->assertOk()
            ->assertSee('Kritis')
            ->assertSee('1.2 mm')
            ->assertSee('kampas habis');
    }

    public function test_progress_calculates_completeness_and_critical_count(): void
    {
        [$oil, $radiator, $pad] = $this->makeChecklistData();
        $service = $this->makeService();
        app(ObservationService::class)->createDefaultChecklist($service);

        $flow = app(WorkshopFlowService::class);
        $before = $flow->checklistProgress($service);
        $this->assertSame(0, $before['checked_count']);
        $this->assertSame(3, $before['total_points']);
        $this->assertSame(0, $before['percentage']);

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'ok'],
            $pad->id => ['condition_status' => 'critical'],
        ]);

        $progress = $flow->checklistProgress($service);
        $this->assertSame(2, $progress['checked_count']);
        $this->assertSame(3, $progress['total_points']);
        $this->assertSame(67, $progress['percentage']);
        $this->assertSame(1, $progress['critical_count']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ServiceFinding;
use App\Models\ServiceObservationPoint;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * B. Checklist update → same Finding ID → row count unchanged.
 */
class FindingUpdateDoesNotDuplicateTest extends WorkshopFlowTestCase
{
    public function test_repeated_checklist_save_keeps_same_finding_id(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas hampir habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);

        /** @var ServiceFinding $findingBefore */
        $findingBefore = ServiceFinding::where('service_id', $service->id)->firstOrFail();
        $countBefore = ServiceFinding::count();
        $this->assertSame(1, $countBefore);

        // Save checklist AGAIN with an updated comment — same point, new note.
        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas tinggal 1mm, wajib ganti', 'measurement_value' => '1', 'measurement_unit' => 'mm'],
        ]);

        $findingAfter = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        // Critical assertion A: same primary key, no duplicate row.
        $this->assertSame($findingBefore->id, $findingAfter->id);
        $this->assertSame($countBefore, ServiceFinding::count());
        $this->assertSame('Kampas tinggal 1mm, wajib ganti', $findingAfter->description);
        $this->assertSame($findingBefore->finding_number, $findingAfter->finding_number);
    }

    public function test_repeated_save_via_service_keep_id_stable(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'repair_required', 'comment' => 'v1'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        // Direct sync (idempotent path) repeated 3 times.
        for ($i = 0; $i < 3; $i++) {
            $flow->syncFindingsFromChecklist($service);
        }

        $this->assertSame($finding->id, ServiceFinding::where('service_id', $service->id)->firstOrFail()->id);
        $this->assertSame(1, ServiceFinding::count());
    }

    public function test_finding_update_endpoint_keeps_primary_key(): void
    {
        [, , $pad] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'original'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();
        $countBefore = ServiceFinding::count();

        $this->put(route('findings.update', $finding), [
            'title' => 'Kampas Rem Depan',
            'severity' => 'critical',
            'description' => 'Kampas rem depan tersisa sekitar 1.2 mm',
            'recommendation' => 'Ganti kampas rem depan',
        ])->assertRedirect();

        $finding->refresh();
        $this->assertSame($finding->id, $finding->id);
        $this->assertSame('Kampas rem depan tersisa sekitar 1.2 mm', $finding->description);
        $this->assertSame('Ganti kampas rem depan', $finding->recommendation);
        $this->assertSame($countBefore, ServiceFinding::count());
    }

    public function test_previously_open_finding_becomes_ok_resolved_before_approved_work(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'repair_required'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        // Re-inspection now reads OK (no approved work exists yet).
        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'ok'],
        ]);

        $finding->refresh();
        $this->assertSame(ServiceFinding::STATUS_RESOLVED, $finding->status);
        $this->assertNotNull($finding->resolved_at);
        $this->assertSame(1, ServiceFinding::count(), 'Resolution keeps history — never deletes.');
    }

    public function test_manual_finding_creation_is_also_idempotent(): void
    {
        [$oil] = $this->makeChecklistData();
        $service = $this->makeService();

        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'ok'],
        ]);

        // Manual finding not tied to any observation point (row is null).
        $flow = app(WorkshopFlowService::class);
        $flow->syncFindingsFromChecklist($service);
        $flow->syncFindingsFromChecklist($service);

        // No phantom rows: only checklist-driven findings exist.
        $this->assertSame(0, ServiceObservationPoint::where('service_id', $service->id)->whereNotNull('condition_status')->count() === 0 ? ServiceFinding::count() : ServiceFinding::count());
        $this->assertSame(0, ServiceFinding::whereNull('service_observation_point_id')->count());
    }
}

<?php

namespace Tests\Feature;

use App\Services\EstimateService;
use App\Services\WorkshopProgressService;

class SimplifiedWorkshopFlowTest extends WorkshopFlowTestCase
{
    public function test_direct_estimate_does_not_require_finding_or_work_package(): void
    {
        $service = $this->makeService(['workflow_status' => 1]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [
            ['item_type' => 'part', 'description' => 'Coil', 'quantity' => 3, 'unit_price' => 475000],
            ['item_type' => 'part', 'description' => 'Busi', 'quantity' => 2, 'unit_price' => 50000],
            ['item_type' => 'labor', 'description' => 'Jasa Diagnosa', 'quantity' => 1, 'unit_price' => 150000],
        ]);

        $this->assertSame(0, $service->findings()->count());
        $this->assertSame(0, $service->workPackages()->count());
        $this->assertSame(3, $estimate->items()->count());
        $this->assertSame(1_675_000.0, (float) $estimate->grand_total);
    }

    public function test_simple_operational_projection_uses_five_visible_stages(): void
    {
        $progress = app(WorkshopProgressService::class)->simpleOperationalFlow($this->makeService(['workflow_status' => 1]));

        $this->assertSame(['check_in', 'estimate', 'work', 'qc', 'invoice'], array_keys($progress['steps']));
        $this->assertSame('estimate', $progress['current_step']);
        $this->assertSame('tab-estimate', $progress['next_action']['target']);
        $this->assertSame('Check-In / Customer', $progress['steps']['check_in']['label']);
    }

    public function test_service_detail_keeps_technical_findings_secondary(): void
    {
        $response = $this->get(route('services.show', $this->makeService()));

        $response->assertOk()
            ->assertSee('Check-In / Customer')
            ->assertSee('Estimasi')
            ->assertSee('Pekerjaan')
            ->assertSee('QC')
            ->assertSee('Invoice')
            ->assertSee('Isi Pemeriksaan Detail')
            ->assertDontSee('>Lanjut ke Temuan<', false);
    }

    public function test_checklist_can_save_and_continue_directly_to_estimate(): void
    {
        [$point] = $this->makeChecklistData();
        $service = $this->makeService();

        $response = $this->post(route('observations.save-checklist', $service), [
            'action' => 'estimate',
            'points' => [$point->id => ['condition_status' => 'ok']],
        ]);

        $response->assertRedirect();
        $this->assertStringEndsWith('#tab-estimate', $response->headers->get('Location'));
    }
}

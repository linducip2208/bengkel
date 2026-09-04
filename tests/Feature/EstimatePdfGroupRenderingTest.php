<?php

namespace Tests\Feature;

use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * Estimate PDF/print render work-package groups with source badges and
 * standard time — the customer can always read WHY work was recommended.
 */
class EstimatePdfGroupRenderingTest extends WorkshopFlowTestCase
{
    protected function makeGroupedIssuedEstimate(): ServiceEstimate
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $pad = ObservationPoint::create(['observation_type_id' => ObservationType::create(['observation_type' => 'REM'])->id, 'observation_point' => 'Kampas Rem']);
        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Kampas', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas Rem', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');

        return $estimate->fresh();
    }

    public function test_pdf_contains_group_header_badge_and_standard_time(): void
    {
        $estimate = $this->makeGroupedIssuedEstimate();

        $pdf = $this->get(route('estimates.pdf', $estimate));
        $pdf->assertOk();
        $this->assertSame('%PDF', substr((string) $pdf->getContent(), 0, 4));
    }

    public function test_html_print_page_renders_group_header_with_source(): void
    {
        $estimate = $this->makeGroupedIssuedEstimate();

        $this->get(route('estimates.print', $estimate))
            ->assertOk()
            ->assertSee('GANTI KAMPAS REM DEPAN')
            ->assertSee('dari checklist kritis')
            ->assertSee('Sumber: '.$estimate->groups()->firstOrFail()->finding->finding_number)
            ->assertSee('Standar waktu: 30 menit');
    }

    public function test_public_pdf_streams_with_groups(): void
    {
        $estimate = $this->makeGroupedIssuedEstimate();

        $response = $this->get(route('public.estimate.pdf', $estimate->public_token));
        $response->assertOk();
        $this->assertSame('%PDF', substr((string) $response->getContent(), 0, 4));
    }

    public function test_public_estimate_shows_finding_recommendation_and_customer_facing_terms(): void
    {
        $estimate = $this->makeGroupedIssuedEstimate();
        $finding = $estimate->groups()->firstOrFail()->finding;

        $this->get(route('public.estimate.show', $estimate->public_token))
            ->assertOk()
            ->assertSee('ESTIMASI SERVIS')
            ->assertSee('Temuan: '.$finding->finding_number)
            ->assertSee('Hasil Pemeriksaan:')
            ->assertSee('1.2 mm')
            ->assertSee('Total Estimasi:')
            ->assertDontSee('Sisa Pembayaran')
            ->assertDontSee('Belum Dibayar');
    }

    public function test_manual_packages_render_manual_badge(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $manual = $flow->saveWorkPackage($service, [
            'title' => 'PEKERJAAN MANUAL',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Manual', 'quantity' => 1, 'unit_price' => 50000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$manual->id]);
        app(EstimateService::class)->markSent($estimate, 'test');

        $this->get(route('estimates.print', $estimate->fresh()))
            ->assertOk()
            ->assertSee('PEKERJAAN MANUAL')
            ->assertSee('manual');
    }

    public function test_legacy_flat_estimate_renders_without_group_headers(): void
    {
        $service = $this->makeService();
        $estimate = app(EstimateService::class)->createDraft($service, [], [
            ['item_type' => 'labor', 'description' => 'Jasa Legacy', 'quantity' => 1, 'unit_price' => 90000],
        ]);
        app(EstimateService::class)->markSent($estimate, 'test');

        $estimate = $estimate->fresh();
        $this->assertSame(0, $estimate->groups()->count());

        $this->get(route('estimates.print', $estimate))
            ->assertOk()
            ->assertSee('Jasa Legacy')
            ->assertDontSee('dari checklist', false);
    }
}

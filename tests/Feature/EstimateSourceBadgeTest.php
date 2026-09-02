<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;

/**
 * Source badges: the customer must see WHY work is recommended.
 * Badge rendering lives in the public estimate view; this test verifies the
 * underlying data that drives the badges for grouped estimates.
 */
class EstimateSourceBadgeTest extends WorkshopFlowTestCase
{
    protected function makeEstimateWithGroupedPackages(): ServiceEstimate
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        // Critical finding → package.
        [$findingA, $packageA] = $this->makeCriticalPad($flow, $service);
        // Attention finding → package.
        $oil = $this->makeAttentionOil($flow, $service);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$packageA->id, $oil->id]);

        return $estimate;
    }

    protected function makeCriticalPad(WorkshopFlowService $flow, $service): array
    {
        [, , $pad] = $this->makeChecklistData();
        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical', 'comment' => 'Kampas hampir habis', 'measurement_value' => '1.2', 'measurement_unit' => 'mm'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->where('severity', 'critical')->firstOrFail();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM DEPAN',
            'service_finding_id' => $finding->id,
            'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Ganti Kampas Rem', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas Rem Depan', 'quantity' => 1, 'unit_price' => 180000],
        ]);

        return [$finding, $package];
    }

    protected function makeAttentionOil(WorkshopFlowService $flow, $service): ServiceWorkPackage
    {
        [$oil] = $this->makeChecklistData();
        app(ObservationService::class)->saveCheckResults($service, [
            $oil->id => ['condition_status' => 'attention'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->where('severity', 'attention')->firstOrFail();

        return $flow->saveWorkPackage($service, [
            'title' => 'GANTI OLI MESIN',
            'service_finding_id' => $finding->id,
            'standard_minutes' => 15,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Ganti Oli', 'quantity' => 1, 'unit_price' => 30000],
            ['item_type' => 'part', 'description' => 'Oli Mesin', 'quantity' => 1, 'unit_price' => 120000],
        ]);
    }

    public function test_groups_carry_severity_for_badge_rendering(): void
    {
        $estimate = $this->makeEstimateWithGroupedPackages();

        $criticalGroup = $estimate->groups()->where('title', 'GANTI KAMPAS REM DEPAN')->firstOrFail();
        $attentionGroup = $estimate->groups()->where('title', 'GANTI OLI MESIN')->firstOrFail();

        // The public view maps severity_snapshot → "dari checklist kritis"/
        // "dari checklist perlu perhatian" badges.
        $this->assertSame('critical', $criticalGroup->severity_snapshot);
        $this->assertSame('attention', $attentionGroup->severity_snapshot);
    }

    public function test_group_links_back_to_finding_number(): void
    {
        $estimate = $this->makeEstimateWithGroupedPackages();

        $groups = $estimate->groups()->with('finding')->get();
        $this->assertSame(2, $groups->count());

        foreach ($groups as $group) {
            $this->assertNotNull($group->finding);
            $this->assertMatchesRegularExpression('/^FND-\d{6}-\d{4}$/', $group->finding->finding_number);
        }
    }

    public function test_manual_group_badge_data_is_null(): void
    {
        $service = $this->makeService();

        // Manual package (no finding) — badge falls back to "manual".
        $manualPackage = app(WorkshopFlowService::class)->saveWorkPackage($service, [
            'title' => 'PEKERJAAN MANUAL',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Manual', 'quantity' => 1, 'unit_price' => 50000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$manualPackage->id]);
        $group = $estimate->groups()->firstOrFail();

        $this->assertNull($group->severity_snapshot);
        $this->assertNull($group->service_finding_id);
    }

    public function test_public_page_renders_group_badges(): void
    {
        $service = $this->makeService();
        $estimate = $this->makeEstimateWithGroupedPackages();
        app(EstimateService::class)->markSent($estimate, 'test');

        $this->get(route('public.estimate.show', $estimate->fresh()->public_token))
            ->assertOk()
            ->assertSee('dari checklist kritis')
            ->assertSee('dari checklist perlu perhatian')
            ->assertSee('GANTI KAMPAS REM DEPAN')
            ->assertSee('GANTI OLI MESIN');
    }

    public function test_total_equals_sum_of_groups(): void
    {
        $estimate = $this->makeEstimateWithGroupedPackages();

        $sum = round((float) $estimate->groups()->sum('grand_total'), 2);
        $this->assertEqualsWithDelta($sum, (float) $estimate->grand_total, 0.01);
    }
}

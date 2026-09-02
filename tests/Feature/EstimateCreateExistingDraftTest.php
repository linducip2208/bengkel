<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Services\EstimateService;
use App\Services\WorkshopFlowService;

/**
 * Existing draft: builder continues the SAME draft (same PK), search shows
 * "Lanjutkan Draft", duplicate estimates are never created.
 */
class EstimateCreateExistingDraftTest extends WorkshopFlowTestCase
{
    protected function makeDraftService(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI OLI', 'standard_minutes' => 15,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Oli', 'quantity' => 1, 'unit_price' => 30000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);

        return [$service, $flow, $package, $estimate->fresh()];
    }

    public function test_search_shows_lanjutkan_draft_action(): void
    {
        [$service] = $this->makeDraftService();

        // Default filter hides drafts; the "Ada Draft" quick filter surfaces them.
        $noneIds = collect($this->get(route('estimates.service-search', ['q' => $service->job_no]))->json('results'))->pluck('id');
        $this->assertFalse($noneIds->contains($service->id), 'Default filter = Belum Ada Estimasi.');

        $results = collect($this->get(route('estimates.service-search', ['q' => $service->job_no, 'filter' => 'draft']))->json('results'));
        $row = $results->firstWhere('id', $service->id);

        $this->assertNotNull($row);
        $this->assertSame('continue_draft', $row['action']);
        $this->assertSame('Lanjutkan Draft', $row['action_label']);
        $this->assertSame('draft', $row['estimate']['status']);
        $this->assertTrue($row['has_active_estimate']);
    }

    public function test_search_shows_view_action_for_sent_estimate(): void
    {
        [$service] = $this->makeDraftService();
        app(EstimateService::class)->markSent(ServiceEstimate::where('service_id', $service->id)->firstOrFail(), 'test');

        $results = collect($this->get(route('estimates.service-search', ['q' => $service->job_no, 'filter' => 'waiting']))->json('results'));
        $row = $results->firstWhere('id', $service->id);

        $this->assertSame('view', $row['action']);
        $this->assertSame('Lihat Estimasi', $row['action_label']);
    }

    public function test_create_page_shows_continue_draft_banner(): void
    {
        [$service, , , $estimate] = $this->makeDraftService();

        $this->get(route('estimates.create', ['service_id' => $service->id]))
            ->assertOk()
            ->assertSee('Lanjutkan Draft')
            ->assertSee($estimate->estimate_number)
            ->assertSee('bukan membuat baru');
    }

    public function test_repeated_saves_reuse_the_same_draft(): void
    {
        [$service, , $package] = $this->makeDraftService();
        $draft = ServiceEstimate::where('service_id', $service->id)->firstOrFail();

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('services.estimates.store', $service), [
                'redirect_to' => 'estimates',
                'packages' => [$package->id],
                'items' => [
                    ['item_type' => 'other', 'description' => 'Item '.$i, 'quantity' => 1, 'unit_price' => 10000 * ($i + 1)],
                ],
            ])->assertRedirect(route('estimates.index'));
        }

        $this->assertSame(1, ServiceEstimate::where('service_id', $service->id)->count());
        $this->assertSame($draft->id, ServiceEstimate::where('service_id', $service->id)->firstOrFail()->id);
        $this->assertSame(ServiceEstimate::STATUS_DRAFT, ServiceEstimate::where('service_id', $service->id)->firstOrFail()->status);
    }

    public function test_approved_estimate_requires_revision_instead_of_duplicate(): void
    {
        [$service, $flow, $package] = $this->makeDraftService();
        $estimate = ServiceEstimate::where('service_id', $service->id)->firstOrFail();
        app(EstimateService::class)->markSent($estimate, 'test');
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        // Builder shows state card, not a duplicate builder.
        $this->get(route('estimates.create', ['service_id' => $service->id]))
            ->assertOk()
            ->assertSee('Buat Revisi')
            ->assertDontSee('Simpan Draft');

        // Correct way: revision creates a NEW document version.
        $countBefore = ServiceEstimate::count();
        $revision = app(EstimateService::class)->revise($estimate->fresh(), [], [], 'Harga berubah');
        $this->assertSame($countBefore + 1, ServiceEstimate::count());
        $this->assertSame($estimate->id, $revision->previous_estimate_id);
        $this->assertSame(2, $revision->version);
        $this->assertSame(ServiceEstimate::STATUS_DRAFT, $revision->status);
    }
}

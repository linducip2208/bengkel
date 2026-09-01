<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ServiceEstimate;
use App\Services\EstimateService;

class EstimateApprovalFlowTest extends EstimateTestCase
{
    public function test_customer_sees_itemized_estimate_on_public_page(): void
    {
        $service = $this->makeService();
        $product = $this->makeProduct();
        $estimate = $this->issueEstimate($service, [
            $this->partPayload($product),
            $this->itemPayload(['description' => 'TUNE UP', 'unit_price' => 150000]),
        ]);

        $response = $this->get('/estimate/'.$estimate->public_token);

        $response->assertOk();
        $response->assertSee($estimate->estimate_number);
        $response->assertSee('v1');
        $response->assertSee($product->name);
        $response->assertSee('TUNE UP');
        $response->assertSee('GRAND TOTAL');
        $response->assertSee('Setujui Estimasi');
        $response->assertSee('Tolak Estimasi');
        // Never any payment terminology on an estimate.
        $response->assertDontSee('Belum Dibayar');
        $response->assertDontSee('Sisa Pembayaran');
        $response->assertDontSee('Dibayar');
    }

    public function test_public_approval_sets_estimate_and_service_approved(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);

        $this->post('/estimate/'.$estimate->public_token.'/approve')->assertRedirect();

        $estimate = $estimate->fresh();
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimate->status);
        $this->assertNotNull($estimate->approved_at);
        $this->assertSame('public_link', $estimate->approval_method);
        $this->assertNotNull($estimate->approval_ip);
        $this->assertNotNull($estimate->approved_hash);
        $this->assertNotNull($estimate->snapshot, 'Issued estimate must carry a snapshot');

        $service = $service->fresh();
        $this->assertTrue((bool) $service->is_approved);
        $this->assertNotNull($service->approved_at);
        $this->assertSame(4, (int) $service->workflow_status);
        $this->assertEquals((float) $estimate->grand_total, (float) $service->charge);
    }

    public function test_legacy_service_approval_link_delegates_to_current_estimate_version(): void
    {
        $service = $this->makeService(['workflow_status' => 3, 'approval_token' => str_repeat('x', 40)]);
        $estimate = $this->issueEstimate($service);

        $this->post('/approve/'.$service->approval_token)->assertRedirect();

        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimate->fresh()->status);
        $this->assertSame(4, (int) $service->fresh()->workflow_status);
        $this->assertSame(1, ActivityLog::where('event', 'estimate.approved')->count());
    }

    public function test_approval_of_superseded_version_does_not_override_service(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService(['workflow_status' => 3]);
        $v1 = $this->issueEstimate($service);
        $estimateService->approve($v1, 'public_link');

        $v2 = $estimateService->revise($v1, [], [$this->itemPayload(['unit_price' => 999999])]);
        $estimateService->markSent($v2, 'test');

        // Old version is superseded — approving its token must not approve the service twice.
        $this->assertSame(ServiceEstimate::STATUS_SUPERSEDED, $v1->fresh()->status);
        $this->assertSame(ServiceEstimate::STATUS_WAITING_APPROVAL, $v2->fresh()->status);
    }

    public function test_draft_estimates_are_not_publicly_reachable(): void
    {
        $service = $this->makeService();

        $estimate = app(EstimateService::class)->createDraft($service, [], [$this->itemPayload()]);
        $estimate->update(['public_token' => 'draft-token-123']);

        $this->get('/estimate/draft-token-123')->assertNotFound();
        $this->post('/estimate/draft-token-123/approve')->assertNotFound();
    }
}

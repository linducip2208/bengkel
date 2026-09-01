<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ServiceEstimate;
use App\Services\EstimateService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EstimateRejectTest extends EstimateTestCase
{
    public function test_rejection_records_reason_and_keeps_history(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $total = (string) $estimate->grand_total;

        $this->post('/estimate/'.$estimate->public_token.'/reject', ['reason' => 'Harga tidak sesuai budget'])->assertRedirect();

        $estimate = $estimate->fresh();
        $this->assertSame(ServiceEstimate::STATUS_REJECTED, $estimate->status);
        $this->assertNotNull($estimate->rejected_at);
        $this->assertSame('Harga tidak sesuai budget', $estimate->rejection_reason);
        $this->assertSame($total, (string) $estimate->grand_total, 'Rejected estimate content must stay intact');
        $this->assertSame(1, ActivityLog::where('event', 'estimate.rejected')->count());
    }

    public function test_rejected_estimate_can_be_revised(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $estimateService->reject($estimate, 'Terlalu mahal');

        $revision = $estimateService->revise($estimate, [], [$this->itemPayload(['unit_price' => 300000])]);

        $this->assertSame(2, $revision->version);
        $this->assertSame(ServiceEstimate::STATUS_DRAFT, $revision->status);
        $this->assertSame(ServiceEstimate::STATUS_SUPERSEDED, $estimate->fresh()->status);
    }

    public function test_rejection_via_legacy_link_keeps_service_waiting(): void
    {
        $service = $this->makeService(['workflow_status' => 3, 'approval_token' => str_repeat('r', 40)]);
        $this->issueEstimate($service);

        $this->post('/reject/'.$service->approval_token, ['reason' => 'Nanti dulu'])->assertRedirect();

        $service = $service->fresh();
        $this->assertNull($service->cancelled_at, 'Legacy reject with estimates must not cancel the service');
        $this->assertSame(3, (int) $service->workflow_status);
    }

    public function test_cannot_reject_approved_estimate(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $estimateService->approve($estimate, 'public_link');

        $this->expectException(HttpException::class);
        $estimateService->reject($estimate, 'terlambat');
    }
}

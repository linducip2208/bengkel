<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ServiceEstimate;
use App\Services\EstimateService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Repeat approve/reject clicks must be idempotent: exactly one event,
 * exactly one state transition, no duplicate history.
 */
class EstimateApprovalIdempotencyTest extends EstimateTestCase
{
    public function test_repeated_approval_creates_only_one_event(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);

        $this->post('/estimate/'.$estimate->public_token.'/approve')->assertRedirect();
        $this->post('/estimate/'.$estimate->public_token.'/approve')->assertRedirect();
        $this->post('/estimate/'.$estimate->public_token.'/approve')->assertRedirect();

        $this->assertSame(1, ActivityLog::where('event', 'estimate.approved')->count());
        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimate->fresh()->status);
        $this->assertSame(1, ServiceEstimate::where('service_id', $service->id)->where('status', ServiceEstimate::STATUS_APPROVED)->count());
    }

    public function test_service_timestamps_and_charge_not_rewritten_on_retry(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);

        $this->post('/estimate/'.$estimate->public_token.'/approve');
        $firstApprovedAt = $service->fresh()->approved_at;
        $this->assertNotNull($firstApprovedAt);

        $this->post('/estimate/'.$estimate->public_token.'/approve');

        $this->assertEquals($firstApprovedAt->toIso8601String(), $service->fresh()->approved_at?->toIso8601String());
    }

    public function test_repeated_rejection_creates_only_one_event(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);

        $this->post('/estimate/'.$estimate->public_token.'/reject', ['reason' => 'Terlalu mahal'])->assertRedirect();
        $this->post('/estimate/'.$estimate->public_token.'/reject')->assertRedirect();

        $this->assertSame(1, ActivityLog::where('event', 'estimate.rejected')->count());
        $this->assertSame(ServiceEstimate::STATUS_REJECTED, $estimate->fresh()->status);
        $this->assertSame('Terlalu mahal', $estimate->fresh()->rejection_reason, 'Second reject must not overwrite the first reason');
    }

    public function test_rejection_does_not_cancel_or_delete_service(): void
    {
        $service = $this->makeService(['workflow_status' => 3]);
        $estimate = $this->issueEstimate($service);

        $this->post('/estimate/'.$estimate->public_token.'/reject');

        $service = $service->fresh();
        $this->assertNull($service->cancelled_at, 'Service must NOT be cancelled on estimate rejection');
        $this->assertNull($service->deleted_at);
        $this->assertSame(3, (int) $service->workflow_status, 'Service stays in Waiting Approval for revision');
        $this->assertNotNull(ServiceEstimate::find($estimate->id), 'Estimate must NOT be deleted');
        $this->assertNull($estimate->fresh()->deleted_at);
    }

    public function test_cannot_approve_after_converted(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $estimateService->approve($estimate, 'public_link');
        $estimateService->convertToInvoice($estimate);

        $this->expectException(HttpException::class);

        $estimateService->approve($estimate, 'public_link');
    }
}

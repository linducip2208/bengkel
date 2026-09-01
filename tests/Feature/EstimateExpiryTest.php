<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ServiceEstimate;
use App\Services\EstimateService;

class EstimateExpiryTest extends EstimateTestCase
{
    public function test_lapsed_estimate_is_marked_expired_by_scheduler(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $estimate->forceFill(['valid_until' => now()->subDay()->toDateString()])->save();

        $count = app(EstimateService::class)->expireLapsed();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame(ServiceEstimate::STATUS_EXPIRED, $estimate->fresh()->status);
        $this->assertSame(1, ActivityLog::where('event', 'estimate.expired')->where('subject_id', $estimate->id)->count());
        $this->assertNull($estimate->fresh()->deleted_at, 'Expired estimates are never auto-deleted');
    }

    public function test_approved_estimates_never_expire(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $estimateService->approve($estimate, 'public_link');
        $estimate->forceFill(['valid_until' => now()->subYear()->toDateString()])->save();

        app(EstimateService::class)->expireLapsed();

        $this->assertSame(ServiceEstimate::STATUS_APPROVED, $estimate->fresh()->status);
    }

    public function test_customer_cannot_approve_expired_estimate_via_public_link(): void
    {
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $estimate->forceFill(['valid_until' => now()->subDay()->toDateString()])->save();

        $response = $this->post('/estimate/'.$estimate->public_token.'/approve');

        $response->assertStatus(409);
        $this->assertSame(ServiceEstimate::STATUS_WAITING_APPROVAL, $estimate->fresh()->status);
    }

    public function test_expired_estimate_can_be_revised(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $this->issueEstimate($service);
        $estimate->forceFill(['valid_until' => now()->subDay()->toDateString()])->save();
        $estimateService->expireLapsed();
        $this->assertSame(ServiceEstimate::STATUS_EXPIRED, $estimate->fresh()->status);

        $revision = $estimateService->revise($estimate, [], [$this->itemPayload()], 'Perpanjangan masa berlaku');

        $this->assertSame(2, $revision->version);
        $this->assertSame(ServiceEstimate::STATUS_DRAFT, $revision->status);
        $this->assertNull($estimate->fresh()->deleted_at);
    }
}

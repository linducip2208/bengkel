<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([RequirePair::class, PreventRequestForgery::class]);
    }

    public function test_customer_can_approve_only_waiting_estimate_and_retry_is_idempotent(): void
    {
        $customer = Customer::create(['name' => 'Approval Customer']);
        $service = Service::create([
            'customer_id' => $customer->id,
            'job_no' => 'BP-'.uniqid(),
            'title' => 'Pengerjaan tambahan',
            'service_date' => now(),
            'workflow_status' => 3,
            'approval_token' => str_repeat('b', 40),
        ]);

        $this->post('/approve/'.$service->approval_token)->assertRedirect();
        $this->post('/approve/'.$service->approval_token)->assertRedirect();

        $this->assertSame(4, (int) $service->fresh()->workflow_status);
        $this->assertSame(1, ActivityLog::where('event', 'estimate.approved')->count());
    }

    public function test_customer_cannot_approve_estimate_after_work_started(): void
    {
        $customer = Customer::create(['name' => 'Late Approval Customer']);
        $service = Service::create([
            'customer_id' => $customer->id,
            'job_no' => 'BP-'.uniqid(),
            'title' => 'Terlambat approval',
            'service_date' => now(),
            'workflow_status' => 5,
            'approval_token' => str_repeat('c', 40),
        ]);

        $this->post('/approve/'.$service->approval_token)->assertStatus(409);
        $this->assertFalse((bool) $service->fresh()->is_approved);
    }
}

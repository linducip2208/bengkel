<?php

namespace Tests\Feature;

use App\Models\ServiceObservationPoint;
use App\Services\InvoiceService;
use App\Services\WorkshopProgressService;
use Illuminate\Validation\ValidationException;

class WorkshopClosingIntegrityTest extends WorkshopFlowTestCase
{
    public function test_modern_service_cannot_use_generic_invoice_form(): void
    {
        $service = $this->makeService();
        [$point] = $this->makeChecklistData();
        ServiceObservationPoint::create([
            'service_id' => $service->id,
            'observation_point_id' => $point->id,
            'condition_status' => 'not_checked',
        ]);

        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->create([
            'invoice_type' => 'service',
            'service_id' => $service->id,
            'customer_id' => $service->customer_id,
            'invoice_date' => now()->toDateString(),
            'items' => [['description' => 'Bypass', 'quantity' => 1, 'unit_price' => 1]],
        ]);
    }

    public function test_progress_keeps_payment_and_release_separate_from_invoice(): void
    {
        $service = $this->makeService();
        $progress = app(WorkshopProgressService::class)->calculate($service);

        $this->assertSame('checklist', $progress['current_step']);
        $this->assertSame(WorkshopProgressService::BLOCKED, $progress['steps']['payment']['state']);
        $this->assertSame(WorkshopProgressService::BLOCKED, $progress['steps']['release']['state']);
    }
}

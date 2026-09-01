<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\ServiceEstimate;
use App\Services\EstimateService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EstimateToInvoiceTest extends EstimateTestCase
{
    public function test_approved_estimate_converts_to_invoice_with_copied_items(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $product = $this->makeProduct('KOPLING SET', 1600000);

        $estimate = $estimateService->createDraft($service, [], [
            $this->partPayload($product, ['quantity' => 1]),
            $this->itemPayload(['description' => 'JASA O/H KOPLING', 'unit_price' => 400000]),
        ]);
        $estimateService->markSent($estimate, 'test');
        $estimateService->approve($estimate, 'public_link');

        $invoice = $estimateService->convertToInvoice($estimate);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{4}$/', $invoice->invoice_number);
        $this->assertSame($service->id, $invoice->service_id);
        $this->assertSame($service->customer_id, $invoice->customer_id);
        $this->assertSame($service->vehicle_id, $invoice->vehicle_id);
        $this->assertSame($estimate->id, (int) $invoice->service_estimate_id);
        $this->assertCount(2, $invoice->items()->withoutGlobalScopes()->get());
        $this->assertEquals((float) $estimate->grand_total, (float) $invoice->grand_total);

        // Estimate stays a separate historical document — NOT mutated into an invoice.
        $estimate = $estimate->fresh();
        $this->assertSame(ServiceEstimate::STATUS_CONVERTED, $estimate->status);
        $this->assertNotNull($estimate->converted_at);
        $this->assertCount(2, $estimate->items()->get());
        $this->assertNull($estimate->deleted_at);
    }

    public function test_conversion_is_idempotent(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');
        $estimateService->approve($estimate, 'public_link');

        $first = $estimateService->convertToInvoice($estimate);
        $second = $estimateService->convertToInvoice($estimate);

        $this->assertSame($first->id, $second->id, 'Retry must return the SAME invoice');
        $this->assertSame(1, Invoice::withoutGlobalScopes()->where('service_estimate_id', $estimate->id)->count());
        $this->assertSame(1, Invoice::withoutGlobalScopes()->where('service_id', $service->id)->count());
    }

    public function test_double_post_of_convert_route_creates_one_invoice(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');
        $estimateService->approve($estimate, 'public_link');

        $this->post('/estimates/'.$estimate->id.'/convert-invoice')->assertRedirect();
        $this->post('/estimates/'.$estimate->id.'/convert-invoice')->assertRedirect();

        $this->assertSame(1, Invoice::withoutGlobalScopes()->where('service_estimate_id', $estimate->id)->count());
    }

    public function test_waiting_estimate_cannot_be_converted(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');

        $this->expectException(HttpException::class);
        $estimateService->convertToInvoice($estimate);
    }

    public function test_unapproved_estimate_rejected_at_route_level(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');

        $response = $this->post('/estimates/'.$estimate->id.'/convert-invoice');

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, Invoice::count());
    }
}

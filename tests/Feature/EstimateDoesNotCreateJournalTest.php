<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Services\EstimateService;

/**
 * Estimates never touch accounting: no journal, no AR, no ledger rows.
 * Accounting starts only at the INVOICE.
 */
class EstimateDoesNotCreateJournalTest extends EstimateTestCase
{
    private function journalCount(): int
    {
        return JournalEntry::count();
    }

    public function test_creating_estimate_creates_no_journal(): void
    {
        $service = $this->makeService();
        $before = $this->journalCount();

        app(EstimateService::class)->createDraft($service, [], [$this->itemPayload(['unit_price' => 5000000])]);

        $this->assertSame($before, $this->journalCount(), 'Draft estimate must not create accounting entries');
    }

    public function test_sending_and_approving_estimate_create_no_journal_or_ar(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $before = $this->journalCount();

        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload(['unit_price' => 13925000])]);
        $estimateService->markSent($estimate, 'test');
        $estimateService->approve($estimate, 'public_link');

        $this->assertSame($before, $this->journalCount(), 'Approval is commercial, not accounting');
        $this->assertSame(0, Invoice::count(), 'No invoice may be created by estimate approval');
    }

    public function test_rejecting_and_expiring_create_no_journal(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $before = $this->journalCount();

        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload()]);
        $estimateService->markSent($estimate, 'test');
        $estimateService->reject($estimate, 'no');
        $estimateService->expireLapsed();

        $this->assertSame($before, $this->journalCount());
    }

    public function test_conversion_creates_invoice_and_journal_together(): void
    {
        $estimateService = app(EstimateService::class);
        $service = $this->makeService();
        $before = $this->journalCount();

        $estimate = $estimateService->createDraft($service, [], [$this->itemPayload(['unit_price' => 1000000])]);
        $estimateService->markSent($estimate, 'test');
        $estimateService->approve($estimate, 'public_link');

        $this->assertSame($before, $this->journalCount());
        $this->assertSame(0, Invoice::count());

        $invoice = $estimateService->convertToInvoice($estimate);

        $this->assertGreaterThan($before, $this->journalCount(), 'Accounting begins with the invoice, not the estimate');
        $this->assertSame(1, Invoice::count());
    }
}

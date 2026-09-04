<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\ServiceEstimate;
use App\Models\ServiceFinding;
use App\Models\ServiceWorkPackage;
use App\Models\ServiceWorkQcCheck;
use App\Models\ServiceWorkTask;
use App\Models\StockRecord;
use App\Services\EstimateService;
use App\Services\ObservationService;
use App\Services\WorkshopFlowService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * N. Invoice conversion copies APPROVED groups only.
 * O. Invoice retry → no duplicate invoice.
 * L/M. Estimate alone → no stock change, no journal.
 */
class EstimateToInvoiceApprovedItemsOnlyTest extends WorkshopFlowTestCase
{
    protected function makePartiallyApprovedEstimate(): array
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);
        $product = $this->makeProduct();

        $brakeType = ObservationType::create(['observation_type' => 'REM']);
        $pad = ObservationPoint::create(['observation_type_id' => $brakeType->id, 'observation_point' => 'Kampas Rem']);

        app(ObservationService::class)->saveCheckResults($service, [
            $pad->id => ['condition_status' => 'critical'],
        ]);
        $finding = ServiceFinding::where('service_id', $service->id)->firstOrFail();

        $packageA = $flow->saveWorkPackage($service, [
            'title' => 'GANTI KAMPAS REM (APPROVED)', 'service_finding_id' => $finding->id, 'standard_minutes' => 30,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Kampas', 'quantity' => 1, 'unit_price' => 75000],
            ['item_type' => 'part', 'description' => 'Kampas Rem', 'quantity' => 1, 'unit_price' => 180000, 'product_id' => $product->id],
        ]);

        $packageB = $flow->saveWorkPackage($service, [
            'title' => 'GANTI OLI (REJECTED)', 'standard_minutes' => 15,
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa Oli', 'quantity' => 1, 'unit_price' => 30000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$packageA->id, $packageB->id]);
        app(EstimateService::class)->markSent($estimate, 'test');
        $estimate = $estimate->fresh();

        $flow->submitGroupDecisions($estimate, [
            ['group_id' => $estimate->groups()->where('service_work_package_id', $packageA->id)->firstOrFail()->id, 'decision' => 'approved'],
            ['group_id' => $estimate->groups()->where('service_work_package_id', $packageB->id)->firstOrFail()->id, 'decision' => 'rejected'],
        ], 'public_link');

        $task = ServiceWorkTask::where('service_work_package_id', $packageA->id)->firstOrFail();
        $flow->finishTask($task);
        $flow->submitQc($packageA->fresh(), ServiceWorkQcCheck::RESULT_PASSED, 'Lulus');

        return [$estimate->fresh(), $service, $packageA, $packageB];
    }

    public function test_invoice_contains_approved_items_only(): void
    {
        [$estimate, $service] = $this->makePartiallyApprovedEstimate();

        $invoice = app(EstimateService::class)->convertToInvoice($estimate);

        $invoice->load('items');
        // Critical assertion N: only approved lines reached the invoice.
        $this->assertEqualsWithDelta(255000.0, (float) $invoice->grand_total, 0.01);
        $this->assertEqualsWithDelta(255000.0, (float) $invoice->total_amount, 0.01);
        $this->assertSame(2, $invoice->items->count());
        $this->assertNull($invoice->items->firstWhere('description', 'Jasa Oli'), 'Rejected work must never be invoiced.');
        $this->assertNotNull($invoice->items->firstWhere('description', 'Jasa Kampas'));
        $this->assertNotNull($invoice->items->firstWhere('description', 'Kampas Rem'));
    }

    public function test_invoice_retry_creates_no_duplicate(): void
    {
        [$estimate] = $this->makePartiallyApprovedEstimate();
        $estimateService = app(EstimateService::class);

        $invoice1 = $estimateService->convertToInvoice($estimate);
        $invoice2 = $estimateService->convertToInvoice($estimate);
        $invoice3 = $estimateService->convertToInvoice($estimate);

        // Critical assertion O: retry returns the same invoice.
        $this->assertSame($invoice1->id, $invoice2->id);
        $this->assertSame($invoice1->id, $invoice3->id);
        $this->assertSame(1, Invoice::where('service_estimate_id', $estimate->id)->count());
    }

    public function test_service_charge_moves_to_invoice_amount_after_conversion(): void
    {
        [$estimate, $service] = $this->makePartiallyApprovedEstimate();

        app(EstimateService::class)->convertToInvoice($estimate);

        $service = $service->fresh();
        $invoice = Invoice::where('service_estimate_id', $estimate->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $invoice->grand_total, (float) $service->charge, 0.01);
        $this->assertEqualsWithDelta(255000.0, (float) $service->charge, 0.01);
    }

    public function test_estimate_creation_creates_no_stock_mutation_or_journal(): void
    {
        $service = $this->makeService();
        $product = $this->makeProduct('FILTER OLI FLOW', 50000);
        $flow = app(WorkshopFlowService::class);

        $stockBefore = (float) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity');
        $journalsBefore = JournalEntry::count();

        $package = $flow->saveWorkPackage($service, [
            'title' => 'GANTI FILTER',
        ], [
            ['item_type' => 'part', 'description' => 'Filter', 'quantity' => 1, 'unit_price' => 50000, 'product_id' => $product->id],
        ]);
        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');

        // Critical assertions L/M: estimate alone mutates nothing.
        $stockAfter = (float) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity');
        $this->assertSame($stockBefore, $stockAfter, 'Estimate must not decrement stock.');
        $this->assertSame($journalsBefore, JournalEntry::count(), 'Estimate must not create journals.');

        // Reservation from approval is a RESERVATION, not a stock decrement.
        $flow->submitGroupDecisions($estimate->fresh(), [
            ['group_id' => $estimate->groups()->firstOrFail()->id, 'decision' => 'approved'],
        ], 'public_link');

        $stockAfterApproval = (float) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity');
        $this->assertSame($stockBefore, $stockAfterApproval, 'Approval must only reserve — not decrement.');
        $this->assertSame($journalsBefore, JournalEntry::count());
    }

    public function test_fully_approved_estimate_converts_entirely(): void
    {
        [$estimate, , , $packageB] = $this->makePartiallyApprovedEstimate();
        $flow = app(WorkshopFlowService::class);

        // Flip the rejected group to approved → full approval conversion.
        $rejectedGroup = $estimate->groups()->where('customer_decision', 'rejected')->firstOrFail();
        $rejectedGroup->forceFill(['customer_decision' => 'approved', 'decided_at' => now()])->save();
        app(EstimateService::class)->recalculateApprovedAmounts($estimate);
        $estimate->forceFill(['status' => ServiceEstimate::STATUS_APPROVED, 'decision_status' => ServiceEstimate::DECISION_APPROVED])->save();

        $flow->createTasksForApprovedGroups($estimate->fresh());
        $taskB = ServiceWorkTask::where('service_work_package_id', $packageB->id)->firstOrFail();
        $flow->finishTask($taskB);
        $flow->submitQc($packageB->fresh(), ServiceWorkQcCheck::RESULT_PASSED, 'Lulus');

        $invoice = app(EstimateService::class)->convertToInvoice($estimate);
        $this->assertEqualsWithDelta(285000.0, (float) $invoice->grand_total, 0.01);
    }

    public function test_waiting_estimate_cannot_convert(): void
    {
        $service = $this->makeService();
        $flow = app(WorkshopFlowService::class);

        $package = $flow->saveWorkPackage($service, [
            'title' => 'WORK',
        ], [
            ['item_type' => 'labor', 'description' => 'Jasa', 'quantity' => 1, 'unit_price' => 50000],
        ]);

        $estimate = app(EstimateService::class)->createDraft($service, [], [], [$package->id]);
        app(EstimateService::class)->markSent($estimate, 'test');

        $this->expectException(HttpException::class);
        app(EstimateService::class)->convertToInvoice($estimate->fresh());
    }

    public function test_tasks_only_for_approved_and_package_status_after_conversion(): void
    {
        [$estimate, $service, $packageA, $packageB] = $this->makePartiallyApprovedEstimate();

        app(EstimateService::class)->convertToInvoice($estimate);

        // Approved package still has its task; rejected has none.
        $this->assertNotNull(ServiceWorkTask::where('service_work_package_id', $packageA->id)->first());
        $this->assertNull(ServiceWorkTask::where('service_work_package_id', $packageB->id)->first());
        $this->assertSame(ServiceWorkPackage::STATUS_REJECTED, $packageB->fresh()->status);
    }
}

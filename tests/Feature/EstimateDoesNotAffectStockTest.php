<?php

namespace Tests\Feature;

use App\Models\ServiceEstimate;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Services\EstimateService;

/**
 * Estimates are quotations: they must never reserve, decrement or otherwise
 * mutate stock. Only actual consumption (use-in-service / invoice) does.
 */
class EstimateDoesNotAffectStockTest extends EstimateTestCase
{
    public function test_creating_estimate_leaves_stock_untouched(): void
    {
        $product = $this->makeProduct('FILTER OLI', 120000);
        $service = $this->makeService();

        $stockBefore = (float) StockRecord::where('product_id', $product->id)->first()->quantity;
        $historyCountBefore = StockHistory::count();

        app(EstimateService::class)->createDraft($service, [], [
            $this->partPayload($product, ['quantity' => 10]),
            $this->partPayload($product, ['quantity' => 5]),
        ]);

        $this->assertEquals($stockBefore, (float) StockRecord::where('product_id', $product->id)->first()->quantity, 'Stock must NOT change when creating an estimate');
        $this->assertSame($historyCountBefore, StockHistory::count(), 'No stock history rows may be created');
    }

    public function test_sending_and_approving_estimate_leave_stock_untouched(): void
    {
        $product = $this->makeProduct('BUSI', 50000);
        $service = $this->makeService();

        $estimate = app(EstimateService::class)->createDraft($service, [], [
            $this->partPayload($product, ['quantity' => 4]),
        ]);
        $stockAfterCreate = (float) StockRecord::where('product_id', $product->id)->first()->quantity;

        app(EstimateService::class)->markSent($estimate, 'test');
        $stockAfterSend = (float) StockRecord::where('product_id', $product->id)->first()->quantity;

        app(EstimateService::class)->approve($estimate, 'public_link');
        $stockAfterApprove = (float) StockRecord::where('product_id', $product->id)->first()->quantity;

        $this->assertEquals($stockAfterCreate, $stockAfterSend);
        $this->assertEquals($stockAfterCreate, $stockAfterApprove);
        $this->assertEquals(100.0, $stockAfterApprove);
    }

    public function test_stock_mutations_only_happen_on_real_consumption(): void
    {
        $product = $this->makeProduct('MASTER KOPLING ATAS', 1200000);
        $service = $this->makeService();

        $estimate = app(EstimateService::class)->createDraft($service, [], [
            $this->partPayload($product, ['quantity' => 2]),
        ]);
        app(EstimateService::class)->markSent($estimate, 'test');
        app(EstimateService::class)->approve($estimate, 'public_link');

        $mutationsFromEstimate = StockHistory::where('product_id', $product->id)
            ->where('reference_type', ServiceEstimate::class)
            ->count();

        $this->assertSame(0, $mutationsFromEstimate, 'No stock mutation may reference an estimate');
    }

    public function test_revisions_do_not_touch_stock(): void
    {
        $product = $this->makeProduct('KOPLING SET', 1600000);
        $service = $this->makeService();
        $estimateService = app(EstimateService::class);

        $estimate = $estimateService->createDraft($service, [], [$this->partPayload($product, ['quantity' => 2])]);
        $estimateService->markSent($estimate, 'test');
        $stockBefore = (float) StockRecord::where('product_id', $product->id)->first()->quantity;

        $estimateService->revise($estimate, [], [$this->partPayload($product, ['quantity' => 99])], 'tambahan');

        $this->assertEquals($stockBefore, (float) StockRecord::where('product_id', $product->id)->first()->quantity);
    }
}

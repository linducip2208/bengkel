<?php

namespace Tests\Feature;

use App\Models\ServiceEstimateItem;
use App\Models\StockRecord;

class EstimateProductItemTest extends EstimateTestCase
{
    public function test_product_selection_persists_product_and_authoritative_price_without_changing_stock(): void
    {
        $normalUser = $this->makeUser('estimate_normal');
        $this->grantPermission($normalUser, 'estimates.create');
        $this->actingAs($normalUser);

        $service = $this->makeService();
        $product = $this->makeProduct('KAMPAS REM', 350000);
        $beforeStock = (float) StockRecord::query()->where('product_id', $product->id)->value('quantity');

        $this->post(route('services.estimates.store', $service), $this->storePayload([
            $this->partPayload($product, [
                'description' => 'KAMPAS REM PILIHAN',
                'quantity' => 2,
                'unit_price' => 1,
                'discount' => 10000,
            ]),
        ]))->assertRedirect();

        $item = ServiceEstimateItem::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertSame('KAMPAS REM PILIHAN', $item->description);
        $this->assertSame(2.0, (float) $item->quantity);
        $this->assertSame(350000.0, (float) $item->unit_price);
        $this->assertSame(690000.0, (float) $item->line_total);
        $this->assertSame($beforeStock, (float) StockRecord::query()->where('product_id', $product->id)->value('quantity'));
    }

    public function test_product_price_override_permission_allows_explicit_price(): void
    {
        $service = $this->makeService();
        $product = $this->makeProduct('KAMPAS REM', 350000);
        $this->grantPermission($this->user, 'pos.price_override');

        $this->post(route('services.estimates.store', $service), $this->storePayload([
            $this->partPayload($product, ['unit_price' => 375000]),
        ]))->assertRedirect();

        $this->assertSame(375000.0, (float) ServiceEstimateItem::query()->where('product_id', $product->id)->value('unit_price'));
    }
}

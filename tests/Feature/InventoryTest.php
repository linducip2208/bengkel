<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\SellReturn;
use App\Models\StockAdjustment;
use App\Models\StockRecord;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\RequirePair::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        ]);
    }

    private function makeUser(string $role = 'super_admin'): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function makeBranch(string $name): Branch
    {
        return Branch::create(['name' => $name, 'is_active' => true]);
    }

    private function makeProduct(Branch $branch): Product
    {
        $type = ProductType::create(['type' => 'Sparepart', 'slug' => 'sparepart', 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs', 'is_active' => true]);

        return Product::create([
            'product_no' => 'P-' . strtoupper(uniqid()),
            'code' => 'CODE-' . strtoupper(uniqid()),
            'name' => 'Spare Part ' . strtoupper(uniqid()),
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 75000,
            'cost_price' => 50000,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_stock_adjustment_approve_updates_stock_and_creates_history(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $branch = $this->makeBranch('Cabang A');
        $product = $this->makeProduct($branch);

        StockRecord::create([
            'product_id' => $product->id,
            'quantity' => 10,
            'minimum_stock' => 0,
            'branch_id' => $branch->id,
        ]);

        $response = $this->post('/stock-adjustments', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'new_quantity' => 25,
            'reason' => 'Hasil stock opname fisik',
        ]);

        $response->assertRedirect(route('stock-adjustments.index'));

        $adjustment = StockAdjustment::where('product_id', $product->id)->first();
        $this->assertNotNull($adjustment);
        $this->assertEquals('pending', $adjustment->status);
        $this->assertEquals(10, $adjustment->previous_quantity);
        $this->assertEquals(25, $adjustment->new_quantity);
        $this->assertEquals(15, $adjustment->quantity_change);

        // Approve
        $response = $this->post("/stock-adjustments/{$adjustment->id}/approve");
        $response->assertRedirect();

        $adjustment->refresh();
        $this->assertEquals('approved', $adjustment->status);

        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
        $this->assertEquals(25, $stock->quantity);

        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity_change' => 15,
            'new_stock' => 25,
        ]);
    }

    public function test_purchase_order_mark_received_creates_purchase_and_increments_stock(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $branch = $this->makeBranch('Cabang B');
        $supplier = Supplier::create(['name' => 'PT Sumber Suku Cadang']);
        $product = $this->makeProduct($branch);

        StockRecord::create([
            'product_id' => $product->id,
            'quantity' => 5,
            'minimum_stock' => 0,
            'branch_id' => $branch->id,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'order_date' => now()->toDateString(),
            'status' => 'sent',
            'subtotal' => 500000,
            'grand_total' => 500000,
            'created_by' => $admin->id,
        ]);

        $po->items()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 50000,
            'total_price' => 500000,
        ]);

        $response = $this->post("/purchase-orders/{$po->id}/mark-received");
        $response->assertRedirect(route('purchase-orders.show', $po));

        $po->refresh();
        $this->assertEquals('received', $po->status);

        $purchase = Purchase::withoutGlobalScopes()->where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($purchase);
        $this->assertEquals('received', $purchase->status);
        $this->assertEquals(500000, (float) $purchase->total_amount);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        // Stock incremented 5 -> 15
        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
        $this->assertEquals(15, $stock->quantity);

        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity_change' => 10,
            'new_stock' => 15,
        ]);
    }

    public function test_sell_return_increments_stock_back_and_creates_history(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);

        $branch = $this->makeBranch('Cabang C');
        $product = $this->makeProduct($branch);

        StockRecord::create([
            'product_id' => $product->id,
            'quantity' => 8,
            'minimum_stock' => 0,
            'branch_id' => $branch->id,
        ]);

        $customer = Customer::create(['name' => 'Pelanggan Retur']);

        $response = $this->post('/sell-returns', [
            'customer_id' => $customer->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Barang rusak',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 75000],
            ],
        ]);

        $response->assertRedirect();

        $sellReturn = SellReturn::where('customer_id', $customer->id)->first();
        $this->assertNotNull($sellReturn);
        $this->assertEquals('completed', $sellReturn->status);
        $this->assertEquals(150000, (float) $sellReturn->refund_amount);

        $this->assertDatabaseHas('sell_return_items', [
            'sell_return_id' => $sellReturn->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Stock incremented back 8 -> 10
        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
        $this->assertEquals(10, $stock->quantity);

        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'type' => 'sell_return',
            'quantity_change' => 2,
            'new_stock' => 10,
        ]);
    }
}

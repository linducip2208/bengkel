<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\SellReturn;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductService;
use App\Services\StockService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            RequirePair::class,
            PreventRequestForgery::class,
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
            'product_no' => 'P-'.strtoupper(uniqid()),
            'code' => 'CODE-'.strtoupper(uniqid()),
            'name' => 'Spare Part '.strtoupper(uniqid()),
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 75000,
            'cost_price' => 50000,
            'branch_id' => $branch->id,
        ]);
    }

    private function createProductWithInitialStock(int $initialStock, string $suffix): Product
    {
        $type = ProductType::create([
            'type' => 'Tipe '.$suffix,
            'slug' => 'tipe-'.strtolower($suffix),
            'is_active' => true,
        ]);
        $unit = ProductUnit::create([
            'name' => 'Unit '.$suffix,
            'abbreviation' => 'u-'.strtolower($suffix),
            'is_active' => true,
        ]);

        return app(ProductService::class)->create([
            'code' => 'INIT-'.$suffix,
            'name' => 'Produk '.$suffix,
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 50000,
            'cost_price' => 35000,
            'initial_stock' => $initialStock,
            'minimum_stock' => 2,
        ]);
    }

    public function test_create_product_with_initial_stock_four_results_in_four(): void
    {
        $product = $this->createProductWithInitialStock(4, 'FOUR');

        $this->assertSame(4.0, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertSame(1, StockHistory::where('product_id', $product->id)->where('type', 'initial')->count());
        $this->assertSame(1, StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->count());
    }

    public function test_create_product_with_initial_stock_ten_results_in_ten(): void
    {
        $product = $this->createProductWithInitialStock(10, 'TEN');

        $this->assertSame(10.0, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertSame(1, StockHistory::where('product_id', $product->id)->where('type', 'initial')->count());
        $this->assertSame(1, StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->count());
    }

    public function test_create_product_with_zero_initial_stock_stays_zero_without_fake_history(): void
    {
        $product = $this->createProductWithInitialStock(0, 'ZERO');

        $this->assertSame(0.0, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertSame(0, StockHistory::where('product_id', $product->id)->where('type', 'initial')->count());
        $this->assertSame(1, StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->count());
    }

    public function test_add_reduce_and_set_adjustments_apply_exactly_once(): void
    {
        $product = $this->createProductWithInitialStock(4, 'ADJUST');

        StockService::increment($product->id, 5, 'adjustment_add', 'Tambah lima');
        $this->assertSame(9.0, (float) $product->fresh('stockRecord')->current_stock);

        StockService::decrement($product->id, 2, 'adjustment_reduce', 'Kurangi dua');
        $this->assertSame(7.0, (float) $product->fresh('stockRecord')->current_stock);

        StockService::set($product->id, 20, 'opname', 'Set menjadi dua puluh');
        $this->assertSame(20.0, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'type' => 'opname',
            'quantity_change' => 13,
            'previous_stock' => 7,
            'new_stock' => 20,
        ]);
        $this->assertSame(1, StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->count());
    }

    public function test_product_initial_stock_is_not_doubled_when_created(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);
        $type = ProductType::create(['type' => 'Oli', 'slug' => 'oli-create', 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Liter', 'abbreviation' => 'ltr-create', 'is_active' => true]);

        $this->post('/products', [
            'code' => 'INIT-008',
            'name' => 'Produk Stok Awal',
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 50000,
            'cost_price' => 35000,
            'initial_stock' => 8,
            'minimum_stock' => 2,
        ])->assertRedirect(route('products.index'));

        $product = Product::where('code', 'INIT-008')->firstOrFail();
        $this->assertSame(8.0, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'quantity_change' => 8,
            'previous_stock' => 0,
            'new_stock' => 8,
            'type' => 'initial',
        ]);
    }

    public function test_api_product_create_applies_current_stock_as_initial_stock(): void
    {
        $admin = $this->makeUser('super_admin');
        $token = $admin->createToken('ci')->plainTextToken;
        $type = ProductType::create(['type' => 'Bearing', 'slug' => 'bearing-create', 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs-api', 'is_active' => true]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/products', [
                'code' => 'API-INIT-001',
                'name' => 'Produk Dari Api',
                'product_type_id' => $type->id,
                'unit_id' => $unit->id,
                'price' => 25000,
                'cost_price' => 15000,
                'current_stock' => 12,
                'minimum_stock' => 3,
            ]);
        if ($response->getStatusCode() !== 201) {
            $this->fail($response->getContent());
        }

        $product = Product::where('code', 'API-INIT-001')->firstOrFail();
        $this->assertSame(12.0, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'quantity_change' => 12,
            'previous_stock' => 0,
            'new_stock' => 12,
            'type' => 'initial',
        ]);
    }

    public function test_product_edit_sets_final_stock_and_records_only_the_difference(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);
        $branch = $this->makeBranch('Cabang Edit');
        $product = $this->makeProduct($branch);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 2, 'minimum_stock' => 1]);

        $this->put("/products/{$product->id}", [
            'code' => $product->code,
            'name' => $product->name,
            'product_type_id' => $product->product_type_id,
            'unit_id' => $product->unit_id,
            'supplier_id' => $product->supplier_id,
            'price' => $product->price,
            'cost_price' => $product->cost_price,
            'current_stock' => 8,
            'minimum_stock' => 3,
            'rack_location' => 'R-A1',
        ])->assertRedirect(route('products.index'));

        $product->refresh()->load('stockRecord');
        $this->assertSame(8.0, $product->current_stock);
        $this->assertSame(3.0, $product->minimum_stock);
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'quantity_change' => 6,
            'previous_stock' => 2,
            'new_stock' => 8,
            'type' => 'product_edit',
        ]);
        $this->assertSame(1, StockHistory::where('product_id', $product->id)->count());
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
                ['product_id' => $product->id, 'quantity' => 1.25, 'unit_price' => 75000],
            ],
        ]);

        $response->assertRedirect();

        $sellReturn = SellReturn::where('customer_id', $customer->id)->first();
        $this->assertNotNull($sellReturn);
        $this->assertEquals('completed', $sellReturn->status);
        $this->assertEquals(93750, (float) $sellReturn->refund_amount);

        $this->assertDatabaseHas('sell_return_items', [
            'sell_return_id' => $sellReturn->id,
            'product_id' => $product->id,
            'quantity' => 1.25,
        ]);

        // Fractional stock is restored precisely: 8 + 1.25 = 9.25.
        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
        $this->assertEquals(9.25, $stock->quantity);

        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'type' => 'sell_return',
            'quantity_change' => 1.25,
            'new_stock' => 9.25,
        ]);
    }
}

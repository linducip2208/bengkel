<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
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
            'quantity' => 10.5,
            'minimum_stock' => 0,
            'branch_id' => $branch->id,
        ]);

        $response = $this->post('/stock-adjustments', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'new_quantity' => 12.25,
            'reason' => 'Hasil stock opname fisik',
        ]);

        $response->assertRedirect(route('stock-adjustments.index'));

        $adjustment = StockAdjustment::where('product_id', $product->id)->first();
        $this->assertNotNull($adjustment);
        $this->assertEquals('pending', $adjustment->status);
        $this->assertEquals(10.5, $adjustment->previous_quantity);
        $this->assertEquals(12.25, $adjustment->new_quantity);
        $this->assertEquals(1.75, $adjustment->quantity_change);

        // Approve
        $response = $this->post("/stock-adjustments/{$adjustment->id}/approve");
        $response->assertRedirect();

        $adjustment->refresh();
        $this->assertEquals('approved', $adjustment->status);

        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
        $this->assertEquals(12.25, $stock->quantity);

        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity_change' => 1.75,
            'new_stock' => 12.25,
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
            'status' => 'approved',
            'subtotal' => 75000,
            'grand_total' => 75000,
            'created_by' => $admin->id,
        ]);

        $po->items()->create([
            'product_id' => $product->id,
            'quantity' => 1.5,
            'unit_price' => 50000,
            'total_price' => 75000,
        ]);

        $response = $this->post("/purchase-orders/{$po->id}/mark-received");
        $response->assertRedirect(route('purchase-orders.show', $po));

        $po->refresh();
        $this->assertEquals('received', $po->status);

        $purchase = Purchase::withoutGlobalScopes()->where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($purchase);
        $this->assertEquals('received', $purchase->status);
        $this->assertEquals(75000, (float) $purchase->total_amount);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 1.5,
        ]);

        // Decimal stock incremented 5 -> 6.5
        $stock = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
        $this->assertEquals(6.5, $stock->quantity);

        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity_change' => 1.5,
            'new_stock' => 6.5,
        ]);

        $this->post("/purchase-orders/{$po->id}/mark-received")
            ->assertSessionHas('error');
        $this->assertSame(1, StockHistory::where('type', 'purchase')->where('reference_id', $purchase->id)->count());
        $this->assertSame(6.5, (float) $stock->fresh()->quantity);
    }

    public function test_purchase_order_cannot_receive_from_draft_state(): void
    {
        $this->actingAs($admin = $this->makeUser());
        $branch = $this->makeBranch('Cabang PO Draft');
        $supplier = Supplier::create(['name' => 'Supplier Draft']);
        $product = $this->makeProduct($branch);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 2, 'branch_id' => $branch->id]);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-DRAFT-001', 'supplier_id' => $supplier->id, 'branch_id' => $branch->id,
            'order_date' => now(), 'status' => 'draft', 'subtotal' => 50000, 'grand_total' => 50000,
            'created_by' => $admin->id,
        ]);
        $po->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50000, 'total_price' => 50000]);

        $this->post("/purchase-orders/{$po->id}/mark-received")->assertSessionHas('error');
        $this->assertSame('draft', $po->fresh()->status);
        $this->assertSame(2.0, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertSame(0, Purchase::withoutGlobalScopes()->count());
    }

    public function test_legacy_sent_purchase_order_cannot_receive_without_approval_transition(): void
    {
        $this->actingAs($admin = $this->makeUser());
        $branch = $this->makeBranch('Cabang Legacy Sent');
        $supplier = Supplier::create(['name' => 'Supplier Legacy Sent']);
        $product = $this->makeProduct($branch);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 1, 'branch_id' => $branch->id]);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-SENT-001', 'supplier_id' => $supplier->id, 'branch_id' => $branch->id,
            'order_date' => now(), 'status' => 'sent', 'subtotal' => 50000, 'grand_total' => 50000,
            'created_by' => $admin->id,
        ]);
        $po->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50000, 'total_price' => 50000]);

        $this->post("/purchase-orders/{$po->id}/mark-received")->assertSessionHas('error');
        $this->assertSame('sent', $po->fresh()->status);
        $this->assertSame(1.0, (float) $product->fresh('stockRecord')->current_stock);
    }

    public function test_ordinary_role_cannot_transition_or_receive_purchase_order_via_web_or_api(): void
    {
        $ordinary = $this->makeUser('mekanik');
        $branch = $this->makeBranch('Cabang Unauthorized PO');
        $ordinary->branches()->attach($branch->id);
        $supplier = Supplier::create(['name' => 'Supplier Unauthorized PO']);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-UNAUTH-001', 'supplier_id' => $supplier->id, 'branch_id' => $branch->id,
            'order_date' => now(), 'status' => 'approved', 'subtotal' => 0, 'grand_total' => 0,
        ]);

        $this->actingAs($ordinary)->post("/purchase-orders/{$po->id}/mark-received")->assertForbidden();
        $this->post("/purchase-orders/{$po->id}/close")->assertForbidden();

        $token = $ordinary->createToken('unauthorized-po')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/purchase-orders/{$po->id}/receive")
            ->assertForbidden();
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/purchase-orders/{$po->id}/approve")
            ->assertForbidden();
    }

    public function test_purchase_order_partial_receive_tracks_each_line_and_prevents_over_receive(): void
    {
        $this->actingAs($admin = $this->makeUser());
        $branch = $this->makeBranch('Cabang Partial Receive');
        $supplier = Supplier::create(['name' => 'Supplier Partial Receive']);
        $product = $this->makeProduct($branch);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 1, 'branch_id' => $branch->id]);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-PARTIAL-001', 'supplier_id' => $supplier->id, 'branch_id' => $branch->id,
            'order_date' => now(), 'status' => 'approved', 'subtotal' => 125000, 'grand_total' => 125000,
            'created_by' => $admin->id,
        ]);
        $line = $po->items()->create(['product_id' => $product->id, 'quantity' => 2.5, 'unit_price' => 50000, 'total_price' => 125000]);

        $payload = ['receipt_items' => [['purchase_order_item_id' => $line->id, 'quantity' => 1.25]]];
        $this->post("/purchase-orders/{$po->id}/mark-received", $payload)->assertSessionHas('success');
        $this->assertSame('partially_received', $po->fresh()->status);
        $this->assertSame(1.25, (float) $line->fresh()->received_quantity);
        $this->assertSame(2.25, (float) $product->fresh('stockRecord')->current_stock);

        $this->post("/purchase-orders/{$po->id}/mark-received", [
            'receipt_items' => [['purchase_order_item_id' => $line->id, 'quantity' => 1.5]],
        ])->assertSessionHas('error');
        $this->assertSame(1.25, (float) $line->fresh()->received_quantity);

        $this->post("/purchase-orders/{$po->id}/mark-received", $payload)->assertSessionHas('success');
        $this->assertSame('received', $po->fresh()->status);
        $this->assertSame(2.5, (float) $line->fresh()->received_quantity);
        $this->assertSame(3.5, (float) $product->fresh('stockRecord')->current_stock);
        $this->post("/purchase-orders/{$po->id}/mark-received", $payload)->assertSessionHas('error');
        $this->assertSame(2, Purchase::withoutGlobalScopes()->where('supplier_id', $supplier->id)->count());
    }

    public function test_purchase_order_state_machine_requires_submit_approve_receive_close(): void
    {
        $this->actingAs($admin = $this->makeUser());
        $branch = $this->makeBranch('Cabang State Machine');
        $supplier = Supplier::create(['name' => 'Supplier State Machine']);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-STATE-001', 'supplier_id' => $supplier->id, 'branch_id' => $branch->id,
            'order_date' => now(), 'status' => 'draft', 'subtotal' => 0, 'grand_total' => 0, 'created_by' => $admin->id,
        ]);

        $this->post("/purchase-orders/{$po->id}/submit")->assertRedirect();
        $this->assertSame('submitted', $po->fresh()->status);
        $this->post("/purchase-orders/{$po->id}/approve")->assertRedirect();
        $this->assertSame('approved', $po->fresh()->status);
        $this->post("/purchase-orders/{$po->id}/close")->assertStatus(422);
    }

    public function test_api_purchase_order_partial_receive_uses_same_receipt_service(): void
    {
        $admin = $this->makeUser();
        $token = $admin->createToken('po-receipt')->plainTextToken;
        $branch = $this->makeBranch('Cabang API Receive');
        $supplier = Supplier::create(['name' => 'Supplier API Receive']);
        $product = $this->makeProduct($branch);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 0, 'branch_id' => $branch->id]);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-API-RECEIVE', 'supplier_id' => $supplier->id, 'branch_id' => $branch->id,
            'order_date' => now(), 'status' => 'approved', 'subtotal' => 100000, 'grand_total' => 100000,
            'created_by' => $admin->id,
        ]);
        $line = $po->items()->create(['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 50000, 'total_price' => 100000]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/purchase-orders/{$po->id}/receive", [
                'receipt_items' => [['purchase_order_item_id' => $line->id, 'quantity' => 0.75]],
            ])->assertCreated();

        $this->assertSame('partially_received', $po->fresh()->status);
        $this->assertSame(0.75, (float) $line->fresh()->received_quantity);
        $this->assertSame(0.75, (float) $product->fresh('stockRecord')->current_stock);
    }

    public function test_purchase_return_supports_partial_full_and_rejects_over_return(): void
    {
        $this->actingAs($admin = $this->makeUser());
        $branch = $this->makeBranch('Cabang Retur Beli');
        $supplier = Supplier::create(['name' => 'Supplier Retur']);
        $product = $this->makeProduct($branch);
        StockRecord::create(['product_id' => $product->id, 'quantity' => 9, 'branch_id' => $branch->id]);
        $purchase = Purchase::create([
            'purchase_no' => 'PUR-RETURN-001', 'supplier_id' => $supplier->id,
            'purchase_date' => now(), 'status' => 'received', 'total_amount' => 200000,
            'created_by' => $admin->id, 'branch_id' => $branch->id,
        ]);
        $purchase->items()->create(['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 50000, 'total_price' => 200000]);

        $this->post("/purchases/{$purchase->id}/return", [
            'return_items' => [['product_id' => $product->id, 'quantity' => 1.5]],
            'return_reason' => 'Retur parsial',
        ])->assertSessionHas('success');
        $this->assertSame('partially_returned', $purchase->fresh()->status);
        $this->assertSame(7.5, (float) $product->fresh('stockRecord')->current_stock);

        $this->post("/purchases/{$purchase->id}/return", [
            'return_items' => [['product_id' => $product->id, 'quantity' => 3]],
            'return_reason' => 'Melebihi sisa',
        ])->assertSessionHas('error');
        $this->assertSame(7.5, (float) $product->fresh('stockRecord')->current_stock);

        $this->post("/purchases/{$purchase->id}/return", [
            'return_items' => [['product_id' => $product->id, 'quantity' => 2.5]],
            'return_reason' => 'Retur sisa',
        ])->assertSessionHas('success');
        $this->assertSame('returned', $purchase->fresh()->status);
        $this->assertSame(5.0, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertSame(2, StockHistory::where('type', 'return')->where('reference_id', $purchase->id)->count());
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
        $invoice = Invoice::create([
            'invoice_number' => 'INV-RETURN-001',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 150000,
            'grand_total' => 150000,
            'payment_status' => 2,
            'branch_id' => $branch->id,
        ]);
        $invoice->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 75000,
            'total_price' => 150000,
        ]);

        $response = $this->post('/sell-returns', [
            'invoice_id' => $invoice->id,
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

        $this->post('/sell-returns', [
            'invoice_id' => $invoice->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Mencoba retur berlebih',
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1]],
        ])->assertSessionHas('error');

        $this->assertSame(1, SellReturn::where('invoice_id', $invoice->id)->count());
        $this->assertSame(9.25, (float) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity'));

        $unrelated = Product::create([
            'product_no' => 'P-UNRELATED', 'code' => 'UNRELATED-001', 'name' => 'Produk Tidak Terkait',
            'product_type_id' => $product->product_type_id, 'unit_id' => $product->unit_id,
            'price' => 10000, 'cost_price' => 5000, 'branch_id' => $branch->id,
        ]);
        StockRecord::create(['product_id' => $unrelated->id, 'quantity' => 3, 'branch_id' => $branch->id]);
        $this->post('/sell-returns', [
            'invoice_id' => $invoice->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Produk tidak terkait',
            'items' => [['product_id' => $unrelated->id, 'quantity' => 1]],
        ])->assertSessionHas('error');
        $this->assertSame(3.0, (float) $unrelated->fresh('stockRecord')->current_stock);
    }

    public function test_signed_adjustment_and_same_value_opname_are_decimal_safe(): void
    {
        $product = $this->createProductWithInitialStock(4, 'SIGNED');

        app(ProductService::class)->adjustStock($product, 1.25, 'Tambah pecahan');
        app(ProductService::class)->adjustStock($product, -0.5, 'Kurangi pecahan');
        $before = StockHistory::where('product_id', $product->id)->count();
        $delta = StockService::set($product->id, 4.75, 'opname', 'Nilai sama');

        $this->assertSame(4.75, (float) $product->fresh('stockRecord')->current_stock);
        $this->assertSame(0.0, $delta);
        $this->assertSame($before, StockHistory::where('product_id', $product->id)->count());
    }
}

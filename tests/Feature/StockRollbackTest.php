<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\RepairCategory;
use App\Models\Service as WorkshopService;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\InvoiceService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * When any downstream step of a multi-write operation fails, the whole
 * operation must roll back — no orphan invoices, no partial stock loss.
 */
class StockRollbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);

        // created_by FKs require an authenticated user.
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $this->actingAs($user);
    }

    private function makeProduct(string $name, int $stock): Product
    {
        $type = ProductType::create(['type' => 'T '.$name.uniqid(), 'slug' => uniqid(), 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'U '.$name.uniqid(), 'abbreviation' => uniqid(), 'is_active' => true]);

        $product = Product::create([
            'product_no' => 'P-'.uniqid(),
            'code' => 'C-'.uniqid(),
            'name' => $name,
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 20000,
        ]);
        StockRecord::create(['product_id' => $product->id, 'quantity' => $stock]);

        return $product;
    }

    public function test_invoice_creation_with_insufficient_stock_rolls_back_everything(): void
    {
        $available = $this->makeProduct('Ada Stok', 10);
        $scarce = $this->makeProduct('Stok Tipis', 1);
        $customer = Customer::create(['name' => 'Cust Rollback']);

        try {
            app(InvoiceService::class)->create([
                'customer_id' => $customer->id,
                'invoice_date' => now()->toDateString(),
                'invoice_type' => 'sale',
                'items' => [
                    ['product_id' => $available->id, 'description' => 'Item OK', 'quantity' => 5, 'unit_price' => 10000],
                    ['product_id' => $scarce->id, 'description' => 'Item Gagal', 'quantity' => 99, 'unit_price' => 5000],
                ],
            ]);
            $this->fail('Expected stock exception was not thrown.');
        } catch (\RuntimeException) {
        }

        // No invoice persisted at all
        $this->assertEquals(0, Invoice::withoutGlobalScopes()->count());
        $this->assertEquals(0, InvoiceItem::withoutGlobalScopes()->count());

        // Stock untouched — the first item's deduction must have been undone
        $this->assertEquals(
            10,
            (int) StockRecord::withoutGlobalScopes()->where('product_id', $available->id)->value('quantity')
        );
        $this->assertEquals(
            1,
            (int) StockRecord::withoutGlobalScopes()->where('product_id', $scarce->id)->value('quantity')
        );
        $this->assertEquals(0, StockHistory::count());
    }

    public function test_deleting_unpaid_invoice_restores_stock(): void
    {
        $product = $this->makeProduct('Produk Retur', 10);
        $customer = Customer::create(['name' => 'Cust Delete']);

        $invoice = app(InvoiceService::class)->create([
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'sale',
            'items' => [
                ['product_id' => $product->id, 'description' => 'Item', 'quantity' => 4, 'unit_price' => 10000],
            ],
        ]);

        $this->assertEquals(6, (int) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity'));

        app(InvoiceService::class)->deleteWithStockRestore($invoice->fresh());

        // Soft-deleted invoice + restored stock
        $this->assertTrue(Invoice::withoutGlobalScopes()->find($invoice->id)->trashed());
        $this->assertEquals(
            10,
            (int) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity')
        );

        // Reversal history recorded, original deduction kept (immutable ledger)
        $this->assertTrue(
            StockHistory::where('product_id', $product->id)
                ->where('quantity_change', 4)
                ->exists()
        );
    }

    public function test_paid_invoice_cannot_be_deleted(): void
    {
        $product = $this->makeProduct('Produk Terkunci', 10);
        $customer = Customer::create(['name' => 'Cust Paid']);
        PaymentMethod::create(['payment' => 'Cash '.uniqid(), 'slug' => uniqid(), 'is_active' => true]);

        $category = RepairCategory::create([
            'repair_category_name' => 'Kat '.uniqid(),
            'slug' => uniqid(),
            'is_active' => true,
        ]);

        $vtype = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => uniqid()]);
        $vbrand = VehicleBrand::create(['vehicle_type_id' => $vtype->id, 'vehicle_brand' => 'Toyota '.uniqid()]);
        $fuel = FuelType::create(['fuel_type' => 'Pertamax', 'slug' => uniqid()]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $vtype->id,
            'vehicle_brand_id' => $vbrand->id,
            'fuel_type_id' => $fuel->id,
            'number_plate' => 'B '.uniqid(),
            'model_name' => 'Avanza',
        ]);

        $service = WorkshopService::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'repair_category_id' => $category->id,
            'title' => 'Servis Terkunci',
            'service_date' => now()->toDateString(),
            'done_status' => 2,
            'workflow_status' => 12,
            'created_by' => auth()->id(),
        ]);

        $invoice = app(InvoiceService::class)->create([
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'service',
            'items' => [
                ['product_id' => $product->id, 'description' => 'Item', 'quantity' => 2, 'unit_price' => 10000],
            ],
        ]);

        $invoice->update(['paid_amount' => 20000, 'amount_received' => 20000, 'payment_status' => 2]);
        PaymentRecord::create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => PaymentMethod::first()->id,
            'amount' => 20000,
            'payment_date' => now(),
        ]);

        $this->expectException(HttpException::class);

        app(InvoiceService::class)->deleteWithStockRestore($invoice->fresh());
    }
}

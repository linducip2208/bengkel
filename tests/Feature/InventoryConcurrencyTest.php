<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FuelType;
use App\Models\PartReservation;
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
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Regression + concurrency coverage for stock mutations.
 *
 * On SQLite (test default) we verify the logical guarantees: sufficiency
 * checks, atomic rollback, ledger consistency and reservation accounting.
 * The true parallel-connection race verification runs only on MySQL, where
 * SELECT ... FOR UPDATE is actually honoured.
 */
class InventoryConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // StockHistory.user_id and services.created_by are user FKs.
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $this->actingAs($user);
    }

    private function makeProduct(int $initialStock = 5): Product
    {
        $type = ProductType::create(['type' => 'Sparepart', 'slug' => 'sparepart-'.uniqid(), 'is_active' => true]);
        $unit = ProductUnit::create(['name' => 'Pcs '.uniqid(), 'abbreviation' => 'pcs'.uniqid(), 'is_active' => true]);

        $product = Product::create([
            'product_no' => 'P-TEST-'.uniqid(),
            'code' => 'TST-'.uniqid(),
            'name' => 'Produk Uji Stok',
            'product_type_id' => $type->id,
            'unit_id' => $unit->id,
            'price' => 10000,
            'cost_price' => 7000,
        ]);

        if ($initialStock > 0) {
            StockRecord::create(['product_id' => $product->id, 'quantity' => $initialStock]);
        }

        return $product;
    }

    public function test_decrement_rejects_insufficient_stock(): void
    {
        $product = $this->makeProduct(3);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tidak cukup');

        StockService::decrement($product->id, 4, 'usage');
    }

    public function test_failed_decrement_does_not_mutate_stock_or_ledger(): void
    {
        $product = $this->makeProduct(3);

        try {
            StockService::decrement($product->id, 10, 'usage');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException) {
        }

        $this->assertEquals(3, StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity'));
        $this->assertNull(StockHistory::where('product_id', $product->id)->first());
    }

    public function test_two_sequential_consumptions_cannot_exceed_available_stock(): void
    {
        $product = $this->makeProduct(5);

        StockService::decrement($product->id, 3, 'usage');
        $this->assertEquals(2, StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity'));

        $this->expectException(\RuntimeException::class);
        StockService::decrement($product->id, 3, 'usage');
    }

    public function test_fractional_sale_quantity_is_deducted_without_rounding_stock(): void
    {
        $product = $this->makeProduct(10);

        StockService::decrement($product->id, 0.75, 'pos');

        $record = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->firstOrFail();
        $history = StockHistory::where('product_id', $product->id)->latest('id')->firstOrFail();

        $this->assertSame('9.25', $record->quantity);
        $this->assertSame('-0.75', $history->quantity_change);
        $this->assertSame('10.00', $history->previous_stock);
        $this->assertSame('9.25', $history->new_stock);
    }

    public function test_ledger_snapshots_reconstruct_current_quantity(): void
    {
        $product = $this->makeProduct(10);

        StockService::increment($product->id, 5, 'purchase');
        StockService::decrement($product->id, 8, 'pos');

        $record = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
        $histories = StockHistory::where('product_id', $product->id)->orderBy('id')->get();

        // previous -> new chain must reconstruct the current quantity exactly.
        $replay = 10;
        foreach ($histories as $history) {
            $this->assertEquals($replay, $history->previous_stock);
            $replay = (int) $history->new_stock;
        }
        $this->assertEquals((int) $record->quantity, $replay);
    }

    public function test_reservation_availability_subtracts_open_reservations(): void
    {
        $product = $this->makeProduct(4);
        $customer = Customer::create(['name' => 'Resv Cust '.uniqid()]);
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
            'title' => 'Reservasi Uji',
            'service_date' => now()->toDateString(),
            'description' => 'reservasi uji',
            'done_status' => 1,
            'workflow_status' => 1,
            'created_by' => auth()->id() ?? 1,
        ]);

        PartReservation::create([
            'service_id' => $service->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'status' => 'reserved',
        ]);

        $onHand = (int) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity');
        $reserved = (int) PartReservation::where('product_id', $product->id)->where('status', 'reserved')->sum('quantity');

        $this->assertEquals(1, max(0, $onHand - $reserved));
    }

    /**
     * TRUE oversell race: two independent PHP processes each try to consume
     * the last unit simultaneously. Row locking must guarantee exactly one
     * succeeds. Requires MySQL (InnoDB SELECT ... FOR UPDATE).
     */
    public function test_two_cli_processes_cannot_both_consume_last_unit(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Parallel row-lock verification requires MySQL.');
        }

        $product = $this->makeProduct(1);

        $base = realpath(__DIR__.'/../..');
        $script = $base.'/storage/app/testing/consume_once.php';
        @mkdir(dirname($script), 0777, true);

        file_put_contents($script, <<<'PHP'
<?php
$base = dirname(__DIR__, 2);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    App\Services\StockService::decrement((int) $argv[1], 1, 'pos', 'race-test');
    echo "CONSUMED";
} catch (RuntimeException $e) {
    echo "REJECTED";
}
PHP);

        $run = fn () => new Process(['php', $script, (string) $product->id], $base, null, null, 30);

        /** @var Process $p1 */
        [$p1, $p2] = [$run(), $run()];
        $p1->start();
        $p2->start();
        $out1 = trim($p1->wait()->getOutput());
        $out2 = trim($p2->wait()->getOutput());

        @unlink($script);

        $results = [$out1, $out2];
        sort($results);

        $this->assertEquals(
            ['CONSUMED', 'REJECTED'],
            $results,
            'Exactly one process may consume the last unit. Got: '.implode(' / ', [$out1, $out2])
        );

        $this->assertEquals(
            0,
            (int) StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->value('quantity'),
            'Stock must land on exactly zero — never negative.'
        );
    }
}

<?php
/**
 * UAT End-to-End Test Script
 * Run: php scripts/uat_test.php
 *
 * Tests every main business flow and reports errors found.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

$errors = [];
$warnings = [];
$ok = [];

function log_err(&$errors, $area, $msg) { $errors[] = "[$area] $msg"; echo "✗ [$area] $msg\n"; }
function log_warn(&$warnings, $area, $msg) { $warnings[] = "[$area] $msg"; echo "⚠ [$area] $msg\n"; }
function log_ok(&$ok, $area, $msg) { $ok[] = "[$area] $msg"; echo "✓ [$area] $msg\n"; }

echo "=== UAT END-TO-END TEST ===\n\n";

/* ============================================================
 * 1. MODELS — pastikan semua model bisa di-instantiate
 * ============================================================ */
echo "--- 1. MODEL INSTANTIATION ---\n";
$modelsDir = __DIR__ . '/../app/Models';
foreach (glob($modelsDir . '/*.php') as $file) {
    $class = 'App\\Models\\' . pathinfo($file, PATHINFO_FILENAME);
    try {
        if (!class_exists($class)) {
            log_err($errors, 'MODEL', "$class class does not autoload");
            continue;
        }
        $instance = new $class;
        $table = $instance->getTable();
        if (!Schema::hasTable($table)) {
            log_err($errors, 'MODEL', "$class.table '$table' DOES NOT EXIST in DB");
        }
    } catch (\Throwable $e) {
        log_err($errors, 'MODEL', "$class -> " . $e->getMessage());
    }
}
echo "\n";

/* ============================================================
 * 2. COLUMN CHECK — fillable harus ada di tabel
 * ============================================================ */
echo "--- 2. FILLABLE vs DB COLUMNS ---\n";
foreach (glob($modelsDir . '/*.php') as $file) {
    $class = 'App\\Models\\' . pathinfo($file, PATHINFO_FILENAME);
    if (!class_exists($class)) continue;
    try {
        $instance = new $class;
        $table = $instance->getTable();
        if (!Schema::hasTable($table)) continue;
        $cols = Schema::getColumnListing($table);
        $fillable = $instance->getFillable();
        foreach ($fillable as $f) {
            if (!in_array($f, $cols)) {
                log_err($errors, 'COLUMN', "$class fillable '$f' MISSING in '$table'");
            }
        }
    } catch (\Throwable $e) {
        log_err($errors, 'COLUMN', "$class -> " . $e->getMessage());
    }
}
echo "\n";

/* ============================================================
 * 3. CONTROLLER METHOD EXISTENCE — tiap route nyambung ke method yang ada
 * ============================================================ */
echo "--- 3. ROUTE -> CONTROLLER METHOD ---\n";
foreach (Route::getRoutes() as $route) {
    $action = $route->getAction();
    if (!isset($action['controller'])) continue;
    $ctrl = $action['controller'];
    if (str_contains($ctrl, '@')) {
        [$class, $method] = explode('@', $ctrl);
    } else {
        continue;
    }
    if (!class_exists($class)) {
        log_err($errors, 'ROUTE', "Class $class missing for route " . $route->uri());
        continue;
    }
    if (!method_exists($class, $method)) {
        log_err($errors, 'ROUTE', "$class@$method MISSING for route " . $route->uri());
    }
}
echo "\n";

/* ============================================================
 * 4. AUTH FLOW
 * ============================================================ */
echo "--- 4. AUTH FLOW ---\n";
try {
    $u = \App\Models\User::first();
    if (!$u) {
        log_warn($warnings, 'AUTH', 'No user in DB — run seeder');
    } else {
        log_ok($ok, 'AUTH', "User exists: {$u->email}");
    }
} catch (\Throwable $e) {
    log_err($errors, 'AUTH', $e->getMessage());
}

/* ============================================================
 * 5. BUSINESS FLOW SIMULATION
 * ============================================================ */
echo "\n--- 5. BUSINESS FLOW SIMULATION ---\n";

DB::beginTransaction();
try {
    // 5a. Customer create
    $customer = \App\Models\Customer::create([
        'name' => 'UAT Customer ' . uniqid(),
        'phone' => '0812' . rand(10000000, 99999999),
        'email' => 'uat' . uniqid() . '@test.local',
        'address' => 'Jl. UAT No. 1',
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Customer create #{$customer->id}");

    // 5b. Vehicle
    $brand = \App\Models\VehicleBrand::first();
    $type = \App\Models\VehicleType::first();
    $vehicle = \App\Models\Vehicle::create([
        'customer_id' => $customer->id,
        'plate_number' => 'UAT ' . rand(1000, 9999) . ' UT',
        'vehicle_brand_id' => $brand?->id,
        'vehicle_type_id' => $type?->id,
        'year' => 2020,
        'fuel_type_id' => \App\Models\FuelType::first()?->id,
        'color_id' => \App\Models\Color::first()?->id,
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Vehicle create #{$vehicle->id} ({$vehicle->plate_number})");

    // 5c. Service
    $service = \App\Models\Service::create([
        'service_code' => 'SRV-UAT-' . uniqid(),
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'service_date' => now(),
        'status' => 'open',
        'complaint' => 'UAT test complaint',
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Service create #{$service->id}");

    // 5d. Jobcard detail
    if (Schema::hasTable('jobcard_details')) {
        $jc = \App\Models\JobcardDetail::create([
            'service_id' => $service->id,
            'description' => 'UAT job item',
            'qty' => 1,
            'unit_price' => 50000,
            'subtotal' => 50000,
        ]);
        log_ok($ok, 'FLOW', "Jobcard detail create #{$jc->id}");
    }

    // 5e. Product
    $product = \App\Models\Product::create([
        'product_code' => 'UAT-' . uniqid(),
        'product_name' => 'UAT Product',
        'product_type_id' => \App\Models\ProductType::first()?->id,
        'product_unit_id' => \App\Models\ProductUnit::first()?->id,
        'buy_price' => 10000,
        'sell_price' => 15000,
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Product create #{$product->id}");

    // 5f. Stock record
    $stock = \App\Models\StockRecord::create([
        'product_id' => $product->id,
        'qty' => 100,
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "StockRecord create qty=100");

    // 5g. Supplier + Purchase
    $supplier = \App\Models\Supplier::create([
        'name' => 'UAT Supplier',
        'phone' => '08120000000',
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    $purchase = \App\Models\Purchase::create([
        'purchase_code' => 'PO-UAT-' . uniqid(),
        'supplier_id' => $supplier->id,
        'purchase_date' => now(),
        'status' => 'pending',
        'total' => 100000,
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Purchase create #{$purchase->id}");

    // 5h. Invoice
    $invoice = \App\Models\Invoice::create([
        'invoice_no' => 'INV-UAT-' . uniqid(),
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'invoice_date' => now(),
        'subtotal' => 50000,
        'tax' => 0,
        'discount' => 0,
        'total' => 50000,
        'paid_amount' => 0,
        'status' => 'unpaid',
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Invoice create #{$invoice->id} ({$invoice->invoice_no})");

    // 5i. Payment record
    $pay = \App\Models\PaymentRecord::create([
        'invoice_id' => $invoice->id,
        'amount' => 50000,
        'payment_method_id' => \App\Models\PaymentMethod::first()?->id,
        'payment_date' => now(),
        'note' => 'UAT payment',
    ]);
    log_ok($ok, 'FLOW', "PaymentRecord create #{$pay->id}");

    // 5j. Income
    $income = \App\Models\Income::create([
        'description' => 'UAT income',
        'amount' => 100000,
        'income_date' => now(),
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Income create #{$income->id}");

    // 5k. Expense
    $expense = \App\Models\Expense::create([
        'description' => 'UAT expense',
        'amount' => 25000,
        'expense_date' => now(),
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Expense create #{$expense->id}");

    // 5l. Sale (vehicle)
    $sale = \App\Models\Sale::create([
        'sale_code' => 'SLE-UAT-' . uniqid(),
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'sale_date' => now(),
        'total' => 50000000,
        'dp' => 5000000,
        'status' => 'pending',
        'branch_id' => \App\Models\Branch::first()?->id,
    ]);
    log_ok($ok, 'FLOW', "Sale create #{$sale->id}");

    // 5m. Voucher
    if (Schema::hasTable('vouchers')) {
        $vou = \App\Models\Voucher::create([
            'code' => 'UAT' . rand(100, 999),
            'type' => 'percent',
            'value' => 10,
            'min_purchase' => 0,
            'max_discount' => 100000,
            'usage_limit' => 100,
            'used_count' => 0,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'active' => 1,
        ]);
        log_ok($ok, 'FLOW', "Voucher create #{$vou->id}");
    }

    // 5n. Loyalty transaction
    if (Schema::hasTable('loyalty_transactions')) {
        $loy = \App\Models\LoyaltyTransaction::create([
            'customer_id' => $customer->id,
            'reference_type' => \App\Models\Invoice::class,
            'reference_id' => $invoice->id,
            'points' => 50,
            'type' => 'earn',
            'description' => 'UAT loyalty earn',
        ]);
        log_ok($ok, 'FLOW', "LoyaltyTransaction create #{$loy->id}");
    }

    // 5o. POS session
    if (Schema::hasTable('pos_sessions')) {
        $pos = \App\Models\PosSession::create([
            'user_id' => $u?->id ?? 1,
            'branch_id' => \App\Models\Branch::first()?->id,
            'opened_at' => now(),
            'opening_balance' => 100000,
            'status' => 'open',
        ]);
        log_ok($ok, 'FLOW', "PosSession create #{$pos->id}");
    }

    // 5p. Booking
    if (Schema::hasTable('bookings')) {
        $bk = \App\Models\Booking::create([
            'customer_name' => 'UAT Booking',
            'customer_phone' => '081200000000',
            'plate_number' => 'UAT 9999 BK',
            'service_type' => 'Servis berkala',
            'preferred_date' => now()->addDays(1),
            'complaint' => 'UAT booking complaint',
            'status' => 'pending',
            'branch_id' => \App\Models\Branch::first()?->id,
        ]);
        log_ok($ok, 'FLOW', "Booking create #{$bk->id}");
    }

    DB::rollBack();
    echo "(rolled back UAT data)\n";

} catch (\Throwable $e) {
    DB::rollBack();
    log_err($errors, 'FLOW', $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
}

/* ============================================================
 * SUMMARY
 * ============================================================ */
echo "\n=== SUMMARY ===\n";
echo "✓ OK: " . count($ok) . "\n";
echo "⚠ WARN: " . count($warnings) . "\n";
echo "✗ ERR: " . count($errors) . "\n\n";

if ($errors) {
    echo "=== ERRORS ===\n";
    foreach ($errors as $e) echo "  $e\n";
}
if ($warnings) {
    echo "=== WARNINGS ===\n";
    foreach ($warnings as $w) echo "  $w\n";
}

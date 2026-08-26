<?php

/**
 * UAT End-to-End Business Flow Test
 * - Login (auth)
 * - Create customer
 * - Create vehicle
 * - Create service
 * - Create jobcard
 * - Service technician
 * - Complete service
 * - Create invoice (from service)
 * - Payment
 * - POS flow
 * - Booking
 * - Voucher + Loyalty
 * - Reminder + Notification template
 * - Reports
 *
 * Wraps everything in transaction → rollback so DB stays clean.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FuelType;
use App\Models\GatePass;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JobcardDetail;
use App\Models\LoyaltyTransaction;
use App\Models\Note;
use App\Models\NotificationTemplate;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Reminder;
use App\Models\RepairCategory;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceObservationPoint;
use App\Models\ServiceTechnician;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Models\Voucher;
use App\Services\ReportService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$ok = [];
$err = [];
function _ok(&$ok, $msg)
{
    $ok[] = $msg;
    echo "✓ $msg\n";
}
function _err(&$err, $msg)
{
    $err[] = $msg;
    echo "✗ $msg\n";
}

echo "=== UAT BUSINESS FLOW ===\n\n";

DB::beginTransaction();
try {
    $branch = Branch::firstOrFail();
    $user = User::firstOrFail();
    auth()->loginUsingId($user->id);
    session(['current_branch_id' => $branch->id]);
    _ok($ok, "Auth login as {$user->email} branch={$branch->id}");

    // -- 1. Customer
    $c = Customer::create([
        'branch_id' => $branch->id,
        'name' => 'UAT '.uniqid(),
        'phone' => '0812'.rand(10000000, 99999999),
        'email' => 'uat'.uniqid().'@t.id',
        'address' => 'Test',
    ]);
    _ok($ok, "Customer #{$c->id}");

    // -- 2. Vehicle
    $v = Vehicle::create([
        'customer_id' => $c->id,
        'vehicle_brand_id' => VehicleBrand::first()?->id,
        'vehicle_type_id' => VehicleType::first()?->id,
        'fuel_type_id' => FuelType::first()?->id,
        'branch_id' => $branch->id,
        'number_plate' => 'UAT '.rand(1000, 9999),
        'model_name' => 'UAT Model',
        'model_year' => 2020,
        'color' => 'black',
        'odometer' => 50000,
    ]);
    _ok($ok, "Vehicle #{$v->id} {$v->number_plate}");

    // -- 3. Service
    $svc = Service::create([
        'customer_id' => $c->id,
        'vehicle_id' => $v->id,
        'repair_category_id' => RepairCategory::first()?->id,
        'title' => 'UAT service',
        'description' => 'Test description',
        'service_date' => now(),
        'charge' => 250000,
        'done_status' => 0,
        'created_by' => $user->id,
        'branch_id' => $branch->id,
        'job_no' => 'UAT-'.uniqid(),
    ]);
    _ok($ok, "Service #{$svc->id} {$svc->job_no}");

    // -- 4. Jobcard
    if (Schema::hasTable('jobcard_details')) {
        $jc = JobcardDetail::create([
            'service_id' => $svc->id,
            'jobcard_no' => 'JC-'.uniqid(),
            'customer_id' => $c->id,
            'vehicle_id' => $v->id,
            'odometer_in' => 50000,
            'in_date' => now(),
            'done_status' => 1,
        ]);
        _ok($ok, "JobcardDetail #{$jc->id} {$jc->jobcard_no}");
    }

    // -- 5. Service technician (commission)
    $tech = ServiceTechnician::create([
        'service_id' => $svc->id,
        'user_id' => $user->id,
        'commission_pct' => 10,
        'commission_amt' => 25000,
    ]);
    _ok($ok, "ServiceTechnician #{$tech->id}");

    // -- 6. Observation point + result
    $obType = ObservationType::first();
    $obPoint = $obType ? ObservationPoint::where('observation_type_id', $obType->id)->first() : null;
    if ($obPoint) {
        $sop = ServiceObservationPoint::create([
            'service_id' => $svc->id,
            'observation_point_id' => $obPoint->id,
            'status' => 'ok',
        ]);
        _ok($ok, "ServiceObservationPoint #{$sop->id}");
    }

    // -- 7. Product + Stock
    $p = Product::create([
        'product_no' => 'P-'.uniqid(),
        'code' => 'C-'.uniqid(),
        'name' => 'UAT Product',
        'product_type_id' => ProductType::first()?->id,
        'unit_id' => ProductUnit::first()?->id,
        'price' => 50000,
        'cost_price' => 30000,
        'branch_id' => $branch->id,
    ]);
    _ok($ok, "Product #{$p->id}");

    $sr = StockRecord::create([
        'product_id' => $p->id,
        'quantity' => 50,
        'minimum_stock' => 5,
        'branch_id' => $branch->id,
    ]);
    _ok($ok, "StockRecord #{$sr->id}");

    // -- 8. Supplier + Purchase
    $sup = Supplier::create([
        'name' => 'UAT Supplier',
        'phone' => '08120001111',
        'address' => 'Test',
    ]);
    $purchase = Purchase::create([
        'purchase_no' => 'PO-'.uniqid(),
        'supplier_id' => $sup->id,
        'purchase_date' => now(),
        'status' => 'pending',
        'total_amount' => 200000,
        'created_by' => $user->id,
        'branch_id' => $branch->id,
    ]);
    PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $p->id,
        'quantity' => 10,
        'unit_price' => 20000,
        'subtotal' => 200000,
    ]);
    _ok($ok, "Purchase #{$purchase->id} {$purchase->purchase_no}");

    // -- 9. Invoice (from service)
    $invNo = 'INV-'.uniqid();
    $inv = Invoice::create([
        'invoice_number' => $invNo,
        'customer_id' => $c->id,
        'service_id' => $svc->id,
        'payment_method_id' => PaymentMethod::first()?->id,
        'payment_status' => 'unpaid',
        'total_amount' => 250000,
        'discount' => 0,
        'tax_amount' => 25000,
        'grand_total' => 275000,
        'paid_amount' => 0,
        'invoice_date' => now(),
        'invoice_type' => 'service',
        'created_by' => $user->id,
        'branch_id' => $branch->id,
    ]);
    _ok($ok, "Invoice #{$inv->id} {$inv->invoice_number}");

    InvoiceItem::create([
        'invoice_id' => $inv->id,
        'description' => 'Service charge',
        'quantity' => 1,
        'unit_price' => 250000,
        'subtotal' => 250000,
    ]);
    _ok($ok, 'InvoiceItem');

    // -- 10. Payment
    $pay = PaymentRecord::create([
        'invoice_id' => $inv->id,
        'payment_method_id' => PaymentMethod::first()?->id,
        'amount' => 275000,
        'payment_date' => now(),
        'created_by' => $user->id,
    ]);
    _ok($ok, "PaymentRecord #{$pay->id}");

    // -- 11. Sale (vehicle)
    $sale = Sale::create([
        'sales_no' => 'SLE-'.uniqid(),
        'customer_id' => $c->id,
        'vehicle_id' => $v->id,
        'sale_date' => now(),
        'total_amount' => 80000000,
        'tax_amount' => 0,
        'grand_total' => 80000000,
        'salesperson_id' => $user->id,
        'created_by' => $user->id,
        'branch_id' => $branch->id,
    ]);
    _ok($ok, "Sale #{$sale->id}");

    // -- 12. Income & Expense
    $inc = Income::create([
        'invoice_number' => 'INC-'.uniqid(),
        'amount' => 100000,
        'income_date' => now(),
        'label' => 'Lain-lain',
        'created_by' => $user->id,
        'branch_id' => $branch->id,
    ]);
    _ok($ok, "Income #{$inc->id}");

    $exp = Expense::create([
        'amount' => 50000,
        'expense_date' => now(),
        'label' => 'Operasional',
        'created_by' => $user->id,
        'branch_id' => $branch->id,
    ]);
    _ok($ok, "Expense #{$exp->id}");

    // -- 13. Gate pass
    $gp = GatePass::create([
        'gate_pass_no' => 'GP-'.uniqid(),
        'vehicle_id' => $v->id,
        'customer_id' => $c->id,
        'service_id' => $svc->id,
        'entry_date' => now(),
        'status' => 'in',
        'created_by' => $user->id,
        'branch_id' => $branch->id,
    ]);
    _ok($ok, "GatePass #{$gp->id}");

    // -- 14. Stock history
    $sh = StockHistory::create([
        'product_id' => $p->id,
        'quantity_change' => 10,
        'previous_stock' => 50,
        'new_stock' => 60,
        'type' => 'in',
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
        'reason' => 'UAT stock in',
        'user_id' => $user->id,
    ]);
    _ok($ok, "StockHistory #{$sh->id}");

    // -- 15. Notification template + reminder
    $tpl = NotificationTemplate::create([
        'name' => 'UAT Template '.uniqid(),
        'slug' => 'uat-template-'.uniqid(),
        'channel' => 'whatsapp',
        'body' => 'Halo {customer_name}, kendaraan {plate} sudah selesai.',
    ]);
    _ok($ok, "NotificationTemplate #{$tpl->id}");

    $rem = Reminder::create([
        'customer_id' => $c->id,
        'vehicle_id' => $v->id,
        'service_id' => $svc->id,
        'reminder_type' => 'service_followup',
        'reminder_date' => now()->addDays(30),
        'message' => 'UAT reminder',
        'created_by' => $user->id,
        'branch_id' => $branch->id,
    ]);
    _ok($ok, "Reminder #{$rem->id}");

    // -- 16. Voucher + Loyalty
    if (Schema::hasTable('vouchers')) {
        $vou = Voucher::create([
            'code' => 'UAT'.rand(100, 999),
            'name' => 'UAT promo',
            'type' => 'percent',
            'value' => 10,
            'min_purchase' => 0,
            'max_discount' => 100000,
            'usage_limit' => 100,
            'used_count' => 0,
            'valid_from' => now(),
            'valid_until' => now()->addDays(30),
            'is_active' => 1,
        ]);
        _ok($ok, "Voucher #{$vou->id} {$vou->code}");
    }

    if (Schema::hasTable('loyalty_transactions')) {
        $loy = LoyaltyTransaction::create([
            'customer_id' => $c->id,
            'reference_type' => Invoice::class,
            'reference_id' => $inv->id,
            'points' => 50,
            'type' => 'earn',
            'description' => 'UAT loyalty',
            'created_by' => $user->id,
        ]);
        _ok($ok, "LoyaltyTransaction #{$loy->id}");
    }

    // -- 17. Note (polymorphic)
    $note = Note::create([
        'notable_type' => Customer::class,
        'notable_id' => $c->id,
        'content' => 'UAT note',
        'created_by' => $user->id,
    ]);
    _ok($ok, "Note #{$note->id}");

    // -- 18. POS session + receipt
    if (Schema::hasTable('pos_sessions')) {
        $pos = PosSession::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'opened_at' => now(),
            'opening_balance' => 200000,
            'status' => 'open',
        ]);
        _ok($ok, "PosSession #{$pos->id}");

        $posInv = Invoice::create([
            'invoice_number' => 'POS-'.uniqid(),
            'customer_id' => $c->id,
            'pos_session_id' => $pos->id,
            'payment_method_id' => PaymentMethod::first()?->id,
            'payment_status' => 'paid',
            'total_amount' => 50000,
            'discount' => 0,
            'tax_amount' => 0,
            'grand_total' => 50000,
            'paid_amount' => 50000,
            'amount_received' => 100000,
            'invoice_date' => now(),
            'invoice_type' => 'pos',
            'created_by' => $user->id,
            'branch_id' => $branch->id,
        ]);
        _ok($ok, "POS Invoice #{$posInv->id}");
    }

    // -- 19. Booking (online)
    if (Schema::hasTable('bookings')) {
        $bk = Booking::create([
            'branch_id' => $branch->id,
            'name' => 'UAT booking',
            'phone' => '08120000000',
            'vehicle_plate' => 'UAT-9999',
            'vehicle_brand' => 'Honda',
            'vehicle_model' => 'Civic',
            'booking_at' => now()->addDays(1),
            'complaint' => 'AC tidak dingin',
            'status' => 'pending',
        ]);
        _ok($ok, "Booking #{$bk->id}");
    }

    // -- 20. Report query smoke test
    $reportService = app(ReportService::class);
    if (method_exists($reportService, 'getServiceReport')) {
        $reportService->getServiceReport(now()->startOfMonth(), now()->endOfMonth());
        _ok($ok, 'Report: service');
    }

    DB::rollBack();
    echo "\n(rolled back UAT data)\n";

} catch (Throwable $e) {
    DB::rollBack();
    _err($err, $e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine());
    echo $e->getTraceAsString()."\n";
}

echo "\n=== SUMMARY ===\n";
echo '✓ OK: '.count($ok)."\n";
echo '✗ ERR: '.count($err)."\n";

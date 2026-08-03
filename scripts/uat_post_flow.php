<?php
/**
 * UAT POST flow — simulates filling forms and submitting.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$base = $argv[1] ?? 'http://127.0.0.1:8124';
$cookieJar = sys_get_temp_dir() . '/uat_post_' . getmypid() . '.txt';
@unlink($cookieJar);

function curlReq($url, $method = 'GET', $data = null, $cookieJar = null, $files = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($files) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($data, $files));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $head = substr($body, 0, $headerSize);
    $body = substr($body, $headerSize);
    curl_close($ch);
    return ['status' => $status, 'head' => $head, 'body' => $body];
}

function fetchCsrf($url, $cookieJar) {
    $r = curlReq($url, 'GET', null, $cookieJar);
    if (preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m)) return $m[1];
    return null;
}

function describeStatus($r, $route) {
    $s = $r['status'];
    if ($s >= 200 && $s < 400) {
        echo "✓ $s POST $route\n";
        return true;
    }
    echo "✗ $s POST $route\n";
    // try to extract error
    if (preg_match("#\"message\":\"([^\"]+)\"#", $r['body'], $m)) echo "   {$m[1]}\n";
    if (preg_match('/<title>(.+?)<\/title>/s', $r['body'], $m)) echo "   {$m[1]}\n";
    return false;
}

// Login
$token = fetchCsrf("$base/login", $cookieJar);
$r = curlReq("$base/login", 'POST', [
    '_token' => $token,
    'email' => 'admin@bengkelpaten.id',
    'password' => 'password',
], $cookieJar);
if ($r['status'] !== 302) {
    echo "✗ Login failed: {$r['status']}\n";
    exit(1);
}
echo "✓ Logged in\n";

// Helper: get latest csrf from any authenticated page
function csrf($base, $cookieJar) {
    return fetchCsrf("$base/customers/create", $cookieJar);
}

$results = [];

// --- 1. Create Customer ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/customers", 'POST', [
    '_token' => $tok,
    'name' => 'UAT POST ' . uniqid(),
    'phone' => '0812' . rand(10000000, 99999999),
    'mobile' => '0813' . rand(10000000, 99999999),
    'email' => 'uatpost' . uniqid() . '@t.id',
    'address' => 'Jl. Test',
], $cookieJar);
$results['create-customer'] = describeStatus($r, '/customers');

// --- 2. Create Vehicle ---
$customer = \App\Models\Customer::latest('id')->first();
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/vehicles", 'POST', [
    '_token' => $tok,
    'customer_id' => $customer->id,
    'vehicle_brand_id' => \App\Models\VehicleBrand::first()->id,
    'vehicle_type_id' => \App\Models\VehicleType::first()->id,
    'fuel_type_id' => \App\Models\FuelType::first()->id,
    'number_plate' => 'POST ' . rand(1000, 9999),
    'model_name' => 'Test Model',
    'model_year' => 2022,
    'color' => 'red',
    'odometer' => 1000,
], $cookieJar);
$results['create-vehicle'] = describeStatus($r, '/vehicles');

// --- 3. Create Service ---
$vehicle = \App\Models\Vehicle::latest('id')->first();
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/services", 'POST', [
    '_token' => $tok,
    'customer_id' => $customer->id,
    'vehicle_id' => $vehicle->id,
    'repair_category_id' => \App\Models\RepairCategory::first()?->id,
    'title' => 'POST Test Service',
    'description' => 'desc',
    'service_date' => date('Y-m-d'),
    'charge' => 250000,
], $cookieJar);
$results['create-service'] = describeStatus($r, '/services');

// --- 4. Create Product ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/products", 'POST', [
    '_token' => $tok,
    'name' => 'POST Test Product',
    'code' => 'PST-' . uniqid(),
    'product_type_id' => \App\Models\ProductType::first()->id,
    'unit_id' => \App\Models\ProductUnit::first()->id,
    'price' => 50000,
    'cost_price' => 30000,
    'description' => 'desc',
], $cookieJar);
$results['create-product'] = describeStatus($r, '/products');

// --- 5. Create Supplier ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/suppliers", 'POST', [
    '_token' => $tok,
    'name' => 'POST Test Supplier',
    'phone' => '081200001111',
    'email' => 'sup' . uniqid() . '@t.id',
    'address' => 'addr',
], $cookieJar);
$results['create-supplier'] = describeStatus($r, '/suppliers');

// --- 6. Create Income ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/incomes", 'POST', [
    '_token' => $tok,
    'amount' => 100000,
    'income_date' => date('Y-m-d'),
    'label' => 'Test Income',
    'description' => 'desc',
], $cookieJar);
$results['create-income'] = describeStatus($r, '/incomes');

// --- 7. Create Expense ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/expenses", 'POST', [
    '_token' => $tok,
    'amount' => 50000,
    'expense_date' => date('Y-m-d'),
    'label' => 'Test Expense',
    'description' => 'desc',
], $cookieJar);
$results['create-expense'] = describeStatus($r, '/expenses');

// --- 8. Create Voucher ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/vouchers", 'POST', [
    '_token' => $tok,
    'code' => 'POST' . rand(100, 999),
    'name' => 'POST Voucher',
    'type' => 'percent',
    'value' => 10,
    'min_purchase' => 0,
    'max_discount' => 100000,
    'usage_limit' => 100,
    'valid_from' => date('Y-m-d'),
    'valid_until' => date('Y-m-d', strtotime('+30 days')),
    'is_active' => 1,
], $cookieJar);
$results['create-voucher'] = describeStatus($r, '/vouchers');

// --- 9. Create Notification Template ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/notification-templates", 'POST', [
    '_token' => $tok,
    'name' => 'POST Tpl ' . uniqid(),
    'slug' => 'post-tpl-' . uniqid(),
    'channel' => 'whatsapp',
    'body' => 'Hi {customer_name}',
], $cookieJar);
$results['create-tpl'] = describeStatus($r, '/notification-templates');

// --- 10. Create master data (one example: vehicle-brand) ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/vehicle-brands", 'POST', [
    '_token' => $tok,
    'name' => 'POST Brand ' . uniqid(),
], $cookieJar);
$results['create-brand'] = describeStatus($r, '/vehicle-brands');

// --- 11. Create payment-method ---
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/payment-methods", 'POST', [
    '_token' => $tok,
    'name' => 'POST PM ' . uniqid(),
    'is_active' => 1,
], $cookieJar);
$results['create-pm'] = describeStatus($r, '/payment-methods');

// --- 12. Create reminder ---
$svc = \App\Models\Service::latest('id')->first();
$tok = csrf($base, $cookieJar);
$r = curlReq("$base/reminders", 'POST', [
    '_token' => $tok,
    'customer_id' => $customer->id,
    'vehicle_id' => $vehicle->id,
    'service_id' => $svc->id,
    'reminder_type' => 'service_followup',
    'reminder_date' => date('Y-m-d', strtotime('+30 days')),
    'message' => 'test reminder',
], $cookieJar);
$results['create-reminder'] = describeStatus($r, '/reminders');

// --- 13. Public Booking ---
$tok = fetchCsrf("$base/booking", $cookieJar);
$r = curlReq("$base/booking", 'POST', [
    '_token' => $tok,
    'name' => 'Public Booking',
    'phone' => '0812' . rand(10000000, 99999999),
    'email' => 'pb' . uniqid() . '@t.id',
    'vehicle_plate' => 'PB ' . rand(1000, 9999),
    'vehicle_brand' => 'Honda',
    'vehicle_model' => 'Brio',
    'booking_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'complaint' => 'AC tidak dingin',
], $cookieJar);
$results['public-booking'] = describeStatus($r, '/booking');

echo "\n=== POST FLOW SUMMARY ===\n";
$ok = count(array_filter($results));
$total = count($results);
echo "✓ OK: $ok / $total\n";
foreach ($results as $name => $pass) {
    echo ($pass ? "✓" : "✗") . " $name\n";
}

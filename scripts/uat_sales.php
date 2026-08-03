<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$base = 'http://127.0.0.1:8124';
$cj = sys_get_temp_dir() . '/uat_sl_' . getmypid() . '.txt';
@unlink($cj);

function req($url, $method = 'GET', $data = null, $cj = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cj);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cj);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($ch);
    $s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $s, 'body' => $body];
}

function csrf($base, $url, $cj) {
    $r = req("$base$url", 'GET', null, $cj);
    preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
    return [$m[1] ?? null, $r['status']];
}

$r = req("$base/login", 'GET', null, $cj);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
req("$base/login", 'POST', ['_token' => $m[1], 'email' => 'admin@bengkelpaten.id', 'password' => 'password'], $cj);
echo "✓ Login\n";

$customer = \App\Models\Customer::first();
$vehicle = \App\Models\Vehicle::first();

[$tok, $s] = csrf($base, '/sales/create', $cj);
echo ($s==200?'✓':'✗') . " 1. GET /sales/create → $s\n";

$r = req("$base/sales", 'POST', [
    '_token' => $tok,
    'customer_id' => $customer->id,
    'vehicle_id' => $vehicle->id,
    'sale_date' => date('Y-m-d'),
    'status' => 'pending',
    'price' => 75000000,
    'down_payment' => 10000000,
    'description' => 'UAT sale test',
], $cj);
$sale = \App\Models\Sale::orderByDesc('id')->first();
echo ($r['status']==302 && $sale && (float)$sale->price == 75000000 ? '✓' : '✗') . " 2. Simpan → status={$r['status']}, price={$sale?->price}, dp={$sale?->down_payment}\n";

// Show
$r = req("$base/sales/{$sale->id}", 'GET', null, $cj);
echo ($r['status']==200?'✓':'✗') . " 3. GET /sales/{$sale->id} → {$r['status']}\n";
if ($r['status'] !== 200) echo substr($r['body'], 0, 400) . "\n";

// Edit
$r = req("$base/sales/{$sale->id}/edit", 'GET', null, $cj);
echo ($r['status']==200?'✓':'✗') . " 4. GET /sales/{$sale->id}/edit → {$r['status']}\n";

// Update
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$tok = $m[1];
$r = req("$base/sales/{$sale->id}", 'PUT', [
    '_token' => $tok,
    'customer_id' => $customer->id,
    'vehicle_id' => $vehicle->id,
    'sale_date' => date('Y-m-d'),
    'status' => 'completed',
    'price' => 80000000,
    'down_payment' => 20000000,
    'description' => 'updated',
], $cj);
$sale->refresh();
echo ($r['status']==302 && $sale->status==='completed' && (float)$sale->price == 80000000 ? '✓' : '✗') . " 5. Update → status={$sale->status}, price={$sale->price}\n";

// Delete
[$tok,] = csrf($base, '/sales/create', $cj);
$r = req("$base/sales/{$sale->id}", 'DELETE', ['_token' => $tok], $cj);
$exists = \App\Models\Sale::withTrashed()->find($sale->id);
echo ($r['status']==302 && $exists && $exists->trashed() ? '✓' : '✗') . " 6. Delete → trashed=" . ($exists?->trashed() ? 'yes' : 'no') . "\n";

\App\Models\Sale::withTrashed()->where('id', $sale->id)->forceDelete();
echo "\n✓ Cleanup\n";

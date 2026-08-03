<?php
/**
 * Test "Simpan Draft" button — should save as draft, not ordered.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$base = 'http://127.0.0.1:8124';
$cookieJar = sys_get_temp_dir() . '/uat_pd_' . getmypid() . '.txt';
@unlink($cookieJar);

function req($url, $method = 'GET', $data = null, $cookieJar = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $body];
}

$r = req("$base/login", 'GET', null, $cookieJar);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
req("$base/login", 'POST', ['_token' => $m[1], 'email' => 'admin@bengkelpaten.id', 'password' => 'password'], $cookieJar);

$r = req("$base/purchases/create", 'GET', null, $cookieJar);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$tok = $m[1];

$supplier = \App\Models\Supplier::first();
$product = \App\Models\Product::first();

// Simpan dengan status=draft (sesuai tombol "Simpan Draft")
$r = req("$base/purchases", 'POST', [
    '_token' => $tok,
    'supplier_id' => $supplier->id,
    'purchase_date' => date('Y-m-d'),
    'notes' => 'test draft',
    'status' => 'draft',  // <-- tombol "Simpan Draft" mengirim ini
    'items' => [
        ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 5000],
    ],
], $cookieJar);

echo "POST: {$r['status']}\n";

$last = \App\Models\Purchase::orderByDesc('id')->first();
echo "Purchase: id={$last->id} status={$last->status} (EXPECTED: draft)\n";
echo $last->status === 'draft' ? "✓ DRAFT BUTTON WORKS\n" : "✗ BUG: status saved as '{$last->status}' instead of 'draft'\n";

\App\Models\Purchase::where('id', $last->id)->forceDelete();

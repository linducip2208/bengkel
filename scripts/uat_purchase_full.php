<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$base = 'http://127.0.0.1:8124';
$cj = sys_get_temp_dir() . '/uat_pf_' . getmypid() . '.txt';
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

function getCsrf($base, $cj) {
    $r = req("$base/purchases/create", 'GET', null, $cj);
    preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
    return $m[1] ?? null;
}

// Login
$r = req("$base/login", 'GET', null, $cj);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
req("$base/login", 'POST', ['_token' => $m[1], 'email' => 'admin@bengkelpaten.id', 'password' => 'password'], $cj);
echo "✓ Login\n";

$supplier = \App\Models\Supplier::first();
$product = \App\Models\Product::first();
$product2 = \App\Models\Product::skip(1)->first() ?: $product;

// 1. Simpan Draft
$tok = getCsrf($base, $cj);
$r = req("$base/purchases", 'POST', [
    '_token' => $tok,
    'supplier_id' => $supplier->id,
    'purchase_date' => date('Y-m-d'),
    'status' => 'draft',
    'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 5000]],
], $cj);
$po = \App\Models\Purchase::orderByDesc('id')->first();
echo ($r['status']==302 && $po->status==='draft' ? '✓' : '✗') . " 1. Simpan Draft → status={$po->status}, id={$po->id}\n";

// 2. Edit (only allowed when draft)
$r = req("$base/purchases/{$po->id}/edit", 'GET', null, $cj);
echo ($r['status']==200 ? '✓' : '✗') . " 2. GET Edit form → {$r['status']}\n";

// 3. Update items
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$tok = $m[1] ?? null;
$r = req("$base/purchases/{$po->id}", 'PUT', [
    '_token' => $tok,
    'supplier_id' => $supplier->id,
    'purchase_date' => date('Y-m-d'),
    'items' => [
        ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 5000],
        ['product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 10000],
    ],
], $cj);
$po->refresh();
echo ($r['status']==302 && (float)$po->total_amount == 25000 ? '✓' : '✗') . " 3. Update → total={$po->total_amount} (expected 25000)\n";

// 4. Simpan & Pesan: create another PO with status=ordered
$tok = getCsrf($base, $cj);
$r = req("$base/purchases", 'POST', [
    '_token' => $tok,
    'supplier_id' => $supplier->id,
    'purchase_date' => date('Y-m-d'),
    'status' => 'ordered',
    'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 8000]],
], $cj);
$po2 = \App\Models\Purchase::orderByDesc('id')->first();
echo ($r['status']==302 && $po2->status==='ordered' ? '✓' : '✗') . " 4. Simpan & Pesan → status={$po2->status}\n";

// 5. Mark received (only allowed when ordered)
$tok = getCsrf($base, $cj);
$r = req("$base/purchases/{$po2->id}/mark-received", 'POST', ['_token' => $tok], $cj);
$po2->refresh();
echo ($r['status']==302 && $po2->status==='received' ? '✓' : '✗') . " 5. Tandai Diterima → status={$po2->status}\n";

// 6. Verify stock increased
$stock = \App\Models\StockRecord::where('product_id', $product->id)->first();
echo ($stock && $stock->quantity > 0 ? '✓' : '✗') . " 6. Stok product #{$product->id} = {$stock->quantity}\n";

// 7. Delete draft (only allowed when draft)
$tok = getCsrf($base, $cj);
$r = req("$base/purchases/{$po->id}", 'DELETE', ['_token' => $tok], $cj);
$exists = \App\Models\Purchase::find($po->id);
echo ($r['status']==302 && !$exists ? '✓' : '✗') . " 7. Hapus Draft → " . ($exists ? 'STILL EXISTS' : 'deleted') . "\n";

// 8. Try delete ordered/received (should fail)
$tok = getCsrf($base, $cj);
$r = req("$base/purchases/{$po2->id}", 'DELETE', ['_token' => $tok], $cj);
$exists = \App\Models\Purchase::find($po2->id);
echo ($exists ? '✓' : '✗') . " 8. Coba hapus PO yg sudah received → tetap ada (proteksi OK)\n";

// Cleanup
\App\Models\Purchase::where('purchase_no', 'like', 'PO-' . date('Ymd') . '%')->forceDelete();
echo "\n✓ Cleanup done\n";

<?php

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Contracts\Console\Kernel;

/**
 * Reproduce purchase flow:
 * 1. GET /purchases/create -> verifikasi form load
 * 2. POST /purchases dengan items -> verifikasi simpan
 * 3. GET /purchases/{id} -> show detail
 * 4. POST /purchases/{id}/mark-received -> terima barang
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$base = 'http://127.0.0.1:8124';
$cookieJar = sys_get_temp_dir().'/uat_purchase_'.getmypid().'.txt';
@unlink($cookieJar);

function req($url, $method = 'GET', $data = null, $cookieJar = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $head = substr($body, 0, $headerSize);
    $body = substr($body, $headerSize);
    curl_close($ch);

    return ['status' => $status, 'head' => $head, 'body' => $body];
}

// 1. Login
$r = req("$base/login", 'GET', null, $cookieJar);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$tok = $m[1] ?? null;
$r = req("$base/login", 'POST', ['_token' => $tok, 'email' => 'admin@bengkelpaten.id', 'password' => 'password'], $cookieJar);
echo "Login: {$r['status']}\n";

// 2. GET /purchases/create
$r = req("$base/purchases/create", 'GET', null, $cookieJar);
echo "GET /purchases/create: {$r['status']}\n";
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$tok = $m[1] ?? null;
echo 'CSRF token: '.($tok ? 'OK' : 'MISSING')."\n";

// Check JS — search-json endpoint
$searchUrl = "$base/products/search-json";
$r = req($searchUrl.'?q=', 'GET', null, $cookieJar);
echo "GET /products/search-json: {$r['status']}\n";
$products = json_decode($r['body'], true);
echo '  products returned: '.(is_array($products) ? count($products) : 'NULL')."\n";

if (! $products) {
    echo '  body sample: '.substr($r['body'], 0, 200)."\n";
    exit(1);
}

$supplier = Supplier::first();
$product = Product::first();
echo "  Using supplier=$supplier->id product=$product->id\n";

// 3. POST /purchases (Simpan & Pesan -> status=ordered)
$payload = [
    '_token' => $tok,
    'supplier_id' => $supplier->id,
    'purchase_date' => date('Y-m-d'),
    'notes' => 'UAT test PO',
    'status' => 'ordered',
    'items' => [
        [
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 10000,
        ],
    ],
];
$r = req("$base/purchases", 'POST', $payload, $cookieJar);
echo "POST /purchases (ordered): {$r['status']}\n";
if ($r['status'] !== 302) {
    echo "  Body sample:\n".substr($r['body'], 0, 1500)."\n";
}
// Check the Location header on redirect
if (preg_match('/Location:\s*(\S+)/i', $r['head'], $lm)) {
    echo "  Redirect: {$lm[1]}\n";
}

// 4. Verify saved
$last = Purchase::orderByDesc('id')->first();
echo "Last Purchase: id={$last->id} no={$last->purchase_no} status={$last->status} total={$last->total_amount}\n";
echo '  items count: '.$last->items()->count()."\n";

// 5. POST mark-received
$tok2 = null;
$r = req("$base/purchases/{$last->id}", 'GET', null, $cookieJar);
if (preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m)) {
    $tok2 = $m[1];
}

$r = req("$base/purchases/{$last->id}/mark-received", 'POST', ['_token' => $tok2], $cookieJar);
echo "POST mark-received: {$r['status']}\n";
if ($r['status'] !== 302) {
    echo substr($r['body'], 0, 600)."\n";
}

$last->refresh();
echo "After mark-received: status={$last->status}\n";

// Cleanup
Purchase::where('purchase_no', 'like', 'PO-'.date('Ymd').'%')->forceDelete();
echo "Cleanup OK\n";

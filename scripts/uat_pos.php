<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$base = 'http://127.0.0.1:8124';
$cj = sys_get_temp_dir() . '/uat_pos_' . getmypid() . '.txt';
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

// Login
$r = req("$base/login", 'GET', null, $cj);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
req("$base/login", 'POST', ['_token' => $m[1], 'email' => 'admin@bengkelpaten.id', 'password' => 'password'], $cj);

// Close any open session first
\App\Models\PosSession::where('user_id', \App\Models\User::first()->id)->where('status', 'open')->update(['status' => 'closed', 'closed_at' => now()]);

// 1. GET /pos/open
$r = req("$base/pos/open", 'GET', null, $cj);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$tok = $m[1] ?? null;
echo ($r['status']==200 && $tok ? '✓' : '✗') . " 1. GET /pos/open → {$r['status']}\n";

// 2. POST /pos/open (open session)
$r = req("$base/pos/open", 'POST', [
    '_token' => $tok,
    'opening_balance' => 500000,
    'notes' => 'UAT POS session',
], $cj);
echo ($r['status']==302?'✓':'✗') . " 2. POST /pos/open → {$r['status']}\n";

$session = \App\Models\PosSession::orderByDesc('id')->first();
echo "   Session #{$session->id} status={$session->status}\n";

// 3. GET /pos terminal
$r = req("$base/pos", 'GET', null, $cj);
echo ($r['status']==200?'✓':'✗') . " 3. GET /pos terminal → {$r['status']}\n";
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$tok = $m[1] ?? null;

// 4. GET /pos/search-product
$r = req("$base/pos/search-product?q=oli", 'GET', null, $cj);
$products = json_decode($r['body'], true);
echo ($r['status']==200?'✓':'✗') . " 4. Search product → " . (is_array($products) ? count($products) . ' results' : 'NULL') . "\n";

// 5. Checkout
$product = \App\Models\Product::with('stockRecord')->whereHas('stockRecord', fn($q) => $q->where('quantity', '>', 0))->first();
$pm = \App\Models\PaymentMethod::first();
$prevStock = $product->stockRecord->quantity;

$r = req("$base/pos/checkout", 'POST', [
    '_token' => $tok,
    'session_id' => $session->id,
    'items' => [
        ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 25000],
    ],
    'discount' => 5000,
    'amount_paid' => 50000,
    'payment_method_id' => $pm->id,
], $cj);
echo ($r['status']==302?'✓':'✗') . " 5. POS Checkout → {$r['status']}\n";

$inv = \App\Models\Invoice::where('invoice_type', 'pos')->orderByDesc('id')->first();
echo "   Invoice #{$inv->id} {$inv->invoice_number} grand_total={$inv->grand_total} paid={$inv->paid_amount}\n";

// 6. GET receipt
$r = req("$base/pos/receipt/{$inv->id}", 'GET', null, $cj);
echo ($r['status']==200?'✓':'✗') . " 6. GET receipt → {$r['status']}\n";

// 7. Verify stock decreased
$product->stockRecord->refresh();
$newStock = $product->stockRecord->quantity;
echo ($newStock == $prevStock - 2 ? '✓' : '✗') . " 7. Stock decreased: $prevStock → $newStock\n";

// 8. Close session
$r = req("$base/pos/sessions/{$session->id}/close", 'GET', null, $cj);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$tok = $m[1];
echo ($r['status']==200?'✓':'✗') . " 8. GET close form → {$r['status']}\n";

$r = req("$base/pos/sessions/{$session->id}/close", 'PUT', [
    '_token' => $tok,
    'closing_balance' => 545000,
    'notes' => 'UAT close',
], $cj);
$session->refresh();
echo ($r['status']==302 && $session->status==='closed' ? '✓' : '✗') . " 9. Close session → status={$session->status}\n";

// Cleanup
\App\Models\PaymentRecord::where('invoice_id', $inv->id)->delete();
\App\Models\InvoiceItem::where('invoice_id', $inv->id)->delete();
\App\Models\StockHistory::where('reference_id', $inv->id)->delete();
$inv->forceDelete();
$session->forceDelete();
$product->stockRecord->update(['quantity' => $prevStock]);
echo "\n✓ Cleanup\n";

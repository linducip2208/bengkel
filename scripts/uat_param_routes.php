<?php
/**
 * Tests parameterized routes (show/edit) by providing real IDs from DB.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$base = $argv[1] ?? 'http://127.0.0.1:8124';
$cookieJar = sys_get_temp_dir() . '/uat_cookies_param_' . getmypid() . '.txt';
@unlink($cookieJar);

function curlReq($url, $method = 'GET', $data = null, $cookieJar = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
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

// Login
$r = curlReq("$base/login", 'GET', null, $cookieJar);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$token = $m[1] ?? null;
$r = curlReq("$base/login", 'POST', [
    '_token' => $token,
    'email' => 'admin@bengkelpaten.id',
    'password' => 'password',
], $cookieJar);
if ($r['status'] !== 302) {
    echo "Login failed\n"; exit(1);
}
echo "✓ Logged in\n";

// Get real IDs from DB
$ids = [
    'customer' => \App\Models\Customer::first()?->id,
    'vehicle' => \App\Models\Vehicle::first()?->id,
    'service' => \App\Models\Service::first()?->id,
    'invoice' => \App\Models\Invoice::first()?->id,
    'product' => \App\Models\Product::first()?->id,
    'purchase' => \App\Models\Purchase::first()?->id,
    'sale' => \App\Models\Sale::first()?->id,
    'supplier' => \App\Models\Supplier::first()?->id,
    'income' => \App\Models\Income::first()?->id,
    'expense' => \App\Models\Expense::first()?->id,
    'observation_type' => \App\Models\ObservationType::first()?->id,
    'repair_category' => \App\Models\RepairCategory::first()?->id,
    'tax_rate' => \App\Models\TaxRate::first()?->id,
    'payment_method' => \App\Models\PaymentMethod::first()?->id,
    'product_unit' => \App\Models\ProductUnit::first()?->id,
    'product_type' => \App\Models\ProductType::first()?->id,
    'color' => \App\Models\Color::first()?->id,
    'fuel_type' => \App\Models\FuelType::first()?->id,
    'vehicle_brand' => \App\Models\VehicleBrand::first()?->id,
    'vehicle_type' => \App\Models\VehicleType::first()?->id,
    'branch' => \App\Models\Branch::first()?->id,
    'business_hour' => \App\Models\BusinessHour::first()?->id,
    'holiday' => \App\Models\Holiday::first()?->id,
    'washbay' => 1,
    'observation_point' => \App\Models\ObservationPoint::first()?->id,
    'inspection_point' => \App\Models\InspectionPointsLibrary::first()?->id,
    'checkout_category' => \App\Models\CheckoutCategory::first()?->id,
    'currency' => \App\Models\Currency::first()?->id,
    'country' => \App\Models\Country::first()?->id,
    'state' => \App\Models\State::first()?->id,
    'city' => \App\Models\City::first()?->id,
    'custom_field' => \App\Models\CustomField::first()?->id,
    'voucher' => \App\Models\Voucher::first()?->id ?: 1,
    'reminder' => \App\Models\Reminder::first()?->id ?: 1,
    'notification_template' => \App\Models\NotificationTemplate::first()?->id ?: 1,
    'gate_pass' => \App\Models\GatePass::first()?->id ?: 1,
    'jobcard' => \App\Models\Service::first()?->id,
    'note' => \App\Models\Note::first()?->id ?: 1,
];

exec('php artisan route:list --json', $out);
$routes = json_decode(implode('', $out), true);

$ok = 0; $bad = 0; $skipped = 0; $badList = [];

foreach ($routes as $route) {
    $method = $route['method'] ?? '';
    $uri = $route['uri'] ?? '';
    $name = $route['name'] ?? '';

    if (!str_contains($method, 'GET')) { continue; }
    if (!str_contains($uri, '{')) { continue; } // only param routes
    if (str_starts_with($uri, 'api/')) { continue; }
    if (str_contains($uri, '__pair')) { continue; }
    if (str_contains($uri, 'telescope') || str_contains($uri, 'horizon')) { continue; }
    if (str_starts_with($uri, 'track/')) { continue; }
    if (str_starts_with($uri, 'payment/callback/')) { continue; }
    if (str_starts_with($uri, 'best/')) { continue; }
    if (str_starts_with($uri, 'alternatives-to/')) { continue; }
    if (str_starts_with($uri, 'compare/')) { continue; }
    if (str_starts_with($uri, 'docs/')) { continue; }
    if (str_starts_with($uri, 'booking') && $method === 'POST') { continue; }

    // Substitute params
    $url = $uri;
    $skip = false;
    if (preg_match_all('/\{(\w+)(\??)\}/', $uri, $m, PREG_SET_ORDER)) {
        foreach ($m as $p) {
            $paramName = $p[1];
            $id = $ids[$paramName] ?? null;
            if (!$id && $p[2] !== '?') {
                $skip = true; break;
            }
            $url = str_replace($p[0], (string) ($id ?? ''), $url);
        }
    }
    if ($skip) { $skipped++; continue; }

    $full = $base . '/' . ltrim($url, '/');
    $res = curlReq($full, 'GET', null, $cookieJar);
    $s = $res['status'];
    if ($s === 200 || $s === 302 || $s === 304 || $s === 403) {
        $ok++;
        echo "✓ $s GET /$url [$name]\n";
    } else {
        $bad++;
        $msg = '';
        if (preg_match('#<title>(.+?)</title>#s', $res['body'], $m)) $msg = trim($m[1]);
        if (preg_match("#\"message\":\"([^\"]+)\"#", $res['body'], $m)) $msg .= ' :: ' . $m[1];
        if (preg_match('/<div class="exception-message-wrapper">.*?<div class="exception-message">(.+?)<\/div>/s', $res['body'], $m)) {
            $msg .= ' :: ' . trim(strip_tags($m[1]));
        }
        $badList[] = "$s GET /$url [$name] :: $msg";
        echo "✗ $s GET /$url [$name]\n   $msg\n";
    }
}

echo "\n=== PARAM ROUTE SUMMARY ===\n";
echo "✓ OK: $ok | ✗ FAIL: $bad | skipped (no fixture): $skipped\n";
if ($badList) {
    echo "\n=== FAILURES ===\n";
    foreach ($badList as $e) echo "  $e\n";
}

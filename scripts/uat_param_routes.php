<?php

use App\Models\Branch;
use App\Models\BusinessHour;
use App\Models\CheckoutCategory;
use App\Models\City;
use App\Models\Color;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Expense;
use App\Models\FuelType;
use App\Models\GatePass;
use App\Models\Holiday;
use App\Models\Income;
use App\Models\InspectionPointsLibrary;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\NotificationTemplate;
use App\Models\ObservationPoint;
use App\Models\ObservationType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Reminder;
use App\Models\RepairCategory;
use App\Models\Sale;
use App\Models\Service;
use App\Models\State;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Models\Voucher;
use Illuminate\Contracts\Console\Kernel;

/**
 * Tests parameterized routes (show/edit) by providing real IDs from DB.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$base = $argv[1] ?? 'http://127.0.0.1:8124';
$cookieJar = sys_get_temp_dir().'/uat_cookies_param_'.getmypid().'.txt';
@unlink($cookieJar);

function curlReq($url, $method = 'GET', $data = null, $cookieJar = null)
{
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
    echo "Login failed\n";
    exit(1);
}
echo "✓ Logged in\n";

// Get real IDs from DB
$ids = [
    'customer' => Customer::first()?->id,
    'vehicle' => Vehicle::first()?->id,
    'service' => Service::first()?->id,
    'invoice' => Invoice::first()?->id,
    'product' => Product::first()?->id,
    'purchase' => Purchase::first()?->id,
    'sale' => Sale::first()?->id,
    'supplier' => Supplier::first()?->id,
    'income' => Income::first()?->id,
    'expense' => Expense::first()?->id,
    'observation_type' => ObservationType::first()?->id,
    'repair_category' => RepairCategory::first()?->id,
    'tax_rate' => TaxRate::first()?->id,
    'payment_method' => PaymentMethod::first()?->id,
    'product_unit' => ProductUnit::first()?->id,
    'product_type' => ProductType::first()?->id,
    'color' => Color::first()?->id,
    'fuel_type' => FuelType::first()?->id,
    'vehicle_brand' => VehicleBrand::first()?->id,
    'vehicle_type' => VehicleType::first()?->id,
    'branch' => Branch::first()?->id,
    'business_hour' => BusinessHour::first()?->id,
    'holiday' => Holiday::first()?->id,
    'washbay' => 1,
    'observation_point' => ObservationPoint::first()?->id,
    'inspection_point' => InspectionPointsLibrary::first()?->id,
    'checkout_category' => CheckoutCategory::first()?->id,
    'currency' => Currency::first()?->id,
    'country' => Country::first()?->id,
    'state' => State::first()?->id,
    'city' => City::first()?->id,
    'custom_field' => CustomField::first()?->id,
    'voucher' => Voucher::first()?->id ?: 1,
    'reminder' => Reminder::first()?->id ?: 1,
    'notification_template' => NotificationTemplate::first()?->id ?: 1,
    'gate_pass' => GatePass::first()?->id ?: 1,
    'jobcard' => Service::first()?->id,
    'note' => Note::first()?->id ?: 1,
];

exec('php artisan route:list --json', $out);
$routes = json_decode(implode('', $out), true);

$ok = 0;
$bad = 0;
$skipped = 0;
$badList = [];

foreach ($routes as $route) {
    $method = $route['method'] ?? '';
    $uri = $route['uri'] ?? '';
    $name = $route['name'] ?? '';

    if (! str_contains($method, 'GET')) {
        continue;
    }
    if (! str_contains($uri, '{')) {
        continue;
    } // only param routes
    if (str_starts_with($uri, 'api/')) {
        continue;
    }
    if (str_contains($uri, '__pair')) {
        continue;
    }
    if (str_contains($uri, 'telescope') || str_contains($uri, 'horizon')) {
        continue;
    }
    if (str_starts_with($uri, 'track/')) {
        continue;
    }
    if (str_starts_with($uri, 'payment/callback/')) {
        continue;
    }
    if (str_starts_with($uri, 'best/')) {
        continue;
    }
    if (str_starts_with($uri, 'alternatives-to/')) {
        continue;
    }
    if (str_starts_with($uri, 'compare/')) {
        continue;
    }
    if (str_starts_with($uri, 'docs/')) {
        continue;
    }
    if (str_starts_with($uri, 'booking') && $method === 'POST') {
        continue;
    }

    // Substitute params
    $url = $uri;
    $skip = false;
    if (preg_match_all('/\{(\w+)(\??)\}/', $uri, $m, PREG_SET_ORDER)) {
        foreach ($m as $p) {
            $paramName = $p[1];
            $id = $ids[$paramName] ?? null;
            if (! $id && $p[2] !== '?') {
                $skip = true;
                break;
            }
            $url = str_replace($p[0], (string) ($id ?? ''), $url);
        }
    }
    if ($skip) {
        $skipped++;

        continue;
    }

    $full = $base.'/'.ltrim($url, '/');
    $res = curlReq($full, 'GET', null, $cookieJar);
    $s = $res['status'];
    if ($s === 200 || $s === 302 || $s === 304 || $s === 403) {
        $ok++;
        echo "✓ $s GET /$url [$name]\n";
    } else {
        $bad++;
        $msg = '';
        if (preg_match('#<title>(.+?)</title>#s', $res['body'], $m)) {
            $msg = trim($m[1]);
        }
        if (preg_match('#"message":"([^"]+)"#', $res['body'], $m)) {
            $msg .= ' :: '.$m[1];
        }
        if (preg_match('/<div class="exception-message-wrapper">.*?<div class="exception-message">(.+?)<\/div>/s', $res['body'], $m)) {
            $msg .= ' :: '.trim(strip_tags($m[1]));
        }
        $badList[] = "$s GET /$url [$name] :: $msg";
        echo "✗ $s GET /$url [$name]\n   $msg\n";
    }
}

echo "\n=== PARAM ROUTE SUMMARY ===\n";
echo "✓ OK: $ok | ✗ FAIL: $bad | skipped (no fixture): $skipped\n";
if ($badList) {
    echo "\n=== FAILURES ===\n";
    foreach ($badList as $e) {
        echo "  $e\n";
    }
}

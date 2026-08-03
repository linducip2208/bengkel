<?php
/**
 * Hits every authenticated GET route as an admin user via a CookieJar+Curl session.
 * Reports HTTP status + first error line from response if status != 200/302.
 */

$base = $argv[1] ?? 'http://127.0.0.1:8124';
$cookieJar = sys_get_temp_dir() . '/uat_cookies_' . getmypid() . '.txt';
@unlink($cookieJar);

function curlReq($url, $method = 'GET', $data = null, $cookieJar = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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

// Step 1: GET login → grab CSRF token
$r = curlReq("$base/login", 'GET', null, $cookieJar);
preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m);
$token = $m[1] ?? null;
if (!$token) {
    echo "✗ Login page CSRF token not found. Status={$r['status']}\n";
    exit(1);
}

// Step 2: POST login
$r = curlReq("$base/login", 'POST', [
    '_token' => $token,
    'email' => 'admin@bengkelpaten.id',
    'password' => 'password',
], $cookieJar);

if ($r['status'] !== 302) {
    echo "✗ Login failed. Status={$r['status']}\n";
    if (preg_match('/<title>(.+?)<\/title>/s', $r['body'], $m)) echo "   Title: {$m[1]}\n";
    echo substr($r['body'], 0, 500) . "\n";
    exit(1);
}
echo "✓ Logged in\n\n";

// Step 3: gather routes via artisan
exec('php artisan route:list --json', $out);
$routes = json_decode(implode('', $out), true);
if (!$routes) {
    echo "✗ Cannot get route list\n";
    exit(1);
}

$skipPatterns = [
    '#^api/#',
    '#__pair#',
    '#^_ignition#',
    '#\{.*\}#',          // routes with required parameters (need fixtures)
    '#^logout#',
    '#sanctum#',
    '#telescope#',
    '#horizon#',
];

$ok = 0; $bad = 0; $skip = 0;
$badList = [];

foreach ($routes as $r) {
    $method = $r['method'] ?? '';
    $uri = $r['uri'] ?? '';
    $name = $r['name'] ?? '';
    if (!str_contains($method, 'GET')) { $skip++; continue; }

    $shouldSkip = false;
    foreach ($skipPatterns as $p) {
        if (preg_match($p, $uri)) { $shouldSkip = true; break; }
    }
    if ($shouldSkip) { $skip++; continue; }

    $url = $base . '/' . ltrim($uri, '/');
    $res = curlReq($url, 'GET', null, $cookieJar);
    $s = $res['status'];

    if ($s === 200 || $s === 302 || $s === 304) {
        $ok++;
        echo "✓ $s GET /$uri" . ($name ? " [$name]" : '') . "\n";
    } else {
        $bad++;
        // Extract Laravel exception message
        $msg = '';
        if (preg_match('#<title>(.+?)</title>#s', $res['body'], $m)) $msg = trim($m[1]);
        if (preg_match('#<div class="exception_title">.*?<h1[^>]*>(.+?)</h1>#s', $res['body'], $m)) $msg .= ' :: ' . trim($m[1]);
        if (preg_match('#<span class="exception_message">(.+?)</span>#s', $res['body'], $m)) $msg .= ' :: ' . strip_tags($m[1]);
        if (preg_match("#\"message\":\"([^\"]+)\"#", $res['body'], $m)) $msg .= ' :: ' . $m[1];
        $badList[] = "$s GET /$uri [$name] :: $msg";
        echo "✗ $s GET /$uri [$name]\n   $msg\n";
    }
}

echo "\n=== ROUTE SUMMARY ===\n";
echo "✓ OK: $ok\n";
echo "✗ FAIL: $bad\n";
echo "  skipped: $skip\n";

if ($badList) {
    echo "\n=== FAILURES ===\n";
    foreach ($badList as $e) echo "  $e\n";
}

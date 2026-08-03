<?php
/**
 * For each blade view, extract @foreach($x ...) and {{ $x }} usage,
 * then find the controller method that loads it and check whether the
 * controller compact()/with() passes those variables.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ctrlDirs = [
    __DIR__ . '/../app/Http/Controllers',
];

// Build map of view name -> controller methods that render it
$viewUsage = []; // viewName => [ [file, method, passedVars] ]

foreach ($ctrlDirs as $d) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d));
    foreach ($rii as $fi) {
        if ($fi->isDir() || !str_ends_with($fi->getFilename(), '.php')) continue;
        $src = file_get_contents($fi->getPathname());

        // Find each public function and capture which view(...) calls happen + which vars passed
        if (!preg_match_all('/public function (\w+)\([^)]*\)[^{]*\{((?:[^{}]|\{[^{}]*\})*)\}/s', $src, $methods, PREG_SET_ORDER)) continue;

        foreach ($methods as $mm) {
            $methodName = $mm[1];
            $body = $mm[2];

            if (!preg_match_all("/view\(['\"]([\w.\-_]+)['\"]\s*,\s*(compact\([^)]+\)|\[[^\]]*\])?\)?/", $body, $vm, PREG_SET_ORDER)) continue;

            foreach ($vm as $v) {
                $viewName = $v[1];
                $argSrc = $v[2] ?? '';
                $passed = [];

                // compact('a','b',...)
                if (preg_match('/compact\(([^)]+)\)/', $argSrc, $cm)) {
                    if (preg_match_all("/['\"](\w+)['\"]/", $cm[1], $cs)) {
                        $passed = array_merge($passed, $cs[1]);
                    }
                }
                // Inline array ['a' => ..., 'b' => ...]
                if (preg_match("/\[([^\]]*?)\]/", $argSrc, $am)) {
                    if (preg_match_all("/['\"](\w+)['\"]\s*=>/", $am[1], $is)) {
                        $passed = array_merge($passed, $is[1]);
                    }
                }
                // ->with('foo', ...)  catch on view returned then chained — search the surrounding body too
                if (preg_match_all("/->with\(['\"](\w+)['\"]/", $body, $wm)) {
                    $passed = array_merge($passed, $wm[1]);
                }

                $viewUsage[$viewName][] = ['ctrl' => $fi->getFilename(), 'method' => $methodName, 'passed' => array_unique($passed)];
            }
        }
    }
}

// Now for each view, parse blade variables used at top level (heuristic: $name in @foreach/foreach/@if/@isset/explicit usage), exclude common locals
$viewsDir = __DIR__ . '/../resources/views';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));

$ignoreNames = [
    'this','user','message','errors','loop','request','app','route','config','auth','session','env',
    'value','item','i','idx','index','key','val','data','obj','x','y','title','content','attr','attributes','slot',
];

$problems = [];

foreach ($rii as $fi) {
    if ($fi->isDir() || !str_ends_with($fi->getFilename(), '.blade.php')) continue;
    $rel = ltrim(str_replace([$viewsDir, '\\', '/'], ['', '.', '.'], $fi->getPathname()), '.');
    $rel = preg_replace('/\.blade\.php$/', '', $rel);
    $rel = ltrim($rel, '.');

    if (!isset($viewUsage[$rel])) continue;

    $src = file_get_contents($fi->getPathname());

    // Detect vars used directly (top-level, NOT inside @foreach loop variable)
    // We look for @foreach($X as ...), @if($X), @isset($X), {{ $X }} where X is not declared by @php $X = ...
    $declared = [];
    if (preg_match_all('/@php\s*\$(\w+)\s*=/', $src, $pm)) $declared = array_merge($declared, $pm[1]);
    if (preg_match_all('/@foreach\s*\(\s*\$\w+(?:->\w+|\[[^\]]+\])*\s+as\s+(?:\$(\w+)\s*=>\s*)?\$(\w+)/', $src, $fm)) {
        $declared = array_merge($declared, $fm[1], $fm[2]);
    }
    if (preg_match_all('/@for\s*\(\s*\$(\w+)/', $src, $fmm)) $declared = array_merge($declared, $fmm[1]);
    if (preg_match_all('/@forelse\s*\(\s*\$\w+\s+as\s+\$(\w+)/', $src, $fem)) $declared = array_merge($declared, $fem[1]);
    if (preg_match_all('/@\s*foreach\s*\([^)]+as\s+\$(\w+)/', $src, $fem2)) $declared = array_merge($declared, $fem2[1]);

    $allRefs = [];
    if (preg_match_all('/\$(\w+)/', $src, $rm)) $allRefs = $rm[1];

    $vars = array_unique($allRefs);
    $vars = array_diff($vars, $declared, $ignoreNames);

    foreach ($viewUsage[$rel] as $cu) {
        $passed = $cu['passed'];
        $missing = array_diff($vars, $passed);
        // Remove method-call-only or short loop vars
        $missing = array_filter($missing, fn($v) => strlen($v) > 1 && !ctype_digit($v));
        if ($missing) {
            $problems[] = "View '$rel' references \$" . implode(', $', $missing) . " but {$cu['ctrl']}::{$cu['method']}() didn't pass them";
        }
    }
}

echo "=== POTENTIAL UNPASSED VIEW VARIABLES ===\n";
foreach ($problems as $p) echo "⚠ $p\n";
echo "\nTotal: " . count($problems) . "\n";

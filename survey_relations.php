<?php

/**
 * Survey: relation methods in app/Models missing native return types.
 * These are the root cause of the 384 phpstan-baseline entries
 * (Larastan cannot infer related-model types without them).
 */
$dir = __DIR__.'/app/Models';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$report = [];

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $code = file_get_contents($path);
    $relPath = str_replace('\\', '/', substr($path, strlen(__DIR__) + 1));

    if (preg_match_all('/public\s+function\s+(\w+)\s*\([^)]*\)\s*\n?\s*\{\s*\n?\s*return\s+\$this->(hasMany|belongsTo|belongsToMany|hasOne|morphTo|morphMany|morphToMany|hasManyThrough)\(/', $code, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $i => $nameMatch) {
            $report[] = [$relPath, $nameMatch[0], $m[2][$i][0]];
        }
    }
}

echo count($report), " relation methods missing return types:\n";
$byType = [];
foreach ($report as [$f, $method, $type]) {
    $byType[$type] = ($byType[$type] ?? 0) + 1;
    echo "  $f :: $method() -> $type\n";
}
echo "\nBy type:\n";
print_r($byType);

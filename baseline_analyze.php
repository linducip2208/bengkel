<?php

// Analyze phpstan-baseline.neon (tab-indented format).

$content = file_get_contents(__DIR__.'/phpstan-baseline.neon');

preg_match_all('/message:\s+\'#?\^?(.*?)\.?\$?#?\'\s*\n\s*identifier:\s+([a-zA-Z.]+)\s*\n(?:\s*count:\s+(\d+)\s*\n)?\s*path:\s+([^\n]+)/', $content, $m, PREG_SET_ORDER);

echo 'parsed: ', count($m), " entries\n";

$byMsg = [];
$byFileProp = [];

foreach ($m as $e) {
    [$all, $msg, $id, $count, $path] = array_pad($e, 5, 1);
    $count = (int) ($count ?: 1);
    $path = trim($path);

    $norm = preg_replace('/\\\\\$[A-Za-z]+/', '$prop', $msg);
    $key = $id.' :: '.$norm;
    $byMsg[$key] = ($byMsg[$key] ?? 0) + $count;

    if ($id === 'property.notFound') {
        $byFileProp[$path] = ($byFileProp[$path] ?? 0) + $count;
    }
}

arsort($byMsg);
echo "\n=== TOP MESSAGES ===\n";
$i = 0;
foreach ($byMsg as $k => $n) {
    echo str_pad((string) $n, 5), substr((string) $k, 0, 140), "\n";
    if (++$i >= 18) {
        break;
    }
}

arsort($byFileProp);
echo "\n=== property.notFound BY FILE ===\n";
$i = 0;
foreach ($byFileProp as $f => $n) {
    echo str_pad((string) $n, 5), $f, "\n";
    if (++$i >= 25) {
        break;
    }
}

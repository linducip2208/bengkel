<?php

/**
 * Targeted auto-fixes driven by phpstan raw output:
 *  1. nullsafe.neverNull  : "$a?->prop ?? X" -> "$a->prop" (phpstan proved prop never null)
 *     (keep the fallback removal conservative: only strip "?->" and trailing " ?? expr"
 *      when identifier is nullsafe.neverNull)
 *  2. class.notFound Log  : "\Log::" -> "Illuminate\Support\Facades\Log::"
 */

$raw = shell_exec('php vendor/phpstan/phpstan/phpstan analyse --no-progress --memory-limit=1G --error-format=raw 2>&1');
$lines = array_filter(explode("\n", (string) $raw));

$nullsafeByFile = [];
$logFiles = [];

foreach ($lines as $line) {
    if (preg_match('/^(.+?):(\d+):\s*(.*)$/', trim($line), $m)) {
        [$all, $file, $lineNo, $msg] = $m;
        if (str_contains($msg, 'Using nullsafe property access')) {
            $nullsafeByFile[$file][] = (int) $lineNo;
        }
        if (str_contains($msg, 'unknown class Log')) {
            $logFiles[$file] = true;
        }
    }
}

$totalFixed = 0;

// --- 1. Nullsafe ---
foreach ($nullsafeByFile as $file => $lineNos) {
    $rows = file($file);
    foreach (array_unique($lineNos) as $ln) {
        $idx = $ln - 1;
        if (! isset($rows[$idx])) continue;
        $original = $rows[$idx];

        // "$svc->vehicle?->number_plate ?? '-'" -> "$svc->vehicle->number_plate"
        // Only when the SAME line contains "?->" followed later by "??"
        $fixed = preg_replace(
            '/(\w+)\?->(\w+)\s*\?\?\s*([^;,)\n]+)/',
            '$1->$2',
            $original,
            -1,
            $count
        );

        if ($count > 0 && $fixed !== null) {
            $rows[$idx] = $fixed;
            $totalFixed += $count;
            echo "nullsafe: {$file}:{$ln}\n";
        }
    }
    file_put_contents($file, implode('', $rows));
}

echo "nullsafe fixed: {$totalFixed}\n";

// --- 2. Log facade imports ---
foreach (array_keys($logFiles) as $file) {
    $code = file_get_contents($file);

    if (! preg_match('/(^|[^\w\\\])Log::/', $code) && ! str_contains($code, '\\Log::')) continue;
    if (str_contains($code, 'use Illuminate\\Support\\Facades\\Log;')) continue;

    // Normalize \Log:: to Log:: then import the facade
    $code = str_replace('\\Log::', 'Log::', $code);

    $new = preg_replace(
        '/^(use\s+[^\n]+;\n)(?!use)/m',
        '$1use Illuminate\\Support\\Facades\\Log;' . "\n",
        $code,
        1,
        $count
    );
    if ($count === 0) {
        $new = preg_replace(
            '/^(<\?php\n\n)/m',
            '$1use Illuminate\\Support\\Facades\\Log;' . "\n\n",
            $code,
            1,
            $count
        );
    }

    if ($count > 0 && $new !== null) {
        file_put_contents($file, $new);
        echo "log import: {$file}\n";
    } else {
        echo "LOG IMPORT FAILED: {$file}\n";
    }
}

echo "done\n";

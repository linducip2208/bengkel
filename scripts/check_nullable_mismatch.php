<?php
/**
 * Find columns marked NOT NULL in DB but `nullable` in Form Request validator
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Map FormRequest -> model/table guess
$requests = glob(__DIR__ . '/../app/Http/Requests/*Request.php');
$mismatches = [];

foreach ($requests as $rf) {
    $name = pathinfo($rf, PATHINFO_FILENAME);
    $base = preg_replace('/Request$/', '', $name);
    $model = "App\\Models\\$base";
    if (!class_exists($model)) continue;
    $inst = new $model;
    $table = $inst->getTable();
    if (!Schema::hasTable($table)) continue;

    $src = file_get_contents($rf);
    if (!preg_match("/rules\(\)[^{]*\{(.+?)\}/s", $src, $m)) continue;
    $rulesBody = $m[1];

    // Parse 'field' => ['nullable', ...] OR 'field' => 'nullable|...'
    if (!preg_match_all("/['\"]([\w_]+)['\"]\s*=>\s*(\[[^\]]+\]|['\"][^'\"]+['\"])/", $rulesBody, $matches)) continue;

    $cols = DB::select("SHOW COLUMNS FROM `$table`");
    $colMap = [];
    foreach ($cols as $c) { $colMap[$c->Field] = ['null' => $c->Null === 'YES', 'def' => $c->Default]; }

    for ($i = 0; $i < count($matches[1]); $i++) {
        $field = $matches[1][$i];
        $rule = $matches[2][$i];
        $isNullable = str_contains($rule, 'nullable');
        $isRequired = preg_match("/['\"]required['\"]/", $rule) || str_contains($rule, "'required'") || str_contains($rule, '"required"');

        if (!isset($colMap[$field])) continue;
        $dbNullable = $colMap[$field]['null'];
        $dbDefault = $colMap[$field]['def'];

        if ($isNullable && !$dbNullable && $dbDefault === null) {
            $mismatches[] = "$table.$field — validator says NULLABLE but DB is NOT NULL (no default)";
            echo "✗ $table.$field — validator says NULLABLE but DB is NOT NULL (no default)\n";
        }
    }
}

echo "\nTotal: " . count($mismatches) . "\n";

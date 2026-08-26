<?php

/**
 * Scan controllers for inline $request->validate() calls and detect nullable/DB mismatches.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$controllers = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../app/Http/Controllers'));
foreach ($rii as $fi) {
    if ($fi->isDir() || ! str_ends_with($fi->getFilename(), '.php')) {
        continue;
    }
    $controllers[] = $fi->getPathname();
}

$tableCache = [];
function getTableCols($table)
{
    global $tableCache;
    if (! isset($tableCache[$table])) {
        if (! Schema::hasTable($table)) {
            return null;
        }
        $cols = DB::select("SHOW COLUMNS FROM `$table`");
        $tableCache[$table] = [];
        foreach ($cols as $c) {
            $tableCache[$table][$c->Field] = ['null' => $c->Null === 'YES', 'def' => $c->Default];
        }
    }

    return $tableCache[$table];
}

// Map model name -> table
$modelTable = [];
foreach (glob(__DIR__.'/../app/Models/*.php') as $mf) {
    $cls = 'App\\Models\\'.pathinfo($mf, PATHINFO_FILENAME);
    if (! class_exists($cls)) {
        continue;
    }
    try {
        $modelTable[pathinfo($mf, PATHINFO_FILENAME)] = (new $cls)->getTable();
    } catch (Throwable $e) {
    }
}

$count = 0;
foreach ($controllers as $cf) {
    $src = file_get_contents($cf);
    // Find Model::create([...]) and Model::find/findOrFail then ->update
    if (! preg_match_all('/(\w+)::(?:create|firstOrCreate|updateOrCreate)\s*\(/', $src, $m)) {
        continue;
    }

    // For each controller, also find inline $request->validate calls
    preg_match_all('/\$request->validate\(\s*\[(.+?)\]\s*\)/s', $src, $valMatches);
    if (empty($valMatches[1])) {
        continue;
    }

    foreach ($valMatches[1] as $rulesBody) {
        // For each model used in this controller, check
        $usedModels = array_unique($m[1]);
        foreach ($usedModels as $modelName) {
            if (! isset($modelTable[$modelName])) {
                continue;
            }
            $table = $modelTable[$modelName];
            $colMap = getTableCols($table);
            if (! $colMap) {
                continue;
            }

            if (! preg_match_all("/['\"]([\w_]+)['\"]\s*=>\s*(\[[^\]]+\]|['\"][^'\"]+['\"])/", $rulesBody, $rmatches)) {
                continue;
            }
            for ($i = 0; $i < count($rmatches[1]); $i++) {
                $field = $rmatches[1][$i];
                $rule = $rmatches[2][$i];
                if (! isset($colMap[$field])) {
                    continue;
                }
                if (! $colMap[$field]['null'] && $colMap[$field]['def'] === null && str_contains($rule, 'nullable')) {
                    echo "✗ $table.$field — DB NOT NULL but inline validator says nullable @ ".basename($cf)."\n";
                    $count++;
                }
            }
        }
    }
}
echo "Total: $count\n";

<?php

/**
 * UAT Audit Script — detects model/DB/route mismatches without inserting dummy data.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$errors = [];

function err(&$errors, $area, $msg)
{
    $errors[] = "[$area] $msg";
    echo "✗ [$area] $msg\n";
}

echo "=== AUDIT 1: Model fillable -> DB column ===\n";
$modelsDir = __DIR__.'/../app/Models';
foreach (glob($modelsDir.'/*.php') as $file) {
    $class = 'App\\Models\\'.pathinfo($file, PATHINFO_FILENAME);
    if (! class_exists($class)) {
        continue;
    }
    try {
        $inst = new $class;
        $table = $inst->getTable();
        if (! Schema::hasTable($table)) {
            err($errors, 'MODEL', "$class table '$table' does not exist");

            continue;
        }
        $cols = Schema::getColumnListing($table);
        foreach ($inst->getFillable() as $f) {
            if (! in_array($f, $cols)) {
                err($errors, 'COLUMN', "$class fillable '$f' MISSING in '$table'");
            }
        }
    } catch (Throwable $e) {
        err($errors, 'MODEL', "$class -> ".$e->getMessage());
    }
}

echo "\n=== AUDIT 2: Route -> controller method ===\n";
foreach (Route::getRoutes() as $route) {
    $action = $route->getAction();
    if (! isset($action['controller']) || ! str_contains($action['controller'], '@')) {
        continue;
    }
    [$class, $method] = explode('@', $action['controller']);
    if (! class_exists($class)) {
        err($errors, 'ROUTE', "Class $class missing for ".$route->uri());

        continue;
    }
    if (! method_exists($class, $method)) {
        err($errors, 'ROUTE', "$class@$method MISSING for ".$route->methods()[0].' /'.$route->uri());
    }
}

echo "\n=== AUDIT 3: BelongsTo FK column exists ===\n";
foreach (glob($modelsDir.'/*.php') as $file) {
    $class = 'App\\Models\\'.pathinfo($file, PATHINFO_FILENAME);
    if (! class_exists($class)) {
        continue;
    }
    try {
        $inst = new $class;
        $table = $inst->getTable();
        if (! Schema::hasTable($table)) {
            continue;
        }
        $cols = Schema::getColumnListing($table);
        $ref = new ReflectionClass($class);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $class) {
                continue;
            }
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }
            $returnType = $method->getReturnType();
            if (! $returnType) {
                continue;
            }
            $typeName = $returnType instanceof ReflectionNamedType ? $returnType->getName() : '';
            if ($typeName !== 'Illuminate\Database\Eloquent\Relations\BelongsTo') {
                continue;
            }
            try {
                $rel = $inst->{$method->getName()}();
                $fk = $rel->getForeignKeyName();
                if (! in_array($fk, $cols)) {
                    err($errors, 'FK', "$class::{$method->getName()}() FK '$fk' MISSING in '$table'");
                }
            } catch (Throwable $e) {
                err($errors, 'FK', "$class::{$method->getName()}() -> ".$e->getMessage());
            }
        }
    } catch (Throwable $e) {
    }
}

echo "\n=== AUDIT 4: Controllers index view exists ===\n";
$controllerDirs = [
    'app/Http/Controllers' => 'App\Http\Controllers',
    'app/Http/Controllers/Tenant' => 'App\Http\Controllers\Tenant',
];
$checked = [];
foreach ($controllerDirs as $dir => $ns) {
    foreach (glob(__DIR__.'/../'.$dir.'/*.php') as $cf) {
        $cls = $ns.'\\'.pathinfo($cf, PATHINFO_FILENAME);
        if (! class_exists($cls)) {
            continue;
        }
        if (isset($checked[$cls])) {
            continue;
        }
        $checked[$cls] = true;
        $content = file_get_contents($cf);
        if (preg_match_all("/view\(['\"]([\w.\-_]+)['\"]/", $content, $m)) {
            foreach ($m[1] as $v) {
                if (! View::exists($v)) {
                    err($errors, 'VIEW', "$cls references missing view '$v'");
                }
            }
        }
    }
}

echo "\n=== AUDIT 5: route() calls in views/controllers point to existing routes ===\n";
$allRouteNames = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->toArray();
$searchDirs = [
    __DIR__.'/../resources/views',
    __DIR__.'/../app/Http/Controllers',
];
foreach ($searchDirs as $d) {
    if (! is_dir($d)) {
        continue;
    }
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d));
    foreach ($rii as $fi) {
        if ($fi->isDir()) {
            continue;
        }
        $name = $fi->getFilename();
        if (! str_ends_with($name, '.php') && ! str_ends_with($name, '.blade.php')) {
            continue;
        }
        $content = @file_get_contents($fi->getPathname());
        if (! $content) {
            continue;
        }
        if (preg_match_all("/route\(['\"]([\w.\-_:]+)['\"]/", $content, $m)) {
            foreach ($m[1] as $rn) {
                if (! in_array($rn, $allRouteNames)) {
                    err($errors, 'ROUTENAME', "'$rn' used in ".str_replace('\\', '/', basename(dirname($fi->getPathname()))).'/'.$name.' — NOT REGISTERED');
                }
            }
        }
    }
}

echo "\n\n=== TOTAL ERRORS: ".count($errors)." ===\n";

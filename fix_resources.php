<?php

/**
 * Add @mixin docblocks to JSON Resources so Larastan resolves magic
 * property access ($this->field) to the underlying model.
 */
$map = [
    'CustomerResource' => 'Customer',
    'VehicleResource' => 'Vehicle',
    'ProductResource' => 'Product',
    'ServiceResource' => 'Service',
    'InvoiceResource' => 'Invoice',
    'InvoiceItemResource' => 'InvoiceItem',
    'PaymentRecordResource' => 'PaymentRecord',
    'SupplierResource' => 'Supplier',
    'PurchaseResource' => 'Purchase',
    'PurchaseItemResource' => 'PurchaseItem',
    'PurchaseHistoryResource' => 'PurchaseHistoryRecord',
    'SaleResource' => 'Sale',
    'IncomeResource' => 'Income',
    'ExpenseResource' => 'Expense',
    'JobcardResource' => 'JobcardDetail',
];

$dir = __DIR__.'/app/Http/Resources';
$changed = 0;

foreach (glob($dir.'/*Resource.php') as $file) {
    $code = file_get_contents($file);
    $class = basename($file, '.php');

    if (! isset($map[$class])) {
        continue;
    }
    if (str_contains($code, '@mixin')) {
        continue;
    }

    $model = $map[$class];
    $docblock = "/**\n * @mixin \\App\\Models\\{$model}\n */\n";

    // Insert right before "class X"
    $new = preg_replace(
        '/^(#\[Fillable[^\n]*\]\n)?(?!.*@mixin)(class\s+'.$class.')/m',
        "$1{$docblock}$2",
        $code,
        1,
        $count
    );

    if ($count === 0) {
        // Class may have attributes on separate lines; fallback insert before class keyword
        $new = preg_replace(
            '/^class\s+'.$class.'/m',
            "{$docblock}class {$class}",
            $code,
            1,
            $count
        );
    }

    if ($count > 0 && $new !== null) {
        file_put_contents($file, $new);
        $changed++;
        echo "mixin added: {$class}\n";
    } else {
        echo "SKIPPED: {$class}\n";
    }
}

echo "\nChanged: {$changed}\n";

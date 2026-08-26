<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Negative stock policy
    |--------------------------------------------------------------------------
    | When false (default), StockService refuses any mutation that would take
    | a product's stock below zero — two concurrent requests cannot oversell
    | the same available unit. Only enable this deliberately, per business
    | decision, e.g. drop-shipping workflows that reconcile later.
    */

    'allow_negative' => env('STOCK_ALLOW_NEGATIVE', false),

];

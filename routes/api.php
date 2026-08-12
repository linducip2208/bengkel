<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApiCustomerController;
use App\Http\Controllers\Api\ApiVehicleController;
use App\Http\Controllers\Api\ApiServiceController;
use App\Http\Controllers\Api\ApiInvoiceController;
use App\Http\Controllers\Api\ApiPaymentController;
use App\Http\Controllers\Api\ApiProductController;
use App\Http\Controllers\Api\ApiPurchaseController;
use App\Http\Controllers\Api\ApiSaleController;
use App\Http\Controllers\Api\ApiSupplierController;
use App\Http\Controllers\Api\ApiIncomeController;
use App\Http\Controllers\Api\ApiExpenseController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiReportController;
use App\Http\Controllers\Api\ApiMasterDataController;

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::get('/dashboard/stats', [ApiDashboardController::class, 'stats'])->name('dashboard.stats');

        Route::get('/master-data', [ApiMasterDataController::class, 'all'])->name('master-data');

        Route::apiResource('customers', ApiCustomerController::class);
        Route::apiResource('vehicles', ApiVehicleController::class);
        Route::apiResource('services', ApiServiceController::class);
        Route::post('/services/{service}/complete', [ApiServiceController::class, 'complete'])->name('services.complete');

        Route::apiResource('invoices', ApiInvoiceController::class);
        Route::post('/invoices/{invoice}/payments', [ApiPaymentController::class, 'store'])->name('invoices.payments.store');
        Route::get('/invoices/{invoice}/pdf', [ApiInvoiceController::class, 'pdf'])->name('invoices.pdf');

        Route::apiResource('products', ApiProductController::class);
        Route::post('/products/{product}/stock-adjust', [ApiProductController::class, 'stockAdjust'])->name('products.stock-adjust');

        Route::apiResource('purchases', ApiPurchaseController::class);
        Route::post('/purchases/{purchase}/receive', [ApiPurchaseController::class, 'markReceived'])->name('purchases.receive');

        Route::apiResource('sales', ApiSaleController::class);
        Route::apiResource('suppliers', ApiSupplierController::class);
        Route::apiResource('incomes', ApiIncomeController::class);
        Route::apiResource('expenses', ApiExpenseController::class);

        Route::get('/reports/service', [ApiReportController::class, 'serviceReport'])->name('reports.service');
        Route::get('/reports/sales', [ApiReportController::class, 'salesReport'])->name('reports.sales');
        Route::get('/reports/stock', [ApiReportController::class, 'stockReport'])->name('reports.stock');
        Route::get('/reports/financial', [ApiReportController::class, 'financialReport'])->name('reports.financial');
    });
});

<?php

use App\Http\Controllers\Api\ApiBookingController;
use App\Http\Controllers\Api\ApiCommissionController;
use App\Http\Controllers\Api\ApiCustomerController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiExpenseController;
use App\Http\Controllers\Api\ApiIncomeController;
use App\Http\Controllers\Api\ApiInvoiceController;
use App\Http\Controllers\Api\ApiJobcardController;
use App\Http\Controllers\Api\ApiMasterDataController;
use App\Http\Controllers\Api\ApiPaymentController;
use App\Http\Controllers\Api\ApiPosController;
use App\Http\Controllers\Api\ApiProductController;
use App\Http\Controllers\Api\ApiPurchaseController;
use App\Http\Controllers\Api\ApiReportController;
use App\Http\Controllers\Api\ApiSaleController;
use App\Http\Controllers\Api\ApiServiceController;
use App\Http\Controllers\Api\ApiSupplierController;
use App\Http\Controllers\Api\ApiVehicleController;
use App\Http\Controllers\Api\ApiWarrantyController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::get('/dashboard/stats', [ApiDashboardController::class, 'stats'])->name('dashboard.stats');

        Route::get('/master-data', [ApiMasterDataController::class, 'all'])->name('master-data');

        Route::apiResource('customers', ApiCustomerController::class);
        Route::apiResource('vehicles', ApiVehicleController::class);
        Route::get('/services', [ApiServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{service}', [ApiServiceController::class, 'show'])->name('services.show');
        Route::post('/services/{service}/complete', [ApiServiceController::class, 'complete'])->name('services.complete')->middleware('role:super_admin|admin|manager|service_advisor');

        // Financial documents: writes are restricted; reads stay open to staff.
        Route::get('/invoices', [ApiInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [ApiInvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices', [ApiInvoiceController::class, 'store'])->name('invoices.store')->middleware('role:super_admin|admin|manager');
        Route::match(['put', 'patch'], '/invoices/{invoice}', [ApiInvoiceController::class, 'update'])->name('invoices.update')->middleware('role:super_admin|admin|manager');
        Route::delete('/invoices/{invoice}', [ApiInvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('role:super_admin|admin');
        Route::post('/invoices/{invoice}/payments', [ApiPaymentController::class, 'store'])->name('invoices.payments.store')->middleware('role:super_admin|admin|manager|kasir');
        Route::get('/invoices/{invoice}/pdf', [ApiInvoiceController::class, 'pdf'])->name('invoices.pdf');

        Route::get('/products', [ApiProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ApiProductController::class, 'show'])->name('products.show');
        Route::post('/products', [ApiProductController::class, 'store'])->name('products.store')->middleware('role:super_admin|admin|inventory');
        Route::match(['put', 'patch'], '/products/{product}', [ApiProductController::class, 'update'])->name('products.update')->middleware('role:super_admin|admin|inventory');
        Route::delete('/products/{product}', [ApiProductController::class, 'destroy'])->name('products.destroy')->middleware('role:super_admin|admin');
        Route::post('/products/{product}/stock-adjust', [ApiProductController::class, 'stockAdjust'])->name('products.stock-adjust')->middleware('role:super_admin|admin|manager|inventory');

        Route::get('/purchases', [ApiPurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/{purchase}', [ApiPurchaseController::class, 'show'])->name('purchases.show');
        Route::post('/purchases', [ApiPurchaseController::class, 'store'])->name('purchases.store')->middleware('role:super_admin|admin|manager');
        Route::match(['put', 'patch'], '/purchases/{purchase}', [ApiPurchaseController::class, 'update'])->name('purchases.update')->middleware('role:super_admin|admin|manager');
        Route::delete('/purchases/{purchase}', [ApiPurchaseController::class, 'destroy'])->name('purchases.destroy')->middleware('role:super_admin|admin');
        Route::post('/purchases/{purchase}/receive', [ApiPurchaseController::class, 'markReceived'])->name('purchases.receive')->middleware('role:super_admin|admin|manager|inventory');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [ApiPurchaseController::class, 'receivePurchaseOrder'])->name('purchase-orders.receive');
        Route::post('/purchase-orders/{purchaseOrder}/{action}', [ApiPurchaseController::class, 'transitionPurchaseOrder'])
            ->whereIn('action', ['submit', 'approve', 'close'])->name('purchase-orders.transition');

        Route::apiResource('sales', ApiSaleController::class);
        Route::apiResource('suppliers', ApiSupplierController::class);
        Route::apiResource('incomes', ApiIncomeController::class);
        Route::apiResource('expenses', ApiExpenseController::class);

        Route::apiResource('jobcards', ApiJobcardController::class);
        Route::apiResource('bookings', ApiBookingController::class);
        Route::apiResource('warranty-claims', ApiWarrantyController::class);
        Route::get('/commissions', [ApiCommissionController::class, 'index'])->name('commissions.index');
        Route::post('/commissions/{serviceTechnician}/mark-paid', [ApiCommissionController::class, 'markPaid'])->name('commissions.mark-paid');
        Route::post('/pos/open', [ApiPosController::class, 'openSession'])->name('pos.open');
        Route::post('/pos/close', [ApiPosController::class, 'closeSession'])->name('pos.close');
        Route::post('/pos/checkout', [ApiPosController::class, 'checkout'])->name('pos.checkout');

        Route::get('/reports/service', [ApiReportController::class, 'serviceReport'])->name('reports.service');
        Route::get('/reports/sales', [ApiReportController::class, 'salesReport'])->name('reports.sales');
        Route::get('/reports/stock', [ApiReportController::class, 'stockReport'])->name('reports.stock');
        Route::get('/reports/financial', [ApiReportController::class, 'financialReport'])->name('reports.financial');
    });
});

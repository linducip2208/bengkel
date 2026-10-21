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
use App\Http\Controllers\Api\ApiWorkshopController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:10,1');
    Route::get('/health', [ApiWorkshopController::class, 'health'])->name('health');

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

        // Sales — write restricted by role.
        Route::get('/sales', [ApiSaleController::class, 'index'])->name('sales.index')->middleware('role:super_admin|admin|manager|kasir|inventory');
        Route::get('/sales/{sale}', [ApiSaleController::class, 'show'])->name('sales.show')->middleware('role:super_admin|admin|manager|kasir|inventory');
        Route::post('/sales', [ApiSaleController::class, 'store'])->name('sales.store')->middleware('role:super_admin|admin|manager|kasir');
        Route::match(['put', 'patch'], '/sales/{sale}', [ApiSaleController::class, 'update'])->name('sales.update')->middleware('role:super_admin|admin|manager|kasir');
        Route::delete('/sales/{sale}', [ApiSaleController::class, 'destroy'])->name('sales.destroy')->middleware('role:super_admin|admin');

        // Suppliers
        Route::get('/suppliers', [ApiSupplierController::class, 'index'])->name('suppliers.index')->middleware('role:super_admin|admin|manager|inventory');
        Route::get('/suppliers/{supplier}', [ApiSupplierController::class, 'show'])->name('suppliers.show')->middleware('role:super_admin|admin|manager|inventory');
        Route::post('/suppliers', [ApiSupplierController::class, 'store'])->name('suppliers.store')->middleware('role:super_admin|admin|inventory');
        Route::match(['put', 'patch'], '/suppliers/{supplier}', [ApiSupplierController::class, 'update'])->name('suppliers.update')->middleware('role:super_admin|admin|inventory');
        Route::delete('/suppliers/{supplier}', [ApiSupplierController::class, 'destroy'])->name('suppliers.destroy')->middleware('role:super_admin|admin');

        // Income — financial
        Route::get('/incomes', [ApiIncomeController::class, 'index'])->name('incomes.index')->middleware('role:super_admin|admin|manager');
        Route::get('/incomes/{income}', [ApiIncomeController::class, 'show'])->name('incomes.show')->middleware('role:super_admin|admin|manager');
        Route::post('/incomes', [ApiIncomeController::class, 'store'])->name('incomes.store')->middleware('role:super_admin|admin|manager');
        Route::match(['put', 'patch'], '/incomes/{income}', [ApiIncomeController::class, 'update'])->name('incomes.update')->middleware('role:super_admin|admin|manager');
        Route::delete('/incomes/{income}', [ApiIncomeController::class, 'destroy'])->name('incomes.destroy')->middleware('role:super_admin|admin');

        // Expense — financial
        Route::get('/expenses', [ApiExpenseController::class, 'index'])->name('expenses.index')->middleware('role:super_admin|admin|manager');
        Route::get('/expenses/{expense}', [ApiExpenseController::class, 'show'])->name('expenses.show')->middleware('role:super_admin|admin|manager');
        Route::post('/expenses', [ApiExpenseController::class, 'store'])->name('expenses.store')->middleware('role:super_admin|admin|manager');
        Route::match(['put', 'patch'], '/expenses/{expense}', [ApiExpenseController::class, 'update'])->name('expenses.update')->middleware('role:super_admin|admin|manager');
        Route::delete('/expenses/{expense}', [ApiExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('role:super_admin|admin');

        // Jobcards / bookings / warranty-claims — reads stay open to staff,
        // but writes are role-gated like every other financial/operational doc.
        Route::get('/jobcards', [ApiJobcardController::class, 'index'])->name('jobcards.index');
        Route::get('/jobcards/{service}', [ApiJobcardController::class, 'show'])->name('jobcards.show');
        Route::post('/jobcards', [ApiJobcardController::class, 'store'])->name('jobcards.store')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::match(['put', 'patch'], '/jobcards/{service}', [ApiJobcardController::class, 'update'])->name('jobcards.update')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::post('/jobcards/{service}/complete', [ApiJobcardController::class, 'complete'])->name('jobcards.complete')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::delete('/jobcards/{service}', [ApiJobcardController::class, 'destroy'])->name('jobcards.destroy')->middleware('role:super_admin|admin');

        Route::get('/bookings', [ApiBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [ApiBookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings', [ApiBookingController::class, 'store'])->name('bookings.store')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::match(['put', 'patch'], '/bookings/{booking}', [ApiBookingController::class, 'update'])->name('bookings.update')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::post('/bookings/{booking}/convert', [ApiBookingController::class, 'convert'])->name('bookings.convert')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::delete('/bookings/{booking}', [ApiBookingController::class, 'destroy'])->name('bookings.destroy')->middleware('role:super_admin|admin');

        Route::get('/warranty-claims', [ApiWarrantyController::class, 'index'])->name('warranty-claims.index');
        Route::get('/warranty-claims/{warrantyClaim}', [ApiWarrantyController::class, 'show'])->name('warranty-claims.show');
        Route::post('/warranty-claims', [ApiWarrantyController::class, 'store'])->name('warranty-claims.store')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::match(['put', 'patch'], '/warranty-claims/{warrantyClaim}', [ApiWarrantyController::class, 'update'])->name('warranty-claims.update')->middleware('role:super_admin|admin|manager');
        Route::delete('/warranty-claims/{warrantyClaim}', [ApiWarrantyController::class, 'destroy'])->name('warranty-claims.destroy')->middleware('role:super_admin|admin');
        Route::get('/commissions', [ApiCommissionController::class, 'index'])->name('commissions.index')->middleware('role:super_admin|admin|manager|kasir');
        Route::post('/commissions/{serviceTechnician}/mark-paid', [ApiCommissionController::class, 'markPaid'])->name('commissions.mark-paid')->middleware('role:super_admin|admin|manager');
        Route::post('/pos/open', [ApiPosController::class, 'openSession'])->name('pos.open')->middleware('role:super_admin|admin|manager|kasir');
        Route::post('/pos/close', [ApiPosController::class, 'closeSession'])->name('pos.close')->middleware('role:super_admin|admin|manager|kasir');
        Route::post('/pos/checkout', [ApiPosController::class, 'checkout'])->name('pos.checkout')->middleware('role:super_admin|admin|manager|kasir');

        Route::get('/reports/service', [ApiReportController::class, 'serviceReport'])->name('reports.service');
        Route::get('/reports/sales', [ApiReportController::class, 'salesReport'])->name('reports.sales');
        Route::get('/reports/stock', [ApiReportController::class, 'stockReport'])->name('reports.stock');
        Route::get('/reports/financial', [ApiReportController::class, 'financialReport'])->name('reports.financial');

        // Workshop workflow (Flutter-ready, branch-scoped via HasBranchScope).
        Route::get('/estimates', [ApiWorkshopController::class, 'estimates'])->name('estimates.index');
        Route::get('/estimates/{estimate}', [ApiWorkshopController::class, 'showEstimate'])->name('estimates.show');
        Route::post('/estimates/{estimate}/approve', [ApiWorkshopController::class, 'approveEstimate'])->name('estimates.approve')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::post('/estimates/{estimate}/reject', [ApiWorkshopController::class, 'rejectEstimate'])->name('estimates.reject')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::post('/estimates/{estimate}/decide', [ApiWorkshopController::class, 'decideEstimateGroups'])->name('estimates.decide')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::post('/estimates/{estimate}/convert', [ApiWorkshopController::class, 'convertEstimate'])->name('estimates.convert')->middleware('role:super_admin|admin|manager');

        Route::get('/work-packages', [ApiWorkshopController::class, 'workPackages'])->name('work-packages.index');
        Route::get('/work-packages/{workPackage}', [ApiWorkshopController::class, 'showWorkPackage'])->name('work-packages.show');
        Route::post('/work-packages/{workPackage}/qc', [ApiWorkshopController::class, 'submitQc'])->name('work-packages.qc')->middleware('role:super_admin|admin|manager|service_advisor');

        Route::get('/work-tasks', [ApiWorkshopController::class, 'workTasks'])->name('work-tasks.index');
        Route::get('/work-tasks/{workTask}', [ApiWorkshopController::class, 'showWorkTask'])->name('work-tasks.show');
        Route::post('/work-tasks/{task}/{action}', [ApiWorkshopController::class, 'transitionTask'])->whereIn('action', ['start', 'pause', 'resume', 'finish'])->name('work-tasks.transition')->middleware('role:super_admin|admin|manager|service_advisor|mekanik');

        Route::get('/findings', [ApiWorkshopController::class, 'findings'])->name('findings.index');
        Route::get('/findings/{finding}', [ApiWorkshopController::class, 'showFinding'])->name('findings.show');
        Route::get('/services/{service}/inspections', [ApiWorkshopController::class, 'inspections'])->name('services.inspections');

        Route::get('/technicians', [ApiWorkshopController::class, 'technicians'])->name('technicians.index');
        Route::get('/branches', [ApiWorkshopController::class, 'branches'])->name('branches.index');
        Route::get('/notifications', [ApiWorkshopController::class, 'notifications'])->name('notifications.index');
        Route::post('/device-tokens', [ApiWorkshopController::class, 'registerDeviceToken'])->name('device-tokens.store');
        Route::delete('/device-tokens', [ApiWorkshopController::class, 'unregisterDeviceToken'])->name('device-tokens.destroy');

        // Vehicle & service photos (multipart). Scoped through parent (branch-safe 404).
        Route::post('/vehicles/{vehicle}/images', [ApiVehicleController::class, 'uploadImage'])->name('vehicles.images.store')->middleware('role:super_admin|admin|manager|service_advisor|mekanik');
        Route::delete('/vehicles/{vehicle}/images/{image}', [ApiVehicleController::class, 'deleteImage'])->name('vehicles.images.destroy')->middleware('role:super_admin|admin|manager|service_advisor');
        Route::post('/services/{service}/images', [ApiServiceController::class, 'uploadImage'])->name('services.images.store')->middleware('role:super_admin|admin|manager|service_advisor|mekanik');
        Route::delete('/services/{service}/images/{image}', [ApiServiceController::class, 'deleteImage'])->name('services.images.destroy')->middleware('role:super_admin|admin|manager|service_advisor');
    });
});

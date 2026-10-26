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

        Route::get('/dashboard/stats', [ApiDashboardController::class, 'stats'])->name('dashboard.stats')->middleware('can.perm:income.view,service.view,report.view');

        Route::get('/master-data', [ApiMasterDataController::class, 'all'])->name('master-data');

        Route::apiResource('customers', ApiCustomerController::class)->middleware('can.module:customer');
        Route::apiResource('vehicles', ApiVehicleController::class)->middleware('can.module:vehicle');
        Route::get('/services', [ApiServiceController::class, 'index'])->name('services.index')->middleware('can.perm:service.view');
        Route::get('/services/{service}', [ApiServiceController::class, 'show'])->name('services.show')->middleware('can.perm:service.view');
        Route::post('/services/{service}/complete', [ApiServiceController::class, 'complete'])->name('services.complete')->middleware('can.perm:service.complete');

        // Financial documents: reads need view, writes are restricted.
        Route::get('/invoices', [ApiInvoiceController::class, 'index'])->name('invoices.index')->middleware('can.perm:invoice.view');
        Route::get('/invoices/{invoice}', [ApiInvoiceController::class, 'show'])->name('invoices.show')->middleware('can.perm:invoice.view');
        Route::post('/invoices', [ApiInvoiceController::class, 'store'])->name('invoices.store')->middleware('can.perm:invoice.create');
        Route::match(['put', 'patch'], '/invoices/{invoice}', [ApiInvoiceController::class, 'update'])->name('invoices.update')->middleware('can.perm:invoice.edit');
        Route::delete('/invoices/{invoice}', [ApiInvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('can.perm:invoice.delete');
        Route::post('/invoices/{invoice}/payments', [ApiPaymentController::class, 'store'])->name('invoices.payments.store')->middleware('can.perm:payment.process');
        Route::get('/invoices/{invoice}/pdf', [ApiInvoiceController::class, 'pdf'])->name('invoices.pdf')->middleware('can.perm:invoice.pdf');

        Route::get('/products', [ApiProductController::class, 'index'])->name('products.index')->middleware('can.perm:product.view');
        Route::get('/products/{product}', [ApiProductController::class, 'show'])->name('products.show')->middleware('can.perm:product.view');
        Route::post('/products', [ApiProductController::class, 'store'])->name('products.store')->middleware('can.perm:product.create');
        Route::match(['put', 'patch'], '/products/{product}', [ApiProductController::class, 'update'])->name('products.update')->middleware('can.perm:product.edit');
        Route::delete('/products/{product}', [ApiProductController::class, 'destroy'])->name('products.destroy')->middleware('can.perm:product.delete');
        Route::post('/products/{product}/stock-adjust', [ApiProductController::class, 'stockAdjust'])->name('products.stock-adjust')->middleware('can.perm:product.stock-adjust');

        Route::get('/purchases', [ApiPurchaseController::class, 'index'])->name('purchases.index')->middleware('can.perm:purchase.view');
        Route::get('/purchases/{purchase}', [ApiPurchaseController::class, 'show'])->name('purchases.show')->middleware('can.perm:purchase.view');
        Route::post('/purchases', [ApiPurchaseController::class, 'store'])->name('purchases.store')->middleware('can.perm:purchase.create');
        Route::match(['put', 'patch'], '/purchases/{purchase}', [ApiPurchaseController::class, 'update'])->name('purchases.update')->middleware('can.perm:purchase.edit');
        Route::delete('/purchases/{purchase}', [ApiPurchaseController::class, 'destroy'])->name('purchases.destroy')->middleware('can.perm:purchase.delete');
        Route::post('/purchases/{purchase}/receive', [ApiPurchaseController::class, 'markReceived'])->name('purchases.receive')->middleware('can.perm:purchase.receive');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [ApiPurchaseController::class, 'receivePurchaseOrder'])->name('purchase-orders.receive')->middleware('can.perm:purchase-order.receive');
        Route::post('/purchase-orders/{purchaseOrder}/{action}', [ApiPurchaseController::class, 'transitionPurchaseOrder'])
            ->whereIn('action', ['submit', 'approve', 'close'])->name('purchase-orders.transition')->middleware('can.perm:purchase-order.receive,purchase-order.approve');

        // Sales — write restricted by role.
        Route::get('/sales', [ApiSaleController::class, 'index'])->name('sales.index')->middleware('can.perm:sale.view');
        Route::get('/sales/{sale}', [ApiSaleController::class, 'show'])->name('sales.show')->middleware('can.perm:sale.view');
        Route::post('/sales', [ApiSaleController::class, 'store'])->name('sales.store')->middleware('can.perm:sale.create');
        Route::match(['put', 'patch'], '/sales/{sale}', [ApiSaleController::class, 'update'])->name('sales.update')->middleware('can.perm:sale.edit');
        Route::delete('/sales/{sale}', [ApiSaleController::class, 'destroy'])->name('sales.destroy')->middleware('can.perm:sale.delete');

        // Suppliers
        Route::get('/suppliers', [ApiSupplierController::class, 'index'])->name('suppliers.index')->middleware('can.perm:supplier.view');
        Route::get('/suppliers/{supplier}', [ApiSupplierController::class, 'show'])->name('suppliers.show')->middleware('can.perm:supplier.view');
        Route::post('/suppliers', [ApiSupplierController::class, 'store'])->name('suppliers.store')->middleware('can.perm:supplier.create');
        Route::match(['put', 'patch'], '/suppliers/{supplier}', [ApiSupplierController::class, 'update'])->name('suppliers.update')->middleware('can.perm:supplier.edit');
        Route::delete('/suppliers/{supplier}', [ApiSupplierController::class, 'destroy'])->name('suppliers.destroy')->middleware('can.perm:supplier.delete');

        // Income — financial
        Route::get('/incomes', [ApiIncomeController::class, 'index'])->name('incomes.index')->middleware('can.perm:income.view');
        Route::get('/incomes/{income}', [ApiIncomeController::class, 'show'])->name('incomes.show')->middleware('can.perm:income.view');
        Route::post('/incomes', [ApiIncomeController::class, 'store'])->name('incomes.store')->middleware('can.perm:income.create');
        Route::match(['put', 'patch'], '/incomes/{income}', [ApiIncomeController::class, 'update'])->name('incomes.update')->middleware('can.perm:income.edit');
        Route::delete('/incomes/{income}', [ApiIncomeController::class, 'destroy'])->name('incomes.destroy')->middleware('can.perm:income.delete');

        // Expense — financial
        Route::get('/expenses', [ApiExpenseController::class, 'index'])->name('expenses.index')->middleware('can.perm:expense.view');
        Route::get('/expenses/{expense}', [ApiExpenseController::class, 'show'])->name('expenses.show')->middleware('can.perm:expense.view');
        Route::post('/expenses', [ApiExpenseController::class, 'store'])->name('expenses.store')->middleware('can.perm:expense.create');
        Route::match(['put', 'patch'], '/expenses/{expense}', [ApiExpenseController::class, 'update'])->name('expenses.update')->middleware('can.perm:expense.edit');
        Route::delete('/expenses/{expense}', [ApiExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('can.perm:expense.delete');

        // Jobcards / bookings / warranty-claims reads need view, writes are gated.
        Route::get('/jobcards', [ApiJobcardController::class, 'index'])->name('jobcards.index')->middleware('can.perm:jobcard.view');
        Route::get('/jobcards/{service}', [ApiJobcardController::class, 'show'])->name('jobcards.show')->middleware('can.perm:jobcard.view');
        Route::post('/jobcards', [ApiJobcardController::class, 'store'])->name('jobcards.store')->middleware('can.perm:service.edit');
        Route::match(['put', 'patch'], '/jobcards/{service}', [ApiJobcardController::class, 'update'])->name('jobcards.update')->middleware('can.perm:service.edit');
        Route::post('/jobcards/{service}/complete', [ApiJobcardController::class, 'complete'])->name('jobcards.complete')->middleware('can.perm:service.complete');
        Route::delete('/jobcards/{service}', [ApiJobcardController::class, 'destroy'])->name('jobcards.destroy')->middleware('can.perm:jobcard.delete');

        Route::get('/bookings', [ApiBookingController::class, 'index'])->name('bookings.index')->middleware('can.perm:booking.view');
        Route::get('/bookings/{booking}', [ApiBookingController::class, 'show'])->name('bookings.show')->middleware('can.perm:booking.view');
        Route::post('/bookings', [ApiBookingController::class, 'store'])->name('bookings.store')->middleware('can.perm:booking.create');
        Route::match(['put', 'patch'], '/bookings/{booking}', [ApiBookingController::class, 'update'])->name('bookings.update')->middleware('can.perm:booking.edit');
        Route::post('/bookings/{booking}/convert', [ApiBookingController::class, 'convert'])->name('bookings.convert')->middleware('can.perm:booking.edit');
        Route::delete('/bookings/{booking}', [ApiBookingController::class, 'destroy'])->name('bookings.destroy')->middleware('can.perm:booking.delete');

        Route::get('/warranty-claims', [ApiWarrantyController::class, 'index'])->name('warranty-claims.index')->middleware('can.perm:warranty.view');
        Route::get('/warranty-claims/{warrantyClaim}', [ApiWarrantyController::class, 'show'])->name('warranty-claims.show')->middleware('can.perm:warranty.view');
        Route::post('/warranty-claims', [ApiWarrantyController::class, 'store'])->name('warranty-claims.store')->middleware('can.perm:warranty.create');
        Route::match(['put', 'patch'], '/warranty-claims/{warrantyClaim}', [ApiWarrantyController::class, 'update'])->name('warranty-claims.update')->middleware('can.perm:warranty.edit');
        Route::delete('/warranty-claims/{warrantyClaim}', [ApiWarrantyController::class, 'destroy'])->name('warranty-claims.destroy')->middleware('can.perm:warranty.delete');
        Route::get('/commissions', [ApiCommissionController::class, 'index'])->name('commissions.index')->middleware('can.perm:commission.view');
        Route::post('/commissions/{serviceTechnician}/mark-paid', [ApiCommissionController::class, 'markPaid'])->name('commissions.mark-paid')->middleware('can.perm:commission.mark_paid');
        Route::post('/pos/open', [ApiPosController::class, 'openSession'])->name('pos.open')->middleware('can.perm:pos.open');
        Route::post('/pos/close', [ApiPosController::class, 'closeSession'])->name('pos.close')->middleware('can.perm:pos.close');
        Route::post('/pos/checkout', [ApiPosController::class, 'checkout'])->name('pos.checkout')->middleware('can.perm:pos.checkout');

        Route::get('/reports/service', [ApiReportController::class, 'serviceReport'])->name('reports.service')->middleware('can.perm:report.view');
        Route::get('/reports/sales', [ApiReportController::class, 'salesReport'])->name('reports.sales')->middleware('can.perm:report.view');
        Route::get('/reports/stock', [ApiReportController::class, 'stockReport'])->name('reports.stock')->middleware('can.perm:report.view');
        Route::get('/reports/financial', [ApiReportController::class, 'financialReport'])->name('reports.financial')->middleware('can.perm:report.view');

        // Workshop workflow (Flutter-ready, branch-scoped via HasBranchScope).
        Route::get('/estimates', [ApiWorkshopController::class, 'estimates'])->name('estimates.index')->middleware('can.perm:estimates.view');
        Route::get('/estimates/{estimate}', [ApiWorkshopController::class, 'showEstimate'])->name('estimates.show')->middleware('can.perm:estimates.view');
        Route::post('/estimates/{estimate}/approve', [ApiWorkshopController::class, 'approveEstimate'])->name('estimates.approve')->middleware('can.perm:estimates.update');
        Route::post('/estimates/{estimate}/reject', [ApiWorkshopController::class, 'rejectEstimate'])->name('estimates.reject')->middleware('can.perm:estimates.update');
        Route::post('/estimates/{estimate}/decide', [ApiWorkshopController::class, 'decideEstimateGroups'])->name('estimates.decide')->middleware('can.perm:estimates.update');
        Route::post('/estimates/{estimate}/convert', [ApiWorkshopController::class, 'convertEstimate'])->name('estimates.convert')->middleware('can.perm:estimates.convert_invoice');

        Route::get('/work-packages', [ApiWorkshopController::class, 'workPackages'])->name('work-packages.index')->middleware('can.perm:work-packages.view');
        Route::get('/work-packages/{workPackage}', [ApiWorkshopController::class, 'showWorkPackage'])->name('work-packages.show')->middleware('can.perm:work-packages.view');
        Route::post('/work-packages/{workPackage}/qc', [ApiWorkshopController::class, 'submitQc'])->name('work-packages.qc')->middleware('can.perm:qc.perform');

        Route::get('/work-tasks', [ApiWorkshopController::class, 'workTasks'])->name('work-tasks.index')->middleware('can.perm:work-tasks.view');
        Route::get('/work-tasks/{workTask}', [ApiWorkshopController::class, 'showWorkTask'])->name('work-tasks.show')->middleware('can.perm:work-tasks.view');
        Route::post('/work-tasks/{task}/{action}', [ApiWorkshopController::class, 'transitionTask'])->whereIn('action', ['start', 'pause', 'resume', 'finish'])->name('work-tasks.transition')->middleware('can.perm:work-tasks.start,work-tasks.finish');

        Route::get('/findings', [ApiWorkshopController::class, 'findings'])->name('findings.index')->middleware('can.perm:findings.view');
        Route::get('/findings/{finding}', [ApiWorkshopController::class, 'showFinding'])->name('findings.show')->middleware('can.perm:findings.view');
        Route::get('/services/{service}/inspections', [ApiWorkshopController::class, 'inspections'])->name('services.inspections')->middleware('can.perm:findings.view');

        Route::get('/technicians', [ApiWorkshopController::class, 'technicians'])->name('technicians.index')->middleware('can.perm:service.edit,work-tasks.view');
        Route::get('/branches', [ApiWorkshopController::class, 'branches'])->name('branches.index');
        Route::get('/notifications', [ApiWorkshopController::class, 'notifications'])->name('notifications.index');
        Route::post('/device-tokens', [ApiWorkshopController::class, 'registerDeviceToken'])->name('device-tokens.store');
        Route::delete('/device-tokens', [ApiWorkshopController::class, 'unregisterDeviceToken'])->name('device-tokens.destroy');

        // Vehicle & service photos (multipart). Scoped through parent (branch-safe 404).
        Route::post('/vehicles/{vehicle}/images', [ApiVehicleController::class, 'uploadImage'])->name('vehicles.images.store')->middleware('can.perm:media.upload');
        Route::delete('/vehicles/{vehicle}/images/{image}', [ApiVehicleController::class, 'deleteImage'])->name('vehicles.images.destroy')->middleware('can.perm:media.delete');
        Route::post('/services/{service}/images', [ApiServiceController::class, 'uploadImage'])->name('services.images.store')->middleware('can.perm:media.upload');
        Route::delete('/services/{service}/images/{image}', [ApiServiceController::class, 'deleteImage'])->name('services.images.destroy')->middleware('can.perm:media.delete');
    });
});

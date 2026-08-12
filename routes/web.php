<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\ProgrammaticSeoController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\BusinessHourController;
use App\Http\Controllers\Tenant\CheckoutCategoryController;
use App\Http\Controllers\Tenant\CityController;
use App\Http\Controllers\Tenant\ColorController;
use App\Http\Controllers\Tenant\CommissionController;
use App\Http\Controllers\Tenant\CountryController;
use App\Http\Controllers\Tenant\CurrencyController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomFieldController;
use App\Http\Controllers\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\EmailLogController;
use App\Http\Controllers\Tenant\EquipmentController;
use App\Http\Controllers\Tenant\ExpenseController;
use App\Http\Controllers\Tenant\FuelTypeController;
use App\Http\Controllers\Tenant\GatePassController;
use App\Http\Controllers\Tenant\HolidayController;
use App\Http\Controllers\Tenant\IncomeController;
use App\Http\Controllers\Tenant\InspectionPointController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\JobcardController;
use App\Http\Controllers\Tenant\NoteController;
use App\Http\Controllers\Tenant\NotificationTemplateController;
use App\Http\Controllers\Tenant\ObservationPointController;
use App\Http\Controllers\Tenant\ObservationTypeController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\PaymentMethodController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\LoyaltyController;
use App\Http\Controllers\Tenant\VoucherController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ProductTypeController;
use App\Http\Controllers\Tenant\ProductUnitController;
use App\Http\Controllers\Tenant\PurchaseController;
use App\Http\Controllers\Tenant\PurchaseReturnController;
use App\Http\Controllers\Tenant\ReminderController;
use App\Http\Controllers\Tenant\RepairCategoryController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\SaleController;
use App\Http\Controllers\Tenant\ServiceController;
use App\Http\Controllers\Tenant\ServicePackageController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\StateController;
use App\Http\Controllers\Tenant\StockAdjustmentController;
use App\Http\Controllers\Tenant\StockHistoryController;
use App\Http\Controllers\Tenant\SubcontractorController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\TaxRateController;
use App\Http\Controllers\Tenant\VehicleBrandController;
use App\Http\Controllers\Tenant\VehicleController;
use App\Http\Controllers\Tenant\VehicleTypeController;
use App\Http\Controllers\Tenant\WashbayController;

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public tracking service (token-based, no auth)
Route::get('/track/{token}', [\App\Http\Controllers\TrackingController::class, 'show'])->name('public.tracking');
Route::post('/track/{token}/review', [\App\Http\Controllers\TrackingController::class, 'review'])->name('public.tracking.review');

// Payment Gateway webhook callback (PUBLIC — gateway POST tanpa session)
Route::any('/payment/callback/{token}', [\App\Http\Controllers\Tenant\PaymentGatewayController::class, 'callback'])->name('payment.callback');

// 2FA challenge (post-login)
Route::get('/2fa/challenge', [\App\Http\Controllers\Auth\TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [\App\Http\Controllers\Auth\TwoFactorController::class, 'verify'])->name('2fa.verify');

// Public Booking Online (no auth)
Route::get('/booking', [\App\Http\Controllers\BookingController::class, 'publicForm'])->name('public.booking');
Route::post('/booking', [\App\Http\Controllers\BookingController::class, 'publicStore'])->name('public.booking.store');

// Customer Portal (separate auth via session)
    Route::get('/customer/login', [\App\Http\Controllers\CustomerPortalController::class, 'loginForm'])->name('customer.login');
    Route::post('/customer/login', [\App\Http\Controllers\CustomerPortalController::class, 'login']);
    Route::get('/customer/dashboard', [\App\Http\Controllers\CustomerPortalController::class, 'dashboard'])->name('customer.dashboard');
    Route::get('/customer/invoice/{id}', [\App\Http\Controllers\CustomerPortalController::class, 'invoiceDetail'])->name('customer.invoice');
    Route::get('/customer/service/{id}', [\App\Http\Controllers\CustomerPortalController::class, 'serviceDetail'])->name('customer.service');
    Route::post('/customer/invoice/{id}/upload-payment', [\App\Http\Controllers\CustomerPortalController::class, 'uploadPayment'])->name('customer.upload-payment');
    Route::post('/customer/change-password', [\App\Http\Controllers\CustomerPortalController::class, 'changePassword'])->name('customer.change-password');
    Route::post('/customer/logout', [\App\Http\Controllers\CustomerPortalController::class, 'logout'])->name('customer.logout');

// Public SEO pages
Route::get('/best/{category}', [ProgrammaticSeoController::class, 'bestService'])->name('seo.best');
Route::get('/best/{category}/{year}', [ProgrammaticSeoController::class, 'bestService'])->name('seo.best.year');
Route::get('/alternatives-to/{slug}', [ProgrammaticSeoController::class, 'serviceAlternatives'])->name('seo.alternatives');
Route::get('/compare/{a}-vs-{b}', [ProgrammaticSeoController::class, 'compareServices'])->name('seo.compare');

// Multilingual PSEO routes (ID / EN / DE)
foreach (['id', 'en', 'de'] as $lang) {
    Route::prefix($lang)->group(function () use ($lang) {
        Route::get('/bengkel-{city}', [\App\Http\Controllers\ProgrammaticSeoController::class, 'cityLanding'])->name("seo.{$lang}.city");
        Route::get('/bengkel-{city}/{kelurahan}', [\App\Http\Controllers\ProgrammaticSeoController::class, 'kelurahanLanding'])->name("seo.{$lang}.kelurahan");
        Route::get('/bengkel-{brand}-{city}', [\App\Http\Controllers\ProgrammaticSeoController::class, 'brandCityLanding'])->name("seo.{$lang}.brand-city");
        Route::get('/service-{service}-{city}', [\App\Http\Controllers\ProgrammaticSeoController::class, 'serviceCityLanding'])->name("seo.{$lang}.service-city");
        Route::get('/bengkel-terbaik-{city}', [\App\Http\Controllers\ProgrammaticSeoController::class, 'bestCityLanding'])->name("seo.{$lang}.best-city");
    });
}

// Blog public
Route::get('/blog', function () {
    $articles = [];
    if (class_exists(\App\Models\BlogPost::class)) {
        $articles = \App\Models\BlogPost::published()->orderBy('published_at', 'desc')->limit(12)->get();
    }
    return view('seo.blog-list', [
        'metaTitle' => 'Blog Aplikasi Bengkel Terbaik — Tips & Berita Otomotif',
        'metaDescription' => 'Baca tips perawatan mobil, berita otomotif, dan panduan service dari Aplikasi Bengkel Terbaik.',
        'jsonLd' => ['@context'=>'https://schema.org','@type'=>'Blog','name'=>'Blog Aplikasi Bengkel Terbaik'],
        'articles' => $articles,
    ]);
})->name('blog.index');
Route::get('/blog/{slug}', [ProgrammaticSeoController::class, 'blogArticle'])->name('seo.blog');
Route::get('/blog/feed.xml', [\App\Http\Controllers\Tenant\BlogController::class, 'rss'])->name('blog.rss');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{group}.xml', [SitemapController::class, 'show'])->name('sitemap.group');
Route::get('/sitemap-stats', [SitemapController::class, 'stats'])->name('sitemap.stats');

// Public Docs / Tutorial
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/{slug}', [DocsController::class, 'show'])->name('docs.show');

// Redirect /admin ke dashboard
Route::redirect('/admin', '/')->name('admin');

// Landing page (public) — guest lihat welcome, user login langsung ke dashboard
Route::get('/', function (\App\Services\ReportService $reportService) {
    if (auth()->check()) {
        return app(\App\Http\Controllers\DashboardController::class)->index($reportService);
    }
    return view('welcome');
})->name('dashboard');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // --- Reports ---
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/service', [ReportController::class, 'serviceReport'])->name('reports.service');
    Route::get('/reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
    Route::get('/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');
    Route::get('/reports/financial', [ReportController::class, 'financialReport'])->name('reports.financial');
    Route::get('/reports/technician', [ReportController::class, 'technicianPerformance'])->name('reports.technician');
    Route::get('/reports/customer-lifetime', [ReportController::class, 'customerLifetime'])->name('reports.customer-lifetime');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
    Route::get('/reports/service/{service}/pdf', [ReportController::class, 'serviceReportPdf'])->name('reports.service-pdf');
    Route::get('/reports/ar-aging', [ReportController::class, 'arAging'])->name('reports.ar-aging');
    Route::get('/reports/parts-usage', [ReportController::class, 'partsUsage'])->name('reports.parts-usage');
    Route::get('/reports/branch-comparison', [ReportController::class, 'branchComparison'])->name('reports.branch-comparison');
    Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');
    Route::get('/reports/general-ledger', [ReportController::class, 'generalLedger'])->name('reports.general-ledger');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');

    // --- Settings ---
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings', [SettingsController::class, 'update']);
    Route::get('/settings/backup', [SettingsController::class, 'backupPage'])->name('settings.backup-page');
    Route::post('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup');
    Route::get('/settings/backup/download', [SettingsController::class, 'backupDownload'])->name('settings.backup-download');
    Route::post('/settings/cache-clear', [SettingsController::class, 'cacheClear'])->name('settings.cache-clear');
    Route::post('/settings/optimize', [SettingsController::class, 'optimize'])->name('settings.optimize');

    // --- Jobcards (non-resource) ---
    Route::get('/jobcards', [JobcardController::class, 'index'])->name('jobcards.index');
    Route::get('/jobcards/{service}', [JobcardController::class, 'show'])->name('jobcards.show');
    Route::post('/jobcards/{service}', [JobcardController::class, 'store'])->name('jobcards.store');
    Route::put('/jobcards/{service}', [JobcardController::class, 'update'])->name('jobcards.update');
    Route::get('/jobcards/{service}/print', [JobcardController::class, 'print'])->name('jobcards.print');
    Route::get('/jobcards/{service}/gate-pass', [JobcardController::class, 'gatePass'])->name('jobcards.gate-pass');

    // --- Payments (nested under invoices) ---
    Route::get('/invoices/{invoice}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/invoices/{invoice}/payments/history', [PaymentController::class, 'history'])->name('payments.history');

    // --- Observations ---
    Route::get('/observations/{service}/checklist', [ObservationController::class, 'checklist'])->name('observations.checklist');
    Route::post('/observations/{service}/save-checklist', [ObservationController::class, 'saveChecklist'])->name('observations.save-checklist');
    Route::get('/observations-by-type/{type}', [ObservationController::class, 'getByType'])->name('observations.by-type');

    // --- Checkouts ---
    Route::get('/checkouts/{service}', [CheckoutController::class, 'index'])->name('checkouts.index');
    Route::post('/checkouts/{service}', [CheckoutController::class, 'store'])->name('checkouts.store');

    // --- Services custom routes (before resource to avoid conflicts) ---
    Route::post('/services/{service}/complete', [ServiceController::class, 'complete'])->name('services.complete');
    Route::post('/services/{service}/start', [ServiceController::class, 'start'])->name('services.start');
    Route::post('/services/{service}/advance', [ServiceController::class, 'advance'])->name('services.advance');
    Route::post('/services/{service}/upload-image', [ServiceController::class, 'uploadImage'])->name('services.upload-image');
    Route::get('/services/customers/search', [ServiceController::class, 'searchCustomers'])->name('services.customers.search');
    Route::get('/services/vehicles-by-customer/{customer}', [ServiceController::class, 'vehiclesByCustomer'])->name('services.vehicles-by-customer');
    Route::get('/services/history', [ServiceController::class, 'history'])->name('services.history');

    // --- Vehicles custom routes (before resource) ---
    Route::post('/vehicles/{vehicle}/upload-image', [VehicleController::class, 'uploadImage'])->name('vehicles.upload-image');
    Route::delete('/vehicles/images/{image}', [VehicleController::class, 'deleteImage'])->name('vehicles.delete-image');
    Route::get('/vehicles/{vehicle}/history', [VehicleController::class, 'serviceHistory'])->name('vehicles.history');

    // --- Invoices custom routes (before resource) ---
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/send-wa', [InvoiceController::class, 'sendWA'])->name('invoices.sendWA');
    Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.sendEmail');

    // --- Thermal Print ---
    Route::post('/invoices/{invoice}/print', [\App\Http\Controllers\Tenant\PrintController::class, 'invoice'])->name('print.invoice');
    Route::post('/services/{service}/print-jobcard', [\App\Http\Controllers\Tenant\PrintController::class, 'jobcard'])->name('print.jobcard');
    Route::post('/pos/open-drawer', [\App\Http\Controllers\Tenant\PrintController::class, 'openDrawer'])->name('print.open-drawer');
    Route::get('/print/invoice/{invoice}/raw', [\App\Http\Controllers\Tenant\PrintController::class, 'rawData'])->name('print.raw');

    // --- Products custom routes (before resource) ---
    Route::match(['get', 'post'], '/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::match(['get', 'post'], '/products/stock-opname', [ProductController::class, 'stockOpname'])->name('products.stock-opname');
    Route::get('/products/search-json', [ProductController::class, 'searchJson'])->name('products.search-json');
    Route::match(['get', 'post'], '/products/{product}/stock-adjust', [ProductController::class, 'stockAdjust'])->name('products.stock-adjust');

    // --- Stock Adjustments (approval flow) ---
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    Route::post('/stock-adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve');
    Route::post('/stock-adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('stock-adjustments.reject');

    // --- Purchases custom routes (before resource) ---
    Route::post('/purchases/{purchase}/mark-received', [PurchaseController::class, 'markReceived'])->name('purchases.mark-received');
    Route::get('/purchases/return', [PurchaseReturnController::class, 'index'])->name('purchases.return.index');
    Route::get('/purchases/{purchase}/return', [PurchaseReturnController::class, 'create'])->name('purchases.return.create');
    Route::post('/purchases/{purchase}/return', [PurchaseReturnController::class, 'store'])->name('purchases.return.store');

    // --- Gate Passes custom routes (before resource) ---
    Route::get('/gate-passes/{gate_pass}/print', [GatePassController::class, 'print'])->name('gate-passes.print');
    Route::post('/gate-passes/{gate_pass}/mark-exit', [GatePassController::class, 'markExit'])->name('gate-passes.mark-exit');

    // --- Reminders custom routes (before resource) ---
    Route::post('/reminders/{reminder}/send', [ReminderController::class, 'send'])->name('reminders.send');
    Route::post('/reminders/send-scheduled', [ReminderController::class, 'sendScheduled'])->name('reminders.send-scheduled');

    // --- Notification Templates custom routes (before resource) ---
    Route::get('/notification-templates/{template}/preview', [NotificationTemplateController::class, 'preview'])->name('notification-templates.preview');

    // --- Customers custom routes (before resource) ---
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');

    // --- Resource routes ---
    Route::resource('customers', CustomerController::class);
    Route::resource('vehicles', VehicleController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::resource('products', ProductController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::resource('sales', SaleController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('incomes', IncomeController::class);
    Route::resource('expenses', ExpenseController::class);
    Route::resource('gate-passes', GatePassController::class);
    Route::resource('reminders', ReminderController::class);
    Route::resource('notification-templates', NotificationTemplateController::class);

    // --- Master data resources (no show method) ---
    Route::resource('observation-types', ObservationTypeController::class)->except(['show']);
    Route::resource('repair-categories', RepairCategoryController::class)->except(['show']);
    Route::resource('tax-rates', TaxRateController::class)->except(['show']);
    Route::resource('payment-methods', PaymentMethodController::class)->except(['show']);
    Route::resource('product-units', ProductUnitController::class)->except(['show']);
    Route::resource('product-types', ProductTypeController::class)->except(['show']);
    Route::resource('colors', ColorController::class)->except(['show']);
    Route::resource('fuel-types', FuelTypeController::class)->except(['show']);
    Route::resource('vehicle-brands', VehicleBrandController::class)->except(['show']);
    Route::resource('vehicle-types', VehicleTypeController::class)->except(['show']);

    // --- Cabang / Multi-branch ---
    Route::post('/branches/switch', [BranchController::class, 'switchBranch'])->name('branches.switch');
    Route::resource('branches', BranchController::class);
    Route::resource('business-hours', BusinessHourController::class)->except(['index', 'show']);
    Route::resource('holidays', HolidayController::class)->except(['show']);
    Route::post('/washbays/{washbay}/occupy', [WashbayController::class, 'occupy'])->name('washbays.occupy');
    Route::post('/washbays/{washbay}/release', [WashbayController::class, 'release'])->name('washbays.release');
    Route::resource('washbays', WashbayController::class)->except(['show']);

    // --- Master data lanjutan ---
    Route::resource('observation-points', ObservationPointController::class)->except(['show']);
    Route::resource('inspection-points', InspectionPointController::class)->except(['show']);
    Route::resource('checkout-categories', CheckoutCategoryController::class)->except(['show']);

    // --- Geographic & Currency ---
    Route::resource('currencies', CurrencyController::class)->except(['show']);
    Route::resource('countries', CountryController::class)->except(['show']);
    Route::resource('states', StateController::class)->except(['show']);
    Route::resource('cities', CityController::class)->except(['show']);

    // --- Extensibility ---
    Route::resource('custom-fields', CustomFieldController::class)->except(['show']);

    // --- Notes (polymorphic catatan internal) ---
    Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
    Route::get('/notes/create', [NoteController::class, 'create'])->name('notes.create');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    // --- Booking Customer (admin) ---
    Route::get('/bookings/calendar', [\App\Http\Controllers\BookingController::class, 'calendar'])->name('bookings.calendar');
    Route::get('/bookings/calendar/events', [\App\Http\Controllers\BookingController::class, 'calendarEvents'])->name('bookings.calendar.events');
    Route::get('/bookings', [\App\Http\Controllers\BookingController::class, 'adminIndex'])->name('bookings.index');
    Route::put('/bookings/{booking}', [\App\Http\Controllers\BookingController::class, 'adminUpdate'])->name('bookings.update');
    Route::delete('/bookings/{booking}', [\App\Http\Controllers\BookingController::class, 'adminDestroy'])->name('bookings.destroy');
    Route::post('/bookings/{booking}/convert', [\App\Http\Controllers\BookingController::class, 'convertToService'])->name('bookings.convert');

    // --- Marketing: Voucher & Loyalty ---
    Route::resource('vouchers', VoucherController::class)->except(['show']);
    Route::post('/vouchers-validate', [VoucherController::class, 'validateCode'])->name('vouchers.validate');
    Route::get('/marketing/campaign', [\App\Http\Controllers\Tenant\CampaignController::class, 'index'])->name('marketing.campaign');
    Route::post('/marketing/campaign', [\App\Http\Controllers\Tenant\CampaignController::class, 'send'])->name('marketing.campaign.send');
    Route::get('/marketing/campaign/search', [\App\Http\Controllers\Tenant\CampaignController::class, 'searchCustomers'])->name('marketing.campaign.search');

    Route::get('/loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index');
    Route::get('/loyalty/{customer}', [LoyaltyController::class, 'show'])->name('loyalty.show');
    Route::post('/loyalty/{customer}/adjust', [LoyaltyController::class, 'adjust'])->name('loyalty.adjust');

    // --- HRM: Komisi Teknisi ---
    Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/report', [CommissionController::class, 'report'])->name('commissions.report');
    Route::put('/commissions/{serviceTechnician}/mark-paid', [CommissionController::class, 'markPaid'])->name('commissions.markPaid');
    Route::post('/commissions/mark-paid-batch', [CommissionController::class, 'markPaidBatch'])->name('commissions.markPaidBatch');

    // --- HRM: Attendance & Salary ---
    Route::get('/hrm/attendance', [\App\Http\Controllers\Tenant\HrmController::class, 'attendanceIndex'])->name('hrm.attendance');
    Route::post('/hrm/clock-in', [\App\Http\Controllers\Tenant\HrmController::class, 'clockIn'])->name('hrm.clock-in');
    Route::post('/hrm/clock-out', [\App\Http\Controllers\Tenant\HrmController::class, 'clockOut'])->name('hrm.clock-out');
    Route::get('/hrm/salary', [\App\Http\Controllers\Tenant\HrmController::class, 'salaryIndex'])->name('hrm.salary');
    Route::post('/hrm/salary/generate', [\App\Http\Controllers\Tenant\HrmController::class, 'salaryGenerate'])->name('hrm.salary.generate');
    Route::get('/hrm/salary/{salary}/slip', [\App\Http\Controllers\Tenant\HrmController::class, 'salarySlip'])->name('hrm.salary.slip');
    Route::put('/hrm/salary/{salary}/mark-paid', [\App\Http\Controllers\Tenant\HrmController::class, 'salaryMarkPaid'])->name('hrm.salary.mark-paid');

    // --- HRM: Leaves / Cuti ---
    Route::get('/hrm/leaves', [\App\Http\Controllers\Tenant\LeaveController::class, 'index'])->name('hrm.leaves.index');
    Route::post('/hrm/leaves', [\App\Http\Controllers\Tenant\LeaveController::class, 'store'])->name('hrm.leaves.store');
    Route::post('/hrm/leaves/{leave}/approve', [\App\Http\Controllers\Tenant\LeaveController::class, 'approve'])->name('hrm.leaves.approve');
    Route::post('/hrm/leaves/{leave}/reject', [\App\Http\Controllers\Tenant\LeaveController::class, 'reject'])->name('hrm.leaves.reject');
    Route::delete('/hrm/leaves/{leave}', [\App\Http\Controllers\Tenant\LeaveController::class, 'destroy'])->name('hrm.leaves.destroy');

    // --- POS Kasir (Retail Module) ---
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'terminal'])->name('terminal');
        Route::get('/sessions', [PosController::class, 'sessions'])->name('sessions');
        Route::get('/open', [PosController::class, 'openForm'])->name('openForm');
        Route::post('/open', [PosController::class, 'open'])->name('open');
        Route::get('/sessions/{session}/close', [PosController::class, 'closeForm'])->name('closeForm');
        Route::put('/sessions/{session}/close', [PosController::class, 'close'])->name('close');
        Route::get('/search-product', [PosController::class, 'searchProduct'])->name('search-product');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{invoice}', [PosController::class, 'receipt'])->name('receipt');
    });

    // --- Review & Warranty ---
    Route::get('/reviews', [\App\Http\Controllers\Tenant\ReviewController::class, 'index'])->name('reviews.index');
    Route::put('/reviews/{review}/publish', [\App\Http\Controllers\Tenant\ReviewController::class, 'publish'])->name('reviews.publish');
    Route::put('/reviews/{review}/unpublish', [\App\Http\Controllers\Tenant\ReviewController::class, 'unpublish'])->name('reviews.unpublish');
    Route::put('/reviews/{review}/reply', [\App\Http\Controllers\Tenant\ReviewController::class, 'reply'])->name('reviews.reply');
    Route::delete('/reviews/{review}', [\App\Http\Controllers\Tenant\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::resource('recalls', RecallController::class)->except(['show']);

    Route::get('/warranty-claims', [\App\Http\Controllers\Tenant\WarrantyClaimController::class, 'index'])->name('warranty-claims.index');
    Route::get('/warranty-claims/create', [\App\Http\Controllers\Tenant\WarrantyClaimController::class, 'create'])->name('warranty-claims.create');
    Route::post('/warranty-claims', [\App\Http\Controllers\Tenant\WarrantyClaimController::class, 'store'])->name('warranty-claims.store');
    Route::get('/warranty-claims/{warrantyClaim}', [\App\Http\Controllers\Tenant\WarrantyClaimController::class, 'show'])->name('warranty-claims.show');
    Route::get('/warranty-claims/{warrantyClaim}/edit', [\App\Http\Controllers\Tenant\WarrantyClaimController::class, 'edit'])->name('warranty-claims.edit');
    Route::put('/warranty-claims/{warrantyClaim}', [\App\Http\Controllers\Tenant\WarrantyClaimController::class, 'update'])->name('warranty-claims.update');
    Route::delete('/warranty-claims/{warrantyClaim}', [\App\Http\Controllers\Tenant\WarrantyClaimController::class, 'destroy'])->name('warranty-claims.destroy');

    // --- Payment Gateway (generic adapter, configurable) ---
    Route::resource('payment-gateways', \App\Http\Controllers\Tenant\PaymentGatewayController::class)->except(['show']);
    Route::post('/invoices/{invoice}/generate-payment-link', [\App\Http\Controllers\Tenant\PaymentGatewayController::class, 'generateLink'])->name('invoices.generate-payment-link');

    // --- User & Role Management ---
    Route::get('/users', [\App\Http\Controllers\Tenant\RoleController::class, 'userIndex'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\Tenant\RoleController::class, 'userStore'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\Tenant\RoleController::class, 'userUpdate'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\Tenant\RoleController::class, 'userDestroy'])->name('users.destroy');
    Route::get('/users/api-tokens', [\App\Http\Controllers\Tenant\RoleController::class, 'apiTokens'])->name('users.api-tokens');
    Route::post('/users/api-tokens', [\App\Http\Controllers\Tenant\RoleController::class, 'createToken'])->name('users.create-token');
    Route::delete('/admin/api-tokens/{token}', [\App\Http\Controllers\Tenant\RoleController::class, 'revokeToken'])->name('admin.revoke-token');
    Route::get('/roles', [\App\Http\Controllers\Tenant\RoleController::class, 'roleIndex'])->name('roles.index');
    Route::post('/roles', [\App\Http\Controllers\Tenant\RoleController::class, 'roleStore'])->name('roles.store');
    Route::put('/roles/{role}', [\App\Http\Controllers\Tenant\RoleController::class, 'roleUpdate'])->name('roles.update');
    Route::delete('/roles/{role}', [\App\Http\Controllers\Tenant\RoleController::class, 'roleDestroy'])->name('roles.destroy');

    // --- Activity Log + Petty Cash + 2FA ---
    Route::get('/activity-logs', [\App\Http\Controllers\Tenant\ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/petty-cash', [\App\Http\Controllers\Tenant\PettyCashController::class, 'index'])->name('petty-cash.index');
    Route::post('/petty-cash', [\App\Http\Controllers\Tenant\PettyCashController::class, 'store'])->name('petty-cash.store');
    Route::delete('/petty-cash/{pettyCash}', [\App\Http\Controllers\Tenant\PettyCashController::class, 'destroy'])->name('petty-cash.destroy');

    Route::get('/2fa/enable', [\App\Http\Controllers\Auth\TwoFactorController::class, 'enableForm'])->name('2fa.enable.form');
    Route::post('/2fa/enable', [\App\Http\Controllers\Auth\TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/2fa/disable', [\App\Http\Controllers\Auth\TwoFactorController::class, 'disable'])->name('2fa.disable');

    // --- Audit Trail ---
    Route::get('/stock-histories', [StockHistoryController::class, 'index'])->name('stock-histories.index');
    Route::get('/email-logs', [EmailLogController::class, 'index'])->name('email-logs.index');
    Route::get('/email-logs/{emailLog}', [EmailLogController::class, 'show'])->name('email-logs.show');
    Route::delete('/email-logs/{emailLog}', [EmailLogController::class, 'destroy'])->name('email-logs.destroy');

    // --- Equipment / Peralatan ---
    Route::resource('equipment', EquipmentController::class);

    // --- Subcontractors ---
    Route::resource('subcontractors', SubcontractorController::class);

    // --- Service Packages ---
    Route::resource('service-packages', ServicePackageController::class)->except(['show']);
    Route::get('/service-packages/{servicePackage}/json', [ServicePackageController::class, 'getJson'])->name('service-packages.json');

    // --- Warehouse ---
    Route::resource('warehouses', \App\Http\Controllers\Tenant\WarehouseController::class);
    Route::get('/stock-transfers', [\App\Http\Controllers\Tenant\WarehouseController::class, 'transferIndex'])->name('warehouses.transfers');
    Route::get('/stock-transfers/create', [\App\Http\Controllers\Tenant\WarehouseController::class, 'transferCreate'])->name('warehouses.transfers.create');
    Route::post('/stock-transfers', [\App\Http\Controllers\Tenant\WarehouseController::class, 'transferStore'])->name('warehouses.transfers.store');

    // --- Finance / Accounting ---
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/coa', [\App\Http\Controllers\Tenant\JournalController::class, 'coaIndex'])->name('coa');
        Route::get('/coa/create', [\App\Http\Controllers\Tenant\JournalController::class, 'coaCreate'])->name('coa.create');
        Route::post('/coa', [\App\Http\Controllers\Tenant\JournalController::class, 'coaStore'])->name('coa.store');
        Route::delete('/coa/{account}', [\App\Http\Controllers\Tenant\JournalController::class, 'coaDestroy'])->name('coa.destroy');
        Route::get('/journal', [\App\Http\Controllers\Tenant\JournalController::class, 'journalIndex'])->name('journal');
        Route::get('/journal/create', [\App\Http\Controllers\Tenant\JournalController::class, 'journalCreate'])->name('journal.create');
        Route::post('/journal', [\App\Http\Controllers\Tenant\JournalController::class, 'journalStore'])->name('journal.store');
    });

    // --- Customer Groups ---
    Route::resource('customer-groups', \App\Http\Controllers\Tenant\CustomerGroupController::class);

    // --- Blog Admin ---
    Route::prefix('blog-admin')->name('blog.admin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\BlogController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Tenant\BlogController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Tenant\BlogController::class, 'store'])->name('store');
        Route::get('/{post}/edit', [\App\Http\Controllers\Tenant\BlogController::class, 'edit'])->name('edit');
        Route::put('/{post}', [\App\Http\Controllers\Tenant\BlogController::class, 'update'])->name('update');
        Route::delete('/{post}', [\App\Http\Controllers\Tenant\BlogController::class, 'destroy'])->name('destroy');
        Route::get('/categories', [\App\Http\Controllers\Tenant\BlogController::class, 'categoryIndex'])->name('categories');
        Route::post('/categories', [\App\Http\Controllers\Tenant\BlogController::class, 'categoryStore'])->name('categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\Tenant\BlogController::class, 'categoryUpdate'])->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\Tenant\BlogController::class, 'categoryDestroy'])->name('categories.destroy');
    });
});

// License v3 pairing routes (must be at bottom to avoid route conflict)
require base_path('routes/pair-routes.php');

// Generic PSEO handler — menangkap semua pattern URL masif (HARUS DI PALING BAWAH)
// Exclude: admin paths, assets, and well-known routes
Route::get('/{slug}', [ProgrammaticSeoController::class, 'genericPseo'])
    ->where('slug', '^(?!admin|api|__pair|webhooks|login|logout|docs|customer|track|booking|payment/callback|sitemap|blog-admin).*')
    ->name('seo.generic');

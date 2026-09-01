<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\DashboardController as TenantDashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\ProgrammaticSeoController;
use App\Http\Controllers\PublicEstimateController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\PublicSurveyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Tenant\ActivityLogController;
use App\Http\Controllers\Tenant\BankAccountController;
use App\Http\Controllers\Tenant\BankReconciliationController;
use App\Http\Controllers\Tenant\BlogController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\BudgetController;
use App\Http\Controllers\Tenant\BusinessHourController;
use App\Http\Controllers\Tenant\CampaignController;
use App\Http\Controllers\Tenant\CheckoutCategoryController;
use App\Http\Controllers\Tenant\CityController;
use App\Http\Controllers\Tenant\ColorController;
use App\Http\Controllers\Tenant\CommissionController;
use App\Http\Controllers\Tenant\CompanyController;
use App\Http\Controllers\Tenant\CountryController;
use App\Http\Controllers\Tenant\CurrencyController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomerGroupController;
use App\Http\Controllers\Tenant\CustomFieldController;
use App\Http\Controllers\Tenant\EmailLogController;
use App\Http\Controllers\Tenant\EquipmentController;
use App\Http\Controllers\Tenant\EstimateController;
use App\Http\Controllers\Tenant\ExpenseController;
use App\Http\Controllers\Tenant\FleetContractController;
use App\Http\Controllers\Tenant\FuelTypeController;
use App\Http\Controllers\Tenant\GatePassController;
use App\Http\Controllers\Tenant\HolidayController;
use App\Http\Controllers\Tenant\HrmController;
use App\Http\Controllers\Tenant\IncomeController;
use App\Http\Controllers\Tenant\InspectionPointController;
use App\Http\Controllers\Tenant\InsuranceClaimController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\InvoiceSchemeController;
use App\Http\Controllers\Tenant\JobcardController;
use App\Http\Controllers\Tenant\JournalController;
use App\Http\Controllers\Tenant\LeaveController;
use App\Http\Controllers\Tenant\LoyaltyController;
use App\Http\Controllers\Tenant\MediaAttachmentController;
use App\Http\Controllers\Tenant\NoteController;
use App\Http\Controllers\Tenant\NotificationTemplateController;
use App\Http\Controllers\Tenant\ObservationPointController;
use App\Http\Controllers\Tenant\ObservationTypeController;
use App\Http\Controllers\Tenant\PartReservationController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\PaymentGatewayController;
use App\Http\Controllers\Tenant\PaymentMethodController;
use App\Http\Controllers\Tenant\PettyCashController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\PrintController;
use App\Http\Controllers\Tenant\PrinterController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ProductTypeController;
use App\Http\Controllers\Tenant\ProductUnitController;
use App\Http\Controllers\Tenant\ProductVariationController;
use App\Http\Controllers\Tenant\PurchaseController;
use App\Http\Controllers\Tenant\PurchaseOrderController;
use App\Http\Controllers\Tenant\PurchaseRequisitionController;
use App\Http\Controllers\Tenant\PurchaseReturnController;
use App\Http\Controllers\Tenant\RecallController;
use App\Http\Controllers\Tenant\ReminderController;
use App\Http\Controllers\Tenant\RepairCategoryController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\ReviewController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SearchController;
use App\Http\Controllers\Tenant\SellingPriceGroupController;
use App\Http\Controllers\Tenant\SellReturnController;
use App\Http\Controllers\Tenant\ServiceController;
use App\Http\Controllers\Tenant\ServicePackageController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\StateController;
use App\Http\Controllers\Tenant\StockAdjustmentController;
use App\Http\Controllers\Tenant\StockHistoryController;
use App\Http\Controllers\Tenant\SubcontractorController;
use App\Http\Controllers\Tenant\SupplierClaimController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\TaxGroupController;
use App\Http\Controllers\Tenant\TaxRateController;
use App\Http\Controllers\Tenant\TechnicianSkillController;
use App\Http\Controllers\Tenant\VehicleBrandController;
use App\Http\Controllers\Tenant\VehicleController;
use App\Http\Controllers\Tenant\VehicleTypeController;
use App\Http\Controllers\Tenant\VoucherController;
use App\Http\Controllers\Tenant\WarehouseController;
use App\Http\Controllers\Tenant\WarrantyClaimController;
use App\Http\Controllers\Tenant\WashbayController;
use App\Http\Controllers\TrackingController;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\ReportService;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Locale switcher (id|en) — stores locale in session
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

// Public tracking service (token-based, no auth)
Route::get('/track/{token}', [TrackingController::class, 'show'])->name('public.tracking');
Route::post('/track/{token}/review', [TrackingController::class, 'review'])->name('public.tracking.review');

// Public NPS survey (post-service rating, token-based, no auth)
Route::get('/survey/{token}', [PublicSurveyController::class, 'show'])->name('survey.show');
Route::post('/survey/{token}', [PublicSurveyController::class, 'store'])->name('survey.store');

// Public shareable invoice link (token-based, no auth)
Route::get('/invoice/{token}', [PublicInvoiceController::class, 'show'])->name('public.invoice');

// Public service estimate approval (token-based, no auth)
Route::get('/approve/{token}', [ApprovalController::class, 'showApprove'])->name('public.approval.approve');
Route::post('/approve/{token}', [ApprovalController::class, 'approve'])->name('public.approval.approve.store');
Route::get('/reject/{token}', [ApprovalController::class, 'showReject'])->name('public.approval.reject');
Route::post('/reject/{token}', [ApprovalController::class, 'reject'])->name('public.approval.reject.store');

// Public estimate document (token-based, no auth) — current estimate version
Route::get('/estimate/{token}', [PublicEstimateController::class, 'show'])->name('public.estimate.show');
Route::get('/estimate/{token}/pdf', [PublicEstimateController::class, 'pdf'])->name('public.estimate.pdf');
Route::post('/estimate/{token}/approve', [PublicEstimateController::class, 'approve'])->name('public.estimate.approve');
Route::post('/estimate/{token}/reject', [PublicEstimateController::class, 'reject'])->name('public.estimate.reject');

// Payment Gateway webhook callback (PUBLIC — gateway POST tanpa session)
Route::any('/payment/callback/{token}', [PaymentGatewayController::class, 'callback'])->name('payment.callback');

// 2FA challenge (post-login)
Route::get('/2fa/challenge', [TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify')->middleware('throttle:10,1');

// Public Booking Online (no auth)
Route::get('/booking', [BookingController::class, 'publicForm'])->name('public.booking');
Route::post('/booking', [BookingController::class, 'publicStore'])->name('public.booking.store')->middleware('throttle:10,1');

// Customer Portal (separate auth via session)
Route::get('/customer/login', [CustomerPortalController::class, 'loginForm'])->name('customer.login');
Route::post('/customer/login', [CustomerPortalController::class, 'login'])->name('customer.login.submit')->middleware('throttle:10,1');
Route::get('/customer/dashboard', [CustomerPortalController::class, 'dashboard'])->name('customer.dashboard');
Route::get('/customer/invoice/{id}', [CustomerPortalController::class, 'invoiceDetail'])->name('customer.invoice');
Route::get('/customer/service/{id}', [CustomerPortalController::class, 'serviceDetail'])->name('customer.service');
Route::post('/customer/invoice/{id}/upload-payment', [CustomerPortalController::class, 'uploadPayment'])->name('customer.upload-payment');
Route::post('/customer/change-password', [CustomerPortalController::class, 'changePassword'])->name('customer.change-password');
Route::post('/customer/logout', [CustomerPortalController::class, 'logout'])->name('customer.logout');

// Public SEO pages
Route::get('/best/{category}', [ProgrammaticSeoController::class, 'bestService'])->name('seo.best');
Route::get('/best/{category}/{year}', [ProgrammaticSeoController::class, 'bestService'])->name('seo.best.year');
Route::get('/alternatives-to/{slug}', [ProgrammaticSeoController::class, 'serviceAlternatives'])->name('seo.alternatives');
Route::get('/compare/{a}-vs-{b}', [ProgrammaticSeoController::class, 'compareServices'])->name('seo.compare');

// Multilingual PSEO routes (ID / EN / DE)
foreach (['id', 'en', 'de'] as $lang) {
    Route::prefix($lang)->group(function () use ($lang) {
        Route::get('/bengkel-{city}', [ProgrammaticSeoController::class, 'cityLanding'])->name("seo.{$lang}.city");
        Route::get('/bengkel-{city}/{kelurahan}', [ProgrammaticSeoController::class, 'kelurahanLanding'])->name("seo.{$lang}.kelurahan");
        Route::get('/bengkel-{brand}-{city}', [ProgrammaticSeoController::class, 'brandCityLanding'])->name("seo.{$lang}.brand-city");
        Route::get('/service-{service}-{city}', [ProgrammaticSeoController::class, 'serviceCityLanding'])->name("seo.{$lang}.service-city");
        Route::get('/bengkel-terbaik-{city}', [ProgrammaticSeoController::class, 'bestCityLanding'])->name("seo.{$lang}.best-city");
    });
}

// Blog public
Route::get('/blog', function () {
    $appName = config('app.name');
    $articles = collect();
    $categories = collect();
    $recent = collect();

    if (class_exists(BlogPost::class)) {
        $articles = BlogPost::published()->orderBy('published_at', 'desc')->limit(24)->get();
        $recent = BlogPost::published()->orderBy('published_at', 'desc')->limit(5)->get();
    }
    if (class_exists(BlogCategory::class)) {
        $categories = BlogCategory::orderBy('name')->get();
    }

    $metaTitle = "Blog {$appName} — Tips & Berita Otomotif";
    $metaDescription = "Baca tips perawatan mobil, berita otomotif, dan panduan service dari {$appName}.";
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Blog',
        'name' => "Blog {$appName}",
        'url' => url('/blog'),
        'inLanguage' => 'id-ID',
        'blogPost' => $articles->map(fn ($p) => [
            '@type' => 'BlogPosting',
            'headline' => $p->title,
            'url' => url('/blog/'.$p->slug),
            'datePublished' => optional($p->published_at ?? $p->created_at)->toIso8601String(),
        ])->all(),
    ];

    return view('seo.blog-list', [
        'metaTitle' => $metaTitle,
        'metaDescription' => $metaDescription,
        'jsonLd' => $jsonLd,
        'articles' => $articles,
        'categories' => $categories,
        'recent' => $recent,
    ]);
})->name('blog.index');
Route::get('/blog/category/{slug}', [ProgrammaticSeoController::class, 'blogCategory'])->name('blog.category');
Route::get('/blog/feed.xml', [BlogController::class, 'rss'])->name('blog.rss');
Route::get('/blog/{slug}', [ProgrammaticSeoController::class, 'blogArticle'])->name('seo.blog');

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
Route::get('/', function (ReportService $reportService) {
    if (auth()->check()) {
        return app(TenantDashboardController::class)->index($reportService);
    }

    return view('welcome');
})->name('dashboard');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // --- Global search command palette ---
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // --- Dashboard config ---
    Route::get('/dashboard/config', [TenantDashboardController::class, 'configure'])->name('dashboard.config');
    Route::post('/dashboard/config', [TenantDashboardController::class, 'saveConfig'])->name('dashboard.config.save');

    // --- Media / Document attachments ---
    Route::get('/media/{media}/download', [MediaAttachmentController::class, 'download'])->name('attachments.download');
    Route::post('/media', [MediaAttachmentController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [MediaAttachmentController::class, 'destroy'])->name('media.destroy');

    // --- Reports ---
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/service', [ReportController::class, 'serviceReport'])->name('reports.service');
    Route::get('/reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
    Route::get('/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');
    Route::get('/reports/financial', [ReportController::class, 'financialReport'])->name('reports.financial');
    Route::get('/reports/technician', [ReportController::class, 'technicianPerformance'])->name('reports.technician');
    Route::get('/reports/customer-lifetime', [ReportController::class, 'customerLifetime'])->name('reports.customer-lifetime');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf')->middleware('role:super_admin|admin|manager');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel')->middleware('role:super_admin|admin|manager');
    Route::get('/reports/service/{service}/pdf', [ReportController::class, 'serviceReportPdf'])->name('reports.service-pdf');
    Route::get('/reports/ar-aging', [ReportController::class, 'arAging'])->name('reports.ar-aging');
    Route::get('/reports/parts-usage', [ReportController::class, 'partsUsage'])->name('reports.parts-usage');
    Route::get('/reports/branch-comparison', [ReportController::class, 'branchComparison'])->name('reports.branch-comparison');
    Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');
    Route::get('/reports/general-ledger', [ReportController::class, 'generalLedger'])->name('reports.general-ledger')->middleware('role:super_admin|admin|manager');
    Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance')->middleware('role:super_admin|admin|manager');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss')->middleware('role:super_admin|admin|manager');
    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet')->middleware('role:super_admin|admin|manager');

    // --- Settings ---
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('role:super_admin|admin');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('role:super_admin|admin');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('role:super_admin|admin');
    Route::get('/settings/backup', [SettingsController::class, 'backupPage'])->name('settings.backup-page')->middleware('role:super_admin|admin');
    Route::post('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup')->middleware('role:super_admin|admin');
    Route::get('/settings/backup/download', [SettingsController::class, 'backupDownload'])->name('settings.backup-download')->middleware('role:super_admin|admin');
    Route::post('/settings/cache-clear', [SettingsController::class, 'cacheClear'])->name('settings.cache-clear')->middleware('role:super_admin|admin');
    Route::post('/settings/optimize', [SettingsController::class, 'optimize'])->name('settings.optimize')->middleware('role:super_admin|admin');

    // --- Printer, Numbering & Bank Accounts ---
    Route::resource('printers', PrinterController::class)->except(['show']);
    Route::resource('invoice-schemes', InvoiceSchemeController::class)->except(['show']);
    Route::resource('bank-accounts', BankAccountController::class)->except(['show']);
    Route::resource('bank-reconciliations', BankReconciliationController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('budgets', BudgetController::class)->except(['show']);

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
    Route::get('/invoices/{invoice}/payment-proof', [InvoiceController::class, 'paymentProof'])->name('invoices.payment-proof')->middleware('role:super_admin|admin|manager');

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
    Route::post('/services/{service}/survey-link', [ServiceController::class, 'surveyLink'])->name('services.survey-link');
    Route::get('/services/{service}/sticker', [ServiceController::class, 'printNextServiceSticker'])->name('services.sticker');
    Route::get('/services/{service}/condition-report', [ServiceController::class, 'printConditionReport'])->name('services.condition-report');
    Route::get('/services/{service}/send-wa', [ServiceController::class, 'sendWA'])->name('services.sendWA');

    // --- Service Estimates (quotation documents) ---
    Route::post('/services/{service}/estimates', [EstimateController::class, 'store'])->name('services.estimates.store');
    Route::get('/estimates/{estimate}/pdf', [EstimateController::class, 'pdf'])->name('estimates.pdf');
    Route::get('/estimates/{estimate}/preview', [EstimateController::class, 'preview'])->name('estimates.preview');
    Route::get('/estimates/{estimate}/print', [EstimateController::class, 'print'])->name('estimates.print');
    Route::post('/estimates/{estimate}/send-wa', [EstimateController::class, 'sendWA'])->name('estimates.send-wa');
    Route::post('/estimates/{estimate}/send-email', [EstimateController::class, 'sendEmail'])->name('estimates.send-email');
    Route::post('/estimates/{estimate}/revise', [EstimateController::class, 'revise'])->name('estimates.revise');
    Route::post('/estimates/{estimate}/override-approve', [EstimateController::class, 'overrideApprove'])->name('estimates.override-approve');
    Route::post('/estimates/{estimate}/convert-invoice', [EstimateController::class, 'convertToInvoice'])->name('estimates.convert-invoice');
    Route::put('/estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');

    // --- Parts Reservation ---
    Route::post('/services/{service}/reservations', [PartReservationController::class, 'store'])->name('services.reservations.store');
    Route::post('/services/reservations/{reservation}/release', [PartReservationController::class, 'release'])->name('services.reservations.release');
    Route::post('/services/reservations/{reservation}/consume', [PartReservationController::class, 'consume'])->name('services.reservations.consume');

    // --- Vehicles custom routes (before resource) ---
    Route::post('/vehicles/{vehicle}/upload-image', [VehicleController::class, 'uploadImage'])->name('vehicles.upload-image');
    Route::delete('/vehicles/images/{image}', [VehicleController::class, 'deleteImage'])->name('vehicles.delete-image');
    Route::get('/vehicles/{vehicle}/history', [VehicleController::class, 'serviceHistory'])->name('vehicles.history');

    // --- Invoices custom routes (before resource) ---
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/send-wa', [InvoiceController::class, 'sendWA'])->name('invoices.sendWA');
    Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.sendEmail');
    Route::post('/invoices/{invoice}/share', [InvoiceController::class, 'share'])->name('invoices.share');

    // --- Thermal Print ---
    Route::post('/invoices/{invoice}/print', [PrintController::class, 'invoice'])->name('print.invoice');
    Route::post('/services/{service}/print-jobcard', [PrintController::class, 'jobcard'])->name('print.jobcard');
    Route::post('/pos/open-drawer', [PrintController::class, 'openDrawer'])->name('print.open-drawer');
    Route::get('/print/invoice/{invoice}/raw', [PrintController::class, 'rawData'])->name('print.raw');

    // --- Products custom routes (before resource) ---
    Route::get('/products/reorder', [ProductController::class, 'reorderSuggestions'])->name('products.reorder');
    Route::post('/products/reorder/create-po', [ProductController::class, 'createReorderPo'])->name('products.reorder.po');
    Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import-form');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::match(['get', 'post'], '/products/stock-opname', [ProductController::class, 'stockOpname'])->name('products.stock-opname');
    Route::get('/products/search-json', [ProductController::class, 'searchJson'])->name('products.search-json');
    Route::match(['get', 'post'], '/products/{product}/stock-adjust', [ProductController::class, 'stockAdjust'])->name('products.stock-adjust');
    Route::get('/products/{product}/barcode', [ProductController::class, 'printBarcode'])->name('products.barcode');
    Route::get('/products/{product}/variations', [ProductVariationController::class, 'index'])->name('products.variations.index');
    Route::post('/products/{product}/variations', [ProductVariationController::class, 'store'])->name('products.variations.store');
    Route::put('/products/{product}/variations/{variation}', [ProductVariationController::class, 'update'])->name('products.variations.update');
    Route::delete('/products/{product}/variations/{variation}', [ProductVariationController::class, 'destroy'])->name('products.variations.destroy');

    // --- Stock Adjustments (approval flow) ---
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    // Approval is separated from request creation (segregation of duties).
    Route::post('/stock-adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve')->middleware('role:super_admin|admin|manager');
    Route::post('/stock-adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('stock-adjustments.reject')->middleware('role:super_admin|admin|manager');

    // --- Purchases custom routes (before resource) ---
    Route::post('/purchases/{purchase}/mark-received', [PurchaseController::class, 'markReceived'])->name('purchases.mark-received');
    Route::get('/purchases/return', [PurchaseReturnController::class, 'index'])->name('purchases.return.index');
    Route::get('/purchases/{purchase}/return', [PurchaseReturnController::class, 'create'])->name('purchases.return.create');
    Route::post('/purchases/{purchase}/return', [PurchaseReturnController::class, 'store'])->name('purchases.return.store');

    // Sales Orders merged into POS — redirect old URLs
    Route::redirect('/sales-orders', '/pos/terminal');

    // --- Purchase Orders custom routes (before resource) ---
    Route::post('/purchase-orders/{purchaseOrder}/mark-received', [PurchaseOrderController::class, 'markReceived'])->name('purchase-orders.mark-received')->middleware('role:super_admin|admin|manager|inventory');
    Route::post('/purchase-orders/{purchaseOrder}/{action}', [PurchaseOrderController::class, 'transition'])
        ->whereIn('action', ['submit', 'approve', 'close'])->name('purchase-orders.transition')
        ->middleware('role:super_admin|admin|manager|inventory');

    // --- Purchase Requisitions custom routes (before resource) ---
    Route::post('/purchase-requisitions/{purchaseRequisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('purchase-requisitions.submit');
    Route::post('/purchase-requisitions/{purchaseRequisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('purchase-requisitions.approve')->middleware('role:super_admin|admin|manager');
    Route::post('/purchase-requisitions/{purchaseRequisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('purchase-requisitions.reject')->middleware('role:super_admin|admin|manager');
    Route::post('/purchase-requisitions/{purchaseRequisition}/convert', [PurchaseRequisitionController::class, 'convertToPurchaseOrder'])->name('purchase-requisitions.convert')->middleware('role:super_admin|admin|manager');

    // --- Gate Passes custom routes (before resource) ---
    Route::get('/gate-passes/{gate_pass}/print', [GatePassController::class, 'print'])->name('gate-passes.print');
    Route::post('/gate-passes/{gate_pass}/mark-exit', [GatePassController::class, 'markExit'])->name('gate-passes.mark-exit');

    // --- Reminders custom routes (before resource) ---
    Route::post('/reminders/{reminder}/send', [ReminderController::class, 'send'])->name('reminders.send');
    Route::post('/reminders/send-scheduled', [ReminderController::class, 'sendScheduled'])->name('reminders.send-scheduled');

    // --- Notification Templates custom routes (before resource) ---
    Route::get('/notification-templates/{template}/preview', [NotificationTemplateController::class, 'preview'])->name('notification-templates.preview');

    // --- Customers custom routes (before resource) ---
    Route::get('/customers/import', [CustomerController::class, 'importForm'])->name('customers.import-form');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');

    // --- Resource routes ---
    Route::resource('customers', CustomerController::class);
    Route::resource('vehicles', VehicleController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::resource('products', ProductController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::resource('purchase-requisitions', PurchaseRequisitionController::class);
    Route::resource('sell-returns', SellReturnController::class)->only(['index', 'create', 'store', 'show']);
    // Sales merged into POS — redirect old URLs to POS terminal
    Route::redirect('/sales', '/pos/terminal')->name('sales.index');
    Route::redirect('/sales/create', '/pos/terminal');
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
    Route::resource('tax-groups', TaxGroupController::class)->except(['show']);
    Route::resource('payment-methods', PaymentMethodController::class)->except(['show']);
    Route::resource('product-units', ProductUnitController::class)->except(['show']);
    Route::resource('product-types', ProductTypeController::class)->except(['show']);
    Route::resource('colors', ColorController::class)->except(['show']);
    Route::resource('fuel-types', FuelTypeController::class)->except(['show']);
    Route::resource('vehicle-brands', VehicleBrandController::class)->except(['show']);
    Route::resource('vehicle-types', VehicleTypeController::class)->except(['show']);

    // --- Perusahaan / Multi-company ---
    Route::resource('companies', CompanyController::class);

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
    Route::get('/bookings/calendar', [BookingController::class, 'calendar'])->name('bookings.calendar');
    Route::get('/bookings/calendar/events', [BookingController::class, 'calendarEvents'])->name('bookings.calendar.events');
    Route::get('/bookings', [BookingController::class, 'adminIndex'])->name('bookings.index');
    Route::put('/bookings/{booking}', [BookingController::class, 'adminUpdate'])->name('bookings.update');
    Route::delete('/bookings/{booking}', [BookingController::class, 'adminDestroy'])->name('bookings.destroy');
    Route::post('/bookings/{booking}/convert', [BookingController::class, 'convertToService'])->name('bookings.convert');

    // --- Marketing: Voucher & Loyalty ---
    Route::resource('vouchers', VoucherController::class)->except(['show']);
    Route::post('/vouchers-validate', [VoucherController::class, 'validateCode'])->name('vouchers.validate');
    Route::get('/marketing/campaign', [CampaignController::class, 'index'])->name('marketing.campaign');
    Route::post('/marketing/campaign', [CampaignController::class, 'send'])->name('marketing.campaign.send');
    Route::get('/marketing/campaign/search', [CampaignController::class, 'searchCustomers'])->name('marketing.campaign.search');

    Route::get('/loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index');
    Route::get('/loyalty/{customer}', [LoyaltyController::class, 'show'])->name('loyalty.show');
    Route::post('/loyalty/{customer}/adjust', [LoyaltyController::class, 'adjust'])->name('loyalty.adjust');

    // --- HRM: Komisi Teknisi ---
    Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/report', [CommissionController::class, 'report'])->name('commissions.report');
    Route::put('/commissions/{serviceTechnician}/mark-paid', [CommissionController::class, 'markPaid'])->name('commissions.markPaid');
    Route::post('/commissions/mark-paid-batch', [CommissionController::class, 'markPaidBatch'])->name('commissions.markPaidBatch');

    // --- HRM: Timer kerja teknisi (start/finish per job) ---
    Route::post('/service-technicians/{serviceTechnician}/start', [CommissionController::class, 'startJob'])->name('service-technicians.start');
    Route::post('/service-technicians/{serviceTechnician}/finish', [CommissionController::class, 'finishJob'])->name('service-technicians.finish');

    // --- HRM: Attendance & Salary ---
    Route::get('/hrm/attendance', [HrmController::class, 'attendanceIndex'])->name('hrm.attendance');
    Route::post('/hrm/clock-in', [HrmController::class, 'clockIn'])->name('hrm.clock-in');
    Route::post('/hrm/clock-out', [HrmController::class, 'clockOut'])->name('hrm.clock-out');
    Route::get('/hrm/salary', [HrmController::class, 'salaryIndex'])->name('hrm.salary');
    Route::post('/hrm/salary/generate', [HrmController::class, 'salaryGenerate'])->name('hrm.salary.generate');
    Route::get('/hrm/salary/{salary}/slip', [HrmController::class, 'salarySlip'])->name('hrm.salary.slip');
    Route::put('/hrm/salary/{salary}/mark-paid', [HrmController::class, 'salaryMarkPaid'])->name('hrm.salary.mark-paid');

    // --- HRM: Skill Matrix Teknisi ---
    Route::get('/technician-skills', [TechnicianSkillController::class, 'index'])->name('technician-skills.index');
    Route::post('/technician-skills', [TechnicianSkillController::class, 'store'])->name('technician-skills.store');
    Route::put('/technician-skills/{skill}', [TechnicianSkillController::class, 'update'])->name('technician-skills.update');
    Route::delete('/technician-skills/{skill}', [TechnicianSkillController::class, 'destroy'])->name('technician-skills.destroy');

    // --- HRM: Leaves / Cuti ---
    Route::get('/hrm/leaves', [LeaveController::class, 'index'])->name('hrm.leaves.index');
    Route::post('/hrm/leaves', [LeaveController::class, 'store'])->name('hrm.leaves.store');
    Route::post('/hrm/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('hrm.leaves.approve');
    Route::post('/hrm/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('hrm.leaves.reject');
    Route::delete('/hrm/leaves/{leave}', [LeaveController::class, 'destroy'])->name('hrm.leaves.destroy');

    // --- POS Kasir (Retail Module) ---
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'terminal'])->name('terminal');
        Route::get('/sessions', [PosController::class, 'sessions'])->name('sessions');
        Route::get('/open', [PosController::class, 'openForm'])->name('openForm');
        Route::post('/open', [PosController::class, 'open'])->name('open');
        Route::get('/sessions/{session}/close', [PosController::class, 'closeForm'])->name('closeForm');
        Route::put('/sessions/{session}/close', [PosController::class, 'close'])->name('close');
        Route::get('/search-product', [PosController::class, 'searchProduct'])->name('search-product');
        Route::get('/prices', [PosController::class, 'prices'])->name('prices');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{invoice}', [PosController::class, 'receipt'])->name('receipt');
        Route::post('/hold', [PosController::class, 'hold'])->name('hold');
        Route::get('/held', [PosController::class, 'heldList'])->name('held');
        Route::get('/held/{held}', [PosController::class, 'recall'])->name('recall');
        Route::delete('/held/{held}', [PosController::class, 'releaseHeld'])->name('release');
    });

    // --- Review & Warranty ---
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::put('/reviews/{review}/publish', [ReviewController::class, 'publish'])->name('reviews.publish');
    Route::put('/reviews/{review}/unpublish', [ReviewController::class, 'unpublish'])->name('reviews.unpublish');
    Route::put('/reviews/{review}/reply', [ReviewController::class, 'reply'])->name('reviews.reply');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::resource('recalls', RecallController::class)->except(['show']);

    Route::get('/warranty-claims', [WarrantyClaimController::class, 'index'])->name('warranty-claims.index');
    Route::get('/warranty-claims/create', [WarrantyClaimController::class, 'create'])->name('warranty-claims.create');
    Route::post('/warranty-claims', [WarrantyClaimController::class, 'store'])->name('warranty-claims.store');
    Route::get('/warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'show'])->name('warranty-claims.show');
    Route::get('/warranty-claims/{warrantyClaim}/edit', [WarrantyClaimController::class, 'edit'])->name('warranty-claims.edit');
    Route::put('/warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'update'])->name('warranty-claims.update');
    Route::delete('/warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'destroy'])->name('warranty-claims.destroy');

    // --- Insurance Claims (Klaim Asuransi) ---
    Route::get('/insurance-claims', [InsuranceClaimController::class, 'index'])->name('insurance-claims.index');
    Route::get('/insurance-claims/create', [InsuranceClaimController::class, 'create'])->name('insurance-claims.create');
    Route::post('/insurance-claims', [InsuranceClaimController::class, 'store'])->name('insurance-claims.store');
    Route::get('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'show'])->name('insurance-claims.show');
    Route::put('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'update'])->name('insurance-claims.update');
    Route::post('/insurance-claims/{insuranceClaim}/approve', [InsuranceClaimController::class, 'approve'])->name('insurance-claims.approve');
    Route::post('/insurance-claims/{insuranceClaim}/reject', [InsuranceClaimController::class, 'reject'])->name('insurance-claims.reject');
    Route::post('/insurance-claims/{insuranceClaim}/mark-paid', [InsuranceClaimController::class, 'markPaid'])->name('insurance-claims.mark-paid');
    Route::delete('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'destroy'])->name('insurance-claims.destroy');

    // --- Supplier Claims (Klaim garansi ke supplier) ---
    Route::get('/supplier-claims', [SupplierClaimController::class, 'index'])->name('supplier-claims.index');
    Route::get('/supplier-claims/create', [SupplierClaimController::class, 'create'])->name('supplier-claims.create');
    Route::post('/supplier-claims', [SupplierClaimController::class, 'store'])->name('supplier-claims.store');
    Route::get('/supplier-claims/{supplierClaim}', [SupplierClaimController::class, 'show'])->name('supplier-claims.show');
    Route::post('/supplier-claims/{supplierClaim}/approve', [SupplierClaimController::class, 'approve'])->name('supplier-claims.approve');
    Route::post('/supplier-claims/{supplierClaim}/reject', [SupplierClaimController::class, 'reject'])->name('supplier-claims.reject');
    Route::post('/supplier-claims/{supplierClaim}/mark-paid', [SupplierClaimController::class, 'markPaid'])->name('supplier-claims.mark-paid');
    Route::delete('/supplier-claims/{supplierClaim}', [SupplierClaimController::class, 'destroy'])->name('supplier-claims.destroy');

    // --- Fleet Contracts (Kontrak Fleet) ---
    Route::get('/fleet-contracts/due', [FleetContractController::class, 'dueVehicles'])->name('fleet-contracts.due');
    Route::resource('fleet-contracts', FleetContractController::class);

    // --- Payment Gateway (generic adapter, configurable) ---
    Route::resource('payment-gateways', PaymentGatewayController::class)->except(['show'])->middleware('role:super_admin|admin');
    Route::post('/invoices/{invoice}/generate-payment-link', [PaymentGatewayController::class, 'generateLink'])->name('invoices.generate-payment-link');

    // --- User & Role Management ---
    Route::get('/users', [RoleController::class, 'userIndex'])->name('users.index')->middleware('role:super_admin|admin');
    Route::post('/users', [RoleController::class, 'userStore'])->name('users.store')->middleware('role:super_admin|admin');
    Route::put('/users/{user}', [RoleController::class, 'userUpdate'])->name('users.update')->middleware('role:super_admin|admin');
    Route::delete('/users/{user}', [RoleController::class, 'userDestroy'])->name('users.destroy')->middleware('role:super_admin|admin');
    Route::get('/users/api-tokens', [RoleController::class, 'apiTokens'])->name('users.api-tokens')->middleware('role:super_admin|admin');
    Route::post('/users/api-tokens', [RoleController::class, 'createToken'])->name('users.create-token')->middleware('role:super_admin|admin');
    Route::delete('/admin/api-tokens/{token}', [RoleController::class, 'revokeToken'])->name('admin.revoke-token')->middleware('role:super_admin|admin');
    Route::get('/roles', [RoleController::class, 'roleIndex'])->name('roles.index')->middleware('role:super_admin|admin');
    Route::post('/roles', [RoleController::class, 'roleStore'])->name('roles.store')->middleware('role:super_admin');
    Route::put('/roles/{role}', [RoleController::class, 'roleUpdate'])->name('roles.update')->middleware('role:super_admin');
    Route::delete('/roles/{role}', [RoleController::class, 'roleDestroy'])->name('roles.destroy')->middleware('role:super_admin');

    // --- Activity Log + Petty Cash + 2FA ---
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index')->middleware('role:super_admin|admin|manager');
    Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty-cash.index');
    Route::post('/petty-cash', [PettyCashController::class, 'store'])->name('petty-cash.store');
    Route::delete('/petty-cash/{pettyCash}', [PettyCashController::class, 'destroy'])->name('petty-cash.destroy');

    Route::get('/2fa/enable', [TwoFactorController::class, 'enableForm'])->name('2fa.enable.form');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

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
    Route::resource('warehouses', WarehouseController::class);
    Route::get('/stock-transfers', [WarehouseController::class, 'transferIndex'])->name('warehouses.transfers');
    Route::get('/stock-transfers/create', [WarehouseController::class, 'transferCreate'])->name('warehouses.transfers.create');
    Route::post('/stock-transfers', [WarehouseController::class, 'transferStore'])->name('warehouses.transfers.store');

    // --- Finance / Accounting ---
    Route::prefix('finance')->middleware('role:super_admin|admin|manager')->name('finance.')->group(function () {
        Route::get('/coa', [JournalController::class, 'coaIndex'])->name('coa');
        Route::get('/coa/create', [JournalController::class, 'coaCreate'])->name('coa.create');
        Route::post('/coa', [JournalController::class, 'coaStore'])->name('coa.store');
        Route::delete('/coa/{account}', [JournalController::class, 'coaDestroy'])->name('coa.destroy');
        Route::get('/journal', [JournalController::class, 'journalIndex'])->name('journal');
        Route::get('/journal/create', [JournalController::class, 'journalCreate'])->name('journal.create');
        Route::post('/journal', [JournalController::class, 'journalStore'])->name('journal.store');
    });

    // --- Customer Groups ---
    Route::resource('customer-groups', CustomerGroupController::class);

    // --- Selling Price Groups ---
    Route::get('/selling-price-groups/{sellingPriceGroup}/prices', [SellingPriceGroupController::class, 'prices'])->name('selling-price-groups.prices');
    Route::post('/selling-price-groups/{sellingPriceGroup}/prices', [SellingPriceGroupController::class, 'setProductPrices'])->name('selling-price-groups.prices.store');
    Route::resource('selling-price-groups', SellingPriceGroupController::class)->except(['show']);

    // --- Blog Admin ---
    Route::prefix('blog-admin')->name('blog.admin.')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/create', [BlogController::class, 'create'])->name('create');
        Route::post('/', [BlogController::class, 'store'])->name('store');
        Route::get('/{post}/edit', [BlogController::class, 'edit'])->name('edit');
        Route::put('/{post}', [BlogController::class, 'update'])->name('update');
        Route::delete('/{post}', [BlogController::class, 'destroy'])->name('destroy');
        Route::get('/categories', [BlogController::class, 'categoryIndex'])->name('categories');
        Route::post('/categories', [BlogController::class, 'categoryStore'])->name('categories.store');
        Route::put('/categories/{category}', [BlogController::class, 'categoryUpdate'])->name('categories.update');
        Route::delete('/categories/{category}', [BlogController::class, 'categoryDestroy'])->name('categories.destroy');
    });
});

// License v3 pairing routes (must be at bottom to avoid route conflict)
require base_path('routes/pair-routes.php');

// Generic PSEO handler — menangkap semua pattern URL masif (HARUS DI PALING BAWAH)
// Exclude: admin paths, assets, and well-known routes
Route::get('/{slug}', [ProgrammaticSeoController::class, 'genericPseo'])
    ->where('slug', '^(?!admin|api|__pair|webhooks|login|logout|docs|customer|track|booking|payment/callback|sitemap|blog-admin).*')
    ->name('seo.generic');

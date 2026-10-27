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
use App\Http\Controllers\Tenant\FindingController;
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
use App\Http\Controllers\Tenant\WorkPackageController;
use App\Http\Controllers\Tenant\WorkQcController;
use App\Http\Controllers\Tenant\WorkTaskController;
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
Route::post('/estimate/{token}/decide', [PublicEstimateController::class, 'decide'])->name('public.estimate.decide');
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
Route::get('/robots.txt', fn () => response()->file(public_path('robots.txt'), ['Content-Type' => 'text/plain']))->name('robots');
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
    Route::get('/media/{media}/download', [MediaAttachmentController::class, 'download'])->name('attachments.download')->middleware('can.perm:service.view,jobcard.view,estimates.view');
    Route::post('/media', [MediaAttachmentController::class, 'store'])->name('media.store')->middleware('can.perm:media.upload');
    Route::delete('/media/{media}', [MediaAttachmentController::class, 'destroy'])->name('media.destroy')->middleware('can.perm:media.upload,media.delete');

    // --- Reports ---
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('can.perm:report.view');
    Route::get('/reports/service', [ReportController::class, 'serviceReport'])->name('reports.service')->middleware('can.perm:report.view');
    Route::get('/reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales')->middleware('can.perm:report.view');
    Route::get('/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock')->middleware('can.perm:report.view');
    Route::get('/reports/financial', [ReportController::class, 'financialReport'])->name('reports.financial')->middleware('can.perm:report.view');
    Route::get('/reports/technician', [ReportController::class, 'technicianPerformance'])->name('reports.technician')->middleware('can.perm:report.view');
    Route::get('/reports/customer-lifetime', [ReportController::class, 'customerLifetime'])->name('reports.customer-lifetime')->middleware('can.perm:report.view');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf')->middleware('can.perm:report.export');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel')->middleware('can.perm:report.export');
    Route::get('/reports/service/{service}/pdf', [ReportController::class, 'serviceReportPdf'])->name('reports.service-pdf')->middleware('can.perm:report.view');
    Route::get('/reports/ar-aging', [ReportController::class, 'arAging'])->name('reports.ar-aging')->middleware('can.perm:report.view');
    Route::get('/reports/work-time', [ReportController::class, 'workTime'])->name('reports.work-time')->middleware('can.perm:report.view');
    Route::get('/reports/parts-usage', [ReportController::class, 'partsUsage'])->name('reports.parts-usage')->middleware('can.perm:report.view');
    Route::get('/reports/branch-comparison', [ReportController::class, 'branchComparison'])->name('reports.branch-comparison')->middleware('can.perm:report.view');
    Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow')->middleware('can.perm:report.view');
    Route::get('/reports/general-ledger', [ReportController::class, 'generalLedger'])->name('reports.general-ledger')->middleware('can.perm:report.view');
    Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance')->middleware('can.perm:report.view');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss')->middleware('can.perm:report.view');
    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet')->middleware('can.perm:report.view');

    // --- Settings ---
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('can.perm:settings.view');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('can.perm:settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('can.perm:settings.edit');
    Route::get('/settings/backup', [SettingsController::class, 'backupPage'])->name('settings.backup-page')->middleware('can.perm:settings.edit');
    Route::post('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup')->middleware('can.perm:settings.edit');
    Route::get('/settings/backup/download', [SettingsController::class, 'backupDownload'])->name('settings.backup-download')->middleware('can.perm:backup.download');
    Route::post('/settings/cache-clear', [SettingsController::class, 'cacheClear'])->name('settings.cache-clear')->middleware('can.perm:settings.edit');
    Route::post('/settings/optimize', [SettingsController::class, 'optimize'])->name('settings.optimize')->middleware('can.perm:settings.edit');

    // --- Printer, Numbering & Bank Accounts ---
    Route::resource('printers', PrinterController::class)->except(['show'])->middleware('can.perm:settings.edit');
    Route::post('/printers/{printer}/test', [PrinterController::class, 'test'])->name('printers.test')->middleware('can.perm:settings.edit');
    Route::resource('invoice-schemes', InvoiceSchemeController::class)->except(['show'])->middleware('can.perm:settings.edit');
    Route::resource('bank-accounts', BankAccountController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('bank-reconciliations', BankReconciliationController::class)->only(['index', 'create', 'store', 'show'])->middleware('can.module:master-data');
    Route::resource('budgets', BudgetController::class)->except(['show'])->middleware('can.module:master-data');

    // --- Jobcards (non-resource) ---
    Route::get('/jobcards', [JobcardController::class, 'index'])->name('jobcards.index')->middleware('can.perm:jobcard.view');
    Route::get('/jobcards/{service}', [JobcardController::class, 'show'])->name('jobcards.show')->middleware('can.perm:jobcard.view');
    Route::post('/jobcards/{service}', [JobcardController::class, 'store'])->name('jobcards.store')->middleware('can.perm:service.edit');
    Route::put('/jobcards/{service}', [JobcardController::class, 'update'])->name('jobcards.update')->middleware('can.perm:service.edit');
    Route::get('/jobcards/{service}/print', [JobcardController::class, 'print'])->name('jobcards.print')->middleware('can.perm:jobcard.print');
    Route::get('/jobcards/{service}/gate-pass', [JobcardController::class, 'gatePass'])->name('jobcards.gate-pass')->middleware('can.perm:gate-pass.create');

    // --- Payments (nested under invoices) ---
    Route::get('/invoices/{invoice}/payments/create', [PaymentController::class, 'create'])->name('payments.create')->middleware('can.perm:payment.process');
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store')->middleware('can.perm:payment.process');
    Route::get('/invoices/{invoice}/payments/history', [PaymentController::class, 'history'])->name('payments.history')->middleware('can.perm:invoice.view');
    Route::get('/invoices/{invoice}/payment-proof', [InvoiceController::class, 'paymentProof'])->name('invoices.payment-proof')->middleware('can.perm:invoice.pdf');

    // --- Observations ---
    Route::get('/observations/{service}/checklist', [ObservationController::class, 'checklist'])->name('observations.checklist')->middleware('can.perm:service.view,jobcard.view');
    Route::get('/observations/{service}/checklist/print', [ObservationController::class, 'printChecklist'])->name('observations.checklist.print')->middleware('can.perm:service.view,jobcard.view');
    Route::post('/observations/{service}/save-checklist', [ObservationController::class, 'saveChecklist'])->name('observations.save-checklist')->middleware('can.perm:service.edit,findings.create');
    Route::get('/observations-by-type/{type}', [ObservationController::class, 'getByType'])->name('observations.by-type')->middleware('can.perm:service.view,jobcard.view');

    // --- Checkouts ---
    Route::get('/checkouts/{service}', [CheckoutController::class, 'index'])->name('checkouts.index')->middleware('can.perm:service.edit');
    Route::post('/checkouts/{service}', [CheckoutController::class, 'store'])->name('checkouts.store')->middleware('can.perm:service.edit');

    // --- Services custom routes (before resource to avoid conflicts) ---
    Route::post('/services/{service}/complete', [ServiceController::class, 'complete'])->name('services.complete')->middleware('can.perm:service.complete');
    Route::post('/services/{service}/start', [ServiceController::class, 'start'])->name('services.start')->middleware('can.perm:service.edit');
    Route::post('/services/{service}/advance', [ServiceController::class, 'advance'])->name('services.advance')->middleware('can.perm:service.edit');
    Route::post('/services/{service}/upload-image', [ServiceController::class, 'uploadImage'])->name('services.upload-image')->middleware('can.perm:media.upload');
    Route::get('/services/customers/search', [ServiceController::class, 'searchCustomers'])->name('services.customers.search')->middleware('can.perm:service.view');
    Route::get('/services/vehicles-by-customer/{customer}', [ServiceController::class, 'vehiclesByCustomer'])->name('services.vehicles-by-customer')->middleware('can.perm:service.view');
    Route::get('/services/history', [ServiceController::class, 'history'])->name('services.history')->middleware('can.perm:service.view');
    Route::post('/services/{service}/survey-link', [ServiceController::class, 'surveyLink'])->name('services.survey-link')->middleware('can.perm:service.view');
    Route::get('/services/{service}/sticker', [ServiceController::class, 'printNextServiceSticker'])->name('services.sticker')->middleware('can.perm:service.view');
    Route::get('/services/{service}/condition-report', [ServiceController::class, 'printConditionReport'])->name('services.condition-report')->middleware('can.perm:service.view');
    Route::get('/services/{service}/send-wa', [ServiceController::class, 'sendWA'])->name('services.sendWA')->middleware('can.perm:service.view');

    // --- Service Estimates (quotation documents) ---
    Route::get('/estimates', [EstimateController::class, 'index'])->name('estimates.index')->middleware('can.perm:estimates.view');
    Route::get('/estimates/create', [EstimateController::class, 'create'])->name('estimates.create')->middleware('can.perm:estimates.create');
    Route::post('/estimates/direct', [EstimateController::class, 'storeDirect'])->name('estimates.direct.store')->middleware('can.perm:estimates.create');
    Route::get('/estimates/service-search', [EstimateController::class, 'searchServices'])->name('estimates.service-search')->middleware('can.perm:estimates.view');
    Route::get('/estimates/catalog/products', [EstimateController::class, 'catalogProducts'])->name('estimates.catalog.products')->middleware('can.perm:estimates.view');
    Route::get('/estimates/catalog/services', [EstimateController::class, 'catalogServices'])->name('estimates.catalog.services')->middleware('can.perm:estimates.view');
    Route::get('/estimates/service-preview/{service}', [EstimateController::class, 'servicePreview'])->name('estimates.service-preview')->middleware('can.perm:estimates.view');
    Route::post('/services/{service}/estimates', [EstimateController::class, 'store'])->name('services.estimates.store')->middleware('can.perm:estimates.create');
    Route::post('/services/{service}/estimates/from-findings', [EstimateController::class, 'addWorkPackagesFromFindings'])->name('services.estimates.from-findings')->middleware('can.perm:estimates.create');
    Route::get('/estimates/{estimate}/pdf', [EstimateController::class, 'pdf'])->name('estimates.pdf')->middleware('can.perm:estimates.view');
    Route::get('/estimates/{estimate}/preview', [EstimateController::class, 'preview'])->name('estimates.preview')->middleware('can.perm:estimates.view');
    Route::get('/estimates/{estimate}/print', [EstimateController::class, 'print'])->name('estimates.print')->middleware('can.perm:estimates.view');
    Route::post('/estimates/{estimate}/send-wa', [EstimateController::class, 'sendWA'])->name('estimates.send-wa')->middleware('can.perm:estimates.send');
    Route::post('/estimates/{estimate}/send-email', [EstimateController::class, 'sendEmail'])->name('estimates.send-email')->middleware('can.perm:estimates.send');
    Route::post('/estimates/{estimate}/revise', [EstimateController::class, 'revise'])->name('estimates.revise')->middleware('can.perm:estimates.revise');
    Route::post('/estimates/{estimate}/override-approve', [EstimateController::class, 'overrideApprove'])->name('estimates.override-approve')->middleware('can.perm:estimates.override');
    Route::post('/estimates/{estimate}/convert-invoice', [EstimateController::class, 'convertToInvoice'])->name('estimates.convert-invoice')->middleware('can.perm:estimates.convert_invoice');
    Route::put('/estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update')->middleware('can.perm:estimates.update');

    // --- Workshop flow: findings / work packages / tasks / QC ---
    Route::get('/services/{service}/qc', [WorkQcController::class, 'show'])->name('work-packages.qc')->middleware('can.perm:qc.view');
    Route::post('/work-packages/{package}/qc', [WorkQcController::class, 'store'])->name('work-packages.qc.store')->middleware('can.perm:qc.perform');
    Route::put('/work-packages/{package}', [WorkPackageController::class, 'update'])->name('work-packages.update')->middleware('can.perm:work-packages.update');
    Route::delete('/work-packages/{package}', [WorkPackageController::class, 'destroy'])->name('work-packages.destroy')->middleware('can.perm:work-packages.update');
    Route::post('/services/{service}/work-packages', [WorkPackageController::class, 'store'])->name('services.work-packages.store')->middleware('can.perm:work-packages.create');
    Route::post('/services/{service}/findings/defer/{finding}', [FindingController::class, 'defer'])->name('findings.defer')->middleware('can.perm:findings.update');
    Route::post('/findings/{finding}/resolve', [FindingController::class, 'resolve'])->name('findings.resolve')->middleware('can.perm:findings.resolve');
    Route::put('/findings/{finding}', [FindingController::class, 'update'])->name('findings.update')->middleware('can.perm:findings.update');
    Route::get('/work-tasks/{task}', [WorkTaskController::class, 'show'])->name('work-tasks.show')->middleware('can.perm:work-tasks.view');
    Route::post('/work-tasks/{task}/start', [WorkTaskController::class, 'start'])->name('work-tasks.start')->middleware('can.perm:work-tasks.start');
    Route::post('/work-tasks/{task}/pause', [WorkTaskController::class, 'pause'])->name('work-tasks.pause')->middleware('can.perm:work-tasks.start');
    Route::post('/work-tasks/{task}/finish', [WorkTaskController::class, 'finish'])->name('work-tasks.finish')->middleware('can.perm:work-tasks.finish');
    Route::post('/work-tasks/{task}/assign', [WorkTaskController::class, 'assign'])->name('work-tasks.assign')->middleware('can.perm:work-packages.update');

    // --- Parts Reservation ---
    Route::post('/services/{service}/reservations', [PartReservationController::class, 'store'])->name('services.reservations.store')->middleware('can.perm:service.edit');
    Route::post('/services/reservations/{reservation}/release', [PartReservationController::class, 'release'])->name('services.reservations.release')->middleware('can.perm:service.edit');
    Route::post('/services/reservations/{reservation}/consume', [PartReservationController::class, 'consume'])->name('services.reservations.consume')->middleware('can.perm:service.edit');

    // --- Vehicles custom routes (before resource) ---
    Route::post('/vehicles/{vehicle}/upload-image', [VehicleController::class, 'uploadImage'])->name('vehicles.upload-image')->middleware('can.perm:media.upload');
    Route::delete('/vehicles/images/{image}', [VehicleController::class, 'deleteImage'])->name('vehicles.delete-image')->middleware('can.perm:media.delete');
    Route::get('/vehicles/{vehicle}/history', [VehicleController::class, 'serviceHistory'])->name('vehicles.history')->middleware('can.perm:vehicle.view');

    // --- Invoices custom routes (before resource) ---
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf')->middleware('can.perm:invoice.pdf');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview')->middleware('can.perm:invoice.pdf');
    Route::get('/invoices/{invoice}/send-wa', [InvoiceController::class, 'sendWA'])->name('invoices.sendWA')->middleware('can.perm:invoice.send-wa');
    Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.sendEmail')->middleware('can.perm:invoice.send-email');
    Route::post('/invoices/{invoice}/share', [InvoiceController::class, 'share'])->name('invoices.share')->middleware('can.perm:invoice.view');
    Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void')->middleware('can.perm:invoice.void');

    // --- Thermal Print ---
    Route::post('/invoices/{invoice}/print', [PrintController::class, 'invoice'])->name('print.invoice')->middleware('can.perm:invoice.pdf');
    Route::post('/services/{service}/print-jobcard', [PrintController::class, 'jobcard'])->name('print.jobcard')->middleware('can.perm:jobcard.print');
    Route::post('/pos/open-drawer', [PrintController::class, 'openDrawer'])->name('print.open-drawer')->middleware('can.perm:pos.view');
    Route::get('/print/invoice/{invoice}/raw', [PrintController::class, 'rawData'])->name('print.raw')->middleware('can.perm:invoice.pdf');

    // --- Products custom routes (before resource) ---
    Route::get('/products/reorder', [ProductController::class, 'reorderSuggestions'])->name('products.reorder')->middleware('can.perm:product.view');
    Route::post('/products/reorder/create-po', [ProductController::class, 'createReorderPo'])->name('products.reorder.po')->middleware('can.perm:purchase-order.create');
    Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import-form')->middleware('can.perm:product.import');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import')->middleware('can.perm:product.import');
    Route::match(['get', 'post'], '/products/stock-opname', [ProductController::class, 'stockOpname'])->name('products.stock-opname')->middleware('can.perm:product.stock-opname');
    Route::get('/products/search-json', [ProductController::class, 'searchJson'])->name('products.search-json')->middleware('can.perm:product.view');
    Route::match(['get', 'post'], '/products/{product}/stock-adjust', [ProductController::class, 'stockAdjust'])->name('products.stock-adjust')->middleware('can.perm:product.stock-adjust');
    Route::get('/products/{product}/barcode', [ProductController::class, 'printBarcode'])->name('products.barcode')->middleware('can.perm:product.view');
    Route::get('/products/{product}/variations', [ProductVariationController::class, 'index'])->name('products.variations.index')->middleware('can.perm:product.view');
    Route::post('/products/{product}/variations', [ProductVariationController::class, 'store'])->name('products.variations.store')->middleware('can.perm:product.create');
    Route::put('/products/{product}/variations/{variation}', [ProductVariationController::class, 'update'])->name('products.variations.update')->middleware('can.perm:product.edit');
    Route::delete('/products/{product}/variations/{variation}', [ProductVariationController::class, 'destroy'])->name('products.variations.destroy')->middleware('can.perm:product.delete');

    // --- Stock Adjustments (approval flow) ---
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index')->middleware('can.perm:inventory.adjust');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create')->middleware('can.perm:inventory.adjust');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store')->middleware('can.perm:inventory.adjust');
    // Approval is separated from request creation (segregation of duties).
    Route::post('/stock-adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve')->middleware('can.perm:stock-adjustment.approve');
    Route::post('/stock-adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('stock-adjustments.reject')->middleware('can.perm:stock-adjustment.approve');

    // --- Purchases custom routes (before resource) ---
    Route::post('/purchases/{purchase}/mark-received', [PurchaseController::class, 'markReceived'])->name('purchases.mark-received')->middleware('can.perm:purchase.receive');
    Route::get('/purchases/return', [PurchaseReturnController::class, 'index'])->name('purchases.return.index')->middleware('can.perm:purchase.view');
    Route::get('/purchases/{purchase}/return', [PurchaseReturnController::class, 'create'])->name('purchases.return.create')->middleware('can.perm:purchase.edit');
    Route::post('/purchases/{purchase}/return', [PurchaseReturnController::class, 'store'])->name('purchases.return.store')->middleware('can.perm:purchase.edit');

    // Sales Orders merged into POS — redirect old URLs
    Route::redirect('/sales-orders', '/pos/terminal');

    // --- Purchase Orders custom routes (before resource) ---
    Route::post('/purchase-orders/{purchaseOrder}/mark-received', [PurchaseOrderController::class, 'markReceived'])->name('purchase-orders.mark-received')->middleware('can.perm:purchase-order.receive');
    Route::post('/purchase-orders/{purchaseOrder}/{action}', [PurchaseOrderController::class, 'transition'])
        ->whereIn('action', ['submit', 'approve', 'close'])->name('purchase-orders.transition')
        ->middleware('can.perm:purchase-order.receive');

    // --- Purchase Requisitions custom routes (before resource) ---
    Route::post('/purchase-requisitions/{purchaseRequisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('purchase-requisitions.submit')->middleware('can.perm:purchase-requisition.submit');
    Route::post('/purchase-requisitions/{purchaseRequisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('purchase-requisitions.approve')->middleware('can.perm:purchase-requisition.approve');
    Route::post('/purchase-requisitions/{purchaseRequisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('purchase-requisitions.reject')->middleware('can.perm:purchase-requisition.approve');
    Route::post('/purchase-requisitions/{purchaseRequisition}/convert', [PurchaseRequisitionController::class, 'convertToPurchaseOrder'])->name('purchase-requisitions.convert')->middleware('can.perm:purchase-requisition.approve');

    // --- Gate Passes custom routes (before resource) ---
    Route::get('/gate-passes/{gate_pass}/print', [GatePassController::class, 'print'])->name('gate-passes.print')->middleware('can.perm:gate-pass.print');
    Route::post('/gate-passes/{gate_pass}/mark-exit', [GatePassController::class, 'markExit'])->name('gate-passes.mark-exit')->middleware('can.perm:gate-pass.edit');

    // --- Reminders custom routes (before resource) ---
    Route::post('/reminders/{reminder}/send', [ReminderController::class, 'send'])->name('reminders.send')->middleware('can.perm:reminder.send');
    Route::post('/reminders/send-scheduled', [ReminderController::class, 'sendScheduled'])->name('reminders.send-scheduled')->middleware('can.perm:reminder.send');

    // --- Notification Templates custom routes (before resource) ---
    Route::get('/notification-templates/{template}/preview', [NotificationTemplateController::class, 'preview'])->name('notification-templates.preview')->middleware('can.perm:notification-template.view');

    // --- Customers custom routes (before resource) ---
    Route::get('/customers/import', [CustomerController::class, 'importForm'])->name('customers.import-form')->middleware('can.perm:customer.import');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import')->middleware('can.perm:customer.import');

    // --- Resource routes ---
    Route::resource('customers', CustomerController::class)->middleware('can.module:customer');
    Route::resource('vehicles', VehicleController::class)->middleware('can.module:vehicle');
    Route::resource('services', ServiceController::class)->middleware('can.module:service');
    Route::resource('invoices', InvoiceController::class)->middleware('can.module:invoice');
    Route::resource('products', ProductController::class)->middleware('can.module:product');
    Route::resource('purchases', PurchaseController::class)->middleware('can.module:purchase');
    Route::resource('purchase-orders', PurchaseOrderController::class)->middleware('can.module:purchase-order');
    Route::resource('purchase-requisitions', PurchaseRequisitionController::class)->middleware('can.module:purchase-requisition');
    Route::resource('sell-returns', SellReturnController::class)->only(['index', 'create', 'store', 'show'])->middleware('can.module:sale');
    // Sales merged into POS — redirect old URLs to POS terminal
    Route::redirect('/sales', '/pos/terminal')->name('sales.index');
    Route::redirect('/sales/create', '/pos/terminal');
    Route::resource('suppliers', SupplierController::class)->middleware('can.module:supplier');
    Route::resource('incomes', IncomeController::class)->middleware('can.module:income');
    Route::resource('expenses', ExpenseController::class)->middleware('can.module:expense');
    Route::resource('gate-passes', GatePassController::class)->middleware('can.module:gate-pass');
    Route::resource('reminders', ReminderController::class)->middleware('can.module:reminder');
    Route::resource('notification-templates', NotificationTemplateController::class)->middleware('can.module:notification-template');

    // --- Master data resources (no show method) ---
    Route::resource('observation-types', ObservationTypeController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('repair-categories', RepairCategoryController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('tax-rates', TaxRateController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('tax-groups', TaxGroupController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('payment-methods', PaymentMethodController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('product-units', ProductUnitController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('product-types', ProductTypeController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('colors', ColorController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('fuel-types', FuelTypeController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('vehicle-brands', VehicleBrandController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('vehicle-types', VehicleTypeController::class)->except(['show'])->middleware('can.module:master-data');

    // --- Perusahaan / Multi-company ---
    Route::resource('companies', CompanyController::class)->middleware('can.module:company');

    // --- Cabang / Multi-branch ---
    Route::post('/branches/switch', [BranchController::class, 'switchBranch'])->name('branches.switch');
    Route::resource('branches', BranchController::class)->middleware('can.module:branch');
    Route::resource('business-hours', BusinessHourController::class)->except(['index', 'show'])->middleware('can.module:branch');
    Route::resource('holidays', HolidayController::class)->except(['show'])->middleware('can.module:holiday');
    Route::post('/washbays/{washbay}/occupy', [WashbayController::class, 'occupy'])->name('washbays.occupy')->middleware('can.perm:service.edit');
    Route::post('/washbays/{washbay}/release', [WashbayController::class, 'release'])->name('washbays.release')->middleware('can.perm:service.edit');
    Route::resource('washbays', WashbayController::class)->except(['show'])->middleware('can.module:washbay');

    // --- Master data lanjutan ---
    Route::resource('observation-points', ObservationPointController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('inspection-points', InspectionPointController::class)->except(['show'])->middleware('can.module:master-data');
    Route::resource('checkout-categories', CheckoutCategoryController::class)->except(['show'])->middleware('can.module:master-data');

    // --- Geographic & Currency ---
    Route::resource('currencies', CurrencyController::class)->except(['show'])->middleware('can.module:currency');
    Route::resource('countries', CountryController::class)->except(['show'])->middleware('can.module:country');
    Route::resource('states', StateController::class)->except(['show'])->middleware('can.module:state');
    Route::resource('cities', CityController::class)->except(['show'])->middleware('can.module:city');

    // --- Extensibility ---
    Route::resource('custom-fields', CustomFieldController::class)->except(['show'])->middleware('can.module:custom-field');

    // --- Notes (polymorphic catatan internal) ---
    Route::get('/notes', [NoteController::class, 'index'])->name('notes.index')->middleware('can.perm:note.view');
    Route::get('/notes/create', [NoteController::class, 'create'])->name('notes.create')->middleware('can.perm:note.create');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store')->middleware('can.perm:note.create');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy')->middleware('can.perm:note.delete');

    // --- Booking Customer (admin) ---
    Route::get('/bookings/calendar', [BookingController::class, 'calendar'])->name('bookings.calendar')->middleware('can.perm:booking.view');
    Route::get('/bookings/calendar/events', [BookingController::class, 'calendarEvents'])->name('bookings.calendar.events')->middleware('can.perm:booking.view');
    Route::get('/bookings', [BookingController::class, 'adminIndex'])->name('bookings.index')->middleware('can.perm:booking.view');
    Route::put('/bookings/{booking}', [BookingController::class, 'adminUpdate'])->name('bookings.update')->middleware('can.perm:booking.edit');
    Route::delete('/bookings/{booking}', [BookingController::class, 'adminDestroy'])->name('bookings.destroy')->middleware('can.perm:booking.delete');
    Route::post('/bookings/{booking}/convert', [BookingController::class, 'convertToService'])->name('bookings.convert')->middleware('can.perm:booking.edit');

    // --- Marketing: Voucher & Loyalty ---
    Route::resource('vouchers', VoucherController::class)->except(['show'])->middleware('can.module:voucher');
    Route::post('/vouchers-validate', [VoucherController::class, 'validateCode'])->name('vouchers.validate')->middleware('can.perm:pos.view,estimates.create');
    Route::get('/marketing/campaign', [CampaignController::class, 'index'])->name('marketing.campaign')->middleware('can.perm:campaign.view');
    Route::post('/marketing/campaign', [CampaignController::class, 'send'])->name('marketing.campaign.send')->middleware('can.perm:campaign.send');
    Route::get('/marketing/campaign/search', [CampaignController::class, 'searchCustomers'])->name('marketing.campaign.search')->middleware('can.perm:campaign.view');

    Route::get('/loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index')->middleware('can.perm:loyalty.view');
    Route::get('/loyalty/{customer}', [LoyaltyController::class, 'show'])->name('loyalty.show')->middleware('can.perm:loyalty.view');
    Route::post('/loyalty/{customer}/adjust', [LoyaltyController::class, 'adjust'])->name('loyalty.adjust')->middleware('can.perm:loyalty.adjust');

    // --- HRM: Komisi Teknisi ---
    Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index')->middleware('can.perm:commission.view');
    Route::get('/commissions/report', [CommissionController::class, 'report'])->name('commissions.report')->middleware('can.perm:commission.report');
    Route::put('/commissions/{serviceTechnician}/mark-paid', [CommissionController::class, 'markPaid'])->name('commissions.markPaid')->middleware('can.perm:commission.mark_paid');
    Route::post('/commissions/mark-paid-batch', [CommissionController::class, 'markPaidBatch'])->name('commissions.markPaidBatch')->middleware('can.perm:commission.mark_paid');

    // --- HRM: Timer kerja teknisi (start/finish per job) ---
    Route::post('/service-technicians/{serviceTechnician}/start', [CommissionController::class, 'startJob'])->name('service-technicians.start')->middleware('can.perm:work-tasks.start');
    Route::post('/service-technicians/{serviceTechnician}/finish', [CommissionController::class, 'finishJob'])->name('service-technicians.finish')->middleware('can.perm:work-tasks.finish');

    // --- HRM: Attendance & Salary ---
    Route::get('/hrm/attendance', [HrmController::class, 'attendanceIndex'])->name('hrm.attendance')->middleware('can.perm:commission.view');
    Route::post('/hrm/clock-in', [HrmController::class, 'clockIn'])->name('hrm.clock-in');
    Route::post('/hrm/clock-out', [HrmController::class, 'clockOut'])->name('hrm.clock-out');
    Route::get('/hrm/salary', [HrmController::class, 'salaryIndex'])->name('hrm.salary')->middleware('can.perm:commission.view');
    Route::post('/hrm/salary/generate', [HrmController::class, 'salaryGenerate'])->name('hrm.salary.generate')->middleware('can.perm:commission.mark_paid');
    Route::get('/hrm/salary/{salary}/slip', [HrmController::class, 'salarySlip'])->name('hrm.salary.slip')->middleware('can.perm:commission.view');
    Route::put('/hrm/salary/{salary}/mark-paid', [HrmController::class, 'salaryMarkPaid'])->name('hrm.salary.mark-paid')->middleware('can.perm:commission.mark_paid');

    // --- HRM: Skill Matrix Teknisi ---
    Route::get('/technician-skills', [TechnicianSkillController::class, 'index'])->name('technician-skills.index')->middleware('can.perm:commission.view');
    Route::post('/technician-skills', [TechnicianSkillController::class, 'store'])->name('technician-skills.store')->middleware('can.perm:commission.edit');
    Route::put('/technician-skills/{skill}', [TechnicianSkillController::class, 'update'])->name('technician-skills.update')->middleware('can.perm:commission.edit');
    Route::delete('/technician-skills/{skill}', [TechnicianSkillController::class, 'destroy'])->name('technician-skills.destroy')->middleware('can.perm:commission.edit');

    // --- HRM: Leaves / Cuti ---
    Route::get('/hrm/leaves', [LeaveController::class, 'index'])->name('hrm.leaves.index')->middleware('can.perm:commission.view');
    Route::post('/hrm/leaves', [LeaveController::class, 'store'])->name('hrm.leaves.store')->middleware('can.perm:commission.view');
    Route::post('/hrm/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('hrm.leaves.approve')->middleware('can.perm:commission.mark_paid');
    Route::post('/hrm/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('hrm.leaves.reject')->middleware('can.perm:commission.mark_paid');
    Route::delete('/hrm/leaves/{leave}', [LeaveController::class, 'destroy'])->name('hrm.leaves.destroy')->middleware('can.perm:commission.view');

    // --- POS Kasir (Retail Module) ---
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'terminal'])->name('terminal')->middleware('can.perm:pos.view');
        Route::get('/sessions', [PosController::class, 'sessions'])->name('sessions')->middleware('can.perm:pos.view');
        Route::get('/open', [PosController::class, 'openForm'])->name('openForm')->middleware('can.perm:pos.view');
        Route::post('/open', [PosController::class, 'open'])->name('open')->middleware('can.perm:pos.open');
        Route::get('/sessions/{session}/close', [PosController::class, 'closeForm'])->name('closeForm')->middleware('can.perm:pos.close');
        Route::put('/sessions/{session}/close', [PosController::class, 'close'])->name('close')->middleware('can.perm:pos.close');
        Route::get('/search-product', [PosController::class, 'searchProduct'])->name('search-product')->middleware('can.perm:pos.view');
        Route::get('/prices', [PosController::class, 'prices'])->name('prices')->middleware('can.perm:pos.view');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout')->middleware('can.perm:pos.checkout');
        Route::get('/receipt/{invoice}', [PosController::class, 'receipt'])->name('receipt')->middleware('can.perm:pos.view');
        Route::post('/hold', [PosController::class, 'hold'])->name('hold')->middleware('can.perm:pos.view');
        Route::get('/held', [PosController::class, 'heldList'])->name('held')->middleware('can.perm:pos.view');
        Route::get('/held/{held}', [PosController::class, 'recall'])->name('recall')->middleware('can.perm:pos.view');
        Route::delete('/held/{held}', [PosController::class, 'releaseHeld'])->name('release')->middleware('can.perm:pos.view');
    });

    // --- Review & Warranty ---
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index')->middleware('can.perm:review.view');
    Route::put('/reviews/{review}/publish', [ReviewController::class, 'publish'])->name('reviews.publish')->middleware('can.perm:review.edit');
    Route::put('/reviews/{review}/unpublish', [ReviewController::class, 'unpublish'])->name('reviews.unpublish')->middleware('can.perm:review.edit');
    Route::put('/reviews/{review}/reply', [ReviewController::class, 'reply'])->name('reviews.reply')->middleware('can.perm:review.edit');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy')->middleware('can.perm:review.delete');

    Route::resource('recalls', RecallController::class)->except(['show'])->middleware('can.module:warranty');

    Route::get('/warranty-claims', [WarrantyClaimController::class, 'index'])->name('warranty-claims.index')->middleware('can.perm:warranty.view');
    Route::get('/warranty-claims/create', [WarrantyClaimController::class, 'create'])->name('warranty-claims.create')->middleware('can.perm:warranty.create');
    Route::post('/warranty-claims', [WarrantyClaimController::class, 'store'])->name('warranty-claims.store')->middleware('can.perm:warranty.create');
    Route::get('/warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'show'])->name('warranty-claims.show')->middleware('can.perm:warranty.view');
    Route::get('/warranty-claims/{warrantyClaim}/edit', [WarrantyClaimController::class, 'edit'])->name('warranty-claims.edit')->middleware('can.perm:warranty.edit');
    Route::put('/warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'update'])->name('warranty-claims.update')->middleware('can.perm:warranty.edit');
    Route::delete('/warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'destroy'])->name('warranty-claims.destroy')->middleware('can.perm:warranty.delete');

    // --- Insurance Claims (Klaim Asuransi) ---
    Route::get('/insurance-claims', [InsuranceClaimController::class, 'index'])->name('insurance-claims.index')->middleware('can.perm:insurance-claim.view');
    Route::get('/insurance-claims/create', [InsuranceClaimController::class, 'create'])->name('insurance-claims.create')->middleware('can.perm:insurance-claim.create');
    Route::post('/insurance-claims', [InsuranceClaimController::class, 'store'])->name('insurance-claims.store')->middleware('can.perm:insurance-claim.create');
    Route::get('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'show'])->name('insurance-claims.show')->middleware('can.perm:insurance-claim.view');
    Route::put('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'update'])->name('insurance-claims.update')->middleware('can.perm:insurance-claim.edit');
    Route::post('/insurance-claims/{insuranceClaim}/approve', [InsuranceClaimController::class, 'approve'])->name('insurance-claims.approve')->middleware('can.perm:insurance-claim.edit');
    Route::post('/insurance-claims/{insuranceClaim}/reject', [InsuranceClaimController::class, 'reject'])->name('insurance-claims.reject')->middleware('can.perm:insurance-claim.edit');
    Route::post('/insurance-claims/{insuranceClaim}/mark-paid', [InsuranceClaimController::class, 'markPaid'])->name('insurance-claims.mark-paid')->middleware('can.perm:insurance-claim.edit');
    Route::delete('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'destroy'])->name('insurance-claims.destroy')->middleware('can.perm:insurance-claim.delete');

    // --- Supplier Claims (Klaim garansi ke supplier) ---
    Route::get('/supplier-claims', [SupplierClaimController::class, 'index'])->name('supplier-claims.index')->middleware('can.perm:warranty.view');
    Route::get('/supplier-claims/create', [SupplierClaimController::class, 'create'])->name('supplier-claims.create')->middleware('can.perm:warranty.create');
    Route::post('/supplier-claims', [SupplierClaimController::class, 'store'])->name('supplier-claims.store')->middleware('can.perm:warranty.create');
    Route::get('/supplier-claims/{supplierClaim}', [SupplierClaimController::class, 'show'])->name('supplier-claims.show')->middleware('can.perm:warranty.view');
    Route::post('/supplier-claims/{supplierClaim}/approve', [SupplierClaimController::class, 'approve'])->name('supplier-claims.approve')->middleware('can.perm:warranty.edit');
    Route::post('/supplier-claims/{supplierClaim}/reject', [SupplierClaimController::class, 'reject'])->name('supplier-claims.reject')->middleware('can.perm:warranty.edit');
    Route::post('/supplier-claims/{supplierClaim}/mark-paid', [SupplierClaimController::class, 'markPaid'])->name('supplier-claims.mark-paid')->middleware('can.perm:warranty.edit');
    Route::delete('/supplier-claims/{supplierClaim}', [SupplierClaimController::class, 'destroy'])->name('supplier-claims.destroy')->middleware('can.perm:warranty.delete');

    // --- Fleet Contracts (Kontrak Fleet) ---
    Route::get('/fleet-contracts/due', [FleetContractController::class, 'dueVehicles'])->name('fleet-contracts.due')->middleware('can.perm:fleet-contract.view');
    Route::resource('fleet-contracts', FleetContractController::class)->middleware('can.module:fleet-contract');

    // --- Payment Gateway (generic adapter, configurable) ---
    Route::resource('payment-gateways', PaymentGatewayController::class)->except(['show'])->middleware('can.perm:payment-gateway.view');
    Route::post('/invoices/{invoice}/generate-payment-link', [PaymentGatewayController::class, 'generateLink'])->name('invoices.generate-payment-link')->middleware('can.perm:invoice.view');

    // --- User & Role Management ---
    Route::get('/users', [RoleController::class, 'userIndex'])->name('users.index')->middleware('can.perm:user.view');
    Route::post('/users', [RoleController::class, 'userStore'])->name('users.store')->middleware('can.perm:user.create');
    Route::put('/users/{user}', [RoleController::class, 'userUpdate'])->name('users.update')->middleware('can.perm:user.edit');
    Route::delete('/users/{user}', [RoleController::class, 'userDestroy'])->name('users.destroy')->middleware('can.perm:user.delete');
    Route::get('/users/api-tokens', [RoleController::class, 'apiTokens'])->name('users.api-tokens')->middleware('can.perm:api-token.view');
    Route::post('/users/api-tokens', [RoleController::class, 'createToken'])->name('users.create-token')->middleware('can.perm:api-token.create');
    Route::delete('/admin/api-tokens/{token}', [RoleController::class, 'revokeToken'])->name('admin.revoke-token')->middleware('can.perm:api-token.revoke');
    Route::get('/roles', [RoleController::class, 'roleIndex'])->name('roles.index')->middleware('can.perm:role.view');
    Route::post('/roles', [RoleController::class, 'roleStore'])->name('roles.store')->middleware('can.perm:role.create');
    Route::put('/roles/{role}', [RoleController::class, 'roleUpdate'])->name('roles.update')->middleware('can.perm:role.edit');
    Route::delete('/roles/{role}', [RoleController::class, 'roleDestroy'])->name('roles.destroy')->middleware('can.perm:role.delete');

    // --- Activity Log + Petty Cash + 2FA ---
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index')->middleware('can.perm:activity-log.view');
    Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty-cash.index')->middleware('can.perm:petty-cash.view');
    Route::post('/petty-cash', [PettyCashController::class, 'store'])->name('petty-cash.store')->middleware('can.perm:petty-cash.create');
    Route::delete('/petty-cash/{pettyCash}', [PettyCashController::class, 'destroy'])->name('petty-cash.destroy')->middleware('can.perm:petty-cash.delete');

    Route::get('/2fa/enable', [TwoFactorController::class, 'enableForm'])->name('2fa.enable.form');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    // --- Audit Trail ---
    Route::get('/stock-histories', [StockHistoryController::class, 'index'])->name('stock-histories.index')->middleware('can.perm:stock-history.view');
    Route::get('/email-logs', [EmailLogController::class, 'index'])->name('email-logs.index')->middleware('can.perm:email-log.view');
    Route::get('/email-logs/{emailLog}', [EmailLogController::class, 'show'])->name('email-logs.show')->middleware('can.perm:email-log.view');
    Route::delete('/email-logs/{emailLog}', [EmailLogController::class, 'destroy'])->name('email-logs.destroy')->middleware('can.perm:email-log.delete');

    // --- Equipment / Peralatan ---
    Route::resource('equipment', EquipmentController::class)->middleware('can.module:equipment');

    // --- Subcontractors ---
    Route::resource('subcontractors', SubcontractorController::class)->middleware('can.module:subcontractor');

    // --- Service Packages ---
    Route::resource('service-packages', ServicePackageController::class)->except(['show'])->middleware('can.module:service-package');
    Route::get('/service-packages/{servicePackage}/json', [ServicePackageController::class, 'getJson'])->name('service-packages.json')->middleware('can.perm:service-package.view');

    // --- Warehouse ---
    Route::resource('warehouses', WarehouseController::class)->middleware('can.module:warehouse');
    Route::get('/stock-transfers', [WarehouseController::class, 'transferIndex'])->name('warehouses.transfers')->middleware('can.perm:stock-transfer.view');
    Route::get('/stock-transfers/create', [WarehouseController::class, 'transferCreate'])->name('warehouses.transfers.create')->middleware('can.perm:stock-transfer.create');
    Route::post('/stock-transfers', [WarehouseController::class, 'transferStore'])->name('warehouses.transfers.store')->middleware('can.perm:stock-transfer.create');

    // --- Finance / Accounting ---
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/coa', [JournalController::class, 'coaIndex'])->name('coa')->middleware('can.perm:finance-coa.view');
        Route::get('/coa/create', [JournalController::class, 'coaCreate'])->name('coa.create')->middleware('can.perm:finance-coa.create');
        Route::post('/coa', [JournalController::class, 'coaStore'])->name('coa.store')->middleware('can.perm:finance-coa.create');
        Route::delete('/coa/{account}', [JournalController::class, 'coaDestroy'])->name('coa.destroy')->middleware('can.perm:finance-coa.delete');
        Route::get('/journal', [JournalController::class, 'journalIndex'])->name('journal')->middleware('can.perm:finance-journal.view');
        Route::get('/journal/create', [JournalController::class, 'journalCreate'])->name('journal.create')->middleware('can.perm:finance-journal.view');
        Route::post('/journal', [JournalController::class, 'journalStore'])->name('journal.store')->middleware('can.perm:finance-journal.create');
    });

    // --- Customer Groups ---
    Route::resource('customer-groups', CustomerGroupController::class)->middleware('can.module:customer-group');

    // --- Selling Price Groups ---
    Route::get('/selling-price-groups/{sellingPriceGroup}/prices', [SellingPriceGroupController::class, 'prices'])->name('selling-price-groups.prices')->middleware('can.perm:master-data.view');
    Route::post('/selling-price-groups/{sellingPriceGroup}/prices', [SellingPriceGroupController::class, 'setProductPrices'])->name('selling-price-groups.prices.store')->middleware('can.perm:master-data.edit');
    Route::resource('selling-price-groups', SellingPriceGroupController::class)->except(['show'])->middleware('can.module:master-data');

    // --- Blog Admin ---
    Route::prefix('blog-admin')->name('blog.admin.')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index')->middleware('can.perm:blog.view');
        Route::get('/create', [BlogController::class, 'create'])->name('create')->middleware('can.perm:blog.create');
        Route::post('/', [BlogController::class, 'store'])->name('store')->middleware('can.perm:blog.create');
        Route::get('/{post}/edit', [BlogController::class, 'edit'])->name('edit')->middleware('can.perm:blog.edit');
        Route::put('/{post}', [BlogController::class, 'update'])->name('update')->middleware('can.perm:blog.edit');
        Route::delete('/{post}', [BlogController::class, 'destroy'])->name('destroy')->middleware('can.perm:blog.delete');
        Route::get('/categories', [BlogController::class, 'categoryIndex'])->name('categories')->middleware('can.perm:blog.view');
        Route::post('/categories', [BlogController::class, 'categoryStore'])->name('categories.store')->middleware('can.perm:blog.create');
        Route::put('/categories/{category}', [BlogController::class, 'categoryUpdate'])->name('categories.update')->middleware('can.perm:blog.edit');
        Route::delete('/categories/{category}', [BlogController::class, 'categoryDestroy'])->name('categories.destroy')->middleware('can.perm:blog.delete');
    });
});

// License v3 pairing routes (must be at bottom to avoid route conflict)
require base_path('routes/pair-routes.php');

// Generic PSEO handler — menangkap semua pattern URL masif (HARUS DI PALING BAWAH)
// Exclude: admin paths, assets, and well-known routes
Route::get('/{slug}', [ProgrammaticSeoController::class, 'commercialPseo'])
    ->where('slug', '^(?!admin|api|__pair|webhooks|login|logout|docs|customer|track|booking|payment/callback|sitemap|blog-admin).*')
    ->name('seo.generic');

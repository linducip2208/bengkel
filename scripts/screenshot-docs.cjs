// scripts/screenshot-docs.cjs
// Jalankan: node scripts/screenshot-docs.cjs
// Capture semua halaman utama untuk /docs tutorial

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'admin@bengkel.test';
const PASSWORD = 'password';
const OUT_DIR = path.resolve(__dirname, '..', 'public', 'marketing', 'screens');

const PAGES = [
    { path: '/login', name: 'login', label: 'Login' },
    { path: '/', name: 'dashboard', label: 'Dashboard' },
    { path: '/branches', name: 'branch-list', label: 'Branches' },
    { path: '/bookings', name: 'booking-list', label: 'Bookings' },
    { path: '/customers', name: 'customer-list', label: 'Customers' },
    { path: '/customers/create', name: 'customer-create', label: 'Add Customer' },
    { path: '/vehicles', name: 'vehicle-list', label: 'Vehicles' },
    { path: '/vehicles/create', name: 'vehicle-create', label: 'Add Vehicle' },
    { path: '/services', name: 'service-list', label: 'Services' },
    { path: '/services/create', name: 'service-create', label: 'Create Service' },
    { path: '/services/history', name: 'service-history', label: 'Service History' },
    { path: '/jobcards', name: 'jobcard-list', label: 'Job Cards' },
    { path: '/gate-passes', name: 'gate-pass-list', label: 'Gate Passes' },
    { path: '/sales', name: 'sales-list', label: 'Sales' },
    { path: '/pos/terminal', name: 'pos-terminal', label: 'POS Terminal' },
    { path: '/pos/sessions', name: 'pos-sessions', label: 'POS Sessions' },
    { path: '/invoices', name: 'invoice-list', label: 'Invoices' },
    { path: '/products', name: 'product-list', label: 'Products' },
    { path: '/products/stock-opname', name: 'product-stock-opname', label: 'Stock Opname' },
    { path: '/purchases', name: 'purchase-list', label: 'Purchases' },
    { path: '/purchases/create', name: 'purchase-create', label: 'Create Purchase' },
    { path: '/purchases/return', name: 'purchase-return', label: 'Purchase Return' },
    { path: '/stock-adjustments', name: 'stock-adjustments', label: 'Stock Adjustments' },
    { path: '/suppliers', name: 'supplier-list', label: 'Suppliers' },
    { path: '/equipment', name: 'equipment-list', label: 'Equipment' },
    { path: '/warehouses', name: 'warehouse-list', label: 'Warehouses' },
    { path: '/incomes', name: 'income-list', label: 'Income' },
    { path: '/expenses', name: 'expense-list', label: 'Expenses' },
    { path: '/petty-cash', name: 'petty-cash', label: 'Petty Cash' },
    { path: '/finance/coa', name: 'coa-list', label: 'Chart of Accounts' },
    { path: '/finance/journal', name: 'journal-list', label: 'Journal Entries' },
    { path: '/vouchers', name: 'voucher-list', label: 'Vouchers' },
    { path: '/loyalty', name: 'loyalty-list', label: 'Loyalty' },
    { path: '/reviews', name: 'review-list', label: 'Reviews' },
    { path: '/warranty-claims', name: 'warranty-list', label: 'Warranty Claims' },
    { path: '/recalls', name: 'recall-list', label: 'Recalls' },
    { path: '/commissions', name: 'commission-list', label: 'Commissions' },
    { path: '/hrm/leaves', name: 'leave-list', label: 'Leave/Permission' },
    { path: '/reports/service', name: 'report-service', label: 'Service Report' },
    { path: '/reports/sales', name: 'report-sales', label: 'Sales Report' },
    { path: '/reports/stock', name: 'report-stock', label: 'Stock Report' },
    { path: '/reports/financial', name: 'report-financial', label: 'Financial Report' },
    { path: '/reports/technician', name: 'report-technician', label: 'Technician Report' },
    { path: '/reports/customer-lifetime', name: 'report-customer-lifetime', label: 'Customer Lifetime' },
    { path: '/reports/ar-aging', name: 'report-ar-aging', label: 'AR Aging' },
    { path: '/reports/parts-usage', name: 'report-parts-usage', label: 'Parts Usage' },
    { path: '/reports/branch-comparison', name: 'report-branch-comparison', label: 'Branch Comparison' },
    { path: '/reports/cash-flow', name: 'report-cash-flow', label: 'Cash Flow' },
    { path: '/reports/general-ledger', name: 'report-general-ledger', label: 'General Ledger' },
    { path: '/reports/profit-loss', name: 'report-profit-loss', label: 'Profit & Loss' },
    { path: '/reports/balance-sheet', name: 'report-balance-sheet', label: 'Balance Sheet' },
    { path: '/settings', name: 'settings', label: 'Settings' },
    { path: '/notification-templates', name: 'notification-templates', label: 'Notification Templates' },
    { path: '/reminders', name: 'reminder-list', label: 'Reminders' },
    { path: '/users', name: 'user-list', label: 'Users' },
    { path: '/roles', name: 'role-list', label: 'Roles' },
    { path: '/activity-logs', name: 'activity-logs', label: 'Activity Log' },
    { path: '/payment-gateways', name: 'payment-gateways', label: 'Payment Gateways' },
    { path: '/docs', name: 'docs-index', label: 'Documentation' },
];

(async () => {
    if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 1,
    });
    const page = await context.newPage();

    // ── Login ──
    console.log('🔑 Login...');
    await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
    await page.fill('input[type="email"]', EMAIL);
    await page.fill('input[type="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
        console.error('❌ Login gagal! Cek kredensial.');
        await browser.close();
        process.exit(1);
    }
    console.log('✅ Login sukses.');

    // ── Capture semua halaman ──
    for (const item of PAGES) {
        try {
            console.log(`📸 ${item.name}...`);
            await page.goto(BASE + item.path, { waitUntil: 'networkidle', timeout: 15000 });
            await page.waitForTimeout(800);
            await page.screenshot({
                path: path.join(OUT_DIR, `${item.name}.png`),
                fullPage: false,
            });
            console.log(`   ✅ ${item.label}`);
        } catch (e) {
            console.log(`   ⚠️ ${item.label}: ${e.message.substring(0, 60)}`);
        }
    }

    await browser.close();
    console.log('\n🎉 Semua screenshot tersimpan di public/marketing/screens/');
})();

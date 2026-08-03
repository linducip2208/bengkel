// scripts/screenshot-docs.cjs
// Jalankan: node scripts/screenshot-docs.cjs
// Capture semua halaman utama untuk /docs tutorial

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'admin@bengkelpaten.id';
const PASSWORD = 'password';
const OUT_DIR = path.resolve(__dirname, '..', 'public', 'marketing', 'screens');

const PAGES = [
    { path: '/login', name: 'login', label: 'Halaman Login' },
    { path: '/', name: 'dashboard', label: 'Dashboard Utama' },
    { path: '/customers', name: 'customer-list', label: 'Daftar Customer' },
    { path: '/customers/create', name: 'customer-create', label: 'Tambah Customer' },
    { path: '/vehicles', name: 'vehicle-list', label: 'Daftar Kendaraan' },
    { path: '/vehicles/create', name: 'vehicle-create', label: 'Tambah Kendaraan' },
    { path: '/services', name: 'service-list', label: 'Daftar Service' },
    { path: '/services/create', name: 'service-create', label: 'Buat Service Baru' },
    { path: '/jobcards', name: 'jobcard-list', label: 'Daftar Jobcard' },
    { path: '/invoices', name: 'invoice-list', label: 'Daftar Invoice' },
    { path: '/products', name: 'product-list', label: 'Daftar Produk / Sparepart' },
    { path: '/products/stock-opname', name: 'product-stock-opname', label: 'Stock Opname' },
    { path: '/purchases', name: 'purchase-list', label: 'Daftar Purchase Order' },
    { path: '/purchases/create', name: 'purchase-create', label: 'Buat Purchase Order' },
    { path: '/suppliers', name: 'supplier-list', label: 'Daftar Supplier' },
    { path: '/gate-passes', name: 'gate-pass-list', label: 'Daftar Gate Pass' },
    { path: '/sales', name: 'sales-list', label: 'Daftar Sales' },
    { path: '/incomes', name: 'income-list', label: 'Income / Pemasukan' },
    { path: '/expenses', name: 'expense-list', label: 'Expense / Pengeluaran' },
    { path: '/reports/service', name: 'report-service', label: 'Laporan Service' },
    { path: '/reports/sales', name: 'report-sales', label: 'Laporan Sales' },
    { path: '/reports/stock', name: 'report-stock', label: 'Laporan Stock' },
    { path: '/reports/financial', name: 'report-financial', label: 'Laporan Financial' },
    { path: '/branches', name: 'branch-list', label: 'Daftar Cabang' },
    { path: '/settings', name: 'settings', label: 'General Settings' },
    { path: '/vouchers', name: 'voucher-list', label: 'Voucher / Promo' },
    { path: '/commissions', name: 'commission-list', label: 'Komisi Teknisi' },
    { path: '/reviews', name: 'review-list', label: 'Review & Rating' },
    { path: '/bookings', name: 'booking-list', label: 'Booking Online' },
    { path: '/activity-logs', name: 'activity-logs', label: 'Activity Log' },
    { path: '/users', name: 'user-list', label: 'Manajemen User' },
    { path: '/roles', name: 'role-list', label: 'Role & Permission' },
    { path: '/payment-gateways', name: 'payment-gateways', label: 'Payment Gateway' },
    { path: '/docs', name: 'docs-index', label: 'Halaman Docs / Tutorial' },
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

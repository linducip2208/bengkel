/**
 * Capture screenshots dari semua halaman fitur untuk landing page.
 * Jalankan: node scripts/capture-features.js
 * Prasyarat: php artisan serve sedang berjalan di port 8765.
 */

const puppeteer = require('puppeteer-core');
const path = require('path');
const fs = require('fs');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8765';
const OUT = path.join(__dirname, '..', 'public', 'images', 'features');
const EMAIL = 'admin@bengkelpaten.id';
const PASSWORD = 'password';

const CHROME_CANDIDATES = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
];

const TARGETS = [
    // Inti operasional
    { slug: 'dashboard',       url: '/',                       label: 'Dashboard' },
    { slug: 'customers',       url: '/customers',              label: 'Customer' },
    { slug: 'vehicles',        url: '/vehicles',               label: 'Vehicle' },
    { slug: 'services',        url: '/services',               label: 'Service' },
    { slug: 'jobcards',        url: '/jobcards',               label: 'Jobcard' },
    { slug: 'gate-passes',     url: '/gate-passes',            label: 'Gate Pass' },
    { slug: 'invoices',        url: '/invoices',               label: 'Invoice' },
    // Inventory & sales
    { slug: 'products',        url: '/products',               label: 'Inventory' },
    { slug: 'suppliers',       url: '/suppliers',              label: 'Supplier' },
    { slug: 'purchases',       url: '/purchases',              label: 'Purchase' },
    { slug: 'sales',           url: '/sales',                  label: 'Sales' },
    // Keuangan
    { slug: 'incomes',         url: '/incomes',                label: 'Income' },
    { slug: 'expenses',        url: '/expenses',               label: 'Expense' },
    // Reports
    { slug: 'reports',         url: '/reports/financial',      label: 'Financial Report' },
    { slug: 'reports-sales',   url: '/reports/sales',          label: 'Sales Report' },
    { slug: 'reports-stock',   url: '/reports/stock',          label: 'Stock Report' },
    { slug: 'reports-service', url: '/reports/service',        label: 'Service Report' },
    // Multi-cabang & operasional
    { slug: 'branches',        url: '/branches',               label: 'Branches' },
    { slug: 'washbays',        url: '/washbays',               label: 'Washbays' },
    { slug: 'holidays',        url: '/holidays',               label: 'Holidays' },
    // Notifikasi & reminder
    { slug: 'reminders',       url: '/reminders',              label: 'Reminders' },
    { slug: 'notif-templates', url: '/notification-templates', label: 'Notif Templates' },
    // Audit & log
    { slug: 'stock-histories', url: '/stock-histories',        label: 'Stock History' },
    { slug: 'email-logs',      url: '/email-logs',             label: 'Email Log' },
    { slug: 'notes',           url: '/notes',                  label: 'Notes' },
    // Settings & extensibility
    { slug: 'settings',        url: '/settings',               label: 'Settings' },
    { slug: 'custom-fields',   url: '/custom-fields',          label: 'Custom Fields' },
    // Master data lanjutan
    { slug: 'inspection-pts',  url: '/inspection-points',      label: 'Inspection Pts' },
    { slug: 'observation-pts', url: '/observation-points',     label: 'Observation Pts' },
    { slug: 'checkout-cats',   url: '/checkout-categories',    label: 'Checkout Cats' },
    // Geografi
    { slug: 'countries',       url: '/countries',              label: 'Countries' },
    { slug: 'currencies',      url: '/currencies',             label: 'Currencies' },
    // Modul lanjutan (booking, POS, loyalty, garansi, dll)
    { slug: 'bookings',         url: '/bookings',              label: 'Booking Online' },
    { slug: 'pos',              url: '/pos',                   label: 'POS Counter' },
    { slug: 'vouchers',         url: '/vouchers',              label: 'Voucher' },
    { slug: 'loyalty',          url: '/loyalty',               label: 'Loyalty Program' },
    { slug: 'warranty-claims',  url: '/warranty-claims',       label: 'Warranty Claims' },
    { slug: 'commissions',      url: '/commissions',           label: 'Commissions' },
    { slug: 'payment-gateways', url: '/payment-gateways',      label: 'Payment Gateways' },
    { slug: 'petty-cash',       url: '/petty-cash',            label: 'Petty Cash' },
    { slug: 'reviews',          url: '/reviews',               label: 'Reviews' },
    { slug: 'users',            url: '/users',                 label: 'User Management' },
    { slug: 'roles',            url: '/roles',                 label: 'Roles & Access' },
    { slug: 'activity-logs',    url: '/activity-logs',         label: 'Activity Log' },
];

function findChrome() {
    for (const c of CHROME_CANDIDATES) {
        if (fs.existsSync(c)) return c;
    }
    throw new Error('Chrome/Edge tidak ditemukan');
}

(async () => {
    fs.mkdirSync(OUT, { recursive: true });

    const browser = await puppeteer.launch({
        executablePath: findChrome(),
        headless: 'new',
        defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 1 },
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    const page = await browser.newPage();
    page.setDefaultTimeout(20000);

    // --- Login ---
    console.log('Login...');
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
    await page.type('input[name="email"]', EMAIL);
    await page.type('input[name="password"]', PASSWORD);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }),
        page.click('button[type="submit"]'),
    ]);

    if (page.url().includes('/login')) {
        console.error('Login gagal! URL: ' + page.url());
        await browser.close();
        process.exit(1);
    }
    console.log('Login OK, URL: ' + page.url());

    // --- Capture ---
    for (const t of TARGETS) {
        const dest = path.join(OUT, `${t.slug}.png`);
        const fullUrl = `${BASE}${t.url}`;
        process.stdout.write(`  • ${t.label.padEnd(20)} → ${t.slug}.png ... `);
        try {
            const resp = await page.goto(fullUrl, { waitUntil: 'networkidle2' });
            if (!resp || resp.status() >= 400) {
                console.log(`SKIP (HTTP ${resp?.status() ?? '?'})`);
                continue;
            }
            await new Promise(r => setTimeout(r, 600));
            await page.screenshot({ path: dest, fullPage: false });
            console.log('OK');
        } catch (e) {
            console.log(`ERR: ${e.message.split('\n')[0]}`);
        }
    }

    await browser.close();
    console.log('\nDone. Screenshots saved to public/images/features/');
})().catch(err => {
    console.error('FATAL:', err);
    process.exit(1);
});

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8765';
const EMAIL = 'admin@bengkel.test';
const PASSWORD = 'password';
const OUT_DIR = path.join(__dirname, '..', 'public', 'marketing', 'screens-mobile');

if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });

const pages = [
    { name: 'welcome', url: '/' },
    { name: 'login', url: '/login' },
    { name: 'docs', url: '/docs' },
    { name: 'blog', url: '/blog' },
    { name: 'dashboard', url: '/', auth: true },
    { name: 'customers', url: '/customers', auth: true },
    { name: 'services', url: '/services', auth: true },
    { name: 'invoices', url: '/invoices', auth: true },
    { name: 'products', url: '/products', auth: true },
];

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 414, height: 896 },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
    });

    // Login first
    const loginPage = await context.newPage();
    await loginPage.goto(BASE_URL + '/login', { waitUntil: 'networkidle' });
    await loginPage.locator('input[type="email"]').first().fill('');
    await loginPage.type('input[type="email"]', EMAIL, { delay: 50 });
    await loginPage.locator('input[type="password"]').first().fill('');
    await loginPage.type('input[type="password"]', PASSWORD, { delay: 50 });
    await loginPage.waitForTimeout(500);
    await loginPage.locator('button[type="submit"]').first().click();
    await loginPage.waitForTimeout(3000);
    await loginPage.close();

    for (const p of pages) {
        const page = await context.newPage();
        try {
            console.log(`Capturing mobile: ${p.name}...`);
            await page.goto(BASE_URL + p.url, { waitUntil: 'networkidle', timeout: 30000 });
            await page.waitForTimeout(1500);
            await page.screenshot({
                path: path.join(OUT_DIR, `${p.name}.png`),
                fullPage: false,
            });
            console.log(`  OK: ${p.name}.png`);
        } catch (err) {
            console.error(`  FAIL: ${p.name} - ${err.message}`);
        } finally {
            await page.close();
        }
    }

    await browser.close();
    console.log('\nDone! Mobile screenshots saved to: ' + OUT_DIR);
})();

/**
 * Render 9 banner marketing untuk Shopee.
 * Setiap slide dirender ke 1920x1080 PNG di folder shopee/.
 */
const puppeteer = require('puppeteer-core');
const path = require('path');
const fs = require('fs');

const HTML = path.join(__dirname, 'shopee-banners.html');
const OUT  = path.join(__dirname, '..', 'shopee');

const CHROME = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
].find(p => fs.existsSync(p));

(async () => {
    fs.mkdirSync(OUT, { recursive: true });

    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        defaultViewport: { width: 1920, height: 1080, deviceScaleFactor: 1 },
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080, deviceScaleFactor: 1 });
    await page.goto('file:///' + HTML.replace(/\\/g, '/'), { waitUntil: 'networkidle0' });
    // Wait for web fonts
    await page.evaluateHandle('document.fonts.ready');
    await new Promise(r => setTimeout(r, 800));

    const slides = await page.$$('.slide');
    console.log(`Rendering ${slides.length} slides...`);

    for (let i = 0; i < slides.length; i++) {
        const dest = path.join(OUT, `${String(i + 1).padStart(2, '0')}-banner.png`);
        await slides[i].screenshot({ path: dest, omitBackground: false });
        console.log(`  ✓ ${path.basename(dest)}`);
    }

    await browser.close();
    console.log('\nDone. Banner saved to: ' + OUT);
})().catch(err => {
    console.error('FATAL:', err);
    process.exit(1);
});

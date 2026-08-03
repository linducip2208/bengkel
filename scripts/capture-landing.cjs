const puppeteer = require('puppeteer-core');
const path = require('path');
const fs = require('fs');

const BASE = 'http://127.0.0.1:8765';
const OUT = path.join(__dirname, '..', 'landing-preview.png');

const CHROME = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
].find(p => fs.existsSync(p));

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 1 },
    });
    const page = await browser.newPage();
    await page.goto(BASE + '/', { waitUntil: 'networkidle2' });
    await new Promise(r => setTimeout(r, 800));
    await page.screenshot({ path: OUT, fullPage: true });
    await browser.close();
    console.log('Landing screenshot saved: ' + OUT);
})();

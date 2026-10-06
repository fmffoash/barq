// Developer tool, run by `php artisan barq:template-thumbnails` (not by the app itself):
// opens each rendered template preview in headless Chromium (Playwright) and saves a JPEG
// screenshot of the top of the page. Needs Node.js + the `playwright` package (global is fine:
// the artisan command sets NODE_PATH to `npm root -g`).
const fs = require('fs');
const { chromium } = require('playwright');

(async () => {
    const jobs = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
    const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
    // 1280x800 page rendered at half resolution -> 640x400 image (sharp on a ~320px card).
    const page = await browser.newPage({ viewport: { width: 1280, height: 800 }, deviceScaleFactor: 0.5 });
    let failed = 0;

    for (const job of jobs) {
        try {
            await page.goto(job.url, { waitUntil: 'networkidle', timeout: 45000 });
            await page.evaluate(() => document.fonts.ready);
            await page.evaluate(() => Promise.all([...document.images]
                .filter((img) => !img.complete)
                .map((img) => new Promise((resolve) => { img.onload = img.onerror = resolve; }))));
            await page.screenshot({ path: job.out, type: 'jpeg', quality: 70 });
            console.log(`ok ${job.slug}`);
        } catch (error) {
            failed++;
            console.log(`fail ${job.slug} ${error.message.split('\n')[0]}`);
        }
    }

    await browser.close();
    process.exit(failed > 0 ? 1 : 0);
})().catch((error) => {
    console.error(error);
    process.exit(2);
});

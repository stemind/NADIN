/* Real Chromium acceptance checks, run by forscore-http.py on disposable data. */
const assert = require('node:assert/strict'), fs = require('node:fs/promises');
const {chromium} = require('playwright');
const base = process.env.NADIN_TEST_URL;
let checks = 0;
function ok(value, label) { assert(value, label); checks++; }
(async () => {
    const browser = await chromium.launch({headless:true, executablePath:process.env.NADIN_CHROMIUM,
        args:['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu']});
    try {
        const context = await browser.newContext({viewport:{width:1200, height:900}});
        const cookies = Object.entries(JSON.parse(process.env.NADIN_TEST_COOKIES)).map(([name, value]) => ({name, value, url:base}));
        await context.addCookies(cookies);
        const page = await context.newPage(), errors = [];
        let posts = 0;
        page.on('request', request => {if (request.method() === 'POST' && request.url().includes('/get/forscore.php')) posts++;});
        page.on('pageerror', e => errors.push(e.message));
        await page.route('**/*', route => route.request().url().startsWith(base) ? route.continue() : route.abort());
        const endpoint = base + '/get/forscore.php?m=gigreps&c=Headings.txt';
        const button = page.locator('[value=download]');
        const count = async key => Number(await page.locator('[data-count="' + key + '"]').textContent());
        async function select(name, value) {
            await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}), page.locator('[name="' + name + '"]').selectOption(value)]);
        }
        async function expectMissingField(name, label) {
            ok(await button.isEnabled(), label + ': download is active');
            const before = posts;
            await button.click();
            const focus = await page.evaluate(() => {
                const el = document.activeElement, r = el.getBoundingClientRect();
                return {name:el.name, invalid:el.getAttribute('aria-invalid'), visible:r.top >= 0 && r.bottom <= innerHeight,
                    message:el.validationMessage};
            });
            ok(focus.name === name && focus.invalid === 'true' && focus.visible && !!focus.message,
                label + ': click focuses and scrolls to the obligatory field with a clear message');
            ok(posts === before, label + ': incomplete form is not submitted');
        }
        await page.goto(endpoint + '&profile=sx');
        ok(!(await page.locator('[data-review]').isVisible()) && await button.isEnabled(), 'download is active even before choosing an import option; manual Review is absent');
        await expectMissingField('workflow', 'missing import intent');
        await select('profile', 'parts');
        await page.goto(endpoint);
        ok(await page.locator('[name=profile]').inputValue() === 'parts', 'reopening the page restores the section cookie before rendering');
        await page.goto(endpoint + '&profile=sx');
        await select('workflow', 'first');
        ok(await button.isEnabled() && await count('included') === 2 && await count('entries') === 6, 'first import is ready with two unique PDFs and six ordered entries');
        ok(await page.locator('select[data-pdf-choice]').count() === 0, 'single matching PDFs have no unnecessary dropdown');
        const paths = await page.locator('input[data-pdf-choice]').evaluateAll(els => els.map(el => el.getAttribute('data-path')));
        const firstEvent = page.waitForEvent('download'); await button.click();
        const first = await firstEvent, firstXml = await fs.readFile(await first.path(), 'utf8');
        ok((firstXml.match(/ data="/g) || []).length === 2, 'actual browser download embeds each new PDF once');
        await select('workflow', 'update');
        ok(await button.isEnabled() && await page.locator('[data-inventory-panel]').isVisible(), 'following import shows the required inventory with an active download button');
        await expectMissingField('csv', 'missing inventory CSV');
        await select('workflow', 'references');
        ok(await button.isEnabled() && await page.locator('[name=csv]').evaluate(el => !el.required && !el.validity.customError), 'changing away from CSV clears its previous required-field error');
        await select('workflow', 'update');
        await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}), page.locator('[name=csv]').setInputFiles({
            name:'current-notes.csv', mimeType:'text/csv', buffer:Buffer.from('Filename\n' + paths.join('\n') + '\n')})]);
        ok(await button.isEnabled() && await count('included') === 0 && await count('reused') === 2, 'CSV selection automatically refreshes to reuse the annotated originals');
        const updateEvent = page.waitForEvent('download'); await button.click();
        const update = await updateEvent, updateXml = await fs.readFile(await update.path(), 'utf8');
        ok(!updateXml.includes(' data="'), 'actual update download contains references to existing PDFs');
        await page.goto(base + '/get/forscore.php?m=gigreps&c=Ambiguous.txt&profile=sx&workflow=first');
        ok(await button.isEnabled() && await count('unresolved') === 1, 'ambiguous tune has an explicit next step');
        const choiceName = await page.locator('select[data-pdf-choice]').getAttribute('name');
        await expectMissingField(choiceName, 'missing PDF choice');
        const choices = await page.locator('select[data-pdf-choice] option').evaluateAll(els => els.map(el => el.value).filter(Boolean));
        await page.locator('select[data-pdf-choice]').selectOption(choices[0]);
        ok(await button.isEnabled() && await count('unresolved') === 0, 'choosing the PDF enables download immediately');
        await page.goto(base + '/get/forscore.php?m=gigreps&c=Missing.txt&profile=sx&workflow=first');
        ok(await button.isEnabled(), 'missing notes retain an active download action');
        await expectMissingField('placeholders', 'missing-note confirmation');
        await page.locator('[name=placeholders]').check();
        ok(await button.isEnabled() && await count('missing') === 4, 'missing-note confirmation is immediately applied');
        for (const width of [1200, 390, 320]) {
            await page.setViewportSize({width, height:900});
            await page.goto(endpoint + '&profile=sx&workflow=first');
            ok(await button.isVisible() && await button.isEnabled(), 'download is visible and active at ' + width + 'px');
            const gap = await page.locator('.nadin-forscore-help').evaluate(el => el.getBoundingClientRect().top - document.querySelector('[value=download]').getBoundingClientRect().bottom);
            ok(gap >= 20, 'optional help has a clear gap from the button at ' + width + 'px');
            ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'no horizontal overflow at ' + width + 'px');
            if (process.env.NADIN_SCREENSHOTS) {
                await fs.mkdir(process.env.NADIN_SCREENSHOTS, {recursive:true});
                await page.screenshot({path:process.env.NADIN_SCREENSHOTS + '/forscore-' + width + '.png', fullPage:true});
            }
        }
        ok(new URL(await page.locator('link[rel=icon]').first().getAttribute('href'), page.url()).pathname === new URL(base + '/favicon.ico').pathname, 'favicon belongs to NADIN rather than its parent site');
        await page.goto(base + '/get/forscore.php?m=gigreps&c=Long%20required.txt&profile=sx');
        await expectMissingField('workflow', 'long page import choice');
        ok(await page.evaluate(() => scrollY < 500), 'clicking the bottom action on a long page returns to the missing import choice near the top');
        ok(errors.length === 0, 'workflow has no browser JavaScript errors');
        const nojs = await browser.newContext({javaScriptEnabled:false});
        await nojs.addCookies(cookies);
        const plain = await nojs.newPage();
        await plain.goto(endpoint + '&profile=sx');
        ok(await plain.locator('[data-review]').isVisible(), 'manual refresh remains available without JavaScript');
        await plain.locator('[value=download]').click();
        ok(await plain.evaluate(() => document.activeElement.name === 'workflow'), 'without JavaScript the active download also focuses the required import choice');
        await plain.locator('[name=workflow]').selectOption('first');
        await Promise.all([plain.waitForNavigation({waitUntil:'domcontentloaded'}), plain.locator('[data-review]').click()]);
        ok((await plain.locator('[data-export-next]').textContent()).startsWith('Ready.'), 'no-JavaScript refresh produces a correct download summary');
        await nojs.close();
        console.log('PASS:', checks, 'forScore workflow Chromium checks');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});

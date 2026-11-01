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
        const button = page.locator('[data-download=top]'), bottomButton = page.locator('[data-download=bottom]');
        const count = async key => Number(await page.locator('[data-count="' + key + '"]').textContent());
        async function select(name, value) {
            await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}), page.locator('[name="' + name + '"]').selectOption(value)]);
        }
        async function closeExport(exporter, link) {
            const closed = exporter.waitForEvent('close');
            try { await link.click(); } catch (error) { if (!exporter.isClosed()) throw error; }
            await closed;
        }
        async function expectMissingField(name, label) {
            for (const action of [button, bottomButton]) {
                const position = await action.getAttribute('data-download');
                ok(await action.isEnabled(), label + '/' + position + ': download is active');
                const before = posts;
                await action.click();
                const focus = await page.evaluate(() => {
                    const el = document.activeElement, r = el.getBoundingClientRect();
                    return {name:el.name, invalid:el.getAttribute('aria-invalid'), visible:r.top >= 0 && r.bottom <= innerHeight,
                        message:el.validationMessage};
                });
                ok(focus.name === name && focus.invalid === 'true' && focus.visible && !!focus.message,
                    label + '/' + position + ': click focuses and scrolls to the obligatory field with a clear message');
                ok(posts === before, label + '/' + position + ': incomplete form is not submitted');
            }
        }
        function contrast(a, b) {
            const light = rgb => {
                const c = rgb.match(/[\d.]+/g).slice(0,3).map(v => {v = Number(v)/255; return v <= .04045 ? v/12.92 : Math.pow((v+.055)/1.055,2.4);});
                return .2126*c[0]+.7152*c[1]+.0722*c[2];
            };
            const x=light(a), y=light(b); return (Math.max(x,y)+.05)/(Math.min(x,y)+.05);
        }
        await page.goto(endpoint + '&profile=sx');
        ok(await page.locator('[value=download]').count() === 2, 'exactly two download actions share one form');
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
        const bottomEvent = page.waitForEvent('download'); await bottomButton.click();
        const bottomDownload = await bottomEvent;
        ok(await fs.readFile(await bottomDownload.path(), 'utf8') === firstXml, 'top and bottom actions download the same reviewed setlist and PDF bytes');
        await select('workflow', 'update');
        ok(await button.isEnabled() && await page.locator('[data-inventory-panel]').isVisible(), 'following import shows the required inventory with an active download button');
        await expectMissingField('csv', 'missing inventory CSV');
        await select('workflow', 'references');
        ok(await button.isEnabled() && await page.locator('[name=csv]').evaluate(el => !el.required && !el.validity.customError), 'changing away from CSV clears its previous required-field error');
        await select('workflow', 'update');
        await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}), page.locator('[name=csv]').setInputFiles({
            name:'current-notes.csv', mimeType:'text/csv', buffer:Buffer.from('Filename\n' + paths.join('\n') + '\n')})]);
        ok(await button.isEnabled() && await count('included') === 0 && await count('reused') === 2, 'CSV selection automatically refreshes to reuse the annotated originals');
        const updateEvent = page.waitForEvent('download'); await bottomButton.click();
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
            const geometry = await button.evaluate(el => {
                const r=el.getBoundingClientRect(), style=getComputedStyle(el), controls=document.querySelector('.nadin-forscore-controls').getBoundingClientRect(), rows=document.querySelector('.nadin-forscore-rows').getBoundingClientRect();
                return {top:r.top,bottom:r.bottom,left:r.left,right:r.right,height:r.height,viewport:innerHeight,controls:controls.top,rows:rows.top,appearance:style.appearance,bg:style.backgroundColor,fg:style.color};
            });
            ok(geometry.top >= 0 && geometry.bottom <= geometry.viewport && geometry.bottom <= geometry.controls && geometry.bottom <= geometry.rows, 'primary download is in the initial viewport, before setup and tunes at ' + width + 'px');
            ok(geometry.appearance === 'none' && geometry.height >= 44 && contrast(geometry.bg, geometry.fg) >= 4.5, 'download uses an explicit accessible design independent of Windows native grey at ' + width + 'px');
            const rgb=geometry.bg.match(/[\d.]+/g).map(Number);
            ok(rgb[2] > rgb[0]+20, 'download retains NADIN blue at ' + width + 'px');
            await button.hover();
            ok(await button.evaluate(el => getComputedStyle(el).backgroundColor) !== geometry.bg, 'hover visibly responds at ' + width + 'px');
            await button.focus(); await page.keyboard.press('Tab'); await page.keyboard.press('Shift+Tab');
            ok(await button.evaluate(el => el.matches(':focus-visible') && parseFloat(getComputedStyle(el).outlineWidth) >= 2), 'keyboard focus has a visible ring at ' + width + 'px');
            await page.mouse.move(0,0); await page.locator('h1,h2').first().click();

            if (width <= 390) {
                ok(await page.locator('.nadin-forscore-row input[data-pdf-choice]').first().evaluate(input => {
                    const row=input.closest('.nadin-forscore-row'), tune=row.querySelector('span[id^=tune-]'), file=row.querySelector('.nadin-forscore-file');
                    return file.getBoundingClientRect().top >= tune.getBoundingClientRect().bottom-1;
                }), 'tune and PDF filename have separate readable lines on a phone at ' + width + 'px');
            }
            const gap = await page.locator('.nadin-forscore-help').evaluate(el => el.getBoundingClientRect().top - document.querySelector('[data-download=bottom]').getBoundingClientRect().bottom);
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
        for (const width of [1200, 390]) {
            await page.setViewportSize({width, height:900});
            for (const sort of ['alpha', 'newest']) {
                await page.goto(base + '/?m=library&sort=' + sort);
                ok(await page.locator('a[href*="forscore.php"]').count() === 0,
                    'media Library has no forScore link at ' + width + 'px/' + sort);
                ok(await page.locator('#library-xls-link,#library-print-link').count() === 2,
                    'media Library retains its Excel and Print exports at ' + width + 'px/' + sort);
                if (process.env.NADIN_SCREENSHOTS && width === 390 && sort === 'alpha')
                    await page.screenshot({path:process.env.NADIN_SCREENSHOTS + '/library-no-forscore-390.png', fullPage:true});
            }
        }
        await page.setViewportSize({width:1200, height:900});
        for (const module of ['gigreps', 'gigrepsarchiv']) {
            await page.goto(base + '/?m=' + module + '&c=Headings.txt');
            const link = page.locator('a[href*="get/forscore.php"]').first();
            await link.scrollIntoViewIfNeeded();
            const original = {url:page.url(), top:await page.evaluate(() => scrollY), tabs:context.pages().length};
            ok(await link.getAttribute('target') === '_blank', module + ': forScore keeps NADIN open in its original tab');
            const opened = page.waitForEvent('popup'); await link.click();
            const exporter = await opened; await exporter.waitForLoadState('domcontentloaded');
            ok(await exporter.locator('h1,h2').filter({hasText:/^forScore$/}).count() === 1 &&
                ['forScore – NADIN', 'forScore - NADIN'].includes(await exporter.title()), module + ': title and heading have no release number');
            const nextProfile = 'sx';
            await Promise.all([exporter.waitForNavigation({waitUntil:'domcontentloaded'}), exporter.locator('[name=profile]').selectOption(nextProfile)]);
            await Promise.all([exporter.waitForNavigation({waitUntil:'domcontentloaded'}), exporter.locator('[name=workflow]').selectOption('references')]);
            ok(await exporter.locator('[name=from_nadin]').inputValue() === '1', module + ': return context survives automatic form refreshes');
            {
                const downloadEvent = exporter.waitForEvent('download'); await exporter.locator('[data-download=top]').click();
                const downloaded = await downloadEvent;
                ok((await fs.readFile(await downloaded.path(), 'utf8')).includes('<forScore '), module + ': a real download leaves the export tab ready to return');
            }
            const back = exporter.getByRole('link', {name:'← Back to NADIN', exact:true});
            ok(await back.isVisible(), module + ': existing Back to NADIN wording is retained');
            await closeExport(exporter, back);
            ok(!page.isClosed() && page.url() === original.url && await page.evaluate(() => scrollY) === original.top && context.pages().length === original.tabs,
                module + ': Back closes the export and preserves the original NADIN view without a duplicate tab');
        }
        // An export error has no form, but still needs the same return behaviour.
        await page.goto(base + '/?m=gigreps&c=Headings.txt');
        const errorEvent = page.waitForEvent('popup'); await page.locator('a[href*="get/forscore.php"]').first().click();
        const errorTab = await errorEvent;
        await errorTab.goto(base + '/get/forscore.php?m=gigreps&c=Absent.txt&from_nadin=1');
        ok(await errorTab.locator('form').count() === 0 && await errorTab.locator('.nadin-forscore-error').isVisible(), 'error fixture has no export form');
        await closeExport(errorTab, errorTab.getByRole('link',{name:'← Back to NADIN',exact:true}));
        ok(!page.isClosed(), 'Back also closes a failed export without losing NADIN');
        const direct = await context.newPage();
        await direct.goto(endpoint);
        await Promise.all([direct.waitForURL(base + '/?m=gigreps&c=Headings.txt'), direct.getByRole('link',{name:'← Back to NADIN',exact:true}).click()]);
        ok(!direct.isClosed(), 'a directly opened export navigates back in its existing tab');
        await direct.goto(endpoint + '&from_nadin=1');
        await direct.evaluate(() => {window.close = function () {};});
        await Promise.all([direct.waitForURL(base + '/?m=gigreps&c=Headings.txt'), direct.getByRole('link',{name:'← Back to NADIN',exact:true}).click()]);
        ok(!direct.isClosed(), 'a browser refusing close receives a working NADIN navigation fallback');
        await direct.close();
        ok(errors.length === 0, 'workflow has no browser JavaScript errors');
        const nojs = await browser.newContext({javaScriptEnabled:false});
        await nojs.addCookies(cookies);
        const plain = await nojs.newPage();
        await plain.goto(endpoint + '&profile=sx');
        ok(await plain.locator('[data-review]').isVisible(), 'manual refresh remains available without JavaScript');
        for (const position of ['top','bottom']) {
            await plain.locator('[data-download=' + position + ']').click();
            ok(await plain.evaluate(() => document.activeElement.name === 'workflow'), 'without JavaScript the ' + position + ' action focuses the required import choice');
        }
        await plain.locator('[name=workflow]').selectOption('first');
        await Promise.all([plain.waitForNavigation({waitUntil:'domcontentloaded'}), plain.locator('[data-review]').click()]);
        ok((await plain.locator('[data-export-next]').textContent()) === 'Ready to download.', 'no-JavaScript refresh produces a correct download summary');
        await plain.goto(base + '/?m=library');
        ok(await plain.locator('a[href*="forscore.php"]').count() === 0, 'media Library has no forScore link without JavaScript');
        await nojs.close();
        console.log('PASS:', checks, 'forScore workflow Chromium checks');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});

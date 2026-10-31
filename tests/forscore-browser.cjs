/* Run through forscore-http.py with NADIN_BROWSER_TESTS=1. Synthetic fixture only. */
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const base = process.env.NADIN_TEST_URL;
let checks = 0;
function ok(value, label) { assert(value, label); checks++; }
(async () => {
  const browser = await chromium.launch({ headless: true,
    executablePath: process.env.NADIN_CHROMIUM || undefined, args: ['--no-sandbox'] });
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.addCookies(Object.entries(JSON.parse(process.env.NADIN_TEST_COOKIES))
      .map(([name, value]) => ({ name, value, url: base })));
    const page = await context.newPage();
    await page.route('**/*', route => route.request().url().startsWith(base)
      ? route.continue() : route.abort());
    for (const module of ['gigreps', 'gigrepsarchiv', 'library']) {
      await page.goto(base + '/?m=' + module + '&c=Headings.txt');
      const link = page.getByRole('link', { name: 'forScore↓', exact: true });
      const print = page.getByRole('link', { name: 'print↓', exact: true });
      ok(await link.isVisible(), 'visible forScore link in ' + module);
      const metrics = async locator => locator.evaluate(el => {
        const r = el.getBoundingClientRect(), s = getComputedStyle(el);
        return { x: r.x, right: r.right, y: r.y, family: s.fontFamily,
          size: s.fontSize, color: s.color, weight: s.fontWeight };
      });
      const a = await metrics(link), b = await metrics(print);
      ok(a.y > b.y && Math.abs(a.right - b.right) < 1,
        'forScore aligns directly below Print in ' + module);
      ok(a.family === b.family && a.size === b.size && a.weight === b.weight && a.color === b.color,
        'forScore has exactly the Print typography in ' + module);
      const popupEvent = context.waitForEvent('page');
      await link.click();
      const popup = await popupEvent;
      await popup.waitForLoadState();
      ok(await popup.getByRole('heading', { name: 'forScore — NADIN 7.7' }).isVisible(),
        'forScore opens in its own tab from ' + module);
      await popup.close();
    }
    for (const width of [1440, 390, 320]) {
      await page.setViewportSize({ width, height: 1000 });
      await page.goto(base + '/get/forscore.php?m=gigreps&c=Headings.txt&profile=sx');
      ok(await page.getByRole('button', { name: 'Download .4ss', exact: true }).isVisible(),
        'download remains reachable at ' + width + 'px');
      ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
        'new export page has no horizontal overflow at ' + width + 'px');
    }
    console.log('PASS:', checks, 'NADIN 7.7 Chromium checks');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });

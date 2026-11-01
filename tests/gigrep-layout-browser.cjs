/* Real browser geometry, resizing and navigation; disposable fixtures only. */
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const cases = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
let checks = 0;
function ok(value, label) { assert(value, label); checks++; }
const near = (a, b) => Math.abs(a - b) < 2;

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.NADIN_CHROMIUM,
    headless: true, args: ['--no-sandbox'] });
  const geometry = new Map();
  try {
    for (const c of cases) for (const role of ['member', 'admin']) {
      for (const scripts of c.label === 'release' ? [true, false] : [true]) {
        const context = await browser.newContext({ javaScriptEnabled: scripts,
          viewport: { width: 1440, height: 1000 } });
        await context.addCookies(Object.entries(c.accounts[role])
          .map(([name, value]) => ({ name, value, url: c.url })));
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/*', route => route.request().url().startsWith(c.url)
          ? route.continue() : route.abort());
        for (const module of ['gigreps', 'gigrepsarchiv']) {
          await page.goto(c.url + '/?m=' + module + '&c=' + encodeURIComponent(c.name));
          const list = page.locator(c.label === 'release' ? '#nadin-gig-list' : 'h3').first();
          const content = page.locator('#includes');
          const info = page.locator('.infos').first();
          const metrics = () => page.evaluate(() => {
            const nav = document.querySelector('#nadin-gig-list') || document.querySelector('h3');
            const rect = e => { const r = e.getBoundingClientRect(); return {x:r.x,y:r.y,width:r.width,height:r.height,right:r.right,bottom:r.bottom}; };
            return { nav:rect(nav), content:rect(document.getElementById('includes')),
              info:rect(document.querySelector('.infos')), float:getComputedStyle(nav).cssFloat,
              client:nav.clientHeight, scroll:nav.scrollHeight,
              links:Array.from(nav.querySelectorAll('a[href]'), a => a.getAttribute('href')) };
          });
          for (const viewport of [{width:1440,height:1000}, {width:1280,height:1200}, {width:800,height:520}]) {
            await page.setViewportSize(viewport);
            if (c.side !== 'no') {
              await page.waitForFunction(height => {
                const nav = document.querySelector('#nadin-gig-list') || document.querySelector('h3');
                return Math.abs(nav.getBoundingClientRect().height - Math.max(600, height - 150)) < 2;
              }, viewport.height);
            }
            const m = await metrics();
            ok(c.side !== 'no' ? m.float === 'left' : m.float === 'none', 'configured orientation ' + c.id);
            if (c.side !== 'no') {
              ok(near(m.nav.width, c.width), 'configured sidebar width ' + c.id);
              ok(m.content.x >= m.nav.right && near(m.content.x - m.nav.x, c.width + 20), 'tunes clear the list ' + c.id);
              ok(near(m.info.x - m.nav.x, c.width), 'info block clears the list ' + c.id);
              ok(m.scroll > m.client, 'long vertical navigation remains scrollable ' + c.id);
            } else {
              ok(m.nav.height <= 201 && m.content.y >= m.nav.bottom, 'horizontal list stays above tunes ' + c.id);
              ok(near(m.content.x, m.nav.x), 'top mode retains full content width ' + c.id);
            }
            const key = [c.side,c.width,role,module,viewport.width,viewport.height].join(':');
            if (scripts) geometry.set(c.label + ':' + key, m);
          }
          ok(await content.getByText('Layout_Tune', {exact:true}).count() >= 2, 'ordered repeated tunes remain visible');
          ok(await page.getByRole('link', {name:'forScore↓',exact:true}).isVisible(), 'forScore remains available');
          if (scripts && c.label === 'release' && c.side !== 'no') {
            if (module === 'gigrepsarchiv') {
              ok(!(await page.locator('#infoarea').isVisible()), 'archive info initially closed');
              await info.getByRole('link', {name:'show/hide infos',exact:true}).click();
              ok(await page.locator('#infoarea').isVisible(), 'archive info can be reopened');
            }
            await list.evaluate(nav => { nav.scrollTop = nav.scrollHeight; });
            await list.getByRole('link', {name:/Layout\s99$/}).click();
            await page.waitForURL(/Layout%2099\.txt|Layout%2099.txt|Layout\+99/);
            ok(await page.locator('#includes').getByText('Layout_Tune',{exact:true}).count() >= 2, 'navigation opens selected GigRep after scrolling');
          }
          ok(errors.length === 0, 'no browser script errors: ' + errors.join(';'));
        }
        await context.close();
      }
    }
    for (const [key, baseline] of geometry) {
      if (!key.startsWith('baseline:')) continue;
      const current = geometry.get(key.replace('baseline:', 'release:'));
      ok(Boolean(current), 'matching release geometry exists');
      for (const element of ['nav','content','info']) {
        for (const property of ['x','width','height']) {
          ok(near(baseline[element][property], current[element][property]),
            '7.7.1 geometry preserved: ' + key + '/' + element + '/' + property);
        }
      }
      ok(JSON.stringify(baseline.links) === JSON.stringify(current.links), 'same navigation destinations as 7.7.1');
    }
    console.log('PASS:', checks, 'classic GigRep Chromium checks');
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });

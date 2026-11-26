// Disposable local fixtures only; invoked by audio-stream-http.py.
const { chromium } = require('playwright');
const http = require('http');
const assert = require('assert/strict');
let input = '';
process.stdin.on('data', chunk => { input += chunk; });
process.stdin.on('end', async () => {
  const config = JSON.parse(input);
  const browser = await chromium.launch({executablePath: process.env.NADIN_CHROMIUM || '/usr/bin/chromium', headless: true,
    args: ['--no-sandbox', '--autoplay-policy=no-user-gesture-required', '--disable-dev-shm-usage']});
  let proxy;
  try {
    const context = await browser.newContext();
    await context.addCookies(config.cookies);
    const page = await context.newPage();
    const responses = [];
    page.on('response', response => { if(response.url().includes('get/file.php')) responses.push(response.status()); });
    await page.goto(config.base + 'audio-probe.html');
    await page.waitForFunction(() => document.querySelector('audio').readyState >= 1);
    assert(Math.abs(await page.$eval('audio', audio => audio.duration) - 130) < 1, 'real MP3 duration');
    await page.$eval('audio', audio => audio.play());
    // Real elapsed playback beyond one minute; no accelerated media clock.
    await page.waitForFunction(() => document.querySelector('audio').currentTime > 65, null, {timeout: 85000});
    assert.equal(await page.$eval('audio', audio => audio.error), null);
    console.log('PASS: Chromium played continuously beyond 65 seconds');
    await page.$eval('audio', audio => { audio.currentTime = 112; });
    await page.waitForFunction(() => { const audio = document.querySelector('audio'); return !audio.seeking && audio.currentTime > 113; }, null, {timeout: 15000});
    await page.$eval('audio', audio => { audio.currentTime = 12; });
    await page.waitForFunction(() => { const audio = document.querySelector('audio'); return !audio.seeking && audio.currentTime > 13 && audio.currentTime < 20; }, null, {timeout: 15000});
    assert(responses.includes(206), 'browser received partial content');
    console.log('PASS: Chromium seeks forwards and backwards with partial content');

    // Drop the first media response after about 60 s of audio bytes. Chromium's
    // native player must recover through the real PHP endpoint using Range.
    let cut = false;
    const resumed = [];
    const upstream = new URL(config.base);
    proxy = http.createServer((request, response) => {
      const target = new URL(request.url, upstream.origin);
      const media = target.pathname.endsWith('/get/file.php');
      const upstreamRequest = http.request(target, {method: request.method, headers: {...request.headers, host: upstream.host}}, incoming => {
        response.writeHead(incoming.statusCode, incoming.headers);
        if(media && cut && request.headers.range) resumed.push({range: request.headers.range, status: incoming.statusCode});
        if(media && !cut) {
          cut = true;
          let bytes = 0;
          incoming.on('data', chunk => {
            if(response.destroyed) return;
            const part = chunk.subarray(0, Math.max(0, 960000 - bytes));
            bytes += part.length;
            if(part.length) response.write(part);
            if(bytes >= 960000) {
              // Finish queued bytes but omit the advertised remainder.
              response.socket.end();
              incoming.destroy();
            }
          });
          incoming.on('end', () => { if(!response.destroyed) response.end(); });
        } else incoming.pipe(response);
        incoming.on('error', () => response.destroy());
      });
      upstreamRequest.on('error', () => response.destroy());
      request.pipe(upstreamRequest);
    });
    await new Promise(resolve => proxy.listen(0, '127.0.0.1', resolve));
    const cutPage = await context.newPage();
    const proxyBase = config.base.replace(upstream.origin, 'http://127.0.0.1:' + proxy.address().port);
    await cutPage.goto(proxyBase + 'audio-probe.html');
    await cutPage.waitForFunction(() => document.querySelector('audio').readyState >= 1);
    await cutPage.$eval('audio', audio => { audio.currentTime = 75; return audio.play(); });
    await cutPage.waitForFunction(() => { const audio = document.querySelector('audio'); return audio.currentTime > 77 && !audio.seeking; }, null, {timeout: 25000});
    assert.equal(await cutPage.$eval('audio', audio => audio.error), null);
    assert(resumed.some(item => item.status === 206), 'interrupted player recovered with partial content');
    console.log('PASS: Chromium recovers after a truncated MP3 response', JSON.stringify(resumed));
  } catch(error) {
    console.error(error);
    process.exitCode = 1;
  } finally {
    await browser.close();
    if(proxy) proxy.closeAllConnections();
    if(proxy) proxy.close();
  }
});

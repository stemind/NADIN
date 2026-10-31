/* Real Chromium checks. Generate views with render-preview.py first.
 * NODE_PATH must include playwright; NADIN_CHROMIUM can select a local browser. */
const fs=require('fs'),path=require('path'),assert=require('assert');
const {pathToFileURL}=require('url');
const {chromium}=require('playwright');
const views=path.resolve(process.argv[2]),root=path.resolve(__dirname,'..');let checks=0;
function ok(value,label){assert(value,label);checks++;}
function fixture(name,tunes){
 let html=fs.readFileSync(path.join(views,name+'.html'),'utf8');
 if(tunes)html=html.replace(/(<script type="application\/json" id="nadin-admin-data">)([\s\S]*?)(<\/script>)/,(_,a,b,c)=>{const boot=JSON.parse(b);boot.selected.tunes=tunes;return a+JSON.stringify(boot)+c;});
 const file=path.join(views,name+'-browser.html');fs.writeFileSync(file,html);return pathToFileURL(file).href;
}
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.NADIN_CHROMIUM||undefined,args:['--no-sandbox']});
 try{
  const page=await browser.newPage({viewport:{width:1440,height:950}}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',r=>r.request().url().startsWith('file:')?r.continue():r.abort());
  if(fs.existsSync(path.join(root,'nadinmodule/admin.js'))){
   await page.goto(fixture('admin',['Aus Gigrep:','Anna_Swings_Best','Berta_Has_The_Blues','Challenge Tunes und Neuigkeiten:','David_Funks_The_City_Hall']));
   const numbers=()=>page.locator('#setlist .nadin-order').allTextContents();
   ok(JSON.stringify(await numbers())===JSON.stringify(['','1','2','','3']),'headings render without numbers in Chromium');
   const source=page.locator('[data-catalog-tune="Anna_Swings_Best"]');
   const initial=await source.evaluate(el=>getComputedStyle(el).backgroundColor);await source.hover();
   ok(await source.evaluate(el=>getComputedStyle(el).backgroundColor)!==initial,'library rows visibly highlight on hover');
   await page.mouse.move(0,0);await source.locator('button').focus();
   ok(await source.evaluate(el=>getComputedStyle(el).backgroundColor)!==initial,'keyboard focus highlights the library row');
   await source.dragTo(page.locator('#setlist [data-row="3"]'),{targetPosition:{x:70,y:2}});
   ok(await page.locator('#setlist [data-row="3"] .nadin-setlist-title').textContent()==='Anna Swings Best','native drag inserts library tune before heading');
   ok(JSON.stringify(await numbers())===JSON.stringify(['','1','2','3','','4']),'native drag recalculates only tune numbers');
   await page.locator('#setlist-undo').click();
   await page.locator('#setlist [data-row="0"]').dragTo(page.locator('#setlist [data-row="2"]'),{targetPosition:{x:70,y:25}});
   ok(await page.locator('#setlist [data-row="2"] .nadin-setlist-title').textContent()==='Aus Gigrep:','native setlist drag preserves heading text and moves it');
   await page.locator('#setlist-undo').click();
   if(process.env.NADIN_SCREENSHOTS){fs.mkdirSync(process.env.NADIN_SCREENSHOTS,{recursive:true});await page.screenshot({path:path.join(process.env.NADIN_SCREENSHOTS,'admin.png'),fullPage:true});}
   await page.goto(fixture('admin',[]));
   await page.locator('[data-catalog-tune="Anna_Swings_Best"]').dragTo(page.locator('#setlist .nadin-empty'));
   ok(JSON.stringify(await numbers())==='["1"]','native drag works into an empty setlist');
  }
  await page.goto(fixture('library'));
  const links=page.locator('#library-alphabet-links a');ok(await links.count()>=8,'alphabet links generated from the library');
  const first=links.first(),original=await first.evaluate(el=>getComputedStyle(el).backgroundColor);await first.hover();
  ok(await first.evaluate(el=>getComputedStyle(el).backgroundColor)!==original,'alphabet has a visible hover state');
  await first.click();await page.waitForFunction(()=>document.querySelector('#library-alphabet-links [aria-current="location"]'));
  ok(await first.getAttribute('aria-current')==='location','alphabet marks the selected letter');
  for(const width of [1440,390,320]){
   await page.setViewportSize({width,height:950});
   ok(await page.locator('.nadin-library-alphabet').isVisible(),'alphabet stays visible at '+width+'px');
   ok(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),'library has no horizontal page overflow at '+width+'px');
   ok(await page.locator('#library-alphabet-links').evaluate(el=>el.scrollWidth<=el.clientWidth),'alphabet wraps without sideways scrolling at '+width+'px');
  }
  await page.selectOption('#library-sort','newest');ok(!await links.first().isVisible(),'newest sorting hides alphabet navigation');
  await page.selectOption('#library-sort','alpha');ok(await links.first().isVisible(),'alphabetical sorting restores navigation');
  if(process.env.NADIN_SCREENSHOTS)await page.screenshot({path:path.join(process.env.NADIN_SCREENSHOTS,'library-mobile.png'),fullPage:true});
  ok(errors.length===0,'no uncaught browser errors: '+errors.join('; '));
  console.log('PASS:',checks,'Chromium interaction and layout checks');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});

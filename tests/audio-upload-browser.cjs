/* V18 real browser: explicit upload, conversion feedback, error recovery and MP3-only fallback. */
const assert=require('node:assert/strict'),fs=require('node:fs/promises'),path=require('node:path');
const {chromium}=require('playwright'),axe=require('axe-core');
const base=process.env.NADIN_AUDIO_URL,app=process.env.NADIN_AUDIO_APP;
let checks=0;const errors=[];
function ok(value,label){assert(value,label);checks++;}
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.NADIN_CHROMIUM,args:['--no-sandbox','--disable-dev-shm-usage']});
 try{
  const context=await browser.newContext({viewport:{width:1200,height:900}});
  await context.addCookies(Object.entries(JSON.parse(process.env.NADIN_AUDIO_COOKIES)).map(([name,value])=>({name,value,url:base})));
  const page=await context.newPage();page.on('pageerror',e=>errors.push(e.message));
  await page.goto(base+'/nadinmodule/uploadaudio.php?tune=Audio_Test');
  ok(await page.locator('.nadin-popup-brand').textContent()==='NADIN V18','audio editor identifies the V18 branch');
  const chosen=page.locator('#media-upload'),button=page.getByRole('button',{name:'Upload files',exact:true});
  const directory=path.join(app,'library/Audio_Test'),output=path.join(directory,'Browser.mp3');
  const recording=async()=>{const names=(await fs.readdir(directory)).filter(n=>/^Browser(?:\.\(\d{2}\.\d{2}\))?\.mp3$/.test(n));ok(names.length===1,'one current Browser recording exists after duration annotation');return path.join(directory,names[0]);};
  let navigations=0;page.on('framenavigated',frame=>{if(frame===page.mainFrame())navigations++;});
  await chosen.setInputFiles({name:'Browser.wav',mimeType:'audio/wav',buffer:await fs.readFile(process.env.NADIN_AUDIO_WAV)});
  ok(!await fs.stat(output).catch(()=>null),'choosing audio does not upload or convert it');
  let release,started;const held=new Promise(resolve=>release=resolve),sent=new Promise(resolve=>started=resolve);
  await page.route('**/AUDsendfile2.php',async route=>{started();await held;await route.continue();});
  await button.click();await sent;
  ok(await page.locator('#nadin-task-status').textContent()==='Uploading and converting audio to MP3… Keep this window open.','conversion is announced during the actual pending upload');
  ok(await button.isDisabled()&&await chosen.isDisabled(),'duplicate uploads are blocked while conversion is pending');
  release();await page.locator('#nadin-task-status').filter({hasText:'Audio converted to MP3 and uploaded.'}).waitFor();await page.unroute('**/AUDsendfile2.php');
  await page.frameLocator('iframe[name=files]').getByRole('link',{name:/^Browser\.\(\d{2}\.\d{2}\)\.mp3$/,exact:true}).waitFor();
  const saved=await fs.readFile(await recording());ok(saved.length>100&&await chosen.inputValue()==='','confirmed conversion refreshes the recording list and clears the chosen file');
  await chosen.setInputFiles({name:'Browser.wav',mimeType:'audio/wav',buffer:Buffer.from('damaged WAV')});await button.click();
  await page.locator('#nadin-task-error').filter({hasText:'could not be converted'}).waitFor();
  ok((await fs.readFile(await recording())).equals(saved)&&await chosen.evaluate(el=>el.files.length===1),'conversion error preserves the recording and the selected upload');
  ok(await button.isEnabled()&&await chosen.isEnabled(),'conversion error restores the controls for retry');
  await chosen.setInputFiles({name:'Browser.mp3',mimeType:'audio/mpeg',buffer:await fs.readFile(process.env.NADIN_AUDIO_MP3)});await button.click();
  await page.locator('#nadin-task-status').filter({hasText:'1 file uploaded.'}).waitFor();
  await page.frameLocator('iframe[name=files]').getByRole('link',{name:'Browser.(00.00).mp3',exact:true}).waitFor();
  ok((await fs.readFile(await recording())).equals(await fs.readFile(process.env.NADIN_AUDIO_MP3)),'retry with MP3 retains exact source bytes');
  ok(navigations===0,'duration annotation preserves the active editor instead of reloading it');
  for(const width of [1200,390,320]){
   await page.setViewportSize({width,height:900});await page.goto(base+'/nadinmodule/uploadaudio.php?tune=Audio_Test');
   ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'audio upload fits '+width+'px');
   ok(await button.isVisible()&&await chosen.isVisible()&&await page.getByText('Other formats: up to 2 MB and 30 minutes.',{exact:false}).isVisible(),'explicit upload and actual server limits remain usable at '+width+'px');
   if(process.env.NADIN_SCREENSHOTS){await fs.mkdir(process.env.NADIN_SCREENSHOTS,{recursive:true});await page.screenshot({path:path.join(process.env.NADIN_SCREENSHOTS,'audio-upload-'+width+'.png'),fullPage:true});}
  }
  await page.addScriptTag({content:axe.source});const audit=await page.evaluate(async()=>await axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}}));
  ok(!audit.violations.some(v=>['critical','serious'].includes(v.impact)),'audio upload has no serious or critical automated accessibility findings');
  const config=path.join(app,'config.inc.php'),original=await fs.readFile(config,'utf8');
  try{
   await fs.writeFile(config,original.replace("$audioffmpeg='auto';","$audioffmpeg='';"));await page.goto(base+'/nadinmodule/uploadaudio.php?tune=Audio_Test');
   ok(await chosen.getAttribute('accept')==='audio/mpeg,.mp3'&&await page.getByText('Automatic audio conversion is unavailable on this server. Upload an MP3 recording.',{exact:true}).isVisible(),'unavailable conversion has an honest MP3-only interface');
   if(process.env.NADIN_SCREENSHOTS)await page.screenshot({path:path.join(process.env.NADIN_SCREENSHOTS,'audio-upload-fallback-320.png'),fullPage:true});
  }finally{await fs.writeFile(config,original);}
  ok(errors.length===0,'audio workflow has no JavaScript errors: '+errors.join('; '));
  console.log('PASS:',checks,'V18 audio Chromium checks');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});

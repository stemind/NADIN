/* Actual layout and navigation for yes/no/missing/empty/other configuration. */
const fs=require('node:fs'),assert=require('node:assert/strict'),{chromium}=require('playwright');
const fixture=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));let checks=0;
function ok(value,label){assert(value,label);checks++;}
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.NADIN_CHROMIUM,args:['--no-sandbox','--disable-dev-shm-usage','--disable-gpu']});
 try{
  for(const role of ['member','admin']){
   const context=await browser.newContext({viewport:{width:1440,height:1000}});
   await context.addCookies(Object.entries(fixture.accounts[role]).map(([name,value])=>({name,value,url:fixture.url})));
   const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',route=>route.request().url().startsWith(fixture.url)?route.continue():route.abort());
   for(const side of ['yes','no','missing','','auto']){
    const setting=side==='missing'?'':"$rendergiglistontheleft='"+side+"';";
    fs.writeFileSync(fixture.config,fixture.original.replace(/^\$rendergiglistontheleft\s*=.*$/m,setting));
    for(const module of ['gigreps','gigrepsarchiv']){
     await page.goto(fixture.url+'/?m='+module+'&c=Layout%20One.txt');
     const view=await page.evaluate(()=>{
      const layout=document.querySelector('.nadin-layout');
      const nav=document.querySelector('[data-sidebar]')||document.querySelector('#nadin-gig-list')||document.querySelector('h3');
      const content=document.getElementById('includes'),a=nav.getBoundingClientRect(),b=content.getBoundingClientRect();
      return {top:layout?layout.classList.contains('nadin-gig-list-top'):getComputedStyle(nav).cssFloat!=='left',nav:{x:a.x,y:a.y,right:a.right,width:a.width,bottom:a.bottom},content:{x:b.x,y:b.y},text:content.textContent,links:Array.from(nav.querySelectorAll('a[href]'),e=>e.getAttribute('href'))};
     });
     ok(view.top===(side==='no'),'only explicit no selects top: '+role+'/'+module+'/'+JSON.stringify(side));
     ok(side==='no'?view.content.y>=view.nav.bottom-2:view.content.x>=view.nav.right-2,'list and tunes have the configured physical relationship');
     if(side!=='no')ok(Math.abs(view.nav.width-240)<2,'custom left-column width is retained');
     ok(view.text.includes('Layout_Tune')||view.text.includes('Layout Tune'),'tunes remain visible');
     ok(view.links.some(h=>h.includes('Layout%20Two')||h.includes('Layout+Two')||h.includes('Layout Two')),'navigation retains other GigReps');
    }
   }
   ok(errors.length===0,'no layout JavaScript errors: '+errors.join(';'));await context.close();
  }
  console.log('PASS:',checks,'default-left Chromium checks');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});

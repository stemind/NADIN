/* Software DOM checks; not a claim of visual/browser acceptance. */
const fs=require('fs'),assert=require('assert'),{JSDOM}=require('jsdom');
const cases=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));let checks=0;
function ok(value,label){assert(value,label);checks++;}
for(const c of cases)for(const viewport of [1440,390])for(const scripts of [false,true]){
 const d=new JSDOM(c.html,{url:'http://fixture.invalid/nadin/?m='+c.module+'&c=Layout%20One.txt',runScripts:'outside-only'}),w=d.window,errors=[];
 w.innerWidth=viewport;w.shortcut={add(){}};w.setTimeout=()=>0;w.clearTimeout=()=>{};w.scrollTo=()=>{};w.alert=()=>{};w.confirm=()=>true;
 w.matchMedia=()=>({matches:viewport<700,addEventListener(){},removeEventListener(){},addListener(){},removeListener(){}});
 const links=()=>Array.from(w.document.querySelectorAll('a[href]'),e=>e.getAttribute('href')).sort();const before=links();
 if(scripts){for(const s of w.document.querySelectorAll('script:not([src])')){try{w.eval(s.textContent)}catch(e){errors.push(e.message)}}if(c.modern){try{w.eval(fs.readFileSync(process.env.NADIN_LAYOUT_JS,'utf8'))}catch(e){errors.push(e.message)}}}
 const side=c.modern ? c.side!=='no' : c.side==='yes';
 if(c.modern){const root=w.document.querySelector('.nadin-layout'),nav=w.document.querySelector('[data-sidebar]'),toggle=w.document.getElementById('nadin-sidebar-toggle');
  ok(root.classList.contains('nadin-gig-list-top')===!side,'modern mode remains config-controlled');
  if(scripts&&!side)ok(toggle.hidden&&toggle.style.display==='none','inline gig bar does not expose an inert sidebar toggle');
  if(scripts&&side&&viewport<700){toggle.click();ok(w.document.body.classList.contains('nadin-sidebar-open'),'mobile side list opens');w.document.dispatchEvent(new w.KeyboardEvent('keydown',{key:'Escape',bubbles:true}));ok(!w.document.body.classList.contains('nadin-sidebar-open'),'mobile side list closes with Escape');}
 }else{const nav=w.document.getElementById('nadin-gig-list'),style=w.getComputedStyle(nav),content=w.getComputedStyle(w.document.getElementById('includes'));
  ok(side?style.cssFloat==='left':['','none'].includes(style.cssFloat),'classic orientation works without script-dependent mutation');
  ok(side?style.width===c.width+'px':true,'classic configured sidebar width is retained');
  ok(side?content.marginLeft===(c.width+20)+'px':['','0','0px'].includes(content.marginLeft),'classic tune area aligns with configured list width: '+JSON.stringify({side:c.side,width:c.width,module:c.module,role:c.role,scripts,viewport,margin:content.marginLeft}));
 }
 ok(JSON.stringify(before)===JSON.stringify(links()),'all original navigation/download links survive DOM adaptation');
 ok(errors.length===0,'no uncaught inline/layout script errors: '+errors.join(';'));w.close();
}
console.log('PASS:',checks,'Main layout software-DOM checks');

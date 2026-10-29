/* Run with NODE_PATH pointing to jsdom, after tests/render-preview.py. No real browser or external requests. */
const fs=require('fs'),path=require('path'),assert=require('assert');
const {JSDOM}=require('jsdom');
const fixture=process.argv[2],root=path.resolve(__dirname,'..');let checks=0;
function ok(value,label){assert(value,label);checks++;}
const tick=()=>new Promise(resolve=>setImmediate(resolve));
function setup(name){
 const doc=fs.readFileSync(path.join(fixture,name+'.html'),'utf8');
 const dom=new JSDOM(doc,{url:'https://nadin.example.invalid/index.php',runScripts:'outside-only',pretendToBeVisual:true});
 const w=dom.window,errors=[];w.addEventListener('error',e=>errors.push(e.message));
 w.HTMLDialogElement.prototype.showModal=function(){this.open=true;};
 w.HTMLDialogElement.prototype.close=function(value=''){this.returnValue=value;this.open=false;this.dispatchEvent(new w.Event('close'));};
 w.fetch=async()=>{throw new Error('Unexpected network call');};
 w.eval(fs.readFileSync(path.join(root,'nadinmodule/admin.js'),'utf8'));
 return {dom,w,d:w.document,errors};
}
(async()=>{
 const a=setup('admin'),{w,d}=a;
 ok(d.querySelectorAll('#setlist li[data-row]').length===12,'server initial gig renders without network');
 ok(d.querySelectorAll('#catalog-items [data-add]').length===12,'complete tune catalog renders');
 const original=d.querySelector('#setlist').textContent;
 d.querySelector('[data-add="Anna_Swings_Best"]').click();ok(d.querySelectorAll('#setlist li[data-row]').length===13,'add repeated tune');
 d.getElementById('setlist-undo').click();ok(d.querySelector('#setlist').textContent===original,'undo restores exact setlist');
 d.querySelector('[data-remove="0"]').click();ok(d.querySelectorAll('#setlist li[data-row]').length===11,'remove tune');d.getElementById('setlist-undo').click();
 d.querySelector('[data-move="1"][data-index="0"]').click();ok(d.querySelector('.nadin-setlist-title').textContent.includes('Berta'),'move tune down');d.getElementById('setlist-undo').click();
 const search=d.getElementById('catalog-search');search.value='MIDNIGHT';search.dispatchEvent(new w.Event('input'));ok(d.querySelectorAll('[data-add]').length===1,'case-insensitive catalog search');
 const input=d.getElementById('gig-infos');input.value='Changes to keep';input.dispatchEvent(new w.Event('input'));
 w.fetch=async(url)=>({ok:true,json:async()=>({csrf:'fixture'})});
 let calls=0;w.fetch=async(url,opts)=>{calls++;return opts&&opts.method==='POST'?{ok:false,status:409,json:async()=>({error:'Changed in another window'})}:{ok:true,json:async()=>({csrf:'fixture'})};};
 d.getElementById('gig-form').dispatchEvent(new w.Event('submit',{cancelable:true}));await tick();await tick();
 ok(input.value==='Changes to keep','save conflict preserves entered infos');ok(d.getElementById('admin-error').textContent.includes('another window'),'save conflict is visible');ok(!d.getElementById('admin-save').disabled,'controls unlock after failed save');ok(d.getElementById('changes').value==='1','unsaved marker remains after failure');ok(calls===2,'write refreshes csrf before request');
 d.getElementById('admin-new').click();await tick();ok(d.getElementById('admin-dialog').open,'switch asks before discarding changes');d.getElementById('admin-dialog').close('cancel');await tick();ok(input.value==='Changes to keep','cancel preserves unsaved work');
 ok(a.errors.length===0,'gig UI has no uncaught errors');
 const u=setup('users'),ud=u.d,uw=u.w;
 ok(ud.querySelectorAll('[name=rights]').length===14,'all standard permissions have controls');ok(ud.querySelector('[name=email]').readOnly,'existing account email remains immutable');
 const adminRights=ud.querySelectorAll('[name=rights]:checked').length;ok(adminRights===14,'admin permissions render exactly');
 ud.querySelector('[data-preset=member]').click();ok(ud.querySelector('[value=musers]').checked,'own user management remains selected in preset');
 ud.querySelector('[data-preset=librarian]').click();ok(ud.querySelector('[value=uppdf]').checked&&ud.querySelector('[value=upaudio]').checked&&ud.querySelector('[value=vlibrary]').checked,'librarian template includes dependencies');
 const library=ud.querySelector('[value=vlibrary]');library.checked=false;library.dispatchEvent(new uw.Event('change'));ok(library.checked,'required browse permission cannot be accidentally unchecked');
 ud.querySelector('[data-preset=substitute]').click();ok(ud.getElementById('has-expiry').checked&&!ud.getElementById('expiry-label').hidden,'substitute has visible expiry');ok(ud.querySelector('[name=expiry]').value!=='2099-12-31','substitute receives finite expiry');
 ok(ud.getElementById('rights-summary').textContent.includes('unsplit Parts'),'parts fallback explained');
 ok(ud.getElementById('person-delete').disabled,'own deletion disabled');ok(u.errors.length===0,'people UI has no uncaught errors');
 // Save read-only renderings for the in-conversation preview. These are actual generated DOMs.
 for(const name of ['admin','users']){
  const view=setup(name);view.d.querySelectorAll('script').forEach(el=>el.remove());view.d.querySelectorAll('iframe').forEach(el=>el.remove());
  fs.writeFileSync(path.join(fixture,name+'-rendered.html'),view.dom.serialize());view.dom.window.close();
 }
 const pw=new JSDOM(fs.readFileSync(path.join(fixture,'password.html'),'utf8'),{runScripts:'outside-only'});pw.window.eval(fs.readFileSync(path.join(root,'nadinmodule/account.js'),'utf8'));
 const toggle=pw.window.document.querySelector('.nadin-password-visibility input');ok(!!toggle,'password visibility control exists');toggle.checked=true;toggle.dispatchEvent(new pw.window.Event('change'));ok(pw.window.document.querySelectorAll('input[type=password]').length===0,'show password works');toggle.checked=false;toggle.dispatchEvent(new pw.window.Event('change'));ok(pw.window.document.querySelectorAll('input[type=password]').length===3,'hide password restores every field');
 a.dom.window.close();u.dom.window.close();pw.window.close();
 console.log('PASS:',checks,'V12 DOM behavior checks');
})().catch(e=>{console.error(e);process.exit(1);});

/* Run with NODE_PATH pointing to jsdom, after tests/render-preview.py. No real browser or external requests. */
const fs=require('fs'),path=require('path'),assert=require('assert');
const {JSDOM}=require('jsdom');
const fixture=process.argv[2],root=path.resolve(__dirname,'..');let checks=0;
function ok(value,label){assert(value,label);checks++;}
const tick=()=>new Promise(resolve=>setImmediate(resolve));
function setup(name,selected,reviewRows){
 const doc=fs.readFileSync(path.join(fixture,name+'.html'),'utf8');
 const dom=new JSDOM(doc,{url:'https://nadin.example.invalid/index.php',runScripts:'outside-only',pretendToBeVisual:true});
 const w=dom.window,errors=[];w.addEventListener('error',e=>errors.push(e.message));
 if(selected||reviewRows){const data=w.document.getElementById('nadin-admin-data'),boot=JSON.parse(data.textContent);if(selected)Object.assign(boot.selected,selected);if(reviewRows)boot.people.unparsed_rows=reviewRows;data.textContent=JSON.stringify(boot);}
 w.HTMLDialogElement.prototype.showModal=function(){this.open=true;};
 w.HTMLDialogElement.prototype.close=function(value=''){this.returnValue=value;this.open=false;this.dispatchEvent(new w.Event('close'));};
 w.fetch=async()=>{throw new Error('Unexpected network call');};
 w.eval(fs.readFileSync(path.join(root,'nadinmodule/admin.js'),'utf8'));
 return {dom,w,d:w.document,errors};
}
(async()=>{
 const a=setup('admin'),{w,d}=a;
 const boot=JSON.parse(d.getElementById('nadin-admin-data').textContent);
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
 // Headings at the beginning and between tunes must not consume tune numbers.
 const headingTunes=['Aus Gigrep:','Anna_Swings_Best','Berta_Has_The_Blues','David_Funks_The_City_Hall','Evening_In_Bern','Friday_Night_Groove','Challenge Tunes und Neuigkeiten:','Good_Times_Ahead','Late_Night_Swing','Anna_Swings_Best'];
 const orders=doc=>Array.from(doc.querySelectorAll('#setlist [data-row]'),row=>row.querySelector('.nadin-order').textContent);
 const expected=['','1','2','3','4','5','','6','7','8'];
 for(const mode of ['current','archive','trash']){
  const view=setup('admin',{mode,tunes:headingTunes.slice()}),doc=view.d;
  ok(JSON.stringify(orders(doc))===JSON.stringify(expected),mode+': only tunes receive consecutive numbers, including repeated tunes');
  ok(doc.getElementById('setlist-count').textContent==='(8 tunes)',mode+': headings excluded from tune count');
  ok(doc.getElementById('setlist').tagName==='UL',mode+': no automatic ordered-list numbering for headings');
  ok(Array.from(doc.querySelectorAll('.nadin-setlist-note .nadin-setlist-title'),el=>el.textContent).join('|')==='Aus Gigrep:|Challenge Tunes und Neuigkeiten:',mode+': headings contain only their own text');
  ok(!doc.querySelector('.nadin-setlist-note small')&&!doc.getElementById('setlist-warning')&&!doc.getElementById('admin-editor').textContent.includes('unavailable tune'),mode+': no heading warning or error label');
  ok((doc.querySelector('[data-remove]')!==null)===(mode==='current'),mode+': editing controls retain access mode');
  if(mode==='current'){
   ok(doc.querySelectorAll('[data-edit-note]').length===2,'current headings have direct edit buttons');
   doc.querySelector('[data-edit-note="0"]').click();ok(doc.querySelector('[name=note]').value==='Aus Gigrep:','heading edit opens with its complete text');doc.querySelector('[name=note]').value='Discarded';doc.getElementById('admin-dialog').close('cancel');await tick();
   ok(doc.querySelector('#setlist [data-row="0"] .nadin-setlist-title').textContent==='Aus Gigrep:','cancel keeps the original heading');
   doc.querySelector('[data-edit-note="0"]').click();doc.querySelector('[name=note]').value="Set 2: Müller & O'Connor";doc.getElementById('admin-dialog').close('confirm');await tick();
   ok(doc.querySelector('#setlist [data-row="0"] .nadin-setlist-title').textContent==="Set 2: Müller & O'Connor"&&JSON.stringify(orders(doc))===JSON.stringify(expected),'heading edit retains special characters, position and tune numbers');doc.getElementById('setlist-undo').click();
   ok(doc.querySelector('#setlist [data-row="0"] .nadin-setlist-title').textContent==='Aus Gigrep:','undo restores the original heading text');
   doc.querySelector('[data-edit-note="0"]').click();doc.querySelector('[name=note]').value='';doc.getElementById('admin-dialog').close('confirm');await tick();
   ok(orders(doc)[0]===''&&doc.querySelector('#setlist [data-row="0"] .nadin-setlist-title').textContent==='—','heading can become a blank unnumbered break');doc.getElementById('setlist-undo').click();
   doc.querySelector('[data-move="1"][data-index="6"]').click();
   ok(JSON.stringify(orders(doc))===JSON.stringify(['','1','2','3','4','5','6','','7','8']),'moving a heading renumbers only tunes');
   doc.getElementById('setlist-undo').click();ok(JSON.stringify(orders(doc))===JSON.stringify(expected),'undo restores heading positions and tune numbers');
   doc.querySelector('[data-remove="0"]').click();ok(doc.getElementById('setlist-count').textContent==='(8 tunes)'&&orders(doc)[0]==='1','removing a heading preserves tune count and numbering');doc.getElementById('setlist-undo').click();
   doc.querySelector('[data-remove="1"]').click();ok(doc.getElementById('setlist-count').textContent==='(7 tunes)'&&orders(doc).filter(Boolean).join(',')==='1,2,3,4,5,6,7','removing a tune closes the numbering gap');doc.getElementById('setlist-undo').click();
   doc.querySelector('[data-add="Anna_Swings_Best"]').click();ok(orders(doc).filter(Boolean).join(',')==='1,2,3,4,5,6,7,8,9','adding a repeated tune continues numbering');doc.getElementById('setlist-undo').click();
   doc.getElementById('setlist-note').click();doc.querySelector('[name=note]').value='Encore & thanks';doc.getElementById('admin-dialog').close('confirm');await tick();
   ok(doc.querySelector('#setlist li:last-child .nadin-setlist-title').textContent==='Encore & thanks'&&orders(doc).at(-1)===''&&doc.getElementById('setlist-count').textContent==='(8 tunes)','adding a heading preserves its text without number or warning');doc.getElementById('setlist-undo').click();
   let saved;view.w.fetch=async(url,opts)=>{if(opts&&opts.method==='POST'){saved=JSON.parse(opts.body);return {ok:true,json:async()=>({gig:Object.assign({},boot.selected,{tunes:saved.tunes}),gigs:boot.gigs})};}return {ok:true,json:async()=>({csrf:'fixture'})};};
   doc.getElementById('gig-form').dispatchEvent(new view.w.Event('submit',{cancelable:true}));await tick();await tick();
   ok(saved&&JSON.stringify(saved.tunes)===JSON.stringify(headingTunes),'save preserves exact headings, tune IDs, order and repetitions');
   ok(JSON.stringify(orders(doc))===JSON.stringify(expected),'saved response renders headings without numbers');
  }else ok(!doc.querySelector('[data-edit-note]'),mode+': headings remain read-only');
  ok(view.errors.length===0,mode+': heading UI has no uncaught errors');view.dom.window.close();
 }
 const notes=setup('admin',{tunes:['First heading','','Second heading']});
 ok(orders(notes.d).every(n=>n==='')&&notes.d.getElementById('setlist-count').textContent==='(0 tunes)','consecutive headings and blank breaks remain unnumbered');notes.dom.window.close();
 const empty=setup('admin',{tunes:[]});ok(!empty.d.querySelector('#setlist .nadin-order')&&empty.d.getElementById('setlist-count').textContent==='(0 tunes)','empty setlist has no tune numbers');empty.dom.window.close();
 const legacyReview=setup('users',null,[{line:5,text:'Ambiguous, <script>unsafe</script>, vgigs=1,'}]);
 ok(legacyReview.d.getElementById('admin-items').textContent.includes('Line 5: Ambiguous'),'unrecognised account-like rows remain visible for review');
 ok(!legacyReview.d.querySelector('#admin-items script'),'review entries are rendered as text');legacyReview.dom.window.close();
 function drag(view,source,target,after=false){
  const transfer={setData(){},effectAllowed:'',dropEffect:''};
  function event(type){const e=new view.w.Event(type,{bubbles:true,cancelable:true});Object.defineProperties(e,{dataTransfer:{value:transfer},clientY:{value:after?1:-1}});return e;}
  source.dispatchEvent(event('dragstart'));target.dispatchEvent(event('dragover'));target.dispatchEvent(event('drop'));view.d.dispatchEvent(event('dragend'));
 }
 const drop=setup('admin',{tunes:headingTunes.slice()}),dd=drop.d;
 drag(drop,dd.querySelector('[data-catalog-tune="Anna_Swings_Best"]'),dd.querySelector('#setlist [data-row="6"]'));
 ok(orders(dd).filter(Boolean).length===9&&dd.querySelector('#setlist [data-row="6"] .nadin-setlist-title').textContent.includes('Anna'),'library drag inserts a tune before the chosen heading');dd.getElementById('setlist-undo').click();
 drag(drop,dd.querySelector('[data-catalog-tune="Berta_Has_The_Blues"]'),dd.querySelector('#setlist [data-row="0"]'),true);
 ok(dd.querySelector('#setlist [data-row="1"] .nadin-setlist-title').textContent.includes('Berta')&&orders(dd)[0]==='','library drag inserts after a heading without numbering that heading');dd.getElementById('setlist-undo').click();
 drag(drop,dd.querySelector('#setlist [data-row="0"]'),dd.querySelector('#setlist [data-row="2"]'),true);
 ok(dd.querySelector('#setlist [data-row="2"]').classList.contains('nadin-setlist-note')&&orders(dd).filter(Boolean).join(',')==='1,2,3,4,5,6,7,8','setlist drag still reorders headings and keeps tune numbering');dd.getElementById('setlist-undo').click();
 ok(JSON.stringify(orders(dd))===JSON.stringify(expected),'undo restores a dragged setlist');
 ok(!dd.querySelector('.nadin-drop-before,.nadin-drop-after,.nadin-drop-target'),'drop indicators clear after dropping');drop.dom.window.close();
 const blank=setup('admin',{tunes:[]});drag(blank,blank.d.querySelector('[data-catalog-tune="Anna_Swings_Best"]'),blank.d.getElementById('setlist'));
 ok(orders(blank.d).join(',')==='1','library tune can be dropped into an empty setlist');blank.dom.window.close();
 const readonly=setup('admin',{mode:'archive',tunes:headingTunes.slice()});
 ok(readonly.d.querySelectorAll('[data-catalog-tune][draggable=true]').length===0,'library drag is disabled for archived gigs');readonly.dom.window.close();
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

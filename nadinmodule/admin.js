(function () {
'use strict';
const boot=JSON.parse(document.getElementById('nadin-admin-data').textContent);
const people=boot.area==='people', editor=document.getElementById('admin-editor'), list=document.getElementById('admin-items');
const status=document.getElementById('admin-status'), error=document.getElementById('admin-error');
const dialog=document.getElementById('admin-dialog'), dialogForm=document.getElementById('admin-dialog-form');
document.getElementById('dialog-cancel').addEventListener('click',()=>dialog.close('cancel'));
let rows=people?boot.people.rows:boot.gigs, mode='current', current=null, dirty=false, busy=false, undo=[];
const catalog=boot.catalog||[], catalogMap=new Map(catalog.map(t=>[t.id,t]));
const h=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const normal=s=>String(s).normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/_/g,' ');
const copy=v=>JSON.parse(JSON.stringify(v));
const message=s=>{status.textContent=s;};
function fail(e){error.hidden=false;error.textContent=e.message||String(e);message('Not saved');}
function mark(){dirty=true;message('Unsaved changes');const b=document.getElementById('admin-save');if(b)b.classList.add('nadin-unsaved');document.getElementById('changes').value='1';}
function clean(text){dirty=false;document.getElementById('changes').value='0';message(text||'Saved');error.hidden=true;}
async function request(data,write=false,file=null){
 if(busy)throw new Error('Please wait for the current action.');
 busy=true;error.hidden=true;document.getElementById('nadin-admin').setAttribute('aria-busy','true');
 const controls=Array.from(document.querySelectorAll('#nadin-admin input,#nadin-admin button,#nadin-admin textarea,#nadin-admin select')).map(el=>[el,el.disabled]);controls.forEach(([el])=>el.disabled=true);
 const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),file?120000:30000);
 try{
  const endpoint='nadinmodule/admin-api.php';
  if(write){const tokenResponse=await fetch(endpoint+'?area='+boot.area+'&action=token',{cache:'no-store',signal:controller.signal});const token=await tokenResponse.json();if(!tokenResponse.ok)throw new Error(token.error);boot.csrf=token.csrf;}
  let url=endpoint, options={cache:'no-store'};
  if(write){data=Object.assign({area:boot.area,csrf:boot.csrf},data);if(file){const body=new FormData();Object.entries(data).forEach(([k,v])=>body.append(k,v));body.append('pdf',file);options={method:'POST',body};}else options={method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)};}
  else url+='?'+new URLSearchParams(Object.assign({area:boot.area},data));
  options.signal=controller.signal;const response=await fetch(url,options);let result;try{result=await response.json();}catch(e){throw new Error('The server did not return a valid response. Your work is still here.');}
  if(!response.ok)throw new Error(result.error||'The action failed.');return result;
 }catch(e){if(e.name==='AbortError')throw new Error('No response from the server. Your changes are still here. Reload to check whether they were saved before retrying.');throw e;}finally{clearTimeout(timer);busy=false;controls.forEach(([el,disabled])=>{if(el.isConnected)el.disabled=disabled;});document.getElementById('nadin-admin').removeAttribute('aria-busy');}
}
function confirmDialog(title,body,confirmText='Continue'){
 return new Promise(resolve=>{
  document.getElementById('dialog-title').textContent=title;document.getElementById('dialog-fields').innerHTML=body;
  document.getElementById('dialog-confirm').textContent=confirmText;dialog.returnValue='';
  dialog.addEventListener('close',()=>resolve(dialog.returnValue==='confirm'?Object.fromEntries(new FormData(dialogForm)):null),{once:true});dialog.showModal();
 });
}
async function canLeave(){if(!dirty)return true;return !!await confirmDialog('Unsaved changes','<p>Discard your unsaved changes and continue?</p>','Discard changes');}
function listRows(){
 const q=normal(document.getElementById('admin-search').value);
 const found=rows.filter(r=>normal(people?r.name+' '+r.email:r.title).includes(q));
 list.innerHTML=found.map(r=>{const id=people?r.email:r.id,selected=current&&(people?current.email===id:current.id===id);return '<button type="button" data-select="'+h(id)+'" '+(selected?'aria-current="true"':'')+'><span>'+h(people?r.name:r.title)+'</span>'+(people?'<small>'+h(r.email)+' · '+(r.expired?'Expired':r.rights.includes('musers')?'People admin':r.rights.includes('mgigs')?'Gig manager':'Member')+'</small>':'')+'</button>';}).join('')||'<p class="nadin-hint">'+(q?'No matches.':'No entries yet.')+'</p>';
}
async function select(id){
 if(busy||!await canLeave())return;
 try{if(people){const result=await request({action:'list'});rows=result.rows;boot.people=result;const person=rows.find(r=>r.email===id);if(!person)throw new Error('This person was removed. Reload the list.');current=copy(person);}else{const r=await request({action:'gig',mode,id});current=r.gig;}undo=[];clean('Saved');renderEditor();listRows();}catch(e){fail(e);}
}
list.addEventListener('click',e=>{const b=e.target.closest('[data-select]');if(b)select(b.dataset.select);});
document.getElementById('admin-search').addEventListener('input',listRows);
document.getElementById('admin-new').addEventListener('click',async()=>{
 if(busy||!await canLeave())return;
 if(!people&&mode!=='current'){try{const result=await request({mode:'current'});rows=result.gigs;}catch(e){fail(e);return;}}
 const date=new Date();date.setMinutes(date.getMinutes()-date.getTimezoneOffset());
 current=people?{name:'',email:'',expiry:'2099-12-31',rights:['vgigs','vreclist'],revision:'',original:''}:{id:'',mode:'current',title:'',date:date.toISOString().slice(0,10),infos:'',tunes:[],revision:'',attachment:false};
 if(!people){mode='current';document.getElementById('gig-mode').value=mode;}
 undo=[];clean('New — not saved');renderEditor();listRows();const input=editor.querySelector('input:not([type=hidden])');if(input)input.focus();
});
function toolbar(extra=''){return '<div class="nadin-toolbar nadin-editor-toolbar"><button type="submit" id="admin-save" class="nadin-primary">Save</button>'+extra+'<button type="button" data-reload>Reload</button></div>';}
function renderEditor(){if(people)renderPerson();else renderGig();}
function renderGig(){
 const editable=current.mode==='current',fresh=!current.id;
 editor.innerHTML='<form id="gig-form">'+(editable?toolbar(fresh?'':'<a href="'+h(current.view_url)+'" target="_blank" rel="noopener">View gig ↗</a>'):'<div class="nadin-toolbar"><button type="button" data-gig-action="restore" class="nadin-primary">Restore gig</button></div>')+
 (fresh?'<div class="nadin-fields"><label>Date<input type="date" name="date" required value="'+h(current.date)+'"></label><label>Gig name<input name="title" maxlength="120" required placeholder="e.g. Autumn concert" value=""></label></div>':'<h4>'+h(current.title)+'</h4>')+
 (!fresh?'<div class="nadin-toolbar nadin-secondary-actions">'+(current.mode!=='trash'?'<button type="button" data-gig-action="copy">Copy</button>':'')+(editable?'<button type="button" data-gig-action="rename">Rename</button><button type="button" data-gig-action="archive">Archive</button>':'')+(current.mode!=='trash'?'<button type="button" data-gig-action="trash">Delete…</button>':'<span class="nadin-hint">Deleted gigs can be restored.</span>')+'</div>':'')+
 '<details class="nadin-gig-info"'+(fresh?' open':'')+'><summary>Gig information'+(current.attachment?' · PDF attached':'')+'</summary><label class="nadin-sr-only" for="gig-infos">Gig information</label><textarea id="gig-infos" name="infos" rows="4" '+(!editable?'readonly':'')+' placeholder="Meeting time, location, dress code…">'+h(current.infos)+'</textarea>'+
 (current.attachment&&current.mode!=='trash'?'<a href="'+h(current.attachment_url)+'" target="_blank" rel="noopener">Open PDF attachment ↗</a> ':'')+
 (editable&&!fresh?'<label class="nadin-upload-label">'+(current.attachment?'Replace':'Add')+' PDF <input type="file" id="gig-pdf" accept="application/pdf,.pdf"></label><span class="nadin-hint">Save your setlist before uploading.</span>':'')+'</details>'+
 '<div class="nadin-toolbar"><h4>Setlist <small id="setlist-count"></small></h4>'+(editable?'<button type="button" id="setlist-undo" disabled>Undo</button><button type="button" id="setlist-note">+ Heading / break</button>':'')+'</div><ol id="setlist" class="nadin-setlist"></ol><p id="setlist-warning" class="nadin-hint"></p></form>';
 if(editable){
  document.getElementById('gig-form').addEventListener('submit',saveGig);
  editor.querySelectorAll('input:not([type=file]),textarea').forEach(el=>el.addEventListener('input',mark));
  document.getElementById('setlist-undo').addEventListener('click',()=>{if(!undo.length)return;current.tunes=undo.pop();mark();renderSetlist();});
  document.getElementById('setlist-note').addEventListener('click',async()=>{const r=await confirmDialog('Heading or break','<label>Text<input name="note" required maxlength="200" placeholder="e.g. Set 2 / Break"></label>','Add');if(r){remember();current.tunes.push(r.note);mark();renderSetlist();}});
  const upload=document.getElementById('gig-pdf');if(upload)upload.addEventListener('change',async()=>{if(!upload.files[0])return;if(dirty){fail(new Error('Save the gig before attaching a PDF.'));upload.value='';return;}try{const r=await request({action:'attachment',id:current.id,revision:current.revision},true,upload.files[0]);current=r.gig;clean('PDF saved');renderGig();}catch(e){fail(e);upload.value='';}});
 }
 renderSetlist();renderCatalog();
}
function remember(){undo.push(current.tunes.slice());if(undo.length>50)undo.shift();}
function renderSetlist(){
 const editable=current.mode==='current';
 document.getElementById('setlist-count').textContent='('+current.tunes.filter(t=>catalogMap.has(t)).length+' tunes)';
 document.getElementById('setlist').innerHTML=current.tunes.map((t,i)=>{const item=catalogMap.get(t);return '<li data-row="'+i+'" '+(editable?'draggable="true"':'')+' class="'+(item?'':'nadin-setlist-note')+'"><span class="nadin-order">'+(i+1)+'</span><span class="nadin-setlist-title">'+h(item?item.title:t||'—')+(!item?'<small>Heading / unavailable tune</small>':'')+'</span>'+(editable?'<span class="nadin-row-actions"><button type="button" data-move="-1" data-index="'+i+'" aria-label="Move '+h(t)+' up" '+(i===0?'disabled':'')+'>↑</button><button type="button" data-move="1" data-index="'+i+'" aria-label="Move '+h(t)+' down" '+(i===current.tunes.length-1?'disabled':'')+'>↓</button><button type="button" data-remove="'+i+'" aria-label="Remove '+h(t)+'">×</button></span>':'')+'</li>';}).join('')||'<li class="nadin-empty">Add tunes from the library. Their order here is the playing order.</li>';
 const un=document.getElementById('setlist-undo');if(un)un.disabled=!undo.length;
 document.getElementById('setlist-warning').textContent=current.tunes.some(t=>!catalogMap.has(t))?'Headings and breaks are kept. Check entries that should be tunes but are missing from the library.':'';
 renderCatalog();
}
function renderCatalog(){
 const q=normal(document.getElementById('catalog-search').value),counts=new Map();if(current)(current.tunes||[]).forEach(t=>counts.set(t,(counts.get(t)||0)+1));
 document.getElementById('catalog-items').innerHTML=catalog.filter(t=>normal(t.title).includes(q)).map(t=>'<div class="nadin-catalog-row"><span><strong>'+h(t.title)+'</strong><small>'+h(t.files.join(' · ')||'No media yet')+(counts.has(t.id)?' · In setlist'+(counts.get(t.id)>1?' ×'+counts.get(t.id):''):'')+'</small></span><button type="button" data-add="'+h(t.id)+'" aria-label="Add '+h(t.title)+(counts.has(t.id)?' again':'')+'" title="'+(counts.has(t.id)?'Add again':'Add to setlist')+'" '+(!current||current.mode!=='current'?'disabled':'')+'>+</button></div>').join('')||'<p class="nadin-hint">No matching tunes.</p>';
}
if(!people){
 document.getElementById('catalog-search').addEventListener('input',renderCatalog);
 document.getElementById('catalog-items').addEventListener('click',e=>{const b=e.target.closest('[data-add]');if(b&&current&&current.mode==='current'&&!busy){remember();current.tunes.push(b.dataset.add);mark();renderSetlist();}});
 document.getElementById('gig-mode').addEventListener('change',async e=>{const value=e.target.value;if(!await canLeave()){e.target.value=mode;return;}try{const r=await request({mode:value});rows=r.gigs;mode=value;current=null;clean('Ready');editor.innerHTML='<p>Select a gig.</p>';listRows();renderCatalog();}catch(err){e.target.value=mode;fail(err);}});
}
async function saveGig(e){
 e.preventDefault();if(busy)return;const form=e.currentTarget;if(!form.reportValidity())return;
 const fields=Object.fromEntries(new FormData(form));message('Saving…');
 try{const r=await request(Object.assign({},current,fields,{action:'save'}),true);current=r.gig;rows=r.gigs;undo=[];clean('Saved');renderGig();listRows();}catch(err){fail(err);}
}
async function gigAction(action){
 if(dirty){fail(new Error('Save your changes before '+action+'; use Reload to discard them.'));return;}
 let data={action,id:current.id,mode:current.mode,revision:current.revision};
 if(action==='copy'||action==='rename'){
  const match=current.title.match(/^(\d{4}-\d{2}-\d{2})[ _](.*)$/),date=match?match[1]:new Date().toISOString().slice(0,10),name=match?match[2]:current.title;
  const r=await confirmDialog(action==='copy'?'Copy gig':'Rename gig','<label>Date<input type="date" name="date" required value="'+h(date)+'"></label><label>Gig name<input name="title" required maxlength="120" value="'+h(name+(action==='copy'?' Copy':''))+'"></label>',action==='copy'?'Create copy':'Rename');if(!r)return;Object.assign(data,r);
 }else{const r=await confirmDialog(action==='trash'?'Delete gig?':action==='archive'?'Archive gig?':'Restore gig?','<p>'+h(current.title)+'</p><p>'+(action==='trash'?'The gig and its PDF attachment move to Deleted. You can restore them.':action==='archive'?'The gig leaves the current list. It remains in the archive.':'The gig returns to the current list.')+'</p>',action==='trash'?'Move to Deleted':action==='archive'?'Archive':'Restore');if(!r)return;}
 try{const r=await request(data,true);current=r.gig;rows=r.gigs;mode=current.mode;document.getElementById('gig-mode').value=mode;clean('Done');renderGig();listRows();}catch(e){fail(e);}
}
const sections=['vsaxes','vbones','vtrumpets','vrhythm','vother','vscore'];
function rightsGroup(title,codes){return '<fieldset><legend>'+h(title)+'</legend>'+codes.map(code=>'<label class="nadin-check"><input type="checkbox" name="rights" value="'+code+'" '+(current.rights.includes(code)?'checked':'')+'>'+h(boot.rights[code])+'</label>').join('')+'</fieldset>';}
function renderPerson(){
 const existing=!!current.revision;current.original=existing?current.email:'';
 editor.innerHTML='<form id="person-form">'+toolbar(existing?'<button type="button" id="person-delete" '+(current.email===boot.actor?'disabled':'')+'>Remove person…</button>':'')+
 '<div class="nadin-fields"><label>Name<input name="name" required maxlength="160" autocomplete="off" value="'+h(current.name)+'"></label><label>Email<input type="email" name="email" required autocomplete="off" value="'+h(current.email)+'" '+(existing?'readonly':'')+'></label></div>'+
 (existing?'<p class="nadin-hint">The email identifies this account. To change it, add the new address as a new person.</p>':'<p class="nadin-hint">After saving, this person can request their start password on the login page. No password needs to be chosen here.</p>')+
 '<div class="nadin-toolbar nadin-expiry"><label class="nadin-check"><input type="checkbox" id="has-expiry" '+(current.expiry!=='2099-12-31'?'checked':'')+'>Temporary access</label><label id="expiry-label" '+(current.expiry==='2099-12-31'?'hidden':'')+'>Expires <input type="date" name="expiry" value="'+h(current.expiry)+'" required></label><small id="expiry-hint" '+(current.expiry==='2099-12-31'?'hidden':'')+'>Access ends at 00:00 on this date.</small></div>'+
 '<div class="nadin-toolbar nadin-presets"><span>Start with:</span><button type="button" data-preset="member">Band member</button><button type="button" data-preset="substitute">Substitute</button><button type="button" data-preset="librarian">Librarian</button><button type="button" data-preset="leader">Band leader</button><button type="button" data-preset="admin">Administrator</button></div><p class="nadin-hint">Templates fill the permissions below; nothing is saved until you click Save. Select the required instrument sections.</p>'+
 '<div class="nadin-rights">'+rightsGroup('Music & parts',sections)+rightsGroup('Browse',['vgigs','vreclist','varchiv','vlibrary'])+rightsGroup('Manage',['upaudio','uppdf','mgigs','musers'])+'</div><p id="rights-summary" class="nadin-rights-summary"></p><p class="nadin-hint">Unsplit Parts follow Current gigs. PDF management includes tune info & audio; managing tunes or gigs requires current gigs and library access. A personal password stays unchanged when permissions change.</p>'+(current.email===boot.actor?'<p class="nadin-hint">Your own account must stay active with permission to manage people.</p>':'')+'</form>';
 const extra=current.rights.filter(code=>!Object.prototype.hasOwnProperty.call(boot.rights,code));if(extra.length){const note=document.createElement('p');note.className='nadin-hint';note.textContent='Additional site permissions, preserved: '+extra.join(', ');editor.appendChild(note);}
 document.getElementById('person-form').addEventListener('submit',savePerson);
 editor.querySelectorAll('input').forEach(el=>el.addEventListener('input',mark));
 document.getElementById('has-expiry').addEventListener('change',e=>{document.getElementById('expiry-label').hidden=!e.target.checked;document.getElementById('expiry-hint').hidden=!e.target.checked;if(e.target.checked&&editor.querySelector('[name=expiry]').value==='2099-12-31'){const date=new Date();date.setDate(date.getDate()+30);editor.querySelector('[name=expiry]').value=date.toISOString().slice(0,10);}mark();});
 editor.querySelectorAll('[name=rights]').forEach(el=>el.addEventListener('change',()=>{dependencies(el.value,el.checked);rightsSummary();}));
 editor.querySelectorAll('[data-preset]').forEach(el=>el.addEventListener('click',()=>preset(el.dataset.preset)));
 const del=document.getElementById('person-delete');if(del)del.addEventListener('click',deletePerson);rightsSummary();
 if(boot.people.unparsed){error.hidden=false;error.textContent='Some legacy user lines could not be displayed. They are preserved unchanged. Check userdatei.txt before editing those entries.';}
}
function right(code){return editor.querySelector('[name=rights][value="'+code+'"]');}
function dependencies(code,checked){
 if(checked){if(code==='uppdf')right('upaudio').checked=true;if(['uppdf','upaudio','mgigs'].includes(code)){right('vlibrary').checked=true;right('vgigs').checked=true;}}
 else if((['vlibrary','vgigs'].includes(code)&&(right('upaudio').checked||right('mgigs').checked||right('uppdf').checked))||(code==='upaudio'&&right('uppdf').checked)){right(code).checked=true;message('This permission is required by the selected management permissions.');}
}
function rightsSummary(){const selected=Array.from(editor.querySelectorAll('[name=rights]:checked')).map(el=>el.value);const instruments=sections.filter(c=>selected.includes(c)).map(c=>boot.rights[c]);document.getElementById('rights-summary').textContent='Music access: '+(instruments.join(', ')||'no section selected')+(selected.includes('vgigs')?' + unsplit Parts':'')+'. '+(selected.includes('musers')?'Can manage people and permissions.':'');}
function preset(type){
 let rights=['vgigs','vreclist'];if(type==='librarian')rights.push('vlibrary','varchiv','upaudio','uppdf');if(type==='leader')rights.push('vlibrary','varchiv','mgigs','vscore');if(type==='admin')rights=Object.keys(boot.rights);
 if(type!=='admin')sections.forEach(c=>{if(right(c).checked&&!rights.includes(c))rights.push(c);});if(current.email===boot.actor&&!rights.includes('musers'))rights.push('musers');
 editor.querySelectorAll('[name=rights]').forEach(el=>el.checked=rights.includes(el.value));
 if(type==='substitute'){document.getElementById('has-expiry').checked=true;document.getElementById('has-expiry').dispatchEvent(new Event('change'));}
 mark();rightsSummary();
}
async function savePerson(e){
 e.preventDefault();if(busy||!e.currentTarget.reportValidity())return;const fields=Object.fromEntries(new FormData(e.currentTarget));fields.rights=Array.from(editor.querySelectorAll('[name=rights]:checked')).map(el=>el.value);if(!document.getElementById('has-expiry').checked)fields.expiry='2099-12-31';
 try{const r=await request(Object.assign({},fields,{action:'save',original:current.original,revision:current.revision}),true);rows=r.people.rows;boot.people=r.people;current=copy(rows.find(p=>p.email===r.email));clean('Person saved');renderPerson();listRows();}catch(err){fail(err);}
}
async function deletePerson(){
 const r=await confirmDialog('Remove person?','<p>'+h(current.name)+' · '+h(current.email)+'</p><p>Access and all sessions end immediately. Their personal password will be removed. Adding the person again starts a new account.</p>','Remove person');if(!r)return;
 try{const result=await request({action:'delete',original:current.original,revision:current.revision},true);rows=result.people.rows;boot.people=result.people;current=null;clean('Person removed');editor.innerHTML='<p>Select a person.</p>';listRows();}catch(e){fail(e);}
}
editor.addEventListener('click',async e=>{
 const reload=e.target.closest('[data-reload]');if(reload){if(current&&(people?current.revision:current.id))select(people?current.email:current.id);return;}
 const action=e.target.closest('[data-gig-action]');if(action){gigAction(action.dataset.gigAction);return;}
 const move=e.target.closest('[data-move]'),remove=e.target.closest('[data-remove]');if((move||remove)&&!busy){remember();if(remove)current.tunes.splice(Number(remove.dataset.remove),1);else{const from=Number(move.dataset.index),to=from+Number(move.dataset.move);const [t]=current.tunes.splice(from,1);current.tunes.splice(to,0,t);}mark();renderSetlist();const next=remove?editor.querySelector('[data-remove="'+Math.min(Number(remove.dataset.remove),current.tunes.length-1)+'"]'):editor.querySelector('[data-move="'+move.dataset.move+'"][data-index="'+(Number(move.dataset.index)+Number(move.dataset.move))+'"]');if(next&&!next.disabled)next.focus();}
});
let dragged=null;
editor.addEventListener('dragstart',e=>{const row=e.target.closest('[data-row]');if(row&&!busy){dragged=Number(row.dataset.row);e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',String(dragged));}});
editor.addEventListener('dragover',e=>{if(dragged!==null&&e.target.closest('[data-row]'))e.preventDefault();});
editor.addEventListener('drop',e=>{const row=e.target.closest('[data-row]');if(dragged===null||!row||busy)return;e.preventDefault();const target=Number(row.dataset.row);if(target!==dragged){remember();const [t]=current.tunes.splice(dragged,1);current.tunes.splice(target,0,t);mark();renderSetlist();}dragged=null;});
editor.addEventListener('dragend',()=>dragged=null);
window.addEventListener('beforeunload',e=>{if(dirty){e.preventDefault();e.returnValue='';}});
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'&&!dialog.open){e.preventDefault();const form=editor.querySelector('form');if(form&&!busy)form.requestSubmit();}});
listRows();if(!people)renderCatalog();
if(boot.selected){current=boot.selected;renderEditor();listRows();}else if(rows.length)select(people?rows[0].email:rows[0].id);
}());

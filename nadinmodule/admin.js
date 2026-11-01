(function () {
'use strict';
const boot=JSON.parse(document.getElementById('nadin-admin-data').textContent);
const people=boot.area==='people', editor=document.getElementById('admin-editor'), list=document.getElementById('admin-items');
const status=document.getElementById('admin-status'), error=document.getElementById('admin-error');
const dialog=document.getElementById('admin-dialog'), dialogForm=document.getElementById('admin-dialog-form');
document.getElementById('dialog-cancel').addEventListener('click',()=>dialog.close('cancel'));
document.getElementById('dialog-save').addEventListener('click',()=>dialog.close('save'));
let rows=people?boot.people.rows:boot.gigs, mode='current', current=null, dirty=false, busy=false, undo=[], textView=false, pendingAttachment=null;
const workspace=document.getElementById('nadin-admin'),narrow=window.matchMedia?window.matchMedia('(max-width:1000px)'):{matches:false};
function pane(name,focus=false){workspace.dataset.pane=name;document.querySelectorAll('[data-workspace-pane]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.workspacePane===name)));if(focus&&narrow.matches){const panel=document.getElementById(name==='list'?'admin-browser-panel':name==='catalog'?'admin-catalog-panel':'admin-editor');const target=panel.querySelector('input:not([type=hidden]),h4,button')||panel;if(!target.matches('input,button'))target.tabIndex=-1;target.focus({preventScroll:true});}}
document.querySelectorAll('[data-workspace-pane]').forEach(b=>b.addEventListener('click',()=>pane(b.dataset.workspacePane)));
pane(boot.startPane||'editor');
const catalog=boot.catalog||[], catalogMap=new Map(catalog.map(t=>[t.id,t]));
const h=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const normal=s=>String(s).normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/_/g,' ');
const copy=v=>JSON.parse(JSON.stringify(v));
const message=s=>{status.textContent=s;};
function fail(e){error.hidden=false;error.textContent=e.message||String(e);message('Not saved');status.dataset.state='error';if(current&&editor.querySelector('form'))pane('editor');error.focus();}
function mark(){dirty=true;message('Unsaved changes');status.dataset.state='dirty';const b=document.getElementById('admin-save');if(b)b.classList.add('nadin-unsaved');document.getElementById('changes').value='1';contextLabel();}
function clean(text){dirty=false;document.getElementById('changes').value=pendingAttachment?'1':'0';message((text||'Saved')+(pendingAttachment?' · PDF not uploaded':''));status.dataset.state=pendingAttachment||text&&text.includes('not saved')?'new':'saved';error.hidden=true;}
function valid(form){const result=window.NadinTask?NadinTask.validate(form,error):form.reportValidity();if(!result)pane('editor');return result;}
function announce(text){document.getElementById('admin-action-status').textContent=text;}
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
function confirmDialog(title,body,confirmText='Continue',options={}){
 return new Promise(resolve=>{
  const invoker=document.activeElement;
  document.getElementById('dialog-title').textContent=title;document.getElementById('dialog-fields').innerHTML=body;
  const confirm=document.getElementById('dialog-confirm'),cancel=document.getElementById('dialog-cancel');confirm.textContent=confirmText;confirm.className=options.danger?'nadin-danger':'nadin-primary';cancel.textContent=options.save?'Keep editing':'Cancel';document.getElementById('dialog-save').hidden=!options.save;document.getElementById('dialog-save').textContent=options.saveText||'Save and continue';dialog.returnValue='';
  dialog.addEventListener('close',()=>{const result=dialog.returnValue==='confirm'?Object.fromEntries(new FormData(dialogForm)):dialog.returnValue==='save'?{_decision:'save'}:null;if(invoker&&invoker.isConnected)invoker.focus({preventScroll:true});resolve(result);},{once:true});dialog.showModal();
  const field=dialog.querySelector('input:not([type=hidden]),textarea,select');if(field){field.focus();if(field.type==='text')field.select();}else(options.danger||options.save?cancel:confirm).focus();
 });
}
async function uploadAttachment(){
 if(!pendingAttachment||busy)return false;
 try{const r=await request({action:'attachment',id:current.id,revision:current.revision},true,pendingAttachment);pendingAttachment=null;current=r.gig;clean('PDF saved');renderGig();return true;}catch(e){fail(e);return false;}
}
async function canLeave(){
 if(!dirty&&!pendingAttachment)return true;
 const result=await confirmDialog('Unsaved changes','<p>Your changes are still in the editor.</p>'+(pendingAttachment?'<p>Selected PDF: '+h(pendingAttachment.name)+' — not uploaded yet.</p>':''),'Discard changes',{danger:true,save:true,saveText:pendingAttachment?'Save / upload and continue':'Save and continue'});
 if(!result)return false;if(result._decision==='save'){const form=editor.querySelector('form');if(!form)return false;if(dirty)await(people?savePerson:saveGig)({preventDefault(){},currentTarget:form});if(dirty)return false;if(pendingAttachment&&!await uploadAttachment())return false;return true;}pendingAttachment=null;return true;
}

function listRows(){
 const q=normal(document.getElementById('admin-search').value);
 const filter=people?document.getElementById('people-filter').value:'all';
 const found=rows.filter(r=>normal(people?r.name+' '+r.email:r.title).includes(q)&&(!people||filter==='all'||filter==='active'&&!r.expired||filter==='expired'&&r.expired||filter==='managers'&&(r.rights.includes('musers')||r.rights.includes('mgigs'))));
 document.getElementById('admin-count').textContent=found.length+' of '+rows.length+(people?' people':' gigs');
 list.innerHTML=found.map(r=>{const id=people?r.email:r.id,selected=current&&(people?current.email===id:current.id===id);return '<button type="button" data-select="'+h(id)+'" '+(selected?'aria-current="true"':'')+'><span>'+h(people?r.name:r.title)+'</span>'+(people?'<small>'+h(r.email)+' · '+(r.expired?'Expired':r.rights.includes('musers')?'People admin':r.rights.includes('mgigs')?'Gig manager':'Member')+'</small>':'')+'</button>';}).join('')||'<p class="nadin-hint">'+(q?'No matches.':'No entries yet.')+'</p>';
 if(people&&boot.people.unparsed_rows&&boot.people.unparsed_rows.length){const review=document.createElement('details'),summary=document.createElement('summary');review.className='nadin-hint';summary.textContent='Entries to review ('+boot.people.unparsed_rows.length+')';review.appendChild(summary);boot.people.unparsed_rows.forEach(row=>{const p=document.createElement('p');p.textContent='Line '+row.line+': '+row.text;review.appendChild(p);});list.appendChild(review);}
}
async function select(id){
 if(busy||!await canLeave())return;
 try{if(people){const result=await request({action:'list'});rows=result.rows;boot.people=result;const person=rows.find(r=>r.email===id);if(!person)throw new Error('This person was removed. Reload the list.');current=copy(person);}else{const r=await request({action:'gig',mode,id});current=r.gig;}undo=[];pendingAttachment=null;clean('Saved');renderEditor();listRows();pane('editor',true);}catch(e){fail(e);}
}
list.addEventListener('click',e=>{const b=e.target.closest('[data-select]');if(b)select(b.dataset.select);});
document.getElementById('admin-search').addEventListener('input',listRows);
if(people)document.getElementById('people-filter').addEventListener('change',listRows);
document.getElementById('admin-new').addEventListener('click',async()=>{
 if(busy||!await canLeave())return;
 if(!people&&mode!=='current'){try{const result=await request({mode:'current'});rows=result.gigs;}catch(e){fail(e);return;}}
 const date=new Date();date.setMinutes(date.getMinutes()-date.getTimezoneOffset());
 current=people?{name:'',email:'',expiry:'2099-12-31',rights:['vgigs','vreclist'],revision:'',original:''}:{id:'',mode:'current',title:'',date:date.toISOString().slice(0,10),infos:'',tunes:[],revision:'',attachment:false};
 if(!people){mode='current';document.getElementById('gig-mode').value=mode;}
 undo=[];pendingAttachment=null;clean('New — not saved');renderEditor();listRows();pane('editor');const input=editor.querySelector('input:not([type=hidden])');if(input)input.focus();
});
function toolbar(extra=''){return '<div class="nadin-toolbar nadin-editor-toolbar"><button type="submit" id="admin-save" class="nadin-primary">Save</button>'+extra+'<button type="button" data-reload>Reload</button></div>';}
function contextLabel(){const el=document.getElementById('admin-context');let label='';if(current){if(people)label=current.email||editor.querySelector('[name=name]')?.value||'New person';else label=current.id?current.title:editor.querySelector('[name=title]')?.value||'New gig';}el.textContent=label;el.title=label;workspace.dataset.selection=label?'true':'false';}
function renderEditor(){if(people)renderPerson();else renderGig();contextLabel();}
function renderGig(){
 const editable=current.mode==='current',fresh=!current.id,oldInfo=editor.querySelector('.nadin-gig-info'),infoOpen=oldInfo?oldInfo.open:fresh;
 editor.innerHTML='<form id="gig-form">'+(editable?toolbar(fresh?'':'<a href="'+h(current.view_url)+'" target="_blank" rel="noopener">View gig ↗</a>'):'<div class="nadin-toolbar"><button type="button" data-gig-action="restore" class="nadin-primary">Restore gig</button></div>')+
 (fresh?'<div class="nadin-fields"><label>Date<input type="date" name="date" required value="'+h(current.date)+'"></label><label>Gig name<input name="title" maxlength="120" required placeholder="e.g. Autumn concert" value=""></label></div>':'<h4>'+h(current.title)+'</h4>')+
 (!fresh?'<div class="nadin-toolbar nadin-secondary-actions">'+(current.mode!=='trash'?'<button type="button" data-gig-action="copy">Copy</button><details class="nadin-more-actions"><summary>More actions</summary><div>':'')+(editable?'<button type="button" data-gig-action="rename">Rename</button><button type="button" data-gig-action="archive">Archive</button>':'')+(current.mode!=='trash'?'<button type="button" class="nadin-danger-quiet" data-gig-action="trash">Move to Deleted…</button></div></details>':'<span class="nadin-hint">Deleted gigs can be restored.</span>')+'</div>':'')+
 '<details class="nadin-gig-info"'+(infoOpen?' open':'')+'><summary>Gig information'+(current.attachment?' · PDF attached':'')+'</summary><label class="nadin-sr-only" for="gig-infos">Gig information</label><textarea id="gig-infos" name="infos" rows="4" '+(!editable?'readonly':'')+' placeholder="Meeting time, location, dress code…">'+h(current.infos)+'</textarea>'+
 (current.attachment&&current.mode!=='trash'?'<a href="'+h(current.attachment_url)+'" target="_blank" rel="noopener">Open PDF attachment ↗</a> ':'')+
 (editable&&!fresh?'<div class="nadin-gig-attachment"><label class="nadin-upload-label" for="gig-pdf">'+(current.attachment?'Replace':'Add')+' PDF attachment</label><input type="file" id="gig-pdf" accept="application/pdf,.pdf"><span id="gig-pdf-selection" class="nadin-hint">'+h(pendingAttachment?pendingAttachment.name:'Choosing a file does not upload it.')+'</span><button type="button" id="gig-pdf-upload" '+(!pendingAttachment?'disabled':'')+'>Upload PDF</button></div>':'')+'</details>'+
 '<div class="nadin-toolbar"><h4>Setlist <small id="setlist-count"></small></h4>'+(editable?'<button type="button" id="setlist-undo" title="Undo the last setlist change" disabled>Undo</button><button type="button" id="setlist-note">+ Heading / break</button>':'')+'<button type="button" id="setlist-text-toggle" aria-controls="setlist-text-panel" aria-expanded="'+textView+'">Text view</button></div><div id="setlist-text-panel" '+(!textView?'hidden':'')+'><label for="setlist-text">Setlist as text</label><textarea id="setlist-text" rows="14" spellcheck="false" '+(!editable?'readonly':'')+' aria-describedby="setlist-text-hint"></textarea><p id="setlist-text-hint" class="nadin-hint">One stored library name or heading per line. Blank lines are breaks. Order and repeated tunes are kept.</p></div><ul id="setlist" class="nadin-setlist" '+(textView?'hidden':'')+'></ul></form>';
 const gigForm=document.getElementById('gig-form');gigForm.noValidate=true;
 document.getElementById('setlist-text-toggle').addEventListener('click',()=>{textView=!textView;document.getElementById('setlist-text-panel').hidden=!textView;document.getElementById('setlist').hidden=textView;document.getElementById('setlist-text-toggle').setAttribute('aria-expanded',String(textView));if(textView)document.getElementById('setlist-text').focus();});
 if(editable){const text=document.getElementById('setlist-text');let textSnapshot=false;text.addEventListener('focus',()=>textSnapshot=false);text.addEventListener('input',()=>{if(!textSnapshot){remember();textSnapshot=true;}current.tunes=text.value.replace(/\r/g,'').split('\n');mark();renderSetlist();});}
 if(editable){
  document.getElementById('gig-form').addEventListener('submit',saveGig);
  editor.querySelectorAll('input:not([type=file]),textarea').forEach(el=>el.addEventListener('input',mark));
  document.getElementById('setlist-undo').addEventListener('click',()=>{if(!undo.length)return;current.tunes=undo.pop();mark();renderSetlist();});
  document.getElementById('setlist-note').addEventListener('click',async()=>{const r=await confirmDialog('Heading or break','<label>Text (leave empty for a break)<input name="note" maxlength="200" placeholder="e.g. Set 2 / Break"></label>','Add');if(r){remember();current.tunes.push(r.note);mark();renderSetlist();}});
  const upload=document.getElementById('gig-pdf');if(upload){upload.addEventListener('change',()=>{pendingAttachment=upload.files[0]||null;document.getElementById('gig-pdf-selection').textContent=pendingAttachment?pendingAttachment.name:'Choosing a file does not upload it.';document.getElementById('gig-pdf-upload').disabled=!pendingAttachment;document.getElementById('changes').value=dirty||pendingAttachment?'1':'0';if(pendingAttachment){message('PDF selected — not uploaded');status.dataset.state='new';}});document.getElementById('gig-pdf-upload').addEventListener('click',async()=>{if(!pendingAttachment||busy)return;if(dirty){const choice=await confirmDialog('Save before uploading?','<p>The setlist and information changes need to be saved before this PDF is attached.</p>','Save and upload');if(!choice)return;await saveGig({preventDefault(){},currentTarget:document.getElementById('gig-form')});if(dirty)return;}await uploadAttachment();});}

 }
 renderSetlist();renderCatalog();contextLabel();
}
function remember(){undo.push(current.tunes.slice());if(undo.length>50)undo.shift();}
function renderSetlist(){
 const editable=current.mode==='current';
 const text=document.getElementById('setlist-text');if(text&&document.activeElement!==text)text.value=current.tunes.join('\n');
 const count=document.getElementById('workspace-tune-count');if(count)count.textContent='('+current.tunes.filter(t=>catalogMap.has(t)).length+')';
 let tuneNumber=0;
 document.getElementById('setlist-count').textContent='('+current.tunes.filter(t=>catalogMap.has(t)).length+' tunes)';
 document.getElementById('setlist').innerHTML=current.tunes.map((t,i)=>{const item=catalogMap.get(t);return '<li data-row="'+i+'" '+(editable?'draggable="true"':'')+' class="'+(item?'':'nadin-setlist-note')+'"><span class="nadin-order">'+(item?++tuneNumber:'')+'</span><span class="nadin-setlist-title">'+h(item?item.title:t||'—')+'</span>'+(editable?'<span class="nadin-row-actions">'+(!item?'<button type="button" data-edit-note="'+i+'" aria-label="Edit heading '+h(t)+'" title="Edit heading or break">✎</button>':'')+'<button type="button" data-move="-1" data-index="'+i+'" aria-label="Move '+h(t)+' up" '+(i===0?'disabled':'')+'>↑</button><button type="button" data-move="1" data-index="'+i+'" aria-label="Move '+h(t)+' down" '+(i===current.tunes.length-1?'disabled':'')+'>↓</button><button type="button" data-remove="'+i+'" aria-label="Remove '+h(t)+'">×</button></span>':'')+'</li>';}).join('')||'<li class="nadin-empty">Add tunes from the library. Their order here is the playing order.</li>';
 const un=document.getElementById('setlist-undo');if(un)un.disabled=!undo.length;
 renderCatalog();
}
function renderCatalog(){
 const q=normal(document.getElementById('catalog-search').value),counts=new Map();if(current)(current.tunes||[]).forEach(t=>counts.set(t,(counts.get(t)||0)+1));
 const editable=current&&current.mode==='current';
 document.getElementById('catalog-count').textContent=catalog.filter(t=>normal(t.title).includes(q)).length+' tunes'+(!current?' · Select a current gig first':current.mode!=='current'?' · Restore this gig to edit':'');
 document.getElementById('catalog-items').innerHTML=catalog.filter(t=>normal(t.title).includes(q)).map(t=>'<div class="nadin-catalog-row" data-catalog-tune="'+h(t.id)+'" draggable="'+!!editable+'"><span><strong>'+h(t.title)+'</strong><small>'+h(t.files.join(' · ')||'No media yet')+(counts.has(t.id)?' · In setlist'+(counts.get(t.id)>1?' ×'+counts.get(t.id):''):'')+'</small></span><button type="button" data-add="'+h(t.id)+'" aria-label="Add '+h(t.title)+(counts.has(t.id)?' again':'')+'" title="'+(counts.has(t.id)?'Add again':'Add to setlist')+'" '+(!editable?'disabled':'')+'>+</button></div>').join('')||'<p class="nadin-hint">No matching tunes.</p>';
}
if(!people){
 document.getElementById('catalog-search').addEventListener('input',renderCatalog);
 document.getElementById('catalog-items').addEventListener('click',e=>{const b=e.target.closest('[data-add]');if(b&&current&&current.mode==='current'&&!busy){const id=b.dataset.add,focused=document.activeElement===b;remember();current.tunes.push(id);mark();renderSetlist();announce('Added '+id.replace(/_/g,' ')+'. '+current.tunes.filter(t=>catalogMap.has(t)).length+' tunes in this draft.');if(focused){const next=Array.from(document.querySelectorAll('[data-add]')).find(x=>x.dataset.add===id);if(next)next.focus({preventScroll:true});}}});
 document.getElementById('catalog-items').addEventListener('dragstart',e=>{const row=e.target.closest('[data-catalog-tune]');if(!row||!current||current.mode!=='current'||busy){e.preventDefault();return;}dragged={tune:row.dataset.catalogTune};e.dataTransfer.effectAllowed='copy';e.dataTransfer.setData('text/plain',row.dataset.catalogTune);});
 document.getElementById('gig-mode').addEventListener('change',async e=>{const value=e.target.value;if(!await canLeave()){e.target.value=mode;return;}try{const r=await request({mode:value});rows=r.gigs;mode=value;current=null;pendingAttachment=null;clean('Ready');contextLabel();editor.innerHTML='<p>Select a gig.</p>';listRows();renderCatalog();}catch(err){e.target.value=mode;fail(err);}});
}
async function saveGig(e){
 e.preventDefault();if(busy)return;const form=e.currentTarget;if(!valid(form))return;
 const fields=Object.fromEntries(new FormData(form));message('Saving…');
 try{const r=await request(Object.assign({},current,fields,{action:'save'}),true);current=r.gig;rows=r.gigs;undo=[];clean('Saved');renderGig();listRows();document.getElementById('admin-save').focus({preventScroll:true});}catch(err){fail(err);}
}
async function gigAction(action){
 if(!await canLeave())return;
 let data={action,id:current.id,mode:current.mode,revision:current.revision};
 if(action==='copy'||action==='rename'){
  const match=current.title.match(/^(\d{4}-\d{2}-\d{2})[ _](.*)$/),date=match?match[1]:new Date().toISOString().slice(0,10),name=match?match[2]:current.title;
  const r=await confirmDialog(action==='copy'?'Copy gig':'Rename gig','<label>Date<input type="date" name="date" required value="'+h(date)+'"></label><label>Gig name<input name="title" required maxlength="120" value="'+h(name+(action==='copy'?' Copy':''))+'"></label>',action==='copy'?'Create copy':'Rename');if(!r)return;Object.assign(data,r);
 }else{const r=await confirmDialog(action==='trash'?'Delete gig?':action==='archive'?'Archive gig?':'Restore gig?','<p>'+h(current.title)+'</p><p>'+(action==='trash'?'The gig and its PDF attachment move to Deleted. You can restore them.':action==='archive'?'The gig leaves the current list. It remains in the archive.':'The gig returns to the current list.')+'</p>',action==='trash'?'Move to Deleted':action==='archive'?'Archive':'Restore',{danger:action==='trash'});if(!r)return;}
 try{const r=await request(data,true);current=r.gig;rows=r.gigs;mode=current.mode;document.getElementById('gig-mode').value=mode;clean('Done');renderGig();listRows();}catch(e){fail(e);}
}
const sections=['vsaxes','vbones','vtrumpets','vrhythm','vother','vscore'];
function rightsGroup(title,codes){return '<fieldset><legend>'+h(title)+'</legend>'+codes.map(code=>'<label class="nadin-check"><input type="checkbox" name="rights" value="'+code+'" '+(current.rights.includes(code)?'checked':'')+'>'+h(boot.rights[code])+'</label>').join('')+'</fieldset>';}
function renderPerson(){
 const existing=!!current.revision;current.original=existing?current.email:'';
 editor.innerHTML='<form id="person-form">'+toolbar(existing?'<details class="nadin-more-actions"><summary>Account actions</summary><div><button type="button" class="nadin-danger-quiet" id="person-delete" '+(current.email===boot.actor?'disabled':'')+'>Remove person…</button></div></details>':'')+
 '<div class="nadin-fields"><label>Name<input name="name" required maxlength="160" autocomplete="off" value="'+h(current.name)+'"></label><label>Email<input type="email" name="email" required autocomplete="off" value="'+h(current.email)+'" '+(existing?'readonly':'')+'></label></div>'+
 (existing?'<p class="nadin-hint">The email identifies this account. To change it, add the new address as a new person.</p>':'<p class="nadin-hint">After saving, this person can request their start password on the login page. No password needs to be chosen here.</p>')+
 '<div class="nadin-toolbar nadin-expiry"><label class="nadin-check"><input type="checkbox" id="has-expiry" '+(current.expiry!=='2099-12-31'?'checked':'')+'>Temporary access</label><label id="expiry-label" '+(current.expiry==='2099-12-31'?'hidden':'')+'>Expires <input type="date" name="expiry" value="'+h(current.expiry)+'" required></label><small id="expiry-hint" '+(current.expiry==='2099-12-31'?'hidden':'')+'>Access ends at 00:00 on this date.</small></div>'+
 '<div class="nadin-toolbar nadin-presets"><span>Start with:</span><button type="button" data-preset="member">Band member</button><button type="button" data-preset="substitute">Substitute</button><button type="button" data-preset="librarian">Librarian</button><button type="button" data-preset="leader">Band leader</button><button type="button" data-preset="admin">Administrator</button></div><p class="nadin-hint">Templates fill the permissions below; nothing is saved until you click Save. Select the required instrument sections.</p>'+
 '<div class="nadin-rights">'+rightsGroup('Music & parts',sections)+rightsGroup('Browse',['vgigs','vreclist','varchiv','vlibrary'])+rightsGroup('Manage',['upaudio','uppdf','mgigs','musers'])+'</div><p id="rights-summary" class="nadin-rights-summary"></p><p class="nadin-hint">Unsplit Parts follow Current gigs. PDF management includes tune info & audio; managing tunes or gigs requires current gigs and library access. A personal password stays unchanged when permissions change.</p>'+(current.email===boot.actor?'<p class="nadin-hint">Your own account must stay active with permission to manage people.</p>':'')+'</form>';
 const extra=current.rights.filter(code=>!Object.prototype.hasOwnProperty.call(boot.rights,code));if(extra.length){const note=document.createElement('p');note.className='nadin-hint';note.textContent='Additional site permissions, preserved: '+extra.join(', ');editor.appendChild(note);}
 document.getElementById('person-form').noValidate=true;document.getElementById('person-form').addEventListener('submit',savePerson);
 editor.querySelectorAll('input').forEach(el=>el.addEventListener('input',mark));
 document.getElementById('has-expiry').addEventListener('change',e=>{document.getElementById('expiry-label').hidden=!e.target.checked;document.getElementById('expiry-hint').hidden=!e.target.checked;if(e.target.checked&&editor.querySelector('[name=expiry]').value==='2099-12-31'){const date=new Date();date.setDate(date.getDate()+30);editor.querySelector('[name=expiry]').value=date.toISOString().slice(0,10);}mark();});
 editor.querySelectorAll('[name=rights]').forEach(el=>el.addEventListener('change',()=>{dependencies(el.value,el.checked);rightsSummary();}));
 editor.querySelectorAll('[data-preset]').forEach(el=>el.addEventListener('click',()=>preset(el.dataset.preset)));
 const del=document.getElementById('person-delete');if(del)del.addEventListener('click',deletePerson);rightsSummary();contextLabel();
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
 e.preventDefault();if(busy||!valid(e.currentTarget))return;const fields=Object.fromEntries(new FormData(e.currentTarget));fields.rights=Array.from(editor.querySelectorAll('[name=rights]:checked')).map(el=>el.value);if(!document.getElementById('has-expiry').checked)fields.expiry='2099-12-31';
 try{const r=await request(Object.assign({},fields,{action:'save',original:current.original,revision:current.revision}),true);rows=r.people.rows;boot.people=r.people;current=copy(rows.find(p=>p.email===r.email));clean('Person saved');renderPerson();listRows();document.getElementById('admin-save').focus({preventScroll:true});}catch(err){fail(err);}
}
async function deletePerson(){
 const r=await confirmDialog('Remove person?','<p>'+h(current.name)+' · '+h(current.email)+'</p><p>Access and all sessions end immediately. Their personal password will be removed. Adding the person again starts a new account.</p>'+(dirty?'<p>Your unsaved edits will also be discarded.</p>':''),'Remove person',{danger:true});if(!r)return;
 try{const result=await request({action:'delete',original:current.original,revision:current.revision},true);rows=result.people.rows;boot.people=result.people;current=null;clean('Person removed');contextLabel();editor.innerHTML='<p>Select a person.</p>';listRows();}catch(e){fail(e);}
}
editor.addEventListener('click',async e=>{
 const editNote=e.target.closest('[data-edit-note]');if(editNote&&!busy&&current&&current.mode==='current'){const index=Number(editNote.dataset.editNote),before=current.tunes[index];const r=await confirmDialog('Edit heading or break','<label>Text (leave empty for a break)<input name="note" maxlength="200" value="'+h(before)+'"></label>','Apply');if(r&&r.note!==before){remember();current.tunes[index]=r.note;mark();renderSetlist();const button=editor.querySelector('[data-edit-note="'+index+'"]');if(button)button.focus();}return;}
 const reload=e.target.closest('[data-reload]');if(reload){if(current&&(people?current.revision:current.id))select(people?current.email:current.id);return;}
 const action=e.target.closest('[data-gig-action]');if(action){gigAction(action.dataset.gigAction);return;}
 const move=e.target.closest('[data-move]'),remove=e.target.closest('[data-remove]');if((move||remove)&&!busy){remember();if(remove)current.tunes.splice(Number(remove.dataset.remove),1);else{const from=Number(move.dataset.index),to=from+Number(move.dataset.move);const [t]=current.tunes.splice(from,1);current.tunes.splice(to,0,t);}mark();renderSetlist();const next=remove?editor.querySelector('[data-remove="'+Math.min(Number(remove.dataset.remove),current.tunes.length-1)+'"]'):editor.querySelector('[data-move="'+move.dataset.move+'"][data-index="'+(Number(move.dataset.index)+Number(move.dataset.move))+'"]');if(next&&!next.disabled)next.focus();else{const fallback=editor.querySelector('#setlist button:not(:disabled)')||document.getElementById('setlist-note');if(fallback)fallback.focus();}announce('Setlist changed. '+current.tunes.filter(t=>catalogMap.has(t)).length+' tunes; changes are not saved yet.');}
});
let dragged=null;
function clearDropTarget(){editor.querySelectorAll('.nadin-drop-before,.nadin-drop-after,.nadin-drop-target').forEach(el=>el.classList.remove('nadin-drop-before','nadin-drop-after','nadin-drop-target'));}
function dropPosition(e){
 const target=e.target.closest('#setlist');if(!target||dragged===null||busy||!current||current.mode!=='current')return null;
 const row=e.target.closest('[data-row]'),after=row&&e.clientY>=row.getBoundingClientRect().top+row.getBoundingClientRect().height/2;
 return {target,row,after,index:row?Number(row.dataset.row)+(after?1:0):current.tunes.length};
}
editor.addEventListener('dragstart',e=>{const row=e.target.closest('[data-row]');if(row&&current&&current.mode==='current'&&!busy){dragged={index:Number(row.dataset.row)};e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',String(dragged.index));}});
editor.addEventListener('dragover',e=>{const position=dropPosition(e);clearDropTarget();if(!position)return;e.preventDefault();e.dataTransfer.dropEffect=dragged.tune?'copy':'move';if(position.row)position.row.classList.add(position.after?'nadin-drop-after':'nadin-drop-before');else position.target.classList.add('nadin-drop-target');});
editor.addEventListener('dragleave',e=>{if(!editor.contains(e.relatedTarget))clearDropTarget();});
editor.addEventListener('drop',e=>{
 const position=dropPosition(e);clearDropTarget();if(!position)return;e.preventDefault();const source=dragged;dragged=null;let index=position.index;
 if(source.tune){if(!catalogMap.has(source.tune))return;remember();current.tunes.splice(index,0,source.tune);}
 else{if(source.index<index)index--;if(source.index===index)return;remember();const [t]=current.tunes.splice(source.index,1);current.tunes.splice(index,0,t);}
 mark();renderSetlist();
});
document.addEventListener('dragend',()=>{dragged=null;clearDropTarget();});
window.addEventListener('beforeunload',e=>{if(dirty||pendingAttachment){e.preventDefault();e.returnValue='';}});
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'&&!dialog.open){e.preventDefault();const form=editor.querySelector('form');if(form&&!busy)form.requestSubmit();}});
listRows();if(!people)renderCatalog();
if(boot.selected){current=boot.selected;clean('Saved');renderEditor();listRows();}else if(rows.length)select(people?rows[0].email:rows[0].id);
}());

/* Shared behaviour for the existing same-origin NADIN work windows. */
(function(){
 'use strict';
 const asset=document.currentScript.src,tokenURL=new URL('media-token.php',asset);
 const forms=new Set();let leaving=false,channel;
 const viewKey='nadin-view:'+location.pathname+':'+(new URLSearchParams(location.search).get('m')||'gigreps');
 try{channel=new BroadcastChannel('nadin-tasks:'+new URL('../',asset).pathname);}catch(e){}
 const box=(id,role)=>{let el=document.getElementById(id);if(!el){el=document.createElement('div');el.id=id;el.setAttribute('role',role);el.tabIndex=-1;document.body.prepend(el);}return el;};
 function report(text,error=false){const el=box(error?'nadin-task-error':'nadin-task-status',error?'alert':'status');el.textContent=text;el.hidden=!text;if(error&&text)el.focus();}
 function clearErrors(form){
  form.querySelectorAll('.nadin-field-error').forEach(el=>el.remove());
  form.querySelectorAll('[aria-invalid]').forEach(el=>{el.removeAttribute('aria-invalid');const ids=(el.getAttribute('aria-describedby')||'').split(' ').filter(id=>!id.startsWith('nadin-error-'));if(ids.length)el.setAttribute('aria-describedby',ids.join(' '));else el.removeAttribute('aria-describedby');});
 }
 function validate(form,errorElement){
  clearErrors(form);const invalid=Array.from(form.elements).filter(el=>el.willValidate&&!el.validity.valid);if(!invalid.length)return true;
  const summary=errorElement||box('nadin-task-error','alert');summary.hidden=false;summary.tabIndex=-1;summary.replaceChildren();
  const heading=document.createElement('h3');heading.textContent='Check the highlighted fields';summary.appendChild(heading);const list=document.createElement('ul');summary.appendChild(list);
  invalid.forEach((field,i)=>{if(!field.id)field.id='nadin-field-'+i;const id='nadin-error-'+field.id;const hint=document.createElement('span');hint.id=id;hint.className='nadin-field-error';hint.textContent=field.validationMessage;field.insertAdjacentElement('afterend',hint);field.setAttribute('aria-invalid','true');field.setAttribute('aria-describedby',((field.getAttribute('aria-describedby')||'')+' '+id).trim());const item=document.createElement('li'),link=document.createElement('a');link.href='#'+field.id;link.textContent=(field.labels&&field.labels[0]?field.labels[0].childNodes[0].textContent.trim()+': ':'')+field.validationMessage;link.addEventListener('click',e=>{e.preventDefault();field.focus();});item.appendChild(link);list.appendChild(item);});summary.focus();return false;
 }
 async function send(url,data){
  const target=new URL(url,location.href);if(target.origin!==location.origin)throw new Error('Use a file editor on this NADIN installation.');
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),120000);
  try{
   const auth=await fetch(tokenURL,{cache:'no-store',credentials:'same-origin',signal:controller.signal}),session=await auth.json();if(!auth.ok)throw new Error(session.error||'Please sign in in another tab, then retry.');
   data.set('csrf',session.csrf);data.set('_nadin_async','1');
   const response=await fetch(target,{method:'POST',body:data,headers:{Accept:'application/json'},credentials:'same-origin',cache:'no-store',signal:controller.signal});let reply;
   try{reply=await response.json();}catch(e){throw new Error('The server did not return a complete result. Your input is still here. Check the file list before retrying.');}
   if(!response.ok||!reply.ok)throw new Error(reply.message||reply.error||'The action could not be completed.');return reply;
  }catch(e){if(e.name==='AbortError')throw new Error('No confirmed result from the server. Your input is still here. Check whether the action finished before retrying.');throw e;}finally{clearTimeout(timer);}
 }
 function dirty(form,value){form.dataset.dirty=value?'true':'false';const any=Array.from(forms).some(f=>f.dataset.dirty==='true');const marker=document.getElementById('changes');if(marker)marker.value=any?'1':'0';window.changes=any?1:0;form.querySelectorAll('button[type=submit]').forEach(b=>b.classList.toggle('nadin-unsaved',value&&form.dataset.nadinAsync==='notes'));if(value)report('Unsaved changes');}
 function notifyOpener(){try{if(opener&&!opener.closed&&typeof opener.fresh==='function'){opener.fresh();return;}}catch(e){}if(channel)channel.postMessage({type:'media-changed'});}
 function rememberView(){
  try{const details=Array.from(document.querySelectorAll('[data-nadin-disclosure]')).map(b=>({tune:b.closest('tr').querySelector('h5').id,kind:b.dataset.nadinDisclosure,open:b.getAttribute('aria-expanded')==='true'}));const filters={};['nadin-tune-search','nadin-gig-search'].forEach(id=>{const el=document.getElementById(id);if(el)filters[id]=el.value;});const marks=Array.from(document.querySelectorAll('h5[id]')).filter(el=>el.style.backgroundColor).map(el=>[el.id,el.style.backgroundColor]);sessionStorage.setItem(viewKey,JSON.stringify({details,filters,marks,y:scrollY}));}catch(e){}
 }
 function restoreView(){
  if(!document.body.classList.contains('nadin-page'))return;
  try{const saved=sessionStorage.getItem(viewKey);if(!saved)return;sessionStorage.removeItem(viewKey);const state=JSON.parse(saved);window.NadinTask.restored=true;Object.entries(state.filters||{}).forEach(([id,value])=>{const el=document.getElementById(id);if(el){el.value=value;el.dispatchEvent(new Event('input',{bubbles:true}));}});document.querySelectorAll('[data-nadin-disclosure]').forEach(b=>{const title=b.closest('tr').querySelector('h5'),entry=state.details.find(d=>d.tune===title.id&&d.kind===b.dataset.nadinDisclosure);if(entry&&entry.open){b.setAttribute('aria-expanded','true');document.getElementById(b.getAttribute('aria-controls')).style.display='block';}});(state.marks||[]).forEach(([id,color])=>{const el=document.getElementById(id);if(el)el.style.backgroundColor=color;});requestAnimationFrame(()=>scrollTo(0,state.y||0));}catch(e){}
 }
 function refreshFiles(){document.querySelectorAll('iframe[data-nadin-file-list]').forEach(frame=>{const src=frame.src.replace(/([?&])_refresh=\d+(&|$)/g,(_,start,end)=>end?start:'').replace(/[?&]$/,'');frame.src=src+(src.includes('?')?'&':'?')+'_refresh='+Date.now();});}
 async function saveForm(form){
  if(form.dataset.busy==='true')return false;
  const file=form.querySelector('input[type=file]');if(file){file.setCustomValidity('');const selected=Array.from(file.files),max=Number(form.dataset.maxFile)||Infinity,total=Number(form.dataset.maxTotal)||Infinity,count=Number(form.dataset.maxFiles)||Infinity;if(selected.length>count)file.setCustomValidity('Choose no more than '+count+' files.');else if(selected.some(f=>f.size>max))file.setCustomValidity('One selected file exceeds the per-file limit shown below.');else if(selected.reduce((sum,f)=>sum+f.size,0)>total)file.setCustomValidity('The selected files exceed the total upload limit shown below.');}
  if(!validate(form))return false;
  box('nadin-task-error','alert').hidden=true;form.dataset.busy='true';form.setAttribute('aria-busy','true');
  const data=new FormData(form),focused=document.activeElement;
  const controls=Array.from(form.querySelectorAll('button,input,textarea,select')).map(el=>[el,el.disabled]);controls.forEach(([el])=>el.disabled=true);
  report(form.dataset.nadinAsync==='upload'?'Uploading… Keep this window open.':'Saving…');
  try{
   const reply=await send(form.action,data);
   if(reply.revision&&form.elements.revision)form.elements.revision.value=reply.revision;
   dirty(form,false);report(reply.message);form.dataset.saved='true';
   if(file){form.reset();const selection=form.querySelector('[data-upload-selection]');if(selection)selection.textContent='';refreshFiles();}
   if(form.dataset.nadinAsync==='complete'&&reply.next_url){
    const next=document.createElement('a');next.href=reply.next_url;next.className='nadin-button-link nadin-primary';next.textContent=reply.next_label||'Continue editing';form.insertAdjacentElement('afterend',next);form.dataset.complete='true';form.querySelectorAll('input,textarea,button').forEach(el=>el.disabled=true);next.focus();
   }
   notifyOpener();return true;
  }catch(e){report(e.message,true);return false;}finally{form.dataset.busy='false';form.removeAttribute('aria-busy');if(form.dataset.complete!=='true'){controls.forEach(([el,disabled])=>el.disabled=disabled);if(focused&&focused.isConnected&&document.activeElement===document.body)focused.focus({preventScroll:true});}}
 }
 function leaveChoice(canSave){return new Promise(resolve=>{
  const dialog=document.createElement('dialog');dialog.className='nadin-dialog';dialog.setAttribute('aria-labelledby','task-leave-title');
  dialog.innerHTML='<form method="dialog"><h3 id="task-leave-title">Unsaved changes</h3><p>Your changes are still in this window.</p><div class="nadin-dialog-actions"><button value="cancel" autofocus>Keep editing</button>'+(canSave?'<button value="save" class="nadin-primary">Save and continue</button>':'')+'<button value="discard" class="nadin-danger">Discard changes</button></div></form>';
  dialog.addEventListener('close',()=>{const value=dialog.returnValue;dialog.remove();resolve(value);},{once:true});document.body.appendChild(dialog);dialog.showModal();
 });}
 async function canLeave(){
  const changed=Array.from(forms).filter(f=>f.dataset.dirty==='true');if(!changed.length)return true;
  if(changed.some(f=>f.dataset.busy==='true')){report('Wait for the current upload or save to finish.',true);return false;}
  const choice=await leaveChoice(changed.every(f=>f.dataset.nadinAsync==='notes'));
  if(choice==='discard'){changed.forEach(f=>dirty(f,false));return true;}
  if(choice==='save'){for(const form of changed)if(!await saveForm(form))return false;return true;}return false;
 }
 function fitFrames(){document.querySelectorAll('iframe[data-nadin-auto-size]').forEach(frame=>{
  let observer;function fit(){try{const doc=frame.contentDocument;if(!doc||!doc.body)return;const view=frame.contentWindow,styles=view.getComputedStyle(doc.body);const height=Math.ceil(Math.max(doc.body.scrollHeight,doc.body.getBoundingClientRect().height)+parseFloat(styles.marginTop||0)+parseFloat(styles.marginBottom||0)+3);if(Math.abs(frame.getBoundingClientRect().height-height)>2)frame.style.height=Math.max(48,height)+'px';}catch(e){/* Only the existing same-origin file lists are resized. */}}
  function watch(){if(observer)observer.disconnect();try{if(frame.contentDocument&&frame.contentDocument.body&&window.ResizeObserver){observer=new ResizeObserver(()=>requestAnimationFrame(fit));observer.observe(frame.contentDocument.body);}fit();}catch(e){}}
  frame.addEventListener('load',watch);watch();
 });}
 function init(){
  document.querySelectorAll('form[data-nadin-async]').forEach(form=>{
   forms.add(form);form.noValidate=true;form.dataset.dirty='false';form.addEventListener('submit',e=>{e.preventDefault();saveForm(form);});
   form.addEventListener('input',e=>{if(e.target.type!=='hidden')dirty(form,true);if(e.target.hasAttribute('aria-invalid')){e.target.removeAttribute('aria-invalid');const hint=document.getElementById('nadin-error-'+e.target.id);if(hint)hint.remove();}});
   const file=form.querySelector('input[type=file]');if(file)file.addEventListener('change',()=>{file.setCustomValidity('');dirty(form,file.files.length>0);const selection=form.querySelector('[data-upload-selection]');if(selection)selection.textContent=Array.from(file.files).map(f=>f.name+' ('+Math.ceil(f.size/1024)+' kB)').join(' · ');});
  });
  document.addEventListener('click',async e=>{
   const close=e.target.closest('[data-nadin-close]');if(close){e.preventDefault();if(!await canLeave())return;leaving=true;window.close();setTimeout(()=>report('Use Back to NADIN if this tab was opened directly.'),100);return;}
   const link=e.target.closest('a[data-nadin-window]');if(link&&!e.ctrlKey&&!e.metaKey&&!e.shiftKey&&!matchMedia('(max-width:700px)').matches){const separate=/(?:^|;\s*)multipop=yes(?:;|$)/.test(document.cookie),name=separate?'_blank':(link.dataset.nadinWindow||'_blank').replace(/^manage\d+$/,'manage');const opened=window.open(link.href,name,'scrollbars=yes,resizable=yes,width=900,height='+Math.min(900,screen.availHeight));if(opened){e.preventDefault();opened.focus();}return;}
   const disclosure=e.target.closest('[data-nadin-disclosure]');if(disclosure){const target=document.getElementById(disclosure.getAttribute('aria-controls'));if(target){const open=disclosure.getAttribute('aria-expanded')!=='true';target.style.display=open?'block':'none';disclosure.setAttribute('aria-expanded',String(open));}return;}
   const nav=e.target.closest('.nadin-popup-nav a,.nadin-popup-brand');if(nav&&Array.from(forms).some(f=>f.dataset.dirty==='true')){e.preventDefault();if(await canLeave()){leaving=true;location.href=nav.href;}}
   document.querySelectorAll('.nadin-more-actions[open],.nadin-task-options[open]').forEach(menu=>{if(!menu.contains(e.target))menu.open=false;});
  });
  document.addEventListener('keydown',e=>{if(document.querySelector('dialog[open]'))return;if(e.key==='Escape')document.querySelectorAll('.nadin-more-actions[open],.nadin-task-options[open]').forEach(menu=>{menu.open=false;menu.querySelector('summary').focus();});if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){const form=document.querySelector('form[data-nadin-async="notes"]');if(form){e.preventDefault();saveForm(form);}}});
  window.addEventListener('beforeunload',e=>{if(!leaving&&Array.from(forms).some(f=>f.dataset.dirty==='true'||f.dataset.busy==='true')){e.preventDefault();e.returnValue='';}});
  const filter=document.getElementById('nadin-media-search');if(filter)filter.addEventListener('input',()=>{const q=filter.value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();let count=0;document.querySelectorAll('.nadin-media-table tbody tr').forEach(row=>{row.hidden=!row.cells[0].textContent.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().includes(q);if(!row.hidden)count++;});const result=document.getElementById('nadin-media-count');result.textContent=count+' '+(result.dataset.countLabel||'files');});
  fitFrames();restoreView();
  if(channel&&document.body.classList.contains('nadin-page'))channel.addEventListener('message',e=>{if(e.data&&e.data.type==='media-changed'&&typeof window.fresh==='function')window.fresh();});
  if(document.body.dataset.nadinAutoClose==='yes'&&window.opener)setInterval(()=>{try{if(opener.closed&&!Array.from(forms).some(f=>f.dataset.dirty==='true'||f.dataset.busy==='true'))window.close();}catch(e){}},1000);
 }
 window.NadinTask={send,report,validate,clearErrors,notifyOpener,refreshFiles,canLeave,rememberView,restored:false};
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());

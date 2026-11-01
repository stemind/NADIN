/* A failed file action keeps the proposed name and the server message together. */
(function(){
 'use strict';
 window.nadinMediaDialog=function(button,done){return new Promise(resolve=>{
  const rename=!!button.dataset.rename;let doc=document;
  try{if(parent!==window&&parent.document.body.classList.contains('nadin-popup'))doc=parent.document;}catch(e){}
  const dialog=doc.createElement('dialog');dialog.className='nadin-dialog';dialog.setAttribute('aria-labelledby','media-dialog-title');
  if(typeof dialog.showModal!=='function'){const value=rename?prompt('New file name (including extension):',button.dataset.name):confirm('Move '+button.dataset.name+' to deleted files?');if(rename?value===null||!value.trim():!value){resolve(false);return;}Promise.resolve(done(rename?value:null)).then(()=>resolve(true),err=>{if(window.NadinTask)NadinTask.report(err.message,true);resolve(false);});return;}
  const form=doc.createElement('form');form.method='dialog';const title=doc.createElement('h3');title.id='media-dialog-title';title.textContent=rename?'Rename file':'Move file to deleted files?';form.appendChild(title);
  const name=doc.createElement('p');name.className='nadin-dialog-subject';name.textContent=button.dataset.name;form.appendChild(name);
  const problem=doc.createElement('p');problem.id='media-dialog-error';problem.setAttribute('role','alert');problem.tabIndex=-1;problem.hidden=true;form.appendChild(problem);
  let field;
  if(rename){const label=doc.createElement('label');label.textContent='File name (including extension)';field=doc.createElement('input');field.name='filename';field.type='text';field.value=button.dataset.name;field.required=true;field.addEventListener('input',()=>field.setCustomValidity(field.value.trim()?'':'Enter a file name.'));label.appendChild(field);form.appendChild(label);}
  else{const message=doc.createElement('p');message.textContent='The file is removed from the tune or gig. It is retained in deleted files for administrator recovery.';form.appendChild(message);}
  const actions=doc.createElement('div');actions.className='nadin-dialog-actions';const cancel=doc.createElement('button');cancel.type='button';cancel.textContent='Cancel';cancel.addEventListener('click',()=>dialog.close());
  const confirm=doc.createElement('button');confirm.type='submit';confirm.className=rename?'nadin-primary':'nadin-danger';confirm.textContent=rename?'Rename file':'Move to deleted files';actions.append(cancel,confirm);form.appendChild(actions);dialog.appendChild(form);
  let busy=false,changed=false;
  form.addEventListener('submit',async e=>{e.preventDefault();if(busy||!form.reportValidity())return;busy=true;problem.hidden=true;confirm.disabled=cancel.disabled=true;dialog.setAttribute('aria-busy','true');try{await done(field?field.value:null);changed=true;dialog.close();}catch(err){problem.textContent=err.message||String(err);problem.hidden=false;if(field){field.setAttribute('aria-invalid','true');field.setAttribute('aria-describedby','media-dialog-error');}problem.focus();}finally{busy=false;confirm.disabled=cancel.disabled=false;dialog.removeAttribute('aria-busy');}});
  dialog.addEventListener('cancel',e=>{if(busy)e.preventDefault();});
  dialog.addEventListener('close',()=>{dialog.remove();if(button.isConnected)button.focus();resolve(changed);},{once:true});
  doc.body.appendChild(dialog);dialog.showModal();if(field){field.focus();field.select();}else cancel.focus();
 });};
}());

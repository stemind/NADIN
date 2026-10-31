/* Software DOM verification of automatic native form submission. */
const assert=require('assert'),fs=require('fs'),{JSDOM}=require('jsdom');
const input=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));let checks=0;
function ok(value,label){assert(value,label);checks++;}
function page(html){const d=new JSDOM(html,{url:'http://fixture.invalid/nadin/get/forscore.php?m=gigreps&c=Headings.txt',runScripts:'outside-only'}),w=d.window,calls=[];w.scrollTo=()=>{};const form=w.document.getElementById('nadin-forscore-form');form.requestSubmit=button=>calls.push({name:button.name,value:button.value,novalidate:button.formNoValidate});w.eval(fs.readFileSync(process.env.NADIN_FORSCORE_JS,'utf8'));return{d,w,form,calls};}
for(const html of [input.blank,input.first,input.update]){
 const p=page(html),{w,form,calls}=p,review=form.querySelector('[data-review]'),download=form.querySelector('[value=download]'),status=form.querySelector('[data-review-status]');
 ok(review.hidden,'manual refresh is hidden only once the automatic handler is active');
 ok(form.elements.workflow.required,'a download requires one explicit import choice');
 ok(!form.hasAttribute('aria-busy')&&!download.disabled,'initial page is interactive');
 const profile=form.elements.profile;profile.value='parts';profile.dispatchEvent(new w.Event('change',{bubbles:true}));
 ok(calls.length===1&&calls[0].value==='review'&&calls[0].novalidate,'register change automatically refreshes even before an import option is chosen');
 ok(form.getAttribute('aria-busy')==='true'&&download.disabled&&!status.hidden,'stale download is unavailable during the refresh');
 ok(new w.FormData(form).has('profile')&&new w.FormData(form).has('csrf'),'refresh does not disable fields needed for the native POST');
 w.dispatchEvent(new w.Event('pageshow'));ok(!download.disabled&&!form.hasAttribute('aria-busy')&&status.hidden,'back/forward restores usable controls');
 form.elements.workflow.value='update';form.elements.workflow.dispatchEvent(new w.Event('change',{bubbles:true}));
 ok(calls.length===2&&!form.querySelector('[data-inventory-panel]').hidden,'inventory option reveals the uploader and refreshes automatically');
 w.dispatchEvent(new w.Event('pageshow'));
 const csv=form.elements.csv;Object.defineProperty(csv,'files',{configurable:true,value:[]});csv.dispatchEvent(new w.Event('change',{bubbles:true}));ok(calls.length===2,'cancelling the file chooser keeps the existing state');
 form.elements.inventory.value='previous-inventory';form.elements.inventory_loaded.value='1';form.elements.inventory_name.value='old.csv';
 Object.defineProperty(csv,'files',{configurable:true,value:[new w.File(['Filename\nA.pdf\n'],'new.csv',{type:'text/csv'})]});csv.dispatchEvent(new w.Event('change',{bubbles:true}));
 ok(calls.length===3&&form.elements.inventory.value==='W10='&&form.elements.inventory_loaded.value==='0','new CSV auto-refreshes without resending the previous large inventory');
 w.dispatchEvent(new w.Event('pageshow'));
 const choice=form.querySelector('[data-pdf-choice]');if(choice){const option=Array.from(choice.options).find(o=>o.value);choice.value=option.value;choice.dispatchEvent(new w.Event('change',{bubbles:true}));ok(calls.length===3&&choice.closest('.nadin-forscore-row').querySelector('[data-pdf-status]').textContent===option.dataset.summary,'PDF choice updates its status immediately without resetting scroll through another review');}
 p.d.window.close();
}
console.log('PASS:',checks,'automatic forScore workflow software-DOM checks');

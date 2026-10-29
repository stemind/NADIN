(function(){
 'use strict';
 var wait=document.getElementById('login-wait');
 if(wait){
  var email=document.getElementById('u'),form=email.form,button=form.querySelector('button[type=submit]');
  var originalEmail=email.value.trim().toLowerCase(),until=Date.now()+Number(wait.dataset.retryAfter)*1000,timer;
  function tick(){
   var seconds=Math.max(0,Math.ceil((until-Date.now())/1000)),same=email.value.trim().toLowerCase()===originalEmail;
   button.disabled=seconds>0&&same;
   if(!seconds){wait.textContent='You can try logging in again.';clearInterval(timer);}
   else wait.textContent='Too many unsuccessful attempts. Try again in '+Math.floor(seconds/60)+':'+String(seconds%60).padStart(2,'0')+'. The waiting time ends automatically.';
  }
  // This is only a convenience: the server enforces the wait even without JavaScript.
  timer=setInterval(tick,1000);tick();email.addEventListener('input',tick);
  form.addEventListener('submit',function(event){if(button.disabled)event.preventDefault();});
 }
 var inputs=Array.from(document.querySelectorAll('input[type=password]'));if(!inputs.length)return;
 var label=document.createElement('label');label.className='nadin-password-visibility';
 var toggle=document.createElement('input');toggle.type='checkbox';label.appendChild(toggle);label.appendChild(document.createTextNode(' Show passwords'));
 inputs[inputs.length-1].insertAdjacentElement('afterend',label);
 toggle.addEventListener('change',function(){inputs.forEach(function(input){input.type=toggle.checked?'text':'password';});});
}());

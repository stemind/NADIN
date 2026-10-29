(function(){
 'use strict';
 var inputs=Array.from(document.querySelectorAll('input[type=password]'));if(!inputs.length)return;
 var label=document.createElement('label');label.className='nadin-password-visibility';
 var toggle=document.createElement('input');toggle.type='checkbox';label.appendChild(toggle);label.appendChild(document.createTextNode(' Show passwords'));
 inputs[inputs.length-1].insertAdjacentElement('afterend',label);
 toggle.addEventListener('change',function(){inputs.forEach(function(input){input.type=toggle.checked?'text':'password';});});
}());

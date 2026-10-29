/* Software DOM test with fake browser time; no network or real browser. */
const fs=require('fs'),path=require('path'),assert=require('assert'),{JSDOM}=require('jsdom');
let checks=0;function ok(value,label){assert(value,label);checks++;}
const dom=new JSDOM('<p id="login-wait" role="status" data-retry-after="30"></p><form><input id="u" value="member@example.invalid"><input id="p" type="password"><button type="submit">Log in</button></form>',{runScripts:'outside-only'});
const w=dom.window,d=w.document;let now=100000,tick,cleared=false;
w.Date.now=()=>now;w.setInterval=fn=>{tick=fn;return 1;};w.clearInterval=()=>{cleared=true;};
w.eval(fs.readFileSync(path.resolve(__dirname,'../nadinmodule/account.js'),'utf8'));
const button=d.querySelector('button'),email=d.getElementById('u');
ok(button.disabled&&d.getElementById('login-wait').textContent.includes('0:30'),'server wait reflected immediately');
const submit=new w.Event('submit',{cancelable:true});d.querySelector('form').dispatchEvent(submit);ok(submit.defaultPrevented,'waiting form cannot submit');
now+=11000;tick();ok(d.getElementById('login-wait').textContent.includes('0:19'),'countdown follows elapsed wall time');
email.value='other@example.invalid';email.dispatchEvent(new w.Event('input'));ok(!button.disabled,'switching account remains possible');
email.value=' MEMBER@example.invalid ';email.dispatchEvent(new w.Event('input'));ok(button.disabled,'case/whitespace changes keep same-account wait');
now+=25000;tick();ok(!button.disabled&&cleared&&d.getElementById('login-wait').textContent.includes('try logging in again'),'timer releases automatically even after suspended tab');
ok(d.getElementById('p').value==='','countdown never stores or resubmits credentials');
const toggle=d.querySelector('.nadin-password-visibility input');toggle.checked=true;toggle.dispatchEvent(new w.Event('change'));ok(d.getElementById('p').type==='text','password visibility still works with countdown');
dom.window.close();console.log('PASS:',checks,'V13 login DOM checks');

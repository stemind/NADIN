function rescale() {
if (window.innerHeight<900)offs=30;
else offs=100;
	
windowheight=window.innerHeight;
if(windowheight<600)windowheight=600;	
windowwidth=window.innerWidth-17;
if(windowwidth<1400)windowwidth=1400;	

document.getElementById('composer').style.height=windowheight-30-offs+'px';
document.getElementById('composer').style.left=windowwidth-1011+'px';

document.getElementById('icomposer').style.height=windowheight-30-offs+'px';

document.getElementById('preview').style.height=windowheight-30-offs+'px';
document.getElementById('preview').style.left=windowwidth-608+'px';

document.getElementById('ipreview').style.height=windowheight-30-offs+'px';
if(ifrc)if(ifrc.document.getElementById('tunesarea'))ifrc.document.getElementById('tunesarea').style.height=windowheight-270-offs+'px';
else if(!ifrc.document.getElementById('anlegen'))setTimeout("rescale()",777);
repos();
}
rescale();

function repos() {
document.getElementById('composer').style.top=(window.pageYOffset+offs)+'px';
document.getElementById('preview').style.top=(window.pageYOffset+offs)+'px';
}
repos();


window.onresize=function startrescale() {
if (document.cookie.indexOf('move=')>-1)movestatus=('; '+document.cookie).split('; move=')[1].split(';')[0];
else movestatus='';
if(movestatus!='yes')rescale()
}

window.onscroll = function startrepos() {
if (document.cookie.indexOf('move=')>-1)movestatus=('; '+document.cookie).split('; move=')[1].split(';')[0];
else movestatus='';
if(movestatus!='yes')repos()
}

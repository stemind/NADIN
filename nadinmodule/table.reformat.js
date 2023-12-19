if(top!=self) {
tag=document.getElementsByTagName('h5');
for(i=0;i<tag.length;i++) {
tag[i].style.fontSize='90%';
}
tag=document.getElementsByTagName('a');
for(i=0;i<tag.length;i++) {
tag[i].style.fontSize='90%';
if(tag[i].innerHTML.indexOf(' ')>0)if(tag[i].href)if(tag[i].href.indexOf('.mp3')>0) tag[i].innerHTML=tag[i].innerHTML[0]+' '+tag[i].innerHTML.split(' ')[1];
if(tag[i].id)if(tag[i].innerHTML.indexOf(' ')>0)if(tag[i].href)if(tag[i].href.indexOf('avascript:void(0)')>0) tag[i].innerHTML=tag[i].innerHTML.split(' ')[0]+'+';
if(tag[i].id)if(tag[i].id.indexOf('eitereaufnahmen')<0)if(tag[i].innerHTML.indexOf(' ')<0 && tag[i].innerHTML!='')if(tag[i].href)if(tag[i].href.indexOf('avascript:void(0)')>0) tag[i].innerHTML='&nbsp;'+tag[i].innerHTML[0].toLowerCase()+'&nbsp;';
}
tag=document.getElementsByTagName('td');
for(i=0;i<tag.length;i++) {
tag[i].style.paddingTop='0';
tag[i].style.paddingBottom='0';
}
tag=document.getElementsByTagName('tr');
for(i=0;i<tag.length;i++) {
tag[i].style.padding='0';
}
tag=document.getElementsByTagName('span');
for(i=0;i<tag.length;i++) {
tag[i].style.fontSize='85%';
}

}
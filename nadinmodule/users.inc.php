<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('musers');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="../style.css" />
</head>

<body style="backgound:#D9F1FF">
<script>
changes=0;

function replace(string,text,by) {
	// Replaces text with by in string
	var strLength = string.length, txtLength = text.length;
	if ((strLength == 0) || (txtLength == 0)) return string;
	var i = string.indexOf(text);
	if ((!i) && (text != string.substring(0,txtLength))) return string;
	if (i == -1) return string;
	var newstr = string.substring(0,i) + by;
	if (i+txtLength < strLength)
	newstr += replace(string.substring(i+txtLength,strLength),text,by);
	return newstr;
}

function limchar(string) {
	
	string=replace(string,'ä','ae');
	string=replace(string,'ö','oe');
	string=replace(string,'ü','ue');
	string=replace(string,'Ä','Ae');		
	string=replace(string,'Ö','Oe');
	string=replace(string,'Ü','Ue');
	string=replace(string,'é','e');
	string=replace(string,'è','e');			
	string=replace(string,'à','a');				
	string=replace(string,'ô','o');
	string=replace(string,'ë','e');			
	string=replace(string,'ù','u');				

    for (var i=0, output='', valid="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 -_"; i<string.length; i++)
       if (valid.indexOf(string.charAt(i)) != -1)
          output += string.charAt(i)
    return output;
}

function doins(here,that) {
insertAtCursor(document.getElementById(here), that);
}

function insertAtCursor(myField, myValue) {
keepscrolltop=myField.scrollTop;
  //IE support
  myValue=unescape(myValue);
  if (document.selection) {
    myField.focus();
    sel = document.selection.createRange();
    sel.text = myValue;
  }
  //MOZILLA/NETSCAPE support
  else if (myField.selectionStart || myField.selectionStart == '0') {
    var startPos = myField.selectionStart;
    var endPos = myField.selectionEnd;
    myField.value = myField.value.substring(0, startPos)
                  + myValue
                  + myField.value.substring(endPos, myField.value.length);
  } else {
    myField.value += myValue;
  }
    myField.focus(); 
	if (myField.setSelectionRange) myField.setSelectionRange(startPos + myValue.length, startPos + myValue.length);
myField.scrollTop=keepscrolltop;
}

</script>
<iframe name="su" frameborder="0" width="0" height="0"></iframe>
<form name="form" method="post" target="su" action="nadinmodule/saveusers.php">


  <script>
function checkchanges() {
if (changes==1) {
if (!confirm('There are unsaved changes which got lost if you are proceeding. Proceed?')) {
document.getElementById('mehr').style.display='none';
document.getElementById('plus').style.display='inline';
document.getElementById('minus').style.display='none';
if (document.form.gig.value!='') document.getElementById('hider').style.display='inline';
}
}
}




rechteanleitung='Each line contains the data of one user, beginning with his/her real name, followed by the first comma, email address, second comma, expiration date of access, third comma.\n\nAfter that follows the comma-separated list of rights. From here on, the order of entries does not matter. In order to keep the list short, unlisted rights are disabled by definition (=0).\n\nCAPTION\n\nv = view\nup = upload\nm = manage\n\n\n\nThe right vgigs should be enabled for all users; it automatically grants the fallback access to the parts if a PDF is not sectioned (therefore, there is no right vparts).\n\nIn order for the users to receive the right uppdf, they must have the right upaudio.\n\nTo separate members from substitutes, it is recommended to use prefixes, such as\n\nM Alhere Peter, alpe@...\nS Ushelp Hans, ushhans@...\n\nThis first "field", i.e. the string up to the first comma, may be filled with any content, for example, information about the instruments (S substitute Hans as ts bs).';
</script>
<span style="font-size:9px"><b>Example <a href="javascript:alert(rechteanleitung)" style="font-size:200%">?</a>:</b> <font color="#00CC00">Reed Saxo, mistertenor@groove.com, 24.12.2016, vgigs=1, vsaxes=1,</font>vbones=0, vtrumpets=0, vrhythm=0, vother=0, vscore=0,  vreclist=0,    varchiv=0,    vlibrary=0,  upaudio=0, uppdf=0, mgigs=0,  musers=0</span>
<h5>Users

<script>

function sort() {
arr=document.form.users.value.split("\n");
arr.sort();
document.form.users.value=arr.join("\n");	 
}

</script>
<small>
<a href="javascript:sort()">sort</a> | 
<a href="javascript:void(0)" onclick="f1=window.open('nadinmodule/p.php','_blank','toolbar=0,location=0,status=1,screenX=0,screenY=0,top=0,left=222,menubar=0,scrollbars=1,resizable=1,width=400,height=700');">passwords</a> |  
</small>

<button id="save" title="save [Ctrl s]" type="button" onclick="document.form.submit()">s</button>

 </h5>

<textarea style="width:100%;height:333px" name="users" id="users" wrap="off" onKeyDown="if(event.keyCode == 9){doins(this.id,'\t');return false};">
<?php 
$readusers=file_get_contents('userdatei/userdatei.txt');
echo htmlXspecialchars(trim($readusers)) 
?>
</textarea>
</span>
</form>

<script>

function getchanges() {
$oldusers=document.form.users.value;
setTimeout("if (document.form.users.value!=$oldusers) alertchanges()",500);	
document.getElementById('users').style.height=document.documentElement.clientHeight-222+'px';
setTimeout("getchanges()",501);
}
setTimeout("getchanges()",100);

function alertchanges() {
changes=1;	
if (parent.document.getElementById('changes')) parent.document.getElementById('changes').value=1;
document.getElementById('save').style.background='pink';
document.getElementById('save').style.color='red';
document.getElementById('save').style.fontWeight='bold';
document.getElementById('save').innerHTML='s*';
document.getElementById('save').title='save [Ctrl s]; The sign * is displayed when unsaved changes are presen.';
}

if (parent.ifrp) parent.ifrp.location.replace('../preview.php?gig=<?php echo $_GET['gig']?>');
if (parent.document.getElementById('changes')) parent.document.getElementById('changes').value=0;

if (document.getElementById('manageusers')) document.getElementById('manageusers').style.background='yellow';
</script>
</body>
</html>
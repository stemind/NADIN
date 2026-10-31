<?php require ('refreshcookies2.inc.php') ?>
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('mgigs');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<script src="../shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
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


function basket(item) {

if (replace(document.form.tunes.value,unescape(item),'')!=document.form.tunes.value) {
document.form.tunes.value=replace(document.form.tunes.value,unescape(item),'')
document.form.tunes.value=replace(document.form.tunes.value,"\n\n","\n")
document.form.tunes.value=replace(document.form.tunes.value,"\r\n\r\n","\r\n")
document.form.tunes.value=document.form.tunes.value.trim();
}

else {
document.form.tunes.value+="\r\n"+unescape(item);
document.form.tunes.value=replace(document.form.tunes.value,"\n\n","\n")
document.form.tunes.value=replace(document.form.tunes.value,"\r\n\r\n","\r\n")
document.form.tunes.value=document.form.tunes.value.trim();
}

}
</script>
<form name="form" method="post" action="savegig.php">

<h5>Gig <small><span style="float:right"><input style="margin-right:-3px" type="checkbox" id="move" onchange="if(this.checked)document.cookie='move=yes; expires=Thu, 31 Dec 2037 12:00:00 GMT; path=/';else {document.cookie='move=no; expires=Thu, 31 Dec 2037 12:00:00 GMT; path=/';parent.rescale()}"/>&#128204;</span>
<?php if($_COOKIE['move']=='yes')echo"<script>if(document.getElementById('move'))document.getElementById('move').checked=true</script>"; ?>

<a id="plus" href="javascript:void(0)" title="more functions: register new gig, rename gig, copy gig, archive gig, delete gig" onclick="document.getElementById('mehr').style.display='block';document.getElementById('minus').style.display='inline';document.getElementById('hider').style.display='none';this.style.display='none';checkchanges()">Go to further transactions (new, archiving etc.)</a>

<a style="display:none" id="minus" href="javascript:void(0)" title="Go back to the gig editor" onclick="document.getElementById('mehr').style.display='none';document.getElementById('plus').style.display='inline';this.style.display='none';if (document.form.gig.value!='') document.getElementById('hider').style.display='inline'">Go back to the gig editor</a>

</small></h5>



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



function togig(that) {

if (changes==0) {
parent.ifrp.location.replace('../preview.php?gig='+that);
location.replace('?gig='+that);
} 

else {
 if (confirm('There are unsaved changes which got lost if you are quitting the gig. Proceed?'))  {
 parent.ifrp.location.replace('../preview.php?gig='+that);
 location.replace('?gig='+that)
 }
}	

}

function stay(that) {
xthat=that;
setTimeout("document.getElementById('gig').value=xthat",777);
}
</script>
<select onchange="stay(getgig);togig(this.value);" name="gig" id="gig">
<?php 
$path='../gigreps';
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }
$gigs=explode($delimiter,$gigs);
sort($gigs);

for ($i=0; $i<count($gigs);$i++) {
$selected='';
if (trim($_GET['gig'])=='') $_GET['gig']=$gigs[$i];
if ($_GET['gig']==$gigs[$i]) $selected='selected="selected"';
echo '<option '.$selected.' value="'.htmlXspecialchars($gigs[$i]).'">'.htmlXspecialchars(str_replace('.txt','',$gigs[$i])).'</option>';
}
?>
</select>
<?php echo '<script>getgig="'.$_GET['gig'].'"</script>'; ?>
<div id="mehr" style="display:none">

<p><h5>Register new gig</h5>
<input id="anlegen" type="text" name="anlegen" style="width:100%" onkeyup="if (this.value!=limchar(this.value)) this.value=limchar(this.value)" /> <a style="font-weight:bold"  href="javascript:void(0)" onclick="location.replace('trx.php?trx=anlegen&old='+document.form.gig.value+'&new='+document.form.anlegen.value)">create</a>
<small><small><p><font color=red>Important:</font> : In order for the iTunes subscriptions to work, be sure to begin the gig name with the date in the yyyy-mm-dd format! Example: <b>2014-12-31&nbsp;Vidmar&nbsp;yearendparty</b></small></small>
<div id="noold">
<hr>
<p><h5>Rename gig <small><?php echo htmlXspecialchars(str_replace('.txt','',$_GET['gig'])) ?></small> to</h5>
<input type="text" name="umtaufen" style="width:100%" onkeyup="if (this.value!=limchar(this.value)) this.value=limchar(this.value)" /> <a style="font-weight:bold"  href="javascript:void(0)" onclick="location.replace('trx.php?trx=umtaufen&old='+document.form.gig.value+'&new='+document.form.umtaufen.value)">rename</a>

<p><h5>Copy gig <small><?php echo htmlXspecialchars(str_replace('.txt','',$_GET['gig'])) ?></small> to</h5>
<input type="text" name="kopieren" style="width:100%" onkeyup="if (this.value!=limchar(this.value)) this.value=limchar(this.value)" /> <a style="font-weight:bold"  href="javascript:void(0)" onclick="location.replace('trx.php?trx=kopieren&old='+document.form.gig.value+'&new='+document.form.kopieren.value)">copy</a>

<p><h5>Archive gig <small><?php echo htmlXspecialchars(str_replace('.txt','',$_GET['gig'])) ?></small></h5>
<a style="font-weight:bold"  href="javascript:void(0)" onclick="location.replace('trx.php?trx=archivieren&old='+document.form.gig.value+'&new='+document.form.anlegen.value)">archive</a>

<p><h5>Gelete gig <small><?php echo htmlXspecialchars(str_replace('.txt','',$_GET['gig'])) ?></small></h5>
<a style="font-weight:bold"  href="javascript:void(0)" onclick="if (confirm('Are you sure?')) location.replace('trx.php?trx=loeschen&old='+document.form.gig.value+'&new='+document.form.anlegen.value)">delete</a>


</div>
<hr>

<p><h5>De-archive gig</h5>
<select name="xgig" id="xgig">
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
$path='../gigrepsarchiv';
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }
$gigs=explode($delimiter,$gigs);
rsort($gigs);

for ($i=0; $i<count($gigs);$i++) {
$selected='';
if ($_GET['gig']==$gigs[$i]) $selected='selected="selected"';
echo '<option '.$selected.' value="'.htmlXspecialchars($gigs[$i]).'">'.htmlXspecialchars(str_replace('.txt','',$gigs[$i])).'</option>';
}


?>
</select>
<br />
<a style="font-weight:bold"  href="javascript:void(0)" onclick="location.replace('trx.php?trx=desarchivieren&old='+document.form.xgig.value+'&new='+document.form.anlegen.value)">de-archive</a>

</div>

<span id="hider">
<button title="save [Ctrl s]" id="save" type="button" onclick="document.form.submit()">s</button>





<?php 
$infos='';
$tunes='';
$c=file_get_contents('../gigreps/'.basename($_GET['gig']));
$c=explode($delimiter2,$c);
$infos=$c[0];
$tunes=$c[1];



?>

<h5>Infos <small><a href="javascript:void(0)" onclick="f1=window.open('uploadatt.php?gig=<?php echo $_GET['gig']?>','_blank','toolbar=0,location=0,status=1,top=0,left=700,menubar=0,scrollbars=1,resizable=1,width=800,height=830');">PDF attachment</a></small></h5>

<textarea style="width:100%;height:120px" name="infos" id="infos">
<?php echo htmlXspecialchars($infos) ?>
</textarea>
<script>
anleitung='You can add/delete tunes in three different ways:\n\n(1) Enter exact name of tune in the field or remove it from there;\n\n(2) In the library (at left), click on a tune name (toggle);\n\n(3) likewise, in the preview at the right.\n\n\n\nThe preview is updated when you click the save button „s". If you are within the form, CTRL+s works as well.';

function sort() {
arr=document.form.tunes.value.split("\n");
arr.sort();
document.form.tunes.value=arr.join("\n");	
}

</script>
<h5>Tunes 
<small>
&nbsp;
<a href="javascript:alert(anleitung)">instructions</a>
&nbsp;
<a href="javascript:sort()">sort</a>
</small>
</h5>

<textarea id="tunesarea" style="width:100%;height:500px" name="tunes" id="tunes" wrap="off">
<?php echo htmlXspecialchars(trim($tunes)) ?>
</textarea>
</span>
</form>

<script>

function mkid(s) {
t='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 -_';

s=s.trim();
n='';

for (ii=0;ii<s.length;ii++) {
if (replace(t,s.substr(ii,1),'')!=t) n+=s[ii];
}
return 'i'+n;		
}

function mkid2(s) {
t='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 -_';

s=s.trim();
n='';

for (ii=0;ii<s.length;ii++) {
if (replace(t,s.substr(ii,1),'')!=t) n+=s[ii];
}
return 'ii'+n;		
}


function unhilite() {
<?php 
$path='../library';
$xtunes='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $xtunes.=$dir.$delimiter;
      }
   }

$xtunes=explode($delimiter,$xtunes);

for ($i=0;$i<count($xtunes);$i++) {
echo "if (parent.document.getElementById('".mkid($xtunes[$i])."')) parent.document.getElementById('".mkid($xtunes[$i])."').style.background='#eee';";
echo "if (parent.ifrp) if (parent.ifrp.document.getElementById('".mkid2($xtunes[$i])."')) parent.ifrp.document.getElementById('".mkid2($xtunes[$i])."').style.textDecoration='line-through';";
}

?>	
}

function hilite() {
unhilite();	
xitem=document.form.tunes.value.split("\n");

for (i=0;i<xitem.length;i++) {
if (parent.document.getElementById(mkid(xitem[i]))) parent.document.getElementById(mkid(xitem[i])).style.background='palegreen';
if (parent.ifrp) if (parent.ifrp.document.getElementById(mkid2(xitem[i]))) parent.ifrp.document.getElementById(mkid2(xitem[i])).style.textDecoration='none';
}
setTimeout("hilite()",333);	
}
setTimeout("hilite()",333);

function getchanges() {
$oldinfos=document.form.infos.value;
$oldtunes=document.form.tunes.value;
if(document.getElementById('plus').style.display!='none') {
setTimeout("if (document.form.infos.value!=$oldinfos) alertchanges()",500);	
setTimeout("if (document.form.tunes.value!=$oldtunes) alertchanges()",500);	
}
else {
changes=0;
if (parent.document.getElementById('changes')) parent.document.getElementById('changes').value=0;
}
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
document.getElementById('save').title='save [Ctrl s]; The sign * is displayed when unsaved changes are present.';
}

if (parent.ifrp) parent.ifrp.location.replace('../preview.php?gig=<?php echo $_GET['gig']?>');
if (parent.document.getElementById('changes')) parent.document.getElementById('changes').value=0;
if (document.form.gig.value=='') document.getElementById("hider").style.display='none';
if (document.form.gig.value=='') document.getElementById("noold").style.display='none';
parent.rescale();
</script>
</body>
</html>
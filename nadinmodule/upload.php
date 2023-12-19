<?php 
setcookie('upos', 'upload', time()+3600*24*365*10, '/');
require('refreshcookies2.inc.php');
require_once('functions.inc.php')
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script src="../shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<link rel="icon" href="../favicon.ico" type="image/x-icon" />
<title>manage <?php echo htmlXspecialchars(str_replace('_',' ',$_GET['tune'])) ?> - NADIN</title>
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('upaudio');
?></head>

<body>

<?php 

$infos='';
$links='';
$c=file_get_contents('../library/'.basename($_GET['tune']).'/infos.txt');
$c=explode($delimiter2,$c);
$infos=$c[0];
$links=$c[1];

echo '<h1>'.htmlXspecialchars(str_replace('_',' ',$_GET['tune'])).'</h1>';
?>

<?php require('uploadnavi.inc.php'); ?>

<p><button title="save [Ctrl s]" id="save" type="button" onclick="document.form.submit()">save</button>
&nbsp;
<button style="display:none" type="button" onclick="self.close()">close</button>



<form name="form" action="uploadsave.php?i=<?php echo $_GET['i']?>" method="post" target="su">

<h2>Tips &amp; info about the tune</h2>
<input type="hidden" name="foldername" value="<?php echo htmlXspecialchars($_GET['tune']) ?>" />
<textarea style="width:100%;height:200px" name="infos" id="infos">
<?php echo htmlXspecialchars(trim($infos)) ?>
</textarea>

<h2>External links referring to the tune</h2>
<textarea style="width:100%;height:200px" name="links" id="links">
<?php 
$links = $links ?? '';
echo htmlXspecialchars(trim($links)) 
?>
</textarea>

</form>

<script>
function getchanges() {
$oldinfos=document.form.infos.value;
$oldlinks=document.form.links.value;
setTimeout("if (document.form.infos.value!=$oldinfos) alertchanges()",500);	
setTimeout("if (document.form.links.value!=$oldlinks) alertchanges()",500);	
setTimeout("getchanges()",501);
}
setTimeout("getchanges()",100);

function alertchanges() {
changes=1;	
if (parent.document.getElementById('changes')) parent.document.getElementById('changes').value=1;
document.getElementById('save').style.background='pink';
document.getElementById('save').style.color='red';
document.getElementById('save').style.fontWeight='bold';
document.getElementById('save').innerHTML='save*';
document.getElementById('save').title='save [Ctrl s]; The sign * is displayed when unsaved changes are presen.';
}

</script>

<iframe name="su" frameborder="0" width="0" height="0"></iframe>
<input type="hidden" id="changes" />

<script>
window.onbeforeunload = function() {
if (document.getElementById('changes').value==1) return('You have unsaved changes which will be lost if you are proceeding.');
self.focus();
}
</script>
</body>
</html>

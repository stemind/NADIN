<?php
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
$tune=mkascii($_POST['tune']);
setcookie('scrlt', mkid2($tune), time()+600, '/'); 
auth('uppdf');
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

</head>

<body><script>
function myclose() {
if(opener)if(opener.document.getElementById('<?php echo mkid2($tune) ?>')) opener.document.getElementById('<?php echo mkid2($tune) ?>').style.background='lightblue';
if(opener)if(opener.document.getElementById('loadmarker')) {
setTimeout("self.close()",5555);
}
setTimeout("myclose()",777);
}
setTimeout("myclose()",777);
</script>


<?php 
echo '<h1>'.htmlXspecialchars(str_replace('_',' ',$_GET['tune'])).'</h2>';
?>

<?php //require('uploadnavi.inc.php'); ?>

<h2>Rename result</h1>
<?php 
if (strlen($tune)<3) die('<script>alert("The name must be at least 3 characters long.");history.go(-1);</script>');

if (file_exists('../library/'.$tune)) die('<script>alert("The tune (target name) '.htmlXspecialchars($tune).' already exists.");history.go(-1);</script>');

if (!file_exists('../library/'.$_GET['tune'])) die('<script>alert("The tune (source name) '.htmlXspecialchars($_GET['tune']).' does not exist.");history.go(-1);</script>');

if (rename('../library/'.basename($_GET['tune']),'../library/'.$tune)) {

//////////////////////////////
$path='../gigreps';
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }

$gigs=explode($delimiter,$gigs);

for ($i=0; $i<count($gigs);$i++) {
$c=file_get_contents($path.'/'.$gigs[$i]);

$fien='';
if(strpos($c,"\t")>0)$fien="\t";
if(strpos($c,"\n")>0)$fien="\n";
if(strpos($c,"\r")>0)$fien="\r";
$c.=$fien;

$c=str_replace("\n".$_GET['tune']."\n","\n".$tune."\n",$c);
$c=str_replace("\n".$_GET['tune']."\r","\n".$tune."\r",$c);
$c=str_replace("\n".$_GET['tune']."\t","\n".$tune."\t",$c);
$c=str_replace("\n".$_GET['tune']." ","\n".$tune." ",$c);

$c=str_replace("\r".$_GET['tune']."\n","\r".$tune."\n",$c);
$c=str_replace("\r".$_GET['tune']."\r","\r".$tune."\r",$c);
$c=str_replace("\r".$_GET['tune']."\t","\r".$tune."\t",$c);
$c=str_replace("\r".$_GET['tune']." ","\r".$tune." ",$c);

$c=str_replace("\t".$_GET['tune']."\n","\t".$tune."\n",$c);
$c=str_replace("\t".$_GET['tune']."\r","\t".$tune."\r",$c);
$c=str_replace("\t".$_GET['tune']."\t","\t".$tune."\t",$c);
$c=str_replace("\t".$_GET['tune']." ","\t".$tune." ",$c);

$c=str_replace(" ".$_GET['tune']."\n"," ".$tune."\n",$c);
$c=str_replace(" ".$_GET['tune']."\r"," ".$tune."\r",$c);
$c=str_replace(" ".$_GET['tune']."\t"," ".$tune."\t",$c);
$c=str_replace(" ".$_GET['tune']." "," ".$tune." ",$c);

file_put_contents($path.'/'.$gigs[$i],trim($c));
}
//////////////////////////////

$path='../gigrepsarchiv';
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }

$gigs=explode($delimiter,$gigs);

for ($i=0; $i<count($gigs);$i++) {
$c=file_get_contents($path.'/'.$gigs[$i]);

$fien='';
if(strpos($c,"\t")>0)$fien="\t";
if(strpos($c,"\n")>0)$fien="\n";
if(strpos($c,"\r")>0)$fien="\r";
$c.=$fien;

$c=str_replace("\n".$_GET['tune']."\n","\n".$tune."\n",$c);
$c=str_replace("\n".$_GET['tune']."\r","\n".$tune."\r",$c);
$c=str_replace("\n".$_GET['tune']."\t","\n".$tune."\t",$c);
$c=str_replace("\n".$_GET['tune']." ","\n".$tune." ",$c);

$c=str_replace("\r".$_GET['tune']."\n","\r".$tune."\n",$c);
$c=str_replace("\r".$_GET['tune']."\r","\r".$tune."\r",$c);
$c=str_replace("\r".$_GET['tune']."\t","\r".$tune."\t",$c);
$c=str_replace("\r".$_GET['tune']." ","\r".$tune." ",$c);

$c=str_replace("\t".$_GET['tune']."\n","\t".$tune."\n",$c);
$c=str_replace("\t".$_GET['tune']."\r","\t".$tune."\r",$c);
$c=str_replace("\t".$_GET['tune']."\t","\t".$tune."\t",$c);
$c=str_replace("\t".$_GET['tune']." ","\t".$tune." ",$c);

$c=str_replace(" ".$_GET['tune']."\n"," ".$tune."\n",$c);
$c=str_replace(" ".$_GET['tune']."\r"," ".$tune."\r",$c);
$c=str_replace(" ".$_GET['tune']."\t"," ".$tune."\t",$c);
$c=str_replace(" ".$_GET['tune']." "," ".$tune." ",$c);

file_put_contents($path.'/'.$gigs[$i],trim($c));
}
//////////////////////////////

?>
<script>
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

if(opener) {
if(opener.parent) {
if(opener.parent.ifrc) {
if(opener.parent.ifrc.document.getElementById('tunes')) {
cv=opener.parent.ifrc.document.getElementById('tunes').value;
cv=replace(cv,'<?php echo $_GET['tune'] ?>','<?php echo $tune ?>');
opener.parent.ifrc.document.getElementById('tunes').value=cv;
}}}}</script>
<iframe style="display:none" src="rezip.php?c=<?php echo $tune ?>"></iframe>
<?php 

echo '<script>
if(opener)opener.fresh();
</script>Success, <b>'.htmlXspecialchars($_GET['tune']).'</b> is now <b>'.htmlXspecialchars($tune).'</b>.<script>myclose();</script>';
}
?>
</body>
</html>
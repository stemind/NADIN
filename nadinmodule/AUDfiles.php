<?php 
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('upaudio');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<script src="../shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
</head>

<body>
<table>
<?php
$totalsizes=0;
$i=0;
$files='';
if (file_exists('../library/'.$_GET['name'])) {

     $path='../library/'.$_GET['name'];
	 
 
     if ($handle = @opendir($path))  { 
     while (false !== ($file = readdir($handle)))  { 

    if (str_replace('.','',$file)!='') {

	 $ext=explode('.',$file);
     $ext=$ext[count($ext)-1];

if ($ext=='mp3') $files.=$file.',';

    } }
     }

$file=explode(',',$files);

rsort($file);
$recodetected=0;
for($i=0;$i<count($file)-1;$i++) {
if ($file[$i]!=str_replace($recommended,'',$file[$i])) $recodetected++;
$thefilesize=filesize($path.'/'.$file[$i]);
$totalsizes+=$thefilesize;
$filesize = round($thefilesize/1024);
$mass='kB';

	echo '<tr bgcolor="#eeeeee" onmouseover="this.style.background=\'lightyellow\'" onmouseout="this.style.background=\'#eeeeee\'">
	<td><a id="'.mkid2($file[$i]).'" target="_blank" href="../get/file.php?c='.str_replace('library/','',$path).'/'.$file[$i].'">'.$file[$i].'</a></td>
	<td>'.date('d.m.Y H:i:s',filemtime($path.'/'.$file[$i]));
	if(striptime($file[$i])==$file[$i]) echo '<iframe style="display:none" src="settime.php?p='.$path.'&f='.$file[$i].'"></iframe>';
	echo '</td>	<td align="right">&nbsp;'.$filesize.$mass.'&nbsp</td>
	<td><a href="javascript:void(0)" onclick="cf=prompt(\'New file name (incl. extension):                                                                                                                     \',\''.htmlXspecialchars(str_replace('..','.',striptime($file[$i]))).'\');location.replace(\'AUDrenamefile.php?n=\'+cf+\'&p='.basename($path).'&f='.basename($file[$i]).'\')">rename</a></td>
	<td><a href="javascript:void(0)" onclick="cf=confirm(\'Do you really want to delete '.$file[$i].'\');if (cf) location.replace(\'AUDdeletefile.php?p='.basename($path).'&f='.basename($file[$i]).'\')">delete</a></td></tr>';
}

}
if ($recodetected>1) echo "<script>setTimeout(\"alert('ERROR: For ".htmlXspecialchars($_GET['name'])." $recodetected audio files are tagged with the string $recommended. It is mandatory that exactly genau 1 audio file is tagged with the string $recommended meaning that this is the most matching recording for that tune. This error must be corrected now. Do rename now ".($recodetected-1)." audio file(s) accordingly (remove $recommended)!')\",2222);</script>";
if (count($file)>1 && $recodetected==0) echo "<script>setTimeout(\"alert('WARNING: For ".htmlXspecialchars($_GET['name'])." $recodetected audio files are tagged with the string $recommended. There should be exactly 1 audio file containing the string $recommended in its file name showing that this is the best recording for this tune. To optimize that situation use rename!')\",2222);</script>";
?>
</table>
<script>
if(document.getElementById('<?php echo $_GET['m']?>'))document.getElementById('<?php echo $_GET['m']?>').style.background='yellow';
if(document.getElementById('<?php echo $_GET['m']?>'))document.getElementById('<?php echo $_GET['m']?>').focus();
</script>
</body>
</html>


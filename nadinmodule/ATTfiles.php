<?php 
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('uppdf');
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
if (file_exists('../abo/zip/'.$_GET['name'])) {

     $path='../abo/zip/'.$_GET['name'];
	 
 
     if ($handle = @opendir($path))  { 
     while (false !== ($file = readdir($handle)))  { 

    if (str_replace('.','',$file)!='' && str_replace('infos.txt','',$file)!='' ) {

$ext=explode('.',$file);
$ext=$ext[count($ext)-1];

if ($file==$_GET['gig'].'.pdf') $files.=$file.',';

    } }
     }

$file=explode(',',$files);

sort($file);

for($i=1;$i<count($file);$i++) {

$thefilesize=filesize($path.'/'.$file[$i]);
$totalsizes+=$thefilesize;
$filesize = round($thefilesize/1024);
$mass='kB';


$ext=explode('.',$file[$i]);
$ext=$ext[count($ext)-1];

$xyz='XYZ';
if(strpos($file[$i],$parts)>0) $xyz=$parts;
if(strpos($file[$i],$score)>0) $xyz=$score;
if(strpos($file[$i],$saxes)>0) $xyz=$saxes;
if(strpos($file[$i],$bones)>0) $xyz=$bones;
if(strpos($file[$i],$trumpets)>0) $xyz=$trumpets;
if(strpos($file[$i],$rhythm)>0) $xyz=$rhythm;
if(strpos($file[$i],$other)>0) $xyz=$other;
if(strpos($file[$i],$hidden)>0) $xyz=$hidden;

if ($ext=='pdf') $vorschlag=basename($path).' '.$xyz.' .'.$ext;
else $vorschlag=basename($path).'_HDDN amytext .'.$ext;

	echo '<tr bgcolor="#eeeeee" onmouseover="this.style.background=\'lightyellow\'" onmouseout="this.style.background=\'#eeeeee\'">
	<td><a target="_blank" href="../get/setlist.php?c='.str_replace('abo/zip/','',$path).'/'.$file[$i].'">'.$file[$i].'</a></td>
	<td>'.date('d.m.Y H:i:s',filemtime($path.'/'.$file[$i])).'</td>
	<td align="right">&nbsp;'.$filesize.$mass.'&nbsp</td>
	<td> </td>
	<td><a href="javascript:void(0)" onclick="cf=confirm(\'Do you really want to delete '.$file[$i].'\');if (cf) location.replace(\'ATTdeletefile.php?p='.basename($path).'&f='.basename($file[$i]).'\')">delete</a></td></tr>';
}

}

?>
</table>

</body>
</html>

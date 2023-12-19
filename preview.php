<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="style.css" />
<script src="shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
</head>
<body style="backgound:#D9F1FF">
<?php 
require('config.inc.php');
require_once('nadinmodule/functions.inc.php');
$c=@file_get_contents('gigreps/'.basename($_GET['gig']));
$c=explode($delimiter2,$c);
$infos=$c[0];
$rep=$c[1];

$small='style="font-size:80%"';

require('nadinmodule/infos.inc.php');
$rep = $rep ?? '';
$tunes=explode("\n",$rep);

require('nadinmodule/table.inc.inc.php');
?>
<iframe name="general" width="0" height="0" frameborder="0"></iframe>
<?php 
require_once('nadinmodule/findpos.js.php');
?>

<?php 
if(!isset($_GET['zip'])) {
echo '<iframe style="display:none" width="0" height="0" frameborder="0" src="preview.php?'.$_SERVER['QUERY_STRING'].'&zip=1"></iframe></body></html>';
exit;
}
?>

<?php
if (file_put_contents('abo/x','x')) {
echo '<!--w ../r/ ok -->';
delfile('abo/x');
}
else echo '<script>alert("PROBLEM: The server does not have the right to write into the folder named \'abo\'.\n\nEFFECTS: The zip files cannot be created.\n\nSOLUTION: CHMOD this folder RECURSIVELY (incl. all its files, subfolders a.s.o.) e. g. to 777 e. g. with FileZilla.");</script>';

if ($offersheetsaszip=='yes') {
///
$allsaxes=explode(',',$allsaxes);
delfile('abo/zip/allsaxes/'.str_replace('.txt','',basename($_GET['gig'])).'_allsaxes.*');
$zip = new ZipArchive();
$filename = 'abo/zip/allsaxes/'.str_replace('.txt','',basename($_GET['gig'])).'_allsaxes.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    echo("cannot open $filename \n");
}
for ($iz=0;$iz<count($allsaxes);$iz++) {
$zip->addFile("library/".$allsaxes[$iz],basename($allsaxes[$iz]));
$zip->deleteName('library/');
}
$zip->close();

///
$allbones=explode(',',$allbones);
delfile('abo/zip/allbones/'.str_replace('.txt','',basename($_GET['gig'])).'_allbones.*');
$zip = new ZipArchive();
$filename = 'abo/zip/allbones/'.str_replace('.txt','',basename($_GET['gig'])).'_allbones.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    echo("cannot open $filename \n");
}
for ($iz=0;$iz<count($allbones);$iz++) {
$zip->addFile("library/".$allbones[$iz],basename($allbones[$iz]));
$zip->deleteName('library/');
}
$zip->close();

///
$alltrumpets=explode(',',$alltrumpets);
delfile('abo/zip/alltrumpets/'.str_replace('.txt','',basename($_GET['gig'])).'_alltrumpets.*');
$zip = new ZipArchive();
$filename = 'abo/zip/alltrumpets/'.str_replace('.txt','',basename($_GET['gig'])).'_alltrumpets.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    echo("cannot open $filename \n");
}
for ($iz=0;$iz<count($alltrumpets);$iz++) {
$zip->addFile("library/".$alltrumpets[$iz],basename($alltrumpets[$iz]));
$zip->deleteName('library/');
}
$zip->close();

///
$allrhythm=explode(',',$allrhythm);
delfile('abo/zip/allrhythm/'.str_replace('.txt','',basename($_GET['gig'])).'_allrhythm.*');
$zip = new ZipArchive();
$filename = 'abo/zip/allrhythm/'.str_replace('.txt','',basename($_GET['gig'])).'_allrhythm.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    echo("cannot open $filename \n");
}
for ($iz=0;$iz<count($allrhythm);$iz++) {
$zip->addFile("library/".$allrhythm[$iz],basename($allrhythm[$iz]));
$zip->deleteName('library/');
}
$zip->close();

///
$allother=explode(',',$allother);
delfile('abo/zip/allother/'.str_replace('.txt','',basename($_GET['gig'])).'_allother.*');
$zip = new ZipArchive();
$filename = 'abo/zip/allother/'.str_replace('.txt','',basename($_GET['gig'])).'_allother.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    echo("cannot open $filename \n");
}
for ($iz=0;$iz<count($allother);$iz++) {
$zip->addFile("library/".$allother[$iz],basename($allother[$iz]));
$zip->deleteName('library/');
}
$zip->close();

///
$allscore=explode(',',$allscore);
delfile('abo/zip/allscore/'.str_replace('.txt','',basename($_GET['gig'])).'_allscore.*');
$zip = new ZipArchive();
$filename = 'abo/zip/allscore/'.str_replace('.txt','',basename($_GET['gig'])).'_allscore.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    echo("cannot open $filename \n");
}
for ($iz=0;$iz<count($allscore);$iz++) {
$zip->addFile("library/".$allscore[$iz],basename($allscore[$iz]));
$zip->deleteName('library/');
}
$zip->close();

///
$allparts=explode(',',$allparts);
delfile('abo/zip/allparts/'.str_replace('.txt','',basename($_GET['gig'])).'_allparts.*');
$zip = new ZipArchive();
$filename = 'abo/zip/allparts/'.str_replace('.txt','',basename($_GET['gig'])).'_allparts.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    echo("cannot open $filename \n");
}
for ($iz=0;$iz<count($allparts);$iz++) {
$zip->addFile("library/".$allparts[$iz],basename($allparts[$iz]));
$zip->deleteName('library/');
}
$zip->close();
}

$alllistento=explode(',',$alllistento);
delfile('abo/zip/alllistento/'.str_replace('.txt','',basename($_GET['gig'])).'_alllistento.*');
if ($offerrcmdmp3saszip=='yes') {
$zip = new ZipArchive();
$filename = 'abo/zip/alllistento/'.str_replace('.txt','',basename($_GET['gig'])).'_alllistento.zip';
if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
echo("cannot open $filename \n");
}
for ($iz=0;$iz<count($alllistento);$iz++) {
$zip->addFile("library/".$alllistento[$iz],basename($alllistento[$iz]));
$zip->deleteName("library/");
$zip->deleteName('library/');
}
$zip->close();
}

function delfile($str) 
{ 
if(!is_dir(dirname($str)))mkdir(dirname($str));
    foreach(glob($str) as $fn) { 
        unlink($fn); 
    } 
}
?>
</body>
</html>
<?php 
require('../config.inc.php');
require('../nadinmodule/functions.inc.php');
$c='../abo/'.secure($_GET['c']);
auth('vgigs');

if (strpos($c,'allsaxes')>0) checkrole('vsaxes');
if (strpos($c,'allbones')>0) checkrole('vbones');
if (strpos($c,'alltrumpets')>0) checkrole('vtrumpets');
if (strpos($c,'allrhythm')>0) checkrole('vrhythm');
if (strpos($c,'allother')>0) checkrole('vother');
if (strpos($c,'allscore')>0) checkrole('vscore');

if (!file_exists($c)) die('File '.$c.' not found.');
require_once('mime.inc.php');
$ext=explode('.',$c);
$ext=$ext[count($ext)-1];
header("Content-type: ".$m[$ext]); 
header("Content-Disposition:inline;filename=".basename($c));
header("Content-Length: " . filesize($c));
readfile($c);
?>
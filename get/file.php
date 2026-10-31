<?php 
require('../config.inc.php');
require('../nadinmodule/functions.inc.php');
$c='../library/'.secure($_GET['c']);
auth('vgigs');
if (!allowedfor('vlibrary')) require('../nadinmodule/isgoa.inc.php');
if (strpos($c,$saxes)>0) checkrole('vsaxes');
if (strpos($c,$bones)>0) checkrole('vbones');
if (strpos($c,$trumpets)>0) checkrole('vtrumpets');
if (strpos($c,$rhythm)>0) checkrole('vrhythm');
if (strpos($c,$other)>0) checkrole('vother');
if (strpos($c,$score)>0) checkrole('vscore');
if (strpos($c,$hidden)>0) checkrole('musers');

if (!file_exists($c)) die('File '.$c.' not found.');
require_once('mime.inc.php');
$ext=explode('.',$c);
$ext=$ext[count($ext)-1];
header("Content-type: ".$m[$ext]); 
header("Content-Disposition:inline;filename=".basename($c));
header("Content-Length: " . filesize($c));
readfile($c);
?>
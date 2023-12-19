<?php 
require('../config.inc.php');
require('../nadinmodule/functions.inc.php');
$c='../abo/zip/'.secure($_GET['c']);
auth('vgigs');
if (!file_exists($c)) die('File '.$c.' not found.');
require_once('mime.inc.php');
$ext=explode('.',$c);
$ext=$ext[count($ext)-1];
header("Content-type: ".$m[$ext]); 
header("Content-Disposition:inline;filename=".basename($c));
header("Content-Length: " . filesize($c));
readfile($c);
?>
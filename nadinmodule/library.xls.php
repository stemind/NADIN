<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('vlibrary'); 

$filename=str_replace(' ','',mkname(substr($bb.$bibliothek,0,60)));
header('Content-Type: text/x-csv; charset=UTF-8'); 
header("Content-Disposition:attachment;filename=".$filename."_".date('Y-m-d_His').".csv");

echo "\xEF\xBB\xBF".$bb.' '.$bibliothek.';';

$path='../library';
$tunes='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $tunes.=$dir.$delimiter;
      }
   }


$tunes=explode($delimiter,$tunes);
sort($tunes);
require('table.inc.xls.php');

?>

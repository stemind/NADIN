<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('vgigs') ;
if ($_GET['m']=='') $_GET['m']='gigreps';
$path='../'.basename($_GET['m']);
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }
$gigs=explode($delimiter,$gigs);

if ($_GET['m']=='gigreps') {
sort($gigs);
if (!isset($_GET['c'])) $_GET['c']=$gigs[1];
}

else {
rsort($gigs);
if (!isset($_GET['c'])) $_GET['c']=$gigs[0];
}

$filename=str_replace(' ','',mkname(substr($bb.str_replace('.txt','',$_GET['c']),0,60)));
header('Content-Type: text/x-csv; charset=UTF-8'); 
header("Content-Disposition:attachment;filename=".$filename."_".date('Y-m-d_His').".csv");

echo "\xEF\xBB\xBF".$bb.' '.str_replace('.txt','',$_GET['c']).';';
 
$c=@file_get_contents('../'.basename($_GET['m']).'/'.basename($_GET['c']));
$c=explode($delimiter2,$c);
$infos=$c[0];
$rep=$c[1];

$tunes=explode("\n",$rep);
require('table.inc.xls.php');
?>
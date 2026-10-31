<?php require __DIR__.'/gig-export.inc.php';
$filename=str_replace(' ','',mkname(substr($bb.str_replace('.txt','',$_GET['c']),0,60)));
header('Content-Type: text/x-csv; charset=UTF-8'); 
header("Content-Disposition:attachment;filename=".$filename."_".date('Y-m-d_His').".csv");

echo "\xEF\xBB\xBF".str_replace(array(';',"\r","\n"),array(',.',' ',' '),nadinText($bb.' '.str_replace('.txt','',$_GET['c']))).';';
 
require('table.inc.xls.php');
?>
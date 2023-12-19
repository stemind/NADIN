<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
setcookie('rezip', '', time()+$timeout, '/');

$get=basename($_GET['c']);

if($get!='') {

$gigs='';
$path='../gigreps/';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }
$gigs=explode($delimiter,$gigs);



for($i=0;$i<count($gigs);$i++) {
$c=@file_get_contents('../gigreps/'.$gigs[$i]);
$c=explode($delimiter2,$c);
$rep=$c[1];
$rep=nl2br($rep);
$rep=explode('<br />',$rep);

for($ii=0;$ii<count($rep);$ii++) {

if (trim($rep[$ii])==trim($_COOKIE['rezip'])) echo '<iframe src="../preview.php?gig='.$gigs[$i].'&zip=1"></iframe>';

}

}

}
?>
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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<title><?php echo $bb.': '.str_replace('.txt','',$_GET['c'])?> &mdash; NADIN</title>
<?php require_once('../config.inc.php');
require_once('../nadinmodule/functions.inc.php')
?></head>
<body>
<?php 
$c=@file_get_contents('../'.basename($_GET['m']).'/'.basename($_GET['c']));
$c=explode($delimiter2,$c);
$infos=$c[0];
$rep=$c[1];

$tunes=explode("\n",$rep);
require('table.print.php');
?>
</body>
</html>
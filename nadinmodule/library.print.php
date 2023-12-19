<?php require_once('../config.inc.php');
require_once('../nadinmodule/functions.inc.php')
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<title><?php echo htmlXentities($bb.' '.$bibliothek) ?></title>
</head>
<body>
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('vlibrary'); 


$path='../library';
$tunes='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $tunes.=$dir.$delimiter;
      }
   }


$tunes=explode($delimiter,$tunes);
sort($tunes);
require('table.print.php');

?>
</body>
</html>
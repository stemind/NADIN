<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
setcookie('u', $up, time()+$timeout, '/');
setcookie('p', $pmd5p, time()+$timeout, '/');
sleep(1);
writelog('login;'.cleancsv($up).';'.crc32($pcrcp));
header("Location: index.php");
?>
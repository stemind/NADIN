<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
setcookie('u', rand(1000000,9999999), time()+3600, '/');
setcookie('p', rand(1000000,9999999), time()+3600, '/');
writelog('logout;'.cleancsv($uc).';'.crc32($pcrcc));
header("Location: login.php");
?>
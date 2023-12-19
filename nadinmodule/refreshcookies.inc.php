<?php 
require_once('config.inc.php');
$_COOKIE['u'] = $_COOKIE['u'] ?? '';
$_COOKIE['p'] = $_COOKIE['p'] ?? '';
setcookie('u', $_COOKIE['u'], time()+$timeout, '/');
setcookie('p', $_COOKIE['p'], time()+$timeout, '/');
setcookie('scrlt', '', time()+600, '/')
?>
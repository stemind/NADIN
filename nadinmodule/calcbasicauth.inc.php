<?php 
$uc=cleanusers($_SERVER['PHP_AUTH_USER']);
$pcrcp=cleanusers($_SERVER['PHP_AUTH_PW']);
$pcrcc=nadinhash($uc.$salt);
if($sticksessiontoip=='yes')$serverremoteaddr=$_SERVER['REMOTE_ADDR'];
else $serverremoteaddr='';
$pmd5i=sha1(trim(str_replace(' ','',($salt.$serverremoteaddr.$pcrcc))));
$pmd5c=$pmd5i;
?>
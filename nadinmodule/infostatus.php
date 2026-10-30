<?php
require_once dirname(__DIR__).'/config.inc.php';require_once __DIR__.'/functions.inc.php';
nadin_require_login();
$name=$_GET['infodiv'] ?? '';$value=$_GET['infostatus'] ?? '';
if (!is_string($name)||!preg_match('/^info[a-f0-9]{32}$/D',$name)||!in_array($value,array('none','inline'),true)){http_response_code(400);exit;}
nadin_cookie($name,$value,time()+31536000);
header('Cache-Control: no-store');http_response_code(204);

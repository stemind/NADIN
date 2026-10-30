<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
if ($uc !== '') nadin_set_session(nadin_session_token());

// Legacy popup URLs are scalar path components; reject script/attribute injection before rendering.
foreach (array('tune','name','gig','p','f','foldername') as $key) {
    $value=$_GET[$key] ?? '';
    if (!is_string($value) || ($value!=='' && ($value[0]==='.' || basename($value)!==$value || preg_match('/[\\\\\x00-\x1f<>"\']/',$value)))) {http_response_code(400);exit('Invalid media link.');}
}
foreach (array('i','m') as $key) if (isset($_GET[$key]) && (!is_string($_GET[$key]) || !preg_match('/^[A-Za-z0-9_-]*$/D',$_GET[$key]))) {http_response_code(400);exit('Invalid media selection.');}

<?php
// Keep the established media tools, but require authenticated POST + CSRF for writes.
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
function nadin_legacy_error($status,$message) {
 http_response_code($status);header('Content-Type: text/html; charset=UTF-8');
 echo '<p role="alert">'.htmlspecialchars($message,ENT_QUOTES,'UTF-8').'</p><script>if(parent!==window){var d=parent.document;var box=d.getElementById("nadin-media-error");if(!box){box=d.createElement("p");box.id="nadin-media-error";box.setAttribute("role","alert");box.style.cssText="background:#fff0ed;border-left:3px solid #b72424;padding:8px";d.body.insertBefore(box,d.body.firstChild);}box.textContent='.json_encode($message,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT).';}</script>';exit;
}
$legacyName=basename($_SERVER['SCRIPT_FILENAME']);
$legacyRights=array('new2.php'=>'uppdf','uploadsave.php'=>'upaudio','renamefolder2.php'=>'uppdf','deletefolder2.php'=>'uppdf','AUDsendfile2.php'=>'upaudio','AUDrenamefile.php'=>'upaudio','AUDdeletefile.php'=>'upaudio','PDFsendfile2.php'=>'uppdf','PDFrenamefile.php'=>'uppdf','PDFdeletefile.php'=>'uppdf','ATTsendfile2.php'=>'mgigs','ATTdeletefile.php'=>'mgigs');
auth($legacyRights[$legacyName] ?? 'musers');
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST'||!nadin_auth()->csrf('media-v12',nadin_session_token(),$_POST['csrf'] ?? '')){nadin_legacy_error(403,'This media form has expired. Reload it before trying again.');}
foreach(array($_GET,$_POST) as $input) foreach(array('p','name','tune','foldername','gig','f') as $key){
 $v=$input[$key] ?? null;
 if($legacyName==='ATTsendfile2.php' && $key==='name' && $v==='')continue;
 if($v!==null&&(!is_string($v)||$v===''||$v[0]==='.'||basename($v)!==$v||preg_match('~[\\\\\x00-\x1f<>"\']~',$v)))nadin_legacy_error(400,'Invalid file or folder name.');
 if(in_array($key,array('p','name','tune','foldername'),true)&&is_string($v)&&is_link(dirname(__DIR__).'/library/'.$v))nadin_legacy_error(400,'Unsafe media directory.');
}
$legacyFile=$_POST['f'] ?? $_GET['f'] ?? '';
if(in_array($legacyName,array('AUDdeletefile.php','AUDrenamefile.php'),true)&&strtolower(pathinfo($legacyFile,PATHINFO_EXTENSION))!=='mp3')nadin_legacy_error(403,'Audio permission only allows changes to MP3 files.');
if($legacyFile!==''&&$hidden!==''&&strpos($legacyFile,$hidden)!==false)auth('musers');
if($legacyName==='PDFrenamefile.php'){
 require dirname(__DIR__).'/configallowedfileextensions.php';$ext=strtolower(pathinfo($_POST['n'] ?? '',PATHINFO_EXTENSION));
 if(!in_array($ext,$okext,true)||in_array($ext,array('php','phar','phtml','txt','mp3'),true)){nadin_legacy_error(400,'This file extension is not allowed.');}
}
// These legacy handlers read their parameters from GET internally; transport is POST only.
if(in_array($legacyName,array('AUDrenamefile.php','AUDdeletefile.php','PDFrenamefile.php','PDFdeletefile.php','ATTdeletefile.php'),true)) $_GET=array_merge($_GET,$_POST);

$legacyLockPath=dirname(__DIR__).'/userdatei/admin-v12-lock.php';
if(is_link($legacyLockPath)){nadin_legacy_error(500,'Unsafe storage path.');}
$legacyLock=fopen($legacyLockPath,'c+');
if(!$legacyLock||!flock($legacyLock,LOCK_EX)){nadin_legacy_error(503,'Storage is busy. Try again.');}
if(!fstat($legacyLock)['size']){fwrite($legacyLock,"<?php http_response_code(404); exit; ?>\n");fflush($legacyLock);}
register_shutdown_function(function() use($legacyLock){flock($legacyLock,LOCK_UN);fclose($legacyLock);});
if($legacyName==='uploadsave.php'){
 $infoPath=dirname(__DIR__).'/library/'.($_POST['foldername'] ?? '').'/infos.txt';
 $currentRevision=is_file($infoPath)?hash_file('sha256',$infoPath):hash('sha256','');
 if(!is_string($_POST['revision'] ?? null)||!hash_equals($currentRevision,$_POST['revision'])){nadin_legacy_error(409,'These tune notes changed in another window. Copy your changes, reload, and compare before saving.');}
}

<?php
require_once __DIR__.'/legacy-write.inc.php';
$kind=$nadinMediaKind ?? '';$action=$nadinMediaAction ?? '';$attachment=$kind==='attachment';$audio=$kind==='audio';
$root=dirname(__DIR__);$path=$_GET['p'] ?? '';$file=$_GET['f'] ?? '';
$dir=$attachment?$root.'/abo/zip':$root.'/library/'.$path;$source=$dir.'/'.$file;
if(!is_dir($dir)||(!$attachment&&is_link($dir))||!is_file($source)||is_link($source))nadin_legacy_error(404,'This file is no longer available. Refresh the file list.');
if(!$attachment&&($audio!==(strtolower(pathinfo($file,PATHINFO_EXTENSION))==='mp3')))nadin_legacy_error(403,'Use the matching file editor for this file.');
if($action==='rename'){
    $new=$_GET['n'] ?? '';
    if(!is_string($new)||$new===''||$new[0]==='.'||basename($new)!==$new||preg_match('/[\\\\\x00-\x1f\x7f]/',$new))nadin_legacy_error(400,'Enter a valid filename, including its extension.');
    $new=mkascii($audio?striptime($new):$new);$ext=strtolower(pathinfo($new,PATHINFO_EXTENSION));
    require $root.'/configallowedfileextensions.php';
    if(($audio&&$ext!=='mp3')||(!$audio&&(!in_array($ext,$okext,true)||in_array($ext,array('mp3','txt','php','phtml','phar'),true))))nadin_legacy_error(400,'Keep an allowed file extension.');
    $target=$dir.'/'.$new;
    if($source!==$target&&(file_exists($target)||is_link($target)))nadin_legacy_error(409,'A file with this name already exists. Nothing was overwritten.');
    if($source!==$target&&!rename($source,$target))nadin_legacy_error(500,'Could not rename this file. Check storage permissions.');
    $message='File renamed.';
}else{
    $target=$root.'/filesdeleted/'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'!'.$uc.'!'.$file;
    if(!rename($source,$target))nadin_legacy_error(500,'Could not move this file to deleted files. Check storage permissions.');
    $message='File moved to deleted files.';
}
if(!$attachment)setcookie('rezip',$path,time()+$timeout,'/');
$next=$attachment?'uploadatt.php?gig='.rawurlencode(substr($file,0,-4)):($audio?'uploadaudio.php':'uploadpdf.php').'?tune='.rawurlencode($path);
nadin_media_reply(true,$message,array('next_url'=>$next,'next_label'=>'Return to files'));

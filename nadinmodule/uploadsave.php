<?php
require_once __DIR__.'/legacy-write.inc.php';
$foldername=basename($_POST['foldername']);
$infos=$_POST['infos'] ?? '';$links=$_POST['links'] ?? '';
if(!is_string($infos)||!is_string($links))nadin_legacy_error(400,'Enter plain text for the tune information and links.');
$data=trim($infos."\r\n".$delimiter2."\r\n".$links);$data=str_replace(array("\r\n\r\n\r\n","\n\n\n"),array("\r\n\r\n","\n\n"),$data);
$path=dirname(__DIR__).'/library/'.$foldername.'/infos.txt';
if(is_link($path)||!is_dir(dirname($path)))nadin_legacy_error(400,'The tune information path is not available.');
$tmp=dirname($path).'/.notes-'.bin2hex(random_bytes(10));
$saved=false;try{if(file_put_contents($tmp,$data)===strlen($data)){if(is_file($path))@chmod($tmp,fileperms($path)&0777);$saved=rename($tmp,$path);}}finally{if(is_file($tmp))@unlink($tmp);}
if(!$saved)nadin_legacy_error(500,'Could not save the notes. Check storage permissions; your changes remain in the editor.');
nadin_media_reply(true,'Tune notes saved.',array('revision'=>hash('sha256',$data),'next_url'=>'upload.php?tune='.rawurlencode($foldername)));

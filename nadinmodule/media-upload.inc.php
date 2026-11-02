<?php
require_once __DIR__.'/legacy-write.inc.php';
require_once __DIR__.'/iniset.inc.php';
$kind=$nadinUploadKind ?? ''; $attachment=$kind==='attachment';$audio=$kind==='audio';
require_once __DIR__.'/audio-convert.inc.php';
$name=$attachment?($_GET['gig'] ?? ''):($_POST['name'] ?? '');$root=dirname(__DIR__);
if(!is_string($name)||$name===''||basename($name)!==$name||$name[0]==='.')nadin_legacy_error(400,'Choose an existing tune or gig.');
if($attachment){
    if(!is_file($root.'/gigreps/'.$name.'.txt')&&!is_file($root.'/gigrepsarchiv/'.$name.'.txt'))nadin_legacy_error(404,'This gig no longer exists.');
    $dir=$root.'/abo/zip';
}else $dir=$root.'/library/'.$name;
if(!is_dir($dir)||(!$attachment&&is_link($dir)))nadin_legacy_error(404,'The destination folder is not available.');
$raw=$_FILES['userfile'] ?? null;$files=array();
if(is_array($raw)&&is_array($raw['name'] ?? null)){
    foreach($raw['name'] as $i=>$unused){$f=array();foreach(array('name','type','tmp_name','error','size') as $key)$f[$key]=$raw[$key][$i] ?? null;$files[]=$f;}
}elseif(is_array($raw))$files[]=$raw;
$max=$attachment||$audio?1:max(1,(int)ini_get('max_file_uploads')-1);
if(!$files||count($files)>$max)nadin_legacy_error(400,'Choose between 1 and '.$max.' files for this upload.');
require $root.'/configallowedfileextensions.php';$prepared=array();$seen=array();
foreach($files as $file){
    if(($file['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_string($file['tmp_name'])||!is_uploaded_file($file['tmp_name']))nadin_legacy_error(400,'The upload did not arrive completely. Check the file size and choose the file again.');
    if(!is_string($file['name'])||!is_numeric($file['size'])||(int)$file['size']<1)nadin_legacy_error(400,'Choose a non-empty file with a normal filename.');
    $original=basename($file['name']);$ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
    if($original===''||$original[0]==='.'||preg_match('/[\\\\\x00-\x1f\x7f]/',$original))nadin_legacy_error(400,'Choose a file with a normal filename.');
    if(($attachment&&$ext!=='pdf')||($audio&&$ext!=='mp3'&&!isset(nadin_audio_formats()[$ext]))||(!$attachment&&!$audio&&(!in_array($ext,$okext,true)||in_array($ext,array('txt','mp3','php','phar','phtml'),true))))nadin_legacy_error(400,$attachment?'Choose a PDF file.':($audio?'Choose an MP3 or an audio format listed on the upload page.':'This file type is not allowed here. Use the audio page for MP3 recordings.'));
    $filename=$attachment?$name.'.pdf':($audio?str_replace('..','.',mkascii(striptime($original))):mkascii($original));
    if($audio)$filename=nadin_audio_filename($original);
    if(isset($seen[$filename]))nadin_legacy_error(400,'Two selected files have the same destination name. Upload them separately with distinct names.');
    $seen[$filename]=true;$target=$dir.'/'.$filename;
    if(is_link($target)||is_dir($target))nadin_legacy_error(400,'This filename cannot be replaced.');
    $prepared[]=array('upload'=>$file,'filename'=>$filename,'target'=>$target,'extension'=>$ext);
}
$stored=array();$convertedAudio=false;
try{
    foreach($prepared as $item){
        $temporary=$dir.'/.upload-'.bin2hex(random_bytes(12));$backup=null;$converted=null;
        try{
            if($audio&&$item['extension']!=='mp3'){
                $destination=nadin_audio_destination_snapshot($dir,$item['filename']);
                flock($legacyLock,LOCK_UN);
                try{$converted=nadin_audio_convert($item['upload']['tmp_name'],$item['extension']);}
                finally{if(!flock($legacyLock,LOCK_EX))throw new RuntimeException('The file store could not be locked.');}
                if(nadin_audio_destination_snapshot($dir,$item['filename'])!==$destination)throw new NadinAudioException('The tune or recording changed while audio was being converted. Reload its file list and retry. No recording was replaced.',409);
                if(!copy($converted,$temporary)||filesize($temporary)!==filesize($converted))throw new RuntimeException('The converted recording could not be staged.');
                $convertedAudio=true;
            }else{
                if(!move_uploaded_file($item['upload']['tmp_name'],$temporary))throw new RuntimeException('The server could not store the uploaded file.');
            }
            // The folder or target may have changed while the converter was running.
            if(is_link($item['target'])||is_dir($item['target']))throw new RuntimeException('This filename cannot be replaced.');
            $previous=$audio?nadin_audio_previous($dir,$item['filename']):(is_file($item['target'])?$item['target']:null);
            if($previous!==null){
                $backup=$attachment?$root.'/filesdeleted/'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'!'.$item['filename']:$dir.'/zzDEL!'.date('YmdHis').'_'.bin2hex(random_bytes(4)).basename($previous);
                if(!rename($previous,$backup))throw new RuntimeException('The previous version could not be retained.');
            }
            if(!rename($temporary,$item['target'])){if($backup)@rename($backup,$previous);throw new RuntimeException('The new file could not be placed in the library.');}
            $stored[]=$item['filename'];
        }finally{if(is_file($temporary))@unlink($temporary);if($converted!==null&&is_file($converted))@unlink($converted);}
    }
}catch(NadinAudioException $e){nadin_legacy_error($e->status,$e->getMessage());
}catch(Throwable $e){error_log('NADIN upload: '.$e->getMessage());nadin_legacy_error(500,($stored?count($stored).' file(s) arrived; the remaining files were not stored. ':'').'Check available storage and folder permissions, then retry only the missing files.');}
if(!$attachment)setcookie('rezip',$name,time()+$timeout,'/');
$next=$attachment?'uploadatt.php?gig='.rawurlencode($name):($audio?'uploadaudio.php':'uploadpdf.php').'?tune='.rawurlencode($name);
nadin_media_reply(true,$convertedAudio?'Audio converted to MP3 and uploaded.':count($stored).' file'.(count($stored)===1?'':'s').' uploaded.',array('files'=>array_map('nadinText',$stored),'next_url'=>$next,'next_label'=>'Return to files'));

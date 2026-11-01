<?php
require_once __DIR__.'/legacy-write.inc.php';
require_once __DIR__.'/iniset.inc.php';
$kind=$nadinUploadKind ?? ''; $attachment=$kind==='attachment';$audio=$kind==='audio';
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
    if(($attachment&&$ext!=='pdf')||($audio&&$ext!=='mp3')||(!$attachment&&!$audio&&(!in_array($ext,$okext,true)||in_array($ext,array('txt','mp3','php','phar','phtml'),true))))nadin_legacy_error(400,$attachment?'Choose a PDF file.':($audio?'Choose an MP3 recording.':'This file type is not allowed here. Use the audio page for MP3 recordings.'));
    $filename=$attachment?$name.'.pdf':($audio?str_replace('..','.',mkascii(striptime($original))):mkascii($original));
    if(isset($seen[$filename]))nadin_legacy_error(400,'Two selected files have the same destination name. Upload them separately with distinct names.');
    $seen[$filename]=true;$target=$dir.'/'.$filename;
    if(is_link($target)||is_dir($target))nadin_legacy_error(400,'This filename cannot be replaced.');
    $prepared[]=array('upload'=>$file,'filename'=>$filename,'target'=>$target);
}
$stored=array();
try{
    foreach($prepared as $item){
        $temporary=$dir.'/.upload-'.bin2hex(random_bytes(12));$backup=null;
        try{
            if(!move_uploaded_file($item['upload']['tmp_name'],$temporary))throw new RuntimeException('The server could not store the uploaded file.');
            if(is_file($item['target'])){
                $backup=$attachment?$root.'/filesdeleted/'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'!'.$item['filename']:$dir.'/zzDEL!'.date('YmdHis').'_'.bin2hex(random_bytes(4)).$item['filename'];
                if(!rename($item['target'],$backup))throw new RuntimeException('The previous version could not be retained.');
            }
            if(!rename($temporary,$item['target'])){if($backup)@rename($backup,$item['target']);throw new RuntimeException('The new file could not be placed in the library.');}
            $stored[]=$item['filename'];
        }finally{if(is_file($temporary))@unlink($temporary);}
    }
}catch(Throwable $e){error_log('NADIN upload: '.$e->getMessage());nadin_legacy_error(500,($stored?count($stored).' file(s) arrived; the remaining files were not stored. ':'').'Check available storage and folder permissions, then retry only the missing files.');}
if(!$attachment)setcookie('rezip',$name,time()+$timeout,'/');
$next=$attachment?'uploadatt.php?gig='.rawurlencode($name):($audio?'uploadaudio.php':'uploadpdf.php').'?tune='.rawurlencode($name);
nadin_media_reply(true,count($stored).' file'.(count($stored)===1?'':'s').' uploaded.',array('files'=>array_map('nadinText',$stored),'next_url'=>$next,'next_label'=>'Return to files'));

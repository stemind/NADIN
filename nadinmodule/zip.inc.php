<?php
require_once __DIR__.'/ui.inc.php';
function nadin_zip_types() {
 global $saxes,$bones,$trumpets,$rhythm,$other,$parts,$score,$recommended;
 return array('alllistento'=>array($recommended,'vgigs','Audio'),'allscore'=>array($score,'vscore','Score'),'allparts'=>array($parts,'vgigs','Parts'),'allsaxes'=>array($saxes,'vsaxes','Saxophones'),'allbones'=>array($bones,'vbones','Trombones'),'alltrumpets'=>array($trumpets,'vtrumpets','Trumpets'),'allrhythm'=>array($rhythm,'vrhythm','Rhythm'),'allother'=>array($other,'vother','Other'));
}
function nadin_zip_sources($root,$gig,$type,$mode='gigreps') {
 global $delimiter2,$hidden;
 $types=nadin_zip_types();
 if(!isset($types[$type]) || !in_array($mode,array('gigreps','gigrepsarchiv'),true) || !is_string($gig) || basename($gig)!==$gig || preg_match('/[\\\\\x00-\x1f]/',$gig))throw new InvalidArgumentException('Invalid download.');
 $rep=$root.'/'.$mode.'/'.$gig;
 if(!is_file($rep)||is_link($rep))throw new InvalidArgumentException('Gig not found.');
 list($info,$tunes)=nadinReadRep($rep,$delimiter2);$sources=array();
 foreach(array_unique($tunes) as $tune){
  if($tune===''||basename($tune)!==$tune||$tune[0]==='.'||strpos($tune,'\\')!==false)continue;
  $dir=$root.'/library/'.$tune;if(!is_dir($dir)||is_link($dir))continue;
  $selected=array();$fallback=array();
  foreach((array)glob($dir.'/*') as $f){
   if(!is_file($f)||is_link($f))continue;$name=basename($f);$ext=strtolower(pathinfo($f,PATHINFO_EXTENSION));
   if(strpos($name,'zzDEL!')===0 || ($hidden!==''&&strpos($tune.'/'.$name,$hidden)!==false))continue;
   $matches=array();foreach($types as $key=>$profile)if($profile[0]!==''&&strpos($tune.'/'.$name,$profile[0])!==false)$matches[]=$key;
   if(count($matches)!==1)continue;
   if($matches[0]===$type && ($type==='alllistento'?$ext==='mp3':$ext==='pdf'))$selected[]=$f;
   if($matches[0]==='allparts' && $ext==='pdf')$fallback[]=$f;
  }
  if(!$selected && in_array($type,array('allsaxes','allbones','alltrumpets','allrhythm','allother'),true))$selected=$fallback;
  foreach($selected as $f)$sources[$tune.'/'.basename($f)]=$f;
 }
 return $sources;
}
function nadin_zip_bundle($root,$gig,$type,$mode='gigreps'){
 $sources=nadin_zip_sources($root,$gig,$type,$mode);if(!$sources)throw new InvalidArgumentException('No files available for this download.');
 $dir=$root.'/abo/zip/'.$type;if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Cannot prepare the ZIP.');
 $target=$dir.'/'.substr($gig,0,-4).'_'.$type.'.zip';$key=hash('sha256',$target);$lockPath=$root.'/userdatei/zip-'.$key.'.php';
 if(is_link($lockPath)||is_link($target))throw new RuntimeException('Unsafe ZIP storage.');
 $handle=fopen($lockPath,'c+');if(!$handle||!flock($handle,LOCK_EX))throw new RuntimeException('Cannot lock the ZIP.');
 $tmp=$dir.'/.v12-'.bin2hex(random_bytes(12)).'.zip';
 try{
  if(!fstat($handle)['size']){fwrite($handle,"<?php http_response_code(404); exit; ?>\n");fflush($handle);}@chmod($lockPath,0600);
  // Hash the actual sources: manual replacements with unchanged dates are detected too.
  $fingerprint='';foreach($sources as $name=>$source)$fingerprint.=$name.'|'.hash_file('sha256',$source)."\n";
  $hash=hash('sha256',$fingerprint);$manifest=$target.'.sha256';
  if(is_file($target)&&is_file($manifest)&&hash_equals(trim(file_get_contents($manifest)),$hash))return $target;
  $zip=new ZipArchive();if($zip->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Cannot create the ZIP.');
  foreach($sources as $name=>$source)if(!$zip->addFile($source,$name))throw new RuntimeException('Cannot add a file to the ZIP.');
  if(!$zip->close()||!rename($tmp,$target))throw new RuntimeException('Cannot save the ZIP.');
  file_put_contents($manifest,$hash,LOCK_EX);return $target;
 }finally{if(is_file($tmp))@unlink($tmp);flock($handle,LOCK_UN);fclose($handle);}
}

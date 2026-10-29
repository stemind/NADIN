<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once dirname(__DIR__).'/nadinmodule/functions.inc.php';
require_once dirname(__DIR__).'/nadinmodule/ui.inc.php';
nadin_require_login();
$relative=$_GET['c'] ?? ''; $root=dirname(__DIR__);
if(!is_string($relative)||!preg_match('~^[^./\\\\\x00-\x1f][^/\\\\\x00-\x1f]*/[^./\\\\\x00-\x1f][^/\\\\\x00-\x1f]*$~D',$relative)){http_response_code(404);exit('File not found.');}
list($tune,$name)=explode('/',$relative,2);$file=$root.'/library/'.$relative;
if(!is_file($file)||is_link($file)||is_link($root.'/library/'.$tune)||strpos($name,'zzDEL!')===0){http_response_code(404);exit('File not found.');}
$ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
$bandAudio=$ext==='mp3'&&(strcasecmp(substr($name,0,3),substr($bb,0,3))===0||strcasecmp(substr($name,0,3),'xyz')===0);
$permitted=role('vlibrary')||($bandAudio&&role('vreclist'));
if(!$permitted && role('vgigs')){
 $folders=array('gigreps');if(role('varchiv'))$folders[]='gigrepsarchiv';
 foreach($folders as $folder)foreach(nadinRepFiles($root.'/'.$folder) as $rep){list($info,$repTunes)=nadinReadRep($root.'/'.$folder.'/'.$rep,$delimiter2);if(in_array($tune,array_map('trim',$repTunes),true)){$permitted=true;break 2;}}
}
if(!$permitted){http_response_code(403);exit('This tune is not included in your permitted gigs or library.');}
foreach(array(array($saxes,'vsaxes'),array($bones,'vbones'),array($trumpets,'vtrumpets'),array($rhythm,'vrhythm'),array($other,'vother'),array($parts,'vgigs'),array($score,'vscore'),array($hidden,'musers')) as $restriction)if($restriction[0]!==''&&strpos($relative,$restriction[0])!==false)auth($restriction[1]);
require __DIR__.'/mime.inc.php';
$inline=in_array($ext,array('pdf','mp3','mp4','m4a','wav','jpg','jpeg','gif','png','webp'),true);
header('Content-Type: '.($m[$ext] ?? 'application/octet-stream'));header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');
header("Content-Security-Policy: sandbox; default-src 'none'");
header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="NADIN.'.preg_replace('/[^a-z0-9]/','',$ext).'"; filename*=UTF-8\'\''.rawurlencode($name));
header('Content-Length: '.filesize($file));readfile($file);

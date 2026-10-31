<?php
require_once __DIR__.'/legacy-write.inc.php';require_once __DIR__.'/popup-ui.inc.php';
$tune=$_POST['tune'] ?? '';$root=dirname(__DIR__);$dir=$root.'/library/'.$tune;
if($tune!==($_GET['tune'] ?? '')||!is_dir($dir)||is_link($dir))nadin_legacy_error(400,'Choose an existing tune.');
foreach(new DirectoryIterator($dir) as $file){if($file->isDot())continue;$name=$file->getFilename();if($name!=='infos.txt'&&strpos($name,'.nadin-forscore')!==0)nadin_legacy_error(400,'This tune still contains media files. Remove them before deleting the tune.');}
foreach(array('gigreps','gigrepsarchiv','gigrepsdeleted') as $folder)foreach(nadinRepFiles($root.'/'.$folder) as $id){list($info,$entries)=nadinReadRep($root.'/'.$folder.'/'.$id,$delimiter2);if(in_array($tune,array_map('trim',$entries),true))nadin_legacy_error(409,'This tune is still used by a setlist: '.substr($id,0,-4).'. Remove the reference first.');}
$dest=$root.'/filesdeleted/'.time().'_'.bin2hex(random_bytes(4)).'!'.$tune;
if(!rename($dir,$dest))nadin_legacy_error(500,'The tune could not be moved to the deleted-files folder.');
nadinPopupStart('Tune removed');
echo '<p role="status">The empty tune folder was moved to filesdeleted and can be recovered from there.</p><button type="button" onclick="window.close()">Close</button><script>if(opener&&typeof opener.fresh==="function")opener.fresh();</script></body></html>';

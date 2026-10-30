<?php
require_once __DIR__.'/refreshcookies2.inc.php';
require_once __DIR__.'/ui.inc.php';
require_once __DIR__.'/media-time.inc.php';
$kind=$mediaListKind ?? '';auth($kind==='audio'?'upaudio':($kind==='attachment'?'mgigs':'uppdf'));
header('Content-Type: text/html; charset=UTF-8');header('Cache-Control: private, no-store');
$name=$kind==='attachment'?($_GET['gig'] ?? ''):($_GET['name'] ?? '');
if (!is_string($name)||$name===''||$name[0]==='.'||basename($name)!==$name||preg_match('/[\\\\\x00-\x1f\x7f]/',$name)) {http_response_code(404);exit('Folder not found.');}
$root=dirname(__DIR__);$dir=$kind==='attachment'?$root.'/abo/zip':$root.'/library/'.$name;
if (!is_dir($dir)||is_link($dir)) {http_response_code(404);exit('Folder not found.');}
if ($kind==='attachment' && !is_file($root.'/gigreps/'.$name.'.txt') && !is_file($root.'/gigrepsarchiv/'.$name.'.txt')) {http_response_code(404);exit('Gig not found.');}
$files=array();foreach (new DirectoryIterator($dir) as $entry) {
    $file=$entry->getFilename();if($entry->isDot()||$entry->isLink()||!$entry->isFile()||$file[0]==='.'||strpos($file,'zzDEL!')===0||$file==='infos.txt')continue;
    if ($hidden!=='' && strpos($name.'/'.$file,$hidden)!==false && !role('musers')) continue;
    $audio=strtolower(pathinfo($file,PATHINFO_EXTENSION))==='mp3';
    if (($kind==='audio'&&!$audio)||($kind==='files'&&$audio)||($kind==='attachment'&&$file!==$name.'.pdf'))continue;
    $files[$file]=array('size'=>$entry->getSize(),'time'=>$entry->getMTime());
}
if($kind==='audio')krsort($files,SORT_STRING);else ksort($files,SORT_STRING);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../style.css"><title>NADIN files</title></head><body>
<table><tbody>
<?php
$recommendedCount=0;$durationForms='';
foreach($files as $file=>$details){
    $url=$kind==='attachment'?'../get/setlist.php?'.http_build_query(array('c'=>$file,'m'=>is_file($root.'/gigreps/'.$name.'.txt')?'gigreps':'gigrepsarchiv')):'../get/file.php?c='.rawurlencode($name.'/'.$file);
    $prefix=$kind==='audio'?'AUD':($kind==='attachment'?'ATT':'PDF');
    $query=http_build_query(array('p'=>$kind==='attachment'?'zip':$name,'f'=>$file));
    echo '<tr><td><a id="'.mkid2($file).'" target="_blank" rel="noopener" href="'.nadinHtml($url).'">'.nadinHtml($file).'</a></td><td>'.date('d.m.Y H:i',$details['time']).'</td><td style="text-align:right">'.number_format($details['size']/1024,0).' kB</td><td>';
    if($kind!=='attachment')echo '<button type="button" data-rename="'.nadinHtml($prefix.'renamefile.php?'.$query).'" data-name="'.nadinHtml($kind==='audio'?striptime($file):$file).'">rename</button> ';
    echo '<button type="button" data-delete="'.nadinHtml($prefix.'deletefile.php?'.$query).'" data-name="'.nadinHtml($file).'">delete</button></td></tr>';
    if($kind==='audio'){
        if($recommended!==''&&strpos($file,$recommended)!==false)$recommendedCount++;
        if(striptime($file)===$file)$durationForms.=nadin_audio_time_form($name,$file);
    }
}
?>
</tbody></table>
<?php
if(!$files)echo '<p>No files yet.</p>';
if($kind==='audio'&&$files&&$recommendedCount!==1)echo '<p role="status">Mark exactly one recommended recording with “'.nadinHtml($recommended).'” in its filename.</p>';
echo $durationForms;
require __DIR__.'/legacy-ui.inc.php';
?>
<script>
document.addEventListener('click',function(e){
 var b=e.target.closest('button[data-rename],button[data-delete]');if(!b)return;
 if(b.dataset.rename){var value=prompt('New file name (including extension):',b.dataset.name);if(value===null||!value.trim())return;var url=new URL(b.dataset.rename,location.href);url.searchParams.set('n',value);nadinMutation(url.href);}
 else if(confirm('Delete '+b.dataset.name+'?'))nadinMutation(b.dataset.delete);
});
(function(){var id=<?php echo json_encode(is_string($_GET['m'] ?? null)?$_GET['m']:'',JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;var el=document.getElementById(id);if(el){el.style.background='yellow';el.focus();}}());
</script></body></html>

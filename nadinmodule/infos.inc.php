<?php
require_once __DIR__.'/ui.inc.php';
$attachmentGig=$selectedGig ?? ($_GET['c'] ?? '');$attachmentMode=$gigModule ?? 'gigreps';$fmt=0;$attachment='';
if (is_string($attachmentGig) && basename($attachmentGig)===$attachmentGig && substr($attachmentGig,-4)==='.txt') {
    $attachmentName=substr($attachmentGig,0,-4).'.pdf';$attachmentPath='abo/zip/'.$attachmentName;
    if (is_file($attachmentPath) && !is_link($attachmentPath)) {
        $fmt=filemtime($attachmentPath);
        $attachment='<a target="_blank" href="get/setlist.php?'.nadinHtml(http_build_query(array('m'=>$attachmentMode,'c'=>$attachmentName))).'">PDF-Anhang '.date('Y-m-d H:i',$fmt).'</a>';
    }
}
$infoId='info'.md5($infos.$fmt);
echo '<div style="float:right">';include('configlinks.inc.php');echo '</div>';
echo '<div id="'.$infoId.'" class="infos"><a style="font-size:70%" href="#" onclick="ionoff();return false">show/hide infos</a><span id="infoarea" '.($small ?? '').'><br>'.nl2br(nadinInfoHtml($infos)).$attachment.'</span></div><br>';
$infoDisplay=$_COOKIE[$infoId] ?? '';
?>
<script>
(function(){var value=<?php echo json_encode(in_array($infoDisplay,array('inline','none'),true)?$infoDisplay:''); ?>;if(value)document.getElementById('infoarea').style.display=value;})();
function ionoff() {
 var area=document.getElementById('infoarea');area.style.display=area.style.display==='none'?'inline':'none';
 document.general.location.replace('nadinmodule/infostatus.php?infodiv=<?php echo $infoId; ?>&infostatus='+area.style.display);
}
</script>

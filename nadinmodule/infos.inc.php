<?php 
$_GET['gig'] =$_GET['gig'] ?? '';
if (file_exists('abo/zip/'.str_replace('.txt','',$_GET['gig']).'.pdf')) $fmt=filemtime('abo/zip/'.str_replace('.txt','',$_GET['gig']).'.pdf');
$_GET['c'] =$_GET['c'] ?? '';
if (file_exists('abo/zip/'.str_replace('.txt','',$_GET['c']).'.pdf')) $fmt=filemtime('abo/zip/'.str_replace('.txt','',$_GET['c']).'.pdf');

echo '<div style="float:right">';
include('configlinks.inc.php');
echo '</div>';

echo '<div id="info'.md5($infos.$fmt).'" class="infos"><a style="font-size:70%" href="javascript:void(0)" onclick="ionoff(\'info'.md5($infos.$fmt).'\')">show/hide infos</a><span id="infoarea" '.$small.'><br>'.nl2br(autohref($infos));

if (file_exists('abo/zip/'.str_replace('.txt','',$_GET['gig']).'.pdf')) echo '<a target="_blank" href="get/setlist.php?c='.str_replace('.txt','',$_GET['gig']).'.pdf">PDF-Anhang '.date('Y-m-d h:i',$fmt).'</a></small>';

if (file_exists('abo/zip/'.str_replace('.txt','',$_GET['c']).'.pdf')) echo '<a target="_blank" href="get/setlist.php?c='.str_replace('.txt','',$_GET['c']).'.pdf">PDF-Anhang '.date('Y-m-d h:i',$fmt).'</a></small>';

echo '</span></div><br />';  
?>
<?php if ($_COOKIE['info'.md5($infos.$fmt)]=='inline' || $_COOKIE['info'.md5($infos.$fmt)]=='none') { ?>
<script>
if (document.getElementById('info<?php echo md5($infos.$fmt)?>')) document.getElementById('infoarea').style.display='<?php echo $_COOKIE['info'.md5($infos.$fmt)]?>';
</script>
<?php } ?>
<script>
function ionoff(infodiv) {
if (document.getElementById('infoarea').style.display=='none') {
document.getElementById('infoarea').style.display='inline';
infostatus='inline';
}
else {
document.getElementById('infoarea').style.display='none'
infostatus='none';
}
document.general.location.replace('nadinmodule/infostatus.php?infodiv='+infodiv+'&infostatus='+infostatus);
}
</script>
<?php if($rendergiglistontheleft=='yes')echo'<script>document.getElementsByClassName("infos")[0].style.marginLeft="'.$ifleftthenthiswidth.'"</script>'; ?>
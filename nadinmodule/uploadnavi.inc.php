<?php
require_once __DIR__.'/ui.inc.php';
$tune=$_GET['tune'] ?? '';
if($tune===''||!is_dir(dirname(__DIR__).'/library/'.$tune)||is_link(dirname(__DIR__).'/library/'.$tune)){http_response_code(404);echo '<p role="alert">This tune is no longer available. Return to the library and choose it again.</p>';exit;}
$popupPage=basename($_SERVER['SCRIPT_NAME'],'.php');$marker=is_string($_GET['m'] ?? null)?$_GET['m']:mkid2($tune);
?>
<nav class="nadin-popup-nav" aria-label="Manage tune">
<?php
$links=array();if(role('uppdf'))$links['uploadpdf']='Scores & files';if(role('upaudio')){$links['uploadaudio']='Recordings';$links['upload']='Info & links';}
foreach($links as $page=>$label)echo '<a href="'.$page.'.php?'.nadinHtml(http_build_query(array('tune'=>$tune,'m'=>$marker))).'" id="'.$page.'"'.($popupPage===$page?' aria-current="page"':'').'>'.nadinHtml($label).'</a>';
if(role('uppdf')){
 echo '<details class="nadin-task-options"><summary>Tune actions</summary><div>';
 foreach(array('renamefolder'=>'Rename tune','deletefolder'=>'Remove empty tune') as $page=>$label)echo '<a href="'.$page.'.php?tune='.rawurlencode($tune).'" id="'.$page.'"'.($popupPage===$page?' aria-current="page"':'').'>'.nadinHtml($label).'</a>';
 echo '</div></details>';
}
if($allowmultiplepopups==='yes'){
 echo '<label class="nadin-popup-option" for="multipop"><input id="multipop" type="checkbox"'.(($_COOKIE['multipop'] ?? '')==='yes'?' checked':'').' onchange="document.cookie=\'multipop=\'+(this.checked?\'yes\':\'no\')+\'; expires=Thu, 31 Dec 2037 12:00:00 GMT; path=/\'">Keep separate windows</label>';
}
?>
</nav>
<script>
function startmyfocus() { /* Saving must not steal focus from another window. */ }
function openerhilite(id) {try{if(opener&&!opener.closed&&typeof opener.openerhilite==='function')opener.openerhilite(id);}catch(e){}}
function openerhilite2(id) {try{if(opener&&!opener.closed&&typeof opener.openerhilite2==='function')opener.openerhilite2(id);}catch(e){}}
window.addEventListener('focus',function(){openerhilite(<?php echo json_encode($marker,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>);});
</script>

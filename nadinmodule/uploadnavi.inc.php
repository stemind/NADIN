<?php
$marker=''; $leers='';
if(isset($_GET['m']))$marker='&m='.rawurlencode($_GET['m']);
if (str_replace(' ','',trim($_GET['tune']))!=$_GET['tune']) $leers='By the way: There is whitespace in the name that could be problematic.';
if (str_replace(' ','',trim($_GET['tune']))=='' || !file_exists('../library/'.$_GET['tune']))echo'<script>alert("'.htmlXspecialchars($_GET['tune']).'\\n\\ndoes not exist in\\n\\n'.$bibliothek.'\\n\\nor it is not spelled identically.\\n\\nTo enable uploads, you must create the corresponding object there.\\n\\n'.$leers.'");self.close();</script>';
?>
<nav class="nadin-popup-nav" aria-label="Manage tune">
<?php
$popupPage=basename($_SERVER['SCRIPT_NAME'],'.php');
$popupLinks=array('upload'=>'infos & links','uploadaudio'=>'audio files');
if(role('uppdf')) $popupLinks+=array('uploadpdf'=>'PDFs & other files','renamefolder'=>'rename','deletefolder'=>'delete');
foreach($popupLinks as $page=>$label){
 if($page==='renamefolder')echo '<span class="nadin-popup-folder">Folder:</span>';
 echo '<a href="'.$page.'.php?tune='.rawurlencode($_GET['tune']).nadinHtml($marker).'" id="'.$page.'"'.($popupPage===$page?' aria-current="page"':'').'>'.nadinHtml($label).'</a>';
}
if($allowmultiplepopups==='yes'){
 echo '<label class="nadin-popup-option" for="multipop">multi popups <input id="multipop" type="checkbox"'.(($_COOKIE['multipop'] ?? '')==='yes'?' checked':'').' onchange="if(this.checked){document.cookie=\'multipop=yes; expires=Thu, 31 Dec 2037 12:00:00 GMT; path=/\';multipopcookie=\'yes\'}else {document.cookie=\'multipop=no; expires=Thu, 31 Dec 2037 12:00:00 GMT; path=/\';multipopcookie=\'no\'};if(opener)opener.fresh()"></label>';
}
?>
</nav>
<script>
function myfocus() {
self.focus();
}

function startmyfocus() {
setTimeout("myfocus()",1111)
}
setTimeout("myfocus()",333)

<?php if(($_GET['m'] ?? '')=='')$_GET['m']=mkid2($_GET['tune']);?>

multipopcookie='<?php echo ($_COOKIE['multipop'] ?? '') ?>';

function openerhilite(that) {
<?php if($autoclosepopups=='yes')echo'if(!opener)self.close();'?>
if(opener)if(opener.document.getElementById('loadmarker')) opener.openerhilite(that);
if(multipopcookie!='yes')setTimeout("openerhilite('<?php echo $_GET['m']?>')",777)
}
if(multipopcookie!='yes')setTimeout("openerhilite('<?php echo $_GET['m']?>')",777)

function openerhilite2(that) {
if(opener)if(opener.document.getElementById('loadmarker')) opener.openerhilite2(that);
if(multipopcookie!='yes')setTimeout("openerhilite2('<?php echo $_GET['m']?>')",666)
if(multipopcookie!='yes')setTimeout("openerhilite2('<?php echo $_GET['m']?>')",888)
}
</script>

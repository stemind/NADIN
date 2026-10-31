<?php 
setcookie('upos', 'upload', time()+3600*24*365*10, '/');
require('refreshcookies2.inc.php');
require_once('functions.inc.php');
auth('upaudio');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart(str_replace('_',' ',$_GET['tune'])); ?>


<?php 

$infos='';
$links='';
$infoPath='../library/'.basename($_GET['tune']).'/infos.txt';
$c=is_file($infoPath) ? file_get_contents($infoPath) : '';
$c=explode($delimiter2,$c);
$infos=$c[0];
$links=$c[1] ?? '';


?>

<?php require('uploadnavi.inc.php'); ?>

<p class="nadin-toolbar"><button title="save [Ctrl s]" id="save" class="nadin-primary" type="button" onclick="document.form.submit()">save</button>
</p>

<form name="form" action="uploadsave.php?i=<?php echo htmlXspecialchars($_GET['i'] ?? '')?>" method="post" target="su">

<h2><label for="infos">Tips &amp; info about the tune</label></h2>
<?php echo nadinPathField('foldername', $_GET['tune']); ?>
<textarea style="width:100%;height:200px" name="infos" id="infos">
<?php echo htmlXspecialchars(trim($infos)) ?>
</textarea>

<h2><label for="links">External links referring to the tune</label></h2>
<textarea style="width:100%;height:200px" name="links" id="links">
<?php 
$links = $links ?? '';
echo htmlXspecialchars(trim($links)) 
?>
</textarea>

</form>

<script>
function getchanges() {
$oldinfos=document.form.infos.value;
$oldlinks=document.form.links.value;
setTimeout("if (document.form.infos.value!=$oldinfos) alertchanges()",500);	
setTimeout("if (document.form.links.value!=$oldlinks) alertchanges()",500);	
setTimeout("getchanges()",501);
}
setTimeout("getchanges()",100);

function alertchanges() {
changes=1;	
if (parent.document.getElementById('changes')) parent.document.getElementById('changes').value=1;
document.getElementById('save').classList.remove('nadin-saved');
document.getElementById('save').classList.add('nadin-unsaved');
document.getElementById('save').innerHTML='save*';
document.getElementById('save').title='save [Ctrl s]; The sign * is displayed when unsaved changes are present.';
}

</script>

<iframe name="su" frameborder="0" width="0" height="0"></iframe>
<input type="hidden" id="changes" />

<script>
window.onbeforeunload = function() {
if (document.getElementById('changes').value==1) return('You have unsaved changes which will be lost if you are proceeding.');
self.focus();
}
</script>


<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</body></html>

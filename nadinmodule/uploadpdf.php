<?php 
setcookie('upos', 'uploadpdf', time()+3600*24*365*10, '/');
require('refreshcookies2.inc.php');
require_once('functions.inc.php');
auth('uppdf');
?>
<?php
require_once('../config.inc.php');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart(str_replace('_',' ',$_GET['tune'])); ?>

<?php require('uploadnavi.inc.php'); ?>
<h2>Existing files</h2>
<iframe title="Existing files" src="PDFfiles.php?name=<?php echo rawurlencode($_GET['tune'])?>" name="files" frameborder="0" width="100%" height="220"></iframe>


<h2>Upload further files</h2>
<iframe title="Upload files" src="PDFsendfile.php?name=<?php echo rawurlencode($_GET['tune'])?>" frameborder="0" width="100%" height="88"></iframe>
<span style="font-size:70%"><?php include('inigetmultiple.inc.php')?></span>
<p>
<div class="nadin-popup-help">
<small>
<b style="font-size:11px">Recommended file names:</b>
<table style="font-size:11px">
<tr><td>mixed score</td><td><?php echo htmlXspecialchars($_GET['tune']).'_<b>'.$parts.'</b>_.pdf'?></td></tr>
<tr><td>conductor's score</td><td><?php echo htmlXspecialchars($_GET['tune']).'_<b>'.$score.'</b>_.pdf'?></td></tr>
<tr><td>sax sheets</td><td><?php echo htmlXspecialchars($_GET['tune']).'_<b>'.$saxes.'</b>_.pdf'?></td></tr>
<tr><td>trombone sheets</td><td><?php echo htmlXspecialchars($_GET['tune']).'_<b>'.$bones.'</b>_.pdf'?></td></tr>
<tr><td>trumpet sheets</td><td><?php echo htmlXspecialchars($_GET['tune']).'_<b>'.$trumpets.'</b>_.pdf'?></td></tr>
<tr><td>rhythm sheets</td><td><?php echo htmlXspecialchars($_GET['tune']).'_<b>'.$rhythm.'</b>_.pdf'?></td></tr>
<tr><td>other sheets</td><td><?php echo htmlXspecialchars($_GET['tune']).'_<b>'.$other.'</b>_.pdf'?></td></tr>
<tr><td>hidden* files</td><td><?php echo htmlXspecialchars($_GET['tune']).'_<b>'.$hidden.'</b>_anytext_.anyext'?></td></tr>
</table>
<small>*visible to admins, suitable for Sibelius files etc.</small>
</small>
</div>

</body>
</html>

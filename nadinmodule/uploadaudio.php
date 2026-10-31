<?php 
setcookie('upos', 'uploadaudio', time()+3600*24*365*10, '/');
require('refreshcookies2.inc.php');
require_once('functions.inc.php');
auth('upaudio');
?>
<?php
require_once('../config.inc.php');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart(str_replace('_',' ',$_GET['tune'])); ?>

<?php require('uploadnavi.inc.php'); ?>
<h2>Existing files</h2>
<iframe title="Existing recordings" src="AUDfiles.php?name=<?php echo rawurlencode($_GET['tune'])?>&m=<?php echo $_GET['m'] ?>" name="files" frameborder="0" width="100%" height="250"></iframe>


<h2>Upload further files</h2>
<iframe title="Upload a recording" src="AUDsendfile.php?name=<?php echo rawurlencode($_GET['tune'])?>" frameborder="0" width="100%" height="88"></iframe>
<span style="font-size:70%"><?php include('iniget.inc.php')?></span>
<p>
<div class="nadin-popup-help">
<small>Mark the recording that best fits the arrangement with the string RCMD in the filename! Mark only one tune per arrangement that way!</small>
<p><small>Name your audio file in a self-explanatory way before uploading it, or, after uploading, with Rename; in both cases, use an as-yet unused name.<p><font color=red>PLEASE NOTE:</font> <?php echo $bb ?> recordings are recordings of your band and must be named in the following manner:<br><b><?php echo $bb ?>_2014-12-31_optGigName_TuneName.mp3</b> or (see above) <b><?php echo $bb ?>_2014-12-31_optGigName_TuneName_<?php echo $recommended ?>.mp3</b>.</small>
</div>

</body>
</html>
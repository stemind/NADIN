<?php 
setcookie('upos', 'deletefolder', time()+3600*24*365*10, '/');
require('refreshcookies2.inc.php');
require_once('functions.inc.php');
auth('uppdf');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart(str_replace('_',' ',$_GET['tune'])); ?>

<?php require('uploadnavi.inc.php'); ?>

<h2>Delete tune</h2>

<form name="form" action="deletefolder2.php?tune=<?php echo rawurlencode($_GET['tune'])?>" method="post">

<p>Only an empty tune that is no longer used in a setlist can be removed. Its folder is kept in the deleted-files folder.</p>
<?php echo nadinPathField('tune', $_GET['tune']); ?>
<button class="nadin-danger" type="submit">Delete empty tune</button>

</form>

<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</body></html>

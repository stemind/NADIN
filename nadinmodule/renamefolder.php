<?php 
setcookie('upos', 'renamefolder', time()+3600*24*365*10, '/');
require('refreshcookies2.inc.php');
require_once('functions.inc.php');
auth('uppdf');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart(str_replace('_',' ',$_GET['tune'])); ?>

<?php require('uploadnavi.inc.php'); ?>

<h2>Rename tune</h2>

<form class="nadin-popup-form" name="form" action="renamefolder2.php?tune=<?php echo rawurlencode($_GET['tune'])?>" method="post">

<label for="tune-name">New name</label>
<input id="tune-name" type="text" name="tune" size="50" value="<?php echo htmlXspecialchars($_GET['tune']) ?>" />

<input class="nadin-primary" type="submit" value="Rename tune" />

</form>

<script>setTimeout("document.form.tune.focus()",555);setTimeout("document.form.tune.select()",666);</script>

<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</body></html>

<?php require ('refreshcookies2.inc.php');
auth('uppdf');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart('New tune'); ?>



<form class="nadin-popup-form" name="form" action="new2.php" method="post">

<label for="tune-name">Tune name</label>
<input id="tune-name" type="text" name="tune" size="50" />

<input class="nadin-primary" type="submit" value="Create tune" />

</form>

<script>
function myopener() {
<?php if($autoclosepopups=='yes')echo'if(!opener)self.close();'?>
setTimeout("myopener()",777);
}
setTimeout("myopener()",777);

document.form.tune.focus();
</script>
<script>document.title=document.title='<?php echo $bb ?> - '+document.title</script>


<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</body></html>

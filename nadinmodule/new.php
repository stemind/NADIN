<?php require ('refreshcookies2.inc.php');
auth('uppdf');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart('New tune'); ?>



<form class="nadin-popup-form" data-nadin-async="complete" name="form" action="new2.php" method="post">

<label for="tune-name">Tune name</label>
<input id="tune-name" type="text" name="tune" size="50" required minlength="3" maxlength="160" autocomplete="off" autofocus />

<input class="nadin-primary" type="submit" value="Create tune" />

<button type="button" data-nadin-close>Cancel</button></form>

<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</main></body></html>

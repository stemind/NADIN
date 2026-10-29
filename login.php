<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/account-page.inc.php';
$binding = nadin_form_binding();
nadin_account_head('Log in');
if (($salt ?? '') === '' || $salt === '???') { echo '<p>Set your installation salt in config.inc.php before using NADIN.</p>'; nadin_account_end(); exit; }
if (isset($_GET['error'])) echo '<p role="alert">Login was not possible. Check your email and password, or wait 15 minutes after repeated unsuccessful attempts.</p>';
if (isset($_GET['reset'])) echo '<p role="status">Your password has been changed. Log in with your new password.</p>';
$members = nadin_auth()->members();
if (count($members) === 1 && isset($members['nadin@nadin.na']) && !nadin_auth()->hasPersonal('nadin@nadin.na')) echo '<p>Initial setup: <strong>nadin@nadin.na</strong> / <strong>'.nadin_h(nadin_auth()->initial('nadin@nadin.na')).'</strong>. Create your administrator account, then remove this setup account.</p>';
?>
<form action="login2.php" method="post">
<?php nadin_csrf_field('login', $binding); ?>
<label for="u">Email address</label><input id="u" name="u" type="email" autocomplete="username" required autofocus>
<label for="p">Password</label><input id="p" name="p" type="password" autocomplete="current-password" required>
<button type="submit">Log in</button>
</form>
<p><a href="bestellen.php">First login or forgotten password?</a></p>
<?php nadin_account_end(); ?>

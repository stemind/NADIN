<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/account-page.inc.php';
$binding = nadin_form_binding(); nadin_account_head('First login or forgotten password');
if (isset($_GET['sent'])) echo '<p role="status">If this is an active registered address and the request limit has not been reached, we have requested an email. Check your inbox and spam folder. If nothing arrives, contact your band administrator.</p>';
if (isset($_GET['error'])) echo '<p role="alert">The form has expired. Please try again.</p>';
?>
<p>Enter the email address registered with your band. You receive your initial password, or a recovery code if you have chosen a personal password.</p>
<form action="bestellen2.php" method="post">
<?php nadin_csrf_field('recovery', $binding); ?>
<label for="u">Email address</label><input id="u" name="u" type="email" autocomplete="username" required>
<button type="submit">Email my access information</button>
</form>
<p><a href="reset-password.php">I have a recovery code</a></p>
<p><a href="login.php">Back to login</a></p>
<?php nadin_account_end(); ?>

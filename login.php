<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/account-page.inc.php';
$binding = nadin_form_binding();
nadin_account_head('Log in');
if (($salt ?? '') === '' || $salt === '???') { echo '<p>Set your installation salt in config.inc.php before using NADIN.</p>'; nadin_account_end(); exit; }
$loginRetry = max(0, (int)($loginRetry ?? 0));
$loginEmail = $loginEmail ?? '';
if (!empty($loginError)) echo '<p role="alert">'.nadin_h($loginError).'</p>';
elseif (isset($_GET['error'])) echo '<p role="alert">Login was not possible. Check your email and password and try again.</p>';
if ($loginRetry) echo '<p id="login-wait" role="status" data-retry-after="'.$loginRetry.'">Too many unsuccessful attempts. Try again in <span>'.ceil($loginRetry/60).'</span> minute(s). The waiting time ends automatically.</p>';
if (isset($_GET['organisation_logout'])) echo '<p role="status">You are signed out of NADIN. Your organisation may still have its own active sign-in session.</p>';
if (isset($_GET['reset'])) echo '<p role="status">Your password has been changed. Log in with your new password.</p>';
$members = nadin_auth()->members();
if (count($members) === 1 && isset($members['nadin@nadin.na']) && !nadin_auth()->hasPersonal('nadin@nadin.na')) echo '<p>Initial setup: <strong>nadin@nadin.na</strong> / <strong>'.nadin_h(nadin_auth()->initial('nadin@nadin.na')).'</strong>. Create your administrator account, then remove this setup account.</p>';
?>
<form action="login2.php" method="post">
<?php nadin_csrf_field('login', $binding); ?>
<label for="u">Email address</label><input id="u" name="u" type="email" autocomplete="username" required autofocus maxlength="254" value="<?php echo nadin_h($loginEmail); ?>">
<label for="p">Password</label><input id="p" name="p" type="password" autocomplete="current-password" required maxlength="1024">
<button type="submit">Log in</button>
</form>
<p><a href="bestellen.php">First login or forgotten password?</a></p>
<?php
require_once __DIR__.'/nadinmodule/federation.inc.php';
foreach (array('oidc','saml') as $provider) {
    try {
        $p=nadin_federation_provider($provider);nadin_federation_dependencies($provider);
        if (!nadin_federation_https()) continue;
        echo '<form class="nadin-federation-login" action="federation.php" method="post">';
        nadin_csrf_field('federation-start',$binding);
        echo '<input type="hidden" name="provider" value="'.$provider.'"><button type="submit">'.nadin_h($p['label']).'</button></form>';
    } catch (Throwable $e) { /* Local login is independent of optional provider configuration. */ }
}
nadin_account_end(); ?>

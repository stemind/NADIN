<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/account-page.inc.php';
require_once __DIR__.'/nadinmodule/account-mail.inc.php';
$binding = nadin_form_binding(); $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!nadin_auth()->csrf('reset', $binding, $_POST['csrf'] ?? '')) $error = 'This form has expired. Reload the page and try again.';
    elseif ($error = NadinAuth::passwordError($_POST['new_password'] ?? '', $_POST['confirm_password'] ?? '')) {}
    elseif (!nadin_auth()->reset($_POST['u'] ?? '', $_POST['code'] ?? '', $_POST['new_password'], $_POST['confirm_password'])) $error = 'This code is invalid or expired, or too many attempts were made. Request a new code or try again later.';
    else {
        $email = NadinAuth::email($_POST['u']);
        if (!nadin_account_mail($email, 'NADIN password reset', 'Your NADIN password has been reset. All previous sessions have ended. If this was not you, contact your band administrator and request a new recovery code.')) error_log('NADIN: password-reset notification could not be sent.');
        header('Location: login.php?reset=1', true, 303); exit;
    }
}
nadin_account_head('Set a new password');
if ($error) echo '<p role="alert">'.nadin_h($error).'</p>';
?>
<form method="post">
<?php nadin_csrf_field('reset', $binding); ?>
<label for="u">Email address</label><input id="u" name="u" type="email" autocomplete="username" required>
<label for="code">Recovery code from your email</label><input id="code" name="code" type="text" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" required maxlength="32">
<?php nadin_password_fields(); ?>
<button type="submit">Set new password</button>
</form><p><a href="bestellen.php">Request another code</a> | <a href="login.php">Back to login</a></p>
<?php nadin_account_end(); ?>

<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/account-page.inc.php';
require_once __DIR__.'/nadinmodule/account-mail.inc.php';
nadin_require_login();
$error = ''; $done = false; $token = nadin_session_token();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!nadin_auth()->csrf('change', $token, $_POST['csrf'] ?? '')) $error = 'This form has expired. Reload the page and try again.';
    elseif ($error = NadinAuth::passwordError($_POST['new_password'] ?? '', $_POST['confirm_password'] ?? '')) {}
    else {
        $newToken = nadin_auth()->change($token, $_POST['current_password'] ?? '', $_POST['new_password'], $_POST['confirm_password']);
        if (!$newToken) $error = 'The current password is not correct, or too many attempts were made. Try again later.';
        else {
            nadin_set_session($newToken); $token = $newToken; $done = true;
            if (!nadin_account_mail($uc,'NADIN password changed',"Your NADIN password has been changed. The initial password and all previous sessions are now invalid. If this was not you, use password recovery and contact your band administrator.")) error_log('NADIN: password-change notification could not be sent.');
        }
    }
}
nadin_account_head('Personal password');
if ($error) echo '<p role="alert">'.nadin_h($error).'</p>';
if ($done) echo '<p role="status">Password changed. You remain logged in here. All other sessions have ended.</p>';
else {
    echo '<p>'.nadin_h($uc).'</p><p>You can keep using your initial password until you choose a personal one. After this change, only your personal password will work. Your administrator cannot see it.</p><form method="post">';
    nadin_csrf_field('change', $token);
    echo '<label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required>';
    nadin_password_fields(); echo '<button type="submit">Save personal password</button></form>';
}
echo '<p><a href="index.php">Back to NADIN</a></p>'; nadin_account_end();

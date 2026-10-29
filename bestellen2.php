<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/account-mail.inc.php';
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !nadin_auth()->csrf('recovery', nadin_form_binding(), $_POST['csrf'] ?? '')) { header('Location: bestellen.php?error=1', true, 303); exit; }
$started = microtime(true);
$recovery = nadin_auth()->recovery($_POST['u'] ?? '');
if ($recovery) {
    $body = "Your access to NADIN is personal. Sheet music and recordings must only be used in the context of ".$bb.".\n\nUsername: ".$recovery['to']."\n\n";
    if ($recovery['kind'] === 'initial') $body .= "Your initial password: ".$recovery['secret']."\n\nOpen your band's NADIN login as usual. You may choose a personal password after logging in. The initial password then stops working.";
    else $body .= "Your one-time recovery code: ".$recovery['secret']."\n\nValid for 30 minutes. Open your band's NADIN login as usual, choose 'First login or forgotten password?' and then 'I have a recovery code'. Enter this code and your new password.\n\nYour current password remains valid until you complete the change. If you did not request this code, ignore this email. Do not forward the code.";
    if (!nadin_account_mail($recovery['to'], 'Your NADIN access', $body)) error_log('NADIN: access email could not be sent.');
}
// Equal minimum response time for unknown, initial-password and personal-password accounts.
$remaining = 0.5-(microtime(true)-$started); if ($remaining > 0) usleep((int)($remaining*1000000));
header('Location: bestellen.php?sent=1', true, 303); exit;

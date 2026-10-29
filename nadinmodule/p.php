<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
auth('musers');
header('Content-Type: text/html; charset=UTF-8'); header('Cache-Control: no-store');
function access_h($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NADIN access details</title><link rel="stylesheet" href="../style.css"></head><body>
<h2>Access details</h2>
<p>Give new members their email address, initial password and the usual NADIN address. They can log in immediately and choose a personal password later.</p>
<p>A personal password is never displayed. Its owner can request a recovery code from the login page. The original initial password no longer works.</p>
<table><thead><tr><th>Email address</th><th>Access</th></tr></thead><tbody>
<?php
$members = nadin_auth()->members(); ksort($members);
foreach ($members as $email=>$member) {
    if ($member['expires'] < time() || empty($member['rights']['vgigs'])) continue;
    $status = nadin_auth()->hasPersonal($email) ? 'Personal password set' : 'Initial password: '.nadin_auth()->initial($email);
    echo '<tr><td>'.access_h($email).'</td><td>'.access_h($status).'</td></tr>';
}
?>
</tbody></table></body></html>

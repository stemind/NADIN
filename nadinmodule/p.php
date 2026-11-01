<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
auth('musers');
header('Content-Type: text/html; charset=UTF-8'); header('Cache-Control: no-store');
function access_h($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
require_once __DIR__.'/popup-ui.inc.php';
nadinPopupStart('Access details');
$members = array_filter(nadin_auth()->members(), function($member) {
    return $member['expires'] >= time() && !empty($member['rights']['vgigs']);
});
ksort($members);
?>
<p>Give new members their email address, initial password and the usual NADIN address. They can log in immediately and choose a personal password later.</p>
<p>A personal password is never displayed. Its owner can request a recovery code from the login page. The original initial password no longer works.</p>
<div class="nadin-file-toolbar"><label for="nadin-media-search">Find a person</label><input type="search" id="nadin-media-search"><span id="nadin-media-count" data-count-label="people" role="status"><?php echo count($members); ?> people</span></div><table class="nadin-media-table nadin-access-table"><thead><tr><th>Email address</th><th>Access</th></tr></thead><tbody>
<?php
foreach ($members as $email=>$member) {
    $status = nadin_auth()->hasPersonal($email) ? 'Personal password set' : '<details class="nadin-access-secret"><summary>Show initial password</summary><span>Initial password: <code>'.access_h(nadin_auth()->initial($email)).'</code></span></details>';
    echo '<tr><td>'.access_h($email).'</td><td>'.$status.'</td></tr>';
}
?>
</tbody></table></main></body></html>

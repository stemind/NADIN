<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
auth('musers');
header('Content-Type: text/html; charset=UTF-8'); header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !nadin_auth()->csrf('users', nadin_session_token(), $_POST['csrf'] ?? '')) { http_response_code(403); exit('This form has expired. Reload the user editor before saving.'); }
try { nadin_auth()->saveMembers($_POST['users'] ?? null); }
catch (RuntimeException $e) { http_response_code(500); exit('The user list could not be saved. Check storage permissions and try again.'); }
?>
<!doctype html><html><head><meta charset="utf-8"><title>Users saved</title></head><body>
Users saved.
<script>
if (parent.document.getElementById('save')) {
var b=parent.document.getElementById('save');b.style.background='lightgreen';b.style.color='black';b.style.fontWeight='normal';b.textContent='s';b.title='save [Ctrl s]';
}
if(parent.document.getElementById('changes'))parent.document.getElementById('changes').value=0;
parent.changes=0;
</script></body></html>

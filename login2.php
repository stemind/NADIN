<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
header('Cache-Control: no-store');
$started = microtime(true);
$token = false;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php', true, 303); exit; }
$validForm = $salt !== '???' && $salt !== '' && nadin_auth()->csrf('login', nadin_form_binding(), $_POST['csrf'] ?? '');
if ($validForm) {
    $token = nadin_auth()->login($_POST['u'] ?? '', $_POST['p'] ?? '');
}
$remaining = 0.5-(microtime(true)-$started); if ($remaining > 0) usleep((int)($remaining*1000000));
if ($token) {
    nadin_set_session($token);
    // Retire old reusable credentials without using them as an authentication fallback.
    setcookie('u', '', time()-3600, '/'); setcookie('p', '', time()-3600, '/');
    writelog('login;'.cleancsv(NadinAuth::email($_POST['u'])));
    header('Location: index.php', true, 303);
} else {
    $loginEmail = NadinAuth::email($_POST['u'] ?? '');
    $loginRetry = nadin_auth()->loginRetryAfter();
    http_response_code($loginRetry ? 429 : ($validForm ? 401 : 403));
    if ($loginRetry) header('Retry-After: '.$loginRetry);
    $loginError = $validForm ? ($loginRetry ? '' : 'Login was not possible. Check your email and password and try again.') : 'This form has expired. Please try again.';
    require __DIR__.'/login.php';
}
exit;

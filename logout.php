<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
$method=nadin_auth()->sessionMethod(nadin_session_token());
nadin_auth()->logout(nadin_session_token());
nadin_cookie(nadin_cookie_name(), '', time()-3600);
setcookie('u', '', time()-3600, '/'); setcookie('p', '', time()-3600, '/');
writelog('logout;'.cleancsv($uc));
header('Location: login.php'.(in_array($method,array('oidc','saml'),true) ? '?organisation_logout=1' : ''), true, 303); exit;

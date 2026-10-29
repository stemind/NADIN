<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
nadin_auth()->logout(nadin_session_token());
nadin_cookie(nadin_cookie_name(), '', time()-3600);
setcookie('u', '', time()-3600, '/'); setcookie('p', '', time()-3600, '/');
writelog('logout;'.cleancsv($uc));
header('Location: login.php', true, 303); exit;

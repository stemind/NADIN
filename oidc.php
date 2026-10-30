<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/oidc.inc.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
if ($_SERVER['REQUEST_METHOD']!=='GET') { header('Allow: GET');nadin_federation_error(405); }
try {
    if (!nadin_federation_https()) throw new RuntimeException('HTTPS required.');
    $pending=nadin_auth()->federationTake('oidc',$_GET['state'] ?? '',nadin_federation_binding('oidc'));
    if (!$pending || isset($_GET['error']) || isset($_GET['id_token'])) throw new RuntimeException('Expired or unsuccessful request.');
    $p=nadin_federation_provider('oidc');nadin_federation_dependencies('oidc');
    if (isset($_GET['iss']) && $_GET['iss']!==$p['issuer']) throw new RuntimeException('Wrong response issuer.');
    $subject=nadin_oidc_callback($p,$pending,$_GET['code'] ?? '');
    nadin_federation_finish($p,$subject,'oidc:'.$pending['nonce']);
} catch (Throwable $e) { nadin_federation_error(); }

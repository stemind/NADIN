<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/federation.inc.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Allow: POST');nadin_federation_error(405); }
try {
    if ($salt==='' || $salt==='???' || !nadin_federation_https() || !nadin_auth()->csrf('federation-start',nadin_federation_binding('oidc'),$_POST['csrf'] ?? '')) nadin_federation_error(403);
    $provider=$_POST['provider'] ?? '';
    $p=nadin_federation_provider($provider);nadin_federation_dependencies($provider);
    require_once __DIR__.'/nadinmodule/'.$provider.'.inc.php';
    $binding=nadin_federation_binding($provider,true);
    $url=$provider==='oidc' ? nadin_oidc_start($p,nadin_auth(),$binding) : nadin_saml_start($p,nadin_auth(),$binding);
    header('Location: '.$url,true,303);exit;
} catch (Throwable $e) { nadin_federation_error(503); }

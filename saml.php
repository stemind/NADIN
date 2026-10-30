<?php
require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/nadinmodule/functions.inc.php';
require_once __DIR__.'/nadinmodule/saml.inc.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
try {
    if (!nadin_federation_https()) throw new RuntimeException('HTTPS required.');
    $p=nadin_federation_provider('saml');nadin_federation_dependencies('saml');
    if ($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['metadata'])) {
        $settings=new OneLogin\Saml2\Settings(nadin_saml_settings($p));
        $xml=$settings->getSPMetadata();
        if ($settings->validateMetadata($xml)) throw new RuntimeException('Invalid SP metadata.');
        header('Content-Type: application/samlmetadata+xml; charset=UTF-8');echo $xml;exit;
    }
    if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Allow: POST');nadin_federation_error(405); }
    $pending=nadin_auth()->federationTake('saml',$_POST['RelayState'] ?? '',nadin_federation_binding('saml'));
    if (!$pending) throw new RuntimeException('Expired request.');
    $identity=nadin_saml_identity($_POST['SAMLResponse'] ?? '',$p,$pending);
    nadin_federation_finish($p,$identity['subject'],$identity['replay'],$identity['expires']);
} catch (Throwable $e) { nadin_federation_error(); }

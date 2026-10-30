<?php
require_once __DIR__.'/federation.inc.php';
function nadin_saml_settings($p) {
    nadin_federation_url($p['idp_sso_url'] ?? '',true);
    foreach (array('entity_id','idp_entity_id','idp_cert','sp_cert','sp_private_key') as $key) if (!is_string($p[$key] ?? null) || $p[$key]==='') throw new RuntimeException('Incomplete SAML configuration.');
    if (!openssl_x509_read($p['idp_cert']) || !openssl_x509_read($p['sp_cert']) || !openssl_x509_check_private_key($p['sp_cert'],$p['sp_private_key'])) throw new RuntimeException('Invalid SAML certificate or key.');
    $details=openssl_pkey_get_details(openssl_pkey_get_private($p['sp_private_key']));
    if (!$details || $details['type']!==OPENSSL_KEYTYPE_RSA || $details['bits']<2048) throw new RuntimeException('Use a matching RSA SP key of at least 2048 bits.');
    if (!is_string($p['subject_attribute'] ?? '')) throw new RuntimeException('Invalid SAML subject attribute.');
    return array('strict'=>true,'debug'=>false,'baseurl'=>$p['base_url'].'/',
        'sp'=>array('entityId'=>$p['entity_id'],'assertionConsumerService'=>array('url'=>$p['base_url'].'/saml.php','binding'=>OneLogin\Saml2\Constants::BINDING_HTTP_POST),'NameIDFormat'=>empty($p['subject_attribute'])?OneLogin\Saml2\Constants::NAMEID_PERSISTENT:OneLogin\Saml2\Constants::NAMEID_UNSPECIFIED,'x509cert'=>$p['sp_cert'],'privateKey'=>$p['sp_private_key']),
        'idp'=>array('entityId'=>$p['idp_entity_id'],'singleSignOnService'=>array('url'=>$p['idp_sso_url'],'binding'=>OneLogin\Saml2\Constants::BINDING_HTTP_REDIRECT),'x509cert'=>$p['idp_cert']),
        'security'=>array('authnRequestsSigned'=>true,'wantAssertionsSigned'=>true,'wantMessagesSigned'=>false,'wantXMLValidation'=>true,'wantNameId'=>true,'requestedAuthnContext'=>false,'rejectUnsolicitedResponsesWithInResponseTo'=>true,'destinationStrictlyMatches'=>true,'relaxDestinationValidation'=>false,'signatureAlgorithm'=>'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256','digestAlgorithm'=>'http://www.w3.org/2001/04/xmlenc#sha256'));
}
function nadin_saml_start($p,$auth,$binding) {
    $client=new OneLogin\Saml2\Auth(nadin_saml_settings($p));$state=bin2hex(random_bytes(32));
    $url=$client->login($state,array(),false,false,true,empty($p['subject_attribute']));
    $auth->federationStart('saml',$binding,array('policy'=>$p['policy'],'request_id'=>$client->getLastRequestID(),'started'=>time()),$state);
    return $url;
}
function nadin_saml_identity($encoded,$p,$pending) {
    if (!hash_equals($p['policy'],$pending['policy'] ?? '') || !is_string($encoded) || strlen($encoded)>1048576 || !is_string($pending['request_id'] ?? null) || $pending['request_id']==='') throw new RuntimeException('Invalid SAML response.');
    $raw=base64_decode($encoded,true);
    if ($raw===false || stripos($raw,'<!DOCTYPE')!==false || stripos($raw,'<!ENTITY')!==false) throw new RuntimeException('Invalid SAML XML.');
    $response=new OneLogin\Saml2\Response(new OneLogin\Saml2\Settings(nadin_saml_settings($p)),$encoded);
    if (!$response->isValid($pending['request_id'])) throw new RuntimeException('SAML validation failed.');
    $doc=$response->getXMLDocument();$xpath=new DOMXPath($doc);
    $xpath->registerNamespace('s','urn:oasis:names:tc:SAML:2.0:assertion');$xpath->registerNamespace('p','urn:oasis:names:tc:SAML:2.0:protocol');$xpath->registerNamespace('ds','http://www.w3.org/2000/09/xmldsig#');
    $assertions=$xpath->query('/p:Response/s:Assertion');
    if ($assertions->length!==1) throw new RuntimeException('Exactly one assertion is required.');
    $assertion=$assertions->item(0);$acs=$p['base_url'].'/saml.php';
    if ($doc->documentElement->getAttribute('Destination')!==$acs || $doc->documentElement->getAttribute('InResponseTo')!==$pending['request_id']) throw new RuntimeException('Incorrect response destination or request.');
    $issued=OneLogin\Saml2\Utils::parseSAML2Time($assertion->getAttribute('IssueInstant'));
    if ($issued<($pending['started'] ?? time())-60 || $issued>time()+60) throw new RuntimeException('Stale assertion.');
    // Require the request ID INSIDE the signed assertion, not merely on the unsigned envelope.
    $confirmation=false;
    foreach ($xpath->query('s:Subject/s:SubjectConfirmation[@Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"]/s:SubjectConfirmationData',$assertion) as $node) {
        if ($node->getAttribute('InResponseTo')!==$pending['request_id'] || $node->getAttribute('Recipient')!==$acs || !$node->hasAttribute('NotOnOrAfter')) continue;
        if (OneLogin\Saml2\Utils::parseSAML2Time($node->getAttribute('NotOnOrAfter'))<=time()-60) continue;
        if ($node->hasAttribute('NotBefore') && OneLogin\Saml2\Utils::parseSAML2Time($node->getAttribute('NotBefore'))>time()+60) continue;
        $confirmation=true;
    }
    if (!$confirmation) throw new RuntimeException('Missing signed request correlation.');
    $conditions=$xpath->query('s:Conditions',$assertion)->item(0);
    if (!$conditions || !$conditions->hasAttribute('NotOnOrAfter') || OneLogin\Saml2\Utils::parseSAML2Time($conditions->getAttribute('NotOnOrAfter'))<=time()-60 || ($conditions->hasAttribute('NotBefore') && OneLogin\Saml2\Utils::parseSAML2Time($conditions->getAttribute('NotBefore'))>time()+60)) throw new RuntimeException('Invalid assertion conditions.');
    $audiences=$xpath->query('s:Conditions/s:AudienceRestriction',$assertion);
    if ($audiences->length===0) throw new RuntimeException('Missing audience.');
    foreach ($audiences as $restriction) {
        $found=false;foreach ($xpath->query('s:Audience',$restriction) as $audience) if ($audience->textContent===$p['entity_id']) $found=true;
        if (!$found) throw new RuntimeException('Wrong assertion audience.');
    }
    foreach ($xpath->query('//ds:SignatureMethod') as $node) if (!in_array($node->getAttribute('Algorithm'),array('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256','http://www.w3.org/2001/04/xmldsig-more#rsa-sha384','http://www.w3.org/2001/04/xmldsig-more#rsa-sha512'),true)) throw new RuntimeException('Weak or unsupported SAML signature.');
    foreach ($xpath->query('//ds:DigestMethod') as $node) if (!in_array($node->getAttribute('Algorithm'),array('http://www.w3.org/2001/04/xmlenc#sha256','http://www.w3.org/2001/04/xmldsig-more#sha384','http://www.w3.org/2001/04/xmlenc#sha512'),true)) throw new RuntimeException('Weak or unsupported SAML digest.');
    $attribute=$p['subject_attribute'] ?? '';
    if ($attribute!=='') {
        $values=$response->getAttributes()[$attribute] ?? array();
        if (count($values)!==1) throw new RuntimeException('A unique stable subject attribute is required.');
        $subject=$values[0];
    } else {
        if ($response->getNameIdFormat()!==OneLogin\Saml2\Constants::NAMEID_PERSISTENT) throw new RuntimeException('Persistent NameID is required.');
        $subject=$response->getNameId();
    }
    if (!nadin_federation_subject($subject)) throw new RuntimeException('Invalid SAML subject.');
    $id=$response->getAssertionId();if (!is_string($id) || $id==='' || strlen($id)>1024) throw new RuntimeException('Invalid assertion identifier.');
    return array('subject'=>$subject,'replay'=>$id,'expires'=>$response->getSessionNotOnOrAfter());
}

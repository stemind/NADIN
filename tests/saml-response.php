<?php
// Test fixture signer. CLI only; generates no persistent key material.
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/vendor/autoload.php';
$d=json_decode(stream_get_contents(STDIN),true,32,JSON_THROW_ON_ERROR);
$esc=function($s){return htmlspecialchars($s,ENT_QUOTES|ENT_XML1,'UTF-8');};
$now=gmdate('Y-m-d\TH:i:s\Z');$until=gmdate('Y-m-d\TH:i:s\Z',time()+300);
$assertion='<s:Assertion xmlns:s="urn:oasis:names:tc:SAML:2.0:assertion" ID="_'.bin2hex(random_bytes(16)).'" Version="2.0" IssueInstant="'.$now.'"><s:Issuer>'.$esc($d['issuer']).'</s:Issuer><s:Subject><s:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">'.$esc($d['subject']).'</s:NameID><s:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><s:SubjectConfirmationData InResponseTo="'.$esc($d['request']).'" Recipient="'.$esc($d['acs']).'" NotOnOrAfter="'.$until.'"/></s:SubjectConfirmation></s:Subject><s:Conditions NotBefore="'.gmdate('Y-m-d\TH:i:s\Z',time()-30).'" NotOnOrAfter="'.$until.'"><s:AudienceRestriction><s:Audience>'.$esc($d['audience']).'</s:Audience></s:AudienceRestriction></s:Conditions><s:AuthnStatement AuthnInstant="'.$now.'"><s:AuthnContext><s:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</s:AuthnContextClassRef></s:AuthnContext></s:AuthnStatement></s:Assertion>';
$xml=OneLogin\Saml2\Utils::addSign($assertion,$d['key'],$d['cert']);
$doc=new DOMDocument();$doc->loadXML($xml);$root=$doc->documentElement;$sig=$root->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#','Signature')->item(0);$issuer=$root->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion','Issuer')->item(0);$root->insertBefore($sig,$issuer->nextSibling);
echo base64_encode('<p:Response xmlns:p="urn:oasis:names:tc:SAML:2.0:protocol" ID="_'.bin2hex(random_bytes(16)).'" Version="2.0" IssueInstant="'.$now.'" Destination="'.$esc($d['acs']).'" InResponseTo="'.$esc($d['request']).'"><s:Issuer xmlns:s="urn:oasis:names:tc:SAML:2.0:assertion">'.$esc($d['issuer']).'</s:Issuer><p:Status><p:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></p:Status>'.$doc->saveXML($root).'</p:Response>');

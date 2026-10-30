<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require_once dirname(__DIR__).'/nadinmodule/auth.inc.php';
require_once dirname(__DIR__).'/nadinmodule/oidc.inc.php';
require_once dirname(__DIR__).'/nadinmodule/saml.inc.php';
nadin_federation_dependencies('saml');$checks=0;
function ok($value,$message) { global $checks;if (!$value) throw new RuntimeException('FAIL: '.$message);$checks++; }
function fails($fn,$message) { try { $fn(); } catch (Throwable $e) { ok(true,$message);return; }ok(false,$message); }
function keys() {
    $key=openssl_pkey_new(array('private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA));openssl_pkey_export($key,$pem);
    $csr=openssl_csr_new(array('commonName'=>'NADIN test only'),$key);$cert=openssl_csr_sign($csr,null,$key,2);openssl_x509_export($cert,$public);
    return array($pem,$public,openssl_pkey_get_details($key));
}
list($key,$cert,$details)=keys();list($otherKey,$otherCert)=keys();
$b64=function($s){return rtrim(strtr(base64_encode($s),'+/','-_'),'=');};
$jwk=array('kty'=>'RSA','use'=>'sig','alg'=>'RS256','kid'=>'fixture','n'=>$b64($details['rsa']['n']),'e'=>$b64($details['rsa']['e']));
$config=array('base_url'=>'https://nadin.example/nadin','oidc'=>array('enabled'=>true,'label'=>'Organisation','issuer'=>'https://idp.example','client_id'=>'nadin','client_secret'=>'test-only','identities'=>array('subject-1'=>'member@example.invalid')),'saml'=>array('enabled'=>true,'label'=>'SAML','entity_id'=>'https://nadin.example/nadin/metadata','idp_entity_id'=>'https://idp.example/saml','idp_sso_url'=>'https://idp.example/sso','idp_cert'=>$cert,'sp_cert'=>$cert,'sp_private_key'=>$key,'identities'=>array('subject-1'=>'member@example.invalid')));
$p=nadin_oidc_settings(nadin_federation_provider('oidc',$config));$pending=array('nonce'=>'nonce-fixture','started'=>time(),'policy'=>$p['policy']);
$claims=array('iss'=>$p['issuer'],'aud'=>$p['client_id'],'sub'=>'subject-1','nonce'=>$pending['nonce'],'iat'=>time(),'exp'=>time()+300);
$token=function($c)use($key){return Firebase\JWT\JWT::encode($c,$key,'RS256','fixture');};
$identity=function($c)use($token,$jwk,$p,$pending){return nadin_oidc_identity($token($c),array('keys'=>array($jwk)),$p,$pending);};
ok($identity($claims)==='subject-1','genuine RSA ID token validates');
foreach (array('iss'=>'https://evil.example','aud'=>'another-client','azp'=>'another-client','nonce'=>'wrong','sub'=>'','iat'=>time()+500,'exp'=>time()-300) as $field=>$value) fails(function()use($identity,$claims,$field,$value){$identity(array_replace($claims,array($field=>$value)));},'reject incorrect '.$field);
fails(function()use($identity,$claims){$identity(array_replace($claims,array('aud'=>array('nadin','other'))));},'multi-audience requires azp');
ok($identity(array_replace($claims,array('aud'=>array('nadin','other'),'azp'=>'nadin')))==='subject-1','multi-audience with correct azp');
fails(function()use($claims,$otherKey,$jwk,$p,$pending){nadin_oidc_identity(Firebase\JWT\JWT::encode($claims,$otherKey,'RS256','fixture'),array('keys'=>array($jwk)),$p,$pending);},'untrusted signing key');
fails(function()use($claims,$jwk,$p,$pending){nadin_oidc_identity(Firebase\JWT\JWT::encode($claims,str_repeat('a',64),'HS256','fixture'),array('keys'=>array($jwk)),$p,$pending);},'algorithm confusion');
fails(function()use($token,$claims,$jwk,$p,$pending){nadin_oidc_identity($token($claims),array('keys'=>array($jwk,$jwk)),$p,$pending);},'duplicate key ID');
fails(function()use($claims,$key,$jwk,$p,$pending){nadin_oidc_identity(Firebase\JWT\JWT::encode($claims,$key,'RS256','fixture',array('jku'=>'https://evil.example')),array('keys'=>array($jwk)),$p,$pending);},'attacker-selected key endpoint');
foreach (array('http://idp.example','https://user:pass@idp.example','https://idp.example/#bad',"https://idp.example/\r\nLocation:evil") as $bad) fails(function()use($bad){nadin_federation_url($bad);},'unsafe HTTPS URL');
fails(function()use($p){nadin_oidc_endpoint('https://different.example/jwks',$p);},'unconfigured endpoint origin');
$pExtra=$p;$pExtra['allowed_endpoint_origins']=array('https://keys.example');ok(nadin_oidc_endpoint('https://keys.example/jwks',$pExtra)==='https://keys.example/jwks','explicit trusted origin');

// Real XML signatures: mutations below are re-signed unless testing signature corruption.
$sp=nadin_federation_provider('saml',$config);$sPending=array('policy'=>$sp['policy'],'request_id'=>'_request_fixture','started'=>time());
$_SERVER['REQUEST_URI']='/nadin/saml.php';$_SERVER['SCRIPT_NAME']='/nadin/saml.php';$_SERVER['HTTP_HOST']='untrusted-host.example';$_SERVER['SERVER_PORT']=80;
function assertion($change=array(),$signed=true) {
    global $sp,$key,$cert;
    $v=array_replace(array('subject'=>'subject-1','issuer'=>$sp['idp_entity_id'],'aud'=>$sp['entity_id'],'request'=>'_request_fixture','recipient'=>$sp['base_url'].'/saml.php','until'=>gmdate('Y-m-d\TH:i:s\Z',time()+300),'issued'=>gmdate('Y-m-d\TH:i:s\Z'),'nameid'=>'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent','id'=>'_assertion_fixture'),$change);
    foreach ($v as &$x) $x=htmlspecialchars($x,ENT_QUOTES|ENT_XML1,'UTF-8');unset($x);
    $xml='<s:Assertion xmlns:s="urn:oasis:names:tc:SAML:2.0:assertion" ID="'.$v['id'].'" Version="2.0" IssueInstant="'.$v['issued'].'"><s:Issuer>'.$v['issuer'].'</s:Issuer><s:Subject><s:NameID Format="'.$v['nameid'].'">'.$v['subject'].'</s:NameID><s:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><s:SubjectConfirmationData InResponseTo="'.$v['request'].'" Recipient="'.$v['recipient'].'" NotOnOrAfter="'.$v['until'].'"/></s:SubjectConfirmation></s:Subject><s:Conditions NotBefore="'.gmdate('Y-m-d\TH:i:s\Z',time()-60).'" NotOnOrAfter="'.$v['until'].'"><s:AudienceRestriction><s:Audience>'.$v['aud'].'</s:Audience></s:AudienceRestriction></s:Conditions><s:AuthnStatement AuthnInstant="'.$v['issued'].'" SessionIndex="_session"><s:AuthnContext><s:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</s:AuthnContextClassRef></s:AuthnContext></s:AuthnStatement></s:Assertion>';
    if (isset($change['attribute_xml'])) $xml=str_replace('</s:Assertion>',$change['attribute_xml'].'</s:Assertion>',$xml);
    if ($signed) { $xml=OneLogin\Saml2\Utils::addSign($xml,$key,$cert,$change['signature'] ?? 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');$doc=new DOMDocument();$doc->loadXML($xml);$root=$doc->documentElement;$sig=$root->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#','Signature')->item(0);$issuer=$root->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion','Issuer')->item(0);$root->insertBefore($sig,$issuer->nextSibling);$xml=$doc->saveXML(); }
    return preg_replace('/<\?xml[^>]*\?>/','',$xml);
}
function response($xml) {
    global $sp;
    return base64_encode('<p:Response xmlns:p="urn:oasis:names:tc:SAML:2.0:protocol" ID="_response_fixture" Version="2.0" IssueInstant="'.gmdate('Y-m-d\TH:i:s\Z').'" Destination="'.$sp['base_url'].'/saml.php" InResponseTo="_request_fixture"><s:Issuer xmlns:s="urn:oasis:names:tc:SAML:2.0:assertion">'.$sp['idp_entity_id'].'</s:Issuer><p:Status><p:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></p:Status>'.$xml.'</p:Response>');
}
$saml=function($xml)use($sp,$sPending){return nadin_saml_identity(response($xml),$sp,$sPending);};
ok($saml(assertion())['subject']==='subject-1','signed SAML assertion validates with fixed configured host');
foreach (array('issuer'=>'https://evil.example','aud'=>'other-sp','request'=>'_wrong','recipient'=>'https://nadin.example/nadin/saml.php.evil','until'=>gmdate('Y-m-d\TH:i:s\Z',time()-500),'issued'=>gmdate('Y-m-d\TH:i:s\Z',time()-700),'nameid'=>'urn:oasis:names:tc:SAML:2.0:nameid-format:transient') as $field=>$value) fails(function()use($saml,$field,$value){$saml(assertion(array($field=>$value)));},'SAML rejects '.$field);
fails(function()use($saml){$saml(assertion(array('request'=>'')));},'missing signed correlation despite matching envelope');
fails(function()use($saml){$saml(assertion(array(),false));},'unsigned assertion');
fails(function()use($saml){$saml(str_replace('subject-1','subject-2',assertion()));},'signed subject tampering');
fails(function()use($saml){$saml(assertion().assertion(array('id'=>'_other')));},'multiple/wrapped assertions');
fails(function()use($sp,$otherCert,$sPending){$sp['idp_cert']=$otherCert;nadin_saml_identity(response(assertion()),$sp,$sPending);},'untrusted IdP certificate');
fails(function()use($saml){$saml(assertion(array('signature'=>'http://www.w3.org/2000/09/xmldsig#rsa-sha1')));},'SHA1 signature rejected');
$attributeSp=$sp;$attributeSp['subject_attribute']='urn:example:stable-subject';
$attributeXml='<s:AttributeStatement><s:Attribute Name="urn:example:stable-subject"><s:AttributeValue>subject-1</s:AttributeValue></s:Attribute></s:AttributeStatement>';
$attributeResult=nadin_saml_identity(response(assertion(array('nameid'=>'urn:oasis:names:tc:SAML:2.0:nameid-format:transient','attribute_xml'=>$attributeXml))),$attributeSp,$sPending);
ok($attributeResult['subject']==='subject-1','exact immutable attribute supports non-persistent NameID');
fails(function()use($attributeSp,$sPending){nadin_saml_identity(response(assertion()),$attributeSp,$sPending);},'missing configured attribute has no email/NameID fallback');
fails(function()use($attributeSp,$sPending,$attributeXml){$xml=str_replace('</s:Attribute>','<s:AttributeValue>subject-2</s:AttributeValue></s:Attribute>',$attributeXml);nadin_saml_identity(response(assertion(array('attribute_xml'=>$xml))),$attributeSp,$sPending);},'multiple subject attribute values rejected');
$settings=new OneLogin\Saml2\Settings(nadin_saml_settings($sp));$metadata=$settings->getSPMetadata();
ok(!$settings->validateMetadata($metadata) && strpos($metadata,'PRIVATE KEY')===false,'valid public metadata contains no private key');

$root=sys_get_temp_dir().'/nadin-federation-'.bin2hex(random_bytes(6));mkdir($root);mkdir($root.'/userdatei');
file_put_contents($root.'/userdatei/userdatei.txt',"Member,member@example.invalid,31.12.2099,vgigs=1,\nExpired,expired@example.invalid,01.01.2000,vgigs=1,\n");
$enabled=true;$policy=$p['policy'];$map=$p['identities'];
$auth=new NadinAuth($root,'fixture',3600,false,'192.0.2.1',function($email,$context)use(&$enabled,&$policy,&$map){return $enabled && $context['policy']===$policy && ($map[$context['subject']] ?? '')===$email;});
$binding=str_repeat('a',64);$state=$auth->federationStart('oidc',$binding,$pending);
ok($auth->federationTake('oidc',$state,str_repeat('b',64))===null,'wrong browser rejected without consuming');
ok($auth->federationTake('saml',$state,$binding)===null,'wrong protocol rejected');
ok($auth->federationTake('oidc',$state,$binding)===$pending,'correct browser consumes once');
ok($auth->federationTake('oidc',$state,$binding)===null,'callback replay rejected');
$context=array('provider'=>'oidc','subject'=>'subject-1','policy'=>$policy);
$t=$auth->federatedLogin('member@example.invalid',$context,'first',time()+600);
ok(is_string($t) && $auth->session($t)==='member@example.invalid' && $auth->sessionMethod($t)==='oidc','federated session works');
ok(!$auth->active('member@example.invalid','musers'),'federation never grants administrator');
ok(!$auth->federatedLogin('member@example.invalid',$context,'first',time()+600),'assertion replay rejected');
ok(!$auth->federatedLogin('unknown@example.invalid',$context,'unknown',time()+600),'no automatic enrolment');
$enabled=false;ok($auth->session($t)==='','disabled provider revokes sessions');$enabled=true;
$policy='changed';ok($auth->session($t)==='','changed configuration revokes sessions');$policy=$p['policy'];
$map=array();ok($auth->session($t)==='','removed mapping revokes sessions');$map=$p['identities'];
$map['subject-1']='expired@example.invalid';ok(!$auth->federatedLogin('expired@example.invalid',$context,'expired',time()+600),'expired local account cannot sign in externally');$map=$p['identities'];
$local=$auth->login('member@example.invalid',$auth->initial('member@example.invalid'));ok($auth->session($local)==='member@example.invalid','simple local onboarding retained');
$enabled=false;ok($auth->session($local)==='member@example.invalid','local session independent of provider');$enabled=true;
$state=$auth->federationStart('oidc',$binding,$pending);$path=$root.'/userdatei/auth-v11.php';$db=json_decode(substr(file_get_contents($path),strlen(NadinAuth::GUARD)),true);$db['federationRequests'][hash('sha256',$state)]['until']=time()-1;file_put_contents($path,NadinAuth::GUARD.json_encode($db));ok($auth->federationTake('oidc',$state,$binding)===null,'expired pending login rejected');
for ($i=0;$i<18;$i++) $auth->federationStart('oidc',$binding,$pending);
fails(function()use($auth,$binding,$pending){$auth->federationStart('oidc',$binding,$pending);},'per-browser start limit');
foreach (glob($root.'/userdatei/*') as $file) unlink($file);rmdir($root.'/userdatei');rmdir($root);
echo 'PASS: '.$checks." federation signature/storage checks\n";

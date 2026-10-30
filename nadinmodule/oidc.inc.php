<?php
require_once __DIR__.'/federation.inc.php';
function nadin_oidc_settings($p) {
    nadin_federation_url($p['issuer'] ?? '');
    if (!is_string($p['client_id'] ?? null) || $p['client_id']==='' || strlen($p['client_id'])>512) throw new RuntimeException('Invalid OIDC client.');
    $method=$p['token_endpoint_auth_method'] ?? 'client_secret_basic';
    if (!in_array($method,array('client_secret_basic','client_secret_post','none'),true) || ($method!=='none' && (!is_string($p['client_secret'] ?? null) || $p['client_secret']===''))) throw new RuntimeException('Invalid OIDC client authentication.');
    $p['token_endpoint_auth_method']=$method;
    $p['allowed_endpoint_origins']=$p['allowed_endpoint_origins'] ?? array();
    if (!is_array($p['allowed_endpoint_origins'])) throw new RuntimeException('Invalid endpoint origins.');
    foreach ($p['allowed_endpoint_origins'] as $origin) if (nadin_federation_origin($origin)!==$origin) throw new RuntimeException('Use exact HTTPS origins.');
    return $p;
}
function nadin_oidc_endpoint($url,$p) {
    nadin_federation_url($url,true);
    if (!in_array(nadin_federation_origin($url),array_merge(array(nadin_federation_origin($p['issuer'])),$p['allowed_endpoint_origins']),true)) throw new RuntimeException('Unexpected OIDC endpoint origin.');
    return $url;
}
function nadin_oidc_discover($p) {
    $d=nadin_federation_http(rtrim($p['issuer'],'/').'/.well-known/openid-configuration',$p);
    $pkce=isset($d['code_challenge_methods_supported'])?in_array('S256',$d['code_challenge_methods_supported'],true):(($p['pkce_s256_supported'] ?? false)===true);
    if (($d['issuer'] ?? '')!==$p['issuer'] || !in_array('code',$d['response_types_supported'] ?? array(),true) || !$pkce || !in_array('RS256',$d['id_token_signing_alg_values_supported'] ?? array(),true)) throw new RuntimeException('Provider must support code flow, S256 and RS256.');
    if (!in_array($p['token_endpoint_auth_method'],$d['token_endpoint_auth_methods_supported'] ?? array('client_secret_basic'),true)) throw new RuntimeException('Unsupported token authentication.');
    $result=array();foreach (array('authorization_endpoint','token_endpoint','jwks_uri') as $key) $result[$key]=nadin_oidc_endpoint($d[$key] ?? '',$p);
    return $result;
}
function nadin_oidc_start($p,$auth,$binding) {
    $p=nadin_oidc_settings($p);$nonce=bin2hex(random_bytes(32));$verifier=bin2hex(random_bytes(32));
    $state=$auth->federationStart('oidc',$binding,array('policy'=>$p['policy'],'nonce'=>$nonce,'verifier'=>$verifier,'started'=>time()));
    $d=nadin_oidc_discover($p);
    $query=array('response_type'=>'code','client_id'=>$p['client_id'],'redirect_uri'=>$p['base_url'].'/oidc.php','scope'=>'openid','state'=>$state,'nonce'=>$nonce,'code_challenge'=>rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'='),'code_challenge_method'=>'S256');
    return $d['authorization_endpoint'].(strpos($d['authorization_endpoint'],'?')===false ? '?' : '&').http_build_query($query,'','&',PHP_QUERY_RFC3986);
}
function nadin_oidc_identity($jwt,$jwks,$p,$pending) {
    if (!is_string($jwt) || strlen($jwt)>65536 || substr_count($jwt,'.')!==2) throw new RuntimeException('Invalid ID token.');
    $head=json_decode(Firebase\JWT\JWT::urlsafeB64Decode(explode('.',$jwt)[0]),true,16,JSON_THROW_ON_ERROR);
    if (!is_array($head) || ($head['alg'] ?? '')!=='RS256' || isset($head['crit']) || isset($head['jku']) || isset($head['x5u']) || isset($head['jwk'])) throw new RuntimeException('Unsupported ID token header.');
    if (isset($head['kid']) && (!is_string($head['kid']) || strlen($head['kid'])>256)) throw new RuntimeException('Invalid key identifier.');
    if (!is_array($jwks['keys'] ?? null) || count($jwks['keys'])>100) throw new RuntimeException('Invalid signing keys.');
    $keys=array();$ids=array();
    foreach ($jwks['keys'] as $key) {
        if (!is_array($key) || ($key['kty'] ?? '')!=='RSA' || ($key['alg'] ?? 'RS256')!=='RS256' || ($key['use'] ?? 'sig')!=='sig' || (isset($key['key_ops']) && (!is_array($key['key_ops']) || !in_array('verify',$key['key_ops'],true)))) continue;
        $kid=$key['kid'] ?? '';
        if (!is_string($kid) || isset($ids[$kid])) throw new RuntimeException('Ambiguous signing key.');
        $ids[$kid]=true;
        if (isset($head['kid']) && $head['kid']!==$kid) continue;
        $parsed=Firebase\JWT\JWK::parseKey($key,'RS256');
        if (!$parsed) throw new RuntimeException('Invalid signing key.');
        $details=openssl_pkey_get_details(openssl_pkey_get_public($parsed->getKeyMaterial()));
        if (!$details || $details['bits']<2048) throw new RuntimeException('RSA keys must have at least 2048 bits.');
        $keys[]=$parsed;
    }
    if (count($keys)!==1) throw new RuntimeException('No unique trusted signing key.');
    $leeway=Firebase\JWT\JWT::$leeway;Firebase\JWT\JWT::$leeway=60;
    try { $claims=(array)Firebase\JWT\JWT::decode($jwt,$keys[0]); } finally { Firebase\JWT\JWT::$leeway=$leeway; }
    $aud=$claims['aud'] ?? null;$audiences=is_string($aud) ? array($aud) : $aud;
    if (($claims['iss'] ?? '')!==$p['issuer'] || !is_array($audiences) || !in_array($p['client_id'],$audiences,true) || (count($audiences)>1 && !isset($claims['azp'])) || (isset($claims['azp']) && $claims['azp']!==$p['client_id'])) throw new RuntimeException('Invalid token issuer or audience.');
    if (!is_int($claims['exp'] ?? null) || !is_int($claims['iat'] ?? null) || $claims['exp']<=time()-60 || $claims['iat']>time()+60 || $claims['iat']<($pending['started'] ?? time())-60 || $claims['exp']<=$claims['iat']) throw new RuntimeException('Invalid token time.');
    if (!is_string($claims['nonce'] ?? null) || !hash_equals($pending['nonce'],$claims['nonce']) || !nadin_federation_subject($claims['sub'] ?? null)) throw new RuntimeException('Invalid token nonce or subject.');
    return $claims['sub'];
}
function nadin_oidc_callback($p,$pending,$code) {
    $p=nadin_oidc_settings($p);
    if (!hash_equals($p['policy'],$pending['policy'] ?? '') || !is_string($code) || $code==='' || strlen($code)>4096) throw new RuntimeException('Expired sign-in.');
    $d=nadin_oidc_discover($p);
    $body=array('grant_type'=>'authorization_code','code'=>$code,'redirect_uri'=>$p['base_url'].'/oidc.php','code_verifier'=>$pending['verifier']);$headers=array('Content-Type: application/x-www-form-urlencoded');
    if ($p['token_endpoint_auth_method']==='client_secret_basic') $headers[]='Authorization: Basic '.base64_encode(urlencode($p['client_id']).':'.urlencode($p['client_secret']));
    else { $body['client_id']=$p['client_id'];if ($p['token_endpoint_auth_method']==='client_secret_post') $body['client_secret']=$p['client_secret']; }
    $result=nadin_federation_http($d['token_endpoint'],$p,$body,$headers);
    if (isset($result['error'])) throw new RuntimeException('Provider rejected the code.');
    $jwt=$result['id_token'] ?? null;$keys=nadin_federation_http($d['jwks_uri'],$p);
    return nadin_oidc_identity($jwt,$keys,$p,$pending);
}

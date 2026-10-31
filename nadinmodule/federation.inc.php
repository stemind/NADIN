<?php
/** Optional providers. No provider configuration or dependency is needed for local login. */
function nadin_federation_config() {
    $file=dirname(__DIR__).'/userdatei/federation.php';
    if (!is_file($file)) return array();
    if (is_link($file)) throw new RuntimeException('Unsafe federation configuration.');
    if (!defined('NADIN_FEDERATION_CONFIG')) define('NADIN_FEDERATION_CONFIG',true);
    $config=require $file;
    if (!is_array($config)) throw new RuntimeException('Invalid federation configuration.');
    return $config;
}
function nadin_federation_url($url,$query=false) {
    if (!is_string($url) || strlen($url)>2048 || preg_match('/[\x00-\x20\x7f\\\\]/',$url)) throw new RuntimeException('Invalid HTTPS URL.');
    $p=parse_url($url);
    if (!$p || ($p['scheme'] ?? '')!=='https' || empty($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['fragment']) || (!$query && isset($p['query']))) throw new RuntimeException('An explicit HTTPS URL is required.');
    return $url;
}
function nadin_federation_origin($url) {
    nadin_federation_url($url,true);$p=parse_url($url);
    return 'https://'.strtolower($p['host']).((isset($p['port']) && $p['port']!==443) ? ':'.$p['port'] : '');
}
function nadin_federation_provider($provider,$config=null) {
    if (!in_array($provider,array('oidc','saml'),true)) throw new RuntimeException('Unknown provider.');
    $config=$config ?? nadin_federation_config();$p=$config[$provider] ?? null;
    if (!is_array($p) || ($p['enabled'] ?? false)!==true) throw new RuntimeException('Provider is disabled.');
    $p['base_url']=rtrim(nadin_federation_url($config['base_url'] ?? ''),'/');
    $p['provider']=$provider;
    if (!is_string($p['label'] ?? null) || strlen($p['label'])>80 || trim($p['label'])==='') throw new RuntimeException('Set a short sign-in label.');
    if (!is_array($p['identities'] ?? null)) throw new RuntimeException('Configure explicit identity mappings.');
    foreach ($p['identities'] as $subject=>$email) {
        if (!nadin_federation_subject((string)$subject) || !is_string($email) || !filter_var($email,FILTER_VALIDATE_EMAIL) || $email!==strtolower(trim($email))) throw new RuntimeException('Invalid identity mapping.');
    }
    $p['session_max_age']=$p['session_max_age'] ?? 28800;
    if (!is_int($p['session_max_age']) || $p['session_max_age']<60 || $p['session_max_age']>86400) throw new RuntimeException('Session lifetime must be 60–86400 seconds.');
    // Any provider/key/mapping change invalidates that provider's outstanding flows and sessions.
    $p['policy']=hash('sha256',json_encode($p,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    return $p;
}
function nadin_federation_subject($subject) {
    return is_string($subject) && strlen($subject)>0 && strlen($subject)<=255 && preg_match('/^[\x21-\x7e]+$/D',$subject)===1;
}
function nadin_federation_session_valid($email,$context) {
    try {
        $p=nadin_federation_provider($context['provider'] ?? '');
        return nadin_federation_subject($context['subject'] ?? null) && is_string($context['policy'] ?? null) && hash_equals($p['policy'],$context['policy']) && ($p['identities'][$context['subject']] ?? '')===$email;
    } catch (Throwable $e) { return false; }
}
function nadin_federation_dependencies($provider) {
    $file=dirname(__DIR__).'/vendor/autoload.php';
    if (!is_file($file)) throw new RuntimeException('Install federation dependencies with Composer.');
    require_once $file;
    foreach (array('openssl','curl','dom') as $extension) if (!extension_loaded($extension)) throw new RuntimeException('Missing federation PHP extension.');
    if (!class_exists($provider==='oidc' ? 'Firebase\\JWT\\JWT' : 'OneLogin\\Saml2\\Auth')) throw new RuntimeException('Missing federation dependency.');
}
function nadin_federation_https() {
    // Configure HTTPS at the trusted web server; client-supplied proxy headers are not trusted.
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') || (string)($_SERVER['SERVER_PORT'] ?? '')==='443';
}
function nadin_federation_binding($provider,$create=false) {
    $name=nadin_cookie_name($provider==='saml' ? 'saml' : 'form');$binding=$_COOKIE[$name] ?? '';
    if (!is_string($binding) || !preg_match('/^[a-f0-9]{64}$/D',$binding)) $binding='';
    if ($create && $provider==='saml') {
        $binding=$binding ?: bin2hex(random_bytes(32));
        // POST ACS needs its own cross-site cookie. The normal session remains SameSite=Lax.
        setcookie($name,$binding,array('expires'=>time()+7200,'path'=>nadin_cookie_path(),'secure'=>true,'httponly'=>true,'samesite'=>'None'));
        $_COOKIE[$name]=$binding;
    }
    return $binding;
}
function nadin_federation_http($url,$provider,$body=null,$headers=array()) {
    nadin_federation_url($url,true);$h=curl_init($url);$response='';
    if (!$h) throw new RuntimeException('Provider is unavailable.');
    $options=array(CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>array_merge(array('Accept: application/json'),$headers),CURLOPT_WRITEFUNCTION=>function($ch,$part) use(&$response) { if (strlen($response)+strlen($part)>1048576) return 0; $response.=$part;return strlen($part); });
    if (!empty($provider['ca_bundle'])) $options[CURLOPT_CAINFO]=$provider['ca_bundle'];
    if ($body!==null) { $options[CURLOPT_POST]=true;$options[CURLOPT_POSTFIELDS]=http_build_query($body,'','&',PHP_QUERY_RFC3986); }
    try {
        curl_setopt_array($h,$options);$ok=curl_exec($h);$status=curl_getinfo($h,CURLINFO_RESPONSE_CODE);
        if ($ok===false || $status!==200) throw new RuntimeException('Provider request failed.');
        $json=json_decode($response,true,32,JSON_THROW_ON_ERROR);
        if (!is_array($json) || !preg_match('/^\s*\{/',$response)) throw new RuntimeException('Invalid provider response.');
        return $json;
    } finally { unset($h); } // CurlHandle is released automatically on PHP 8+.
}
function nadin_federation_finish($p,$subject,$replay,$expires=null) {
    if (!nadin_federation_subject($subject)) throw new RuntimeException('Invalid external identity.');
    $email=$p['identities'][$subject] ?? '';
    $context=array('provider'=>$p['provider'],'subject'=>$subject,'policy'=>$p['policy']);
    $token=nadin_auth()->federatedLogin($email,$context,$replay,min(time()+$p['session_max_age'],$expires ?? PHP_INT_MAX));
    if (!$token) throw new RuntimeException('External identity has no active local access.');
    // A successful login rotates the current browser's session, without touching its password.
    nadin_auth()->logout(nadin_session_token());nadin_set_session($token);
    setcookie('u','',time()-3600,'/');setcookie('p','',time()-3600,'/');
    header('Location: index.php',true,303);exit;
}
function nadin_federation_error($status=400) {
    http_response_code($status);
    require_once __DIR__.'/account-page.inc.php';
    nadin_account_head('Organisation sign-in');
    echo '<p role="alert">Sign-in could not be completed. Start again, or use your NADIN email and password. If this continues, ask your administrator to check the provider and your account assignment.</p><p><a href="login.php">Back to login</a></p>';
    nadin_account_end();exit;
}

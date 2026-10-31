<?php
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require dirname(__DIR__).'/nadinmodule/federation.inc.php';
$failed=false;
try {
    $config=nadin_federation_config();
    foreach(array('oidc','saml') as $provider){
        if(empty($config[$provider]['enabled'])){echo strtoupper($provider).": disabled (local login remains available)\n";continue;}
        try {
            $p=nadin_federation_provider($provider,$config);nadin_federation_dependencies($provider);require dirname(__DIR__).'/nadinmodule/'.$provider.'.inc.php';
            if($provider==='oidc'){$p=nadin_oidc_settings($p);if(in_array('--online',$argv,true))nadin_oidc_discover($p);}
            else { $settings=new OneLogin\Saml2\Settings(nadin_saml_settings($p));if($settings->validateMetadata($settings->getSPMetadata()))throw new RuntimeException('Invalid metadata.'); }
            echo strtoupper($provider).': configuration OK; '.count($p['identities'])." explicit identity mapping(s)\n";
        } catch(Throwable $e){$failed=true;fwrite(STDERR,strtoupper($provider).": configuration/dependency check failed. Check the required settings in docs/V15-federation.md.\n");}
    }
} catch(Throwable $e){$failed=true;fwrite(STDERR,"Federation configuration cannot be read.\n");}
exit($failed?1:0);

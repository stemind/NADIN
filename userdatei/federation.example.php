<?php
// Copy to federation.php. This file is evaluated only by NADIN, never served as data.
if (!defined('NADIN_FEDERATION_CONFIG')) { http_response_code(404); exit; }
return array(
    'base_url'=>'https://band.example/nadin', // No trailing slash. Exact public installation URL.
    'oidc'=>array(
        'enabled'=>false, 'label'=>'Organisation (OpenID Connect)',
        'issuer'=>'https://identity.example/realms/band',
        'client_id'=>'nadin', 'client_secret'=>getenv('NADIN_OIDC_SECRET') ?: '',
        'token_endpoint_auth_method'=>'client_secret_basic', // basic, client_secret_post or none
        'allowed_endpoint_origins'=>array(), // Only when discovery uses another trusted HTTPS origin.
        // 'pkce_s256_supported'=>true, // Only if the IdP omits this discovery field AND its S256 support is verified.
        'session_max_age'=>28800,
        'identities'=>array('immutable-subject-from-idp'=>'member@example.invalid'),
    ),
    'saml'=>array(
        'enabled'=>false, 'label'=>'Organisation (SAML)',
        'entity_id'=>'https://band.example/nadin/saml.php?metadata',
        'idp_entity_id'=>'https://identity.example/saml',
        'idp_sso_url'=>'https://identity.example/saml/sso',
        'idp_cert'=>'', // PEM certificate supplied by the trusted IdP administrator, NOT a fingerprint.
        'sp_cert'=>'', // Public PEM certificate; register it with the IdP.
        'sp_private_key'=>'', // Read PEM from outside the web root, e.g. file_get_contents('/etc/nadin/sp.key').
        'subject_attribute'=>'', // Empty = persistent NameID. Otherwise exact immutable subject attribute name.
        'session_max_age'=>28800,
        'identities'=>array('immutable-persistent-nameid'=>'member@example.invalid'),
    ),
);

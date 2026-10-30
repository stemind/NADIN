# Optional SAML and OpenID Connect in V16

## Keep local onboarding

With no `userdatei/federation.php`, NADIN behaves as before. No Composer installation is needed for local password login. Initial passwords, personal passwords and recovery remain available alongside organisation login. Do not change an existing installation's salt, user list, `auth-v11.php` or its lock marker during an upgrade.

NADIN is the authority for accounts, expiry and permissions. Federation verifies identity only. There is no automatic enrolment, email-based account linking, group-to-role mapping, or implicit administrator role. This keeps onboarding and rights administration in the existing compact editor.

## Install and configure

1. Back up the existing installation and data. Use a maintained PHP release; V16 was tested on PHP 8.3. Federation libraries require PHP 8.0 or later and OpenSSL, DOM and cURL. ZIP downloads require ZipArchive. Keep the existing deployment's required extensions too.
2. In a trusted build/deployment environment, run `composer install --no-dev --classmap-authoritative --no-interaction`. Deploy `vendor/` with the application; it is intentionally not committed. The lock file pins Firebase PHP-JWT 7.2.1, OneLogin PHP-SAML 4.3.2 and xmlseclibs 3.1.5. Run `composer audit --locked` when maintaining the installation.
3. Copy `userdatei/federation.example.php` to `userdatei/federation.php`, preserving its direct-access guard. The copy is ignored by Git. Protect it as installation configuration; keep private keys and secrets outside the web root or in the server environment.
4. Set `base_url` to the exact public HTTPS installation URL, including any subdirectory and without a trailing slash. Do not derive it from request headers. If TLS terminates at a proxy, configure the trusted web server to report HTTPS to PHP; NADIN does not trust arbitrary forwarded headers.
5. Configure the chosen provider, add explicit stable-subject → lowercase NADIN-email mappings, then set `enabled` to `true`. Keep the other provider disabled. The label is the button caption, e.g. “Organisation”.
6. Run `php nadinmodule/check-federation.php`. For an online OIDC discovery/TLS check, run `php nadinmodule/check-federation.php --online`. These commands never print keys, client secrets, assertions or mappings.
7. Test one ordinary member and one administrator, then test an unmapped and expired account, logout, local password fallback, and permission removal. Use the identity provider's test tenant first. Keep a working local administrator account.

On Apache, preserve the existing access-denial rules for `userdatei`, `library`, setlist directories and `abo` (including its ZIP cache). Other web servers need equivalent rules. Never expose the complete repository or data directories as static public files. Use HTTPS and accurate server clocks.

## OIDC registration

| Setting | Value |
| --- | --- |
| Flow | Authorization Code, query callback, PKCE S256 |
| Redirect URI | `https://your-host/your-nadin-path/oidc.php` |
| Scope | `openid` |
| ID-token signature | RS256, trusted RSA keys of at least 2048 bits |
| Subject mapping | Exact, immutable `sub` from the configured issuer |
| Client authentication | `client_secret_basic` by default; `client_secret_post` or `none` when explicitly configured |

The `issuer` must match discovery and the ID token exactly, including its path and trailing-slash semantics. Discovery is fetched at `issuer/.well-known/openid-configuration`. Discovery, token and JWKS requests verify TLS, do not follow redirects and have bounded time/response size. Endpoint origins must match the issuer's HTTPS origin, or an exact origin explicitly added to `allowed_endpoint_origins`.

Discovery must advertise code flow, RS256 and S256. If the provider omits `code_challenge_methods_supported`, and its administrator/documentation confirms S256 support, explicitly set `pkce_s256_supported` to `true`. This does **not** disable PKCE: every request still uses a fresh S256 challenge and verifier. An explicitly incompatible advertised method list is rejected.

State, nonce and verifier are random, server-side and tied to the browser. Pending requests expire after ten minutes and can be consumed once. The callback validates signature, issuer, audience/authorised party, time, nonce and subject. JWKS is fetched afresh for key rollover; token-supplied key URLs and symmetric/unsigned tokens are rejected. Access/refresh tokens are not retained and UserInfo is not used for account linking.

An optional `ca_bundle` path can supply an additional installation-specific trust bundle for a private CA. It never disables certificate or hostname verification. No extra origins or private CA are needed for a provider whose endpoints use its issuer origin and the system trust store.

## SAML registration

| Setting | Value |
| --- | --- |
| SP metadata | `https://your-host/your-nadin-path/saml.php?metadata` |
| ACS / reply URL | `https://your-host/your-nadin-path/saml.php` |
| Request binding | HTTP-Redirect, signed AuthnRequest |
| Response binding | HTTP-POST |
| SP entity ID | Exact configured `entity_id`; register this at the IdP |
| Identity | Persistent NameID, or one exact configured immutable subject attribute |
| Assertion | Signed with the explicitly trusted IdP certificate; SHA-256 or stronger supported RSA signature/digest |

Set the trusted `idp_entity_id`, `idp_sso_url` and full PEM `idp_cert`. Set a matching RSA `sp_private_key` and `sp_cert` (at least 2048 bits). Register the SP's public certificate with the IdP; the private key never appears in metadata. Rotate certificates deliberately by updating configuration and the IdP registration.

By default, request a persistent NameID and map it exactly. If the provider uses a dedicated immutable attribute instead, set `subject_attribute` to its **exact attribute name**, not its friendly name or a shortened URI. Exactly one value is required; the default persistent-NameID policy is then not requested. Email addresses and transient display names should not be used as stable identities. NADIN's supported subject identifiers are nonempty printable ASCII strings without spaces, up to 255 bytes.

Strict validation checks the trusted signature, issuer, audience, destination, conditions and request ID. In particular, `InResponseTo`, recipient and expiry are required **inside the signed assertion**, not only in the outer response. A request must have been started in the same browser; unsolicited IdP-initiated responses are rejected. Both the transaction and assertion ID are replay-protected. Clock tolerance is at most 60 seconds in NADIN's additional time checks.

SAML POST uses a dedicated Secure, HttpOnly, SameSite=None binding cookie. The regular NADIN session keeps SameSite=Lax. If the browser blocks the binding cookie, sign-in fails safely and local login remains available. This profile is SP-initiated sign-in; SAML Single Logout and OIDC back-channel logout are not implemented.

## Sessions and permissions

The browser receives an ordinary opaque NADIN session cookie. Provider tokens, assertions and attributes are not stored in browser cookies. Local rights and expiry are checked as before. Removing an account revokes its sessions. Changed permissions apply on the next request; stale sessions are revoked, except that an administrator saving their own account can retain the current session with the updated rights. Furthermore, disabling a provider, changing its configuration or removing a mapping revokes that provider's sessions on the next request. Existing local sessions remain independent of federation configuration.

The normal local idle timeout still applies. Federated sessions additionally expire after `session_max_age` (default eight hours, allowed one minute to 24 hours); an earlier SAML session expiry wins. OIDC assertion expiry is checked at authentication, while the NADIN session has its own bounded lifetime. Revocation at the IdP alone is not pushed into NADIN; use local disable/removal for immediate NADIN access removal.

“Logout” ends the NADIN session. The page explains that the organisation may retain its own IdP session. NADIN does not automatically redirect back to SSO. A subsequent deliberate organisation-login click may therefore reuse the IdP session.

## Failures and recovery

A failed, expired, mismatched or replayed login creates no NADIN session and shows a generic retry/local-login message. Starting federation requires POST and CSRF protection; starts are limited per browser and network. Provider dependency/configuration errors do not break local login. Run the configuration checker first, then inspect your trusted IdP administration logs; do not expose detailed assertion/token errors or enable production XML/token dumps.

A browser's pending sign-ins expire after ten minutes. Sign in again after changing mappings, provider settings or certificates. Back up the local authentication store along with the ordinary NADIN data; do not delete it to repair provider login.

## Verification

From the repository root, with the Composer dependencies installed:

```sh
php tests/auth.php
php tests/bruteforce.php
php tests/forscore.php
php tests/federation.php
php tests/zip.php
python3 tests/auth-http.py
python3 tests/admin-http.py
python3 tests/federation-http.py
python3 tests/v16-http.py
python3 tests/render-preview.py /tmp/nadin-preview
node tests/admin-dom.cjs /tmp/nadin-preview/views
node tests/login-dom.cjs
composer validate --no-check-publish
composer audit --locked
```

Python tests require `requests`, `beautifulsoup4`; federation HTTPS tests additionally require `cryptography`. DOM tests require `jsdom` (set `NODE_PATH` if installed outside the repository). Set `NADIN_PHP` if PHP has a nonstandard path. `tests/zip.php` runs concurrent-worker checks when `pcntl` is available. The TLS fixtures use a temporary trusted test CA and genuine signatures; verification is never disabled. Software DOM tests are not a substitute for real-browser or production-provider interoperability testing.

Design references: [OpenID Connect Core](https://openid.net/specs/openid-connect-core-1_0.html), [OAuth security BCP / RFC 9700](https://www.rfc-editor.org/rfc/rfc9700.html), [OneLogin PHP-SAML](https://github.com/SAML-Toolkits/php-saml), [Firebase PHP-JWT](https://github.com/firebase/php-jwt). u5CMS's separate SAML integration was an architectural reference; its older cookie/automatic-enrolment pattern was not copied.

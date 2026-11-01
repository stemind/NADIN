# NADIN branch and release map

The numbered branches describe successive functional stages. Common repairs have also been backported, so Git ancestry alone does not describe the current feature set. A development branch can contain newer work than a release tag carrying the same 7.7 family name.

| Line | GitHub branch | Functional starting point | Added or maintained capability |
| --- | --- | --- | --- |
| main | [`main`](https://github.com/stemind/NADIN/tree/main) | 7.6 | Maintained 7.x baseline |
| 7.7 | [`7.7-original-interface-forScore`](https://github.com/stemind/NADIN/tree/7.7-original-interface-forScore) | 7.6 | Original interface with forScore development |
| 7.7 classic release | [`7.7.2-css-gigrep-layout`](https://github.com/stemind/NADIN/tree/7.7.2-css-gigrep-layout) | 7.6 | Classic CSS GigRep layout and guided forScore export |
| V8 | [`V8-compact-interface`](https://github.com/stemind/NADIN/tree/V8-compact-interface) | 7.x baseline | Compact original interface |
| V9 | [`V9-forScore-export`](https://github.com/stemind/NADIN/tree/V9-forScore-export) | V8 | forScore export |
| V10 | [`V10-personal-passwords`](https://github.com/stemind/NADIN/tree/V10-personal-passwords) | V9 | Personal passwords and onboarding |
| V11 | [`V11-gig-user-management`](https://github.com/stemind/NADIN/tree/V11-gig-user-management) | V10 | Compact gig and user management |
| V12 | [`V12-login-protection`](https://github.com/stemind/NADIN/tree/V12-login-protection) | V11 | Progressive login protection |
| V13 | [`V13-zip-download-layout`](https://github.com/stemind/NADIN/tree/V13-zip-download-layout) | V12 | Readable ZIP downloads |
| V14 | [`V14-refined-interface`](https://github.com/stemind/NADIN/tree/V14-refined-interface) | V13 | Refined compact interface |
| V15 | [`V15-oidc-saml-reliable-zips`](https://github.com/stemind/NADIN/tree/V15-oidc-saml-reliable-zips) | V14 | Optional federation and reliable ZIPs |
| V16 | [`V16-php85-php9-preparation`](https://github.com/stemind/NADIN/tree/V16-php85-php9-preparation) | V15 | PHP 8.5 validation and PHP 9 preparation |
| V17 | [`V17-unified-interface-and-workflows`](https://github.com/stemind/NADIN/tree/V17-unified-interface-and-workflows) | V16 | Coherent compact task windows, explicit uploads, draft protection, mobile administration and visual/text setlists |

| V18 | [`V18-audio-upload-conversion`](https://github.com/stemind/NADIN/tree/V18-audio-upload-conversion) | V17 | Optional conversion of audio uploads to MP3, unchanged MP3 passthrough, atomic replacement and MP3-only fallback |

## Integration and release status

- `main` is the maintained legacy comparison baseline and has no forScore exporter. Later interface, account and federation stages are separate development lines.
- The consolidated classic release is [NADIN 7.7](7.7.md), sourced from `7.7.2-css-gigrep-layout` (historical branch name). It includes the earlier patch corrections and supplies one cumulative `NADIN-7.6-to-7.7.zip` update.
- `7.7-original-interface-forScore` retains its classic development layout. Both classic lines and V8–V18 share the guided forScore workflow and active-download validation, while each retains its visual stage.
- In every branch, only the literal string `rendergiglistontheleft='no'` puts current/archive GigRep navigation above the tunes. Missing, empty, `yes` and other values use the left vertical column. A positive pixel width remains configurable.
- Shared fixes preserve literal headings, GigRep/archive forScore access, section/Parts permissions, custom markers, source order, file identities and required directories.
- iTunes/RSS routes have been retired in V10 and later. Ordinary PDF/recording downloads, ZIPs and `abo/zip` gig attachments remain.
- Federation is optional in V15 and later. Install dependencies from `composer.lock` to use it; local login works without them.

Use one line's complete source files. Preserve installation configuration and salt, member/authentication files, music, Reps, attachments and hidden forScore identity metadata. Do not replace production configuration with the repository example.

## Historical numbering

The skipped V10 was closed by renumbering former V11–V16 to V10–V15. The current V16 is the later PHP-compatibility preparation step. Historical commits and some older note/test filenames retain their original numbers; they are not additional live branches. Internal authentication/cache namespaces are deliberately preserved compatibility identifiers.

## Verification

Current defaults, package and workflow verification are described in [the consolidated 7.7 guide](7.7.md). The [evolution audit](EVOLUTION-AUDIT.md) records earlier executed checks and their historical scope.

The media Library intentionally has no forScore link. Do not restore it as a missing feature; its Excel and Print exports remain available.

# NADIN branch and release map

The numbered branches describe successive functional stages. Common repairs have also been backported, so Git ancestry alone does not describe the current feature set. A development branch can contain newer work than a release tag carrying the same 7.7 family name.

| Line | GitHub branch | Functional starting point | Added or maintained capability |
| --- | --- | --- | --- |
| main | [`main`](https://github.com/stemind/NADIN/tree/main) | 7.6 | Maintained 7.x baseline |
| 7.7 | [`7.7-original-interface-forScore`](https://github.com/stemind/NADIN/tree/7.7-original-interface-forScore) | 7.6 | Original interface with forScore development |
| 7.7.2 | [`7.7.2-css-gigrep-layout`](https://github.com/stemind/NADIN/tree/7.7.2-css-gigrep-layout) | 7.7.1 | classic GigRep layout |
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

## Integration and release status

- `main` is the maintained legacy comparison baseline. The later interface, account and federation stages are not all merged into it.
- `v7.7.1` remains the previous immutable release snapshot. `v7.7.2` added the conservative CSS-layout maintenance. `v7.7.3` builds on that release with the guided forScore workflow, on `7.7.2-css-gigrep-layout`.
- `7.7-original-interface-forScore` retains its own classic layout. The remembered-section and guided forScore workflow are applied to both classic lines and V8–V17; each keeps its visual stage.
- V8–V16 retain their individual visual stages. Shared fixes preserve literal headings, Library forScore access, section/Parts permissions, custom markers, source order, file identities and required directories.
- Current/archive GigRep position is controlled by `rendergiglistontheleft`. Only explicit `yes` enables the classic left list; a missing classic flag retains the original top position. Compact branches retain left as their default when no flag is provided. A positive pixel width remains configurable.
- iTunes/RSS routes have been retired in the V10-and-later development lines. Ordinary PDF/recording downloads, ZIPs and `abo/zip` gig attachments remain. This historical feature decision is distinct from the 7.7.2 layout maintenance.
- Federation is optional in V15–V17. Install the versions in `composer.lock` with Composer to enable and test it; local login works without those optional dependencies.
- V17 builds on V16, including the compact ZIP row and current guided forScore export. Its [design rationale](V17-DESIGN.md) and [verification](V17-VERIFICATION.md) describe its distinct scope; it is not merged into the classic release lines.

Use one line's complete source files. Preserve installation configuration and salt, member/authentication files, music, Reps, attachments and hidden forScore identity metadata. Do not replace a production configuration file with the repository example.

## Historical numbering

The skipped V10 was closed by renumbering former V11–V16 to V10–V15. The current V16 is the later PHP-compatibility preparation step. Historical commits and some older note/test filenames retain their original numbers; they are not additional live branches. Internal authentication/cache namespaces are deliberately preserved compatibility identifiers.

## Verification

The final executed checks and concrete collateral corrections are recorded in [the evolution audit](EVOLUTION-AUDIT.md). The [7.7.2 release notes](7.7.2.md) describe its deliberately smaller source delta and upgrade packages.

The [7.7.3 release notes](7.7.3.md) describe the current forScore workflow release and update packages.

The current classic release is [7.7.4](7.7.4.md). Active download and required-field guidance are backported to all forScore lines, including V17.

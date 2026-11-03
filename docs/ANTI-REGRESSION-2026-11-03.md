# NADIN anti-regression review — 3 November 2026

**Final result: 347 suite executions / 32,371 numbered checks pass across all 14 maintained branches**, including 2,023 PHP syntax checks. The two classic installation paths additionally pass **20 suites / 3,064 checks**. Two real Chromium classic upload journeys add **16 checks**. No confirmed failure remains in this executed scope.

## What this additional review established

The exact 14 published branch heads and trees were fetched and compared with the preceding saved delivery. Fresh detached source checkouts repeated its complete 333-suite / 31,509-check matrix successfully. That repetition did not by itself cover every valid filename. New counterexamples reproduced another regression in **all fourteen** published stages.

`Session.(2026.11)_RCMD.mp3` and `Concert.(123.45)_RCMD.mp3` contain title text, rather than a duration suffix. The preceding extension of `striptime()` for three-digit minutes removed these interior fragments. The editor then failed to schedule duration measurement, XLS could report the title number as a time, and an explicit measurement could shorten the filename. The audio bytes were not changed.

The correction restricts the newly added long-duration recognition to the final annotation position, immediately before the final file extension or the established extension-free stem boundary. It preserves the original two-digit handling. **One runtime line changes per branch**, in `nadinmodule/functions.inc.php`; no configuration, account, data-format, layout or dependency migration is introduced.

The audio workflow suite now performs **84 checks per branch**, following actual generated forms/iframes, comparing durations with independent FFprobe, preserving exact bytes, keeping unknown XLS times blank and repeating measurements without filename drift. The new cases include dotted date/numeric title text and upper-case long-recording extensions.

## Challenge the correction itself

An initial MP3-only boundary was rejected **before publication**: an independently expanded V18 upload probe demonstrated that it would retain an obsolete long duration label on a WAV conversion. The final expression preserves suffix removal for source extensions too. The permanent V18 conversion suite tests both title preservation and removal of old long-duration suffixes through MP3 and every supported conversion extension. Its independent non-browser execution passes 85 checks; the full matrix also includes the existing 21 Chromium conversion checks.

The two real classic browser journeys now upload `Browser.(2026.11)_RCMD.mp3`, follow the actual nested iframe chain, observe the correctly annotated name and verify identical original bytes. Existing long recordings stay stable over multiple refresh intervals.

Fourteen additional independent cross-workflow HTTP suites contribute **490 checks**. They open notes and gig editors, create/review forScore identities where available, read current and archived views, request Print/XLS and download real section ZIPs. They compare protected source bytes, preserve stable forScore identities, confirm that unrelated exports do not invalidate pending editors and confirm that genuinely stale notes still receive a conflict after a real edit. Configuration, accounts, PDFs, recordings, current/archive setlists and the required directory marker remain byte-for-byte unchanged by these read/export operations.

## Entire final matrix

The complete final source matrix repeats login/expiry/permissions, onboarding/passwords, gig and member management, stale and no-op edits, shared data, tune notes, public/hidden media classification, navigation/layouts, ordered/repeated headings and tunes, Print/XLS/ZIP agreement, forScore selection/inventory/identities/download/return workflows, local signed OIDC/SAML and V18 conversion/protection/fallback journeys where the stage provides those features. Simultaneous retiming, bounded busy responses and killed-lock-holder recovery remain covered in every branch.

| Branch | Suite executions | Numbered checks | Result |
| --- | ---: | ---: | --- |
| `7.7-original-interface-forScore` | 13 | 1,888 | PASS |
| `7.7.2-css-gigrep-layout` | 13 | 2,531 | PASS |
| `V10-personal-passwords` | 18 | 1,922 | PASS |
| `V11-gig-user-management` | 27 | 2,478 | PASS |
| `V12-login-protection` | 29 | 2,556 | PASS |
| `V13-zip-download-layout` | 29 | 2,554 | PASS |
| `V14-refined-interface` | 29 | 2,555 | PASS |
| `V15-oidc-saml-reliable-zips` | 34 | 2,772 | PASS |
| `V16-php85-php9-preparation` | 36 | 2,839 | PASS |
| `V17-unified-interface-and-workflows` | 38 | 2,955 | PASS |
| `V18-audio-upload-conversion` | 39 | 3,062 | PASS |
| `V8-compact-interface` | 15 | 1,772 | PASS |
| `V9-forScore-export` | 15 | 1,772 | PASS |
| `main` | 12 | 715 | PASS |

Counts describe executed checks, including repeated executions; they are not counts of distinct requirements. The accompanying CSV/JSON retain the exact public inputs, final runtime/test fingerprints and every suite result. The preceding [reliability review](RELIABILITY-RECHECK-2026-11-03.md) remains the historical record of the preceding source snapshot.

## Classic release and installation

The classic release remains **7.7**. Its cumulative `NADIN-7.6-to-7.7.zip` is rebuilt from the corrected classic branch. The unchanged thirteen-file subset in [the installation guide](7.7.md) includes the corrected `functions.inc.php`. Both untouched `v7.6` and the original existing classic 7.7 input are exercised again; configuration/account bytes and a customised existing `infos.inc.php` calendar connection are preserved. No live production installation is deployed by publishing this release.

## Execution boundary

Native Linux PHP **8.4.27**, Chrome Headless Shell **156.0.8078.80**, FFmpeg/FFprobe **7.1.5**, Node **24.22.1** and existing optional locked federation packages. All data is synthetic and local. Native Windows, iPad/forScore import, Safari, production SMTP/IdP/hosting, unusual filesystems and native PHP 8.5/9 were not executed. The result applies to the documented workflows and environment.

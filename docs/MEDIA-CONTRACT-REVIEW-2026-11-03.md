# NADIN independent media contract review — 3 November 2026

**375 suite executions / 34,047 numbered checks pass across all 14 maintained branches**, including 2,023 PHP syntax checks. Both classic installation paths additionally pass **22 suites / 3,174 checks**; two real classic Chromium journeys add **32 checks**; an independent V18 conversion probe adds **85 checks**. No confirmed failure remains within this executed scope.

## Why another method was necessary

The preceding passing matrix had not exercised several ordinary destructive file operations. This review first froze all 14 actual public branch heads and wrote behavioural contracts derived from original Main's editors, before constructing new experiments. Only reproduced data loss or violated existing behaviour justified a runtime change. The original `v7.6` source was tested separately for attribution. Repeating the earlier passing suites came after these new experiments.

Five additional fault families were reproduced. All five already occur in original Main; they are **pre-existing faults**, rather than regressions caused by the preceding one-line duration-title correction. The latest published V17/V18 implementations already pass the new cases and receive **no runtime changes** in this review.

| Reproduced fault | Affected maintained stages | Correction |
| --- | --- | --- |
| Audio rename checks the raw name but writes a different, duration-stripped name; `Keep(01.23).mp3` can replace `Keep.mp3` | Main, both 7.7 lines, V8–V14; V15/V16 already check the final name | Compute the final name once and check that exact destination, including links |
| Two deletions with the same basename in the same second replace a previous recovery copy; attachment deletions are affected too | Main, both 7.7 lines, V8–V16 | Keep the established recovery location and timestamp/user/name information, adding a collision-checked random component |
| Rapid audio/PDF replacement overwrites an existing `zzDEL!` previous version | Main, both 7.7 lines, V8–V16 | Unique retained versions and staging paths; check backup/commit results and preserve the previous current file on failure |
| Distinct PDF inputs normalise to the same destination within one batch | Main, both 7.7 lines, V8–V16 | Reject the duplicate destination and incomplete inputs before storing the first item |
| A failed move into `filesdeleted` is followed by unconditional source deletion | Main, both 7.7 lines, V8–V10; V11–V16 had already removed that unlink | Remove the unconditional deletion and report a failed recovery move while preserving the source |

## Size and boundaries of the repair

Nine runtime files change in each of the twelve affected stages: the existing functions include and eight audio/PDF/attachment controllers. The classic interface, existing extension rules, permissions and request transports remain. Authentication and CSRF guards in the later stages remain in force. No conversion, federation, account, configuration or layout migration is introduced. Legacy attachment replacement retains its existing direct-replacement behaviour; this review does not add an unrelated version-history feature to it.

Classic file controllers now coordinate their short mutations with the duration renamer through the **existing required directory marker**, opened read-only with a bounded 100 ms acquisition attempt. There is no new lock file or writable lock directory. Valid operations recover after contention; marker bytes, modification time and read-only permissions are checked. V17/V18 retain their existing common write architecture, including V18's deliberate release of the write lock during FFmpeg conversion.

The full before/after byte comparisons distinguish sources, current files and retained versions. They include six same-name audio/PDF/attachment deletions, five rapid audio/PDF replacements, simultaneous requests through independent PHP workers, legitimate renames, cancellation, rejected types, duplicate batches and unavailable recovery storage. A preliminary V11–V16 token-extraction mistake was corrected by reading the token from the actual child form; those denied test requests are harness diagnostics and are not counted as product findings.

## Challenge the corrections themselves

After the runtime candidates were fixed, a fresh challenge used filesystem filename limits to make version retention fail, incomplete multi-file input, a symbolic-link destination, a read-only coordination marker and held/released locks. All final checks pass: the old current file remains when its backup cannot be created, staging files are removed, a normal distinct PDF batch still succeeds, and a refused batch leaves the prior file intact. This additional search found **no further confirmed defect** in its executed cases.

The complete earlier evolution matrix is repeated on these repaired sources: login/expiry/permissions, account and GigRep edits/conflicts, notes and metadata, configuration, archives, catalogue, layouts, screen/Print/XLS/ZIP agreement, forScore, optional local federation and audio conversion. Separate cross-workflow probes verify that read/export operations preserve pending editors and source bytes. Real classic Chromium tests use the actual chooser, nested frames, rename prompts and delete confirmation/cancellation; the dated-title and long-recording journeys continue to pass.

## Executed matrix

| Branch | Suites | Checks | Result |
| --- | ---: | ---: | --- |
| `7.7-original-interface-forScore` | 15 | 2,008 | PASS |
| `7.7.2-css-gigrep-layout` | 15 | 2,651 | PASS |
| `V10-personal-passwords` | 20 | 2,042 | PASS |
| `V11-gig-user-management` | 29 | 2,598 | PASS |
| `V12-login-protection` | 31 | 2,676 | PASS |
| `V13-zip-download-layout` | 31 | 2,674 | PASS |
| `V14-refined-interface` | 31 | 2,675 | PASS |
| `V15-oidc-saml-reliable-zips` | 36 | 2,892 | PASS |
| `V16-php85-php9-preparation` | 38 | 2,959 | PASS |
| `V17-unified-interface-and-workflows` | 40 | 3,073 | PASS |
| `V18-audio-upload-conversion` | 41 | 3,180 | PASS |
| `V8-compact-interface` | 17 | 1,892 | PASS |
| `V9-forScore-export` | 17 | 1,892 | PASS |
| `main` | 14 | 835 | PASS |

Full results, fixed input commit IDs, per-suite output and source fingerprints are in `media-contract-review-20261103.json`; the CSV provides a compact matrix. The original Main attribution uses 15 suites / 645 checks. It is expected to fail and is not mixed into the passing final count. The corrected primary matrix, cross-workflow probes and final challenge total 375 suites / 34,047 checks; repeated exploratory runs are also excluded from this headline.

## Classic 7.7 installation

The current cumulative **NADIN-7.6-to-7.7.zip** contains the corrected controllers as well as the earlier interface, forScore and duration repairs. An existing consolidated classic 7.7 installation must now use the **21-file subset listed in `docs/7.7.md`**, together, from the same package. The preceding thirteen-file subset alone is insufficient for these controller corrections. A customised `nadinmodule/infos.inc.php` calendar connection, configuration/salt, accounts, music, GigReps, generated files and hidden forScore metadata remain outside that subset.

The installation tests start from untouched v7.6 and the earlier consolidated classic v7.7 source. They compare all cumulative runtime files and preserve exact installation-owned bytes, including a synthetic customised calendar include. The displayed classic release remains **7.7**. The preceding tag source and canonical assets are retained under `before-contract-review-20261103` names when the release is refreshed.

## Practical limits

Executed locally on native Linux PHP 8.4.27, Chromium 156.0.8078.80 and FFmpeg/FFprobe 7.1.5, using disposable synthetic installations and the existing optional federation dependencies. Native Windows, iPad/forScore import, Safari, production hosting/SMTP/IdP, unusual filesystems and native PHP 8.5/9 have not been executed. No production installation was changed. Passing these tests establishes the stated coverage; it does not prove the absence of every possible defect.

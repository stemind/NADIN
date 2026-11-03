> **Later anti-regression review:** [The additional complete review](ANTI-REGRESSION-2026-11-03.md) supersedes this source snapshot: 347 suites / 32,371 checks and both classic installation paths pass. It corrects an additional filename-boundary regression. The earlier findings and counts below remain historical evidence.

# NADIN reliability follow-up — 3 November 2026

**Final result:** **333 suite executions / 31,509 numbered checks across all 14 maintained branches pass**, including **2,023 PHP syntax checks**. Both classic installation paths additionally pass **20 suite executions / 3,014 checks**. Two native Chromium upload journeys add **16 checks**. No confirmed failure remains in this executed matrix.

The extra review was necessary: a real classic upload connection had been missed, and a sequential collision test did not establish safety under simultaneous requests. The preceding [repair countercheck](REPAIR-COUNTERCHECK-2026-11-03.md) records its earlier snapshot; its 305-suite result is not a claim about this later scope.

## Confirmed failure families and origin

| Finding | Origin and affected stages | Reproduction and correction |
| --- | --- | --- |
| The classic audio list still passed `../library/Tune` into the duration endpoint. The new strict validation expected just `Tune`. | **Regression from the earlier duration repair**, in the two classic branches. Later lines already used the basename. | Follow the actual iframe emitted by `AUDfiles.php`, rather than constructing a valid request in the test. The old request returns 400. The caller now sends the encoded basename and filename; the validation remains strict. Native Chromium selects a real MP3, uploads it and follows the nested iframe chain to the correctly annotated file, with identical bytes. |
| The collision guard checked for a destination and then renamed. Two separate source files could both pass that check and write the same timed name. | **Incomplete earlier collision repair** before V15. V15–V18 already serialised these requests through their existing media-write lock. | Two independent PHP HTTP workers and sessions perform 200 simultaneous retiming pairs against the published Main snapshot: nine pairs report two successes and lose one recording. The final check-and-rename phase is now coordinated in every line, after audio parsing, using the existing required `abo/zip/donotdeletethisfolder.txt` marker. Open/lock retries are bounded to a short interval; busy requests return 503 unchanged. No additional metadata or lock file is created. The final suites repeat parallel collisions in every branch, allow unrelated names to complete and kill an independent lock holder before retrying successfully. |
| A valid recommended MP3 without a duration annotation could break the GigRep view under PHP 8; XLS could display filename text as a time. | **Pre-existing Main behaviour**, reproduced on original tag `v7.6`. The screen arithmetic remained before V15; the export issue persisted in later stages too. | View a freshly stored recording before annotation as both an editor and a musician. Unknown times are now kept empty and never used as nonnumeric operands. Actual current-view and XLS responses are checked before and after annotation. Headings, numbers, permissions and known time values retain their existing behaviour. Legacy background URLs now encode their path parameters. |
| Three-digit minute counts were not recognised as annotations. Retiming an already annotated file could add another separator; long recordings could repeatedly schedule duration work. | **Pre-existing filename handling**, present throughout the maintained evolution. | A real MP3 independently measured by FFprobe at 6,366.024 seconds exercises the 100-minute boundary. Long dotted annotations are recognised, and a repeated duration request keeps the same filename and exact bytes. A parenthesised year/month inside a title remains intact. The native classic browser observes multiple refresh intervals with no repeated duration request or reload loop. |

The implementation changes are confined to `functions.inc.php`, `settime.php`, `table.inc.inc.php` and `table.inc.xls.php`; the two classic branches also correct their `AUDfiles.php` caller. V16–V18's existing runtime runners include both new suites. Configuration, accounts and music data formats are unchanged.

## Challenge the new corrections

The two new portable suites are `tests/evolution-audio-workflow-http.py` (**59 checks per branch**) and `tests/evolution-duration-concurrency-http.py` (**75 per branch**). These **1,876 additional numbered checks** cover actual generated forms/iframes, independent file bytes, VBR, upper-case MP3 extensions, spaces/ampersands/plus signs, ID3 artwork/text, legal APEv2 binary metadata, year/month text in a filename, a long recording, repeated annotation, musician access and real XLS output.

The concurrency fixture uses separate PHP processes sharing the same disposable installation. It checks both recordings after every competing pair, successful independent destinations, a bounded busy response, successful retry after killing the lock holder, unchanged configuration bytes, an unchanged read-only marker and absence of newly created lock files.

Coordination deliberately uses the existing non-PHP marker rather than a configuration/source file. The [PHP manual](https://www.php.net/manual/en/function.flock.php) documents mandatory Windows locking and second-handle restrictions. The reader now consumes its already locked MP3 stream instead of reopening the file; callers that supply only a filename retain the existing class API. This addresses the documented handle interaction; **native Windows execution is not claimed**.

The original Main and prior published snapshots distinguish a regression from older defects. The evidence retains the failing expectations and the actual overwrite experiment. Preliminary test results are not counted as final passes. One early diagnostic probe was corrected to isolate each recording and to compare tagged CBR audio with the untagged audio's independent duration: adding metadata must not change the duration oracle by inflating a filesize-based estimate.

## Complete final matrix

Runtime: **PHP 8.4.27**, Chrome Headless Shell **156.0.8078.80**, FFmpeg/FFprobe **7.1.5**, Node **24.22.1**, Playwright, jsdom and axe-core. Existing locked optional federation packages are unchanged. All fixtures are synthetic, local and disposable; no production music, accounts or outgoing email are used.

| Branch | Suite executions | Numbered checks | Result |
| --- | ---: | ---: | --- |
| `main` | 11 | 661 | PASS |
| `7.7-original-interface-forScore` | 12 | 1,830 | PASS |
| `7.7.2-css-gigrep-layout` | 12 | 2,473 | PASS |
| `V8-compact-interface` | 14 | 1,714 | PASS |
| `V9-forScore-export` | 14 | 1,714 | PASS |
| `V10-personal-passwords` | 17 | 1,864 | PASS |
| `V11-gig-user-management` | 26 | 2,416 | PASS |
| `V12-login-protection` | 28 | 2,494 | PASS |
| `V13-zip-download-layout` | 28 | 2,492 | PASS |
| `V14-refined-interface` | 28 | 2,493 | PASS |
| `V15-oidc-saml-reliable-zips` | 33 | 2,710 | PASS |
| `V16-php85-php9-preparation` | 35 | 2,777 | PASS |
| `V17-unified-interface-and-workflows` | 37 | 2,893 | PASS |
| `V18-audio-upload-conversion` | 38 | 2,978 | PASS |

The matrix repeats the preceding login, permissions, independent editing, gig/user management, archives, configurable layouts, screen/Print/XLS/ZIP agreement, forScore identities/inventory/downloads/return tabs, local signed OIDC/SAML and V18 conversion journeys where those stages exist. The [CSV](reliability-recheck-20261103.csv) and [JSON](reliability-recheck-20261103.json) record the exact published inputs, final tested source fingerprints, every suite output and before/after evidence. Counts include repeated executions; they are not counts of distinct requirements.

## Classic update and preservation

The cumulative `NADIN-7.6-to-7.7.zip` is rebuilt from `7.7.2-css-gigrep-layout`. It is checked on untouched `v7.6` and on the original existing classic 7.7 input `3c77aa2c868304582575d6b1c02ca2f7503c6725`. The documented **thirteen-file subset** now also includes `nadinmodule/functions.inc.php`, `nadinmodule/AUDfiles.php` and the required `abo/zip/donotdeletethisfolder.txt` marker. Configuration and account bytes remain identical, and the existing-7.7 fixture retains a custom `infos.inc.php` calendar connection byte-for-byte. Both paths pass all 20 installation suites.

See [the installation guide](7.7.md). Preserve configuration/salt, accounts/authentication files, music, GigReps, attachments, hidden forScore identities and normal data-directory permissions. The classic release remains **7.7**. Later stages retain their own feature scope and designs.

## Evidence boundary

These executions establish the listed contracts on the tested Linux fixtures. Native Windows, iPad/forScore import, Safari, production SMTP/IdP/hosting and unusual filesystems were not executed. PHP 8.5/9 native execution is not claimed. Publishing the repaired source does not deploy it to a production installation. This is a more complete check of the identified workflows, not a universal absence-of-bugs guarantee.

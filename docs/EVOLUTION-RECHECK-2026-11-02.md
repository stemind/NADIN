# NADIN follow-up evolution check — 2 November 2026

**Result:** all **291 final suite executions pass** across **14 maintained branches**, Main through V18. The final matrix reports **29,353 numbered checks**, including **2,023 PHP syntax checks**. Of these, **1,040 checks** exercise the two new independent state/audio suites. No confirmed failure remains in this executed matrix.

This is a new check of the files actually published on GitHub. Earlier audit reports describe their own snapshots. At the start, nine branch trees matched the previously tested source; Main, V8, V9, V16 and V17 still lacked part of that publication. The remaining repairs/tests/documentation were reconciled against the actual input heads before this final run. V16 already had its runtime repair but lacked the corresponding complete tests/guidance.

## Additional confirmed defects and repairs

| Finding | Affected branches | Result |
| --- | --- | --- |
| A gig form used only content/attachment hashes as its revision. After another editor renamed the gig and recreated its old name with identical bytes, the stale form could save, archive, trash, copy, rename or attach a PDF to the replacement gig. | V11–V18 | The revision also identifies the actual file device/inode. All six stale operations return 409 and preserve all gig/attachment filenames and bytes. Fresh forms and consecutive saves still work. Two independent authenticated sessions exercise these cases. |
| The gig catalogue could advertise MIDI, wrong-type audio, empty, recovery or hidden files as available section notes/recordings. Older catalogue scanners had not received all the public file-role rules. | V11–V18; more false badges before V17 | Badges require a readable, nonempty, ordinary PDF or recommended MP3 with the configured marker. Hidden working files remain in authorised media tools. Tests cover prefix markers, uppercase extensions, both catalogue roles, empty files, recovery files and child symlinks. |
| Older automatic duration annotation overwrote a same-named recovery copy when different tunes already had a conflicting timed MP3 name. A second collision could remove the only retained bytes of the first recording. Later collision guards emitted a success HTTP status because output had already started. | All lines; backup overwrite before V15 | A collision reports 409 before output and leaves both source recordings at their original paths. Source locking uses the recording itself and adds no writable metadata-directory requirement to old installations. The source inode is rechecked before rename. |
| Invalid/reserved MP3 frame headers could raise a PHP fatal error in older lines or rename unreadable audio to a zero-duration filename in later lines. | All lines | Invalid/unreadable duration reports 422 without changing the file. Valid subsecond audio still receives its established rounded `00.00` annotation; the native upload journey caught and helped correct this boundary in the initial guard. Reserved fields and short ID3 headers are handled; file handles are closed. Existing GET transport remains in the older interface; the later POST/CSRF transport remains intact. Older duration routes also require the existing audio-edit permission. |
| MPEG-2/2.5 Layer III frame sizes used the MPEG-1 coefficient, producing wrong times or no useful duration at lower sample rates. | All lines | The frame coefficient is selected for the actual MPEG sample-rate family. Real MP3 files at 48,000, 44,100, 32,000, 24,000, 22,050, 16,000, 12,000, 11,025 and 8,000 Hz are compared with independent FFprobe durations; the exact MP3 bytes are preserved. |

The new failures were reproduced before their corresponding repairs. Selected before-repair expectation failures and every final suite output are retained in [the evidence JSON](evolution-recheck-20261102.json). The rate experiment was performed after the separate collision/header repair and before the frame-coefficient repair; its byte-preservation assertion was tightened to include the unchanged source path when no rename occurred.

## Review perspectives and preserved concepts

1. **Published evolution and original contracts.** Current GitHub trees were compared with the last tested trees and with Main behaviour. All missing common repairs were completed where applicable: hidden/typed media, canonical/legacy tune information, trumpet-only numbering, archive/cache separation and notes/conversion conflict checks. Installation-owned configuration and data formats were preserved. Deliberate feature stages remain those in the [branch map](BRANCHES.md).
2. **State changes and independent editors.** Real authenticated requests cover complete gig lifecycles, permissions, notes/media edits, replacement gigs, stale forms, fresh reopening, attachments, backup/recovery and repeated saves. The catalogue is checked as a musician-facing availability statement, independently of its implementation.
3. **Independent output and audio evidence.** Existing screen/Print/XLS/forScore/ZIP byte/order tests were retained. Real audio files add an external duration oracle across nine sample rates, same-name collisions across tunes and malformed-header handling. Main and every later line run their matching native-browser/layout/forScore journeys.

The matrix covers login/onboarding/personal passwords, expired and revoked permissions, current/archive lists, Library and recordings, headings/repeated tunes, Print/XLS, section/Parts/hidden-file rights, media upload/rename/delete, gig/user management, ZIP bytes/concurrency, forScore identities/revisions/inventory/return tabs, optional local OIDC/SAML providers and V18 audio conversion as present in each branch. Classic and later designs retain their own layouts. The Library's deliberately removed forScore link stays absent. Installation-specific calendar changes are retained by the documented classic update subset.

## Final executed matrix

Runtime: PHP 8.3.6, Chrome for Testing Headless Shell 156.0.8078.80, FFmpeg/FFprobe 6.1.1, Node Playwright, jsdom and axe-core. The Latin-1 server-default cases are included. Optional dependencies come from the existing locked vendor snapshots. Fixtures use synthetic members/music and disposable local installations; no production installation or outgoing mail is used.

The preliminary runner's browser launch failed on executable permissions. The final matrix below was rerun from the completed corrected sources with a working native browser. No failed preliminary execution is counted as a final pass. Assertion counts include repeated executions; they are not counts of distinct requirements.

| Branch | Suite executions | Numbered checks | Result |
| --- | ---: | ---: | --- |
| `main` | 8 | 511 | PASS |
| `7.7-original-interface-forScore` | 9 | 1,680 | PASS |
| `7.7.2-css-gigrep-layout` | 9 | 2,323 | PASS |
| `V8-compact-interface` | 11 | 1,564 | PASS |
| `V9-forScore-export` | 11 | 1,564 | PASS |
| `V10-personal-passwords` | 14 | 1,714 | PASS |
| `V11-gig-user-management` | 23 | 2,259 | PASS |
| `V12-login-protection` | 25 | 2,337 | PASS |
| `V13-zip-download-layout` | 25 | 2,335 | PASS |
| `V14-refined-interface` | 25 | 2,336 | PASS |
| `V15-oidc-saml-reliable-zips` | 30 | 2,553 | PASS |
| `V16-php85-php9-preparation` | 32 | 2,620 | PASS |
| `V17-unified-interface-and-workflows` | 34 | 2,736 | PASS |
| `V18-audio-upload-conversion` | 35 | 2,821 | PASS |

The [CSV matrix](evolution-recheck-20261102.csv) and [evidence JSON](evolution-recheck-20261102.json) identify every input commit, suite result and tested source fingerprint. Fingerprints cover application and fixture files, excluding Git metadata, vendor, README and docs. Documentation is written afterwards without changing those tested runtime files.

## Applying the repairs and remaining boundaries

Use the maintained branch matching the installation. The additional common runtime change is `nadinmodule/settime.php`; V11–V18 additionally update `nadinmodule/admin.inc.php`. The previously incomplete branches also receive the common repair paths documented by the earlier audit. No new data-format migration is introduced. Preserve configuration/salt, accounts/authentication data, music, GigReps, attachments and hidden forScore identities.

The cumulative classic `NADIN-7.6-to-7.7.zip` is rebuilt from this corrected classic source and checked separately on unchanged 7.6 and existing 7.7 fixtures. Existing classic users can apply the ten-file subset in [the 7.7 guide](7.7.md), retaining their customised `infos.inc.php`.

These results establish the enumerated contracts on the tested fixtures. Native iPad/forScore import, Safari, production SMTP/IdP/hosting, maximum production data volumes and native PHP 8.5/9 execution were not part of this run. Production deployment remains separate from publishing the corrected source.

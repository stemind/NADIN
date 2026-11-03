> Historical snapshot: the [later reliability follow-up](RELIABILITY-RECHECK-2026-11-03.md) supersedes the final-state assessment below with the expanded 333-suite review.

# NADIN repair countercheck — 3 November 2026

**Result:** the additional review found three reproducible side effects in the proposed repairs. They are corrected with small changes. The final run passes **305 suite executions / 29,633 numbered checks across all 14 maintained branches**, including **2,023 PHP syntax checks**. The two classic installation paths separately pass **16 suite executions / 2,746 checks**. No confirmed failure remains in this executed matrix.

## Method: challenge the repairs themselves

The original Main workflows and installation-owned settings were used as contracts. Each proposed repair was examined as a chain: reproducible defect, smallest necessary change, legitimate cases the change might affect, independent before/after evidence, and a full functional comparison. A passing security or error-path check alone was insufficient: valid editing, metadata, media access, custom configuration and update paths also had to continue working. This supplements the [2 November follow-up](EVOLUTION-RECHECK-2026-11-02.md); that report retains its historical results.

## Confirmed side effects and reductions

| Repair challenged | Reproduced unwanted effect | Minimal correction | Legitimate behaviour and protection checked |
| --- | --- | --- | --- |
| File-identity revisions for gig forms, V11–V18 | Saving identical gig text rewrote the file, changed its inode and created an unnecessary recovery copy. A second genuinely current editor then received a conflict. | After the existing revision check and inside the existing write lock, an exact byte-identical save returns the current gig without rewriting it. | Same revision, inode and bytes; no extra recovery copy; independent second editor can save. Real content changes and recreated same-name gigs still reject stale operations with 409, including save/archive/trash/copy/rename/attachment. |
| Strict invalid-frame handling in MP3 duration annotation, all branches | A valid 3.2-second MP3 with a legal binary APEv2 metadata field was rejected when the tag bytes resembled a reserved frame. The earlier reader retained the measured audio duration. | Preserve the duration already measured from valid audio before encountering the invalid trailing candidate. A file with no measured audio still fails safely. | FFmpeg creates real audio, Mutagen adds an independent APEv2 tag, and FFprobe verifies MP3 format/duration. Annotation succeeds and preserves every source byte. Invalid/reserved headers still return 422 unchanged; subsecond audio and nine sample-rate families retain their expected filenames. |
| Added source-file lock for duration annotation, all branches | A competing lock could leave a PHP worker waiting indefinitely and allow a delayed rename after the requesting client had given up. | Acquire the same source-file lock nonblockingly; return the existing retryable 503 response when busy. No extra lock file or writable directory is introduced. | Independent OS-level lock, immediate response, unchanged busy source, successful retry after release and no persistent lock. Collision protection still retains both recordings. |

The pre-repair V18 experiment produced nine failed expectations from these three causes; it is retained in [the evidence JSON](evolution-countercheck-20261103.json). These are not nine separate defects. The implementation changes are restricted to `nadinmodule/settime.php` in all lines and a two-line no-op guard in `nadinmodule/admin.inc.php` in V11–V18, plus tests and documentation. No data migration, new application dependency or additional persistent metadata is required.

## Other legitimate cases challenged

An intentionally empty canonical `infos.txt` must not revive obsolete fallback notes. Authorised administrators must retain hidden working-file downloads, ordinary members must be denied the file bytes, and an explicitly empty hidden marker must retain its established no-hiding meaning. All these cases pass in every branch.

The preliminary new permission assertion assumed status 403 everywhere. Older branches legitimately deny with an HTML response and status 200. The assertion was corrected to verify that the PDF bytes and PDF content type are withheld, and all fourteen new countercheck suites were repeated. No authentication controller was changed to satisfy the test. All existing suites had already passed on the same application files.

The complete rerun retains the original contract suites for login and onboarding, permissions, current/archive GigReps, configurable left/top navigation, headings/order/repetitions, Library and recordings, Print/XLS, attachments, notes/media editing, recovery, independent editors, catalogue availability, ZIP contents/cache/concurrency, forScore identities/inventory/downloads/return tabs and no-JavaScript fallbacks. Optional signed local OIDC/SAML provider journeys and V18 audio conversion are exercised where those stages exist. Their feature stages and interface designs remain separate.

## Executed matrix

Runtime: **PHP 8.4.27**, Chrome Headless Shell **156.0.8078.80**, FFmpeg/FFprobe **7.1.5**, Node **24.22.1**, Playwright, jsdom and axe-core. Optional federation packages use the repository's existing locked versions. Python Mutagen is a test-only metadata fixture dependency. Latin-1 server-default cases are retained. Tests use synthetic local installations and send no mail.

| Branch | Suite executions | Numbered checks | Result |
| --- | ---: | ---: | --- |
| `main` | 9 | 527 | PASS |
| `7.7-original-interface-forScore` | 10 | 1,696 | PASS |
| `7.7.2-css-gigrep-layout` | 10 | 2,339 | PASS |
| `V8-compact-interface` | 12 | 1,580 | PASS |
| `V9-forScore-export` | 12 | 1,580 | PASS |
| `V10-personal-passwords` | 15 | 1,730 | PASS |
| `V11-gig-user-management` | 24 | 2,282 | PASS |
| `V12-login-protection` | 26 | 2,360 | PASS |
| `V13-zip-download-layout` | 26 | 2,358 | PASS |
| `V14-refined-interface` | 26 | 2,359 | PASS |
| `V15-oidc-saml-reliable-zips` | 31 | 2,576 | PASS |
| `V16-php85-php9-preparation` | 33 | 2,643 | PASS |
| `V17-unified-interface-and-workflows` | 35 | 2,759 | PASS |
| `V18-audio-upload-conversion` | 36 | 2,844 | PASS |

Counts include repeated executions and are not counts of distinct requirements. The [CSV](evolution-countercheck-20261103.csv) and [JSON](evolution-countercheck-20261103.json) retain every result, initial source fingerprints, final application/test fingerprints, exact input/review commits, the preliminary assertion correction and package evidence. Application files were checked unchanged across the complete run and final assertion rerun. The new countercheck file is also included in V16–V18's existing runtime runner.

## Classic 7.7 update and recovery

The cumulative `NADIN-7.6-to-7.7.zip` is rebuilt from the corrected `7.7.2-css-gigrep-layout` source. It is applied to untouched tag `v7.6`; its application payload matches the tested classic source. The documented ten-file subset is separately applied to the existing classic 7.7 input at `3c77aa2c868304582575d6b1c02ca2f7503c6725`. Both paths pass the installation matrix. Configuration and member data remain byte-identical. The latter fixture also retains an installation-specific `nadinmodule/infos.inc.php` calendar connection byte-for-byte.

Use [the 7.7 installation guide](7.7.md) and verify the current ZIP against its accompanying `SHA256SUMS.txt`. Preserve configuration/salt, accounts/authentication files, music, GigReps, attachments and hidden forScore identities. Later development branches use their own complete source. The displayed classic version remains 7.7.

## Evidence boundary

The checks support the enumerated contracts on these fixtures. They do not prove universal absence of collateral effects. Native iPad/forScore import, Safari, production SMTP/IdP/hosting, unusual storage filesystems and maximum production data volumes were not exercised. PHP 8.5/9 native execution is not claimed. Publishing these sources does not deploy them to a production installation.

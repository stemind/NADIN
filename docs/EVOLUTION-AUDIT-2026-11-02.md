# NADIN evolution audit — 2 November 2026

**Result:** all **269 final suite executions** pass across **14 maintained branches**, from Main through V18. They report **28,313 numbered checks**, including **2,023 PHP syntax checks**. The initial audit and three additional investigation rounds found and corrected inherited file-classification, numbering and cache defects, plus concurrent-edit defects. Outdated test assumptions were corrected too. No further failures remained in the executed contract matrix.

The reference is the current maintained Main, not an assumption that Git ancestry alone proves preservation. Source/configuration comparisons were combined with authenticated requests, file-byte comparisons, unit tests, software DOM checks and real browser journeys. Earlier reports remain historical records of their own source snapshots.

## Confirmed defects

| Finding | Affected lines | Correction and evidence |
| --- | --- | --- |
| Section matching accepted non-PDF working files; `RCMD` matching accepted non-MP3 files; markers at the beginning of filenames were missed. Additional text files could replace the canonical tune information. | All 14 | Match non-empty section markers anywhere in PDF filenames, and recommended markers only in MP3 filenames. Prefer `infos.txt`, retain the historical text-metadata fallback when it is absent, and keep other text files downloadable. Private/recovery files and child symlinks are excluded. The independent file-role suite passes **41 checks per branch**. |
| A hidden `HDDN` file also containing a section marker could displace the ordinary score link according to directory iteration order. Hidden source files could also enter the legacy section ZIP source lists. This contradicted the configuration's rule that hidden working files appear under “more” for `musers`. | Main, both classic lines, V8–V18 | Classify hidden files before section/recommended-audio matching in the screen, Print and XLS scanners. Authorised administrators retain them under “more”; ordinary section links remain intact, other members receive no hidden bytes, and new legacy ZIP source lists exclude them. The strengthened Main HTTP contract passes **47 checks per branch**, including explicit hidden-link classification and direct-download permissions. It no longer depends on a favourable directory order. |
| A stale notes editor could write into a renamed-and-recreated tune directory when its initial notes bytes were identical. A content hash alone did not identify the intended tune. | V11–V18 | Bind the opaque notes revision to the directory device/inode and content hash. Both HTML forms and subsequent-save responses use that revision. Independent editor sessions verify stale saves, recreated destinations, unchanged renamed originals, legitimate reopening and repeated saves. **130 additional checks** pass across the eight lines. |
| V18 released the global media lock during conversion but checked only that a destination directory still existed afterwards. A slower conversion could overwrite a newer recording, or write into an unrelated directory recreated under the original tune name. | V18 | Capture the destination directory's device/inode and the exact previous recording path/hash before releasing the lock. Recheck after reacquiring it and return HTTP 409 on a change, before staging or replacing anything. Both races were reproduced on the old source: it returned HTTP 200, overwrote the newer bytes and accepted the recreated directory. The expanded audio HTTP suite passes **63 checks**, including preservation of newer bytes, the replacement directory, the renamed original directory and temporary-file cleanup. |
| The counter referenced the nonexistent `$trumpetpdf` variable instead of `$trumpetspdf`. A tune with trumpet notes alone was skipped and following numbers shifted. | Main, both classic lines, V8–V14 | Correct the reference in screen, Print/XLS and their related duration indicators. The cross-output suite tests every section separately, literal headings and repeats. V15 and later already used the correct variable. |
| Viewing an archived gig unconditionally deleted caches with the same basename, including those belonging to a still-current gig. | Main, both classic lines, V8–V10 | Preserve current caches whenever the matching current gig exists; retain the old cleanup for genuinely archived-only gigs. Actual ZIP downloads are verified after viewing both sources. |
| The V17 journey still selected one `[value=download]` after the forScore interface gained two download buttons. Playwright correctly rejected the ambiguous selector. | V17 test harness | Select the existing top action explicitly, as V18 already did. The actual forScore interface needed no change. V17 and V18 each pass **80 complete browser journey checks and 25 accessibility scans**. The specialised forScore suites exercise both download actions separately. |

## Preserved contracts

| Concept | Executed evidence |
| --- | --- |
| Installation-owned configuration, salt and file-based data; UTF-8 and legacy byte filenames | Encoding/unit/HTTP checks, exact source snapshots, legacy editor round trips, shared-directory cases. No Main PHP route is physically absent from V18; retired routes are explicit stubs. |
| Initial-password onboarding, optional personal passwords, expiry and permissions | Main parity; authentication, member-format, legacy-user and progressive-protection suites where those features exist. Current/archive, Library, recording and hidden-file permissions remain distinct. Extension-defined member permissions survive edits. |
| Ordered/repeated tunes and literal headings, including slash-containing headings | Main parity plus Print/XLS and forScore regressions. A heading is not silently substituted with another tune or conductor score. Section/Parts fallback and custom configured markers remain supported. |
| Current/archive lists, configurable navigation, recordings and Library | Default-layout native browser checks on all 14 lines; configuration/width/current/archive/role checks; alpha/newest Library exports and view checks. The classic CSS line additionally exercises long-list navigation, resize and JavaScript-disabled layouts. |
| Media notes/links, upload/rename/remove, attachments and gig/user administration | Real HTTP/storage tests; DOM and native task journeys in the corresponding stages. Exact download/upload bytes, conflict handling, recovery retention, copy/archive/trash/restore, draft protection and popup behaviour are exercised. |
| ZIPs and forScore identities/import choices | ZIP integrity/concurrency tests where implemented; exporter unit/HTTP/software-DOM/native-browser tests in every exporting line. Tests cover missing notes, headers, repeats, inventory choices, stable identities, revisions, section permissions, both actions and the original-tab return flow. |
| Optional federation and V18 conversion | SAML/OIDC tests with the locked dependency versions and local synthetic providers in V15–V18. Local login remains usable without those providers. V18 tests real advertised input formats, original MP3 bytes, fallback, limits, deadlines, cleanup and the new concurrent-change cases. |

Intentional evolution is retained: Main has no forScore exporter; the media Library has no forScore link following the explicit later decision; Print/XLS remain. The iTunes/RSS retirement in V10 and later is documented. Optional personal passwords, management interfaces, login protection, federation and audio conversion appear in their designated stages; this audit does not insert those later features into earlier designs.

## Three additional investigation rounds

| Round | Independent perspective | Result after corrections |
| --- | --- | --- |
| 1 | Unusual file collections, combined markers, filename prefixes, canonical and legacy metadata | 574 checks across all 14 branches |
| 2 | Two independent editors, stale forms, renamed/recreated destinations and subsequent saves | 130 checks across V11–V18, where optimistic media revisions exist |
| 3 | A musician's setlist across screen, Print, XLS, actual forScore XML, direct PDFs and section ZIPs; all registers, headings, repeats and same-name archives | 596 checks across all 14 branches |

These **1,300 additional checks** are included in the final combined matrix below, rather than added to its total a second time. The final matrix was rerun on the complete corrected source after the exploratory rounds. Test fixtures preserve the established CSV branding field and distinguish the empty counter cell from the administrator's edit control. Older media tests now consume the server-issued opaque revision instead of recreating its former implementation formula.

## Final executed matrix

Runtime: **PHP 8.3.6**, **Chrome for Testing Headless Shell 156.0.8078.80**, **FFmpeg 6.1.1 with libmp3lame**, Node/Playwright, jsdom and axe-core. The forced Latin-1 server-default cases are included. Optional federation dependencies were verified against each branch's lockfile: firebase/php-jwt v7.2.1, onelogin/php-saml 4.3.2 and robrichards/xmlseclibs 3.1.5.

Counts below are suite/assertion execution counts, not a claim that every assertion represents a distinct requirement. The [CSV matrix](evolution-audit-20261102.csv) and [evidence JSON](evolution-audit-20261102.json) retain suite names, counts, outputs and source fingerprints. The final V17 row uses its complete rerun after the selector repair.

| Branch | Suite executions | Numbered checks | Result |
| --- | ---: | ---: | --- |
| `main` | 7 | 479 | PASS |
| `7.7-original-interface-forScore` | 8 | 1,648 | PASS |
| `7.7.2-css-gigrep-layout` | 8 | 2,291 | PASS |
| `V8-compact-interface` | 10 | 1,532 | PASS |
| `V9-forScore-export` | 10 | 1,532 | PASS |
| `V10-personal-passwords` | 13 | 1,682 | PASS |
| `V11-gig-user-management` | 21 | 2,153 | PASS |
| `V12-login-protection` | 23 | 2,231 | PASS |
| `V13-zip-download-layout` | 23 | 2,229 | PASS |
| `V14-refined-interface` | 23 | 2,230 | PASS |
| `V15-oidc-saml-reliable-zips` | 28 | 2,447 | PASS |
| `V16-php85-php9-preparation` | 30 | 2,514 | PASS |
| `V17-unified-interface-and-workflows` | 32 | 2,630 | PASS |
| `V18-audio-upload-conversion` | 33 | 2,715 | PASS |

All fixtures use synthetic accounts/music and disposable installations. No production member data, hosting installation or outbound mail delivery was changed or used.

## Reviewed inputs and tested source

The input SHA identifies each published head before these repairs. The final SHA-256 fingerprints the tested application/fixture source, using sorted relative paths, a NUL, file bytes and another NUL. README, docs, vendor and Git metadata are excluded; the locked vendor versions are identified above. Publication commits add only the reviewed repairs, tests and documentation.

| Branch | Input commit | Final source SHA-256 |
| --- | --- | --- |
| `main` | `3b337b04878703d435613136e3be434a7886293a` | `7ec053dba2ee69ad4242ce10a1d98d79e6b3f2f87d76b89303532be6cdb40171` |
| `7.7-original-interface-forScore` | `0f04a89b9c141573c304729a5c8a17ebfbc242a8` | `7cdecdc07ba824b3f6292bbe69305509e1f00232091343d970b7fbfce3fecd29` |
| `7.7.2-css-gigrep-layout` | `3c14c2f1111e32a05c8efd49cb6dd9e5f9bd32aa` | `a53661d295fdb43205ee9ea57f4ddb01ad0b559e055efd21a465bdb54502d3d7` |
| `V8-compact-interface` | `9260a17ebc615a9a30858b62bf7ea7e1407d06bb` | `564fd01c5e4abae2af87d88f56214cd0d2726108ebd7cc9d6455b12c14287765` |
| `V9-forScore-export` | `0f9595952301968631159dd4a0b403c150cf497c` | `2f558a8872b0d823b70b2799aea0b800465edc228e02d4920ba8834fb8f274a5` |
| `V10-personal-passwords` | `a584a98d93d3b180e31e60ed220cb193b07500a8` | `def226dd00c9963f85b0c849aafee889114109b5817be30f6584f54e12732091` |
| `V11-gig-user-management` | `cdaddcdebb8ed938ef27e3433189ca489e632b04` | `a384ec5ad772ad450204663b8cbd9fda11d7d690cc4f9c58db5780d3177c3a18` |
| `V12-login-protection` | `964a99449612b95d4fb191336d0b412a36812147` | `8f4e6d92eb35c3920ca3c9a90bf8f9b0d5e3f19a59df19956ff1617053cccf6c` |
| `V13-zip-download-layout` | `54b9038e76d9db6e9d18090adeec0c7c3d765d6e` | `a00267531fe0ad3bd9997d2be2ac8773a7e94a82867f4acfe70aa43f842c4659` |
| `V14-refined-interface` | `888373cfea3d61d716db6c5eeb1fee8222a6d4e2` | `c70cf162913f415d8952d795d2b6a9dd72cd3f68727e7f5f0cb2d4274742ea2e` |
| `V15-oidc-saml-reliable-zips` | `a6434f6e2700c00430c11257ab2272a9b8fd5040` | `0b9c6548ce66dc583e552829b26dc6c93559b3e936a0ccf8301b5e76a05d3a84` |
| `V16-php85-php9-preparation` | `4b4cec9e03aa3a9b24469685c080dab56358ea8b` | `98317896b044fbd3d277f38e947eef2c682ce4295cb9804f600ba41ced169ec7` |
| `V17-unified-interface-and-workflows` | `324cbea70556eead3ca4414f7f94c8fac36415b9` | `09aab110d312cfc5b3028af61e17372f7af6186243a78ef59b105b513af73737` |
| `V18-audio-upload-conversion` | `9861f8bb28eec85c8ef8b43031fbd00037f9c515` | `04eb17025d37cfbe311e56652699f0adf3b83c2fa399572c3c239951f12fa2a5` |

## Applying the corrections and remaining boundaries

Use the updated maintained branch matching the installation. The common runtime changes are in `nadinmodule/table.inc.inc.php`, `nadinmodule/table.print.php` and `nadinmodule/table.inc.xls.php`. The six legacy lines also update archive cache cleanup. V11–V18 add `media-revision.inc.php` and update the matching notes form/save guards; V17–V18 also update their explicit notes form and response. V18 updates `nadinmodule/audio-convert.inc.php` and `nadinmodule/media-upload.inc.php`. Preserve installation configuration, salt, members, music, GigReps, attachments and identity metadata. No data-format migration is added. Existing legacy generated ZIP caches need regeneration through the existing ZIP workflow before they reflect the corrected classification; this review does not modify a live cache.

The cumulative classic `NADIN-7.6-to-7.7.zip` update is prepared from the corrected classic branch and checked by applying it to an untouched 7.6 fixture. The matched source files and all excluded configuration/member/music files are verified separately. Production deployment remains an installation operation.

These results establish the enumerated contracts on the tested fixtures. They do not certify every production data combination, native iPad/forScore import, Safari, real SMTP delivery, a production IdP, hosting converter availability or a native PHP 9 runtime. PHP 8.5 was not rerun for this review.

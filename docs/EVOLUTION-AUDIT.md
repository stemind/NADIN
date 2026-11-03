# NADIN evolution audit — 31 October 2026

For the subsequent current-source review, see [the 2 November 2026 audit](EVOLUTION-AUDIT-2026-11-02.md). The results below describe the historical source snapshots listed here.

Historical audit: the results below describe the source and layout contract at that time. The current [consolidated 7.7 guide](7.7.md) supersedes the old release instructions and missing-flag behaviour: only `no` selects the top list.

**Result:** all 278 final suite executions pass on PHP 8.3.6 and PHP 8.5.12 after the corrections below. The final matrix contains 27,688 numbered assertions, including 3,356 PHP syntax checks. It covers the maintained Main baseline, the conservative 7.7 development line, release 7.7.2 and V8–V16. The corrected runtime/test source fingerprints match the tested working trees.

The source reference is historical `v7.6`; its functional HTTP contract also passes. That historical tag still displays 7.5 in its login footer. The maintained Main baseline displays 7.6; historical tags are not rewritten.

## Confirmed collateral findings and corrections

| Finding | Affected source | Correction and evidence |
| --- | --- | --- |
| A missing classic layout flag silently switched the list to the left | 7.7 development | Only explicit `yes` enables the left list. Missing/no keeps the original top position. Current/archive, roles, widths and scripts-on/off checks pass. 7.7.2 preserves this behaviour from the outset. |
| Login headline showed the previous numbering while the footer showed the current stage | V10–V14 | Corrected the actual account-page heading. The cross-branch HTTP contract now checks all visible login version labels, instead of accepting one correct footer beside an incorrect heading. |
| Media Library forScore link incorrectly restored as an assumed missing capability | All forScore branches | The media Library intentionally has no forScore link. Removed the link and corrected the earlier audit classification. Regression checks require its absence for both roles, sort orders, desktop/mobile and without JavaScript; Excel and Print remain available. GigRep/archive forScore links retain each branch's design. |
| Headings containing slashes became missing-note warnings | V13–V16 | Plain text such as `Set 2 / Pause:` is again an automatic literal placeholder. Exact title/XML tests fail on the old source and pass after repair. Real existing-but-empty/inaccessible tune folders still require the missing-notes confirmation. |
| The required ZIP/attachment directory was absent from fresh conservative source downloads | 7.7 development, 7.7.2 | Added `abo/zip/donotdeletethisfolder.txt`; existing generated downloads and attachments are not included in update ZIPs. |
| New popup rejected an installation-owned top-level attachment-directory link | V16 | A configured shared `abo/zip` root is accepted. The popup lists and downloads exact attachment bytes; individual child-file links are still excluded. The new HTTP regression fails before the change and passes afterwards. |
| GigRep position restoration was only partly applied across development stages | V13–V16 | Completed the same config-controlled left/top rendering used by V8–V12, including top-mode CSS and the mobile-toggle handling. Role/current/archive/width/missing-flag and software-DOM cases pass. Compact default without a flag remains left. |
| README branch links named destinations that did not yet exist | V11–V16 | Completed the actual GitHub renames, retained the numbered stages and added per-line starting point/status plus a shared branch map. |
| Development-head features were described as though they belonged to the pending stable patch | 7.7 documentation | The conservative 7.7.2 release is explicitly based on published 7.7.1 and contains the classic CSS-layout maintenance. The separate 7.7 development branch retains its additional workflow work. |

## Final matrix

The counts below include each line's syntax checks and the assertions reported by its actual unit/HTTP/DOM suites. They are execution counts, not a claim of distinct or exhaustive requirements. [The full machine-readable matrix](evolution-audit-results.csv) lists the individual suites.

| Line | PHP | Suite executions | Numbered assertions | Result |
| --- | --- | ---: | ---: | --- |
| main | 8.3 | 4 | 281 | PASS |
| main | 8.5 | 4 | 281 | PASS |
| 7.7 | 8.3 | 5 | 868 | PASS |
| 7.7 | 8.5 | 5 | 868 | PASS |
| 7.7.2 | 8.3 | 5 | 1,087 | PASS |
| 7.7.2 | 8.5 | 5 | 1,087 | PASS |
| V8 | 8.3 | 7 | 848 | PASS |
| V8 | 8.5 | 7 | 848 | PASS |
| V9 | 8.3 | 7 | 848 | PASS |
| V9 | 8.5 | 7 | 848 | PASS |
| V10 | 8.3 | 10 | 998 | PASS |
| V10 | 8.5 | 10 | 998 | PASS |
| V11 | 8.3 | 14 | 1,353 | PASS |
| V11 | 8.5 | 14 | 1,353 | PASS |
| V12 | 8.3 | 15 | 1,423 | PASS |
| V12 | 8.5 | 15 | 1,423 | PASS |
| V13 | 8.3 | 15 | 1,409 | PASS |
| V13 | 8.5 | 15 | 1,409 | PASS |
| V14 | 8.3 | 15 | 1,410 | PASS |
| V14 | 8.5 | 15 | 1,410 | PASS |
| V15 | 8.3 | 20 | 1,626 | PASS |
| V15 | 8.5 | 20 | 1,626 | PASS |
| V16 | 8.3 | 22 | 1,693 | PASS |
| V16 | 8.5 | 22 | 1,693 | PASS |

## Additional executed browser and reference checks

- Actual Chromium: the conservative layout is compared with released 7.7.1 for current/archive, member/admin, yes/no/missing flag, 240/375 px widths and three window sizes. The reference run passed **1,816 browser assertions and 145 HTTP/source assertions**. Comparison covers horizontal positions and dimensions; it is not a pixel-diff certification. The new layout is also exercised without JavaScript, on resize, with long-list scrolling/navigation and reopening archive info. The final matrix additionally repeats the new-layout browser fixture on both PHP runtimes.
- Actual Chromium: **18 forScore link, typography, popup and narrow-window checks** on 7.7.2 passed. The forScore service passed 62 unit checks and 71 HTTP checks; both PHP runtimes and a forced Latin-1 server default are included in the final matrix.
- Actual Chromium: **24 interaction/layout checks on each of V11–V16** passed. These exercise the structured heading editor, Undo, tune numbering, drag/drop/hover behaviour, library interactions and compact layouts using disposable rendered views.
- The original 7.6 source passes **41 functional reference HTTP checks**. New 7.7.2 passes **43 functional HTTP checks per runtime**, covering admin/member/expired accounts, custom section/Parts markers, ordered/repeated tunes and literal headings, Print/XLS, recordings, links, attachments, Library access and exact unchanged source bytes.
- Final V16 passes the complete existing native-runtime suite with strict future-error simulation on PHP 8.5, including the new shared-attachment regression. This promotes selected future-sensitive notices/deprecations and disables retired functions; it is explicitly **not a native PHP 9 runtime**.

## Preservation boundaries

The checks cover local initial-password onboarding, optional personal-password flows, expiry and rights, custom register markers and Parts fallback, hidden files, current/archive sources, Print/XLS, audio and sheet downloads, gig attachments, create/copy/archive/trash/restore operations, legacy member conversion, unknown extension rights, encoding and byte-preserving legacy filenames, shared installation-owned directories, ZIP integrity/concurrency, optional federation and its local-login fallback.

`main` remains the maintained legacy comparison line; this work does not merge the full V16 application into it. The historical retirement of iTunes/RSS in V10-and-later branches remains documented; normal ZIP downloads and attachments remain available. Stable authentication/cache namespaces are retained during the visible version-number corrections.

Native import into the forScore iPad app, real mail delivery, production hosting, real external identity providers and native PHP 9/vendor compatibility are separate acceptance checks. The fixture suites do not modify production installations or production member/music data. Browser coverage is the specific Chromium coverage above, rather than an assertion about all browsers or every screen.

## Tested inputs and source fingerprints

Input commit IDs identify the source before these repairs. The SHA-256 values below fingerprint the final application/test source used by both runtime jobs; README/report files are outside the runtime-source fingerprint. The source trees published to GitHub are checked against the prepared local Git tree IDs.

| Branch | Input commit | Final runtime/test SHA-256 |
| --- | --- | --- |
| `main` | `871deeaddf81a997e9ac606c2136b8a3f32586db` | `f01ef28ca9506f73428aa649c944e0c8e4e2efd7064e5c5c4cf3531db6f827e7` |
| `7.7-original-interface-forScore` | `2536aea9f21eb2833206baff07fc7f7b637cc1e3` | `59dbe65af483fa0cdd35b9cfb3f985e2aff198a3c6de313e7a50ef8a29c77114` |
| `7.7.2-css-gigrep-layout` | `ecf735b1d226d6543f1c9bdee01dd231b8c11d68` | `bacfcb7bcac3f87265ec304515ba54bf5d4303510458b102c822ce7c7659be78` |
| `V8-compact-interface` | `7e7a73b6347144d6f7b98b63ad40735114b37fcc` | `80bc89aabf30d6614a6ef6f6a4ce2ad40cf8fa771aa33c1c8237a03a124b27c2` |
| `V9-forScore-export` | `79169ba2680888db504f3bf3b5670a745de3a552` | `3033bd64cac122d20a1857709d6e9c0020af1eb9419c0102bb09bbf492888688` |
| `V10-personal-passwords` | `d54a06217a217caab11feb01837a51f0128d76ed` | `b228121161d9e7566cd0ad657fa8545160b0c7ef85f752e72a27c011a46b22bc` |
| `V11-gig-user-management` | `c26dd2b0df573c7088a2422079fe84c16ea021dc` | `a04090f4ebe20c1ba2f506c90bc5d077ed64369472b071f000b1f25181077146` |
| `V12-login-protection` | `9544a25f4448c67527f5172fc52771215a7904c8` | `d32bc2860349d88dbe8d3c0baa6d3d3357523aaf8e4b6528ff86e171282ebb44` |
| `V13-zip-download-layout` | `a01529d40d4d676693be41f3be2b78d4f27cbb28` | `3e2ba29662eee81d0d4700c744929ebd572eb2e80c61a31feac0ef94cf9288bd` |
| `V14-refined-interface` | `f3d147ecfb62e9891f62c5377fb379d33caf6443` | `9e4d2d0157b0dd49a4dcffdcee3a15e94dc49bbc7f4f95f1adf6553f9d5c1855` |
| `V15-oidc-saml-reliable-zips` | `8ec80a0569b5b306e09fa0b8ef972991333a5679` | `ca86e8d7036b43edbb8a04f8006060153bf33066147f774a589e325772d233fc` |
| `V16-php85-php9-preparation` | `880efe7ae1c413a8202492dc53859761a08210ba` | `98c61e300da3c98cc4577ca1760e5f1460ed4ad1b6117ca183cfb1bf9022303e` |

The [branch map](BRANCHES.md) describes the individual stages and their integration status. [7.7.2 notes](7.7.2.md) describe the smaller release delta and upgrade packages.

The [follow-up evolution check of 2 November 2026](EVOLUTION-RECHECK-2026-11-02.md) verifies all fourteen current branch snapshots and documents the additional gig-state, catalogue and audio-duration repairs.

The [repair countercheck of 3 November 2026](REPAIR-COUNTERCHECK-2026-11-03.md) challenges the repairs themselves, corrects three reproduced side effects and records the final 305-suite matrix plus both classic installation paths.

> Historical maintenance draft. The final source, release scope and independently executed results are described in [EVOLUTION-AUDIT.md](EVOLUTION-AUDIT.md). In particular, the Library forScore entry is retained and the missing classic layout flag keeps the original top position.

# Main-to-V16 maintenance review — 31 October 2026

The reference is maintained `main` at `6f0f7073ed66091a90c0e6a70153c5740e82e489`, with the original 7.x file model and UI. The conservative forScore line starts directly at `v7.6`, while V8–V16 are successive development stages. Shared fixes are applied to the existing affected lines, preserving their visual designs and stable authentication/storage identifiers.

## Findings corrected in this maintenance

| Finding | Affected lines | Result |
| --- | --- | --- |
| Hosting sends a Latin-1 HTTP charset despite UTF-8 HTML metadata | 7.7; explicit endpoint protection also added to V8–V16 | forScore HTML explicitly sends UTF-8; binary XML/PDF data retains its download type and bytes |
| Version labels lag behind their branch/release | Main, 7.7, V8–V16 | Main displays 7.6, the conservative patch displays 7.7.2, development screens display their V number |
| Library forScore entry is unsuitable for the whole library | 7.7, V8–V16 | Library link removed; GigRep/current/archive links and Library Print/XLS retained |
| Section or CSV change can leave an outdated preview unless Review is clicked | 7.7, V8–V16 | Native automatic form refresh, one required import choice, visible loaded inventory, pending state and no-JavaScript fallback |
| Legacy config placement was removed in V8, and classic layout relied on duplicated current/archive scripts | 7.7, V8–V16 | `rendergiglistontheleft` is honoured for both current/archive views; classic arrangement is server-rendered; modern layout preserves its mobile menu and uses the same config choice |
| Slash-bearing headings in the newer exporter become missing-note warnings | V8–V16 | Invalid path components remain inert heading text; no file lookup or missing-note confirmation |
| The V16 popup attachment listing rejects an installation-owned top-level `abo/zip` link | V16 | Shared top-level attachment directory accepted; child symlinks remain excluded |
| forScore controls lag behind the refined branch design | V14–V15; V16 checked/preserved | Controls align with each branch theme; V16 keeps its separately completed popup design |

The user's current-view layout failure on their live 7.7.1 installation could not be reproduced from its source alone: v7.6 and v7.7.1 used the same layout script apart from the forScore link, and 16 old/new config/current/archive/role cases passed. The patch removes that script dependency and the duplicate archive path rather than claiming a proven browser-specific cause. The installation flag is in `config.inc.php`.

## Concept and feature inventory

| Main concept | Evolution and review evidence |
| --- | --- |
| Database-free library, gig, archive, deleted-file and user directories | File model retained. Read flows check exact unchanged source bytes; configured top-level shared directories remain supported. Individual unsafe child links are rejected. |
| Existing email/initial-password onboarding, rights and expiry | Retained. Personal passwords at V10 add optional hashed credentials/recovery; local login remains independent of federation. Authentication, member-format and HTTP tests cover expiry, revocation and initial login. |
| Plain headings/breaks, ordered and repeated tunes | Original text/sequence retained in view, Print/XLS, editor and forScore. V11+ structured-editor fixes cover edit/cancel/Undo, consecutive tune numbers, hover and library drag/drop. |
| Per-installation register markers, Parts, hidden files and labels | A new cross-branch contract runs actual requests with custom section/Parts markers, a restricted musician, hidden media, source repetition and a same-name archive. Existing rights are used rather than inferred from labels. |
| Gig and tune/file management | Existing media tools remain; V11 replaces the gig/user editor with a structured editor. Tests exercise create/copy/rename/archive/trash/restore, attachments, PDF upload/rename/delete, exact tune reference updates, user conversion, extension rights, safety copies and stale-save rejection. |
| Print/XLS, recommended/other audio, tune infos/links, recordings and gig attachments | Preserved and requested through actual links. Cross-branch tests check headings/order/repetition, raw downloads, custom markers and current/archive sources. V16's cumulative suite also covers bracket/legacy filenames, audio rename collisions, link schemes and attachment context. |
| Config-controlled ZIP offers and section permissions | Preserved. Existing suites cover lazy creation, Parts fallback, disabled ZIP rejection, archive rights, exact bytes, invalid files, concurrent cache generation and source replacement. V15+ resolves configured shared roots without trusting child links. |
| Original layout and compact/mobile evolution | 7.7 stays classic. V8 starts the compact layout; V14 refines controls and colours; V16 aligns standalone popups. Both GigRep positions now honour configuration. Links survive software-DOM adaptation at desktop/mobile widths; print CSS still hides compact navigation. |
| iTunes/RSS subscriptions | Intentionally retired at V10, as agreed. Feed-only routes return 410. `abo/zip` remains because normal downloads and gig attachments need it. This is an explicit feature decision, not an unreported regression. |
| Optional federation | Added at V15. Local accounts/passwords still work with absent, invalid or failing optional federation configuration. TLS/signature/issuer/audience/replay tests use synthetic identity providers. |

## Executed validation for the final maintenance source

- Main and each of 7.7, V8–V16 pass the new **38 real HTTP concept/feature checks** with disposable member/admin/expired accounts and custom configuration.
- All ten forScore lines pass export unit tests and actual HTTP form/download tests on **PHP 8.3.6 and 8.5.12**, including a forced ISO-8859-1 server default. Automatic workflow behaviour adds **33 software-DOM checks** per line. Earlier UTF-8/default-charset matrix checks remain applicable to the explicit charset implementation.
- GigRep configuration checks cover member/admin, yes/no/missing flag, two sidebar widths, current/archive, desktop/mobile and scripts on/off. Classic: **75 HTTP + 480 DOM** checks; compact lines: **99 HTTP + 336 DOM** checks.
- V10–V16 retain authentication/member-format checks. V11–V16 also pass **108 administration HTTP checks** with ordinary directories and another **108 with shared directory links**, plus legacy-user conversion checks.
- Final V16 passes the complete updated runtime suite on both PHP runtimes with future-error simulation: **1,596 numbered checks plus 163 PHP syntax checks per runtime**. This includes actual forScore HTTP/workflow/layout contracts, encoding, accounts, admin/storage, federation, ZIP integrity/concurrency, legacy filenames, media, shared attachments and software DOM.
- The shared-attachment case fails before the V16 listing fix and passes afterwards. Slash/path-like heading regressions are covered by XML parsing and exact literal-title checks.

The current maintenance phase has not run native browser visual acceptance or imported the result on an iPad. Software DOM is not a substitute for those checks. Native PHP 9, real production mail and real external identity providers remain separate acceptance boundaries. The fixture suites do not alter a production installation or production user/music data.

## Deployment and branch map

Use the selected branch's source files together. Preserve local configuration, salt, user/authentication files, music, Reps, attachments and hidden forScore metadata. Never replace production `config.inc.php` wholesale. Include the new `nadinmodule/forscore.js` in upgrades. The 7.7.2 release supplies correction/7.6-upgrade ZIPs without configuration or user/music data.

Descriptive GitHub branch names and per-branch README descriptions identify the evolutionary stages. The historical `php9_compat` branch is removed; its former head was `11e04e05faeb5d56372d77c3870ab6ad4cb1cfc5`. Historical release tags are retained. Internal `auth-v11`/`admin-v12`/cache identifiers remain stable compatibility identifiers, not displayed release numbers.

# NADIN everyday-use corrections — 31 October 2026

The additional review reproduced three gaps not covered by the earlier release suites. These corrections are applied to the existing affected branches, preserving their version-specific functionality.

- Historical user lists can contain quoted names, doubled quotes, or unquoted commas before the account email. The shared reader locates one unambiguous email field and retains the complete name. Authentication and the people editor use the same reader. Saving quotes names when necessary, preserves extension permissions and makes a safety copy. Existing account stamps remain unchanged when loading. Blank lines and instructions are preserved; account-like rows with an ambiguous/missing email are listed under “Entries to review” instead of disappearing silently. No identity or permission is guessed.
- Current-gig headings and blank breaks have a direct edit button. Apply, cancel and Undo retain the existing order and tune numbering. A blank text creates a break. Archived/deleted gigs remain read-only.
- ZIP downloads accept installation-owner-configured symlinks for the top-level `library`, current/archive gig directories and `abo/zip`. Source and cache paths are resolved against those directory roots. Symlinks to individual tunes, media files, gig files, cache subdirectories, cache files and locks remain rejected. The existing top-level `userdatei` directory link continues to work; authentication files themselves must remain ordinary files.

The user-list reader is corrected from V10 onwards; the structured people/heading editors exist from V11 onwards. The restrictive ZIP parent-directory checks were introduced in V15 and are corrected in V15–V16. Earlier branches already accept the relevant top-level directory links and are checked without changing their ZIP implementation. No production installation or user/music data is changed by this source update.

Validation uses disposable old-format user lists, actual logins and saves, real ZIP bytes and directory links, rejection cases, archived gigs, dialog cancel/Undo, and real Chromium interactions. The cumulative runtime suite remains the final application regression check. Native PHP 9, production mail delivery and real identity providers remain separate acceptance boundaries.

## Executed verification

All seven affected branches were checked on PHP 8.3: 35 member-format and 67 authentication checks each. V11–V16 additionally passed the user-import/save tests, the normal administration HTTP suite and the same 108 HTTP checks with all seven data directories shared through symlinks, plus heading-edit/cancel/Undo DOM tests and 24 real Chromium interaction/layout checks. V15–V16 retained the ZIP integrity/concurrency suite.

V16 passed the complete cumulative suite on PHP 8.3.6 and PHP 8.5.12 with future-error simulation: 954 numbered checks and 162 PHP syntax checks per runtime. These are local test results, not GitHub CI or a certification of native PHP 9. The fixtures are disposable; no production identities, user files, music or mail delivery were exercised.

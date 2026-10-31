# V16 popup design alignment

The standalone editors now use the main interface's system font, blue actions, grey surfaces, yellow current section, compact spacing and rounded fields. `popup-ui.inc.php` provides one title bar with a keyboard-accessible close button; the shared CSS is scoped to popup windows and media frames.

Covered windows: tune notes/links, recordings, PDFs/other files, gig attachments, new/rename/delete tune, their completion pages, access details, login timeout, gig preview and forScore export. File lists, upload forms and upload/error responses use the same visual language. Tune notes show unsaved changes in yellow and successful saves in green, retaining the existing save button, Ctrl+S, revision token and unload warning.

File rename and delete confirmations use the existing `.nadin-dialog` component. They mount in the same-origin editor document so the short file-list iframe cannot clip them. Rename selects the existing filename and rejects blank input; delete initially focuses Cancel. Escape and Cancel make no request and restore focus to the original file action. Confirmed actions still use the existing authenticated POST, CSRF and filename-byte handling. Older browsers without native dialog support retain the previous prompt/confirm fallback.

The file selection, filesystem operations, permissions, setlist and forScore export logic are retained. This change is confined to V16; it does not alter the separate 7.7 work based on the original 7.6 interface.

## Verification on 31 October 2026

All tests used disposable installations and synthetic accounts/files:

- 158 Chromium popup checks: ten window types at 800, 390 and 320 pixels; iframe overflow; rename/delete, Cancel, Escape, Enter, blank-name validation, initial focus; notes save and Ctrl+S; Unicode persistence; tune creation, rename and deletion; completion pages; no uncaught browser errors. Screenshots inspected for files and modal layout.
- 24 existing Chromium editor/library interaction and layout checks.
- 93 HTTP encoding checks, including the real file-action JavaScript and legacy filename bytes.
- 108 administration/storage HTTP checks and 41 V16 HTTP regression checks on PHP 8.3.6.
- 33 final-review HTTP checks on PHP 8.3.6.
- 49 HTTP compatibility checks with strict future-error simulation and 32 forScore heading/export HTTP checks on PHP 8.5.12.
- All 26 changed PHP files pass syntax checks under PHP 8.3.6 and 8.5.12; the new JavaScript passes `node --check` and `git diff --check` is clean.

`tests/encoding-http.py` loads the file-dialog script explicitly in its DOM fixture, so it continues to exercise the actual mutation URL and POST fields.

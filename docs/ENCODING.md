# Text encoding and existing filenames

NADIN renders HTML, JSON and CSV as UTF-8. `nadinmodule/encoding.inc.php` converts old ISO-8859-1/Windows-1252 text at output boundaries, preserves valid UTF-8 runs in mixed files and removes a leading UTF-8 BOM. Accents, German umlauts, typographic quotes and the euro sign are retained. Conversion does not require iconv or mbstring.

Filesystem paths remain byte-exact. Display conversion must never be applied to a file lookup. Existing single-byte and UTF-8 directories are not renamed automatically, and opening the library no longer invokes the historical ASCII renaming pass. Explicit upload/rename operations keep the version's established filename rules.

Hidden media form identifiers and mutation links use explicitly marked percent-encoded values so a browser's UTF-8 form submission cannot silently change a legacy path. The server decodes only the named fields of the corresponding endpoints. Existing authentication, permissions, CSRF checks and revision checks still apply. Visible text fields are normal UTF-8.

The gig editors map displayed legacy tune names back to existing source bytes and retain missing references and repetitions. Ambiguous displayed names are rejected before writing. Existing gig attachments remain in `abo/zip`; uploaded PDFs and media contents are not converted.

CSV exports contain a UTF-8 BOM and UTF-8 cells. Print views use UTF-8. Download filenames explicitly labelled UTF-8 are encoded accordingly. ZIP entry names use UTF-8 while their source paths remain unchanged; ZIP caches rebuild when the name conversion changes.

## Regression checks

- `php tests/encoding.php`: UTF-8, Latin-1, Windows-1252, mixed text, BOM, HTML escaping, hidden path round trips and ambiguous setlist names. Also runs with `php -n`, without iconv/mbstring.
- `python tests/encoding-http.py`: disposable installation; login, library/gig display, alphabet, printing, CSV, media forms, note save/reopen, identical-looking directories, old/new gig editors, PDF and ZIP downloads, and explicit deletion of an empty legacy directory. Requires Python `requests`/`beautifulsoup4`; branches with the newer media list also use Node.js `jsdom` to execute the actual rename handler.
- V16 includes both checks in `tests/check-runtime.py`; run with the documented PHP runtimes and dependencies in `docs/V16.md`.

No bulk conversion of installation data is performed. Previously stored ambiguous or already garbled text is not guessed or rewritten.

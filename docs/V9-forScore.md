# NADIN V9: forScore export

V9 builds directly on V8. Current/archived GigReps and the library have a compact **forScore** link. No WebDAV server, separate cloud account, or iPad helper app is required.

## Workflow

1. Open a Rep (or the library) and choose forScore, then your section.
2. For the first import into an empty NADIN collection, choose **Setlist + PDFs missing from the inventory** and explicitly confirm the first import. A non-empty collection should instead supply a current forScore metadata CSV with the Filename column, exported from forScore's Backup panel.
3. Review the proposed PDFs. The selected section is preferred; Parts is the fallback. Score is never substituted for a missing section. Multiple candidates require a choice. Missing files and free-text entries require explicit placeholder confirmation.
4. Download the `.4ss` file and open it in forScore. PDFs embedded in Open Setlist XML require forScore 14.3 or later. Reference-only exports require forScore 12 or later.
5. Before subsequent imports, supply a fresh CSV from the actual destination library. Existing PDFs are referenced and only missing PDFs are embedded. A downloaded file or an earlier export is not evidence of a successful import.

A Rep preserves its sequence and repeated tunes. A newly embedded PDF appears only once in the transport file; subsequent occurrences reference the same filename. The same notes can belong to multiple Rep setlists. Existing original NADIN filenames can also be adopted when the filename is unique across the source library. PDF contents cannot be verified from a filename-only CSV. Custom renames that remove the NADIN identity prevent automatic matching; do not resolve such cases by title alone.

## Updates and identity

Each tune folder receives two hidden files: `.nadin-forscore.json` (persistent PDF identities) and `.nadin-forscore.lock` (writer lock). Keep these files with the tune in backups and transfers. Writes are serialized and the JSON is replaced atomically. Invalid metadata blocks the export rather than silently regenerating IDs. No source PDF or Rep is rewritten.

Export filenames contain an asset ID and a content revision digest. Unchanged notes have the same filename in every Rep. Replacing a source PDF creates a new revision filename; the old local file stays selected when identified in the inventory. An added section does not automatically replace already imported Parts. Users explicitly select the new revision/section. Original forScore PDFs and their annotations are never overwritten by this exporter. Repeated import of a stale/incomplete inventory can nevertheless create copies: use a current CSV.

A tune-folder rename retains identities because the metadata moves with it. Renaming an unchanged PDF retains its identity if its content identifies one unique missing previous filename. If a folder is copied while its original still exists, the copy receives distinct identities. A simultaneous rename and content change cannot safely be inferred and is treated as a new asset. Independent arrangements are never deduplicated solely because titles or PDF bytes happen to match.

## Access and limits

The endpoint authenticates the existing NADIN session and checks current GigRep, archive, library, section, Score and hidden-file rights. It resolves export selections on the server, rejects traversal and symlink sources, and validates the reviewed plan before download. Source or Rep changes require another review. CSRF protection and a form-completeness marker guard POSTs. Inventory CSVs accept comma, semicolon or tab delimiters, UTF-8 BOM, UTF-16 and Windows-1252 text. Invalid/duplicate filenames and title-only lists are rejected.

No inventory is persisted on the server. It is returned to the browser as a hidden form field for the review/download request. Export XML is streamed through a temporary file outside the application tree and removed when the request ends. Maximum inventory input is 5 MB; PHP's own upload/post limits can be lower. Embedded PDFs are limited to 150 MB per export. Large collections should be exported as smaller Reps. The web-server user needs write access to the tune folders for persistent identity metadata and to PHP's temporary directory for downloads. Keep the existing protection of `library/`; exclude `tests/` from deployment or deny web access to it.

This is a controlled import/export workflow. It does not automatically synchronise the iPad, modify existing forScore setlists in place, upload annotations, or attach recordings. After verifying a newly imported Rep, remove the previous setlist if forScore keeps both. Removing a setlist does not remove its notes.

## Validation

`php tests/forscore.php` exercises section/Parts selection, repeated tunes, cross-Rep reuse, revision changes, keeping local Parts, explicit ambiguous-file selection, source access, symlinks, hidden PDFs, CSV encodings, legacy filenames, file/folder renames, copied arrangements, Windows-1252 filenames, binary PDF transport, mid-export changes and corrupt identity metadata.

Additional HTTP checks cover initial import, a second import with zero embedded PDFs, inventory review, stale source rejection, archive/library/section/traversal permissions, unauthenticated requests and CSRF rejection. Browser checks cover the export form at 320/390/1440px and retain V8's navigation/menu checks. Generated XML is parsed and embedded PDF bytes are compared with their sources. PHP 8.3 and Chromium were used. Actual import into the native forScore application remains an acceptance check on the user's iPad; it has not been claimed as tested.

Official format reference: https://forscore.co/developers-file-types/

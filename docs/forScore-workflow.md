# forScore workflow

The export keeps the interface of each NADIN branch. The classic line keeps its original controls; V14 and later inherit the refined interface, and V16 keeps its popup frame. Every standalone export explicitly loads NADIN's own favicon. The title and heading show forScore without a release number.

Open forScore from a current GigRep or the archive in a new tab. The media Library has no forScore link; its Excel and Print exports remain available. **Back to NADIN** closes the export tab, revealing the original NADIN page with its selected view and scroll position intact. This also works after automatic selection refreshes and downloads. Directly opened pages, or browsers refusing to close a tab, follow the normal NADIN return link as a fallback.

1. Choose your section. A one-year, HTTP-only cookie remembers it for this installation and account in this browser. Explicit section choices take precedence; unavailable saved sections are ignored. No inventory or import confirmation is stored in that cookie.
2. Choose the import intent: first NADIN import with PDFs, update using a fresh forScore inventory CSV, or setlist only. Section, import and CSV changes refresh automatically. With JavaScript disabled, use **Update selection**.
3. A single matching PDF is selected and displayed automatically. Only multiple PDF/revision choices need a selection. Headers are automatic text placeholders; actual missing notes still require confirmation.
4. Check the download summary. It counts ordered entries and headings, unique embedded/reused/referenced PDF filenames, missing-note entries and unresolved PDF choices. Repeated tunes keep their positions while each new PDF is embedded only once. PDF selections update the summary immediately.
5. Choose **Download forScore setlist (.4ss)** at the top, beside the live summary, and open it in forScore. An identical action remains below the tunes for long setlists. Both buttons stay active and share the same reviewed choices. They have an explicit NADIN-blue appearance in both classic and newer designs. If an obligatory field is missing, the browser scrolls to it, focuses it and displays its validation message; the server independently checks permissions, CSRF, the current preview and file contents.

The first-import option is deliberately not remembered: a download does not establish that the notes were imported. For updates use a fresh Filename inventory so existing files and annotations can be reused. CSV content is processed for the request and carried through this form, without server-side persistence. No change is made to the exporter format, file identities, heading detection or permission model.

The optional **Import and updates** help has its own separated area below the download action. The busy refresh state does not grey out the button. A click during a pending refresh announces that the selection is still being updated.

The export page loads `nadinmodule/forscore-actions.css` for its scoped action styling. Existing classic 7.7 installations can copy just that new file, `nadinmodule/forscore.js`, `get/forscore.php`, `nadinmodule/gigreps.inc.php` and `nadinmodule/library.inc.php` from the cumulative update, retaining any local calendar customisation in `nadinmodule/infos.inc.php`. The release version remains 7.7.

# forScore workflow

The export keeps the interface of each NADIN branch. The classic line keeps its original controls; V14 and later inherit the refined interface, and V16 keeps its popup frame. Every standalone export explicitly loads NADIN's own favicon.

1. Choose your section. A one-year, HTTP-only cookie remembers it for this installation and account in this browser. Explicit section choices take precedence; unavailable saved sections are ignored. No inventory or import confirmation is stored in that cookie.
2. Choose the import intent: first NADIN import with PDFs, update using a fresh forScore inventory CSV, or setlist only. Section, import and CSV changes refresh automatically. With JavaScript disabled, use **Update selection**.
3. A single matching PDF is selected and displayed automatically. Only multiple PDF/revision choices need a selection. Headers are automatic text placeholders; actual missing notes still require confirmation.
4. Check the download summary. It counts ordered entries and headings, unique embedded/reused/referenced PDF filenames, missing-note entries and unresolved PDF choices. Repeated tunes keep their positions while each new PDF is embedded only once. PDF selections update the summary immediately.
5. Choose **Download forScore setlist (.4ss)** and open it in forScore. The button stays active. If an obligatory field is missing, the browser scrolls to it, focuses it and displays its validation message; the server independently checks permissions, CSRF, the current preview and file contents.

The first-import option is deliberately not remembered: a download does not establish that the notes were imported. For updates use a fresh Filename inventory so existing files and annotations can be reused. CSV content is processed for the request and carried through this form, without server-side persistence. No change is made to the exporter format, file identities, heading detection or permission model.

The optional **Import and updates** help has its own separated area below the download action. The busy refresh state does not grey out the button. A click during a pending refresh announces that the selection is still being updated.

# V17 — interface and workflow design

Research and source review: 1 November 2026. Starting point: V16, including the compact ZIP download row. These scenarios are derived from NADIN's actual routes, permissions and file model, and Stefan Minder's requirements. They are not a claim of interviews or a user study.

## Design decisions

NADIN is a working music library, not a dashboard. Keep its familiar navigation, dense setlists, direct downloads, instrument markers and file-based installation model. Use the existing blue, restrained yellow selection and neutral surfaces consistently. The member's music remains the visual priority; rare administration actions have lower emphasis. Keep the installation's GigRep position setting.

Long-running editing belongs in an editor or task window, where the user can consult the source list. Short decisions belong in a native dialog. A confirmation states the affected item and consequence, names the action and returns keyboard focus. Destructive confirmations start at Cancel. A saved notice remains near the action; an error keeps the entered values and explains recovery. Saving and uploading are explicit, separate from choosing a file.

## Use cases and acceptance

| Person and real task | Before / risk | V17 response and acceptance |
| --- | --- | --- |
| Musician opens the current rehearsal on a phone | Administration and download decoration can compete with the setlist | Keep compact tune rows, current/archive navigation and one wrapping ZIP link row. Instrument and Parts permissions still control actual downloads. |
| Musician looks for another recording or tune notes | Unlabelled click targets and hover-dependent controls | Named keyboard-operable edit links and disclosure buttons; expanded state and controlled content are exposed. One mobile action menu at a time; Escape returns to its trigger. |
| Musician imports a Rep into forScore | The wrong section or an unrefreshed preview can lead to a wrong export | Retain the current forScore workflow improvements and per-installation section choice. Explain initial import, reuse and CSV inventory; keep signed previews, headings, repeated entries and annotated-file protection. Do not claim a download was imported. |
| Librarian adds a tune and immediately adds media | The create window closes before the next useful action | Keep success visible and offer the relevant next editor. Invalid names remain editable with their error. |
| Librarian uploads/replaces scores or recordings | File selection posts immediately, fixed iframe height clips controls, scripts use modal alerts | Choose files, inspect the selection, then Upload. Show format/size/replacement guidance and progress. Report success/failure inline. Refresh the file list without losing the surrounding task. Let embedded lists fit their contents. |
| Librarian renames or removes a specific file | A failed rename can lose the proposed name; delete emphasis can be ambiguous | Keep failed input in a named dialog; show the filename and consequence. Cancel is the initial destructive focus. Preserve authenticated POST, CSRF, filename encoding and existing access checks. |
| Librarian edits tune notes and links | Polling guesses the dirty state; save completion is unclear | Input events track edits; Ctrl/Cmd+S validates and saves. Explicit Saved / Unsaved / Not saved states. Keep the draft on network, session and revision conflicts. |
| Librarian saves over a slow connection | Typing or choosing another file during a pending write can make the displayed state differ from what was saved | Capture the submitted values, mark the form busy and protect its fields until the confirmed result. Restore the controls and useful focus afterwards; keep failures editable. |
| Band leader builds a gig on a phone | Three stacked work areas require repeated long scrolling | Switch directly between gig list, setlist editor and library with the same live editor DOM. Adding from the library, moving with arrows, headings, repetition and Undo remain available. |
| Band leader pastes or revises a setlist | Dragging individual entries is slow for an existing list | Offer a text view alongside visual editing. One stored tune name or literal heading per line, blank lines for breaks. Both views edit the same ordered draft; save validation stays on the server. |
| Band leader leaves a changed gig | A discard-only prompt makes preserving work unnecessarily awkward | Offer Save and continue, Discard changes, or Keep editing. Invalid or failed saves block navigation and retain the draft. |
| Band leader chooses a gig PDF and then changes gigs | A selected file can disappear even though the setlist is already saved | Treat a pending PDF as unfinished work. Keep editing retains the File; Save / upload and continue completes it before navigation. |
| Administrator adds a temporary substitute | Expiry, section access and powerful presets need to remain understandable | Keep explicit section permissions and temporary access, add useful roster filtering and a concise rights summary. Privilege changes still require Save; preserve unknown site rights and self-lockout protection. |
| Administrator corrects an error or encounters a concurrent edit | A generic notice or rerender can obscure the relevant field or discard work | Focus an error summary, associate client validation with fields, retain server-conflict input and make retry/reload consequences explicit. Preserve revisions and backend checks. |

## Researched guidance and application

The following are primary sources. NADIN adopts the interaction principles; it does not import another product's component framework or visual branding.

- [W3C WCAG 2.2: target size](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum): compactness must still permit accurate operation. Use meaningful control dimensions and spacing rather than tiny pencil spans.
- [W3C: focus not obscured](https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum): keep focused fields and controls visible beside sticky editing controls.
- [W3C: dragging movements](https://www.w3.org/WAI/WCAG22/Understanding/dragging-movements.html): retain Add and movement buttons as alternatives to drag-and-drop.
- [WAI-ARIA APG: modal dialogs](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/): label the dialog, choose initial focus by task, support Escape and return focus. Prefer the native dialog element.
- [Carbon: dialogs](https://www.carbondesignsystem.com/building-blocks/core/patterns/dialogs): reserve interruption for a focused decision. Keep complex editing in its workspace. Validate before closing and retain failed input.
- [Carbon: forms](https://www.carbondesignsystem.com/building-blocks/core/patterns/forms): choose the editing context from the task, not from a desire to use a modal everywhere.
- [GOV.UK: error summaries](https://design-system.service.gov.uk/components/error-summary/): make validation discoverable and link the summary to the relevant fields.
- [GOV.UK: validation](https://design-system.service.gov.uk/patterns/validation/): explain what is wrong and how to correct it, without removing the user's entries. NADIN keeps its native field constraints as well as server validation.
- [GOV.UK: file upload](https://design-system.service.gov.uk/components/file-upload/): use a labelled native input, specific type/size guidance and clear errors. Avoid an ornamental drag-and-drop surface.
- [Apple: modality](https://developer.apple.com/design/human-interface-guidelines/modality): choose dedicated tasks deliberately; do not block the setlist for routine browsing.
- [forScore: setlists](https://forscore.co/documentation/setlists/) and [Backups / CSV metadata](https://forscore.co/documentation/backups/): setlist-only references and PDF-containing transfers serve different cases. Preserve the inventory workflow and distinguish a metadata index from the actual PDF library.
- [MDN: ResizeObserver](https://developer.mozilla.org/en-US/docs/Web/API/ResizeObserver): resize the existing same-origin embedded file lists when content changes, instead of polling or fixing a short height.

## Scope and evidence

V17 is a separate development line. Keep the established authentication/cache namespaces, source filenames, data formats, symlink installation support, optional federation and all inherited musical workflows. There is no data migration and no additional external font, analytics or UI dependency.

Verification is recorded in `V17-VERIFICATION.md`: actual PHP/HTTP operations, browser scenarios and their limits. Automated accessibility checks are evidence for those checks, not a blanket WCAG certification or a substitute for assistive-technology/user testing.

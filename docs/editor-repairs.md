# Editor and alphabet repairs — 31 October 2026

The gig editor displays free-text headings and breaks as plain, unnumbered entries. Only library tunes receive consecutive playing numbers; repeated tunes each receive a number. Headings do not affect the tune count and have no warning or “unavailable tune” label.

Moving, adding, removing, saving and undoing entries recalculates the displayed tune numbers without changing the stored headings, tune IDs or their order. The same display rule applies to current, archived and deleted gigs. The setlist uses an unordered HTML list so browsers do not add automatic heading numbers.

The heading regression is present in V11–V16 and is corrected in each of those existing branches. V8–V10 retain their earlier editor and are unaffected. The public gig and print views already leave headings unnumbered.

The library column in the gig editor now highlights the row on hover and keyboard focus. Tunes can be dragged from that column into an empty setlist, before or after an existing entry, including headings. The insertion line shows the destination. Repetitions are allowed; Undo restores the previous list. Archived and deleted gigs remain read-only. The + button remains available for keyboard and touch use.

Historical user rows are converted automatically into editable form values. Supported expiry dates include unpadded Swiss dates, optional spacing and ISO dates. UTF-8 BOMs and single-byte names are handled; instruction/comment lines are preserved without an error message. Loading does not rewrite the source or change current authentication stamps. Saving normalises the edited row with an exact safety copy and retains extension permissions. An account with a genuinely invalid expiry date remains visible and inactive, with a required date field; no access expiry is guessed.

The library alphabet uses a compact horizontal strip with consistent letter sizes, hover/focus states and a selected-letter marker. It wraps on narrow screens without sideways scrolling. Alphabetical/newest sorting and mobile tune menus are retained. This alphabet change is applied to all V8–V16 branches; the editor repairs apply wherever the new editor exists (V11–V16).

Regression coverage is in `tests/admin-dom.cjs`, `tests/legacy-users.php` and `tests/ui-browser.cjs`. Generate fixtures with `tests/render-preview.py`, then run the DOM tests as described in the branch's administration documentation. The Chromium suite additionally requires Playwright and an installed Chromium browser (`NADIN_CHROMIUM` can select it):

```sh
php tests/legacy-users.php
node tests/admin-dom.cjs /path/to/preview/views
node tests/ui-browser.cjs /path/to/preview/views
```

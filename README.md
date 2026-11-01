# NADIN V17 — coherent interface and task workflows

V17 brings the music library, standalone editing windows, gig management and member administration into one compact interface. Its design follows the actual work: find and play music, add or correct files, build a setlist, give a substitute access, and recover from an error without losing the draft.

**Branch:** `V17-unified-interface-and-workflows`

**Starting point:** V16 at `ab802d7`, including compact ZIP links and the guided forScore workflow
**Status:** Separate development line. It does not replace a released classic-interface package.

## What changes

- A shared task frame for scores, recordings, attachments, tune notes, tune actions, access details and previews. File selection is followed by an explicit Upload action. Confirmed results and recoverable errors stay beside the task.
- Focused dialogs with named actions, keyboard focus, Escape and retained input after a failed operation. Notes and administration offer Save and continue; pending gig PDFs are protected too.
- Gig management keeps three working areas on a wide screen and switches directly between them on a phone. Visual and text setlist views share the same ordered draft, including headings, breaks and repeated tunes. Add buttons and movement arrows remain alternatives to dragging.
- People management adds roster filters, a concise rights summary and quieter account actions. Initial passwords are revealed individually; personal passwords remain private.

The familiar music rows, configurable GigRep position, compact wrapping ZIP row, section permissions, source order and forScore identities remain. V17 adds no external UI framework, font or analytics dependency and requires no data migration.

See the [design decisions, actual use cases and primary research](docs/V17-DESIGN.md) and the [executed checks and test limits](docs/V17-VERIFICATION.md).

## Project

NADIN is a file-based PHP application for big bands. It manages sheet music, recordings, member permissions and current/archived gig setlists without a database. It was developed by Urs Petermann and Stefan Minder for the Uptown Big Band Bern. Any warranty and liability is excluded. Project information: https://yuba.ch/nadin.

## Branches and releases

See [the complete branch map](docs/BRANCHES.md) for the stages, historical numbering, integration status and current release. [NADIN 7.7.3](https://github.com/stemind/NADIN/releases/tag/v7.7.3) is the current classic-interface release with guided forScore export.

## Installation and verification

Use this line's complete source files together and preserve your configuration, salt, member/authentication files, music, Reps, attachments and hidden forScore metadata. The new shared PHP/JavaScript helpers are required alongside the updated controllers. Optional federation uses `composer install` with the committed lock file; ordinary local login does not need those optional packages. Keep the installation's configured data permissions.

V17 retains V16's PHP compatibility preparation. Native PHP 9 compatibility is not claimed. See [V17 verification](docs/V17-VERIFICATION.md) for the runtimes and features actually exercised, [the earlier evolution audit](docs/EVOLUTION-AUDIT.md) for the inherited corrections, and [7.7.3 notes](docs/7.7.3.md) for classic-interface patch packages.

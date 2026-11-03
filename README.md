# NADIN V18 — automatic audio upload conversion

V18 adds optional audio conversion to the V17 interface and task workflows. In **Recordings**, upload an MP3 unchanged or choose WAV, AIFF, M4A/AAC, FLAC, Ogg/Opus or WMA and let the server convert it to high-quality MP3. Without a working converter, the established MP3 upload remains available.

**Branch:** `V18-audio-upload-conversion`
**Starting point:** V17 at `324cbea70556eead3ca4414f7f94c8fac36415b9`, including the guided forScore workflow and intentional absence of a forScore link in the media Library.
**Status:** Separate development line. It does not replace V17 or the released classic 7.7 package.

## V18 audio uploads

- Existing MP3s keep their exact bytes. Other supported formats use optional server-side FFmpeg with the LAME MP3 encoder. No external conversion service receives files.
- Explicit upload, visible conversion feedback, retained selection after errors and MP3-only fallback when conversion is unavailable.
- A recording is replaced only after successful conversion and staging; its previous bytes remain recoverable. Runtime-annotated filenames are recognised, and ambiguous duplicates are reported instead of guessed.
- Conversion has time, duration and size limits. It does not hold the global media-write lock while encoding. Existing playback and audio ZIPs continue to use MP3.

See [V18 audio setup, behaviour and verification](docs/V18-AUDIO.md). Existing installations keep their configuration and data; missing optional settings use defaults. No data migration is required.

## Inherited V17 interface

The shared task frame, explicit file uploads, focused dialogs, draft protection, mobile gig/member workspaces and visual/text setlist editing remain. Music rows, configurable GigRep position, compact wrapping ZIP links, permissions, source order and forScore identities are retained. See the [V17 design decisions](docs/V17-DESIGN.md) and [historical V17 verification](docs/V17-VERIFICATION.md).

## Project

NADIN is a file-based PHP application for big bands. It manages sheet music, recordings, member permissions and current/archived gig setlists without a database. It was developed by Urs Petermann and Stefan Minder for the Uptown Big Band Bern. Any warranty and liability is excluded. Project information: https://yuba.ch/nadin.

## Branches and releases

See [the complete branch map](docs/BRANCHES.md) for the stages, historical numbering, integration status and current release. [NADIN 7.7](https://github.com/stemind/NADIN/releases/tag/v7.7) is the current classic-interface release with guided forScore export.

## Installation and verification

Use this line's complete source files together and preserve your configuration, salt, member/authentication files, music, Reps, attachments and hidden forScore metadata. The new shared PHP/JavaScript helpers are required alongside the updated controllers. Optional federation uses `composer install` with the committed lock file; ordinary local login does not need those optional packages. Keep the installation's configured data permissions.

V17 retains V16's PHP compatibility preparation. Native PHP 9 compatibility is not claimed. See [V17 verification](docs/V17-VERIFICATION.md) for the runtimes and features actually exercised, [the earlier evolution audit](docs/EVOLUTION-AUDIT.md) for the inherited corrections, and [7.7 notes](docs/7.7.md) for the cumulative classic-interface update.

GigRep navigation defaults to the left vertical column in all branches. Only the literal value `$rendergiglistontheleft='no';` puts it above the tunes; missing, empty and other values keep the default.

## Current evolution verification

See [the 2 November 2026 audit](docs/EVOLUTION-AUDIT-2026-11-02.md) for the 14-branch review, three additional investigation rounds and all resulting corrections.

The [follow-up evolution check of 2 November 2026](docs/EVOLUTION-RECHECK-2026-11-02.md) verifies all fourteen current branch snapshots and documents the additional gig-state, catalogue and audio-duration repairs.

The [repair countercheck of 3 November 2026](docs/REPAIR-COUNTERCHECK-2026-11-03.md) challenges the repairs themselves, corrects three reproduced side effects and records the final 305-suite matrix plus both classic installation paths.

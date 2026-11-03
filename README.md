# NADIN — Compact gig and user management

Structured setlist and member administration, editable unnumbered headings, tune ordering, library drag/drop, hover feedback, safe legacy-member conversion and preserved extension rights.

**Branch:** `V11-gig-user-management`
**Starting point:** V10
**Status:** Development line; shared collateral corrections are backported without taking over the later visual stages.

## Project

NADIN is a file-based PHP application for big bands. It manages sheet music, recordings, member permissions and current/archived gig setlists without a database. It was developed by Urs Petermann and Stefan Minder for the Uptown Big Band Bern. Any warranty and liability is excluded. Project information: https://yuba.ch/nadin.

## Branches and releases

See [the complete branch map](docs/BRANCHES.md) for the stages, historical numbering, integration status and current release. [NADIN 7.7](https://github.com/stemind/NADIN/releases/tag/v7.7) is the current classic-interface release with guided forScore export.

## Installation and verification

Use the selected line's source files together and preserve your configuration, salt, member/authentication files, music, Reps, attachments and hidden forScore metadata. See [the executed evolution audit](docs/EVOLUTION-AUDIT.md) for the checks and corrected collateral issues, and [7.7 notes](docs/7.7.md) for the cumulative 7.6→7.7 update.

GigRep navigation defaults to the left vertical column in all branches. Only the literal value `$rendergiglistontheleft='no';` puts it above the tunes; missing, empty and other values keep the default.

## Current evolution verification

See [the 2 November 2026 audit](docs/EVOLUTION-AUDIT-2026-11-02.md) for the 14-branch review, three additional investigation rounds and all resulting corrections.

The [follow-up evolution check of 2 November 2026](docs/EVOLUTION-RECHECK-2026-11-02.md) verifies all fourteen current branch snapshots and documents the additional gig-state, catalogue and audio-duration repairs.

The [repair countercheck of 3 November 2026](docs/REPAIR-COUNTERCHECK-2026-11-03.md) challenges the repairs themselves, corrects three reproduced side effects and records the final 305-suite matrix plus both classic installation paths.

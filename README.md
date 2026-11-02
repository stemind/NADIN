# NADIN 7.7 — classic interface with guided forScore export

The classic maintenance line, retaining the CSS-based GigRep layout and adding a remembered section, automatic updates, a concise download summary and NADIN’s own favicon on the forScore page.

**Branch:** `7.7.2-css-gigrep-layout`
**Starting point:** 7.6
**Status:** Consolidated classic release with one cumulative 7.6→7.7 update. Includes the previous patch corrections.

## Project

NADIN is a file-based PHP application for big bands. It manages sheet music, recordings, member permissions and current/archived gig setlists without a database. It was developed by Urs Petermann and Stefan Minder for the Uptown Big Band Bern. Any warranty and liability is excluded. Project information: https://yuba.ch/nadin.

## Branches and releases

See [the complete branch map](docs/BRANCHES.md) for the stages, historical numbering, integration status and current release. [NADIN 7.7](https://github.com/stemind/NADIN/releases/tag/v7.7) is the current classic-interface release with guided forScore export.

## Installation and verification

Use the selected line's source files together and preserve your configuration, salt, member/authentication files, music, Reps, attachments and hidden forScore metadata. See [the executed evolution audit](docs/EVOLUTION-AUDIT.md) for the checks and corrected collateral issues, and [7.7 notes](docs/7.7.md) for the cumulative 7.6→7.7 update.

GigRep navigation defaults to the left vertical column in all branches. Only the literal value `$rendergiglistontheleft='no';` puts it above the tunes; missing, empty and other values keep the default.

## Current evolution verification

See [the 2 November 2026 audit](docs/EVOLUTION-AUDIT-2026-11-02.md) for the 14-branch review, three additional investigation rounds and all resulting corrections.

# NADIN — PHP 8.5 validation and PHP 9 preparation

PHP 8.5 compatibility checks, strict future-error simulation and the aligned standalone popup design. Native PHP 9 compatibility is not claimed; optional federation dependencies require their own supported-runtime validation.

**Branch:** `V16-php85-php9-preparation`  
**Starting point:** V15  
**Status:** Development line; shared collateral corrections are backported without taking over the later visual stages.

## Project

NADIN is a file-based PHP application for big bands. It manages sheet music, recordings, member permissions and current/archived gig setlists without a database. It was developed by Urs Petermann and Stefan Minder for the Uptown Big Band Bern. Any warranty and liability is excluded. Project information: https://yuba.ch/nadin.

## Branches and releases

See [the complete branch map](docs/BRANCHES.md) for the stages, historical numbering, integration status and current release. [NADIN 7.7.4](https://github.com/stemind/NADIN/releases/tag/v7.7.4) is the current classic-interface release with guided forScore export.

## Installation and verification

Use the selected line's source files together and preserve your configuration, salt, member/authentication files, music, Reps, attachments and hidden forScore metadata. Optional federation in this line requires `composer install` using the committed lock file. See [the executed evolution audit](docs/EVOLUTION-AUDIT.md) for the checks and corrected collateral issues, and [7.7.4 notes](docs/7.7.4.md) for the current patch packages.

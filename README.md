# NADIN — Compact gig and user management

Adds structured gig/setlist and people administration, with compact controls and explicit permissions.

**Branch:** `V11-gig-user-management`

Unnumbered editable headings, tune ordering, library drag/drop and hover feedback, legacy-member conversion and preserved extension rights.

## Project

NADIN is a file-based PHP application for big bands. It manages sheet music, recordings, member permissions and current/archived gig setlists without a database. It was developed by Urs Petermann and Stefan Minder for the Uptown Big Band Bern. Any warranty and liability is excluded. Project information: https://yuba.ch/nadin.

## Branches

The numbered development lines are successive stages; later lines retain the earlier application capabilities, apart from the agreed retirement of iTunes/RSS at V10. Shared forScore, encoding and interaction corrections have also been backported where applicable. The 7.7 line deliberately keeps the original interface.

| Line | Branch | Main purpose |
| --- | --- | --- |
| main | [`main`](https://github.com/stemind/NADIN/tree/main) | Maintained 7.x baseline |
| 7.7 | [`7.7-original-interface-forScore`](https://github.com/stemind/NADIN/tree/7.7-original-interface-forScore) | Original interface with forScore |
| V8 | [`V8-compact-interface`](https://github.com/stemind/NADIN/tree/V8-compact-interface) | Compact original interface |
| V9 | [`V9-forScore-export`](https://github.com/stemind/NADIN/tree/V9-forScore-export) | forScore export |
| V10 | [`V10-personal-passwords`](https://github.com/stemind/NADIN/tree/V10-personal-passwords) | Personal passwords and onboarding |
| V11 | [`V11-gig-user-management`](https://github.com/stemind/NADIN/tree/V11-gig-user-management) | Compact gig and user management |
| V12 | [`V12-login-protection`](https://github.com/stemind/NADIN/tree/V12-login-protection) | Progressive login protection |
| V13 | [`V13-zip-download-layout`](https://github.com/stemind/NADIN/tree/V13-zip-download-layout) | Readable ZIP downloads |
| V14 | [`V14-refined-interface`](https://github.com/stemind/NADIN/tree/V14-refined-interface) | Refined compact interface |
| V15 | [`V15-oidc-saml-reliable-zips`](https://github.com/stemind/NADIN/tree/V15-oidc-saml-reliable-zips) | Optional federation and reliable ZIPs |
| V16 | [`V16-php85-php9-preparation`](https://github.com/stemind/NADIN/tree/V16-php85-php9-preparation) | PHP 8.5 validation and PHP 9 preparation |

## Installation and updates

Use the source files for the selected line together. Preserve your installation configuration, user/member and authentication files, music/gig directories and hidden forScore identity metadata. The application retains the file-based storage model. Existing technical notes and verification tools are in `docs/` and `tests/` where present.

The current conservative 7.x forScore release is [NADIN 7.7.2](https://github.com/stemind/NADIN/releases/tag/v7.7.2), with separate correction and 7.6-upgrade ZIPs. First forScore imports need no CSV; later PDF-containing imports use a current Filename inventory to reuse annotated PDFs. Native forScore/iPad acceptance is separate from the PHP/HTTP regression checks.

The forScore Library link is removed. Changing the section/import option or choosing a CSV updates the export automatically. `rendergiglistontheleft` controls the current and archived GigRep list position. See `docs/2026-10-31-maintenance.md` for the Main comparison and test boundaries.

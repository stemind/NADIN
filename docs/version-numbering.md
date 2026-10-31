# Consecutive version numbering

On 31 October 2026, the missing V10 number was closed by renaming the branches below. Commit history is retained. Historical commit messages and documentation in historical commits use their original version labels. The current documentation uses the new numbering.

| Former branch | New branch | Scope | Original head before final-review fixes |
| --- | --- | --- | --- |
| V11 | V10 | Personal passwords and simple onboarding | `e218272649bc7fbb62bdcdfcaf77e5edc0d904da` |
| V12 | V11 | Compact administration and permissions | `dc8a2d836b0e79735dda3bcebf56685a5c8102f8` |
| V13 | V12 | Brute-force protection | `7c5e713dbd16514e5d4618e9f9c1639d895a0bce` |
| V14 | V13 | ZIP presentation | `9391348ec0d25317d424e0315b8e5403579323f0` |
| V15 | V14 | Compact look and feel | `2bf573bea99ef03d97152ce1f486be14e3ecc27d` |
| V16 | V15 | Optional federation, reliable ZIPs, SVG logo and final review | `4f6f63a85015f8f0360f9c62087c4cd32c42c086` |

V8 and V9 keep their names. The new [V16](V16.md) is the separate PHP compatibility preparation release, based on the reviewed V15.

Internal storage format numbers, authentication filenames (`auth-v11.php` and its lock), CSRF scope identifiers, cache directories and existing regression-test filenames are stable compatibility identifiers. They are **not** release branch names and are not renumbered. No passwords, sessions, music files or data formats are migrated.

Existing local clones need their local branch names and upstreams updated; a fetch alone does not rename a local branch. Use the mapping above and check the commit before changing an upstream. The immutable SHAs disambiguate older links because several branch names are being reused.

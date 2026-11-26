# MP3 playback and interrupted downloads

## Quick update

For the classic NADIN 7.7 release, back up and replace **both** `get/file.php` and the new `get/stream.inc.php` from the same corrected classic source. They must be installed together. The three-file classic patch also updates `login.php` to display **7.7.1**. Preserve installation configuration, accounts, recordings and all other installation data.

Each maintained branch has its own corrected `get/file.php`, preserving that branch's authentication and permissions. When updating a development branch, use its own source files; do not install the classic download controller into a later authentication stage.

## Problem and correction

The former controller ignored an HTTP `Range` request and returned the complete MP3 with status 200. A request to continue at byte 960000 therefore received the beginning again. This was reproduced against the released classic source: 30 of the original 63 transport checks failed.

The corrected controller completes its existing login and permission checks, then delegates MP3 delivery to the shared helper. It supports bounded, open-ended and suffix byte ranges, returns status 206 with exact `Content-Range` and `Content-Length` values, and rejects unsatisfiable ranges with an empty 416 response. `HEAD` returns full metadata without a body. Malformed and multiple ranges are ignored and receive the complete representation. An unverifiable `If-Range` condition also receives the complete representation; no unreliable strong validator is invented for installation-owned files.

The helper opens the recording once, measures that handle and streams bounded chunks. It disables PHP output compression and removable output buffers so byte positions describe the original recording. It releases an active PHP session before streaming, requests unbuffered delivery from nginx and removes the PHP execution limit for this authorised media response. Uppercase MP3 extensions are recognised. Other file types keep the existing delivery path and all branch-specific headers.

This repairs the demonstrated resume defect. It does **not** establish the source of a particular server's one-minute interruption. PHP-FPM termination limits, reverse-proxy limits, connection failures and client-specific behaviour still need checking if playback continues to fail after updating. The helper cannot override a hosting provider's hard request limit.

## Verification

- **72 real HTTP checks on each of the 14 maintained branches: 1,008 passed.** Disposable installations use the actual branch login and download controller under a nested URL, PHP output buffering and compression enabled, and synthetic accounts and files. Coverage includes exact full/partial bytes, open-ended/suffix/oversized/mixed-case ranges, invalid and multiple ranges, `If-Range`, `HEAD`, zero-byte files, a deliberately aborted client transfer, uppercase extensions, ordinary PDFs, anonymous/expired users and hidden/section permissions.
- Every branch also delivers an actual generated **130-second MP3**, resumes beyond its one-minute byte position and reconstructs the original bytes. Independent FFprobe confirms the full reconstructed duration.
- The classic source passes **47 Main parity checks, 55 media contracts, 108 forScore HTTP checks and 62 forScore regression checks**. These are separate from the new streaming checks.
- Changed PHP files pass syntax validation on PHP **8.4.27**. This is not a native Windows, PHP 7.4, Safari or iOS result.

Run the portable HTTP suite with `NADIN_PHP=/path/to/php python3 tests/audio-stream-http.py`. FFmpeg and FFprobe enable the three long-MP3 checks; without them, the suite runs 69 transport checks. `NADIN_APP_ROOT` selects another branch checkout. Tests use temporary synthetic installations and send no mail.

An optional local Chromium fixture is supplied as `tests/audio-stream-browser.cjs`; enable it with `NADIN_STREAM_BROWSER=1`, with Node Playwright and `NADIN_CHROMIUM` pointing to a runnable Chromium. It checks continuous playback beyond 65 seconds, forward/backward seeking and a truncated response. **That local browser run could not start in this execution environment and is not counted as passed.**

Protocol reference: [RFC 9110, section 14](https://www.rfc-editor.org/rfc/rfc9110.html#name-range-requests).

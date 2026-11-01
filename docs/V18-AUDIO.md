# V18 — automatic audio upload conversion

V18 starts from `V17-unified-interface-and-workflows` at commit `324cbea70556eead3ca4414f7f94c8fac36415b9`. Its branch is `V18-audio-upload-conversion`. It adds optional audio conversion in the existing **Recordings** upload task. The V17 interface, permissions and other workflows remain the starting point. Existing branches and the classic 7.7 release are not changed by V18.

## Upload behaviour

- Choose one recording, then press **Upload files**. Choosing a file alone never uploads it.
- MP3 uploads keep their original bytes. Their filename extension is normalised to `.mp3`.
- With a working converter, WAV, AIF/AIFF, M4A/AAC, FLAC, OGG/OGA, OPUS and WMA uploads become MP3. Conversion uses LAME variable bitrate quality 2; no volume normalisation, tempo change or trimming is applied. Ordinary title metadata is carried forward when supported by the source format. No lossless quality claim is made for MP3 output.
- The resulting MP3 uses NADIN's existing filename cleanup, including duration-name cleanup. Recommended-recording markers remain in the name. The existing player, recording list and recommended-audio ZIPs continue to use MP3.
- Uploads announce **Uploading and converting audio to MP3…** while pending. Errors retain the selected file and restore the controls for retry. Success refreshes the recording list. The native form also works without JavaScript.
- Conversion is bounded to 100 MB of source audio, 30 minutes of decoded audio, 90 seconds of converter runtime and 100 MB of output. Existing PHP upload/request limits can be lower. Exceeding a limit fails the upload; a recording is never silently shortened. MP3 passthrough continues to use the ordinary PHP upload limits.
- The old recording is replaced only after conversion and staging succeed. Its previous bytes remain in NADIN's existing `zzDEL!` recovery convention. A conversion failure or deadline leaves it untouched. Different source extensions with the same cleaned basename deliberately target the same MP3. An existing runtime annotation such as `Practice.(03.20).mp3` is recognised when uploading `Practice.wav` again. Multiple matching pre-existing recordings cause a clear conflict instead of choosing one. Duration annotation refreshes the file list without reloading the editor or discarding a selected upload.

The generic **Scores & files** page retains its existing behaviour; conversion belongs to **Recordings**. forScore is unchanged and has no media-Library link. This feature does not add MP3s to forScore exports.

## Server setup and fallback

Install FFmpeg **with the `libmp3lame` encoder** using the host's normal package management. PHP must allow `proc_open`, `proc_get_status`, `proc_terminate` and `proc_close`. Check this using the same PHP/FPM account that serves NADIN, not only an administrator's shell. Temporary storage and the existing writable music directories must have sufficient free space. Match the web server/FPM/proxy timeouts to upload time plus the chosen conversion limit.

Optional settings in the existing installation's `config.inc.php` are:

```php
$audioffmpeg='auto'; // /usr/bin/ffmpeg, /usr/local/bin/ffmpeg, then PATH
$audioconversiontimeout=90; // 1–90 seconds for the converter
$audiomaxseconds=1800; // 1–1800 seconds of audio
```

An absolute executable path can replace `auto`. Set `$audioffmpeg=''` to disable conversion. Missing settings use the defaults above, so an existing installation does not need its configuration replaced. Do not copy the repository example over an installation's configuration or salt.

NADIN tests the actual encoder capability. A missing executable, unavailable process functions or missing MP3 encoder results in an MP3-only upload picker with a clear explanation. Existing MP3 uploads remain usable. The default does not install programs or change hosting permissions. No external conversion service receives audio files.

## File and process handling

The converter receives an argument array, without a shell. User filenames are not executable command text. Input is the uploaded temporary local file, using a forced demuxer from a fixed extension allowlist. Network protocols, playlists, video inputs and MOV external data references are not enabled. MP3 output is prepared in private temporary storage before it is staged and committed to the Library.

The slow conversion runs outside the existing global media-write lock. The lock is reacquired before committing, and the destination is checked again. Conversion errors and deadlines clean temporary output; a failed conversion never causes the previous recording to be moved to recovery. Existing authentication, `upaudio`, POST and CSRF checks still apply before conversion.

## Verification

`tests/audio-upload-http.py` generates real source audio with FFmpeg and performs authenticated uploads on a disposable installation. It checks every advertised extension, actual MP3 codec/duration, metadata, exact MP3 passthrough, replacement/recovery, damaged and disguised files, rejected playlists, shell characters in filenames, permissions/CSRF, direct listening downloads, recommended-audio ZIPs, native forms, duration limits, missing/disabled converters, a converter deadline, executable paths containing spaces, duration-annotated replacement, ambiguity protection, private temporary-file cleanup and release/reacquisition of the global media-write lock during conversion. It also restarts the test server with `proc_open` disabled and verifies honest fallback and working MP3 uploads.

With `NADIN_AUDIO_BROWSER=1`, `tests/audio-upload-browser.cjs` adds real Chromium checks for explicit submission, pending conversion feedback, duplicate-submit protection, confirmed list refresh, preserved files/selection after errors, successful retry, 1200/390/320 px layouts duration-list refresh without losing the active editor, and an automated accessibility scan. `NADIN_SCREENSHOTS` selects an output directory outside the installation.

```sh
NADIN_PHP=/path/to/php python3 tests/audio-upload-http.py
NADIN_AUDIO_BROWSER=1 NADIN_CHROMIUM=/path/to/chromium \
  NADIN_PHP=/path/to/php python3 tests/audio-upload-http.py
```

The tests require Python requests/BeautifulSoup, FFmpeg/ffprobe, and Node Playwright/axe-core for browser checks. Tests use synthetic local recordings and accounts, and send no email. Runtime support depends on the FFmpeg build and the actual hosting configuration; production-host conversion is not claimed without a host check.

### Executed checks for this branch

The V18 implementation was exercised with PHP 8.3.6, FFmpeg 6.1.1 with libmp3lame, and headless Chromium 153 on Linux:

| Suite | Result |
| --- | --- |
| New audio conversion and real HTTP uploads | 56 checks passed |
| New audio browser journeys | 21 checks passed, including accessibility |
| Inherited task HTTP suite | 34 checks passed |
| Inherited native browser journeys | 80 checks passed; 25 accessibility scans |
| Main behaviour parity | 41 checks passed |
| forScore exporter regression | 60 checks passed |
| forScore HTTP / software DOM / Chromium | 92 / 66 / 103 checks passed |
| ZIP integrity and concurrency | 32 checks passed |
| PHP compatibility preparation | 16 checks passed |

Changed PHP/JavaScript files pass syntax checks. The PHP 9 preparation suite is not a claim of testing a native PHP 9 runtime. The customer's hosting environment has not been changed or tested; its converter capability is detected at runtime.

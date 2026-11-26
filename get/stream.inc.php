<?php
/** Deliver an authorised MP3 with HTTP byte ranges (RFC 9110, section 14). */
function nadin_stream_mp3($file) {
    // The caller must finish authentication and all file permissions first.
    $stream = @fopen($file, 'rb');
    if ($stream === false) {
        http_response_code(404);
        exit('Audio file not found.');
    }
    $stat = fstat($stream);
    if ($stat === false || ($stat['mode'] & 0170000) !== 0100000) {
        fclose($stream);
        http_response_code(404);
        exit('Audio file not found.');
    }
    $size = $stat['size'];
    $start = 0;
    $end = $size - 1;
    $partial = false;
    $unsatisfiable = false;
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $range = $_SERVER['HTTP_RANGE'] ?? '';

    // There is no strong representation validator for installation-owned audio.
    // An If-Range condition therefore cannot match; return the full file.
    // Unsupported/malformed or multiple ranges are deliberately ignored.
    if ($method === 'GET' && !isset($_SERVER['HTTP_IF_RANGE']) &&
        preg_match('/^bytes=(\d*)-(\d*)$/iD', trim($range), $match) &&
        ($match[1] !== '' || $match[2] !== '')) {
        if ($match[1] === '') {
            $suffix = (int)$match[2];
            $unsatisfiable = $suffix === 0;
            $start = max(0, $size - $suffix);
            $partial = $size > 0;
        } else {
            $start = (int)$match[1];
            $requestedEnd = $match[2] === '' ? $end : (int)$match[2];
            // Compare decimal strings before casting: HTTP offsets can exceed
            // PHP_INT_MAX, and two different huge numbers must not compare equal.
            $firstDigits = ltrim($match[1], '0');
            $lastDigits = ltrim($match[2], '0');
            $ascending = strlen($lastDigits) > strlen($firstDigits) ||
                (strlen($lastDigits) === strlen($firstDigits) && strcmp($lastDigits, $firstDigits) >= 0);
            if ($match[2] === '' || $ascending) {
                $unsatisfiable = $start >= $size;
                $end = min($end, $requestedEnd);
                $partial = true;
            } else {
                $start = 0; // Invalid descending range: use the full response.
            }
        }
    }

    // Byte offsets and lengths describe the original, uncompressed MP3 bytes.
    @ini_set('zlib.output_compression', '0');
    while (ob_get_level() > 0) {
        if (!@ob_end_clean()) break;
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    if (function_exists('set_time_limit')) @set_time_limit(0);
    header('Content-Type: audio/mpeg');
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, no-store, no-transform');
    header('X-Accel-Buffering: no');

    if ($unsatisfiable) {
        http_response_code(416);
        header('Content-Range: bytes */'.$size);
        header('Content-Length: 0');
        fclose($stream);
        exit;
    }
    if ($start > 0 && fseek($stream, $start, SEEK_SET) !== 0) {
        fclose($stream);
        http_response_code(500);
        exit('Unable to seek in audio file.');
    }
    $remaining = $partial ? $end - $start + 1 : $size;
    http_response_code($partial ? 206 : 200);
    if ($partial) header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
    header('Content-Length: '.$remaining);
    if ($method !== 'HEAD') {
        while ($remaining > 0 && !connection_aborted()) {
            $chunk = fread($stream, min(65536, $remaining));
            if ($chunk === false || $chunk === '') break;
            echo $chunk;
            $remaining -= strlen($chunk);
            flush();
        }
    }
    fclose($stream);
    exit;
}

<?php
/** Text is UTF-8 at output boundaries; filesystem identifiers retain their original bytes. */
function nadinText($value) {
    $text = (string)$value;
    if (substr($text, 0, 3) === "\xEF\xBB\xBF") $text = substr($text, 3);
    if (preg_match('//u', $text)) return $text;
    // Preserve valid UTF-8 runs in old files that also contain Windows-1252 bytes.
    // No iconv/mbstring dependency, ignored bytes or locale-dependent conversion.
    $valid = '[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE-\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}';
    return preg_replace_callback('/'.$valid.'|[\x80-\xFF]/', function ($match) {
        if (strlen($match[0]) > 1) return $match[0];
        static $windows = array(0x80=>0x20AC,0x82=>0x201A,0x83=>0x0192,0x84=>0x201E,0x85=>0x2026,0x86=>0x2020,0x87=>0x2021,0x88=>0x02C6,0x89=>0x2030,0x8A=>0x0160,0x8B=>0x2039,0x8C=>0x0152,0x8E=>0x017D,0x91=>0x2018,0x92=>0x2019,0x93=>0x201C,0x94=>0x201D,0x95=>0x2022,0x96=>0x2013,0x97=>0x2014,0x98=>0x02DC,0x99=>0x2122,0x9A=>0x0161,0x9B=>0x203A,0x9C=>0x0153,0x9E=>0x017E,0x9F=>0x0178);
        $code = ord($match[0]); $code = isset($windows[$code]) ? $windows[$code] : $code;
        return $code < 0x800 ? chr(0xC0 | ($code >> 6)).chr(0x80 | ($code & 63)) : chr(0xE0 | ($code >> 12)).chr(0x80 | (($code >> 6) & 63)).chr(0x80 | ($code & 63));
    }, $text);
}
function nadinInitial($value) {
    preg_match('/^./us', nadinText($value), $match);
    return isset($match[0]) ? $match[0] : '';
}
/** Hidden form values use an explicit byte-preserving transport, never a guessed filename. */
function nadinPathField($name, $value) {
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="'.$name.'" value="'.rawurlencode($value).'">'.nadinPathMarker($name);
}
function nadinPathMarker($name) {
    return '<input type="hidden" name="_nadin_path_encoding['.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').']" value="url">';
}
function nadinRestorePathFields() {
    if (!isset($_POST['_nadin_path_encoding'])) return;
    $routes = array('uploadsave.php'=>array('foldername'),'PDFsendfile2.php'=>array('name'),'AUDsendfile2.php'=>array('name'),'ATTsendfile2.php'=>array('name'),'deletefolder2.php'=>array('tune'),'settime.php'=>array('p','f'),'AUDrenamefile.php'=>array('p','f'),'AUDdeletefile.php'=>array('p','f'),'PDFrenamefile.php'=>array('p','f'),'PDFdeletefile.php'=>array('p','f'),'ATTdeletefile.php'=>array('p','f'),'savegig.php'=>array('gig'));
    $script = basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
    $allowed = isset($routes[$script]) ? $routes[$script] : array();
    $markers = $_POST['_nadin_path_encoding'];
    if (!is_array($markers)) { http_response_code(400); exit('Invalid filename encoding.'); }
    foreach ($markers as $name => $encoding) {
        if (!in_array($name, $allowed, true) || $encoding !== 'url' || !isset($_POST[$name]) || !is_string($_POST[$name])) { http_response_code(400); exit('Invalid filename encoding.'); }
        $raw = rawurldecode($_POST[$name]);
        if ($raw !== '' && ($raw[0] === '.' || basename($raw) !== $raw || preg_match('~[\\\\\x00-\x1f\x7f]~', $raw))) { http_response_code(400); exit('Invalid filename.'); }
        $_POST[$name] = $raw;
    }
    unset($_POST['_nadin_path_encoding']);
}
/** Resolve edited legacy setlist lines without renaming their source directories. */
function nadinSetlistBytes($text, $previous, $library) {
    $names = array();
    foreach (array_merge(preg_split('/\r\n|\n|\r/', $previous), array_map('basename', (array)glob($library.'/*', GLOB_ONLYDIR))) as $raw) {
        $raw = trim($raw); if ($raw !== '') $names[nadinText($raw)][$raw] = true;
    }
    $lines = preg_split('/(\r\n|\n|\r)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($lines as $i => $line) if ($i % 2 === 0 && isset($names[trim($line)])) {
        $matches = array_keys($names[trim($line)]);
        if (count($matches) !== 1) throw new DomainException('Two tune names have the same displayed spelling. Resolve the collision before saving.');
        $lines[$i] = $matches[0];
    }
    return implode('', $lines);
}

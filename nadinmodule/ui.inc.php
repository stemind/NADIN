<?php
// Shared helpers for the existing, file-based NADIN interface.
require_once __DIR__.'/encoding.inc.php';
function nadinHtml($value) {
    return htmlspecialchars(nadinText($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function nadinRepFiles($directory, $descending = false) {
    $files = array();
    foreach ((array)glob($directory . '/*.txt') as $file) {
        if (is_file($file) && !is_link($file)) $files[] = basename($file);
    }
    sort($files, SORT_STRING);
    return $descending ? array_reverse($files) : $files;
}
function nadinReadRep($file, $separator) {
    $text = (string)file_get_contents($file);
    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
    $text = str_replace(array("\r\n", "\r"), "\n", $text);
    $parts = explode($separator, $text, 2);
    return array($parts[0], isset($parts[1]) ? explode("\n", trim($parts[1], "\n")) : array());
}
function nadinSidebarWidth() {
    global $ifleftthenthiswidth;
    $width = isset($ifleftthenthiswidth) ? (int)$ifleftthenthiswidth : 300;
    return max(120, min(450, $width));
}

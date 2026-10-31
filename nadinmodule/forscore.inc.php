<?php
// Helpers are loaded only by the optional forScore exporter.
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

function nadinHtml($value) {
    return htmlspecialchars(nadinText($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function nadinReadRep($file, $separator) {
    $text = (string)file_get_contents($file);
    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
    $text = str_replace(array("\r\n", "\r"), "\n", $text);
    $parts = explode($separator, $text, 2);
    return array($parts[0], isset($parts[1]) ? explode("\n", trim($parts[1], "\n")) : array());
}


// forScore Open Setlist: https://forscore.co/developers-file-types/
// PDFs are embedded only after an explicit initial import or a current inventory.
class NadinForScore {
    private $root;
    private $separator;
    private $profiles;
    private $rights;

    public function __construct($root, $separator, array $profiles, array $rights) {
        $this->root = realpath($root);
        if (!$this->root) throw new RuntimeException('NADIN directory not found.');
        $this->separator = $separator;
        $this->profiles = $profiles;
        $this->rights = $rights;
    }

    public function allowedProfiles() {
        $allowed = array();
        foreach ($this->profiles as $key => $profile) {
            if (!empty($this->rights[$profile['right']])) $allowed[$key] = $profile;
        }
        return $allowed;
    }

    private function component($name) {
        return is_string($name) && $name !== '' && $name[0] !== '.' && !preg_match('~[\\\\/\x00-\x1f]~', $name);
    }

    public function tunes($module, $rep) {
        if (empty($this->rights['vgigs'])) throw new RuntimeException('No permission to export notes.');
        if ($module === 'library') {
            if (empty($this->rights['vlibrary'])) throw new RuntimeException('No library permission.');
            $tunes = array();
            foreach ((array)glob($this->root . '/library/*', GLOB_ONLYDIR) as $dir) {
                if (!is_link($dir) && $this->component(basename($dir))) $tunes[] = basename($dir);
            }
            sort($tunes, SORT_STRING);
            return $tunes;
        }
        if (!in_array($module, array('gigreps', 'gigrepsarchiv'), true)) throw new RuntimeException('Invalid source.');
        if ($module === 'gigrepsarchiv' && empty($this->rights['varchiv'])) throw new RuntimeException('No archive permission.');
        if (!$this->component($rep) || substr($rep, -4) !== '.txt') throw new RuntimeException('Invalid GigRep.');
        $path = $this->root . '/' . $module . '/' . $rep;
        if (!is_file($path) || is_link($path)) throw new RuntimeException('GigRep not found.');
        list($info, $tunes) = nadinReadRep($path, $this->separator);
        return array_map('trim', $tunes);
    }

    private function type($filename) {
        // Match the configured NADIN markers and enforce every applicable right.
        $matches = array();
        foreach ($this->profiles as $key => $profile) {
            if ($profile['code'] !== '' && strpos($filename, $profile['code']) !== false) {
                if (empty($this->rights[$profile['right']])) return null;
                $matches[] = $key;
            }
        }
        if (count($matches) !== 1) return null;
        return $matches[0];
    }

    private function registry($directory, array $files) {
        $path = $directory . '/.nadin-forscore.json';
        $lockPath = $directory . '/.nadin-forscore.lock';
        $temporary = null;
        if (is_link($path) || is_link($lockPath)) throw new RuntimeException('Unsafe forScore identity file.');
        $handle = @fopen($lockPath, 'c+');
        if (!$handle || !flock($handle, LOCK_EX)) {
            if ($handle) fclose($handle);
            throw new RuntimeException('Cannot store stable forScore identities in the tune folder.');
        }
        try {
            $raw = is_file($path) ? file_get_contents($path) : '';
            if ($raw === false) throw new RuntimeException('Cannot read forScore identities.');
            $data = $raw === '' ? array('version' => 1, 'directory' => base64_encode(basename($directory)), 'files' => array()) : json_decode($raw, true);
            if (!is_array($data) || !isset($data['version'], $data['files'], $data['directory']) || $data['version'] !== 1 || !is_array($data['files'])) throw new RuntimeException('Invalid forScore identity file; restore it rather than creating new identities.');
            // A copied tune folder is a new arrangement; a renamed folder retains identities.
            if ($data['directory'] !== base64_encode(basename($directory)) && $this->component(base64_decode($data['directory'], true)) && is_dir(dirname($directory) . '/' . base64_decode($data['directory'], true))) $data['files'] = array();
            $data['directory'] = base64_encode(basename($directory));
            $result = array();
            foreach ($files as $file => $type) {
                $source = $directory . '/' . $file;
                $fileKey = base64_encode($file);
                $hash = hash_file('sha256', $source);
                if ($hash === false) throw new RuntimeException('Cannot read PDF: ' . nadinText($file));
                if (!isset($data['files'][$fileKey])) {
                    $renames = array();
                    foreach ($data['files'] as $old => $entry) {
                        if ($this->component(base64_decode($old, true)) && !file_exists($directory . '/' . base64_decode($old, true)) && isset($entry['hash']) && $entry['hash'] === $hash) $renames[] = $old;
                    }
                    if (count($renames) === 1) {
                        $data['files'][$fileKey] = $data['files'][$renames[0]];
                        unset($data['files'][$renames[0]]);
                    } else {
                        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', pathinfo($file, PATHINFO_FILENAME));
                        $data['files'][$fileKey] = array('id' => bin2hex(random_bytes(16)), 'slug' => substr($slug, 0, 90));
                    }
                }
                $entry = $data['files'][$fileKey];
                if (!isset($entry['id'], $entry['slug']) || !preg_match('/^[a-f0-9]{32}$/', $entry['id']) || !preg_match('/^[A-Za-z0-9_-]{0,90}$/', $entry['slug'])) throw new RuntimeException('Invalid forScore file identity.');
                $entry['hash'] = $hash;
                $data['files'][$fileKey] = $entry;
                $result[] = array('source' => $source, 'file' => $file, 'type' => $type, 'id' => $entry['id'], 'hash' => $hash,
                    'export' => $entry['slug'] . '--nadin-' . $entry['id'] . '-' . substr($hash, 0, 16) . '.pdf');
            }
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $temporary = $directory . '/.nadin-forscore-' . bin2hex(random_bytes(12)) . '.tmp';
            if ($json === false || file_put_contents($temporary, $json . "\n", LOCK_EX) !== strlen($json) + 1 || !rename($temporary, $path)) throw new RuntimeException('Could not save forScore identities.');
            $temporary = null;
            return $result;
        } finally {
            if ($temporary !== null && is_file($temporary)) unlink($temporary);
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function assets($tune) {
        if (!$this->component($tune)) return array();
        $directory = $this->root . '/library/' . $tune;
        if (!is_dir($directory) || is_link($directory)) return array();
        $files = array();
        foreach ((array)glob($directory . '/*') as $source) {
            $file = basename($source);
            if (!is_file($source) || is_link($source) || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') continue;
            if (!$this->component($file)) continue;
            global $hidden;
            if (isset($hidden) && $hidden !== '' && strpos($file, $hidden) !== false && empty($this->rights['musers'])) continue;
            $permitted = true;
            foreach ($this->profiles as $restriction) {
                if ($restriction['code'] !== '' && strpos($tune . '/' . $file, $restriction['code']) !== false && empty($this->rights[$restriction['right']])) $permitted = false;
            }
            if (!$permitted) continue;
            $type = $this->type($file);
            if ($type !== null) $files[$file] = $type;
        }
        ksort($files, SORT_STRING);
        return $files ? $this->registry($directory, $files) : array();
    }

    public static function inventory($csv) {
        if (strlen($csv) > 5 * 1024 * 1024) throw new RuntimeException('The library CSV exceeds 5 MB.');
        if (substr($csv, 0, 2) === "\xFF\xFE" || substr($csv, 0, 2) === "\xFE\xFF") {
            if (!function_exists('iconv')) throw new RuntimeException('Convert the library CSV to UTF-8 first.');
            $csv = iconv(substr($csv, 0, 2) === "\xFF\xFE" ? 'UTF-16LE' : 'UTF-16BE', 'UTF-8', substr($csv, 2));
        }
        $csv = nadinText(preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        $handle = fopen('php://temp', 'w+'); fwrite($handle, $csv); rewind($handle);
        $column = null; $delimiter = ',';
        foreach (array(',', ';', "\t") as $candidate) {
            rewind($handle); $header = fgetcsv($handle, 0, $candidate, '"', '');
            if (!is_array($header)) continue;
            foreach ($header as $index => $name) {
                if (in_array(strtolower(trim($name)), array('filename', 'file name'), true)) { $column = $index; $delimiter = $candidate; break 2; }
            }
        }
        if ($column === null) { fclose($handle); throw new RuntimeException('Use the forScore library metadata CSV with a Filename column. A title-only list cannot identify PDFs.'); }
        $names = array();
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if (!isset($row[$column]) || trim($row[$column]) === '') continue;
            $name = trim($row[$column]);
            if (strlen($name) > 512 || preg_match('~[\\\\/\x00-\x1f]~', $name) || strtolower(substr($name, -4)) !== '.pdf') throw new RuntimeException('Invalid PDF filename in the library CSV.');
            if (isset($names[$name])) throw new RuntimeException('The library CSV has duplicate filenames; resolve them in forScore first.');
            $names[$name] = true;
            if (count($names) > 50000) throw new RuntimeException('Too many files in the library CSV.');
        }
        fclose($handle);
        return array_keys($names);
    }

    public function plan($module, $rep, $profile, array $names, array $choices = array()) {
        $allowed = $this->allowedProfiles();
        if (!isset($allowed[$profile])) throw new RuntimeException('This section is not available to your account.');
        $tunes = $this->tunes($module, $rep);
        $entries = array(); $rows = array(); $identities = array();
        $knownNames = array_fill_keys($names, true);
        $versions = array();
        foreach ($names as $name) {
            if (preg_match('/--nadin-([a-f0-9]{32})-[a-f0-9]{16}\.pdf$/i', $name, $identity)) $versions[strtolower($identity[1])][] = $name;
        }
        foreach ($tunes as $tune) {
            if ($tune === '') continue;
            $key = hash('sha256', $tune);
            $entries[] = $key;
            if (isset($rows[$key])) continue;
            // Legacy Reps store headings as text without a corresponding library entry.
            // An existing entry (including an empty folder or a blocked symlink) is
            // still a tune whose missing notes require explicit confirmation.
            $directory = $this->root . '/library/' . $tune;
            $heading = $module !== 'library' && (!$this->component($tune) || (!file_exists($directory) && !is_link($directory)));
            $row = array('tune' => nadinText($tune), 'key' => $key, 'kind' => $heading ? 'heading' : 'tune', 'options' => array(), 'selected' => '', 'reason' => '');
            if ($heading) { $rows[$key] = $row; continue; }
            $assets = $this->assets($tune);
            if (!$assets) {
                $row['reason'] = 'No accessible PDF'; $rows[$key] = $row; continue;
            }
            $primary = array(); $fallback = array(); $existing = array();
            foreach ($assets as $asset) {
                if ($asset['type'] !== $profile && !($asset['type'] === 'parts' && $profile !== 'score' && $profile !== 'parts')) continue;
                if (isset($identities[$asset['id']]) && $identities[$asset['id']] !== $asset['source']) throw new RuntimeException('Two tune folders share a forScore identity. Remove the copied metadata from the copied tune folder.');
                $identities[$asset['id']] = $asset['source'];
                $current = 'current:' . $asset['id'];
                $exact = isset($knownNames[$asset['export']]);
                $row['options'][$current] = array('asset' => $asset, 'path' => $asset['export'], 'known' => $exact, 'label' => $asset['file'] . ($exact ? ' — in forScore' : ' — current PDF'), 'old' => false);
                if ($asset['type'] === $profile) $primary[] = $current; else $fallback[] = $current;
                if ($exact) $existing[] = $current;
                if (isset($knownNames[nadinText($asset['file'])]) && $asset['file'] !== $asset['export']) {
                    $sameName = 0;
                    foreach ((array)glob($this->root . '/library/*', GLOB_ONLYDIR) as $otherDirectory) if (is_file($otherDirectory . '/' . $asset['file'])) $sameName++;
                    if ($sameName === 1) {
                        $legacyKey = 'local:' . hash('sha256', nadinText($asset['file']));
                        $row['options'][$legacyKey] = array('asset' => $asset, 'path' => nadinText($asset['file']), 'known' => true, 'label' => $asset['file'] . ' — existing filename (contents unverified)', 'old' => true);
                        $existing[] = $legacyKey;
                    }
                }
                foreach (isset($versions[$asset['id']]) ? $versions[$asset['id']] : array() as $name) {
                    if ($name !== $asset['export'] && preg_match('/--nadin-' . $asset['id'] . '-[a-f0-9]{16}\.pdf$/i', $name)) {
                        $oldKey = 'local:' . hash('sha256', $name);
                        $row['options'][$oldKey] = array('asset' => $asset, 'path' => $name, 'known' => true, 'label' => $asset['file'] . ' — KEEP local version (' . $name . ')', 'old' => true);
                        $existing[] = $oldKey;
                    }
                }
            }
            if (isset($choices[$key])) {
                if ($choices[$key] !== '' && !isset($row['options'][$choices[$key]])) throw new RuntimeException('A selected PDF changed or is no longer accessible. Review the export again.');
                $row['selected'] = $choices[$key];
            } elseif (count($existing) === 1) {
                $row['selected'] = $existing[0];
            } elseif (count($existing) > 1) {
                $row['reason'] = 'Several local versions: choose one';
            } else {
                $candidates = $primary ? $primary : $fallback;
                if (count($candidates) === 1) $row['selected'] = $candidates[0];
                else $row['reason'] = $candidates ? 'Several PDFs: choose one' : 'No matching section or Parts';
            }
            $rows[$key] = $row;
        }
        return array('entries' => $entries, 'rows' => $rows);
    }

    public static function xml($value) {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', nadinText($value));
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function write($handle, $text) {
        $offset = 0; $length = strlen($text);
        while ($offset < $length) {
            $written = fwrite($handle, substr($text, $offset));
            if ($written === false || $written === 0) throw new RuntimeException('Export storage is full or unavailable.');
            $offset += $written;
        }
    }

    public static function writeSetlist($handle, $title, array $plan, $includeNew, $allowPlaceholders) {
        $emitted = array(); $total = 0;
        foreach ($plan['rows'] as $row) {
            if ($row['selected'] === '' && $row['options']) throw new RuntimeException('Choose a PDF for every ambiguous tune.');
            if ($row['kind'] !== 'heading' && $row['selected'] === '' && !$allowPlaceholders) throw new RuntimeException('Missing notes require explicit placeholder confirmation.');
            if ($row['selected'] !== '') {
                $option = $row['options'][$row['selected']];
                if ($includeNew && !$option['known']) $total += filesize($option['asset']['source']);
            }
        }
        if ($total > 150 * 1024 * 1024) throw new RuntimeException('The PDFs exceed 150 MB. Export smaller Reps or import individual PDFs first.');
        self::write($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<forScore kind="setlist" version="1.0" title="' . self::xml($title) . '">' . "\n");
        foreach ($plan['entries'] as $key) {
            $row = $plan['rows'][$key];
            if ($row['selected'] === '') { self::write($handle, '  <placeholder title="' . self::xml($row['tune']) . '" />' . "\n"); continue; }
            $option = $row['options'][$row['selected']];
            $path = $option['path'];
            self::write($handle, '  <score title="' . self::xml(str_replace('_', ' ', $row['tune'])) . '" path="' . self::xml($path) . '"');
            if ($includeNew && !$option['known'] && !isset($emitted[$path])) {
                $source = $option['asset']['source'];
                $pdf = fopen($source, 'rb');
                if (!$pdf) throw new RuntimeException('PDF no longer readable.');
                $hash = hash_init('sha256');
                self::write($handle, ' data="');
                try {
                    if (fread($pdf, 5) !== '%PDF-') throw new RuntimeException('The selected file is not a PDF.');
                    rewind($pdf);
                    while (!feof($pdf)) {
                        $chunk = fread($pdf, 49152); // A multiple of three: concatenate base64 safely.
                        if ($chunk === false) throw new RuntimeException('PDF read failed.');
                        hash_update($hash, $chunk); self::write($handle, base64_encode($chunk));
                    }
                } finally { fclose($pdf); }
                if (hash_final($hash) !== $option['asset']['hash']) throw new RuntimeException('PDF changed during export. Please review again.');
                self::write($handle, '"');
            }
            $emitted[$path] = true;
            self::write($handle, " />\n");
        }
        self::write($handle, "</forScore>\n");
    }
}

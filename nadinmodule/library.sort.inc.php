<?php
// Shared by the library screen, print view and XLS export.
$librarySort = (isset($_GET['sort']) && $_GET['sort'] === 'newest') ? 'newest' : 'alpha';
$libraryTuneDates = array();
$libraryTuneOrder = array_flip($tunes);

// Dates are available in both modes, so the browser can sort without another request.
foreach ($tunes as $libraryTune) {
    $libraryTune = trim($libraryTune);
    $libraryDirectory = $path . '/' . $libraryTune;
    $libraryLatestTime = 0;
    if ($libraryTune !== '' && ($libraryHandle = @opendir($libraryDirectory))) {
        while (false !== ($libraryFile = readdir($libraryHandle))) {
            if (substr($libraryFile, 0, 1) === '.') continue;
            $libraryFilePath = $libraryDirectory . '/' . $libraryFile;
            if (!is_file($libraryFilePath)) continue;
            $libraryFileTime = @filemtime($libraryFilePath);
            if ($libraryFileTime !== false && $libraryFileTime > $libraryLatestTime) {
                $libraryLatestTime = $libraryFileTime;
            }
        }
        closedir($libraryHandle);
    }
    $libraryTuneDates[$libraryTune] = $libraryLatestTime;
}

if ($librarySort === 'newest') {
    usort($tunes, function ($a, $b) use ($libraryTuneDates, $libraryTuneOrder) {
        $libraryATime = $libraryTuneDates[trim($a)];
        $libraryBTime = $libraryTuneDates[trim($b)];
        if ($libraryATime === $libraryBTime) {
            return $libraryTuneOrder[$a] <=> $libraryTuneOrder[$b];
        }
        return $libraryBTime <=> $libraryATime;
    });
}

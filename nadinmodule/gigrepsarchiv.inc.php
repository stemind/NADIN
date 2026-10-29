<?php
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('varchiv');
require('gigreps.inc.php');
// Preserve archive ZIP cleanup without a second layout script.
foreach (array('alllistento', 'allscore', 'allparts', 'allsaxes', 'allbones', 'alltrumpets', 'allrhythm', 'allother') as $archiveZipType) {
    $archiveZip = 'abo/zip/' . $archiveZipType . '/' . str_replace('.txt', '', basename($_GET['c'])) . '_' . $archiveZipType . '.zip';
    if (is_file($archiveZip)) unlink($archiveZip);
}

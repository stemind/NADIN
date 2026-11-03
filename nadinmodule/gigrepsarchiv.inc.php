<?php
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('varchiv');
require('gigreps.inc.php');
// An archived name must never delete a current gig's ZIP cache.
if (!is_file('gigreps/'.basename($_GET['c']))) {
// Preserve archive ZIP cleanup without a second layout script.
foreach (array('alllistento', 'allscore', 'allparts', 'allsaxes', 'allbones', 'alltrumpets', 'allrhythm', 'allother') as $archiveZipType) {
    $archiveZip = 'abo/zip/' . $archiveZipType . '/' . str_replace('.txt', '', basename($_GET['c'])) . '_' . $archiveZipType . '.zip';
    if (is_file($archiveZip)) unlink($archiveZip);
}
}

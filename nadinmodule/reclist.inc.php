<?php
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
require_once('nadinmodule/ui.inc.php');
auth('vreclist');
$recordings = array();
foreach ((array)glob('library/*', GLOB_ONLYDIR) as $tuneDirectory) {
    foreach ((array)glob($tuneDirectory . '/*') as $recording) {
        $file = basename($recording);
        $prefix = substr($file, 0, 3);
        if (!is_file($recording) || is_link($recording)) continue;
        if (strcasecmp($prefix, substr($bb, 0, 3)) !== 0 && strcasecmp($prefix, 'xyz') !== 0) continue;
        $group = substr($file, 0, 14);
        $recordings[$group][] = array('file' => $file, 'tune' => basename($tuneDirectory));
    }
}
krsort($recordings, SORT_STRING);
?>
<div class="nadin-layout" style="--nadin-sidebar-width:<?php echo nadinSidebarWidth(); ?>px">
<nav id="nadin-sidebar" class="nadin-sidebar" aria-label="Recording dates" data-sidebar="reclist">
<?php foreach ($recordings as $group => $items) { ?>
<a href="#rec-<?php echo md5($group); ?>"><?php echo nadinHtml(substr($group, 4)); ?></a>
<?php } ?>
</nav>
<main class="nadin-content nadin-recordings">
<?php
require('abous.inc.inc.php');
if (!$recordings) echo '<p>No recordings available.</p>';
foreach ($recordings as $group => $items) {
    usort($items, function ($a, $b) { return strcmp($b['file'], $a['file']); });
    echo '<section id="rec-' . md5($group) . '"><h4>' . nadinHtml(substr($group, 4)) . '</h4><div class="nadin-recording-list">';
    $totalseconds = 0;
    foreach ($items as $item) {
        $file = $item['file'];
        echo '<div class="nadin-recording-row"><a id="' . mkid2($file) . '" href="get/file.php?c=' . rawurlencode($item['tune'] . '/' . $file) . '" target="_blank">' . nadinHtml(substr($file, strlen($group))) . '</a>';
        if (role('upaudio')) {
            $manageUrl = 'nadinmodule/managetune.php?' . http_build_query(array('tune' => $item['tune'], 'm' => mkid2($file)));
            echo ' <a class="nadin-manage-recording" title="manage ' . nadinHtml(str_replace('_', ' ', $item['tune'])) . '" href="' . nadinHtml($manageUrl) . '" target="_blank">&#9998;</a>';
        }
        echo '</div>';
        if (preg_match('/\((\d+)\.(\d{2})\)/', $file, $duration)) $totalseconds += (int)$duration[1] * 60 + (int)$duration[2];
    }
    echo '</div><small class="nadin-recording-duration">' . hms($totalseconds) . '</small></section>';
}
?>
</main>
</div>

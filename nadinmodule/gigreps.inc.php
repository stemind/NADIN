<?php
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
require_once('nadinmodule/ui.inc.php');
auth('vgigs');
$gigModule = $_GET['m'] === 'gigrepsarchiv' ? 'gigrepsarchiv' : 'gigreps';
$gigs = nadinRepFiles($gigModule, $gigModule === 'gigrepsarchiv');
$selectedGig = isset($_GET['c']) && is_string($_GET['c']) ? $_GET['c'] : '';
if (!in_array($selectedGig, $gigs, true)) $selectedGig = isset($gigs[0]) ? $gigs[0] : '';
$_GET['c'] = $selectedGig;
?>
<div class="nadin-layout" style="--nadin-sidebar-width:<?php echo nadinSidebarWidth(); ?>px">
<nav id="nadin-sidebar" class="nadin-sidebar" aria-label="GigReps" data-sidebar="<?php echo $gigModule; ?>">
<?php foreach ($gigs as $gig) { ?>
<a id="id<?php echo md5($gig); ?>" href="?<?php echo nadinHtml(http_build_query(array('m' => $gigModule, 'c' => $gig))); ?>"<?php if ($gig === $selectedGig) echo ' aria-current="page"'; ?> title="<?php echo nadinHtml(substr($gig, 0, -4)); ?>"><?php echo nadinHtml(substr($gig, 0, -4)); ?></a>
<?php } ?>
</nav>
<main class="nadin-content">
<?php
if ($selectedGig === '') {
    echo '<p>No GigReps available.</p>';
} else {
    list($infos, $tunes) = nadinReadRep($gigModule . '/' . $selectedGig, $delimiter2);
    echo '<div class="nadin-info-scroll"><div class="nadin-info-inner">';
    require('nadinmodule/infos.inc.php');
    echo '</div></div>';
?>
<div class="nadin-export-links">
<a href="get/forscore.php?<?php echo nadinHtml(http_build_query(array('m' => $gigModule, 'c' => $selectedGig))); ?>">forScore</a>
<a href="nadinmodule/gigreps.xls.php?<?php echo nadinHtml(http_build_query(array('m' => $gigModule, 'c' => $selectedGig))); ?>" target="_blank">xls&darr;</a>
<a href="nadinmodule/gigreps.print.php?<?php echo nadinHtml(http_build_query(array('m' => $gigModule, 'c' => $selectedGig))); ?>" target="_blank">print&darr;</a>
</div>
<div id="includes">
<div class="nadin-table-scroll" tabindex="0" role="region" aria-label="Tunes and downloads">
<?php require('table.inc.inc.php'); ?>
</div>
<?php
echo '<details class="nadin-downloads" open><summary>Downloads</summary>';
require('zipped.inc.php');
echo '</details>';
if ($gigModule !== 'gigrepsarchiv') require('abo.inc.inc.php');
?>
</div>
<?php } ?>
</main>
</div>

<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('vgigs');
// Default to the left column; only the literal 'no' selects the top list.
$gigListOnLeft = ($rendergiglistontheleft ?? 'yes') !== 'no';
$gigListWidth = isset($ifleftthenthiswidth) ? (int)$ifleftthenthiswidth : 300;
if ($gigListWidth < 1) $gigListWidth = 300;
?>
<?php if ($gigListOnLeft) { ?>
<style>
body { min-width:1200px; }
#nadin-gig-list { margin-top:-11px;margin-right:11px;font-size:85%;width:<?php echo $gigListWidth; ?>px;float:left;min-height:600px;height:calc(100vh - 150px); }
#includes { margin-left:<?php echo $gigListWidth + 20; ?>px; }
#nadin-gig-list ~ .infos { margin-left:<?php echo $gigListWidth; ?>px; }
</style>
<?php } ?>
<h3 id="nadin-gig-list" style="max-height:<?php echo $gigListOnLeft ? 'none' : '200px'; ?>;overflow-x:hidden;overflow-y:auto">
<?php
$path='./'.basename($_GET['m']);
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }
$gigs=explode($delimiter,$gigs);

if ($_GET['m']=='gigreps') {
sort($gigs);
if (!isset($_GET['c'])) $_GET['c']=$gigs[1];
}

else {
rsort($gigs);
if (!isset($_GET['c'])) $_GET['c']=$gigs[0];
}

for ($i=0; $i<count($gigs);$i++) {
echo '<a id="id'.md5($gigs[$i]).'" href="./?m='.$_GET['m'].'&c='.$gigs[$i].'">'.str_replace('.txt','',str_replace(' ','&nbsp;',str_replace('-','&#8209;',$gigs[$i]))).'</a>'.($gigListOnLeft ? '<br>' : ' &nbsp; ');
}
?>
</h3>
<?php
$c=@file_get_contents('./'.basename($_GET['m']).'/'.basename($_GET['c']));
$c=explode($delimiter2,$c);
$infos=$c[0];
$rep=$c[1];


require('nadinmodule/infos.inc.php');

$rep = $rep ?? '';
$tunes=explode("\n",$rep);

?>
<div style="float:right;line-height:28px;text-align:right">
<a style="text-decoration:none" href="nadinmodule/gigreps.xls.php?<?php echo $_SERVER['QUERY_STRING']?>" target="_blank">xls&darr;</a><br />
<a style="text-decoration:none" href="nadinmodule/gigreps.print.php?<?php echo $_SERVER['QUERY_STRING']?>" target="_blank">print&darr;</a><br />
<?php if (role('vgigs')) { ?><a style="text-decoration:none" href="get/forscore.php?<?php echo htmlspecialchars(http_build_query(array('m' => $_GET['m'], 'c' => $_GET['c'], 'from_nadin' => '1')), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">forScore&darr;</a><?php } ?>
</div>

<div id="includes">
<?php 
require('table.inc.inc.php');
require('zipped.inc.php');
if($_GET['m']!='gigrepsarchiv') require('abo.inc.inc.php');
?>
</div>

<br /><br /><br />

<script>
if (document.getElementById('id<?php echo md5($_GET['c'])?>')) {
document.getElementById('id<?php echo md5($_GET['c'])?>').style.background='yellow';
document.getElementById('id<?php echo md5($_GET['c'])?>').focus();
}
</script>

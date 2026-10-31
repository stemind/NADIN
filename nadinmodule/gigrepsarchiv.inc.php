<?php
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('varchiv');
require('gigreps.inc.php');
unlink('abo/zip/alllistento/'.str_replace('.txt','',basename($_GET['c'])).'_alllistento.zip');
unlink('abo/zip/allscore/'.str_replace('.txt','',basename($_GET['c'])).'_allscore.zip');
unlink('abo/zip/allparts/'.str_replace('.txt','',basename($_GET['c'])).'_allparts.zip');
unlink('abo/zip/allsaxes/'.str_replace('.txt','',basename($_GET['c'])).'_allsaxes.zip');
unlink('abo/zip/allbones/'.str_replace('.txt','',basename($_GET['c'])).'_allbones.zip');
unlink('abo/zip/alltrumpets/'.str_replace('.txt','',basename($_GET['c'])).'_alltrumpets.zip');
unlink('abo/zip/allrhythm/'.str_replace('.txt','',basename($_GET['c'])).'_allrhythm.zip');
unlink('abo/zip/allother/'.str_replace('.txt','',basename($_GET['c'])).'_allother.zip');
?>
<?php if ($gigListOnLeft) { ?><style>#infoarea { display:none; }</style><script>if (document.getElementById('infoarea')) document.getElementById('infoarea').style.display='none';</script><?php } ?>
<script>
if (document.getElementById('id<?php echo md5($_GET['c'])?>')) {
document.getElementById('id<?php echo md5($_GET['c'])?>').style.background='yellow';
document.getElementById('id<?php echo md5($_GET['c'])?>').focus();
}
</script>
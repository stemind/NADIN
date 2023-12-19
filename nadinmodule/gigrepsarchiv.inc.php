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
<?php if ($rendergiglistontheleft=='yes') { ?>
<script>
h3=document.getElementsByTagName('h3')[0];
h3.innerHTML=h3.innerHTML.replace(/ &nbsp; /g,'<br>');
h3.style.marginTop='-11px';
h3.style.marginRight='11px';
h3.style.fontSize='85%';
h3.style.width='<? echo $ifleftthenthiswidth?>';
h3.style.float='left';
document.getElementById('infoarea').style.display='none';

body=document.getElementsByTagName('body')[0];
body.style.minWidth='1200px';

function arch() {
h3.style.maxHeight='50000px';
h3.style.minHeight='600px';
h3.style.height=window.innerHeight-150+'px';
setTimeout("arch()",777);
}
arch();
</script>
<?php } ?>
<script>
if (document.getElementById('id<?php echo md5($_GET['c'])?>')) {
document.getElementById('id<?php echo md5($_GET['c'])?>').style.background='yellow';
document.getElementById('id<?php echo md5($_GET['c'])?>').focus();
}
</script>
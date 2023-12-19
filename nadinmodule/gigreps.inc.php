<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('vgigs')?>
<h3 style="max-height:200px;overflow-x:hidden;overflow-y:auto">
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
echo '<a id="id'.md5($gigs[$i]).'" href="./?m='.$_GET['m'].'&c='.$gigs[$i].'">'.str_replace('.txt','',str_replace(' ','&nbsp;',str_replace('-','&#8209;',$gigs[$i]))).'</a> &nbsp; ';
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
<a style="text-decoration:none" href="nadinmodule/gigreps.print.php?<?php echo $_SERVER['QUERY_STRING']?>" target="_blank">print&darr;</a>
</div>

<div id="includes">
<?php 
require('table.inc.inc.php');
require('zipped.inc.php');
if($_GET['m']!='gigrepsarchiv') require('abo.inc.inc.php');
?>
</div>

<br /><br /><br />

<?php if ($rendergiglistontheleft=='yes') { ?>
<script>
h3=document.getElementsByTagName('h3')[0];
h3.innerHTML=h3.innerHTML.replace(/ &nbsp; /g,'<br>');
h3.style.marginTop='-11px';
h3.style.marginRight='11px';
h3.style.fontSize='85%';
h3.style.width='<?php echo $ifleftthenthiswidth?>';
h3.style.float='left';

body=document.getElementsByTagName('body')[0];
body.style.minWidth='1200px';

function arch() {
h3.style.maxHeight='50000px';
h3.style.minHeight='600px';
h3.style.height=window.innerHeight-150+'px';
document.getElementById('includes').style.marginLeft='<?php echo ((str_replace('px','',$ifleftthenthiswidth)+20).'px') ?>';
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
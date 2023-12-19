<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('vlibrary'); 
if (role('uppdf') && role('upaudio') && $_GET['m']=='library') $neu = '<span style="color:white;background:green;font-weight:bold;cursor:pointer;font-size:10px;" title="Ordner f&uuml;r neuen Tune anlegen" onclick="f1=window.open(\'nadinmodule/new.php\',\'_blank\',\'toolbar=0,location=0,status=1,top=0,left=700,menubar=0,scrollbars=1,resizable=1,width=800,height=830\');">neu</span><p>';
else $neu='';
?>
<h3 id="alphabet" style="position:fixed; padding:5px; top:111px; left:7px;"><?php echo $neu ?><a style="font-size:10px" onmouseover="location.href='#top'" href="#top">top</a><br/></h3>
<div style="float:right;line-height:28px;text-align:right">
<a style="text-decoration:none" href="nadinmodule/library.xls.php?<?php echo $_SERVER['QUERY_STRING']?>" target="_blank">xls&darr;</a><br />
<a style="text-decoration:none" href="nadinmodule/library.print.php?<?php echo $_SERVER['QUERY_STRING']?>" target="_blank">print&darr;</a>
</div>

<blockquote>
<?php
$path='./library';
$tunes='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $tunes.=$dir.$delimiter;
      }
   }


$tunes=explode($delimiter,$tunes);
sort($tunes);

require('ascii.inc.php');
require('table.inc.inc.php');

?>
</blockquote>

<?php 

if ($_GET['c']=='admin') {
?>

<div id="composer" style="position:absolute;width:400px;height:800px;top:111px;left:700px;background:#D9F1FF"><iframe id="icomposer" src="nadinmodule/composer.php" frameborder="0" width="400" height="800" name="ifrc"></iframe></div>
<div id="preview" style="position:absolute;width:600px;height:800px;top:111px;left:1110px;background:#D9F1FF"><iframe id="ipreview" src="preview.php"  frameborder="0" width="600" height="800" name="ifrp"></iframe></div>
<script>if (document.getElementById('managegigs')) document.getElementById('managegigs').style.background='yellow';</script>
<script src="nadinmodule/composer.position.js"></script>
<?php 
}
?>
<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('vlibrary'); 
if (role('uppdf') && role('upaudio') && $_GET['m']=='library') $neu = '<span style="color:white;background:green;font-weight:bold;cursor:pointer;font-size:10px;" title="Ordner f&uuml;r neuen Tune anlegen" onclick="f1=window.open(\'nadinmodule/new.php\',\'_blank\',\'toolbar=0,location=0,status=1,top=0,left=700,menubar=0,scrollbars=1,resizable=1,width=800,height=830\');">neu</span><p>';
else $neu='';
?>
<h3 id="alphabet" style="position:fixed; padding:5px; top:111px; left:7px;"><?php echo $neu ?><a style="font-size:10px" onmouseover="location.href='#top'" href="#top">top</a><br/><span id="library-alphabet-links"<?php if (isset($_GET['sort']) && $_GET['sort'] === 'newest') echo ' style="display:none"'; ?>></span></h3>
<div style="float:right;line-height:28px;text-align:right">
<a id="library-xls-link" style="text-decoration:none" href="nadinmodule/library.xls.php?<?php echo $_SERVER['QUERY_STRING']?>" target="_blank">xls&darr;</a><br />
<a id="library-print-link" style="text-decoration:none" href="nadinmodule/library.print.php?<?php echo $_SERVER['QUERY_STRING']?>" target="_blank">print&darr;</a>
</div>
<style>
#library-sort-form[data-library-sort="alpha"] { margin:33px 0 -77px 77px; }
#library-sort-form[data-library-sort="newest"] { margin:0 0 12px 77px; }
#nadin-library-table .nadin-library-tune-cell { position:relative; }
#nadin-library-table .nadin-library-date-badge {
    position:absolute; top:50%; right:3px; transform:translateY(-50%);
    padding:1px 4px; border-radius:3px; background:#ddd; color:#666;
    font-size:10px; line-height:13px; font-weight:normal; white-space:nowrap;
    pointer-events:none; z-index:1; -webkit-print-color-adjust:exact; print-color-adjust:exact;
}
#nadin-library-table[data-library-sort="alpha"] .nadin-library-date-badge,
#nadin-library-table[data-library-sort="newest"] .nadin-library-letter { display:none; }
</style>

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
require('library.sort.inc.php');
?>
<form id="library-sort-form" data-library-sort="<?php echo $librarySort ?>" method="get" action="./" onsubmit="return false" style="font-size:12px; color:#666;">
<?php
foreach ($_GET as $libraryQueryName => $libraryQueryValue) {
    if ($libraryQueryName === 'sort' || !is_scalar($libraryQueryValue)) continue;
    echo '<input type="hidden" name="' . htmlXspecialchars($libraryQueryName) . '" value="' . htmlXspecialchars((string)$libraryQueryValue) . '" />';
}
?>
<label for="library-sort">Sort:</label>
<select id="library-sort" name="sort" style="margin-left:4px; font-size:12px;" onchange="nadinLibrarySort(this.value)">
<option value="alpha"<?php if ($librarySort === 'alpha') echo ' selected="selected"'; ?>>Alphabetical</option>
<option value="newest"<?php if ($librarySort === 'newest') echo ' selected="selected"'; ?>>Newest first</option>
</select>
</form>
<?php

require('ascii.inc.php');
$libraryClientSorting = true;
require('table.inc.inc.php');

?>
<script src="nadinmodule/library.sort.js"></script>
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

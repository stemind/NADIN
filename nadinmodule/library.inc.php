<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('vlibrary'); 
if (role('uppdf') && role('upaudio') && $_GET['m']=='library') $neu = '<a class="nadin-library-new" href="nadinmodule/new.php" target="_blank" rel="noopener" title="Create a tune" onclick="window.open(this.href,\'_blank\',\'toolbar=0,location=0,status=1,top=0,left=700,menubar=0,scrollbars=1,resizable=1,width=800,height=830\');return false;">+ Tune</a>';
else $neu='';
?>
<div class="nadin-layout nadin-library-layout">
<nav id="nadin-sidebar" class="nadin-library-alphabet" aria-label="Library alphabet" data-sidebar="library"><?php echo $neu ?><a href="#top">Top</a><span id="library-alphabet-links"<?php if (isset($_GET['sort']) && $_GET['sort'] === 'newest') echo ' style="display:none"'; ?>></span></nav>
<main class="nadin-content">
<div class="nadin-export-links nadin-library-export">
<a href="get/forscore.php?m=library">forScore</a>
<a id="library-xls-link" style="text-decoration:none" href="nadinmodule/library.xls.php?<?php echo htmlXspecialchars(http_build_query(array('m'=>'library','sort'=>($_GET['sort'] ?? '')==='newest'?'newest':'alpha')))?>" target="_blank">xls&darr;</a>
<a id="library-print-link" style="text-decoration:none" href="nadinmodule/library.print.php?<?php echo htmlXspecialchars(http_build_query(array('m'=>'library','sort'=>($_GET['sort'] ?? '')==='newest'?'newest':'alpha')))?>" target="_blank">print&darr;</a>
</div>
<style>
#library-sort-form[data-library-sort="alpha"] { margin:0 0 4px; }
#library-sort-form[data-library-sort="newest"] { margin:0 0 4px; }
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

<div class="nadin-library-body"><label class="nadin-search-label">Find a tune <input id="nadin-tune-search" type="search" placeholder="Title…"></label><span id="nadin-search-count" role="status" class="nadin-hint"></span>
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

// Existing media names are never changed by viewing the library.
$libraryClientSorting = true;
echo '<div class="nadin-table-scroll" tabindex="0" role="region" aria-label="Library tunes and downloads">';
require('table.inc.inc.php');
echo '</div>';

?>
<script src="nadinmodule/library.sort.js"></script>
</div>
</main></div>

<?php require('config.inc.php');require_once('nadinmodule/functions.inc.php');auth('mgigs');require_once('nadinmodule/popup-ui.inc.php');nadinPopupStart('Gig preview',''); ?>
<?php 
$c=@file_get_contents('gigreps/'.basename($_GET['gig']));
$c=explode($delimiter2,$c);
$infos=$c[0];
$rep=$c[1] ?? '';
$_GET['m']='gigreps';

$small='style="font-size:80%"';

require('nadinmodule/infos.inc.php');
$rep = $rep ?? '';
$tunes=explode("\n",$rep);

require('nadinmodule/table.inc.inc.php');
?>
<iframe name="general" width="0" height="0" frameborder="0"></iframe>
<?php 
require_once('nadinmodule/findpos.js.php');
?>

</body></html>

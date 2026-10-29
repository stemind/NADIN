<?php require('config.inc.php'); require_once('nadinmodule/functions.inc.php'); auth('mgigs'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="style.css" />
<script src="shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
</head>
<body style="backgound:#D9F1FF">
<?php 
$c=@file_get_contents('gigreps/'.basename($_GET['gig']));
$c=explode($delimiter2,$c);
$infos=$c[0];
$rep=$c[1];

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

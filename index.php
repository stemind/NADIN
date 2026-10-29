<?php 
require('nadinmodule/refreshcookies.inc.php'); 
require_once('config.inc.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" type="text/css" href="style.css" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<title><?php echo $bb ?> - NADIN - Online Big Band Sheet Music Manager</title>
<?php 
require_once('nadinmodule/functions.inc.php')
?>
<script src="shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
</head>
<body class="nadin-page">
<header class="nadin-header">
<button type="button" class="nadin-sidebar-toggle" id="nadin-sidebar-toggle" aria-expanded="false" aria-controls="nadin-sidebar" aria-label="Open list">&#9776;</button>
<h1>NADIN <small class="nadin-version">V8</small></h1>
<div class="nadin-account"><?php echo htmlXspecialchars($uc) ?> | <a href="logout.php">logout</a></div>
<img src="nadink.gif" width="40" height="40" alt="" />
</header>
<?php
if (!isset($_GET['m']) || !is_string($_GET['m']) || !in_array($_GET['m'], array('gigreps', 'gigrepsarchiv', 'reclist', 'library', 'users'), true)) $_GET['m']='gigreps';

require_once('nadinmodule/navigation.inc.php');

if (file_exists('nadinmodule/'.basename($_GET['m']).'.inc.php')) require_once('nadinmodule/'.basename($_GET['m']).'.inc.php');

?>
<iframe name="general" title="Background requests" style="display:none"></iframe>
<script src="nadinmodule/layout.js"></script>
<script>setTimeout("location.href='login.php'",<?php echo ($timeout*1000) ?>);</script>
<?php 
require_once('nadinmodule/findpos.js.php');

foldercheck('library');
foldercheck('userdatei');
foldercheck('gigreps');
foldercheck('gigrepsarchiv');
foldercheck('gigrepsdeleted');
foldercheck('filesdeleted');
foldercheck('abo/zip');

function foldercheck($folder) {
if (file_put_contents("$folder/x",'x')) {
echo "<!--w $folder ok -->";
unlink("$folder/x");
}
else echo "<script>alert('ERROR. The following folder is not writable for the server but it should be: $folder');</script>";
}
?>
</body>
</html>
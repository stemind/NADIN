<?php 
setcookie('upos', 'uploadpdf', time()+3600*24*365*10, '/');
require('refreshcookies2.inc.php');
require_once('functions.inc.php')
?>
<?php
require_once('../config.inc.php');
setcookie('u', $_COOKIE['u'], time()+$timeout, '/');
setcookie('p', $_COOKIE['p'], time()+$timeout, '/');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script src="../shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<link rel="icon" href="../favicon.ico" type="image/x-icon" />
<title>manage <?php echo htmlXspecialchars(str_replace('_',' ',$_GET['tune'])) ?> - NADIN</title>
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('uppdf');
?></head>

<body>

<?php 
echo '<h1>'.htmlXspecialchars(str_replace('_',' ',$_GET['tune'])).'</h1>';?>

<?php require('uploadnavi.inc.php'); ?>
<h2>Existing files</h2>
<iframe src="PDFfiles.php?name=<?php echo $_GET['tune']?>" name="files" frameborder="0" width="100%" height="220"></iframe>


<h2>Upload further files</h2>
<iframe src="PDFsendfile.php?name=<?php echo $_GET['tune']?>" frameborder="0" width="100%" height="88"></iframe>
<span style="font-size:70%"><?php include('inigetmultiple.inc.php')?></span>
<p>
<div style="background:#eee;padding:3px">
<small>
<b style="font-size:11px">Recommended file names:</b>
<table style="font-size:11px">
<tr><td>mixed score</td><td><?php echo $_GET['tune'].'_<b>'.$parts.'</b>_.pdf'?></td></tr>
<tr><td>conductor's score</td><td><?php echo $_GET['tune'].'_<b>'.$score.'</b>_.pdf'?></td></tr>
<tr><td>sax sheets</td><td><?php echo $_GET['tune'].'_<b>'.$saxes.'</b>_.pdf'?></td></tr>
<tr><td>trombone sheets</td><td><?php echo $_GET['tune'].'_<b>'.$bones.'</b>_.pdf'?></td></tr>
<tr><td>trumpet sheets</td><td><?php echo $_GET['tune'].'_<b>'.$trumpets.'</b>_.pdf'?></td></tr>
<tr><td>rhythm sheets</td><td><?php echo $_GET['tune'].'_<b>'.$rhythm.'</b>_.pdf'?></td></tr>
<tr><td>other sheets</td><td><?php echo $_GET['tune'].'_<b>'.$other.'</b>_.pdf'?></td></tr>
<tr><td>hidden* files</td><td><?php echo $_GET['tune'].'_<b>'.$hidden.'</b>_anytext_.anyext'?></td></tr>
</table>
<small>*visible to admins, suitable for Sibelius files etc.</small>
</small>
</div>
<iframe style="display:none" src="rezip.php?c=<?php echo $_COOKIE['rezip']?>"></iframe>
</body>
</html>

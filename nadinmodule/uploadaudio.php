<?php 
setcookie('upos', 'uploadaudio', time()+3600*24*365*10, '/');
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
auth('upaudio');
?></head>

<body>

<?php 
echo '<h1>'.htmlXspecialchars(str_replace('_',' ',$_GET['tune'])).'</h1>';
?>

<?php require('uploadnavi.inc.php'); ?>
<h2>Existing files</h2>
<iframe src="AUDfiles.php?name=<?php echo $_GET['tune']?>&m=<?php echo $_GET['m'] ?>" name="files" frameborder="0" width="100%" height="250"></iframe>


<h2>Upload further files</h2>
<iframe src="AUDsendfile.php?name=<?php echo $_GET['tune']?>" frameborder="0" width="100%" height="88"></iframe>
<span style="font-size:70%"><?php include('iniget.inc.php')?></span>
<p>
<div style="background:#eee;padding:3px">
<small>Mark the recording that best fits the arrangement with the string RCMD in the filename! Mark only one tune per arrangement that way!</small>
<p><small>Name your audio file in a self-explanatory way before uploading it, or, after uploading, with Rename; in both cases, use an as-yet unused name.<p><font color=red>PLEASE NOTE:</font> <?php echo $bb ?> recordings are recordings of your band and must be named in the following manner:<br><b><?php echo $bb ?>_2014-12-31_optGigName_TuneName.mp3</b> or (see above) <b><?php echo $bb ?>_2014-12-31_optGigName_TuneName_<?php echo $recommended ?>.mp3</b>.</small>
</div>
<iframe style="display:none" src="rezip.php?c=<?php echo $_COOKIE['rezip']?>"></iframe>
</body>
</html>
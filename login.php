<?php require_once('config.inc.php'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="style.css" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<title><?php echo $bb ?> - NADIN - Login</title>
<?php
require_once('nadinmodule/functions.inc.php');
require_once('nadinmodule/zr.inc.php');
if (trim($salt)=='' || trim($salt)=='???') die('In config.inc.php $salt has to be defined.');
if(strlen(mkid($bb))!=4) die('In config.inc.php $bb has to be defined with exactly 3 characters, no special characters!');
?>
<script src="shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
</head>
<body id="body">
<div id="content" style="display:none">
<img align="right" src="nadink.gif" width="40" height="40" />
<h1>NADIN</h1>
<h2>Login</h2>
<?php
if (strpos($users,'nadin@nadin.na')>0) echo '
<h3><big>NADIN INITIAL LOGIN</big>
<br>Username = <font color=red>nadin@nadin.na</font>
<br>Password = <font color=red>'.nadinhash('nadin@nadin.na'.$salt).'</font>
</h3>
';
?>
<form name="form" action="login2.php" method="post">

<p>Username:<br>
<input type="text" name="u" id="u" style="font-size:200%;width:500px">

<p>Password:<br>
<input type="password" name="p" style="font-size:200%;width:500px"><br><br>

<p><input type="submit" value="log in" style="font-size:200%">
</form>

<br>
<p>
<a href="bestellen.php">I don&apos;t yet have a password or have forgotten it.</a>

<script>
if (top!=self) top.location.href=location.href;
document.getElementById('content').style.display='block';
document.getElementById('u').focus();
if (!navigator.cookieEnabled) {alert("You have Cookies disabled in your Browser. You have to enable Cookies to work with NADIN!");document.getElementById('body').innerHTML='Enable Cookies now in your Browser Properties, then <a href="javascript:location.href=location.href">restart NADIN by clicking here</a>.';}
</script>
</div>
<noscript>You have JavaScript disabled in your Browser. You have to enable JavaScript to work with NADIN! Enable Cookies now in your Browser Properties, then <a href="login.php">restart NADIN by clicking here</a>.</noscript>
<div align="right" style="font-size:10px"><a href="http://yuba.ch/nadin" target="_blank">NADIN</a> 7.3</div>
</body>
</html>
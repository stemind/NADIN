<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<link rel="icon" href="../favicon.ico" type="image/x-icon" />
<title>NADIN - Login</title>
<?php 
require_once('../config.inc.php');
require_once('../nadinmodule/functions.inc.php');
sleep(1);
?>
</head>
<body id="body">
<img align="right" src="../nadink.gif" width="40" height="40" />
<h1>NADIN</h1>

<h2>Timeout / wrong login</h2>

Please log in again.
<p>
<a href="javascript:einloggen()">next</a>
<script>
function einloggen() {
if(!opener)window.open('../login.php');
if(opener)opener.location.reload();
if(opener)opener.focus();
document.title='close';
document.getElementById('body').style.display='none';
self.close();
}
</script>
</body>
</html>
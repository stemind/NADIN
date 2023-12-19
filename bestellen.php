<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="style.css" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<title>NADIN - Login</title>
<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php')
?>
<script>
function validated(string) {
    for (var i=0, output='', valid="0123456789"; i<string.length; i++)
       if (valid.indexOf(string.charAt(i)) != -1)
          output += string.charAt(i)
    return output;
}
</script>
<script src="shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
</head>
<body>
<img align="right" src="nadink.gif" width="40" height="40" />
<h1>NADIN</h1>

<h2>Get a password</h2>

<form name="form" action="bestellen2.php" method="post">

<p>Your email address<br>
<input type="text" name="u" id="u" style="font-size:200%;width:500px">

<p>Checking number: number of the day of today&apos;s <small>(<?php echo date('T').' <small>'.date('e').'</small>' ?>)</small> date<br>
<input onkeyup="if (this.value!=validated(this.value)) this.value=validated(this.value)" onchange="if (this.value!=validated(this.value)) this.value=validated(this.value)" type="text" name="c" style="font-size:200%;width:500px"><br><br>

<p><input type="submit" value="Order password" style="font-size:200%">
</form>

<br>
<p>
<a href="login.php">Order no password.</a>

<script>document.getElementById('u').focus();</script>

</body>
</html>
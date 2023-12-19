<?php
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
$tune=mkascii($_POST['tune']);
setcookie('scrlt', mkid2($tune), time()+600, '/'); 
auth('uppdf');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<link rel="icon" href="../favicon.ico" type="image/x-icon" />
<title>New tune - NADIN</title>
</head>

<body>
<?php 

if (strlen($tune)<3) die('<script>alert("The name must be at least 3 characters long.");history.go(-1);</script>');

if (file_exists('../library/'.$tune)) die('<script>alert("The tune '.htmlXspecialchars($tune).' already exists.");history.go(-1);</script>');

if (mkdir('../library/'.$tune)) echo '<script>
if(opener)opener.location.reload();
</script>Success, <b>'.htmlXspecialchars($tune).'</b> was created.<script>setTimeout("self.close()",2222);</script>';

?>
</body>
</html>

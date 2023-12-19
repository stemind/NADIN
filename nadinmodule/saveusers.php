<?php require ('refreshcookies2.inc.php') ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="../style.css" />
</head>

<body style="backgound:#D9F1FF">
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('musers');
if (file_put_contents('../userdatei/userdatei.txt',$_POST['users'])) echo "<script>

parent.document.getElementById('save').style.background='lightgreen';
parent.document.getElementById('save').style.color='black';
parent.document.getElementById('save').innerHTML='s';
parent.document.getElementById('save').title='save [Ctrl s]';
parent.document.getElementById('changes').value=0;

</script>";
else echo '<script>alert("Could not save. The server must have write permissions in the folder userdatei and on all files there (incl. .htpasswd) (e. g. chmod 777).")</script>';
?>
<iframe src="p.php" width="0" height="0" frameborder="0"</iframe>
</body>
</html>
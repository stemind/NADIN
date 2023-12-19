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
auth('mgigs');
$filename=basename($_POST['gig']);
$data=trim($_POST['infos']."\r\n".$delimiter2."\r\n".$_POST['tunes']);
$data=str_replace("\r\n\r\n\r\n","\r\n\r\n",$data);
$data=str_replace("\n\n\n","\n\n",$data);
if (file_put_contents('../gigreps/'.$filename,$data)) echo "<script>if (parent.ifrp) parent.ifrp.location.reload();location.href='composer.php?gig=".$filename."';</script>";
else echo '<script>alert("Could not save. The server must have write permissions on the folder gigreps and on the file '.$filename.' (e. g. chmod 777).")</script>';
?>

</body>
</html>
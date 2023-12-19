<?php
include('iniset.inc.php');
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
setcookie('rezip', basename($_POST['name']), time()+$timeout, '/');
auth('upaudio');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
</head>

<body id="body" bgcolor="#009900" text="#FFFFFF" style="font-family:Arial, Helvetica, sans-serif">
<?php 
$time=date('YmdHis');
$ext=explode('.',$_FILES['userfile']['name']);
$ext=$ext[count($ext)-1];

$preparedfilename=str_replace('..','.',mkascii(striptime($_FILES['userfile']['name'])));

if ($ext!='mp3') die('<script>document.getElementById("body").style.background="red";alert("Only mp3-files are allowed. The extension .mp3 must be lower case (prepare the correct file name locally before uploading)");parent.location.href=parent.location.href;</script>');
if ($ext=='txt') die('<script>document.getElementById("body").style.background="red";alert(".txt files are not allowed (they are used by the system)");parent.location.href=parent.location.href;</script>');

if (!move_uploaded_file($_FILES['userfile']['tmp_name'], '../library/'.basename($_POST['name']).'/'.$time.$preparedfilename)) {
echo '<script>document.getElementById("body").style.background="red";alert("Error, no file uploaded");parent.location.href=parent.location.href;</script>';
}
else {
rename('../library/'.basename($_POST['name']).'/'.$preparedfilename,'../library/'.basename($_POST['name']).'/zzDEL!'.$time.$preparedfilename);
rename('../library/'.basename($_POST['name']).'/'.$time.$preparedfilename,'../library/'.basename($_POST['name']).'/'.$preparedfilename);
echo '<script>
if(parent)if(parent.opener)parent.opener.fresh();
parent.location.href=parent.location.href;
</script>';
}
?>
</body>
</html>
<?php require_once __DIR__.'/legacy-write.inc.php'; ?>
<?php
include('iniset.inc.php');
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('mgigs');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../style.css"><title>Upload files - NADIN</title>
</head>

<body id="body" class="nadin-media-frame">
<?php 
$time=date('YmdHis');
$ext=explode('.',$_FILES['userfile']['name']);
$ext=$ext[count($ext)-1];

if (strtolower($ext)!='pdf') die('<script>document.getElementById("body").style.background="red";alert("Only pdf files are allowed!");parent.location.href=parent.location.href;</script>');

if (!move_uploaded_file($_FILES['userfile']['tmp_name'], '../abo/zip/'.$_GET['gig'].'.'.strtolower($ext))) {
echo '<script>document.getElementById("body").style.background="red";alert("Error, no file uploaded");parent.location.href=parent.location.href;</script>';
}
else {
echo '<script>
if(parent)if(parent.opener)parent.opener.parent.ifrp.fresh();
parent.location.href=parent.location.href;
</script>';
}
?>
</body>
</html>
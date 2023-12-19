<?php require('refreshcookies2.inc.php') ?>
<?php require_once('functions.inc.php')?>
<?php setcookie('i', $_GET['i'], time()+$timeout, '/'); ?>
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
auth('upaudio');
$foldername=basename($_POST['foldername']);
$data=trim($_POST['infos']."\r\n".$delimiter2."\r\n".$_POST['links']);
$data=str_replace("\r\n\r\n\r\n","\r\n\r\n",$data);
$data=str_replace("\n\n\n","\n\n",$data);
if (file_put_contents('../library/'.$foldername.'/infos.txt',$data)) echo "ok<script>

parent.document.getElementById('save').style.background='lightgreen';
parent.document.getElementById('save').style.color='black';
parent.document.getElementById('save').innerHTML='save';
parent.document.getElementById('save').title='save [Ctrl s]';
parent.document.getElementById('changes').value=0;
if(parent)if(parent.opener)parent.opener.fresh();
parent.startmyfocus();
</script>";
else echo '<script>alert("Could not save. The server must have write permissions in the folder library recursively including all subfolders and files (e. g. chmod 777).")</script>';
?>
</body>
</html>
<?php
include('iniset.inc.php');
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
setcookie('rezip', basename($_GET['p']), time()+$timeout, '/');
auth('uppdf');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
</head>
<body>

<?php 

$time=date('YmdHis');
$file=basename($_GET['f']);
$path=basename($_GET['p']);
$new=basename($_GET['n']);
if ($new=='null') die('<script>alert(\'Transaction cancelled, nothing changed.\');parent.location.href=parent.location.href</script>');
if (strpos($new,'.')<1) die('<script>alert(\'Invalid file name!\');parent.location.href=parent.location.href</script>');

$ext=explode('.',$file);
$ext=$ext[count($ext)-1];
if ($ext=='mp3') die('<script>alert("Do not upload audio files here. Audio files do have a dedicated upload page.");parent.location.href=parent.location.href;</script>');

$ext=explode('.',$new);
$ext=$ext[count($ext)-1];
if ($ext=='mp3') die('<script>alert("Do not upload audio files here. Audio files do have a dedicated upload page.");parent.location.href=parent.location.href;</script>');


if (file_exists('../library/'.$path.'/'.mkascii($new))) die('<script>alert(\'File name already exists!\');parent.location.href=parent.location.href</script>');

?>
<script>
if(parent.location.href.indexOf('&m')>0)palohref=parent.location.href.split('&m')[0]+'&m=<?php echo mkid2($_GET['n'])?>';
else palohref=parent.location.href;
</script>
<?php 
if (rename('../library/'.$path.'/'.$file,'../library/'.$path.'/'.mkascii($new))) echo '<script>
if(parent)if(parent.opener)parent.opener.fresh();
parent.location.href=palohref;
</script>';
else echo '<script>alert("ERROR: Could not rename. Is the server allowed to write there (rights)?");parent.location.href=parent.location.href</script>';

?>
</body>
</html>

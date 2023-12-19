<?php require ('refreshcookies2.inc.php') ?>
<?php require_once('functions.inc.php'); ?>
<?php setcookie('i', '', time()+$timeout, '/'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script src="../shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<link rel="icon" href="../favicon.ico" type="image/x-icon" />
<title>manage <?php echo htmlXspecialchars(str_replace('_',' ',$_GET['tune'])) ?> - NADIN</title>
<?php 
require_once('../config.inc.php');
auth('uppdf');
?></head>

<body>

<?php 
echo '<h1>'.htmlXspecialchars(str_replace('_',' ',$_GET['tune'])).'</h2>';
?>

<?php require('uploadnavi.inc.php'); ?>

<h2>Delete result</h1>
<?php 

$tune=mkascii($_POST['tune']);

if (strlen($tune)<3) die('<script>alert("The name must be at least 3 characters long.");history.go(-1);</script>');

if ($_POST['tune']!=$_GET['tune']) die('<script>alert("The tune you partially entered manually\n'.htmlXspecialchars($tune).' does not match the tune you clicked before\n'.htmlXspecialchars($_GET['tune']).'");history.go(-1);</script>');

if (!file_exists('../library/'.$_GET['tune'])) die('<script>alert("The tune (source name) '.htmlXspecialchars($_GET['tune']).' does not exist.");history.go(-1);</script>');

if (file_exists('../library/'.basename($_GET['tune']).'/infos.txt')) {

if (!rename('../library/'.basename($_GET['tune']).'/infos.txt','../filesdeleted/'.$tune.'.txt')) die('<script>alert("SYSTEM PROBLEM: infos.txt of '.htmlXspecialchars($_GET['tune']).' should have been temporarely moved to the folder filesdeleted what was not possible. Does the server have rights to write into the folder gigrepsdeleted and into the file infos.txt?");history.go(-1);</script>');
}

if (rmdir('../library/'.$tune)) echo '<script>
if(opener)opener.fresh();
self.close();
</script>';
else {
echo 'Could not delete.<p>Possible reasons: Folder <b>'.htmlXspecialchars($tune).'</b> not empty or the server has no rights to write in this folder.';

if (file_exists('../filesdeleted/'.$tune.'.txt') && !rename('../filesdeleted/'.$tune.'.txt','../library/'.basename($_GET['tune']).'/infos.txt')) die('<script>alert("SYSTEM PROBLEM: infos.txt of '.htmlXspecialchars($_GET['tune']).' could not be moved back from folder filesdeleted. Does the server have write permissions in the folder gigrepsdeleted and the mentioned infos.txt?");history.go(-1);</script>');
}

?>
</body>
</html>

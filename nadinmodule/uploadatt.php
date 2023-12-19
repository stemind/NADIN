<?php require('refreshcookies2.inc.php') ?>
<?php require_once('functions.inc.php')?>
<?php
require_once('../config.inc.php');
setcookie('u', $_COOKIE['u'], time()+$timeout, '/');
setcookie('p', $_COOKIE['p'], time()+$timeout, '/');
?>
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
<title>manage <?php echo htmlXspecialchars(str_replace('.txt','',str_replace('_',' ',$_GET['gig']))) ?> - NADIN</title>
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('mgigs');
?></head>

<body>

<?php 
echo '<h1>'.htmlXspecialchars(str_replace('.txt','',str_replace('_',' ',$_GET['gig']))).'</h1>';?>


<h2>Existing pdf attachment</h2>
<iframe src="ATTfiles.php?gig=<?php echo str_replace('.txt','',$_GET['gig'])?>" name="files" frameborder="0" width="100%" height="220"></iframe>


<h2>Upload / replace pdf attachment</h2>
<iframe src="ATTsendfile.php?gig=<?php echo str_replace('.txt','',$_GET['gig'])?>" frameborder="0" width="100%" height="88"></iframe>
<span style="font-size:70%"><?php include('iniget.inc.php')?></span>
<p>
<script>document.title=document.title.replace(/manage/,'<?php echo $bb ?> - ')</script>
</body>
</html>

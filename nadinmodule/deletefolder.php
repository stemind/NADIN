<?php 
setcookie('upos', 'deletefolder', time()+3600*24*365*10, '/');
require('refreshcookies2.inc.php');
require_once('functions.inc.php');
auth('uppdf');
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
<title>manage <?php echo htmlXspecialchars(str_replace('_',' ',$_GET['tune'])) ?> - NADIN</title>
<?php 
require_once('../config.inc.php');

?></head>

<body>

<?php 
echo '<h1>'.htmlXspecialchars(str_replace('_',' ',$_GET['tune'])).'</h2>';
?>

<?php require('uploadnavi.inc.php'); ?>

<h2>Delete <?php echo htmlXspecialchars($_GET['tune'])?></h1>

<form name="form" action="deletefolder2.php?tune=<?php echo rawurlencode($_GET['tune'])?>" method="post">

<p>Only an empty tune that is no longer used in a setlist can be removed. Its folder is kept in the deleted-files folder.</p>
<?php echo nadinPathField('tune', $_GET['tune']); ?>
<button type="submit">Delete empty tune</button>

</form>



</body>
</html>

<?php require __DIR__.'/legacy-ui.inc.php'; ?>

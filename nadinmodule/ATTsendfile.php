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
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<script src="../shortcut.js"></script>
<script>
shortcut.add("Ctrl+S",function() {
	if(document.form)document.form.submit();
})
</script>
</head>

<body>
<form name="form1" enctype="multipart/form-data" action="ATTsendfile2.php?gig=<?php echo $_GET['gig']?>" method="post">
<!--<input type="hidden" name="MAX_FILE_SIZE" value="999999999999" />-->
<?php require('submit.inc.php') ?>
<input name="name" type="hidden" size="44" value="<?php echo $_GET['name']?>" />
<!--<input type="submit" name="submit" value="upload "/>-->
</form>
</body>

</html>

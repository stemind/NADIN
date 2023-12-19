<?php require ('refreshcookies2.inc.php');?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="../style.css" />
<link rel="icon" href="../favicon.ico" type="image/x-icon" />
<title>New tune - NADIN</title>
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('uppdf');
?></head>

<body>
<h1>Create new folder</h1>
<div style="position:absolute;top:0px;left:10px;color:red;background:#ddd;height:27px;width:25px;text-align:center;cursor:pointer;font-size:20px" onmouseover="this.style.fontWeight='bold';this.style.background='#ccc';" onmouseout="this.style.fontWeight='100';this.style.background='#ddd';" onclick="self.close()">&#215;</div>
<form name="form" action="new2.php" method="post">

Tune name:

<input type="text" name="tune" size="50" />

<input type="submit" value="create" />

</form>

<script>
function myopener() {
<?php if($autoclosepopups=='yes')echo'if(!opener)self.close();'?>
setTimeout("myopener()",777);
}
setTimeout("myopener()",777);

document.form.tune.focus();
</script>
<script>document.title=document.title='<?php echo $bb ?> - '+document.title</script>
</body>
</html>

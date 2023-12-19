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
</head>
<body>
<?php 
$time=date('YmdHis');
$file=basename($_GET['f']);
$path=basename($_GET['p']);

$ext=explode('.',$file);
$ext=$ext[count($ext)-1];

if(trim($file)!='') {
rename('../abo/zip/'.$file,'../filesdeleted/'.$time.'!'.$uc.'!'.$file);
unlink('../abo/zip/'.$file);
}
?>
<script>
if(parent)if(parent.opener)parent.opener.parent.ifrp.fresh();
parent.location.href=parent.location.href;
</script>
</body>
</html>

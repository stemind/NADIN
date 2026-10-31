<?php require ('refreshcookies2.inc.php') ?>
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
auth('mgigs');
$filename=basename($_POST['gig']);
$previous=is_file('../gigreps/'.$filename)?file_get_contents('../gigreps/'.$filename):'';
$previous=explode($delimiter2,$previous,2);
try { $_POST['tunes']=nadinSetlistBytes($_POST['tunes'],isset($previous[1])?$previous[1]:'',dirname(__DIR__).'/library'); }
catch (DomainException $e) { http_response_code(409);exit(htmlXspecialchars($e->getMessage())); }

$data=trim($_POST['infos']."\r\n".$delimiter2."\r\n".$_POST['tunes']);
$data=str_replace("\r\n\r\n\r\n","\r\n\r\n",$data);
$data=str_replace("\n\n\n","\n\n",$data);
if (file_put_contents('../gigreps/'.$filename,$data)) echo "<script>if (parent.ifrp) parent.ifrp.location.reload();location.href='composer.php?gig=".rawurlencode($filename)."';</script>";
else echo '<script>alert("Could not save. The server must have write permissions on the folder gigreps and on the file '.$filename.' (e. g. chmod 777).")</script>';
?>

</body>
</html>
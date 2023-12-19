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
$trx=$_GET['trx'];
$new=str_replace('.txt','',mkname($_GET['new'])).'.txt';
$old=basename($_GET['old']);

///////////////////////////////////////////////////

if ($trx=='anlegen') {
if (strlen($new)<7) die('<script>alert("Name too short");history.go(-1);</script>');
if (file_exists('../gigreps/'.$new)) die('<script>location.replace("composer.php?gig='.$new.'")</script>');	
$data=trim('Created on '.date('Y-m-d H:i:s')."\r\n".$delimiter2."\r\n".' ');
if (file_put_contents('../gigreps/'.$new,$data)) echo "<script>if (parent.ifrp) parent.ifrp.location.reload();location.href='composer.php?gig=".$new."';</script>";
else echo 'Could not save. The server must have write permissions on the folder gigreps and the file '.$new.' (e. g. chmod 777).';
}

///////////////////////////////////////////////////
if ($trx=='umtaufen') {
if (strlen($new)<7) die('<script>alert("Name too short");history.go(-1);</script>');
if (file_exists('../gigreps/'.$new)) die('<script>location.replace("composer.php?gig='.$new.'")</script>');	
if (rename('../gigreps/'.$old,'../gigreps/'.$new)) {
echo "<script>if (parent.ifrp) parent.ifrp.location.reload();location.href='composer.php?gig=".$new."';</script>";
rename('../abo/zip/'.str_replace('.txt','.pdf',$old),'../abo/zip/'.str_replace('.txt','.pdf',$new));
}
else echo 'Could not rename. The server must have write permissions on the folder gigreps and the file '.$new.'(e. g. chmod 777).';
}

///////////////////////////////////////////////////
if ($trx=='kopieren') {
if (strlen($new)<7) die('<script>alert("Name too short");history.go(-1);</script>');
if (file_exists('../gigreps/'.$new)) die('<script>location.replace("composer.php?gig='.$new.'")</script>');	
if (copy('../gigreps/'.$old,'../gigreps/'.$new)) {
copy('../abo/zip/'.str_replace('.txt','.pdf',$old),'../abo/zip/'.str_replace('.txt','.pdf',$new));
echo "<script>if (parent.ifrp) parent.ifrp.location.reload();location.href='composer.php?gig=".$new."';</script>";
}
else echo 'Could not copy. The server must have write permissions on the folder gigreps and on the file '.$new.' (e. g. chmod 777).';
}

///////////////////////////////////////////////////
if ($trx=='loeschen') {
if (rename('../gigreps/'.$old,'../gigrepsdeleted/'.(time()).'_'.$old)) echo "<script>if (parent.ifrp) parent.ifrp.location.reload();location.href='composer.php?gig=".$new."';</script>";
else echo 'Could not delete. The server must have write permissions in the folder gigrepsdeleted (z. B. chmod 777) haben.';
}

///////////////////////////////////////////////////
if ($trx=='archivieren') {
if (file_exists('../gigrepsarchiv/'.$old)) die('<script>alert("There is already a gig with this name in the archive!");history.go(-1)</script>');	
if (rename('../gigreps/'.$old,'../gigrepsarchiv/'.$old)) echo "<script>if (parent.ifrp) parent.ifrp.location.reload();location.href='composer.php?gig=".$new."';alert('".htmlXspecialchars(str_replace('.txt','',$old))."\\n\\nsuccessfully archived.');</script>";
else echo 'Could not archive. The server must have write permissions in the folder gigrepsarchiv (e. g. chmod 777).';
}

///////////////////////////////////////////////////
if ($trx=='desarchivieren') {
if (file_exists('../gigreps/'.$old)) die('<script>alert("There is already a current gig with this name!");history.go(-1)</script>');	
if (rename('../gigrepsarchiv/'.$old,'../gigreps/'.$old)) echo "<script>if (parent.ifrp) parent.ifrp.location.reload();location.href='composer.php?gig=".$old."';</script>";
else echo 'Could not de-archive. The server must have write permissions in the folder gigreps (e. g. chmod 777).';
}

?>

</body>
</html>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<link rel="stylesheet" type="text/css" href="style.css" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<title>NADIN - Login</title>
<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
sleep(1);
writelog('order;'.cleancsv($up).';'.cleancsv($_POST['c']));
?>
</head>
<body>
<img align="right" src="nadink.gif" width="40" height="40" />
<h1>NADIN</h1>

<h2>E-mail sent to <?php echo $up ?></h2>

<?php
if (str_replace('.','',$up)==$up) die('<script>alert("wrong e-mail address");history.go(-1);</script>');
if (trim($_POST['c'])!=date('d')) die('<script>alert("wrong checking number");history.go(-1);</script>');

if (strpos($users,','.$up.',')>0) {

zmail($up,'Your NADIN Login','
Welcome to NADIN

Your access to NADIN is personal and must not be accessible to third parties. All data available in NADIN (sheet music and audio files) must only be used in the educational context of '.$bb.'. Other publication or usage only by written consent of '.$bb.'.

Your username is

'.$up.'


your password is

'.$pcrcp.' 


The URL of NADIN is

'.$homepage.'


Regards, '.$bb.'
');
}

else {
zmail($up,'ERROR: Your NADIN login','
Thank you for ordering a login for NADIN.

As e-mail address you indicated

'.$up.'

Unfortunately this e-mail address is not registered in our system. For registration, you have to use exactly the e-mail address which we know about you. 

Perhaps none of your e-mails is prepared to be used in the system? If you think that is the case, please contact a mamber of '.$bb.'.


Regards, '.$bb.'
');
}

function zmail($t,$s,$m) {
global $mymail;
global $usesmtp;
global $smtpservername;
global $mysmtpconfigdata;
global $doublepasswordmailing;

$zendfrom=$mymail;
$zendname=$mymail;
$zendto=$t;
$zendsubject=$s;
$zendmessage=$m;
mail($t,$s,$m);
}

?>

An e-mail has been sent to you. Please check your inbox. If the message is overdue, check your spam folder too.
<p>
<a href="login.php">next</a>
</body>
</html>
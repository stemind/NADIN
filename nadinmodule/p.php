<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<title>Logins</title>
</head>
<body style="font-family:'Courier New', Courier, monospace">
<?php 
require_once('../config.inc.php');
require_once('functions.inc.php');
auth('musers');
$items=str_replace("\n",',',$users);
$items=explode(',',$items);

$echo='';

for($i=0;$i<count($items);$i++) {
if (strpos($items[$i],'@')>0) $echo.=$items[$i].',';
}

$items = explode(',',$echo);

sort($items);
$hts='';
for($i=1;$i<count($items);$i++) {

$items[$i]=cleanusers($items[$i]);

$pw=nadinhash($items[$i].$salt);

$uc=$items[$i];

if (role('vgigs')) {
echo $pw.'&nbsp;'.$items[$i].'<br>';
$hts.=$items[$i].':'.pwdhsh($pw)."\r\n";
}


if (role('vscore')) {
$hts_allscore.=$items[$i].':'.pwdhsh($pw)."\r\n";
}

if (role('vsaxes')) {
$hts_allsaxes.=$items[$i].':'.pwdhsh($pw)."\r\n";
}

if (role('vbones')) {
$hts_allbones.=$items[$i].':'.pwdhsh($pw)."\r\n";
}

if (role('vtrumpets')) {
$hts_alltrumpets.=$items[$i].':'.pwdhsh($pw)."\r\n";
}

if (role('vrhythm')) {
$hts_allrhythm.=$items[$i].':'.pwdhsh($pw)."\r\n";
}

if (role('vother')) {
$hts_allother.=$items[$i].':'.pwdhsh($pw)."\r\n";
}



}


if (file_put_contents("../userdatei/.htpasswd",$hts)) echo "<script>if (parent.parent.document.getElementById('save')) parent.parent.document.getElementById('save').style.fontWeight='normal';</script>";
else echo '<script>alert("Could not write userdatei/.htpasswd; The Server must have write permissions in the folder userdatei (recursively incl. .htpasswd, e. g. chmod 777)")</script>';

mkdir('../userdatei/alllistento');
mkdir('../userdatei/allsaxes');
mkdir('../userdatei/allbones');
mkdir('../userdatei/alltrumpets');
mkdir('../userdatei/allother');
mkdir('../userdatei/allrhythm');
mkdir('../userdatei/allscore');
mkdir('../userdatei/allparts');

file_put_contents("../userdatei/alllistento/.htpasswd",$hts);
file_put_contents("../userdatei/allscore/.htpasswd",$hts_allscore);
file_put_contents("../userdatei/allparts/.htpasswd",$hts);
file_put_contents("../userdatei/allsaxes/.htpasswd",$hts_allsaxes);
file_put_contents("../userdatei/allbones/.htpasswd",$hts_allbones);
file_put_contents("../userdatei/alltrumpets/.htpasswd",$hts_alltrumpets);
file_put_contents("../userdatei/allrhythm/.htpasswd",$hts_allrhythm);
file_put_contents("../userdatei/allother/.htpasswd",$hts_allother);




if (file_put_contents('../abo/x','x')) {
echo '<!--w ../r/ ok -->';
unlink('../abo/x');
}
else echo '<script>alert("PROBLEM: The server does not have the right to write into the folder named \'abo\'.\n\nEFFECTS: The zip files cannot be created.\n\nSOLUTION: CHMOD this folder RECURSIVELY (incl. all its files, subfolders a.s.o.) e. g. to 777 e. g. with FileZilla.");</script>';

mkdir('../abo/zip/alllistento');
mkdir('../abo/zip/allsaxes');
mkdir('../abo/zip/allbones');
mkdir('../abo/zip/alltrumpets');
mkdir('../abo/zip/allother');
mkdir('../abo/zip/allrhythm');
mkdir('../abo/zip/allscore');
mkdir('../abo/zip/allparts');

mkdir('../userdatei/alllistento');
$t='AuthName "Dein NADIN Login bitte"
AuthType Basic
AuthUserFile "'.str_replace('nadinmodule/p.php','userdatei/alllistento/',$_SERVER['SCRIPT_FILENAME']).'.htpasswd"';
file_put_contents('../abo/zip/alllistento/.htaccess',$t);

mkdir('../userdatei/allscore');
$t='AuthName "Dein NADIN Login bitte"
AuthType Basic
AuthUserFile "'.str_replace('nadinmodule/p.php','userdatei/allscore/',$_SERVER['SCRIPT_FILENAME']).'.htpasswd"';
file_put_contents('../abo/zip/allscore/.htaccess',$t);

mkdir('../userdatei/allparts');
$t='AuthName "Dein NADIN Login bitte"
AuthType Basic
AuthUserFile "'.str_replace('nadinmodule/p.php','userdatei/allparts/',$_SERVER['SCRIPT_FILENAME']).'.htpasswd"';
file_put_contents('../abo/zip/allparts/.htaccess',$t);

mkdir('../userdatei/allsaxes');
$t='AuthName "Dein NADIN Login bitte"
AuthType Basic
AuthUserFile "'.str_replace('nadinmodule/p.php','userdatei/allsaxes/',$_SERVER['SCRIPT_FILENAME']).'.htpasswd"';
file_put_contents('../abo/zip/allsaxes/.htaccess',$t);

mkdir('../userdatei/allbones');
$t='AuthName "Dein NADIN Login bitte"
AuthType Basic
AuthUserFile "'.str_replace('nadinmodule/p.php','userdatei/allbones/',$_SERVER['SCRIPT_FILENAME']).'.htpasswd"';
file_put_contents('../abo/zip/allbones/.htaccess',$t);

mkdir('../userdatei/alltrumpets');
$t='AuthName "Dein NADIN Login bitte"
AuthType Basic
AuthUserFile "'.str_replace('nadinmodule/p.php','userdatei/alltrumpets/',$_SERVER['SCRIPT_FILENAME']).'.htpasswd"';
file_put_contents('../abo/zip/alltrumpets/.htaccess',$t);

mkdir('../userdatei/allrhythm');
$t='AuthName "Dein NADIN Login bitte"
AuthType Basic
AuthUserFile "'.str_replace('nadinmodule/p.php','userdatei/allrhythm/',$_SERVER['SCRIPT_FILENAME']).'.htpasswd"';
file_put_contents('../abo/zip/allrhythm/.htaccess',$t);

mkdir('../userdatei/allother');
$t='AuthName "Dein NADIN Login bitte"
AuthType Basic
AuthUserFile "'.str_replace('nadinmodule/p.php','userdatei/allother/',$_SERVER['SCRIPT_FILENAME']).'.htpasswd"';
file_put_contents('../abo/zip/allother/.htaccess',$t);

?>
</body>
</html>

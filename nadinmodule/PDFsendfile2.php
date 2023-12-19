<?php
include('iniset.inc.php');
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
setcookie('rezip', basename($_POST['name']), time()+$timeout, '/');
auth('uppdf');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
</head>

<body id="body" bgcolor="#009900" text="#FFFFFF" style="font-family:Arial, Helvetica, sans-serif">
<?php 

$file_ary = reArrayFiles($_FILES['userfile']);


if(count($file_ary)>ini_get('max_file_uploads')-1)die('<script>document.getElementById("body").style.background="red";alert("NOTHING UPLOADED. Reason: You tried to upload more than '.(ini_get('max_file_uploads')-1).' files at once.");parent.location.href=parent.location.href</script>');


foreach ($file_ary as $file) {
$ext=explode('.',$file['name']);
$ext=$ext[count($ext)-1];
if ($ext=='mp3') die('<script>document.getElementById("body").style.background="red";alert("NOTHING UPLOADED because your upload contained one or several mp3-files. Do not upload audio files here. Audio files do have a dedicated upload page.");parent.location.href=parent.location.href;</script>');
if ($ext=='txt') die('<script>document.getElementById("body").style.background="red";alert("NOTHING UPLOADED because your upload contained one or several txt-files which are forbidden because the system creates them.");parent.location.href=parent.location.href;</script>');

require('../configallowedfileextensions.php');
if (!in_array(strtolower($ext),$okext)) die('<script>document.getElementById("body").style.background="red";alert("NOTHING UPLOADED because your upload contained one or several files with the extension '.$ext.' which is not white-listed in configallowedfileextensions.php.");parent.location.href=parent.location.href;</script>');
}

$started=0;
foreach ($file_ary as $file) {
$started++;
if($started>=count($file_ary))doneandrefresh();
$time=date('YmdHis');
$ext=explode('.',$file['name']);
$ext=$ext[count($ext)-1];

if ($ext=='mp3') die('<script>document.getElementById("body").style.background="red";alert("Do not upload audio files here. Audio files do have a dedicated upload page.");parent.location.href=parent.location.href;</script>');
if ($ext=='txt') die('<script>document.getElementById("body").style.background="red";alert("txt files are forbidden because the system creates them.");parent.location.href=parent.location.href;</script>');

require('../configallowedfileextensions.php');
if (!in_array(strtolower($ext),$okext)) die('<script>document.getElementById("body").style.background="red";alert("This file extension is not white-listed in configallowedfileextensions.php.");parent.location.href=parent.location.href;</script>');

if (!move_uploaded_file($file['tmp_name'], '../library/'.basename($_POST['name']).'/'.$time.mkascii($file['name']))) {
echo '<script>document.getElementById("body").style.background="red";alert("Error, no file uploaded");parent.location.href=parent.location.href;</script>';
}
else {
rename('../library/'.basename($_POST['name']).'/'.mkascii($file['name']),'../library/'.basename($_POST['name']).'/zzDEL!'.$time.mkascii($file['name']));
rename('../library/'.basename($_POST['name']).'/'.$time.mkascii($file['name']),'../library/'.basename($_POST['name']).'/'.mkascii($file['name']));

}
}

function doneandrefresh() {
echo '<script>setTimeout("if(parent)if(parent.opener)parent.opener.fresh();parent.location.href=parent.location.href;",1)</script>';
}

function reArrayFiles(&$file_post) {

    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }

    return $file_ary;
}
?>
</body>
</html>
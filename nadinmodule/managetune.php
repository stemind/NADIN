<?php 
$_COOKIE['upos'] =$_COOKIE['upos'] ?? '';
if ($_COOKIE['upos']=='upload') header("Location: upload.php?".$_SERVER['QUERY_STRING']);
else if ($_COOKIE['upos']=='uploadaudio') header("Location: uploadaudio.php?".$_SERVER['QUERY_STRING']);
else if ($_COOKIE['upos']=='uploadpdf') header("Location: uploadpdf.php?".$_SERVER['QUERY_STRING']);
else if ($_COOKIE['upos']=='deletefolder') header("Location: deletefolder.php?".$_SERVER['QUERY_STRING']);
else if ($_COOKIE['upos']=='renamefolder') header("Location: renamefolder.php?".$_SERVER['QUERY_STRING']);
else header("Location: uploadaudio.php?".$_SERVER['QUERY_STRING']);
?>
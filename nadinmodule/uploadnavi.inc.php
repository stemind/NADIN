<script>document.title=document.title.replace(/manage/,'<?php echo $bb ?> - ')</script>
<div style="position:absolute;top:0px;left:10px;color:red;background:#ddd;height:27px;width:25px;text-align:center;cursor:pointer;font-size:20px" onmouseover="this.style.fontWeight='bold';this.style.background='#ccc';" onmouseout="this.style.fontWeight='100';this.style.background='#ddd';" onclick="self.close()">&#215;</div>
<?php
$marker='';
if(isset($_GET['m']))$marker='&m='.$_GET['m'];
if (str_replace(' ','',trim($_GET['tune']))!=$_GET['tune']) $leers='By the way: There is whitespace in the name that could be problematic.';
if (str_replace(' ','',trim($_GET['tune']))=='' || !file_exists('../library/'.$_GET['tune']))echo'<script>alert("'.htmlXspecialchars($_GET['tune']).'\\n\\ndoes not exist in\\n\\n'.$bibliothek.'\\n\\nor it is not spelled identically.\\n\\nTo enable uploads, you must create the corresponding object there.\\n\\n'.$leers.'");self.close();</script>';
?>
<a style="text-decoration:underline" href="upload.php?tune=<?php echo $_GET['tune'].$marker?>" id="upload">infos &amp; links</a>

&nbsp;&nbsp;

<a style="text-decoration:underline" href="uploadaudio.php?tune=<?php echo $_GET['tune'].$marker?>" id="uploadaudio">audio files</a>

&nbsp;&nbsp;

<?php if (role('uppdf')) {?>

<a style="text-decoration:underline" href="uploadpdf.php?tune=<?php echo $_GET['tune'].$marker?>" id="uploadpdf">PDFs &amp; other files</a>

&nbsp;&nbsp;

Folder:

<a style="text-decoration:underline" href="renamefolder.php?tune=<?php echo $_GET['tune'].$marker?>" id="renamefolder">rename</a>

|

<a style="text-decoration:underline" href="deletefolder.php?tune=<?php echo $_GET['tune'].$marker?>" id="deletefolder">delete</a>

<?php } ?>

<?php 
if($allowmultiplepopups=='yes')echo'<span style="font-size:9px;float:right">multi popups<input id="multipop" type="checkbox" onchange="if(this.checked){document.cookie=\'multipop=yes; expires=Thu, 31 Dec 2037 12:00:00 GMT; path=/\';multipopcookie=\'yes\'}else {document.cookie=\'multipop=no; expires=Thu, 31 Dec 2037 12:00:00 GMT; path=/\';multipopcookie=\'no\'};if(opener)opener.fresh()" /></span>';if($_COOKIE['multipop']=='yes')echo"<script>if(document.getElementById('multipop'))document.getElementById('multipop').checked=true</script>";
?>

<script>
if (location.href.indexOf('upload.php')>0) document.getElementById('upload').style.background='yellow';
if (location.href.indexOf('uploadaudio.php')>0) document.getElementById('uploadaudio').style.background='yellow';
if (location.href.indexOf('uploadpdf.php')>0) document.getElementById('uploadpdf').style.background='yellow';
if (location.href.indexOf('renamefolder.php')>0) document.getElementById('renamefolder').style.background='yellow';
if (location.href.indexOf('deletefolder.php')>0) document.getElementById('deletefolder').style.background='yellow';

if (location.href.indexOf('upload2.php')>0) document.getElementById('upload').style.background='yellow';
if (location.href.indexOf('uploadaudio2.php')>0) document.getElementById('uploadaudio').style.background='yellow';
if (location.href.indexOf('uploadpdf2.php')>0) document.getElementById('uploadpdf').style.background='yellow';
if (location.href.indexOf('renamefolder2.php')>0) document.getElementById('renamefolder').style.background='yellow';
if (location.href.indexOf('deletefolder2.php')>0) document.getElementById('deletefolder').style.background='yellow';

function myfocus() {
self.focus();
}

function startmyfocus() {
setTimeout("myfocus()",1111)
}
setTimeout("myfocus()",333)

<?php if($_GET['m']=='')$_GET['m']=mkid2($_GET['tune']);?>

multipopcookie='<?php echo $_COOKIE['multipop'] ?>';

function openerhilite(that) {
<?php if($autoclosepopups=='yes')echo'if(!opener)self.close();'?>
if(opener)if(opener.document.getElementById('loadmarker')) opener.openerhilite(that);
if(multipopcookie!='yes')setTimeout("openerhilite('<?php echo $_GET['m']?>')",777)
}
if(multipopcookie!='yes')setTimeout("openerhilite('<?php echo $_GET['m']?>')",777)

function openerhilite2(that) {
if(opener)if(opener.document.getElementById('loadmarker')) opener.openerhilite2(that);
if(multipopcookie!='yes')setTimeout("openerhilite2('<?php echo $_GET['m']?>')",666)
if(multipopcookie!='yes')setTimeout("openerhilite2('<?php echo $_GET['m']?>')",888)
}
</script>
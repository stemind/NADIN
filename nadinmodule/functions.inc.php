<?php
require_once __DIR__.'/encoding.inc.php';
nadinRestorePathFields();
ini_set('default_charset', 'UTF-8');
foreach (array('humansaxes','humanbones','humantrumpets','humanrhythm','humanother','humanparts','humanscore','humantherec','humanfilesandlinks','humaninfos','aktuellegigs','archiviertegigs','bbaufnahmen','bibliothek') as $nadinLabel) {
    if (isset($GLOBALS[$nadinLabel]) && is_string($GLOBALS[$nadinLabel])) $GLOBALS[$nadinLabel]=nadinText($GLOBALS[$nadinLabel]);
}
unset($nadinLabel);

if ($autoresolvefastcgiproblems=='yes') {
if ($basicauthvarname=='') $basicauthvarname='HTTP_AUTHORIZATION';
list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER[$basicauthvarname], 6)));
}

/////////////////////////////////////////////////////////////////////
function edoced_8ftu($string) {
    return mb_convert_encoding($string ?? '', 'ISO-8859-1', 'UTF-8');
}


/////////////////////////////////////////////////////////////////////

function mkid($s) {
$t='()ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 -_';
$s=trim($s);
$n='';
for ($i=0;$i<strlen($s);$i++) {
if (str_replace($s[$i],'',$t)!=$t) $n.=$s[$i];
}
return 'i'.str_replace('(','O_',str_replace(')','_C',str_replace(' ','_',$n)));	
}

function mkid2($s) {
$t='()ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 -_';
$s=trim($s);
$n='';
for ($i=0;$i<strlen($s);$i++) {
if (str_replace($s[$i],'',$t)!=$t) $n.=$s[$i];
}
return 'ii'.str_replace('(','O_',str_replace(')','_C',str_replace(' ','_',$n)));	
}


function mkname($s) {
$t='()ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 -_';
$s=trim($s);
$n='';
for ($i=0;$i<strlen($s);$i++) {
if (str_replace($s[$i],'',$t)!=$t) $n.=$s[$i];
}
return str_replace('(','O_',str_replace(')','_C',str_replace(' ','_',$n)));	
}

function nadinhash($p) {
return substr(sha1($p),7,6);
}

function mkfilename($s) {
$t='()ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.-_';
$s=trim($s);
$n='';
for ($i=0;$i<strlen($s);$i++) {
if (str_replace($s[$i],'',$t)!=$t) $n.=$s[$i];
}
return $n;	
}

function mkascii($s) {
$s=edoced_8ftu($s);
$badascii = array(" ",":","\xA6","|","@" ,"&","\xA2","\xA3","\xA4","\xA5","\x80","\xA7","\xA8","\xA9","\xAA","\xAC","\xAE","\xAF","\xB0","\xB1","\xB4","\xB5","\xB6","\x85","\xB7","\xB8","\xBA","\xF7","\x86","\xDF" ,"\x84","\x93","\x94","\x82","\x91","\x92","\xAB","\xBB","\x8B","\x9B","\xA1","\xBF","\x96","\xC0","\xC1","\xC2","\xC3","\xC4" ,"\xC5","\xC6" ,"\xC7","\xC8","\xC9","\xCA","\xCB","\xCC","\xCD","\xCE","\xCF","\xD1","\xD2","\xD3","\xD4","\xD5","#","\xD6", "\xD8","\x8C" ,"\xD9","\xDA","\xDB","\xDC", "\xE0","\xE1","\xE2","\xE3","\xE4", "\xE5","\xE6" ,"\xE7","\xE8","\xE9","\xEA","\xEB","\xEC","\xED","\xEE","\xEF","\xF1","\xF2","\xF3","\xF4","\xF5","#","\xF6", "\xF8","\x9C", "\xF9","\xFA","\xFB","\xFC", "\xFF","'","%","/","\\","<",">",   ",",   ";","+","=","[","]","{","}","*","?","!","`","\"");
$goodascii= array("_","", "", "", "AT","u","c","L","", "Y","E","S","","c","a","N","R","", "o","pm","", "u","P","", "", "", "o","", "", "ss","", "", "", "", "", "", "", "", "", "", "", "", "", "A","A","A","A","Ae","A","Ae","C","E","E","E","E","I","I","I","I","N","O","O","O","O","","Oe","O","Oe","U","U","U","Ue","a","a","a","a","ae","a","ae","c","e","e","e","e","i","i","i","i","n","o","o","o","o","","oe","o","oe","u","u","u","ue","y","", "", "", "",  "", "",     "",    "","u","", "", "", "", "", "x","", "", "", "_");
$s=str_replace($badascii,$goodascii,$s);
return mkfilename($s);
}

if (file_exists('../userdatei/userdatei.txt')) $users=file_get_contents('../userdatei/userdatei.txt');
if (file_exists('userdatei/userdatei.txt')) $users=file_get_contents('userdatei/userdatei.txt');
if (file_exists('../../userdatei/userdatei.txt')) $users=file_get_contents('../../userdatei/userdatei.txt');

$users=cleanusers($users);

if (!isset($_POST['u'])) $_POST['u']='';
if (!isset($_COOKIE['u'])) $_COOKIE['u']='';
if (!isset($_POST['p'])) $_POST['p']='';
if (!isset($_COOKIE['p'])) $_COOKIE['p']='';

$up=str_replace(' ','',strtolower(trim($_POST['u'])));
$uc=str_replace(' ','',strtolower(trim($_COOKIE['u'])));

$pcrcp=nadinhash($up.$salt);
$pcrcc=nadinhash($uc.$salt);

if($sticksessiontoip=='yes')$serverremoteaddr=$_SERVER['REMOTE_ADDR'];
else $serverremoteaddr='';
$pmd5p=sha1(trim(str_replace(' ','',($salt.$serverremoteaddr.strtolower($_POST['p'])))));
$pmd5i=sha1(trim(str_replace(' ','',($salt.$serverremoteaddr.$pcrcc))));
$pmd5c=$_COOKIE['p'];

$homepage=str_replace(basename($nadin),'',$nadin);

function auth($right) {
global $uc;
global $pmd5c;
global $pmd5i;
global $users;
global $pcrcc;

if (strpos($users,$uc)<1) {
writelog('unknown;'.cleancsv($uc).';'.crc32($pcrcc));
echo '<script>location.href="'.$homepage.'login.php";</script>';
exit;
}

if ($pmd5c!=$pmd5i) {
writelog('byebye;'.cleancsv($uc).';'.crc32($pcrcc));
echo '<script>location.href="'.$homepage.'login.php";</script>';
exit;
}

$userrights=explode(','.$uc.',',$users);
$userrights=$userrights[1];
$userrights=explode("\n",$userrights);
$userrights=$userrights[0];

$userexp=explode(',',$userrights);
$userexp=$userexp[0];
$userexp=explode('.',$userexp);
$d=$userexp[0];
$m=$userexp[1];
$y=$userexp[2];
$userexp=mktime(0,0,0,intval($m),intval($d),intval($y));
if ($userexp<time())  {
writelog('exp'.date('Ymd',$userexp).';'.cleancsv($uc).';'.crc32($pcrcc));
echo '<script>alert("Dein Zugang ist per '.date('d.m.Y',$userexp).' abgelaufen.");location.href="'.$homepage.'../login.php";</script>';
exit;
}

if (strpos($userrights,($right.'=1'))<1) die('<script>if (document.getElementById(\'navigation\')) document.getElementById(\'navigation\').style.visibility=\'visible\';alert("Du hast keinen Zugriff auf diesen NADIN Bereich (fehlende Berechtigungsstufe '.$right.').");</script>');
}

function role($right) {
global $uc;
global $users;

$users = $users ?? '';
$userrights=explode(','.$uc.',',$users);
$userrights=$userrights[1];
$userrights = $userrights ?? '';
$userrights=explode("\n",$userrights);
$userrights=$userrights[0];

$userexp=explode(',',$userrights);
$userexp=$userexp[0];
$userexp=explode('.',$userexp);
$d=$userexp[0];
$m=$userexp[1];
$y=$userexp[2];
$userexp=mktime(0,0,0,intval($m),intval($d),intval($y));

$userrights=explode(','.$uc.',',$users);
$userrights=$userrights[1];
$userrights=explode("\n",$userrights);
$userrights=$userrights[0];

if (strpos($userrights,($right.'=1'))>0 && $userexp>=time()) return true;
else return false;
}


function checkrole($right) {
if (!role($right)) die('<script>alert("Du hast keinen Zugriff auf diese Datei (fehlende Berechtigungsstufe '.$right.').");self.close();</script>');
}

function allowedfor($right) {
if (!role($right)) return false;
else return true;
}

function nw($s) {
return str_replace('-','&#8209;',str_replace(' ','&nbsp;',htmlXspecialchars($s)));
}

function writelog($str) {
$fp = fopen("userdatei/loginlog.csv","a+");
if($fp) {
fputs($fp,date('Y-m-d H:i:s').';'.$str.';'.$_SERVER['REMOTE_ADDR'].";\n");
fclose($fp); 
}
}

function cleancsv($str) {
$before=array(";","\r","\n");
$after=array(",."," "," ");
$str=str_replace($before,$after,$str);
$str=substr($str,0,100);
return $str;
}

function cleanusers($users) {
$users=trim($users);
$users=str_replace(" ","",$users);
$users=str_replace("\t","",$users);
$users=strtolower($users);
return $users;
}

function autohref($text) {
$text=str_replace('www.','http://www.',$text);
$text=str_replace('http://http://','http://',$text);
$text=str_replace('https://http://','https://',$text);
$pattern = "/(((http[s]?:\/\/)|(www\.))(([a-z][-a-z0-9]+\.)?[a-z][-a-z0-9]+\.[a-z]+(\.[a-z]{2,2})?)\/?[a-z0-9.,_\/~#&=:;%+?-]+[a-z0-9\/#=?]{1,1})/is";
$text = preg_replace($pattern, " <a target=_blank href='$1'>$1</a>", $text);
// fix URLs without protocols
$text = preg_replace("/href='www/", "href='http://www", $text);

$regex = '/(\S+@\S+\.\S+)/';
$replace = '<a href="mailto:$1">$1</a>';

return preg_replace($regex, $replace, $text);
}

function htmlXspecialchars($that) {
return htmlspecialchars(nadinText($that), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
}

function htmlXentities($that) {
return htmlentities(nadinText($that), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
}

function htmlX_entity_decode($that) {
return html_entity_decode(nadinText($that), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
}

function pwdhsh($p) {
$p = base64_encode(sha1($p, true));
return('{SHA}' . $p);
}

function striptime($s) {
$s=preg_replace('/\(\d\d\.\d\d\)/','',$s);
return $s;
}

function hms($duration) //as hh:mm:ss
    {
        //return sprintf("%d:%02d", $duration/60, $duration%60);
        $hours = floor($duration / 3600);
        $minutes = floor( ($duration - ($hours * 3600)) / 60);
        $seconds = $duration - ($hours * 3600) - ($minutes * 60);
        //return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
		$minutes = $minutes+$hours*60;
		return sprintf("%02d.%02d", $minutes, $seconds);
    }

function secure($path) {
$path=str_replace(chr(0),'',$path);
$path=str_replace('../','',$path);
if(strpos('x'.$path,'../')>0) secure($path);
$path=str_replace('/..','',$path);
if(strpos('x'.$path,'/..')>0) secure($path);
$path=str_replace('./','',$path);
if(strpos('x'.$path,'./')>0) secure($path);
$path=str_replace('/.','',$path);
if(strpos('x'.$path,'/.')>0) secure($path);
else return $path;
}
?>
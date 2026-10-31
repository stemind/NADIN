<?php 
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
// Match the former ISO-8859-1 conversion, including '?' for unmappable text.
$substitute=mb_substitute_character();
try { mb_substitute_character(63); $s=mb_convert_encoding($s ?? '', 'ISO-8859-1', 'UTF-8'); }
finally { mb_substitute_character($substitute); }
$badascii = array(" ",":","¦","|","@" ,"&","¢","£","¤","¥","€","§","¨","©","ª","¬","®","¯","°","±","´","µ","¶","…","·","¸","º","÷","†","ß" ,"„","“","”","‚","‘","’","«","»","‹","›","¡","¿","–","À","Á","Â","Ã","Ä" ,"Å","Æ" ,"Ç","È","É","Ê","Ë","Ì","Í","Î","Ï","Ñ","Ò","Ó","Ô","Õ","#","Ö", "Ø","Œ" ,"Ù","Ú","Û","Ü", "à","á","â","ã","ä", "å","æ" ,"ç","è","é","ê","ë","ì","í","î","ï","ñ","ò","ó","ô","õ","#","ö", "ø","œ", "ù","ú","û","ü", "ÿ","'","%","/","\\","<",">",   ",",   ";","+","=","[","]","{","}","*","?","!","`","\"");
$goodascii= array("_","", "", "", "AT","u","c","L","", "Y","E","S","","c","a","N","R","", "o","pm","", "u","P","", "", "", "o","", "", "ss","", "", "", "", "", "", "", "", "", "", "", "", "", "A","A","A","A","Ae","A","Ae","C","E","E","E","E","I","I","I","I","N","O","O","O","O","","Oe","O","Oe","U","U","U","Ue","a","a","a","a","ae","a","ae","c","e","e","e","e","i","i","i","i","n","o","o","o","o","","oe","o","oe","u","u","u","ue","y","", "", "", "",  "", "",     "",    "","u","", "", "", "", "", "x","", "", "", "_");
$s=str_replace($badascii,$goodascii,$s);
return mkfilename($s);
}

require_once __DIR__.'/auth.inc.php';
$users = cleanusers((string)file_get_contents(dirname(__DIR__).'/userdatei/userdatei.txt'));
$up = NadinAuth::email($_POST['u'] ?? '');
$uc = nadin_auth()->session(nadin_session_token(), true);
$pmd5c = nadin_session_token();
$pmd5i = $uc !== '' ? $pmd5c : '!invalid!';
$pcrcp = $pcrcc = ''; // Legacy logging variables never contain password-derived values.
$homepage = nadin_cookie_path();

function auth($right) {
    nadin_require_login();
    if (!role($right)) {
        if (!headers_sent()) http_response_code(403);
        exit('Access denied.');
    }
}
function role($right) {
    global $uc;
    return $uc !== '' && nadin_auth()->active($uc, $right);
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
return htmlspecialchars($that, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1');
}

function htmlXentities($that) {
return htmlentities($that, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1');
}

function htmlX_entity_decode($that) {
return html_entity_decode($that, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1');
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
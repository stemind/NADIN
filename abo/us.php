<?php 
require_once('../config.inc.php');
require_once('../nadinmodule/functions.inc.php');

header("Content-Type: text/xml;charset=iso-8859-1");
echo '<?xml version="1.0" encoding="ISO-8859-1" ?> 
  <rss version="2.0">
 <channel>
  <title>us: '.$_SERVER['PHP_AUTH_USER'].' '.str_replace(basename($nadin),'..',$nadin).'</title>
  <link>'.str_replace(basename($nadin),'..',$nadin).'</link> 
  <description>us: '.str_replace(basename($nadin),'..',$nadin).' für '.$_SERVER['PHP_AUTH_USER'].'</description> 
  <language>de-ch</language> 
  <copyright>(c) UTBB</copyright> 
  <pubDate>'.date('r').'</pubDate> 
  <ttl>5</ttl> 
 <image>
  <title>us: '.str_replace(basename($nadin),'..',$nadin).'</title> 
  <link>'.str_replace(basename($nadin),'..',$nadin).'</link>  
  <url>'.str_replace(basename($nadin),'nadinus.gif',$nadin).'</url> 
  <width>100</width> 
  <height>100</height> 
  <description>us: '.str_replace(basename($nadin),'..',$nadin).'</description> 
  </image>
';

$asc2uni = Array();
for($i=281;$i<282;$i++){
  $asc2uni[chr($i)] = "&#x".dechex($i).";";    
}

function XMLStrFormat($str){
    global $asc2uni;    
    $str = str_replace("\"", "&quot;", $str);  
    $str = str_replace("\r", "", $str);
    $str = strtr($str,$asc2uni);
    return $str;
} 

function cxml($mythis) {
$before = array('&','<','>','\'');
$after = array('&amp;','&lt;','&gt;','&apos;');
return XMLStrFormat(str_replace($before,$after,stripslashes($mythis)));
}
?>




<?php
$path='../library';
$tunes='';
$letter='';
$oldletter='';

if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $tunes.=$dir.$delimiter;
      }
   }


$tunes=explode($delimiter,$tunes);

$bbrecs='';

for ($i=0;$i<count($tunes);$i++) {


$path='../library/'.$tunes[$i];

if ($handle = @opendir($path))  { 
   while (false !== ($file = readdir($handle)))  { 

      if (substr($file,0,3)==strtoupper($bb) || substr($file,0,3)==strtolower($bb) || substr($file,0,3)==strtoupper('xyz') || substr($file,0,3)==strtolower('xyz')) $bbrecs.=$file.'/'.$tunes[$i].$delimiter;
      }
   }

}

$bbrecs=explode($delimiter,$bbrecs);

rsort($bbrecs);

for ($i=0;$i<count($bbrecs);$i++) {

$rec=explode('/',$bbrecs[$i]);

if ($bbrecs[$i]!='') echo item($rec[1],$rec[0],'application/octet-stream');

}


echo'
</channel>
</rss>
';

?>

<?php

function item($thepath,$thefile,$thetype) {
global $nadin;

$date=explode('_',$thefile);
$date=explode('-',$date[1]);
$yyyy=$date[0];
$mm=$date[1];
$dd=$date[2];

$thedate=date('r',mktime(1,1,1,$mm,$dd,$yyyy));
$thelength=filesize('../library/'.$thepath.'/'.$thefile);
$getfile=str_replace(basename($nadin),'rss/',$nadin).xurlencode($thepath).'/'.xurlencode($thefile);
return ('
<item><pubDate>'.$thedate.'</pubDate>
<title>'.strtoupper(str_replace('_',' ',cxml($thepath))).': '.xurlencode($thefile).'</title>
<description>'.xurlencode($thefile).' ('.strtoupper(str_replace('_',' ',cxml($thepath))).')</description>
<link>'.$getfile.'</link>
<enclosure url="'.$getfile.'" length="'.$thelength.'" type="'.$thetype.'" />
<guid>g'.sha1($thedate.$getfile.$thelength).'</guid>
</item>
');
}

function xurlencode($x) {
return str_replace('%28','(',str_replace('%29',')',rawurlencode($x)));
}
?>
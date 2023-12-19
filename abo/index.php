<?php 
require('../config.inc.php');
require('../nadinmodule/functions.inc.php');
require('../nadinmodule/calcbasicauth.inc.php');
auth('vgigs');
header("Content-Type: text/xml;charset=iso-8859-1");
echo '<?xml version="1.0" encoding="ISO-8859-1" ?> 
  <rss version="2.0">
 <channel>
  <title>'.$_SERVER['PHP_AUTH_USER'].' '.str_replace(basename($nadin),'..',$nadin).'</title>
  <link>'.str_replace(basename($nadin),'..',$nadin).'</link> 
  <description>'.str_replace(basename($nadin),'..',$nadin).' für '.$_SERVER['PHP_AUTH_USER'].'</description> 
  <language>de-ch</language> 
  <copyright>(c) UTBB</copyright> 
  <pubDate>'.date('r').'</pubDate> 
  <ttl>5</ttl> 
 <image>
  <title>'.str_replace(basename($nadin),'..',$nadin).'</title> 
  <link>'.str_replace(basename($nadin),'..',$nadin).'</link>  
  <url>'.str_replace(basename($nadin),'nadin.gif',$nadin).'</url> 
  <width>100</width> 
  <height>100</height> 
  <description>'.str_replace(basename($nadin),'..',$nadin).'</description> 
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


$path='../gigreps';
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }


$gigs=explode($delimiter,$gigs);
sort($gigs);


for ($i=0;$i<count($gigs);$i++) {
$c=@file_get_contents('../gigreps/'.$gigs[$i]);

if (trim($c)!='') {

$c=explode($delimiter2,$c);
$rep=$c[1];
$tunes=explode("\n",$rep);
////////////////////////////////////////

$once=0;   
   for ($ii=0;$ii<count($tunes);$ii++) {

$saxespdf='';
$bonespdf='';
$trumpetspdf='';
$rhythmpdf='';
$otherpdf='';
$partspdf='';
$scorepdf='';
$listento='';
  
	  
      $tunes[$ii]=trim($tunes[$ii]);

      $path='../library/'.$tunes[$ii];
   



      if ($handle = @opendir($path))  { 
         while (false !== ($file = readdir($handle)))  { 

      if (strpos($file,$saxes)>1) $saxespdf=$file;
      else if (strpos($file,$bones)>1) $bonespdf=$file;
	  else if (strpos($file,$trumpets)>1) $trumpetspdf=$file;
	  else if (strpos($file,$rhythm)>1) $rhythmpdf=$file;
	  else if (strpos($file,$other)>1) $otherpdf=$file;
	  else if (strpos($file,$parts)>1) $partspdf=$file;
	  else if (strpos($file,$score)>1) $scorepdf=$file;

      else if (strpos($file,$recommended)>1) $listento=$file;
      else if (strpos($file,'.txt')>1) $zusatz=$file;						
      else if ($tunes[$i]!='' && substr($file,0,1)!='.') $more.=$file.$delimiter;     
		    }
         }


$yyyy=substr($gigs[$i],0,4);
$mm=substr($gigs[$i],5,2);
$dd=substr($gigs[$i],8,2);
$thedate=date('r',mktime(0,0,0,$mm,$dd,$yyyy));

if ($yyyy<2000) {
$yyyy=2020;
$mm=4;
$dd=1;
}


if ($once==0) {
if (allowedfor('vgigs')) echo infos($gigs[$i],'text/plain');
if (allowedfor('vgigs')) if (file_exists('zip/'.str_replace('.txt','.pdf',$gigs[$i]))) echo attachment($gigs[$i],'text/plain');
$once=1;
}

if (allowedfor('vsaxes')) if (trim($saxespdf)!='') echo item($saxespdf,'application/pdf');
if (allowedfor('vbones')) if (trim($bonespdf)!='') echo item($bonespdf,'application/pdf');
if (allowedfor('vtrumpets')) if (trim($trumpetspdf)!='') echo item($trumpetspdf,'application/pdf');
if (allowedfor('vrhythm')) if (trim($rhythmpdf)!='') echo item($rhythmpdf,'application/pdf');
if (allowedfor('vother')) if (trim($otherpdf)!='') echo item($otherpdf,'application/pdf');
if (allowedfor('vgigs')) if (trim($partspdf)!='') echo item($partspdf,'application/pdf');
if (allowedfor('vscore')) if (trim($scorepdf)!='') echo item($scorepdf,'application/pdf');
if (allowedfor('vgigs')) if (trim($listento)!='') echo item($listento,'application/octet-stream');


}


////////////////////////////////////////

}

}


echo'
</channel>
</rss>
';

function infos($thefile,$thetype) {
global $tunes;
global $gigs;
global $i;
global $ii;
global $nadin;
global $mm;
global $dd;
global $yyyy;

$thedate=date('r',mktime(7,7,7,$mm,$dd,$yyyy));
$thelength=filesize('../gigreps/'.$thefile);
$getfile=str_replace(basename($nadin),'rss/',$nadin).xurlencode($thefile).'.pdf';
$stand=date('Y-m-d H:i',filemtime('../gigreps/'.$thefile));
return ('
<item><pubDate>'.$thedate.'</pubDate>
<title>'.strtoupper(str_replace('.txt','',cxml($gigs[$i]))).' *** INFOS Stand '.$stand.' ****************************</title>
<description>'.strtoupper(str_replace('.txt','',cxml($gigs[$i]))).' *** INFOS Stand '.$stand.' ****************************</description>
<link>'.$getfile.'</link>
<enclosure url="'.$getfile.'" length="'.$thelength.'" type="'.$thetype.'" />
<guid>g'.sha1($thedate.$getfile.$stand).'</guid>
</item>
');
}

function attachment($thefile,$thetype) {
global $tunes;
global $gigs;
global $i;
global $ii;
global $nadin;
global $mm;
global $dd;
global $yyyy;
$thefile=str_replace('.txt','',$thefile);

$thedate=date('r',mktime(7,7,7,$mm,$dd,$yyyy));
$thelength=filesize('zip/'.$thefile.'.pdf');
$getfile=str_replace(basename($nadin),'zip/',$nadin).xurlencode($thefile).'.pdf';
$stand=date('Y-m-d H:i',filemtime('zip/'.$thefile.'.pdf'));
return ('
<item><pubDate>'.$thedate.'</pubDate>
<title>'.strtoupper(str_replace('.txt','',cxml($gigs[$i]))).' *** PDF-ATTACHMENT Stand '.$stand.' *******************</title>
<description>'.strtoupper(str_replace('.txt','',cxml($gigs[$i]))).' *** PDF-ATTACHMENT Stand '.$stand.' *******************</description>
<link>'.$getfile.'</link>
<enclosure url="'.$getfile.'" length="'.$thelength.'" type="'.$thetype.'" />
<guid>g'.sha1($thedate.$getfile.$stand).'</guid>
</item>
');
}



function item($thefile,$thetype) {
global $tunes;
global $gigs;
global $i;
global $ii;
global $nadin;
global $mm;
global $dd;
global $yyyy;
global $recommended;

$thedate=date('r',mktime(1,1,1,$mm,$dd,$yyyy));
$thelength=filesize('../library/'.$tunes[$ii].'/'.$thefile);
$getfile=str_replace(basename($nadin),'rss/',$nadin).xurlencode($tunes[$ii]).'/'.xurlencode($thefile);
if ($thefile!=str_replace($recommended,'',$thefile)) $ext='AUD'; 
else $ext='PDF';
return ('
<item><pubDate>'.$thedate.'</pubDate>
<title>'.strtoupper(str_replace('.txt','',cxml($gigs[$i]))).' '.$ext.': '.xurlencode($thefile).'</title>
<description>'.xurlencode($thefile).' ('.$ext.') '.strtoupper(str_replace('.txt','',cxml($gigs[$i]))).'</description>
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
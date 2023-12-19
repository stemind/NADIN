<?php
$inumber=1;
for ($i=0;$i<count($tunes);$i++) {

$saxespdf='';
$bonespdf='';
$trumpetspdf='';
$rhythmpdf='';
$otherpdf='';
$partspdf='';
$scorepdf='';

$more='';
$zusatz='';	  
$listento='';
	  
      $tunes[$i]=trim($tunes[$i]);

      $path='../library/'.$tunes[$i];
   
   
      if ($handle = @opendir($path))  { 
         while (false !== ($file = readdir($handle)))  { 

      if (strpos($file,$saxes)>1) {$saxespdf=$file;$allsaxes.=$tunes[$i].'/'.$file.',';}
      else if (strpos($file,$bones)>1) {$bonespdf=$file;$allbones.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$trumpets)>1) {$trumpetspdf=$file;$alltrumpets.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$rhythm)>1) {$rhythmpdf=$file;$allrhythm.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$other)>1) {$otherpdf=$file;$allother.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$parts)>1) {$partspdf=$file;$allparts.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$score)>1) {$scorepdf=$file;$allscore.=$tunes[$i].'/'.$file.',';}

      else if (strpos($file,$recommended)>1) {$listento=$file; $alllistento.=$tunes[$i].'/'.$file.',';}
	  
	  else if (strpos($file,'.txt')>1) $zusatz=$file;						
      else if ($tunes[$i]!='' && substr($file,0,1)!='.') {
	  if (role('musers')) $more.=$file.$delimiter; 
	  else if ($file==str_replace($hidden,'',$file)) $more.=$file.$delimiter; 
		       }
		    }
         } 

if (strpos($zusatz,'.txt')>0 && file_exists('../library/'.$tunes[$i].'/'.$zusatz)) {
$zusatz=file_get_contents('../library/'.$tunes[$i].'/'.$zusatz);
$zusatz=explode($delimiter2,$zusatz);
}
				
if ($tunes[$i]!='') {

if($_GET['m']!='library') {
if($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetpdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='')echo $inumber++.';';
else echo ';';
}

echo nsk($tunes[$i]).';';

echo nsk($zusatz[0]).';';

if ($scorepdf!='') echo nsk($humanscore);
echo ';';
if ($saxespdf!='') echo nsk($humansaxes);
echo ';';
if ($bonespdf!='') echo nsk($humanbones);
echo ';';
if ($trumpetspdf!='') echo nsk($humantrumpets);
echo ';';
if ($rhythmpdf!='') echo nsk($humanrhythm);
echo ';';
if ($otherpdf!='') echo nsk($humanother);
echo ';';
if ($partspdf!='') echo nsk($humanparts);
echo ';';
if ($listento!='') echo nsk($humantherec);
echo ';';

$time='';
if ($listento!='') {
$time=explode('(',$listento);
$time=explode(')',$time[count($time)-1]);
$time='0:'.str_replace('.',':',$time[0]);
}

if ($listento!='') echo nsk($time);
echo ';';
}
echo "\r\n";
}

function nsk($s) {
$s=str_replace(';',',.',$s);
$s=str_replace("\n",' | ',$s);
$s=str_replace("\r",' | ',$s);
$s=str_replace(" |  | ",' | ',$s);
return $s;
}
?>
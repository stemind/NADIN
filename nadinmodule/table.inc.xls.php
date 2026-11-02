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
      if ($_GET['m']=='library' && isset($libraryTuneDates) && $tunes[$i] === '') continue;

      $path='../library/'.$tunes[$i];
   
   
      if ($handle = @opendir($path))  { 
         while (false !== ($file = readdir($handle)))  { 

      if ($file[0]==='.' || strpos($file,'zzDEL!')===0 || !is_file($path.'/'.$file) || is_link($path.'/'.$file)) continue;
      // Hidden working files belong under more, never in the public section slots or ZIP lists.
      if ($hidden!=='' && strpos($tunes[$i].'/'.$file,$hidden)!==false) {
          if (role('musers')) $more.=$file.$delimiter;
          continue;
      }
      $nadinMediaExtension=strtolower(pathinfo($file,PATHINFO_EXTENSION));
      if ($nadinMediaExtension==='pdf' && $saxes!=='' && strpos($file,$saxes)!==false) {$saxespdf=$file;$allsaxes.=$tunes[$i].'/'.$file.',';}
      else if ($nadinMediaExtension==='pdf' && $bones!=='' && strpos($file,$bones)!==false) {$bonespdf=$file;$allbones.=$tunes[$i].'/'.$file.',';}
	  else if ($nadinMediaExtension==='pdf' && $trumpets!=='' && strpos($file,$trumpets)!==false) {$trumpetspdf=$file;$alltrumpets.=$tunes[$i].'/'.$file.',';}
	  else if ($nadinMediaExtension==='pdf' && $rhythm!=='' && strpos($file,$rhythm)!==false) {$rhythmpdf=$file;$allrhythm.=$tunes[$i].'/'.$file.',';}
	  else if ($nadinMediaExtension==='pdf' && $other!=='' && strpos($file,$other)!==false) {$otherpdf=$file;$allother.=$tunes[$i].'/'.$file.',';}
	  else if ($nadinMediaExtension==='pdf' && $parts!=='' && strpos($file,$parts)!==false) {$partspdf=$file;$allparts.=$tunes[$i].'/'.$file.',';}
	  else if ($nadinMediaExtension==='pdf' && $score!=='' && strpos($file,$score)!==false) {$scorepdf=$file;$allscore.=$tunes[$i].'/'.$file.',';}

      else if ($nadinMediaExtension==='mp3' && $recommended!=='' && strpos($file,$recommended)!==false) {$listento=$file; $alllistento.=$tunes[$i].'/'.$file.',';}
	  
	  else if ($file==='infos.txt') $zusatz=$file;
      else if ($nadinMediaExtension==='txt') {
          // Keep the historical text-file fallback when no canonical infos.txt exists.
          if (!is_file($path.'/infos.txt')) $zusatz=$file;
          $more.=$file.$delimiter;
      }
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
if($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetspdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='')echo $inumber++.';';
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
if ($_GET['m']=='library' && isset($libraryTuneDates) && !empty($libraryTuneDates[$tunes[$i]])) {
    echo date('Y-m-d', $libraryTuneDates[$tunes[$i]]);
}
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
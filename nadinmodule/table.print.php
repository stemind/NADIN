<button class="noprint" style="font-size:33px" onclick="self.close()">back</button>
<table id="tprint">
<h2><?php echo htmlXspecialchars($bb.': '.str_replace('.txt','',$_GET['c'] ?? ''))?></h2>
<?php
$allsaxes=$allbones=$alltrumpets=$allrhythm=$allother=$allparts=$allscore=$alllistento='';
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
   
   
      if (!is_link($path) && basename($tunes[$i])===$tunes[$i] && $tunes[$i]!=='' && $tunes[$i][0]!=='.' && ($handle = @opendir($path)))  {
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
if($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetspdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='' || $more!='' || $zusatz!='' || $listento!='')echo '<tr class="trcontent" style="height:33px"><td align="right" class="tdcounter">'.$inumber++.'</td>';
else echo '<tr class="trspacer"><td></td><td></td><tr><td></td>';
}

if ($_GET['m']=='library' && isset($libraryTuneDates)) {
    $libraryPrintDate = !empty($libraryTuneDates[$tunes[$i]]) ? date('d.m.Y', $libraryTuneDates[$tunes[$i]]) : '';
    echo '<tr><td style="white-space:nowrap; padding-right:10px;">';
    if ($libraryPrintDate !== '') echo '<span title="Latest file modification" style="display:inline-block; padding:1px 4px; border-radius:3px; background:#ddd; color:#666; font-size:10px; line-height:13px; white-space:nowrap; -webkit-print-color-adjust:exact; print-color-adjust:exact;">'.$libraryPrintDate.'</span>';
    echo '</td><td class="print">'.nsk($tunes[$i]).'</td></tr>';
} else {
echo '</td><td class="print">'.nsk($tunes[$i]).'<td></tr>';
}
}
echo "\r\n";
}


function nsk($s) {
$s=str_replace(';',',.',$s);
$s=str_replace("\n",' | ',$s);
$s=str_replace("\r",' | ',$s);
$s=str_replace(" |  | ",' | ',$s);
return htmlXspecialchars($s);
}
?>
</table>
<hr />
<script>
if(document.getElementById('tprint').offsetHeight>840) {
tr=document.getElementsByClassName('trcontent');
for(i=0;i<tr.length;i++) {
tr[i].style.height='1px';	
}
tr=document.getElementsByClassName('trspacer');
for(i=0;i<tr.length;i++) {
tr[i].style.height='0px';	
}
}
if(document.getElementById('tprint').offsetHeight>840) {
tr=document.getElementsByClassName('print');
for(i=0;i<tr.length;i++) {
tr[i].style.fontSize='15px';	
}	
}
if(document.getElementById('tprint').offsetHeight>840) {
tr=document.getElementsByClassName('print');
for(i=0;i<tr.length;i++) {
tr[i].style.fontSize='13px';	
}	
}
if(document.getElementById('tprint').offsetHeight>840) {
tr=document.getElementsByClassName('tdcounter');
for(i=0;i<tr.length;i++) {
tr[i].style.fontSize='13px';	
}	
}
if(document.getElementById('tprint').offsetHeight>840) {
tr=document.getElementsByTagName('td');
for(i=0;i<tr.length;i++) {
tr[i].style.paddingTop='0px';
tr[i].style.paddingBottom='0px';
}	
}
window.print();
</script>
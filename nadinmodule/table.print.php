<button class="noprint" style="font-size:33px" onclick="self.close()">back</button>
<table id="tprint">
<h2><?php echo $bb.': '.str_replace('.txt','',$_GET['c'])?></h2>
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
if($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetpdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='' || $more!='' || $zusatz!='' || $listento!='')echo '<tr class="trcontent" style="height:33px"><td align="right" class="tdcounter">'.$inumber++.'</td>';
else echo '<tr class="trspacer"><td></td><td></td><tr><td></td>';
}

echo '</td><td class="print">'.nsk($tunes[$i]).'<td></tr>';
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
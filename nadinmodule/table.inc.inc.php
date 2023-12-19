<?php
echo'<script>if(top===self)windowleft=700;else windowleft=10</script>';
$inumber=1;
$letter='';
$oldletter='';

   echo '<table '.$small.'>';

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

      $path='library/'.$tunes[$i];
   
   
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

if (strpos($zusatz,'.txt')>0 && file_exists('library/'.$tunes[$i].'/'.$zusatz)) {
$zusatz=file_get_contents('library/'.$tunes[$i].'/'.$zusatz);
$zusatz=explode($delimiter2,$zusatz);
}
				
	  if ($tunes[$i]!='') {

      
	  
	   $letter=substr($tunes[$i],0,1);
	   if ($_GET['m']=='library' && $letter!=$oldletter) echo '<tr colspan="6"><td><br /><h4><a name="'.$letter.'"></a>'.$letter.'</h4>
	   
	   <script>
	   document.getElementById("alphabet").innerHTML=document.getElementById("alphabet").innerHTML+"<a onmouseover=\"location.href=\'#'.$letter.'\'\" href=\"#'.$letter.'\">'.$letter.'</a><br/>";
	   </script>
	   
	   </td></tr>';
	   $oldletter=substr($tunes[$i],0,1);

$basket='';
if ($_GET['c']=='admin') $basket='style="cursor:pointer" onmouseover="this.style.color=\'green\';this.style.textDecoration=\'underline\'" onmouseout="this.style.color=\'black\';this.style.textDecoration=\'none\'" onclick="ifrc.basket(escape(this.innerHTML))"';

if (isset($_GET['gig'])) $basket='style="cursor:pointer" onmouseover="this.style.color=\'red\'" onmouseout="this.style.color=\'black\';" onclick="parent.ifrc.basket(escape(this.innerHTML));this.style.textDecoration=\'line-through\'"';


      echo '<tr style="background:#eee" onmouseover="this.style.background=\'lightyellow\'" onmouseout="this.style.background=\'#eee\'"></td>';
         if($_GET['m']!='library') {
			 if($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetpdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='')echo'<td align="right">'.$inumber++.'</td>';
		     else echo '<td></td>';
		 }
		 
		 echo '<td id="'.mkid($tunes[$i]).'"><h5 id="'.mkid2($tunes[$i]).'" '.$basket.'>'.$tunes[$i].'</h5></td>';

         echo '<td>';
		 
		 if (!allowedfor('vscore')) $mute='class="mute"';
		 else $mute='';	 
		 if ($scorepdf!='') echo '<a '.$mute.' href="get/file.php?c='.$tunes[$i].'/'.$scorepdf.'" target="_blank">'.$humanscore.'</a>';
         echo '</td>';
		 
         echo '<td nowrap="nowrap">';

		 if (!allowedfor('vsaxes')) $mute='class="mute"';
		 else $mute='';	 
         if ($saxespdf!='')    echo '<a '.$mute.' href="get/file.php?c='.$tunes[$i].'/'.$saxespdf.'" target="_blank">'.$humansaxes.'</a> ';

		 if (!allowedfor('vbones')) $mute='class="mute"';
		 else $mute='';	 
         if ($bonespdf!='')    echo '<a '.$mute.' href="get/file.php?c='.$tunes[$i].'/'.$bonespdf.'" target="_blank">'.$humanbones.'</a> ';

		 if (!allowedfor('vtrumpets')) $mute='class="mute"';
		 else $mute='';	 
		 if ($trumpetspdf!='') echo '<a '.$mute.' href="get/file.php?c='.$tunes[$i].'/'.$trumpetspdf.'" target="_blank">'.$humantrumpets.'</a> ';

		 if (!allowedfor('vrhythm')) $mute='class="mute"';
		 else $mute='';	 
		 if ($rhythmpdf!='')   echo '<a '.$mute.' href="get/file.php?c='.$tunes[$i].'/'.$rhythmpdf.'" target="_blank">'.$humanrhythm.'</a> ';

		 if (!allowedfor('vother')) $mute='class="mute"';
		 else $mute='';	 
		 if ($otherpdf!='')    echo '<a '.$mute.' href="get/file.php?c='.$tunes[$i].'/'.$otherpdf.'" target="_blank">'.$humanother.'</a> ';

		 if (!allowedfor('vgigs')) $mute='class="mute"';
		 else $mute='';	 
		 if ($partspdf!='')    echo '<a '.$mute.' href="get/file.php?c='.$tunes[$i].'/'.$partspdf.'" target="_blank">'.$humanparts.'</a> ';

         echo '</td>';         echo '<td nowrap="nowrap">';
         if ($listento=='' && ($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetpdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='')) $secwarn=' (!)';
         if ($listento!='')   {
			 $time=explode('(',$listento);
			 $time=explode(')',$time[count($time)-1]);
			 $time=$time[0];
			 $seconds=explode('.',$time);
			 $seconds=$seconds[0]*60+$seconds[1];
			 if($seconds<1)$secwarn=' (!!)';
			 $totalseconds+=$seconds;
			 if(striptime($listento)==$listento) echo '<iframe style="display:none" src="nadinmodule/settime.php?p='.$tunes[$i].'&f='.$listento.'"></iframe>';
			 echo '<a title="'.$listento.'" href="get/file.php?c='.$tunes[$i].'/'.$listento.  '" target="_blank">'.$humantherec.' '.$time.'</a>  ';
		 }
		 else if($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetpdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='') echo '<span class="inumber" style="display:none">?</span>';
         echo '</td>';

         echo '<td nowrap="nowrap">';
         echo '<a id="weitereaufnahmen'.$i.'" href="javascript:void(0)" onclick="if (document.getElementById(\'td1'.$i.'\').style.display!=\'block\') {document.getElementById(\'td1'.$i.'\').style.display=\'block\'} else {document.getElementById(\'td1'.$i.'\').style.display=\'none\'}"></a>';
         echo '</td>';

if($_COOKIE['multipop']=='yes') $randomnumber=rand(0,999999999);
else $randomnumber='';

if (role('upaudio')) $upnow='&nbsp;<span style="color:white;background:#ddd;font-weight:bold;cursor:pointer" onmouseover="this.style.background=\'green\'" onmouseout="this.style.background=\'#ddd\'" title="manage Infos, Links, Files in'."\n".htmlXspecialchars(strtoupper(str_replace('_',' ',$tunes[$i]))).'" onclick="if(location.href.indexOf(\'&c=admin\')>0 && ifrc && ifrc.changes && ifrc.changes>0 && ifrc.document.getElementById(\'plus\').style.display!=\'none\')alert(\'There are unsaved changes in the blue manage Gigs area. You have to save them before clicking on a pencil in the white area.\');else{f1=window.open(\'nadinmodule/managetune.php?i='.$i.'&tune='.rawurlencode($tunes[$i]).'\',\'manage'.$randomnumber.'\',\'toolbar=0,location=0,status=1,top=0,left=\'+windowleft+\',menubar=0,scrollbars=1,resizable=1,width=800,height=830\');f1.focus()}">&nbsp;&#9998;&nbsp;</span>';


         echo '<td align="right">';
         echo '<a id="mehrinfos'.$i.'" href="javascript:void(0)" onclick="if (document.getElementById(\'td2'.$i.'\').style.display!=\'block\') {document.getElementById(\'td2'.$i.'\').style.display=\'block\'} else {document.getElementById(\'td2'.$i.'\').style.display=\'none\'}"></a>';
         echo $upnow.'</td>';


      }

      echo '</tr>';

      echo '<tr><td align="right" colspan="6" class="mehr"><span style="display:none;" id="td2'.$i.'"><b>'.$humaninfos.':</b> ';
      include('mehrinfos.inc.inc.php');    
	  echo '</span></td></tr>';

      echo '<tr><td align="right" colspan="6" class="mehr"><span style="display:none;" id="td1'.$i.'"><b>'.$humanfilesandlinks.':</b> ';
      include('weitereaufnahmen.inc.inc.php');    
	  echo '</span></td></tr>'; 
      }
  if($_GET['m']!='library')$onemoretd='<td></td>';
  echo '<tr>'.$onemoretd.'<td></td><td></td><td></td><td align="right"> '.hms($totalseconds).$secwarn.'<small>&nbsp;</small></td><td></td><td></td></tr>';

   echo '</table>';
if ($_COOKIE['i']>0) echo '<script>if (document.getElementById(\'td1'.$_COOKIE['i'].'\')) document.getElementById(\'td1'.$_COOKIE['i'].'\').style.display=\'block\';if (document.getElementById(\'td2'.$_COOKIE['i'].'\')) document.getElementById(\'td2'.$_COOKIE['i'].'\').style.display=\'block\';</script>';

?>
<script src="nadinmodule/table.reformat.js"></script>

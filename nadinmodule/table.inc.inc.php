<?php
$allsaxes=$allbones=$alltrumpets=$allrhythm=$allother=$allparts=$allscore=$alllistento='';
$totalseconds=0; $secwarn=$onemoretd='';
$small=$small ?? '';
echo'<script>if(top===self)windowleft=700;else windowleft=10</script>';
$inumber=1;
$letter='';
$oldletter='';

   $libraryInteractive = !empty($libraryClientSorting) && isset($_GET['m']) && $_GET['m'] === 'library';
   $librarySeenLetters = array();
   $libraryBackgroundFrames = '';
   $libraryTableAttributes = $libraryInteractive ? ' id="nadin-library-table" data-library-sort="'.$librarySort.'"' : '';
   echo '<table class="nadin-tunes" '.$small.$libraryTableAttributes.'>';

   for ($i=0;$i<count($tunes);$i++) {



$saxespdf='';
$bonespdf='';
$trumpetspdf='';
$rhythmpdf='';
$otherpdf='';
$partspdf='';
$scorepdf='';

$more=''; $upnow='';
$zusatz=array('', '');
$listento=''; $zusatzFile='';
	  
      $tunes[$i]=trim($tunes[$i]);
      if ($libraryInteractive && $tunes[$i] === '') continue;

      $path='library/'.$tunes[$i];
   
   
      if (!is_link($path) && basename($tunes[$i])===$tunes[$i] && $tunes[$i]!=='' && $tunes[$i][0]!=='.' && ($handle = @opendir($path)))  {
         while (false !== ($file = readdir($handle)))  { 
      if ($file[0]==='.' || strpos($file,'zzDEL!')===0 || is_link($path.'/'.$file) || !is_file($path.'/'.$file) || ($hidden!=='' && strpos($tunes[$i].'/'.$file,$hidden)!==false && !role('musers'))) continue;

      if (strpos($file,$saxes)>1) {$saxespdf=$file;$allsaxes.=$tunes[$i].'/'.$file.',';}
      else if (strpos($file,$bones)>1) {$bonespdf=$file;$allbones.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$trumpets)>1) {$trumpetspdf=$file;$alltrumpets.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$rhythm)>1) {$rhythmpdf=$file;$allrhythm.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$other)>1) {$otherpdf=$file;$allother.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$parts)>1) {$partspdf=$file;$allparts.=$tunes[$i].'/'.$file.',';}
	  else if (strpos($file,$score)>1) {$scorepdf=$file;$allscore.=$tunes[$i].'/'.$file.',';}

      else if (strpos($file,$recommended)>1) {$listento=$file; $alllistento.=$tunes[$i].'/'.$file.',';}
	  
	  else if (strpos($file,'.txt')>1) $zusatzFile=$file;
      else if ($tunes[$i]!='' && substr($file,0,1)!='.') {
	  if (role('musers')) $more.=$file.$delimiter; 
	  else if ($file==str_replace($hidden,'',$file)) $more.=$file.$delimiter; 
		       }
		    }
         } 

if (strpos($zusatzFile,'.txt')>0 && file_exists('library/'.$tunes[$i].'/'.$zusatzFile)) {
$zusatz=file_get_contents('library/'.$tunes[$i].'/'.$zusatzFile);
$zusatz=explode($delimiter2,$zusatz);
}
				
	  if ($tunes[$i]!='') {

      
	  
	   $letter=substr($tunes[$i],0,1);
       if ($libraryInteractive) {
           if (!isset($librarySeenLetters[$letter])) {
               $librarySeenLetters[$letter] = true;
               echo '<tbody class="nadin-library-letter" data-library-letter="'.htmlXspecialchars($letter).'"><tr><td colspan="6"><br /><h4><a name="'.htmlXspecialchars($letter).'"></a>'.htmlXspecialchars($letter).'</h4></td></tr></tbody>';
           }
           echo '<tbody class="nadin-library-tune" data-library-letter="'.htmlXspecialchars($letter).'" data-library-alpha="'.$libraryTuneOrder[$tunes[$i]].'" data-library-time="'.$libraryTuneDates[$tunes[$i]].'">';
       } else {
	   if ($_GET['m']=='library' && (!isset($librarySort) || $librarySort !== 'newest') && $letter!=$oldletter) echo '<tr colspan="6"><td><br /><h4><a name="'.$letter.'"></a>'.$letter.'</h4>
	   
	   <script>
	   document.getElementById("alphabet").innerHTML=document.getElementById("alphabet").innerHTML+"<a onmouseover=\"location.href=\'#'.$letter.'\'\" href=\"#'.$letter.'\">'.$letter.'</a><br/>";
	   </script>
	   
	   </td></tr>';
       }
	   $oldletter=substr($tunes[$i],0,1);

$basket='';



      echo '<tr class="nadin-tune-row" style="background:#eee" onmouseover="this.style.background=\'lightyellow\'" onmouseout="this.style.background=\'#eee\'">'.($libraryInteractive ? '' : '</td>');
         if($_GET['m']!='library') {
			 if($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetspdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='')echo'<td class="nadin-number" align="right">'.$inumber++.'</td>';
		     else echo '<td class="nadin-number"></td>';
		 }
		 
		 echo '<td id="'.mkid($tunes[$i]).'"'.($libraryInteractive ? ' class="nadin-tune-cell nadin-library-tune-cell"' : ' class="nadin-tune-cell"').'>';
         if ($libraryInteractive && !empty($libraryTuneDates[$tunes[$i]])) {
             echo '<span class="nadin-library-date-badge" title="Latest file modification">'.date('d.m.Y', $libraryTuneDates[$tunes[$i]]).'</span>';
         }
         echo '<h5 id="'.mkid2($tunes[$i]).'" '.$basket.'>'.htmlXspecialchars($tunes[$i]).'</h5></td>';

         echo '<td>';
		 
		 if (!allowedfor('vscore')) $mute='class="mute"';
		 else $mute='';	 
		 if ($scorepdf!='' && allowedfor('vscore')) echo '<a '.$mute.' href="get/file.php?c='.rawurlencode($tunes[$i].'/'.$scorepdf).'" target="_blank">'.$humanscore.'</a>';
         echo '</td>';
		 
         echo '<td nowrap="nowrap">';

		 if (!allowedfor('vsaxes')) $mute='class="mute"';
		 else $mute='';	 
         if ($saxespdf!='' && allowedfor('vsaxes'))    echo '<a '.$mute.' href="get/file.php?c='.rawurlencode($tunes[$i].'/'.$saxespdf).'" target="_blank">'.$humansaxes.'</a> ';

		 if (!allowedfor('vbones')) $mute='class="mute"';
		 else $mute='';	 
         if ($bonespdf!='' && allowedfor('vbones'))    echo '<a '.$mute.' href="get/file.php?c='.rawurlencode($tunes[$i].'/'.$bonespdf).'" target="_blank">'.$humanbones.'</a> ';

		 if (!allowedfor('vtrumpets')) $mute='class="mute"';
		 else $mute='';	 
		 if ($trumpetspdf!='' && allowedfor('vtrumpets')) echo '<a '.$mute.' href="get/file.php?c='.rawurlencode($tunes[$i].'/'.$trumpetspdf).'" target="_blank">'.$humantrumpets.'</a> ';

		 if (!allowedfor('vrhythm')) $mute='class="mute"';
		 else $mute='';	 
		 if ($rhythmpdf!='' && allowedfor('vrhythm'))   echo '<a '.$mute.' href="get/file.php?c='.rawurlencode($tunes[$i].'/'.$rhythmpdf).'" target="_blank">'.$humanrhythm.'</a> ';

		 if (!allowedfor('vother')) $mute='class="mute"';
		 else $mute='';	 
		 if ($otherpdf!='' && allowedfor('vother'))    echo '<a '.$mute.' href="get/file.php?c='.rawurlencode($tunes[$i].'/'.$otherpdf).'" target="_blank">'.$humanother.'</a> ';

		 if (!allowedfor('vgigs')) $mute='class="mute"';
		 else $mute='';	 
		 if ($partspdf!='' && allowedfor('vgigs'))    echo '<a '.$mute.' href="get/file.php?c='.rawurlencode($tunes[$i].'/'.$partspdf).'" target="_blank">'.$humanparts.'</a> ';

         echo '</td>';         echo '<td nowrap="nowrap">';
         if ($listento=='' && ($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetspdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='')) $secwarn=' (!)';
         if ($listento!='')   {
             $time='';$seconds=0;
             if (preg_match('/\((\d+)\.(\d{2})\)/',$listento,$duration)) { $time=$duration[1].'.'.$duration[2];$seconds=(int)$duration[1]*60+(int)$duration[2]; }
             if($seconds<1)$secwarn=' (!!)';
			 $totalseconds+=$seconds;
             if(striptime($listento)==$listento) {
                 require_once __DIR__.'/media-time.inc.php';$libraryTimeFrame=nadin_audio_time_form($tunes[$i],$listento);
                 if ($libraryInteractive) $libraryBackgroundFrames .= $libraryTimeFrame;
                 else echo $libraryTimeFrame;
             }
			 echo '<a title="'.htmlXspecialchars($listento).'" href="get/file.php?c='.rawurlencode($tunes[$i].'/'.$listento).'" target="_blank"><span class="nadin-recording-label">'.$humantherec.'</span><span class="nadin-recording-symbol" aria-hidden="true">&#9835;</span> '.$time.'</a>  ';
		 }
		 else if($scorepdf!='' || $saxespdf!='' || $bonespdf!='' || $trumpetspdf!='' || $rhythmpdf!='' || $otherpdf!='' || $partspdf!='') echo '<span class="inumber" style="display:none">?</span>';
         echo '</td>';

         echo '<td nowrap="nowrap">';
         echo '<a id="weitereaufnahmen'.$i.'" href="javascript:void(0)" onclick="if (document.getElementById(\'td1'.$i.'\').style.display!=\'block\') {document.getElementById(\'td1'.$i.'\').style.display=\'block\'} else {document.getElementById(\'td1'.$i.'\').style.display=\'none\'}"></a>';
         echo '</td>';

if(($_COOKIE['multipop'] ?? '')=='yes') $randomnumber=rand(0,999999999);
else $randomnumber='';

if (role('upaudio')) $upnow='&nbsp;<span style="color:white;background:#ddd;font-weight:bold;cursor:pointer" onmouseover="this.style.background=\'green\'" onmouseout="this.style.background=\'#ddd\'" title="manage Infos, Links, Files in'."\n".htmlXspecialchars(strtoupper(str_replace('_',' ',$tunes[$i]))).'" onclick="if(location.href.indexOf(\'&c=admin\')>0 && typeof ifrc!==\'undefined\' && ifrc.changes && ifrc.changes>0 && ifrc.document.getElementById(\'plus\').style.display!=\'none\')alert(\'There are unsaved changes in the blue manage Gigs area. You have to save them before clicking on a pencil in the white area.\');else{f1=window.open(\'nadinmodule/managetune.php?i='.$i.'&tune='.rawurlencode($tunes[$i]).'\',\'manage'.$randomnumber.'\',\'toolbar=0,location=0,status=1,top=0,left=\'+windowleft+\',menubar=0,scrollbars=1,resizable=1,width=800,height=830\');f1.focus()}">&nbsp;&#9998;&nbsp;</span>';


         echo '<td align="right">';
         echo '<a id="mehrinfos'.$i.'" href="javascript:void(0)" onclick="if (document.getElementById(\'td2'.$i.'\').style.display!=\'block\') {document.getElementById(\'td2'.$i.'\').style.display=\'block\'} else {document.getElementById(\'td2'.$i.'\').style.display=\'none\'}"></a>';
         echo $upnow.'</td>';


      }

      echo '</tr>';

      echo '<tr class="nadin-detail-row"><td align="right" colspan="6" class="mehr"><span style="display:none;" id="td2'.$i.'"><b>'.$humaninfos.':</b> ';
      include('mehrinfos.inc.inc.php');    
	  echo '</span></td></tr>';

      echo '<tr class="nadin-detail-row"><td align="right" colspan="6" class="mehr"><span style="display:none;" id="td1'.$i.'"><b>'.$humanfilesandlinks.':</b> ';
      include('weitereaufnahmen.inc.inc.php');    
	  echo '</span></td></tr>'; 
      if ($libraryInteractive) echo '</tbody>';
      }
  if($_GET['m']!='library')$onemoretd='<td></td>';
  if ($libraryInteractive) echo '<tfoot>';
  echo '<tr>'.$onemoretd.'<td></td><td></td><td></td><td align="right"> '.hms($totalseconds).$secwarn.'<small>&nbsp;</small></td><td></td><td></td></tr>';

   if ($libraryInteractive) echo '</tfoot>';
   echo '</table>';
   if ($libraryInteractive) echo $libraryBackgroundFrames;
if (isset($_COOKIE['i']) && is_string($_COOKIE['i']) && ctype_digit($_COOKIE['i']) && (int)$_COOKIE['i']<count($tunes) && (int)$_COOKIE['i']>0) echo '<script>if (document.getElementById(\'td1'.$_COOKIE['i'].'\')) document.getElementById(\'td1'.$_COOKIE['i'].'\').style.display=\'block\';if (document.getElementById(\'td2'.$_COOKIE['i'].'\')) document.getElementById(\'td2'.$_COOKIE['i'].'\').style.display=\'block\';</script>';

?>
<script src="nadinmodule/table.reformat.js"></script>

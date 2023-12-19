<?php 
require_once('config.inc.php');
require_once('nadinmodule/functions.inc.php');
auth('vreclist');
?>
<h3 id="alphabet" style="max-height:200px;overflow-x:hidden;overflow-y:auto"></h3>

<?php
$path='./library';
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


$path='./library/'.$tunes[$i];

if ($handle = @opendir($path))  { 
   while (false !== ($file = readdir($handle)))  { 

      if (substr($file,0,5)==('zzDEL')) $bbrecs.=$file.'/'.$tunes[$i].$delimiter;
      }
   }

}

$bbrecs=explode($delimiter,$bbrecs);

rsort($bbrecs);

for ($i=0;$i<count($bbrecs);$i++) {

$letter=substr($bbrecs[$i],0,0);

if($i==0) //include('abous.inc.inc.php');

if($letter!=$oldletter) {
if($oldletter!='') echo hms($totalseconds);
$totalseconds=0;	
}

if($letter!=$oldletter) echo '<h4 style="margin-bottom:3px"><a name="'.$letter.'"></a>'.str_replace(strtolower('xyz').'_','',str_replace(strtoupper('xyz').'_','',str_replace(strtolower($bb).'_','',str_replace(strtoupper($bb).'_','',$letter)))).'</h4>

  <script>  document.getElementById("alphabet").innerHTML=document.getElementById("alphabet").innerHTML+"<a href=#'.$letter.'>'.str_replace(strtolower('xyz').'_','',str_replace(strtoupper('xyz').'_','',str_replace(strtolower($bb).'_','',str_replace(strtoupper($bb).'_','',str_replace(' ','&nbsp;',str_replace('-','&#8209;',$letter)))))).'</a> &nbsp; ";

  </script>

';
$oldletter=$letter;


$rec=explode('/',$bbrecs[$i]);

if($_COOKIE['multipop']=='yes') $randomnumber=rand(0,999999999);
else $randomnumber='';

if (role('upaudio')) $upnow='&nbsp;<span style="color:#eee;font-weight:bold;cursor:pointer" onmouseover="this.style.background=\'green\'" onmouseout="this.style.background=\'#fff\'" title="manage Infos, Links, Files in'."\n".htmlXspecialchars(strtoupper(str_replace('_',' ',$rec[1]))).'" onclick="f1=window.open(\'nadinmodule/managetune.php?i='.$i.'&tune='.rawurlencode($rec[1]).'&m='.mkid2($rec[0]).'\',\'manage'.$randomnumber.'\',\'toolbar=0,location=0,status=1,top=0,left=700,menubar=0,scrollbars=1,resizable=1,width=800,height=830\');f1.focus()">&nbsp;&#9998;&nbsp;</span>';

if ($bbrecs[$i]!='') echo '<a id="'.mkid2($rec[0]).'" href="get/file.php?c='.str_replace('library/','',$path).$rec[1].'/'.$rec[0].'" target="_blank">'.str_replace($letter,'',$rec[0]).'</a>'.$upnow.'<br />';	

			 $time=explode('(',$rec[0]);
			 $time=explode(')',$time[count($time)-1]);
			 $time=$time[0];
			 $seconds=explode('.',$time);
			 $seconds=$seconds[0]*60+$seconds[1];
			 if($seconds<1)$secwarn=' (!)';
			 $totalseconds+=$seconds;
}


?>
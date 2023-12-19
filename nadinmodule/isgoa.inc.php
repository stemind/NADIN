<?php
if (strpos($c,$saxes)>0 || strpos($c,$bones)>0 || strpos($c,$trumpets)>0 || strpos($c,$rhythm)>0 || strpos($c,$other)>0 || strpos($c,$score)>0 || strpos($c,$hidden)>0 || strpos($c,$parts)>0 || strpos('x'.$c,$bb)!=1) {

$foundgoa=0;
$purec=explode('/',$_GET['c']);
$purec=$purec[0];

////////////////////////////////////
	
$path='./../gigreps';
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }
$gigs=explode($delimiter,$gigs);

for ($i=0; $i<count($gigs);$i++) {
if(strpos('x'.file_get_contents($path.'/'.$gigs[$i]),$purec)>0) $foundgoa++;
}
	
////////////////////////////////////

if ($foundgoa==0 && allowedfor('varchiv')) {
$path='./../gigrepsarchiv';
$gigs='';
if ($handle = @opendir($path))  { 
   while (false !== ($dir = readdir($handle)))  { 
      if (substr($dir,0,1)!='.') $gigs.=$dir.$delimiter;
      }
   }
$gigs=explode($delimiter,$gigs);

for ($i=0; $i<count($gigs);$i++) {
if(strpos('x'.file_get_contents($path.'/'.$gigs[$i]),$purec)>0) $foundgoa++;
}
}
}
if ($foundgoa==0) die('You do not have access to this file because it is not on a setlist of a gig.');
?>
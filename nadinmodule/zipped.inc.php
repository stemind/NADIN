<?php
require_once __DIR__.'/zip.inc.php';
$links=array();$zipMode=$_GET['m']==='gigrepsarchiv'?'gigrepsarchiv':'gigreps';
foreach(nadin_zip_types() as $type=>$profile){
 if(!role($profile[1])||($type==='alllistento'?$offerrcmdmp3saszip!=='yes':$offersheetsaszip!=='yes'))continue;
 try{$available=nadin_zip_sources(dirname(__DIR__),$_GET['c'],$type,$zipMode);}catch(Throwable $e){$available=array();}
 if(!$available)continue;
 $label=$profile[2];if(in_array($type,array('allsaxes','allbones','alltrumpets','allrhythm','allother'),true))$label.=' + Parts';
 $url='get/zip.php?'.http_build_query(array('c'=>'zip/'.$type.'/'.substr($_GET['c'],0,-4).'_'.$type.'.zip'));
 $links[]='<a href="'.nadinHtml($url).'">'.nadinHtml($label).' ZIP</a>';
}
echo implode(' · ',$links);

<?php
require_once __DIR__.'/zip.inc.php';
$links=array();$zipMode=$_GET['m']==='gigrepsarchiv'?'gigrepsarchiv':'gigreps';
foreach(nadin_zip_types() as $type=>$profile){
 if(!role($profile[1])||($type==='alllistento'?$offerrcmdmp3saszip!=='yes':$offersheetsaszip!=='yes'))continue;
 try{$available=nadin_zip_sources(dirname(__DIR__),$_GET['c'],$type,$zipMode);}catch(Throwable $e){$available=array();}
 if(!$available)continue;
 $includesParts=false;
 if($parts!==''&&in_array($type,array('allsaxes','allbones','alltrumpets','allrhythm','allother'),true)){
  foreach(array_keys($available) as $source){if(strpos($source,$parts)!==false){$includesParts=true;break;}}
 }
 $count=count($available);$format=$type==='alllistento'?'MP3':'PDF';
 $description=$profile[2].' ZIP: '.$count.' '.$format.($count===1?'':'s').($includesParts?'; includes unsplit Parts where section PDFs are missing':'');
 $url='get/zip.php?'.http_build_query(array('m'=>$zipMode,'c'=>'zip/'.$type.'/'.substr($_GET['c'],0,-4).'_'.$type.'.zip'));
 $label=$profile[2].($includesParts?' + Parts':'');
 $links[]='<a class="nadin-zip-link" href="'.nadinHtml($url).'" title="'.nadinHtml($description).'" aria-label="'.nadinHtml($description).'">'.nadinHtml($label).'</a>';
}
if($links){
 echo '<div class="nadin-downloads" role="group" aria-label="ZIP downloads"><span class="nadin-downloads-label">ZIP:</span>'.implode('',$links).'</div>';
}

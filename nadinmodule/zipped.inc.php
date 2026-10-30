<?php
require_once __DIR__.'/zip.inc.php';
$links=array();$hasPartsFallback=false;$zipMode=$_GET['m']==='gigrepsarchiv'?'gigrepsarchiv':'gigreps';
foreach(nadin_zip_types() as $type=>$profile){
 if(!role($profile[1])||($type==='alllistento'?$offerrcmdmp3saszip!=='yes':$offersheetsaszip!=='yes'))continue;
 try{$available=nadin_zip_sources(dirname(__DIR__),$_GET['c'],$type,$zipMode);}catch(Throwable $e){$available=array();}
 if(!$available)continue;
 $includesParts=false;
 if($parts!==''&&in_array($type,array('allsaxes','allbones','alltrumpets','allrhythm','allother'),true)){
  foreach(array_keys($available) as $source){if(strpos($source,$parts)!==false){$includesParts=true;break;}}
 }
 $hasPartsFallback=$hasPartsFallback||$includesParts;
 $count=count($available);$format=$type==='alllistento'?'MP3':'PDF';
 $description=$count.' '.$format.($count===1?'':'s').($includesParts?' · incl. Parts':'');
 $url='get/zip.php?'.http_build_query(array('m'=>$zipMode,'c'=>'zip/'.$type.'/'.substr($_GET['c'],0,-4).'_'.$type.'.zip'));
 $links[]='<a class="nadin-zip-link" href="'.nadinHtml($url).'"><span class="nadin-zip-badge"><span aria-hidden="true">↓ </span>ZIP</span><span class="nadin-zip-text"><strong>'.nadinHtml($profile[2]).'</strong><small>'.nadinHtml($description).'</small></span></a>';
}
if($links){
 echo '<details class="nadin-downloads" open><summary>ZIP downloads <span class="nadin-downloads-hint">Whole setlist</span></summary><div class="nadin-zip-grid">'.implode('',$links).'</div>';
 if($hasPartsFallback)echo '<p class="nadin-zip-note">“Incl. Parts”: includes unsplit Parts where section PDFs are missing.</p>';
 echo '</details>';
}

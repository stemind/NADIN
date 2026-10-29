<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/nadinmodule/forscore.inc.php';
$root = sys_get_temp_dir() . '/nadin-test-' . bin2hex(random_bytes(6));
mkdir($root); foreach (array('library','gigreps','gigrepsarchiv') as $dir) mkdir($root.'/'.$dir);
$separator = '* SECTION *'; $hidden = 'HDDN'; $checks = 0;
$profiles = array('sx'=>array('code'=>'SXS','right'=>'vsaxes'), 'tb'=>array('code'=>'TBS','right'=>'vbones'), 'parts'=>array('code'=>'PRTS','right'=>'vgigs'), 'score'=>array('code'=>'SCRE','right'=>'vscore'));
$rights = array('vgigs'=>true,'vsaxes'=>true,'vbones'=>false,'vscore'=>false,'varchiv'=>false,'vlibrary'=>false);
$service = new NadinForScore($root, $separator, $profiles, $rights);
function ok($test, $message) { global $checks; if (!$test) throw new RuntimeException('FAIL: '.$message); $checks++; }
function fails($call, $message) { try { $call(); } catch (RuntimeException $e) { ok(true,$message); return; } ok(false,$message); }
function asset($tune,$name,$bytes=null) { global $root; @mkdir($root.'/library/'.$tune); $bytes=$bytes??"%PDF-1.4\n".str_repeat('abc123',17000)."\n%%EOF"; file_put_contents($root.'/library/'.$tune.'/'.$name,$bytes); }
function rep($name,$tunes,$newline="\n") { global $root,$separator; file_put_contents($root.'/gigreps/'.$name,"info".$newline.$separator.$newline.implode($newline,$tunes)); }
function row($plan,$tune) { return $plan['rows'][hash('sha256',$tune)]; }
function selected($plan,$tune) { $r=row($plan,$tune); return $r['options'][$r['selected']]; }
function output($plan,$new=true,$placeholders=false) { $h=tmpfile(); try{NadinForScore::writeSetlist($h,'Concert & "Music" — été',$plan,$new,$placeholders);rewind($h);return stream_get_contents($h);}finally{fclose($h);} }
function removeTree($path) { foreach (scandir($path) as $file) { if ($file==='.'||$file==='..')continue; $p=$path.'/'.$file; if(is_dir($p)&&!is_link($p))removeTree($p);else unlink($p); } rmdir($path); }
try {
asset('A','A_SXS.pdf'); asset('A','A_PRTS.pdf'); asset('B','B_PRTS.pdf'); asset('C','C_TBS.pdf');
rep('One.txt',array('A','B','A','Pause','C'),"\r\n");rep('Two.txt',array('B','A'),"\r");
$p=$service->plan('gigreps','One.txt','sx',array());
ok(selected($p,'A')['asset']['type']==='sx','prefer section');ok(selected($p,'B')['asset']['type']==='parts','fallback Parts');
ok(count($p['entries'])===5&&count($p['rows'])===4,'preserve repeats and placeholders');ok(!row($p,'C')['options'],'deny other sections');
fails(function()use($p){output($p);},'missing entries need confirmation');
$xml=output($p,true,true);$doc=simplexml_load_string($xml);ok($doc!==false,'valid XML');ok(count($doc->score)===3&&count($doc->placeholder)===2,'ordered entries');
ok(count($doc->xpath('score[@data]'))===2,'embed unique PDFs once');
ok(base64_decode((string)$doc->score[0]['data'])===file_get_contents($root.'/library/A/A_SXS.pdf'),'base64 chunks preserve exact bytes');
ok((string)$doc['title']==='Concert & "Music" — été','XML escaping');
$names=array(selected($p,'A')['path'],selected($p,'B')['path']);
$p2=$service->plan('gigreps','Two.txt','sx',$names);ok(count(simplexml_load_string(output($p2))->xpath('score[@data]'))===0,'cross-Rep reuse');
ok(selected($p2,'A')['path']===selected($p,'A')['path'],'stable filenames');
asset('B','B_SXS.pdf');$p3=$service->plan('gigreps','Two.txt','sx',$names);ok(selected($p3,'B')['asset']['type']==='parts','later section does not replace local Parts');
asset('A','A_SXS.pdf',"%PDF-1.4\ncorrected\n%%EOF");$p4=$service->plan('gigreps','Two.txt','sx',$names);ok(selected($p4,'A')['path']===$names[0]&&selected($p4,'A')['old'],'keep annotated old revision');
$a=row($p4,'A');$current='current:'.selected($p4,'A')['asset']['id'];$p5=$service->plan('gigreps','Two.txt','sx',$names,array($a['key']=>$current));
ok(selected($p5,'A')['path']!==$names[0],'new revision filename');ok(count(simplexml_load_string(output($p5))->xpath('score[@data]'))===1,'only changed PDF embedded');
$legacy=$service->plan('gigreps','Two.txt','sx',array('A_SXS.pdf'));ok(selected($legacy,'A')['path']==='A_SXS.pdf'&&selected($legacy,'A')['known'],'adopt unique original filenames');
asset('A','A_Second_SXS.pdf');$amb=$service->plan('gigreps','Two.txt','sx',array());ok(row($amb,'A')['selected']==='','multiple section files require choice');fails(function()use($amb){output($amb,true,true);},'cannot silently choose ambiguous PDF');
fails(function()use($service){$service->plan('gigreps','One.txt','tb',array());},'denied profile');
fails(function()use($service){$service->plan('library','','sx',array());},'library rights');
copy($root.'/gigreps/One.txt',$root.'/gigrepsarchiv/Old.txt');fails(function()use($service){$service->plan('gigrepsarchiv','Old.txt','sx',array());},'archive rights');
fails(function()use($service){$service->plan('gigreps','../One.txt','sx',array());},'traversal');
symlink($root.'/library/A',$root.'/library/Linked');rep('Link.txt',array('Linked'));ok(!row($service->plan('gigreps','Link.txt','sx',array()),'Linked')['options'],'symlink tune excluded');
asset('Hidden','Hidden_SXS_HDDN.pdf');rep('Hidden.txt',array('Hidden'));ok(!row($service->plan('gigreps','Hidden.txt','sx',array()),'Hidden')['options'],'hidden PDF rights');
ok(NadinForScore::inventory("\xEF\xBB\xBFFilename,Title\n\"A.pdf\",\"A, B\"\n")==array('A.pdf'),'CSV BOM/quotes');
ok(NadinForScore::inventory("Filename;Title\r\nB.pdf;B\r\n")==array('B.pdf'),'semicolon CSV');
ok(NadinForScore::inventory("Filename\tTitle\nC.pdf\tC\n")==array('C.pdf'),'TSV');
ok(NadinForScore::inventory("\xFF\xFE".iconv('UTF-8','UTF-16LE',"Filename,Title\nD.pdf,D\n"))===array('D.pdf'),'UTF16');
fails(function(){NadinForScore::inventory("Title\nA\n");},'title-only inventory');fails(function(){NadinForScore::inventory("Filename\nA.pdf\nA.pdf\n");},'duplicate filenames');
fails(function(){NadinForScore::inventory("Filename\n../A.pdf\n");},'unsafe inventory path');
// A tune and a PDF rename must retain their persistent file identity.
asset('Rename','Rename_PRTS.pdf');rep('Rename.txt',array('Rename'));$before=selected($service->plan('gigreps','Rename.txt','parts',array()),'Rename');
rename($root.'/library/Rename/Rename_PRTS.pdf',$root.'/library/Rename/New_PRTS.pdf');$after=selected($service->plan('gigreps','Rename.txt','parts',array()),'Rename');ok($before['path']===$after['path'],'PDF rename identity');
rename($root.'/library/Rename',$root.'/library/Renamed');rep('Rename.txt',array('Renamed'));$after2=selected($service->plan('gigreps','Rename.txt','parts',array()),'Renamed');ok($before['path']===$after2['path'],'folder rename identity');
mkdir($root.'/library/Copied');foreach(glob($root.'/library/Renamed/*') as $f)copy($f,$root.'/library/Copied/'.basename($f));copy($root.'/library/Renamed/.nadin-forscore.json',$root.'/library/Copied/.nadin-forscore.json');rep('Copy.txt',array('Copied'));$copy=selected($service->plan('gigreps','Copy.txt','parts',array()),'Copied');ok($copy['path']!==$before['path'],'copied arrangement has own identity');
$latin="M\xFCller";asset($latin,$latin.'_PRTS.pdf');rep('Latin.txt',array($latin));$l1=selected($service->plan('gigreps','Latin.txt','parts',array()),$latin);$l2=selected($service->plan('gigreps','Latin.txt','parts',array()),$latin);ok($l1['path']===$l2['path'],'Windows-1252 filesystem identity');
asset('Bad','Bad_PRTS.pdf','not PDF');rep('Bad.txt',array('Bad'));$bad=$service->plan('gigreps','Bad.txt','parts',array());fails(function()use($bad){output($bad);},'invalid PDF rejected');
rep('Changed.txt',array('Renamed'));$changing=$service->plan('gigreps','Changed.txt','parts',array());file_put_contents($root.'/library/Renamed/New_PRTS.pdf',"%PDF-changed");fails(function()use($changing){output($changing);},'change during export rejected');
file_put_contents($root.'/library/Copied/.nadin-forscore.json','broken');fails(function()use($service){$service->plan('gigreps','Copy.txt','parts',array());},'corrupted identities fail closed');ok(file_get_contents($root.'/library/Copied/.nadin-forscore.json')==='broken','corrupt metadata not overwritten');
echo "PASS: $checks forScore regression checks\n";
} finally { removeTree($root); }

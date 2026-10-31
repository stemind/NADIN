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
// Headings are automatic; missing notes on actual library tunes still need consent.
$heading='Aus_Gigrep: Grüße & "Zugabe"';
rep('Headings.txt',array($heading,'A','Pause','B',$heading,'A',''));
$headings=$service->plan('gigreps','Headings.txt','sx',array());
ok(row($headings,$heading)['kind']==='heading'&&row($headings,$heading)['reason']==='','heading is not a missing-notes warning');
ok(row($headings,'A')['kind']==='tune','library entry remains a tune');
$headingDoc=simplexml_load_string(output($headings));
$sequence=array();foreach($headingDoc->children() as $entry)$sequence[]=$entry->getName().':'.(string)$entry['title'];
ok($sequence===array('placeholder:'.$heading,'score:A','placeholder:Pause','score:B','placeholder:'.$heading,'score:A'),'headings retain exact text, positions and repetitions without confirmation');
ok(count($headingDoc->xpath('score[@data]'))===2,'headings do not change unique PDF transport');
$references=simplexml_load_string(output($headings,false));
ok(count($references->placeholder)===3&&count($references->xpath('score[@data]'))===0,'reference-only export also includes headings automatically');
rep('Only headings.txt',array($heading,'Pause',$heading));
ok(count(simplexml_load_string(output($service->plan('gigreps','Only headings.txt','sx',array())))->placeholder)===3,'heading-only Rep exports without confirmation');
$latinHeading="Gr\xFCsse & Zugabe:";rep('Latin headings.txt',array($latinHeading));
ok((string)simplexml_load_string(output($service->plan('gigreps','Latin headings.txt','sx',array())))->placeholder[0]['title']==='Grüsse & Zugabe:','single-byte heading is converted without text loss');
$archiveRights=$rights;$archiveRights['varchiv']=true;
$archiveService=new NadinForScore($root,$separator,$profiles,$archiveRights);
copy($root.'/gigreps/Headings.txt',$root.'/gigrepsarchiv/Headings.txt');
ok(count(simplexml_load_string(output($archiveService->plan('gigrepsarchiv','Headings.txt','sx',array())))->placeholder)===3,'archived headings also export automatically');
mkdir($root.'/library/Empty');rep('Empty.txt',array('Start:','Empty'));
$empty=$service->plan('gigreps','Empty.txt','sx',array());
ok(row($empty,'Empty')['kind']==='tune','empty library tune is not a heading');
fails(function()use($empty){output($empty);},'empty tune still needs missing-notes confirmation');
ok(count(simplexml_load_string(output($empty,true,true))->placeholder)===2,'confirmed missing notes and automatic headings coexist');
rep('Denied.txt',array('Start:','C'));
$denied=$service->plan('gigreps','Denied.txt','sx',array());
ok(row($denied,'C')['kind']==='tune','unavailable section is not a heading');
fails(function()use($denied){output($denied);},'unavailable section still needs confirmation');
symlink($root.'/library/DoesNotExist',$root.'/library/Dangling');rep('Dangling.txt',array('Dangling'));
$dangling=$service->plan('gigreps','Dangling.txt','sx',array());
ok(row($dangling,'Dangling')['kind']==='tune','dangling source symlink is not a heading');
fails(function()use($dangling){output($dangling);},'dangling source cannot bypass missing-notes confirmation');
$repBefore=file_get_contents($root.'/gigreps/Headings.txt');output($headings);
ok(file_get_contents($root.'/gigreps/Headings.txt')===$repBefore,'export does not migrate or rewrite the source Rep');
rep('Text headings.txt',array('Set 2 / Pause:','../text only','<b>Grüsse & Zugabe</b>','A'));
$textHeadings=simplexml_load_string(output($service->plan('gigreps','Text headings.txt','sx',array())));
ok(count($textHeadings->placeholder)===3,'punctuated headings remain text without file lookups');
ok((string)$textHeadings->placeholder[0]['title']==='Set 2 / Pause:','slash-separated heading text remains exact');
ok((string)$textHeadings->placeholder[1]['title']==='../text only','path-like heading is inert text');
ok((string)$textHeadings->placeholder[2]['title']==='<b>Grüsse & Zugabe</b>','heading markup is escaped without loss');
$names=array(selected($p,'A')['path'],selected($p,'B')['path']);
$upperNames=array_map('strtoupper',$names);
$upper=$service->plan('gigreps','Two.txt','sx',$upperNames);
ok(selected($upper,'A')['path']===$upperNames[0],'inventory case changes retain the existing PDF filename');
ok(count(simplexml_load_string(output($upper))->xpath('score[@data]'))===0,'case-only filename changes never embed another PDF');
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

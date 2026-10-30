<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require_once dirname(__DIR__).'/nadinmodule/zip.inc.php';
$saxes='SXS';$bones='TBS';$trumpets='TRPTS';$rhythm='RHYTHM';$other='OTHER';$parts='PRTS';$score='SCRE';$recommended='LISTENTO';$hidden='HDDN';$delimiter2='* SECTION *';$checks=0;
function ok($value,$message) { global $checks;if (!$value) throw new RuntimeException('FAIL: '.$message);$checks++; }
function fails($fn,$message) { try { $fn(); } catch (Throwable $e) { ok(true,$message);return; }ok(false,$message); }
function rmTree($p) { if (is_dir($p)&&!is_link($p)) {foreach (new DirectoryIterator($p) as $f) if (!$f->isDot()) rmTree($f->getPathname());rmdir($p);} else unlink($p); }
$root=sys_get_temp_dir().'/nadin-zip-'.bin2hex(random_bytes(6));mkdir($root);foreach (array('library','gigreps','gigrepsarchiv','userdatei','abo') as $d) mkdir($root.'/'.$d);
function asset($tune,$name,$bytes='PDF fixture') { global $root;@mkdir($root.'/library/'.$tune);file_put_contents($root.'/library/'.$tune.'/'.$name,$bytes); }
function rep($name,$tunes,$mode='gigreps') {global $root,$delimiter2;file_put_contents($root.'/'.$mode.'/'.$name,"Infos\n".$delimiter2."\n".implode("\n",$tunes));}
function contents($path) {$zip=new ZipArchive();if($zip->open($path,ZipArchive::CHECKCONS)!==true)throw new RuntimeException('Bad ZIP');$out=array();for($i=0;$i<$zip->numFiles;$i++)$out[$zip->getNameIndex($i)]=$zip->getFromIndex($i);$zip->close();return $out;}
try {
asset('A','A_SXS.pdf','saxes');asset('A','A_PRTS.pdf','unsplit');asset('A','A_TBS.pdf','bones');asset('A','A_LISTENTO.mp3','audio');
asset('B','B_PRTS.pdf','fallback');asset('B','B_SXS.pdf','');asset('B','B_PRTS_second.pdf','second');
asset('C[1]','C_SXS.pdf','literal brackets');asset('Été','Été_SXS.PDF','unicode');asset("M\xfcller","M\xfcller_SXS.pdf",'latin1');
asset('Hidden','Hidden_HDDN_SXS.pdf');asset('A','zzDEL!_SXS.pdf');asset('A','.secret_SXS.pdf');asset('A','ambiguous_SXS_TBS.pdf');asset('A','fake_SXS.php');
symlink($root.'/library/A/A_SXS.pdf',$root.'/library/A/link_SXS.pdf');symlink($root.'/library/A',$root.'/library/Linked');
rep('One.txt',array('A','B','A','Pause','C[1]','Été',"M\xfcller",'Hidden','Linked','../outside'));
$path=nadin_zip_bundle($root,'One.txt','allsaxes');$files=contents($path);
ok(count($files)===6,'unique section files, multiple Parts fallback, bracket/Unicode/Latin1 names');
ok($files['A/A_SXS.pdf']==='saxes' && !isset($files['A/A_PRTS.pdf']),'prefer actual section');
ok($files['B/B_PRTS.pdf']==='fallback' && $files['B/B_PRTS_second.pdf']==='second','empty section uses all readable Parts');
ok($files['C[1]/C_SXS.pdf']==='literal brackets' && $files['Müller/Müller_SXS.pdf']==='latin1','literal source names and UTF-8 ZIP names');
$mtime=filemtime($path);ok(nadin_zip_bundle($root,'One.txt','allsaxes')===$path && filemtime($path)===$mtime,'valid cache reused');
file_put_contents($path,'broken');ok(contents(nadin_zip_bundle($root,'One.txt','allsaxes'))===$files,'corrupt ZIP rebuilt');
file_put_contents($path.'.json','broken');ok(contents(nadin_zip_bundle($root,'One.txt','allsaxes'))===$files,'corrupt manifest rebuilt');
$oldSource=$root.'/library/A/A_SXS.pdf';$mtime=filemtime($oldSource);file_put_contents($oldSource,'SAXES');touch($oldSource,$mtime);
$new=nadin_zip_bundle($root,'One.txt','allsaxes');ok($new!==$path && contents($new)['A/A_SXS.pdf']==='SAXES','same size/mtime source change detected');
ok(!file_exists($path),'superseded cache pruned');
rep('One.txt',array('B'),'gigrepsarchiv');$archive=nadin_zip_bundle($root,'One.txt','allsaxes','gigrepsarchiv');ok($archive!==$new && count(contents($archive))===2,'same-name archived gig isolated');
$stream=nadin_zip_prepare($root,'One.txt','allsaxes','gigreps',true);$oldBytes=stream_get_contents($stream['stream']);rewind($stream['stream']);
asset('A','A_SXS.pdf','next revision');$latest=nadin_zip_bundle($root,'One.txt','allsaxes');ok(!file_exists($new) && stream_get_contents($stream['stream'])===$oldBytes,'open download survives replacement and pruning');ok($stream['size']===strlen($oldBytes),'correct streaming length');fclose($stream['stream']);
$audio=nadin_zip_bundle($root,'One.txt','alllistento');$z=new ZipArchive();$z->open($audio);ok($z->numFiles===1 && $z->statIndex(0)['comp_method']===ZipArchive::CM_STORE,'MP3 is stored without wasteful recompression');$z->close();
rep('Empty.txt',array('Missing'));fails(function()use($root){nadin_zip_bundle($root,'Empty.txt','allsaxes');},'empty gig has no bogus ZIP');
foreach(array('../One.txt','.hidden.txt',"evil\0.txt",'One.pdf') as $bad)fails(function()use($root,$bad){nadin_zip_bundle($root,$bad,'allsaxes');},'invalid gig path');
fails(function()use($root){nadin_zip_bundle($root,'One.txt','bad');},'unknown type');fails(function()use($root){nadin_zip_bundle($root,'One.txt','allsaxes','library');},'unknown mode');
unlink($root.'/library/A/A_SXS.pdf');$without=contents(nadin_zip_bundle($root,'One.txt','allsaxes'));ok(!isset($without['A/A_SXS.pdf']) && isset($without['A/A_PRTS.pdf']),'removed section replaced with Parts');
$target=nadin_zip_bundle($root,'One.txt','allsaxes');unlink($target);symlink($root.'/library/A/A_PRTS.pdf',$target);fails(function()use($root){nadin_zip_bundle($root,'One.txt','allsaxes');},'symlink cache refused');unlink($target);
if (function_exists('pcntl_fork')) {
    $children=array();for($i=0;$i<6;$i++){ $pid=pcntl_fork();if($pid===0){try{$d=nadin_zip_prepare($root,'One.txt','allsaxes','gigreps',true);$b=stream_get_contents($d['stream']);fclose($d['stream']);file_put_contents($root.'/worker-'.$i,$b);exit(0);}catch(Throwable $e){exit(1);}}$children[]=$pid; }
    foreach($children as $pid){pcntl_waitpid($pid,$status);ok(pcntl_wexitstatus($status)===0,'concurrent ZIP worker completes');}
    $hashes=array();for($i=0;$i<6;$i++)$hashes[]=hash_file('sha256',$root.'/worker-'.$i);ok(count(array_unique($hashes))===1,'concurrent requests receive identical complete archive');
    $lock=nadin_zip_lock($root.'/userdatei/admin-v12-lock.php');$pid=pcntl_fork();
    if($pid===0){fclose($lock);nadin_zip_bundle($root,'One.txt','allsaxes');file_put_contents($root.'/blocked','done');exit(0);}
    usleep(150000);ok(!is_file($root.'/blocked'),'ZIP waits for active admin write');flock($lock,LOCK_UN);fclose($lock);pcntl_waitpid($pid,$status);ok(pcntl_wexitstatus($status)===0 && is_file($root.'/blocked'),'ZIP resumes after admin write');
}
$leftovers=array();$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/abo',FilesystemIterator::SKIP_DOTS));foreach($it as $file)if(strpos($file->getFilename(),'.build-')===0)$leftovers[]=$file->getPathname();ok(!$leftovers,'no partial build files left');
echo 'PASS: '.$checks." ZIP integrity/concurrency checks\n";
} finally {rmTree($root);}

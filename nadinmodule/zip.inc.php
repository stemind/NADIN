<?php
require_once __DIR__.'/ui.inc.php';
final class NadinZipChanged extends RuntimeException {}
function nadin_zip_types() {
    global $saxes,$bones,$trumpets,$rhythm,$other,$parts,$score,$recommended;
    return array('alllistento'=>array($recommended,'vgigs','Audio'),'allscore'=>array($score,'vscore','Score'),'allparts'=>array($parts,'vgigs','Parts'),'allsaxes'=>array($saxes,'vsaxes','Saxophones'),'allbones'=>array($bones,'vbones','Trombones'),'alltrumpets'=>array($trumpets,'vtrumpets','Trumpets'),'allrhythm'=>array($rhythm,'vrhythm','Rhythm'),'allother'=>array($other,'vother','Other'));
}
function nadin_zip_sources($root,$gig,$type,$mode='gigreps') {
    global $delimiter2,$hidden;
    $types=nadin_zip_types();
    if (!isset($types[$type]) || !in_array($mode,array('gigreps','gigrepsarchiv'),true) || !is_string($gig) || $gig==='' || $gig[0]==='.' || basename($gig)!==$gig || substr($gig,-4)!=='.txt' || preg_match('/[\\\\\x00-\x1f\x7f]/',$gig)) throw new InvalidArgumentException('Invalid download.');
    $rep=$root.'/'.$mode.'/'.$gig;
    if (!is_file($rep) || is_link($rep) || is_link(dirname($rep))) throw new InvalidArgumentException('Gig not found.');
    list($info,$tunes)=nadinReadRep($rep,$delimiter2);$sources=array();
    foreach (array_unique(array_map('trim',$tunes)) as $tune) {
        if ($tune==='' || basename($tune)!==$tune || $tune[0]==='.' || preg_match('/[\\\\\x00-\x1f\x7f]/',$tune)) continue;
        $dir=$root.'/library/'.$tune;
        if (!is_dir($dir) || is_link($dir) || is_link(dirname($dir))) continue;
        $selected=array();$fallback=array();$files=array();
        // DirectoryIterator treats brackets and other glob characters literally.
        foreach (new DirectoryIterator($dir) as $entry) {
            if ($entry->isDot() || $entry->isLink() || !$entry->isFile()) continue;
            $name=$entry->getFilename();$f=$entry->getPathname();$relative=$tune.'/'.$name;
            if ($name[0]==='.' || preg_match('/[\\\\\x00-\x1f\x7f]/',$name) || strpos($name,'zzDEL!')===0 || ($hidden!=='' && strpos($relative,$hidden)!==false)) continue;
            if (!$entry->isReadable() || $entry->getSize()===0) continue;
            $files[$name]=$f;
        }
        ksort($files,SORT_STRING);
        foreach ($files as $name=>$f) {
            $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$matches=array();
            // Keep the same conservative marker/rights interpretation as direct downloads.
            foreach ($types as $key=>$profile) if ($profile[0]!=='' && strpos($tune.'/'.$name,$profile[0])!==false) $matches[]=$key;
            if (count($matches)!==1) continue;
            if ($matches[0]===$type && ($type==='alllistento' ? $ext==='mp3' : $ext==='pdf')) $selected[]=$f;
            if ($matches[0]==='allparts' && $ext==='pdf') $fallback[]=$f;
        }
        if (!$selected && in_array($type,array('allsaxes','allbones','alltrumpets','allrhythm','allother'),true)) $selected=$fallback;
        foreach ($selected as $f) $sources[$tune.'/'.basename($f)]=$f;
    }
    return $sources;
}
function nadin_zip_snapshot($root,$gig,$type,$mode) {
    clearstatcache();
    $sources=nadin_zip_sources($root,$gig,$type,$mode);$entries=array();$fingerprint=$mode."\0".$gig."\0".$type."\0";
    foreach ($sources as $name=>$source) {
        $hash=@hash_file('sha256',$source);$size=@filesize($source);
        if ($hash===false || $size===false || $size===0) throw new NadinZipChanged('Files changed while preparing the download. Please retry.');
        $entry=nadinText($name);
        if (isset($entries[$entry])) throw new RuntimeException('Two files have the same ZIP name after text conversion.');
        $entries[$entry]=array('source'=>$source,'hash'=>$hash,'size'=>$size);
        $fingerprint.=base64_encode($name).'|'.$size.'|'.$hash."\n";
    }
    return array('hash'=>hash('sha256',$fingerprint),'entries'=>$entries);
}
function nadin_zip_lock($path,$shared=false) {
    if (is_link($path)) throw new RuntimeException('Unsafe ZIP lock.');
    $h=@fopen($path,'c+');
    if (!$h) throw new RuntimeException('Cannot open ZIP lock.');
    if (!flock($h,$shared ? LOCK_SH : LOCK_EX)) { fclose($h);throw new RuntimeException('Cannot lock ZIP storage.'); }
    if (!fstat($h)['size']) {
        if ($shared && !flock($h,LOCK_EX)) { fclose($h);throw new RuntimeException('Cannot initialise ZIP lock.'); }
        if (!fstat($h)['size']) { $guard="<?php http_response_code(404); exit; ?>\n";if (fwrite($h,$guard)!==strlen($guard) || !fflush($h)) { flock($h,LOCK_UN);fclose($h);throw new RuntimeException('Cannot initialise ZIP lock.'); } }
        if ($shared && !flock($h,LOCK_SH)) { fclose($h);throw new RuntimeException('Cannot share ZIP lock.'); }
    }
    @chmod($path,0600);return $h;
}
function nadin_zip_cache_valid($target,$manifest,$snapshot) {
    if (!is_file($target) || !is_file($manifest) || is_link($target) || is_link($manifest)) return false;
    $data=json_decode((string)@file_get_contents($manifest),true);
    if (!is_array($data) || ($data['source'] ?? '')!==$snapshot['hash'] || !is_string($data['zip'] ?? null)) return false;
    $actual=@hash_file('sha256',$target);
    if (!$actual || !hash_equals($data['zip'],$actual)) return false;
    $zip=new ZipArchive();
    if ($zip->open($target,ZipArchive::CHECKCONS)!==true) return false;
    try {
        if ($zip->numFiles!==count($snapshot['entries'])) return false;
        foreach ($snapshot['entries'] as $name=>$entry) { $stat=$zip->statName($name);if (!$stat || $stat['size']!==$entry['size']) return false; }
        return true;
    } finally { $zip->close(); }
}
/** The streaming caller obtains an open inode before any locks are released. */
function nadin_zip_prepare($root,$gig,$type,$mode='gigreps',$open=false) {
    if (!class_exists('ZipArchive')) throw new RuntimeException('Enable the PHP zip extension.');
    $admin=nadin_zip_lock($root.'/userdatei/admin-v12-lock.php',true);$lock=null;$tmp=null;
    try {
        $snapshot=nadin_zip_snapshot($root,$gig,$type,$mode);
        if (!$snapshot['entries']) throw new InvalidArgumentException('No readable files are available for this download.');
        $key=hash('sha256',$mode."\0".$gig."\0".$type);
        $lock=nadin_zip_lock($root.'/userdatei/zip-'.$key.'.php');$dir=$root;
        foreach (array('abo','zip','.cache-v16',$key) as $part) {
            $dir.='/'.$part;
            if (is_link($dir) || (!is_dir($dir) && !@mkdir($dir,0700) && !is_dir($dir))) throw new RuntimeException('Cannot prepare safe ZIP storage.');
        }
        for ($attempt=0;$attempt<3;$attempt++) {
            $snapshot=nadin_zip_snapshot($root,$gig,$type,$mode);
            if (!$snapshot['entries']) throw new InvalidArgumentException('No readable files are available for this download.');
            $target=$dir.'/'.$snapshot['hash'].'.zip';$manifest=$target.'.json';
            if (is_link($target) || is_link($manifest)) throw new RuntimeException('Unsafe ZIP cache.');
            if (!nadin_zip_cache_valid($target,$manifest,$snapshot)) {
                $tmp=$dir.'/.build-'.bin2hex(random_bytes(12)).'.zip';$zip=new ZipArchive();
                if ($zip->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Cannot create ZIP.');
                try {
                    foreach ($snapshot['entries'] as $name=>$entry) {
                        if (!$zip->addFile($entry['source'],$name)) throw new NadinZipChanged('A source file changed. Please retry.');
                        if ($type==='alllistento') $zip->setCompressionName($name,ZipArchive::CM_STORE);
                    }
                    if (!$zip->close()) throw new NadinZipChanged('Could not finish the ZIP. Check free disk space and retry.');
                } catch (Throwable $e) { try { $zip->close(); } catch (Throwable $ignored) {} throw $e; }
                // Check embedded bytes, including replacements made outside NADIN (FTP).
                $check=new ZipArchive();$stable=$check->open($tmp,ZipArchive::CHECKCONS)===true;
                if ($stable) {
                    try {
                        foreach ($snapshot['entries'] as $name=>$entry) {
                            $stream=$check->getStream($name);
                            if (!$stream) { $stable=false;break; }
                            $ctx=hash_init('sha256');$bytes=hash_update_stream($ctx,$stream);fclose($stream);
                            if ($bytes!==$entry['size'] || !hash_equals($entry['hash'],hash_final($ctx))) { $stable=false;break; }
                        }
                    } finally { $check->close(); }
                }
                if (!$stable || nadin_zip_snapshot($root,$gig,$type,$mode)['hash']!==$snapshot['hash']) { @unlink($tmp);$tmp=null;continue; }
                $zipHash=hash_file('sha256',$tmp);
                if (!$zipHash || !@rename($tmp,$target)) throw new RuntimeException('Cannot save the ZIP.');
                $tmp=$manifest.'.'.bin2hex(random_bytes(8)).'.tmp';
                $json=json_encode(array('source'=>$snapshot['hash'],'zip'=>$zipHash),JSON_THROW_ON_ERROR);
                if (file_put_contents($tmp,$json,LOCK_EX)!==strlen($json) || !rename($tmp,$manifest)) throw new RuntimeException('Cannot save ZIP integrity information.');
                $tmp=null;
            }
            if (nadin_zip_snapshot($root,$gig,$type,$mode)['hash']!==$snapshot['hash']) continue;
            $stream=$open ? @fopen($target,'rb') : null;
            if ($open && !$stream) throw new RuntimeException('Cannot open the ZIP download.');
            // Superseded versions are disposable; an already open download remains valid.
            foreach (new DirectoryIterator($dir) as $old) if ($old->isFile() && !$old->isLink() && $old->getPathname()!==$target && $old->getPathname()!==$manifest && preg_match('/^[a-f0-9]{64}\.zip(?:\.json)?$/D',$old->getFilename())) @unlink($old->getPathname());
            return $open ? array('stream'=>$stream,'size'=>fstat($stream)['size'],'name'=>nadinText(substr($gig,0,-4).'_'.$type.'.zip')) : $target;
        }
        throw new NadinZipChanged('Files are being updated. Please try the download again.');
    } finally {
        if ($tmp && is_file($tmp)) @unlink($tmp);
        if ($lock) { flock($lock,LOCK_UN);fclose($lock); }
        flock($admin,LOCK_UN);fclose($admin);
    }
}
function nadin_zip_bundle($root,$gig,$type,$mode='gigreps') { return nadin_zip_prepare($root,$gig,$type,$mode); }

<?php
/** V12 administrative storage. The existing text files remain authoritative. */
require_once __DIR__.'/ui.inc.php';
final class NadinAdmin {
    private $root, $separator;
    public function __construct($root, $separator) { $this->root = rtrim($root,'/'); $this->separator = $separator; }
    public static function rights() {
        return array('vgigs'=>'Current gigs & unsplit parts','vsaxes'=>'Saxophones','vbones'=>'Trombones','vtrumpets'=>'Trumpets','vrhythm'=>'Rhythm section','vother'=>'Other parts','vscore'=>"Conductor’s score",'vreclist'=>'Band recordings','varchiv'=>'Archived gigs','vlibrary'=>'Complete library','upaudio'=>'Edit tune info & audio','uppdf'=>'Manage sheet music & tunes','mgigs'=>'Manage gigs & setlists','musers'=>'Manage people, permissions & hidden files');
    }
    public function locked($fn) {
        $p=$this->root.'/userdatei/admin-v12-lock.php';
        if (is_link($p)) throw new RuntimeException('Unsafe storage path.');
        $h=@fopen($p,'c+');
        if (!$h || !flock($h,LOCK_EX)) throw new RuntimeException('Storage is busy. Please try again.');
        try { if (!fstat($h)['size']) { fwrite($h,"<?php http_response_code(404); exit; ?>\n"); fflush($h); } @chmod($p,0600); return $fn(); }
        finally { flock($h,LOCK_UN); fclose($h); }
    }
    private function atomic($path,$text) {
        if (is_link($path)) throw new RuntimeException('Unsafe storage path.');
        if (!is_dir(dirname($path)) && !mkdir(dirname($path),0700,true)) throw new RuntimeException('Could not create storage.');
        $tmp=dirname($path).'/.v12-'.bin2hex(random_bytes(10)).'.php';
        try { if (file_put_contents($tmp,$text,LOCK_EX)!==strlen($text) || !rename($tmp,$path)) throw new RuntimeException('Could not save. Your changes are still in the editor.'); }
        finally { if (is_file($tmp)) @unlink($tmp); }
    }
    private function backup($path) {
        if (!is_file($path)) return;
        $dir=$this->root.'/userdatei/revisions';
        if (!is_dir($dir) && !mkdir($dir,0700)) throw new RuntimeException('Could not create a safety copy.');
        $data=json_encode(array('path'=>substr($path,strlen($this->root)+1),'saved'=>gmdate('c'),'data'=>base64_encode(file_get_contents($path))),JSON_THROW_ON_ERROR);
        $this->atomic($dir.'/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(6)).'.php',"<?php http_response_code(404); exit; ?>\n".$data);
    }
    private function folder($mode) {
        $map=array('current'=>'gigreps','archive'=>'gigrepsarchiv','trash'=>'gigrepsdeleted');
        if (!isset($map[$mode])) throw new InvalidArgumentException('Unknown gig list.');
        return $this->root.'/'.$map[$mode];
    }
    private function path($mode,$id) {
        if (!is_string($id) || $id==='' || basename($id)!==$id || strpos($id,'\\')!==false || substr($id,-4)!=='.txt' || preg_match('/[\x00-\x1f]/',$id)) throw new InvalidArgumentException('Invalid gig.');
        $p=$this->folder($mode).'/'.$id;
        if (!is_file($p) || is_link($p)) throw new InvalidArgumentException('This gig no longer exists. Reload the list.');
        return $p;
    }
    private function original($id,$mode) { return $mode==='trash' ? preg_replace('/^(?:[0-9]+_[a-f0-9]+!|[0-9]+_)/','',$id) : $id; }
    private function attachment($mode,$id) { return $mode==='trash' ? $this->folder('trash').'/'.substr($id,0,-4).'.pdf' : $this->root.'/abo/zip/'.substr($id,0,-4).'.pdf'; }
    private function revision($mode,$id) {
        $p=$this->path($mode,$id); $a=$this->attachment($mode,$id);
        return hash('sha256',file_get_contents($p).'|'.(is_file($a) ? hash_file('sha256',$a) : ''));
    }
    private function expected($mode,$id,$revision) {
        if (!is_string($revision) || !hash_equals($this->revision($mode,$id),$revision)) throw new DomainException('This gig changed in another window. Your work is still here. Reload before saving again.');
    }
    public function gigs($mode='current') {
        $rows=array();
        foreach (nadinRepFiles($this->folder($mode),$mode!=='current') as $id) $rows[]=array('id'=>$id,'title'=>nadinText(substr($this->original($id,$mode),0,-4)));
        return $rows;
    }
    public function gig($mode,$id) {
        $p=$this->path($mode,$id); list($info,$tunes)=nadinReadRep($p,$this->separator);
        return array('id'=>$id,'mode'=>$mode,'title'=>nadinText(substr($this->original($id,$mode),0,-4)),'infos'=>nadinText(rtrim($info)),'tunes'=>array_map('nadinText',$tunes),'revision'=>$this->revision($mode,$id),'attachment'=>is_file($this->attachment($mode,$id)));
    }
    public function catalog() {
        global $saxes,$bones,$trumpets,$rhythm,$other,$parts,$score,$recommended;
        $tags=array('sx'=>$saxes,'tb'=>$bones,'tp'=>$trumpets,'rh'=>$rhythm,'ot'=>$other,'Parts'=>$parts,'Score'=>$score,'Audio'=>$recommended);
        $rows=array();
        foreach ((array)glob($this->root.'/library/*',GLOB_ONLYDIR) as $dir) {
            if (is_link($dir)) continue;
            $found=array();
            foreach ((array)glob($dir.'/*') as $f) if (is_file($f) && !is_link($f)) foreach ($tags as $label=>$tag) if ($tag!=='' && strpos(basename($f),$tag)!==false) $found[$label]=true;
            $rows[]=array('id'=>basename($dir),'title'=>nadinText(str_replace('_',' ',basename($dir))),'files'=>array_keys($found));
        }
        usort($rows,function($a,$b){return strnatcasecmp($a['title'],$b['title']);}); return $rows;
    }
    /** Caller already holds the shared admin lock (legacy-write.inc.php). */
    public function renameTuneLocked($old,$new) {
        foreach(array($old,$new) as $name) if(!is_string($name)||strlen($name)<3||$name[0]==='.'||basename($name)!==$name||preg_match('/[\\\\\x00-\x1f<>]/',$name)) throw new InvalidArgumentException('Invalid tune name.');
        $source=$this->root.'/library/'.$old;$target=$this->root.'/library/'.$new;
        if(!is_dir($source)||is_link($source))throw new InvalidArgumentException('The original tune does not exist.');
        if(file_exists($target))throw new DomainException('A tune with this name already exists.');
        $updates=array();
        foreach(array('current','archive','trash') as $mode)foreach(nadinRepFiles($this->folder($mode)) as $id){
            $p=$this->folder($mode).'/'.$id;$original=file_get_contents($p);$parts=explode($this->separator,$original,2);if(count($parts)!==2)continue;
            $lines=preg_split('/(\r\n|\n|\r)/',$parts[1],-1,PREG_SPLIT_DELIM_CAPTURE);$changed=false;
            foreach($lines as $i=>$line)if($i%2===0&&trim($line)===$old){$lines[$i]=str_replace($old,$new,$line);$changed=true;}
            if($changed){$this->backup($p);$updates[$p]=array($original,$parts[0].$this->separator.implode('',$lines));}
        }
        if(!rename($source,$target))throw new RuntimeException('Could not rename the tune.');
        $done=array();
        try{foreach($updates as $p=>$texts){$this->atomic($p,$texts[1]);$done[]=$p;}}
        catch(Throwable $e){foreach($done as $p)$this->atomic($p,$updates[$p][0]);rename($target,$source);throw $e;}
        // Media files and the hidden forScore identity registry move together unchanged.
        return count($updates);
    }
    private function newName($date,$title) {
        if (!is_string($date) || !is_string($title)) throw new InvalidArgumentException('Enter a date and a gig name.');
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if (!$d || $d->format('Y-m-d')!==$date) throw new InvalidArgumentException('Enter a valid gig date.');
        $title=trim(preg_replace('/\s+/u',' ',$title));
        if ($title==='' || strlen($title)>120 || !preg_match('/^[\pL\pN _().&+\-]+$/u',$title)) throw new InvalidArgumentException('Use a name of up to 120 bytes with letters, numbers, spaces, hyphens or parentheses.');
        // Keep ASCII names compatible with legacy download, ZIP and print routes.
        $title=strtr($title,array('ä'=>'ae','ö'=>'oe','ü'=>'ue','Ä'=>'Ae','Ö'=>'Oe','Ü'=>'Ue','ß'=>'ss','&'=>'and','+'=>'and'));
        $title=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title); $title=preg_replace('/[^A-Za-z0-9 _().-]/','',$title);
        if (trim($title)==='') throw new InvalidArgumentException('The gig name needs at least one Latin letter or number.');
        $id=$date.' '.$title.'.txt';
        foreach (array('current','archive') as $mode) if (file_exists($this->folder($mode).'/'.$id)) throw new DomainException('A current or archived gig already has this name. Choose another name.');
        if (file_exists($this->attachment('current',$id))) throw new DomainException('An attachment already uses this name. Choose another name.');
        return $id;
    }
    private function gigText($infos,$tunes) {
        if (!is_string($infos) || strlen($infos)>100000 || strpos($infos,$this->separator)!==false || !is_array($tunes) || count($tunes)>1000) throw new InvalidArgumentException('Invalid gig information or setlist.');
        foreach ($tunes as &$t) {
            if (!is_string($t) || strlen($t)>250 || preg_match('/[\x00-\x1f<>|\\\\\/]/',$t) || strpos($t,$this->separator)!==false) throw new InvalidArgumentException('Invalid setlist entry.');
            $t=trim($t);
        } unset($t);
        return rtrim($infos)."\n\n".$this->separator."\n".implode("\n",$tunes);
    }
    public function saveGig($data) {
        return $this->locked(function() use($data) {
            $id=$data['id'] ?? ''; $text=$this->gigText($data['infos'] ?? '',$data['tunes'] ?? array());
            if ($id==='') { $id=$this->newName($data['date'] ?? '',$data['title'] ?? ''); $p=$this->folder('current').'/'.$id; }
            else { $p=$this->path('current',$id); $this->expected('current',$id,$data['revision'] ?? ''); $this->backup($p); }
            $this->atomic($p,$text); $this->invalidate($id);
            return $this->gig('current',$id);
        });
    }
    private function invalidate($id) {
        foreach (array('alllistento','allscore','allparts','allsaxes','allbones','alltrumpets','allrhythm','allother') as $type) {
            $p=$this->root.'/abo/zip/'.$type.'/'.substr($id,0,-4).'_'.$type.'.zip'; if (is_file($p)) @unlink($p);
        }
    }
    public function gigAction($data) {
        return $this->locked(function() use($data) {
            $mode=$data['mode'] ?? 'current'; $id=$data['id'] ?? ''; $action=$data['action'] ?? '';
            $p=$this->path($mode,$id); $this->expected($mode,$id,$data['revision'] ?? '');
            $targetMode=$mode; $target=$id; $copy=false;
            if ($action==='copy' || $action==='rename') {
                if ($mode==='trash' || ($action==='rename' && $mode!=='current')) throw new InvalidArgumentException('Restore this gig first.');
                $target=$this->newName($data['date'] ?? '',$data['title'] ?? ''); $targetMode='current'; $copy=$action==='copy';
            } elseif ($action==='archive' && $mode==='current') $targetMode='archive';
            elseif ($action==='restore' && ($mode==='archive' || $mode==='trash')) { $targetMode='current'; $target=$this->original($id,$mode); }
            elseif ($action==='trash' && $mode!=='trash') { $targetMode='trash'; $target=time().'_'.bin2hex(random_bytes(4)).'!'.$id; }
            else throw new InvalidArgumentException('This action is not available.');
            $dest=$this->folder($targetMode).'/'.$target;
            if (file_exists($dest)) throw new DomainException('The destination already contains a gig with this name. Nothing was overwritten.');
            if ($action==='restore') foreach (array('current','archive') as $m) if ($m!==$mode && file_exists($this->folder($m).'/'.$target)) throw new DomainException('A gig with this name already exists.');
            $a=$this->attachment($mode,$id); $b=$this->attachment($targetMode,$target); $moved=false;
            if ($a!==$b && is_file($a)) {
                if (file_exists($b)) throw new DomainException('An attachment already has this name. Nothing was overwritten.');
                if (!($copy ? copy($a,$b) : rename($a,$b))) throw new RuntimeException('Could not transfer the attachment.'); $moved=true;
            }
            if (!($copy ? copy($p,$dest) : rename($p,$dest))) {
                if ($moved) { if ($copy) @unlink($b); else rename($b,$a); }
                throw new RuntimeException('Could not complete the action. The original gig is intact.');
            }
            $this->invalidate($id); return $this->gig($targetMode,$target);
        });
    }
    public function uploadAttachment($data,$file) {
        return $this->locked(function() use($data,$file) {
            $id=$data['id'] ?? ''; $this->expected('current',$id,$data['revision'] ?? '');
            if (!$file || ($file['error'] ?? 1)!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size']>20*1024*1024 || file_get_contents($file['tmp_name'],false,null,0,5)!=='%PDF-') throw new InvalidArgumentException('Choose a PDF up to 20 MB (or the hosting upload limit).');
            $p=$this->attachment('current',$id); $this->backup($p); $this->atomic($p,file_get_contents($file['tmp_name'])); return $this->gig('current',$id);
        });
    }
    public function people() {
        $text=file_get_contents($this->root.'/userdatei/userdatei.txt'); $rows=array(); $unparsed=0;
        foreach (preg_split('/\r\n|\n|\r/',$text) as $line) {
            if (!trim($line)) continue; $f=array_map('trim',explode(',',$line));
            if (count($f)<3 || !filter_var($f[1],FILTER_VALIDATE_EMAIL)) { $unparsed++; continue; }
            $d=DateTimeImmutable::createFromFormat('!d.m.Y',$f[2]);
            if (!$d || $d->format('d.m.Y')!==$f[2]) { $unparsed++; continue; }
            $rights=array(); foreach (array_slice($f,3) as $r) if (preg_match('/^([a-z]+)\s*=\s*1$/i',$r,$m)) $rights[]=strtolower($m[1]);
            $rows[]=array('email'=>strtolower($f[1]),'name'=>nadinText($f[0]),'expiry'=>$d->format('Y-m-d'),'expired'=>$d->getTimestamp()<time(),'rights'=>$rights,'revision'=>hash('sha256',$line));
        }
        usort($rows,function($a,$b){return strnatcasecmp($a['name'],$b['name']);});
        return array('rows'=>$rows,'unparsed'=>$unparsed);
    }
    public function savePerson($data,$auth,$actor) {
        return $this->locked(function() use($data,$auth,$actor) {
            $path=$this->root.'/userdatei/userdatei.txt'; $text=file_get_contents($path); $lines=preg_split('/\r\n|\n|\r/',$text);
            $original=strtolower(trim($data['original'] ?? '')); $delete=($data['action'] ?? '')==='delete'; $index=null; $existingRights=array();
            foreach ($lines as $i=>$line) { $f=array_map('trim',explode(',',$line)); if (isset($f[1]) && strtolower($f[1])===$original && $original!=='') { if ($index!==null) throw new DomainException('Duplicate email in the existing list. Correct the source list before editing this person.'); $index=$i; } }
            if ($original!=='' && ($index===null || !hash_equals(hash('sha256',$lines[$index]),$data['revision'] ?? ''))) throw new DomainException('This person changed in another window. Your work is still here. Reload before saving again.');
            if ($delete && $original===$actor) throw new InvalidArgumentException('You cannot remove your own account.');
            if (!$delete) {
                $email=strtolower(trim($data['email'] ?? '')); $name=trim($data['name'] ?? ''); $expiry=$data['expiry'] ?? ''; $rights=$data['rights'] ?? array();
                if (!is_string($name) || $name==='' || strlen($name)>160 || preg_match('/[,\x00-\x1f]/',$name) || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a name and a valid email address. Commas are not allowed in names.');
                if ($original!=='' && $original!==$email) throw new InvalidArgumentException('Email addresses identify accounts. Add the new address as a new person to keep passwords unambiguous.');
                $d=DateTimeImmutable::createFromFormat('!Y-m-d',$expiry);
                if (!$d || $d->format('Y-m-d')!==$expiry) throw new InvalidArgumentException('Choose a valid expiry date.');
                if (!is_array($rights) || array_diff($rights,array_keys(self::rights()))) throw new InvalidArgumentException('Unknown permission.');
                $rights=array_values(array_unique($rights));
                if (in_array('uppdf',$rights,true) && !in_array('upaudio',$rights,true)) throw new InvalidArgumentException('Managing sheet music also requires tune info & audio access.');
                if ((in_array('mgigs',$rights,true) || in_array('upaudio',$rights,true)) && (!in_array('vlibrary',$rights,true) || !in_array('vgigs',$rights,true))) throw new InvalidArgumentException('Managing gigs or tunes requires current gigs and library access.');
                if ($email===$actor && (!in_array('musers',$rights,true) || $d->getTimestamp()<time())) throw new InvalidArgumentException('Keep your own account active with permission to manage people.');
                foreach ($lines as $i=>$line) { $f=array_map('trim',explode(',',$line)); if ($i!==$index && isset($f[1]) && strtolower($f[1])===$email) throw new DomainException('This email already belongs to a person.'); }
                // Preserve extension-defined permissions not represented by the standard UI.
                if ($index!==null) foreach (array_slice(explode(',',$lines[$index]),3) as $r) if (preg_match('/^\s*([a-z]+)\s*=\s*1\s*$/i',$r,$m) && !isset(self::rights()[strtolower($m[1])])) $existingRights[]=strtolower($m[1]);
                $new=$name.', '.$email.', '.$d->format('d.m.Y').', '.implode(', ',array_map(function($r){return $r.'=1';},array_merge($rights,$existingRights))).',';
                if ($index===null) $lines[]=$new; else $lines[$index]=$new;
            } else { if ($index===null) throw new InvalidArgumentException('Choose a person.'); unset($lines[$index]); }
            $result=implode("\n",$lines);
            $activeAdmins=0;
            foreach (explode("\n",$result) as $line) { $f=array_map('trim',explode(',',$line)); $d=isset($f[2]) ? DateTimeImmutable::createFromFormat('!d.m.Y',$f[2]) : false; if ($d && $d->getTimestamp()>=time() && preg_match('/(?:^|,)\s*musers\s*=\s*1\s*(?:,|$)/i',$line)) $activeAdmins++; }
            if (!$activeAdmins) throw new InvalidArgumentException('Keep at least one active person with permission to manage people.');
            $this->backup($path); $auth->saveMembers($result,hash('sha256',$text),nadin_session_token()); return $delete ? '' : $email;
        });
    }
}

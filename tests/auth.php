<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/nadinmodule/auth.inc.php';
$root = sys_get_temp_dir().'/nadin-auth-test-'.bin2hex(random_bytes(6)); mkdir($root); mkdir($root.'/userdatei');
$lines = "Member, member@example.invalid, 31.12.2099, vgigs=1, vsaxes=1,\nAdmin, admin@example.invalid, 31.12.2099, vgigs=1, musers=1,\nExpired, expired@example.invalid, 01.01.2000, vgigs=1,\n";
file_put_contents($root.'/userdatei/userdatei.txt', $lines);
$auth = new NadinAuth($root, 'test-secret', 3600, false, '192.0.2.1'); $checks = 0;
function ok($test,$message) { global $checks; if (!$test) throw new RuntimeException('FAIL: '.$message); $checks++; }
function db() { global $root; return json_decode(substr(file_get_contents($root.'/userdatei/auth-v11.php'),strlen(NadinAuth::GUARD)),true); }
function putdb($db) { global $root; file_put_contents($root.'/userdatei/auth-v11.php',NadinAuth::GUARD.json_encode($db)); }
function throws($f,$message) { try{$f();}catch(RuntimeException $e){ok(true,$message);return;}ok(false,$message); }
try {
$email='member@example.invalid';$initial=$auth->initial($email);$pass='Personal Password 123';
ok($auth->active($email,'vsaxes') && !$auth->active($email,'musers'),'rights unchanged');
ok(!$auth->active('expired@example.invalid'),'expired member');
ok(!$auth->active('ember@example.invalid'),'exact email matching');
ok(!$auth->login('expired@example.invalid',$auth->initial('expired@example.invalid')),'expired login denied');
ok(!$auth->login('missing@example.invalid','wrong'),'unknown account denied');
ok(!$auth->login($email,'wrong'),'wrong initial denied');
$t1=$auth->login(strtoupper($email),strtoupper($initial));ok(is_string($t1)&&strlen($t1)===64,'legacy initial case compatibility');
$t2=$auth->login($email,$initial);ok($t1!==$t2,'random independent sessions');
ok($auth->session($t1)===$email && $auth->session($t2)===$email,'both sessions valid');
ok($auth->session(sha1('test-secret'.$initial))==='','legacy derived cookie rejected');
ok($auth->session(str_repeat('a',64))==='','forged session denied');
ok(!$auth->hasPersonal($email),'initial account status');
$r=$auth->recovery($email);ok($r['kind']==='initial'&&$r['secret']===$initial,'initial onboarding unchanged');
ok($auth->recovery('missing@example.invalid')===null,'unknown recovery no email');
ok($auth->recovery('expired@example.invalid')===null,'expired recovery no email');
ok($auth->csrf('change',$t1)!==$auth->csrf('users',$t1),'csrf scoped');
$csrf=$auth->csrf('change',$t1);ok($auth->csrf('change',$t1,$csrf),'csrf valid');
ok(!$auth->csrf('change',$t2,$csrf)&&!$auth->csrf('users',$t1,$csrf),'csrf bound to session and action');
ok(!$auth->csrf('change',$t1,'0.deadbeef'),'expired/forged csrf');
ok(NadinAuth::passwordError('short','short')!=='','short personal password denied');
ok(NadinAuth::passwordError(str_repeat('a',73),str_repeat('a',73))!=='','no bcrypt truncation');
ok(NadinAuth::passwordError("a\0".str_repeat('x',20),"a\0".str_repeat('x',20))!=='','NUL rejected');
ok(NadinAuth::passwordError('Müster längere Worte','Müster längere Worte')==='','Unicode and spaces accepted');
ok(!$auth->change($t1,'wrong',$pass,$pass),'current password required');
ok(!$auth->change($t1,$initial,$pass,'mismatch'),'confirmation required');
$t3=$auth->change($t1,$initial,$pass,$pass);ok(is_string($t3),'personal password set');
ok($auth->hasPersonal($email),'personal account status');
ok($auth->session($t1)===''&&$auth->session($t2)===''&&$auth->session($t3)===$email,'change revokes previous sessions');
ok(!$auth->login($email,$initial),'initial permanently disabled');
ok(!$auth->login($email,strtolower($pass)),'personal passwords case-sensitive');
ok(is_string($auth->login($email,$pass)),'personal login works');
$raw=file_get_contents($root.'/userdatei/auth-v11.php');ok(strpos($raw,$pass)===false&&strpos($raw,$t3)===false,'no plaintext password/session');
ok(substr($raw,0,strlen(NadinAuth::GUARD))===NadinAuth::GUARD,'PHP guard');
$r1=$auth->recovery($email);$r2=$auth->recovery($email);ok($r1['kind']==='reset'&&$r1['secret']!==$r2['secret'],'independent recovery codes');
ok($auth->session($t3)===$email,'request does not revoke session');
ok(is_string($auth->login($email,$pass)),'request does not change password');
ok($auth->recovery($email)===null,'recovery rate limit');
ok(strpos(file_get_contents($root.'/userdatei/auth-v11.php'),str_replace('-','',$r1['secret']))===false,'codes stored hashed');
ok(!$auth->reset($email,'0000-0000-0000','Another Password 456','Another Password 456'),'wrong code rejected');
$new='Another Password 456';ok($auth->reset($email,strtolower($r1['secret']),$new,$new),'first code survives subsequent request; case and separators accepted');
ok($auth->session($t3)===''&&!$auth->login($email,$pass),'reset revokes old login/session');
ok(!$auth->reset($email,$r1['secret'],$pass,$pass)&&!$auth->reset($email,$r2['secret'],$pass,$pass),'reset invalidates every code');
$t4=$auth->login($email,$new);ok(is_string($t4),'new password works');$auth->logout($t4);ok($auth->session($t4)==='','logout revokes copied token');
$d=db();$d['rates']=array();putdb($d);$r=$auth->recovery($email);$d=db();foreach($d['accounts'][$email]['resets'] as &$code)$code['until']=time()-1;unset($code);putdb($d);
ok(!$auth->reset($email,$r['secret'],$pass,$pass),'expired code denied');
$t=$auth->login($email,$new);$d=db();foreach($d['accounts'][$email]['sessions'] as &$session)$session['until']=time()-1;unset($session);putdb($d);ok($auth->session($t)==='','session server expiry');
$bound=new NadinAuth($root,'test-secret',3600,true,'192.0.2.5');$t=$bound->login($email,$new);$other=new NadinAuth($root,'test-secret',3600,true,'192.0.2.6');ok($bound->session($t)===$email&&$other->session($t)==='','optional IP binding');
$t=$auth->login($email,$new);$auth->saveMembers(str_replace('vsaxes=1','vsaxes=0',$lines));ok($auth->session($t)===''&&!$auth->active($email,'vsaxes'),'rights changes revoke existing session');
ok($auth->hasPersonal($email)&&is_string($auth->login($email,$new)),'user edits preserve personal password');
$auth->saveMembers(str_replace('member@example.invalid','renamed@example.invalid',$lines));ok(!$auth->hasPersonal($email)&&!$auth->login($email,$new),'removed account denied and credentials removed');
ok(is_string($auth->login('renamed@example.invalid',$auth->initial('renamed@example.invalid'))),'new email receives simple onboarding');
$auth->saveMembers($lines);$d=db();$d['rates']=array();putdb($d);
for($i=0;$i<12;$i++)$auth->login($email,'wrong');ok(!$auth->login($email,$initial),'login attempts bounded');
$d=db();foreach($d['rates'] as &$rate)$rate['until']=time()-1;unset($rate);putdb($d);ok(is_string($auth->login($email,$initial)),'automatic limit release');
// Independent PHP workers exercise the file lock, rather than serial calls.
$d=db();$d['rates']=array();putdb($d);
$workers=array();$workerCode='require '.var_export(dirname(__DIR__).'/nadinmodule/auth.inc.php',true).'; $a=new NadinAuth('.var_export($root,true).',"test-secret",3600,false,"192.0.2.20"); echo $a->login("member@example.invalid",'.var_export($initial,true).');';
for($i=0;$i<4;$i++) {$pipes=array();$proc=proc_open(array(PHP_BINARY,'-n','-r',$workerCode),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);fclose($pipes[0]);$workers[]=array($proc,$pipes);}
$tokens=array();foreach($workers as $w){$tokens[]=stream_get_contents($w[1][1]);$err=stream_get_contents($w[1][2]);fclose($w[1][1]);fclose($w[1][2]);ok(proc_close($w[0])===0,'concurrent worker completed: '.$err);}
ok(count(array_unique($tokens))===4,'parallel logins create distinct sessions');foreach($tokens as $t)ok($auth->session($t)===$email,'parallel session persisted');
$t=$auth->change($tokens[0],$initial,$pass,$pass);$r=$auth->recovery($email);
$workerCode='require '.var_export(dirname(__DIR__).'/nadinmodule/auth.inc.php',true).'; $a=new NadinAuth('.var_export($root,true).',"test-secret",3600,false,"192.0.2.30"); echo $a->reset("member@example.invalid",'.var_export($r['secret'],true).',"Concurrent new password","Concurrent new password") ? "YES" : "NO";';
$workers=array();for($i=0;$i<2;$i++){$pipes=array();$proc=proc_open(array(PHP_BINARY,'-n','-r',$workerCode),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);fclose($pipes[0]);$workers[]=array($proc,$pipes);}
$out=array();foreach($workers as $w){$out[]=stream_get_contents($w[1][1]);fclose($w[1][1]);fclose($w[1][2]);ok(proc_close($w[0])===0,'parallel reset completed');}
ok(count(array_filter($out,function($v){return $v==='YES';}))===1,'single-use recovery is atomic under concurrency');
$backup=file_get_contents($root.'/userdatei/auth-v11.php');file_put_contents($root.'/userdatei/auth-v11.php','corrupt');throws(function()use($auth,$email,$initial){$auth->login($email,$initial);},'corruption fails closed');file_put_contents($root.'/userdatei/auth-v11.php',$backup);
unlink($root.'/userdatei/auth-v11.php');throws(function()use($auth,$email,$initial){$auth->login($email,$initial);},'missing store fails closed after initialisation');file_put_contents($root.'/userdatei/auth-v11.php',$backup);
echo 'PASS: '.$checks." authentication regression checks\n";
} finally { foreach(glob($root.'/userdatei/*') as $p)unlink($p);rmdir($root.'/userdatei');rmdir($root); }

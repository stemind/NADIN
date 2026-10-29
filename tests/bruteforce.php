<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/nadinmodule/auth.inc.php';
$root=sys_get_temp_dir().'/nadin-v13-'.bin2hex(random_bytes(6));mkdir($root);mkdir($root.'/userdatei');
file_put_contents($root.'/userdatei/userdatei.txt',"Member, member@example.invalid, 31.12.2099, vgigs=1,\nOther, other@example.invalid, 31.12.2099, vgigs=1,\n");
$checks=0;$email='member@example.invalid';$ip='192.0.2.13';
function ok($v,$label){global $checks;if(!$v)throw new RuntimeException('FAIL: '.$label);$checks++;}
function client($ip='192.0.2.13'){global $root;return new NadinAuth($root,'fixture-only',3600,false,$ip);}
function readDb(){global $root;return json_decode(substr(file_get_contents($root.'/userdatei/auth-v11.php'),strlen(NadinAuth::GUARD)),true);}
function writeDb($d){global $root;file_put_contents($root.'/userdatei/auth-v11.php',NadinAuth::GUARD.json_encode($d));}
function keyFor($d,$scope,$value){return hash_hmac('sha256','login-v13-'.$scope.'|'.$value,$d['key']);}
function freshRates(){ $d=readDb();$d['rates']=array();$d['loginEvents']=array();writeDb($d); }
function endWait($email){$d=readDb();$d['rates'][keyFor($d,'account',$email)]['blocked']=time()-1;writeDb($d);}
try {
 $a=client();$initial=$a->initial($email);$session=$a->login($email,$initial);freshRates();
 for($i=1;$i<=5;$i++)ok(!$a->login($email,'wrong')&&$a->loginRetryAfter()===0,'five typo attempts: '.$i);
 ok(!$a->login($email,'wrong')&&$a->loginRetryAfter()===30,'sixth failure starts 30 seconds');
 $before=readDb();$other=client('192.0.2.99');
 ok(!$other->login(strtoupper($email),$initial)&&$other->loginRetryAfter()>0,'IP rotation and email case cannot bypass account lock');
 ok(readDb()===$before,'blocked requests do not extend lock, increase counts or flood history');
 ok($a->session($session)===$email,'existing session remains usable during attack');
 ok(is_string($a->login('other@example.invalid',$a->initial('other@example.invalid'))),'other band member behind same NAT can log in');
 foreach(array(120,600,3600,3600) as $seconds){endWait($email);ok(!$a->login($email,'wrong')&&$a->loginRetryAfter()===$seconds,'next lock stage '.$seconds);}
 endWait($email);ok(is_string($a->login($email,$initial))&&$a->loginRetryAfter()===0,'correct password after wait resets account');
 $d=readDb();ok(!isset($d['rates'][keyFor($d,'account',$email)]),'successful login clears account streak');
 ok($d['rates'][keyFor($d,'network',$ip)]['n']===10,'success does not erase network failures');
 freshRates();
 for($i=0;$i<6;$i++)$a->login('missing@example.invalid','wrong');
 ok($a->loginRetryAfter()===30,'unknown address gets identical lock behavior');
 $d=readDb();$d['rates'][keyFor($d,'account','missing@example.invalid')]['until']=time()-1;writeDb($d);
 ok(!$a->login('missing@example.invalid','wrong')&&$a->loginRetryAfter()===0,'24h idle observation period starts a fresh streak');
 freshRates();
 for($i=0;$i<99;$i++)$a->login('absent'.$i.'@example.invalid','wrong');
 ok($a->loginRetryAfter()===0,'network permits fewer than 100 evaluated failures');
 ok(!$a->login('absent99@example.invalid','wrong')&&$a->loginRetryAfter()===600,'100 failures in ten minutes lock network for ten minutes');
 ok(!$a->login($email,$initial)&&$a->loginRetryAfter()>0,'network throttle applies across account names');
 ok(is_string(client('192.0.2.44')->login($email,$initial)),'network attack does not lock the whole installation');
 $d=readDb();$d['rates'][keyFor($d,'network',$ip)]['until']=time()-1;writeDb($d);
 ok(is_string($a->login($email,$initial)),'network lock automatically expires');
 freshRates();
 for($i=0;$i<65;$i++)$a->login($email,$initial);
 ok(is_string($a->login($email,$initial)),'more than sixty correct logins do not exhaust shared NAT');
 $d=readDb();$d['rates'][keyFor($d,'network',$ip)]=array('n'=>99,'until'=>time()+600,'blocked'=>0,'attempts'=>array_fill(0,99,time()-601));writeDb($d);
 $a->login('window@example.invalid','wrong');$d=readDb();
 ok($d['rates'][keyFor($d,'network',$ip)]['n']===1,'rolling network window discards old failures');
 // Recovery/change remain available, while already-issued codes stay single-use.
 freshRates();$t=$a->login($email,$initial);$password='Personal test phrase 13';$t=$a->change($t,$initial,$password,$password);
 for($i=0;$i<6;$i++)$a->login($email,'wrong');
 $r=$a->recovery($email);ok($r&&$r['kind']==='reset','locked member can request a recovery code');
 ok($a->reset($email,$r['secret'],'Reset test phrase 13','Reset test phrase 13'),'valid code resets password while login locked');
 ok(is_string($a->login($email,'Reset test phrase 13')),'reset clears account lock');
 ok(!$a->reset($email,$r['secret'],$password,$password),'used recovery code still rejected');
 $t=$a->login($email,'Reset test phrase 13');for($i=0;$i<6;$i++)$a->login($email,'wrong');
 ok(is_string($a->change($t,'Reset test phrase 13',$password,$password)),'authenticated password change remains possible');
 ok(is_string($a->login($email,$password)),'password change clears account lock');
 // Upgrade preserves an existing exhausted V11/V12 limit until its original end.
 freshRates();$d=readDb();$k=hash_hmac('sha256','login-account|'.$email,$d['key']);$d['rates'][$k]=array('n'=>12,'until'=>time()+80);writeDb($d);
 ok(!$a->login($email,$password)&&$a->loginRetryAfter()>0,'upgrade honours previous lock');
 $d=readDb();$d['rates'][$k]['until']=time()-1;writeDb($d);ok(is_string($a->login($email,$password)),'old limit expires normally');
 // Atomic cross-process failure accounting; no real waiting or test backdoor in production.
 freshRates();$workers=array();
 $code='require '.var_export(dirname(__DIR__).'/nadinmodule/auth.inc.php',true).';$a=new NadinAuth('.var_export($root,true).',"fixture-only",3600,false,"192.0.2.13");$a->login("member@example.invalid","wrong");echo $a->loginRetryAfter();';
 for($i=0;$i<10;$i++){$pipes=array();$p=proc_open(array(PHP_BINARY,'-n','-r',$code),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);fclose($pipes[0]);$workers[]=array($p,$pipes);}
 foreach($workers as $w){stream_get_contents($w[1][1]);$err=stream_get_contents($w[1][2]);fclose($w[1][1]);fclose($w[1][2]);ok(proc_close($w[0])===0,'concurrent failure worker: '.$err);}
 $d=readDb();ok($d['rates'][keyFor($d,'account',$email)]['n']===6,'parallel failures stop exactly at first lock');
 ok(count($d['loginEvents'])===6,'parallel blocked attempts do not flood diagnostic history');
 // A bounded store must not evict an active limit to admit random new identities.
 freshRates();$d=readDb();for($i=0;$i<NadinAuth::MAX_RATES;$i++)$d['rates'][hash('sha256','fixture'.$i)]=array('n'=>1,'until'=>time()+600);writeDb($d);
 ok(!$a->login('overflow@example.invalid','wrong')&&$a->loginRetryAfter()===60,'full attempt table fails closed');
 ok(count(readDb()['rates'])===NadinAuth::MAX_RATES,'attempt table remains bounded');
 ok($a->recovery('overflow@example.invalid')===null&&count(readDb()['rates'])===NadinAuth::MAX_RATES,'recovery cannot grow a full attempt table');
 freshRates();
 for($i=0;$i<210;$i++)client('192.0.2.'.($i%200+1))->login('history'.$i.'@example.invalid','NEVER-LOG-THIS-PASSWORD');
 $events=readDb()['loginEvents'];$encoded=json_encode($events);
 ok(count($events)===200,'diagnostic ring is bounded');
 ok(strpos($encoded,'NEVER-LOG')===false&&strpos($encoded,'@example.invalid')===false&&strpos($encoded,'192.0.2.')===false,'diagnostics contain no password, email or raw IP');
 ok(NadinAuth::email(array())===''&&NadinAuth::email(str_repeat('x',255))==='','malformed/oversized identities bounded');
 $d=readDb();$d['rates']['corrupt']=array('n'=>'wrong','until'=>time()+100);writeDb($d);$threw=false;
 try{$a->login($email,$password);}catch(RuntimeException $e){$threw=true;}ok($threw,'corrupt attempt data fails closed');
 echo 'PASS: '.$checks." V13 brute-force checks\n";
} finally {foreach(glob($root.'/userdatei/*') as $p)unlink($p);rmdir($root.'/userdatei');rmdir($root);}

<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/nadinmodule/auth.inc.php';
require_once dirname(__DIR__).'/nadinmodule/admin.inc.php';
$root=sys_get_temp_dir().'/nadin-legacy-users-'.bin2hex(random_bytes(8));
mkdir($root);mkdir($root.'/userdatei');$checks=0;
function legacy_ok($test,$label){global $checks;if(!$test)throw new RuntimeException('FAIL: '.$label);$checks++;}
function legacy_clean($path){foreach(new DirectoryIterator($path) as $f){if($f->isDot())continue;$f->isDir()?legacy_clean($f->getPathname()):unlink($f->getPathname());}rmdir($path);}
try {
 $lines="\xEF\xBB\xBFAdmin, ADMIN@example.invalid, 1.1.2099, vgigs=1, vlibrary=1, musers=1,\r\n".
 "M\xFCller, old@example.invalid, 2. 3. 2099, vgigs=1, vsaxes = 1, customright=1,\r\n".
 "ISO User, iso@example.invalid, 2099-12-31, vgigs=1,\r\n".
 "Needs date, date@example.invalid, 31.2.2099, vgigs=1,\r\n\r\n".
 "Here, enter your users' data and your own data.\r\n";
 file_put_contents($root.'/userdatei/userdatei.txt',$lines);
 $auth=new NadinAuth($root,'fixture');$admin=new NadinAdmin($root,'fixture-separator');$people=$admin->people();
 legacy_ok(count($people['rows'])===4&&$people['unparsed']===0,'historical accounts imported automatically; instructions ignored');
 $rows=array_column($people['rows'],null,'email');
 legacy_ok($rows['admin@example.invalid']['expiry']==='2099-01-01','unpadded Swiss date converted for date input');
 legacy_ok($rows['old@example.invalid']['expiry']==='2099-03-02'&&$rows['old@example.invalid']['name']==='Müller','spaced date and Latin-1 name converted automatically');
 legacy_ok(in_array('customright',$rows['old@example.invalid']['rights'],true)&&in_array('vsaxes',$rows['old@example.invalid']['rights'],true),'historical and extension permissions retained');
 legacy_ok($rows['iso@example.invalid']['expiry']==='2099-12-31'&&$auth->active('iso@example.invalid'),'ISO date imported consistently by editor and authentication');
 legacy_ok($rows['date@example.invalid']['expiry']===''&&!$auth->active('date@example.invalid'),'invalid date stays visible and inactive instead of inventing access');
 legacy_ok($auth->active('admin@example.invalid','musers')&&$auth->active('old@example.invalid','vsaxes'),'old accounts keep access and permissions');
 $standard="Admin, ADMIN@example.invalid, 1.1.2099, vgigs=1, vlibrary=1, musers=1,";
 legacy_ok(nadinMemberLine($standard)['stamp']===hash('sha256',implode(',',array_map('trim',explode(',',strtolower($standard))))),'existing account stamp algorithm preserved');
 $token=$auth->login('admin@example.invalid',$auth->initial('admin@example.invalid'));
 legacy_ok(is_string($token)&&$auth->session($token)==='admin@example.invalid','historical account can log in');
 legacy_ok(file_get_contents($root.'/userdatei/userdatei.txt')===$lines,'loading the editor preserves source bytes and sessions');
 $member=$rows['old@example.invalid'];$data=array('action'=>'save','original'=>$member['email'],'revision'=>$member['revision'],'name'=>$member['name'],'email'=>$member['email'],'expiry'=>$member['expiry'],'rights'=>array('vgigs','vsaxes'));
 $admin->savePerson($data,$auth,'admin@example.invalid');$saved=file_get_contents($root.'/userdatei/userdatei.txt');
 legacy_ok(strpos($saved,'Müller, old@example.invalid, 02.03.2099,')!==false,'saving normalises an imported row into the current format');
 legacy_ok(strpos($saved,'customright=1')!==false&&strpos($saved,"Here, enter your users' data")!==false,'saving preserves extension permissions and instructions');
 legacy_ok($auth->session($token)==='admin@example.invalid','editing an imported member does not revoke another account session');
 legacy_ok(count(glob($root.'/userdatei/revisions/*.php'))===1,'conversion keeps an exact safety copy');
 try{$admin->savePerson($data,$auth,'admin@example.invalid');legacy_ok(false,'stale save rejected');}catch(DomainException $e){legacy_ok(true,'stale save rejected');}
 $quoted='"Müller, ""Anna""", anna@example.invalid, 31.12.2099, vgigs=1, customright=1,';
 file_put_contents($root.'/userdatei/userdatei.txt',$saved."\n".$quoted."\n");
 $imported=array_column($admin->people()['rows'],null,'email')['anna@example.invalid'];
 legacy_ok($imported['name']==='Müller, "Anna"','quoted comma and double quotes appear in the person editor');
 $initial=$auth->initial($imported['email']);$session=$auth->login($imported['email'],$initial);
 legacy_ok(is_string($session),'quoted-name account can log in');
 $password='Personal quoted-name password 42';$personal=$auth->change($session,$initial,$password,$password);
 legacy_ok(is_string($personal),'quoted-name account can choose a personal password');
 $edit=array('action'=>'save','original'=>$imported['email'],'revision'=>$imported['revision'],'name'=>'Müller, "Anna Maria"','email'=>$imported['email'],'expiry'=>$imported['expiry'],'rights'=>array('vgigs'));
 $admin->savePerson($edit,$auth,'admin@example.invalid');
 $after=array_column($admin->people()['rows'],null,'email')['anna@example.invalid'];
 legacy_ok($after['name']===$edit['name']&&in_array('customright',$after['rights'],true),'editing quoted name preserves its complete spelling and extension rights');
 legacy_ok(is_string($auth->login($edit['email'],$password))&&!$auth->login($edit['email'],$initial),'saving imported account retains its personal password');
 file_put_contents($root.'/userdatei/userdatei.txt',file_get_contents($root.'/userdatei/userdatei.txt')."\nUnknown, one@example.invalid, two@example.invalid, 31.12.2099, vgigs=1,\n");
 $review=$admin->people();legacy_ok($review['unparsed']===1&&count($review['unparsed_rows'])===1,'ambiguous account-like row remains visible for review');
 echo 'PASS: '.$checks." legacy user conversion checks\n";
} finally {legacy_clean($root);}

<?php
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once dirname(__DIR__).'/nadinmodule/auth.inc.php';
$checks=0;
function member_ok($value,$label){global $checks;if(!$value)throw new RuntimeException('FAIL: '.$label);$checks++;}
$cases=array(
 array('Plain Name, plain@example.invalid, 31.12.2099, vgigs=1,','Plain Name','plain@example.invalid'),
 array('display@example.invalid, actual@example.invalid, 31.12.2099, vgigs=1,','display@example.invalid','actual@example.invalid'),
 array('"Müller, Anna", anna@example.invalid, 31.12.2099, vgigs=1, customright=1,','Müller, Anna','anna@example.invalid'),
 array('Müller, Anna, anna2@example.invalid, 31.12.2099, vgigs=1,','Müller, Anna','anna2@example.invalid'),
 array('"Doe, ""Jane""", jane@example.invalid, 1. 2. 2099, vgigs = 1,','Doe, "Jane"','jane@example.invalid'),
 array("O'Connor, oconnor@example.invalid, 2099-12-31, vgigs=1,","O'Connor",'oconnor@example.invalid'),
 array('Back\slash, back@example.invalid, 31.12.2099, vgigs=1,','Back\slash','back@example.invalid'),
 array("\xEF\xBB\xBFAdmin, ADMIN@example.invalid, 31.12.2099, musers=1,",'Admin','admin@example.invalid'),
 array("M\xFCller, Anna, latin@example.invalid, 31.12.2099, vgigs=1,","M\xFCller, Anna",'latin@example.invalid')
);
$root=sys_get_temp_dir().'/nadin-member-format-'.bin2hex(random_bytes(6));mkdir($root.'/userdatei',0700,true);
try{
 file_put_contents($root.'/userdatei/userdatei.txt',implode("\n",array_column($cases,0))."\n");$auth=new NadinAuth($root,'fixture');
 foreach($cases as $case){
  $m=nadinMemberLine($case[0]);member_ok($m&&$m['email']===$case[2]&&$m['name']===$case[1],'historical name and identity: '.$case[2]);
  member_ok($auth->active($case[2]),'historical account remains active: '.$case[2]);
  $encoded=nadinMemberNameField($m['name']).', '.$m['email'].', '.$m['date']->format('d.m.Y').', vgigs=1,';
  member_ok(nadinMemberLine($encoded)['name']===$m['name'],'canonical CSV round trip: '.$case[2]);
 }
 $standard=$cases[0][0];member_ok(nadinMemberLine($standard)['stamp']===hash('sha256',implode(',',array_map('trim',explode(',',strtolower($standard))))),'existing session stamps retain their algorithm');
 foreach(array('',"\n",'# comment, ignored',"Here, enter your users' data") as $comment)member_ok(nadinMemberLine($comment)===null&&!nadinMemberNeedsReview($comment),'comments are not mistaken for user entries');
 foreach(array('A, first@example.invalid, second@example.invalid, 31.12.2099, vgigs=1,','Broken, wrong@, 31.12.2099, vgigs=1,') as $invalid)member_ok(nadinMemberLine($invalid)===null&&nadinMemberNeedsReview($invalid),'ambiguous or invalid accounts require review without granting access');
 member_ok(isset($auth->members()['anna@example.invalid']['rights']['customright']),'extension permission is retained after quoted name');
 echo 'PASS: '.$checks." member-format compatibility checks\n";
}finally{unlink($root.'/userdatei/userdatei.txt');rmdir($root.'/userdatei');rmdir($root);}

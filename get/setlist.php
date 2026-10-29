<?php
require_once dirname(__DIR__).'/config.inc.php';require_once dirname(__DIR__).'/nadinmodule/functions.inc.php';
auth('vgigs');$name=$_GET['c'] ?? '';$root=dirname(__DIR__);
if(!is_string($name)||$name===''||$name[0]==='.'||basename($name)!==$name||preg_match('/[\\\\\x00-\x1f]/',$name)||strtolower(substr($name,-4))!=='.pdf'){http_response_code(404);exit('Attachment not found.');}
$gig=substr($name,0,-4).'.txt';
if(!is_file($root.'/gigreps/'.$gig)){if(!is_file($root.'/gigrepsarchiv/'.$gig)){http_response_code(404);exit('Gig not found.');}auth('varchiv');}
$file=$root.'/abo/zip/'.$name;if(!is_file($file)||is_link($file)){http_response_code(404);exit('Attachment not found.');}
header('Content-Type: application/pdf');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');header('Content-Disposition: inline; filename="NADIN.pdf"; filename*=UTF-8\'\''.rawurlencode($name));header('Content-Length: '.filesize($file));readfile($file);

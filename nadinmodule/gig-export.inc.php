<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
require_once __DIR__.'/ui.inc.php';
auth('vgigs');
$exportMode=$_GET['m'] ?? 'gigreps';
if (!is_string($exportMode) || !in_array($exportMode,array('gigreps','gigrepsarchiv'),true)) { http_response_code(404);exit('Gig list not found.'); }
if ($exportMode==='gigrepsarchiv') auth('varchiv');
$exportGigs=nadinRepFiles(dirname(__DIR__).'/'.$exportMode,$exportMode==='gigrepsarchiv');
$exportGig=$_GET['c'] ?? ($exportGigs[0] ?? '');
if (!is_string($exportGig) || !in_array($exportGig,$exportGigs,true)) { http_response_code(404);exit('Gig not found.'); }
$_GET['m']=$exportMode;$_GET['c']=$exportGig;
list($infos,$tunes)=nadinReadRep(dirname(__DIR__).'/'.$exportMode.'/'.$exportGig,$delimiter2);
header('Cache-Control: private, no-store');

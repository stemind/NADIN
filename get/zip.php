<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once dirname(__DIR__).'/nadinmodule/functions.inc.php';
require_once dirname(__DIR__).'/nadinmodule/zip.inc.php';
auth('vgigs');
try {
 $c=$_GET['c'] ?? '';
 if(!is_string($c)||!preg_match('~^zip/(alllistento|allscore|allparts|allsaxes|allbones|alltrumpets|allrhythm|allother)/([^/\\\\]+)_\1\.zip$~D',$c,$m))throw new InvalidArgumentException('Invalid ZIP request.');
 $types=nadin_zip_types();auth($types[$m[1]][1]);$gig=$m[2].'.txt';$root=dirname(__DIR__);
 if(($m[1]==='alllistento'?$offerrcmdmp3saszip:$offersheetsaszip)!=='yes'){http_response_code(403);exit('ZIP downloads are disabled.');}
 $mode=$_GET['m'] ?? (is_file($root.'/gigreps/'.$gig)?'gigreps':'gigrepsarchiv');
 if(!is_string($mode)||!in_array($mode,array('gigreps','gigrepsarchiv'),true))throw new InvalidArgumentException('Invalid gig list.');
 if($mode==='gigrepsarchiv')auth('varchiv');
 $download=nadin_zip_prepare($root,$gig,$m[1],$mode,true);
 header('Content-Type: application/zip');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');
 header('Content-Disposition: attachment; filename="NADIN.zip"; filename*=UTF-8\'\''.rawurlencode($download['name']));header('Content-Length: '.$download['size']);
 try{fpassthru($download['stream']);}finally{fclose($download['stream']);}
}catch(InvalidArgumentException $e){http_response_code(404);echo htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8');}
catch(NadinZipChanged $e){http_response_code(503);header('Retry-After: 3');echo 'Files are being updated or ZIP creation could not finish. Please retry in a moment.';}
catch(Throwable $e){http_response_code(500);echo 'The download could not be prepared. Please try again.';error_log('NADIN ZIP: '.$e->getMessage());}

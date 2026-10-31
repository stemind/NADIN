<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once dirname(__DIR__).'/nadinmodule/functions.inc.php';
require_once dirname(__DIR__).'/nadinmodule/zip.inc.php';
auth('vgigs');
try {
 $c=$_GET['c'] ?? '';
 if(!is_string($c)||!preg_match('~^zip/(alllistento|allscore|allparts|allsaxes|allbones|alltrumpets|allrhythm|allother)/([^/\\\\]+)_\1\.zip$~D',$c,$m))throw new InvalidArgumentException('Invalid ZIP request.');
 $types=nadin_zip_types();auth($types[$m[1]][1]);$gig=$m[2].'.txt';$root=dirname(__DIR__);$mode='gigreps';
 if(!is_file($root.'/'.$mode.'/'.$gig)){$mode='gigrepsarchiv';auth('varchiv');}
 $file=nadin_zip_bundle($root,$gig,$m[1],$mode);
 header('Content-Type: application/zip');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');
 header('Content-Disposition: attachment; filename="NADIN.zip"; filename*=UTF-8\'\''.rawurlencode(nadinText(basename($file))));header('Content-Length: '.filesize($file));readfile($file);
}catch(InvalidArgumentException $e){http_response_code(404);echo htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8');}
catch(Throwable $e){http_response_code(500);echo 'The download could not be prepared. Please try again.';error_log('NADIN ZIP: '.$e->getMessage());}

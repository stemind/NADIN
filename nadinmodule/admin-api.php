<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
require_once __DIR__.'/admin.inc.php';
header('Content-Type: application/json; charset=UTF-8'); header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff');
function nadin_admin_reply($data,$status=200) { http_response_code($status); echo json_encode($data,JSON_HEX_TAG|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
if (!$uc) nadin_admin_reply(array('error'=>'Your session has ended. Log in in another tab, then retry; keep this editor open.'),401);
$isPost=($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST';
$d=$isPost ? $_POST : $_GET;
if ($isPost && strpos($_SERVER['CONTENT_TYPE'] ?? '','application/json')===0) { $raw=file_get_contents('php://input',false,null,0,1100001); $d=json_decode($raw,true); }
if (!is_array($d)) nadin_admin_reply(array('error'=>'Invalid request.'),400);
$area=$d['area'] ?? '';
if (!in_array($area,array('gigs','people'),true) || !role($area==='people' ? 'musers' : 'mgigs')) nadin_admin_reply(array('error'=>'Access denied.'),403);
if ($isPost && !nadin_auth()->csrf('admin-v12',nadin_session_token(),$d['csrf'] ?? '')) nadin_admin_reply(array('error'=>'This form has expired. Keep your editor open, log in in another tab, then retry.'),403);
$store=new NadinAdmin(dirname(__DIR__),$delimiter2);
try {
    $action=$d['action'] ?? 'list';
    if (!$isPost && $action==='token') nadin_admin_reply(array('csrf'=>nadin_auth()->csrf('admin-v12',nadin_session_token())));
    if ($area==='people') {
        if (!$isPost) nadin_admin_reply($store->people());
        if (!in_array($action,array('save','delete'),true)) throw new InvalidArgumentException('Unknown action.');
        $email=$store->savePerson($d,nadin_auth(),$uc); nadin_admin_reply(array('email'=>$email,'people'=>$store->people()));
    }
    if (!$isPost) {
        if ($action==='gig') nadin_admin_reply(array('gig'=>$store->gig($d['mode'] ?? 'current',$d['id'] ?? '')));
        nadin_admin_reply(array('gigs'=>$store->gigs($d['mode'] ?? 'current'),'catalog'=>$store->catalog()));
    }
    if ($action==='save') $gig=$store->saveGig($d);
    elseif ($action==='attachment') $gig=$store->uploadAttachment($d,$_FILES['pdf'] ?? null);
    else $gig=$store->gigAction($d);
    nadin_admin_reply(array('gig'=>$gig,'gigs'=>$store->gigs($gig['mode'])));
} catch (DomainException $e) { nadin_admin_reply(array('error'=>$e->getMessage()),409); }
catch (InvalidArgumentException $e) { nadin_admin_reply(array('error'=>$e->getMessage()),400); }
catch (Throwable $e) { error_log('NADIN admin: '.$e->getMessage()); nadin_admin_reply(array('error'=>'Could not complete this action. Your work is still in the editor. Check storage permissions or try again.'),500); }

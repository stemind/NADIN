<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='GET') {http_response_code(405);header('Allow: GET');echo json_encode(array('error'=>'Use GET.'));exit;}
if (!$uc) {http_response_code(401);echo json_encode(array('error'=>'Your session has ended. Keep this editor open, sign in in another tab, then retry.'));exit;}
if (!role('uppdf')&&!role('upaudio')&&!role('mgigs')) {http_response_code(403);echo json_encode(array('error'=>'You do not have permission to edit these files.'));exit;}
echo json_encode(array('csrf'=>nadin_auth()->csrf('media-v12',nadin_session_token())));

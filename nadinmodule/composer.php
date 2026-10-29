<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
auth('mgigs');
header('Location: ../index.php?'.http_build_query(array('m'=>'managegigs','c'=>$_GET['gig'] ?? '')),true,303);

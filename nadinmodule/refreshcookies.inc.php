<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
if ($uc !== '') nadin_set_session(nadin_session_token());

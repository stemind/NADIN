<?php
require_once dirname(__DIR__).'/config.inc.php';
require_once __DIR__.'/functions.inc.php';
auth('mgigs');
http_response_code(410);
header('Content-Type: text/html; charset=UTF-8');
echo 'This editor was replaced in V11. <a href="../index.php?m=managegigs">Open the current editor</a>.';

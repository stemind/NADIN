<?php
require_once __DIR__.'/legacy-write.inc.php';
$tune=mkascii($_POST['tune'] ?? '');
if(strlen($tune)<3)nadin_legacy_error(400,'Use a tune name with at least 3 characters.');
$folder=dirname(__DIR__).'/library/'.$tune;
if(file_exists($folder)||is_link($folder))nadin_legacy_error(409,'A tune with this name already exists. Choose a different name.');
if(!mkdir($folder))nadin_legacy_error(500,'Could not create this tune. Check library storage permissions.');
setcookie('scrlt',mkid2($tune),time()+600,'/');
nadin_media_reply(true,'Tune created: '.nadinText(str_replace('_',' ',$tune)).'. Add its scores or recordings next.',array('next_url'=>'uploadpdf.php?tune='.rawurlencode($tune),'next_label'=>'Add scores and files'));

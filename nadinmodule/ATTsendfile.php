<?php
require __DIR__.'/refreshcookies2.inc.php';require_once __DIR__.'/popup-ui.inc.php';require_once __DIR__.'/media-form.inc.php';auth('mgigs');nadinPopupStart('Upload pdf attachment');
$name=preg_replace('/\.txt$/','',$_GET['gig'] ?? '');nadin_media_form('attachment',$name);require __DIR__.'/legacy-ui.inc.php';
?>
</main></body></html>

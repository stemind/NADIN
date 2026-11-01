<?php
require __DIR__.'/refreshcookies2.inc.php';require_once __DIR__.'/popup-ui.inc.php';require_once __DIR__.'/media-form.inc.php';auth('upaudio');nadinPopupStart('Upload recordings');
$name=($_GET['name'] ?? '');nadin_media_form('audio',$name);require __DIR__.'/legacy-ui.inc.php';
?>
</main></body></html>

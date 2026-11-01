<?php
require_once __DIR__.'/ui.inc.php';
function nadin_media_async() {
    return ($_POST['_nadin_async'] ?? '')==='1' || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')!==false;
}
function nadin_media_reply($ok,$message,$extra=array(),$status=200) {
    http_response_code($status);header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
    if (nadin_media_async()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array_merge(array('ok'=>$ok,'message'=>$message),$extra),JSON_HEX_TAG|JSON_INVALID_UTF8_SUBSTITUTE);exit;
    }
    require_once __DIR__.'/popup-ui.inc.php';
    nadinPopupStart($ok?'Action completed':'Could not complete this action');
    echo '<p role="'.($ok?'status':'alert').'">'.nadinHtml($message).'</p>';
    if (!empty($extra['next_url'])) echo '<p><a class="nadin-primary nadin-button-link" href="'.nadinHtml($extra['next_url']).'">'.nadinHtml($extra['next_label'] ?? 'Continue editing').'</a></p>';
    echo '<p><button type="button" onclick="history.back()">Back to the editor</button> <a href="../?m=library">Score &amp; Media Library</a></p></main></body></html>';exit;
}
function nadin_ini_bytes($value) {
    $value=trim((string)$value);if($value==='')return 0;
    $bytes=(float)$value;$unit=strtolower(substr($value,-1));
    if($unit==='g')$bytes*=1024*1024*1024;elseif($unit==='m')$bytes*=1024*1024;elseif($unit==='k')$bytes*=1024;
    return (int)$bytes;
}

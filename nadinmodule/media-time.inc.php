<?php
/** Preserve the duration helper for audio managers using authenticated POST, never a GET write. */
function nadin_audio_time_form($tune,$file) {
    if (!role('upaudio')) return '';
    $id='audio-time-'.bin2hex(random_bytes(6));$url=nadin_cookie_path().'nadinmodule/settime.php';
    $html='<iframe name="'.$id.'" title="Audio duration" style="display:none"></iframe><form id="'.$id.'" method="post" target="'.$id.'" action="'.htmlXspecialchars($url).'" style="display:none">';
    foreach (array('p'=>$tune,'f'=>$file,'csrf'=>nadin_auth()->csrf('media-v12',nadin_session_token())) as $name=>$value) $html.='<input type="hidden" name="'.$name.'" value="'.htmlXspecialchars(in_array($name,array('p','f'),true)?rawurlencode($value):$value).'">';
    $html.=nadinPathMarker('p').nadinPathMarker('f');
    return $html.'</form><script>document.getElementById("'.$id.'").submit();</script>';
}

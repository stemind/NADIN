<?php
require_once __DIR__.'/media-response.inc.php';
function nadin_media_form($kind,$name) {
    $attachment=$kind==='attachment';$audio=$kind==='audio';
    $action=$attachment?'ATTsendfile2.php?gig='.rawurlencode($name):($audio?'AUDsendfile2.php':'PDFsendfile2.php');
    $limit=nadin_ini_bytes(ini_get('upload_max_filesize'));$postLimit=nadin_ini_bytes(ini_get('post_max_size'));
    $count=$attachment||$audio?1:max(1,(int)ini_get('max_file_uploads')-1);
    $accept=$attachment?'application/pdf,.pdf':($audio?'audio/mpeg,.mp3':'');
    if(!$attachment&&!$audio){require dirname(__DIR__).'/configallowedfileextensions.php';$accept=implode(',',array_map(function($ext){return '.'.$ext;},$okext));}
    $label=$attachment?'PDF attachment':($audio?'MP3 recording':'Scores and other files');
    echo '<form class="nadin-upload-form" data-nadin-async="upload" data-max-file="'.$limit.'" data-max-total="'.$postLimit.'" data-max-files="'.$count.'" enctype="multipart/form-data" method="post" action="'.nadinHtml($action).'">';
    echo '<input type="hidden" name="csrf" value="'.nadinHtml(nadin_auth()->csrf('media-v12',nadin_session_token())).'">';
    if(!$attachment)echo nadinPathField('name',$name);
    echo '<label for="media-upload">'.$label.'</label><input id="media-upload" type="file" name="userfile'.($count>1?'[]':'').'" '.($count>1?'multiple ':'').'accept="'.nadinHtml($accept).'" aria-describedby="media-upload-hint" required>';
    echo '<p id="media-upload-hint" class="nadin-hint">'.($attachment?'One PDF.':($audio?'One MP3.':'Choose up to '.$count.' files.')).' Up to '.nadinHtml(ini_get('upload_max_filesize')).' per file; '.nadinHtml(ini_get('post_max_size')).' per upload. Choosing a file does not upload it.</p>';
    echo '<div class="nadin-upload-selection" data-upload-selection role="status"></div><div class="nadin-toolbar"><button class="nadin-primary" type="submit">'.($attachment?'Upload / replace PDF':'Upload files').'</button><span class="nadin-hint">Existing names are replaced; previous file versions are retained.</span></div></form>';
}

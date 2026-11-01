<?php
require_once __DIR__.'/media-response.inc.php';
require_once __DIR__.'/audio-convert.inc.php';
function nadin_media_form($kind,$name) {
    $attachment=$kind==='attachment';$audio=$kind==='audio';
    $action=$attachment?'ATTsendfile2.php?gig='.rawurlencode($name):($audio?'AUDsendfile2.php':'PDFsendfile2.php');
    $limit=nadin_ini_bytes(ini_get('upload_max_filesize'));$postLimit=nadin_ini_bytes(ini_get('post_max_size'));
    $count=$attachment||$audio?1:max(1,(int)ini_get('max_file_uploads')-1);
    $conversion=$audio?nadin_audio_capability():array('available'=>false);
    $accept=$attachment?'application/pdf,.pdf':($audio?'audio/mpeg,.mp3':'');
    if($audio&&$conversion['available'])$accept.=',.wav,.aif,.aiff,.m4a,.aac,.flac,.ogg,.oga,.opus,.wma';
    if(!$attachment&&!$audio){require dirname(__DIR__).'/configallowedfileextensions.php';$accept=implode(',',array_map(function($ext){return '.'.$ext;},$okext));}
    $label=$attachment?'PDF attachment':($audio?'Audio recording':'Scores and other files');
    echo '<form class="nadin-upload-form" data-nadin-async="upload" '.($audio&&$conversion['available']?'data-audio-conversion="yes" ':'').'data-max-file="'.$limit.'" data-max-total="'.$postLimit.'" data-max-files="'.$count.'" enctype="multipart/form-data" method="post" action="'.nadinHtml($action).'">';
    echo '<input type="hidden" name="csrf" value="'.nadinHtml(nadin_auth()->csrf('media-v12',nadin_session_token())).'">';
    if(!$attachment)echo nadinPathField('name',$name);
    echo '<label for="media-upload">'.$label.'</label><input id="media-upload" type="file" name="userfile'.($count>1?'[]':'').'" '.($count>1?'multiple ':'').'accept="'.nadinHtml($accept).'" aria-describedby="media-upload-hint" required>';
    echo '<p id="media-upload-hint" class="nadin-hint">'.($attachment?'One PDF.':($audio?'One audio file.':'Choose up to '.$count.' files.')).' Up to '.nadinHtml(ini_get('upload_max_filesize')).' per file; '.nadinHtml(ini_get('post_max_size')).' per upload. Choosing a file does not upload it.</p>';
    $conversionLimit=$conversion['available']?min($conversion['bytes'],$limit>0?$limit:PHP_INT_MAX,$postLimit>0?$postLimit:PHP_INT_MAX):0;
    if($audio)echo '<p class="nadin-hint">'.($conversion['available']?'MP3 files are kept unchanged. WAV, AIFF, M4A/AAC, FLAC, Ogg/Opus and WMA are converted to high-quality MP3. Other formats: up to '.nadinHtml((string)round($conversionLimit/1048576,2)).' MB and '.nadinHtml((string)round($conversion['duration']/60,2)).' minutes.':'Automatic audio conversion is unavailable on this server. Upload an MP3 recording.').'</p>';
    echo '<div class="nadin-upload-selection" data-upload-selection role="status"></div><div class="nadin-toolbar"><button class="nadin-primary" type="submit">'.($attachment?'Upload / replace PDF':'Upload files').'</button><span class="nadin-hint">Existing names are replaced; previous file versions are retained.</span></div></form>';
}

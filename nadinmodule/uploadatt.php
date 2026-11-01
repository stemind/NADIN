<?php
require __DIR__.'/refreshcookies2.inc.php';require_once __DIR__.'/popup-ui.inc.php';require_once __DIR__.'/media-form.inc.php';auth('mgigs');
$name=preg_replace('/\.txt$/','',$_GET['gig'] ?? '');nadinPopupStart($name.' — PDF attachment');
?>
<section class="nadin-media-section" aria-labelledby="media-files-heading"><h2 id="media-files-heading">PDF attachment</h2>
<iframe data-nadin-file-list data-nadin-auto-size title="Existing pdf attachment" src="ATTfiles.php?gig=<?php echo rawurlencode($name); ?>" name="files" width="100%" height="180"></iframe></section>
<section class="nadin-media-section" aria-labelledby="media-upload-heading"><h2 id="media-upload-heading">Upload / replace attachment</h2>
<?php nadin_media_form('attachment',$name); ?></section>
<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</main></body></html>

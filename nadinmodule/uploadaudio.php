<?php
require __DIR__.'/refreshcookies2.inc.php';require_once __DIR__.'/popup-ui.inc.php';require_once __DIR__.'/media-form.inc.php';auth('upaudio');
setcookie('upos','uploadaudio',time()+3600*24*365*10,'/');$name=$_GET['tune'] ?? '';nadinPopupStart(str_replace('_',' ',$name));require __DIR__.'/uploadnavi.inc.php';
?>
<section class="nadin-media-section" aria-labelledby="media-files-heading"><h2 id="media-files-heading">Recordings</h2>
<iframe data-nadin-file-list data-nadin-auto-size title="Existing recordings" src="AUDfiles.php?name=<?php echo rawurlencode($name); ?>" name="files" width="100%" height="180"></iframe></section>
<section class="nadin-media-section" aria-labelledby="media-upload-heading"><h2 id="media-upload-heading">Upload files</h2>
<?php nadin_media_form('audio',$name); ?></section>
<details class="nadin-task-help"><summary>Recording names and recommendations</summary><p>Mark exactly one recommended recording per tune with <strong><?php echo nadinHtml($recommended); ?></strong> in its filename. It becomes the direct listening link beside the tune.</p><p>For a band recording, use <code><?php echo nadinHtml($bb); ?>_YYYY-MM-DD_Gig_Tune.mp3</code>. Other recordings can use a descriptive name. Duration is measured automatically; it does not need to be typed into the filename.</p></details>
<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</main></body></html>

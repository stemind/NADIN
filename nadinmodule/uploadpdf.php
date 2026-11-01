<?php
require __DIR__.'/refreshcookies2.inc.php';require_once __DIR__.'/popup-ui.inc.php';require_once __DIR__.'/media-form.inc.php';auth('uppdf');
setcookie('upos','uploadpdf',time()+3600*24*365*10,'/');$name=$_GET['tune'] ?? '';nadinPopupStart(str_replace('_',' ',$name));require __DIR__.'/uploadnavi.inc.php';
?>
<section class="nadin-media-section" aria-labelledby="media-files-heading"><h2 id="media-files-heading">Scores and files</h2>
<iframe data-nadin-file-list data-nadin-auto-size title="Existing scores and files" src="PDFfiles.php?name=<?php echo rawurlencode($name); ?>" name="files" width="100%" height="180"></iframe></section>
<section class="nadin-media-section" aria-labelledby="media-upload-heading"><h2 id="media-upload-heading">Upload files</h2>
<?php nadin_media_form('files',$name); ?></section>
<details class="nadin-task-help"><summary>Instrument markers and allowed file types</summary><p>Include the matching marker in the filename so musicians receive the correct section. Use Parts for material that has not been split by section.</p><table class="nadin-naming-table"><thead><tr><th>Music</th><th>Filename example</th></tr></thead><tbody><?php foreach(array('Parts'=>$parts,'Score'=>$score,'Saxophones'=>$saxes,'Trombones'=>$bones,'Trumpets'=>$trumpets,'Rhythm'=>$rhythm,'Other'=>$other) as $label=>$marker)echo '<tr><th>'.nadinHtml($label).'</th><td><code>'.nadinHtml($name.'_'.$marker.'.pdf').'</code></td></tr>'; ?></tbody></table><p>Hidden marker: <code><?php echo nadinHtml($hidden); ?></code>. Hidden files are visible only to administrators. <?php require dirname(__DIR__).'/configallowedfileextensions.php';echo 'Allowed types here: '.nadinHtml(implode(', ',$okext)).'.'; ?></p></details>
<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</main></body></html>

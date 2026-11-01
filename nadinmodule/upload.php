<?php
require __DIR__.'/refreshcookies2.inc.php';require_once __DIR__.'/popup-ui.inc.php';auth('upaudio');
setcookie('upos','upload',time()+3600*24*365*10,'/');$name=$_GET['tune'] ?? '';
$infoPath=dirname(__DIR__).'/library/'.$name.'/infos.txt';$raw=is_file($infoPath)?file_get_contents($infoPath):'';$pieces=explode($delimiter2,$raw,2);
nadinPopupStart(str_replace('_',' ',$name));require __DIR__.'/uploadnavi.inc.php';
?>
<form name="form" class="nadin-notes-form" data-nadin-async="notes" method="post" action="uploadsave.php">
<div class="nadin-toolbar nadin-editor-toolbar"><button id="save" class="nadin-primary" type="submit">Save notes</button><span class="nadin-hint">Ctrl / ⌘ + S · changes stay here until saved</span></div>
<input type="hidden" name="csrf" value="<?php echo nadinHtml(nadin_auth()->csrf('media-v12',nadin_session_token())); ?>">
<input type="hidden" name="revision" value="<?php echo hash('sha256',$raw); ?>">
<?php echo nadinPathField('foldername',$name); ?>
<label for="infos">Tips and information about the tune</label><textarea name="infos" id="infos" rows="8"><?php echo nadinHtml(trim($pieces[0])); ?></textarea>
<label for="links">External links</label><textarea name="links" id="links" rows="5" aria-describedby="links-hint"><?php echo nadinHtml(trim($pieces[1] ?? '')); ?></textarea>
<p id="links-hint" class="nadin-hint">One web address per line. An optional label can precede it: Arrangement: https://example.org/</p>
</form><input type="hidden" id="changes" value="0">
<?php require __DIR__.'/legacy-ui.inc.php'; ?>
</main></body></html>

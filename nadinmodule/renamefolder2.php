<?php
require_once __DIR__.'/legacy-write.inc.php';require_once __DIR__.'/admin.inc.php';require_once __DIR__.'/popup-ui.inc.php';
try{
 $old=$_GET['tune'] ?? '';$new=mkascii($_POST['tune'] ?? '');
 $store=new NadinAdmin(dirname(__DIR__),$delimiter2);$count=$store->renameTuneLocked($old,$new);
 header('Content-Type: text/html; charset=UTF-8');
 nadinPopupStart('Tune renamed');
 echo '<p role="status">'.nadinHtml(str_replace('_',' ',$old)).' → '.nadinHtml(str_replace('_',' ',$new)).'</p><p>'.$count.' setlist(s) updated. Media files and forScore identities were preserved.</p><a href="upload.php?tune='.rawurlencode($new).'">Continue editing</a><script>if(opener&&typeof opener.fresh==="function")opener.fresh();</script></body></html>';
}catch(InvalidArgumentException $e){nadin_legacy_error(400,$e->getMessage());}
catch(DomainException $e){nadin_legacy_error(409,$e->getMessage());}
catch(Throwable $e){error_log('NADIN rename: '.$e->getMessage());nadin_legacy_error(500,'The rename could not be completed. Check storage permissions and reload.');}

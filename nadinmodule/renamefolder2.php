<?php
require_once __DIR__.'/legacy-write.inc.php';require_once __DIR__.'/admin.inc.php';require_once __DIR__.'/popup-ui.inc.php';
try{
 $old=$_GET['tune'] ?? '';$new=mkascii($_POST['tune'] ?? '');
 $store=new NadinAdmin(dirname(__DIR__),$delimiter2);$count=$store->renameTuneLocked($old,$new);
 nadin_media_reply(true,'Tune renamed. '.$count.' setlist(s) updated. Media files and forScore identities were preserved.',array('next_url'=>'upload.php?tune='.rawurlencode($new),'next_label'=>'Continue editing'));
}catch(InvalidArgumentException $e){nadin_legacy_error(400,$e->getMessage());}
catch(DomainException $e){nadin_legacy_error(409,$e->getMessage());}
catch(Throwable $e){error_log('NADIN rename: '.$e->getMessage());nadin_legacy_error(500,'The rename could not be completed. Check storage permissions and reload.');}

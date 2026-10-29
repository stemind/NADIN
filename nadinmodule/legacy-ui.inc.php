<?php
require_once dirname(__DIR__).'/config.inc.php';require_once __DIR__.'/functions.inc.php';
$legacyInfoRevision=null;
if(basename($_SERVER['SCRIPT_FILENAME'])==='upload.php'){
 $legacyTune=basename($_GET['tune'] ?? '');$legacyInfo=dirname(__DIR__).'/library/'.$legacyTune.'/infos.txt';
 $legacyInfoRevision=is_file($legacyInfo)?hash_file('sha256',$legacyInfo):hash('sha256','');
}
?>
<script>
(function(){
 var revision=<?php echo json_encode($legacyInfoRevision); ?>;
 var token=<?php echo json_encode(nadin_auth()->csrf('media-v12',nadin_session_token()),JSON_HEX_TAG); ?>;
 window.nadinMutation=function(url){
  var parsed=new URL(url,location.href);if(parsed.origin!==location.origin)return;
  var form=document.createElement('form');form.method='post';form.action=parsed.pathname;
  parsed.searchParams.set('csrf',token);parsed.searchParams.forEach(function(value,name){var input=document.createElement('input');input.type='hidden';input.name=name;input.value=value;form.appendChild(input);});document.body.appendChild(form);form.submit();
 };
 document.querySelectorAll('form[method="post"],form[method="POST"]').forEach(function(form){if(!form.querySelector('[name=csrf]')){var input=document.createElement('input');input.type='hidden';input.name='csrf';input.value=token;form.appendChild(input);}if(revision!==null){var version=document.createElement('input');version.type='hidden';version.name='revision';version.value=revision;form.appendChild(version);}});
}());
</script>

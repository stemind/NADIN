<?php
require_once __DIR__.'/legacy-write.inc.php';
// The write guard holds the common media lock throughout validation and rename.
$file=$_GET['f'] ?? '';$path=$_GET['p'] ?? '';$new=$_GET['n'] ?? '';
if (!is_string($new) || $new==='' || basename($new)!==$new || preg_match('/[\\\\\x00-\x1f]/',$new) || strtolower(pathinfo($new,PATHINFO_EXTENSION))!=='mp3') nadin_legacy_error(400,'Choose a valid MP3 filename.');
$new=mkascii(striptime($new));
$directory=dirname(__DIR__).'/library/'.$path;
$source=$directory.'/'.$file;$target=$directory.'/'.$new;
if (!is_file($source) || is_link($source)) nadin_legacy_error(404,'Audio file not found.');
if ($source!==$target) {
    // Check the exact final name, after stripping the calculated duration.
    if (file_exists($target) || is_link($target)) nadin_legacy_error(409,'A recording with this name already exists. Nothing was overwritten.');
    if (!rename($source,$target)) nadin_legacy_error(500,'Could not rename the recording. Check storage permissions.');
}
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Recording renamed</title></head><body>
<script>
if(parent.opener&&parent.opener.fresh)parent.opener.fresh();
var url=new URL(parent.location.href);
url.searchParams.set('m',<?php echo json_encode(mkid2($new),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>);
parent.location.replace(url.href);
</script>
</body></html>

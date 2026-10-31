<?php
$more=explode($delimiter,$more);
rsort($more);
$countaufnahmen=0;

for ($ii=0;$ii<count($more)-1;$ii++) {
if (trim($more[$ii])!='') {
	echo '<a href="get/file.php?c='.rawurlencode(basename($path).'/'.$more[$ii]).'" target="_blank">'.htmlXspecialchars($more[$ii]).'</a><br />';
    $countaufnahmen++;
}
}

if ($zusatz!='') {
$links=$zusatz[1] ?? '';
$links=explode("\n",$links);
for ($ii=0;$ii<count($links);$ii++) {
$link=trim($links[$ii]);
// Accept both a plain URL and the existing "Label: URL" format.
// Split a label once; never remove a URL's own scheme or later occurrences.
if (!preg_match('~^(https?://|mailto:)~i',$link) && strpos($link,':')!==false) $link=trim(explode(':',$link,2)[1]);
if (trim($links[$ii])!='' && preg_match('~^(https?://|mailto:)~i',trim($link))) {
	echo '<a href="'.htmlXspecialchars(trim($link)).'" target="_blank" rel="noopener noreferrer">'.htmlXspecialchars(trim($links[$ii])).'</a><br />';
    $countaufnahmen++;
}
}
}


?>

<script>
if (document.getElementById('weitereaufnahmen<?php echo $i ?>')) document.getElementById('weitereaufnahmen<?php echo $i ?>').innerHTML='<?php if ($countaufnahmen>0) echo ($countaufnahmen).' '.$humanfilesandlinks ?>';
</script>
<br />

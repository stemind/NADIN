<?php
$more=explode($delimiter,$more);
rsort($more);
$countaufnahmen=0;

for ($ii=0;$ii<count($more)-1;$ii++) {
if (trim($more[$ii])!='') {
	echo '<a href="get/file.php?c='.str_replace('library/','',$path).'/'.$more[$ii].'" target="_blank">'.$more[$ii].'</a><br />';		
    $countaufnahmen++;
}
}

if ($zusatz!='') {
$links=$zusatz[1];
$links=explode("\n",$links);
for ($ii=0;$ii<count($links);$ii++) {
$link=explode(':',$links[$ii]);
$link=str_replace($link[0].':','',$links[$ii]);
if (trim($links[$ii])!='') {
	echo '<a href="'.htmlXspecialchars(trim($link)).'" target="_blank">'.htmlXspecialchars(trim($links[$ii])).'</a><br />';		
    $countaufnahmen++;
}
}
}


?>

<script>
if (document.getElementById('weitereaufnahmen<?php echo $i ?>')) document.getElementById('weitereaufnahmen<?php echo $i ?>').innerHTML='<?php if ($countaufnahmen>0) echo ($countaufnahmen).' '.$humanfilesandlinks ?>';
</script>
<br />
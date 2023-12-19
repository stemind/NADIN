<?php

echo nl2br(htmlXspecialchars($zusatz[0]));

?>

<script>
if (document.getElementById('mehrinfos<?php echo $i ?>')) document.getElementById('mehrinfos<?php echo $i ?>').innerHTML='<?php if (trim(@$zusatz[0])!='') echo htmlXspecialchars($humaninfos) ?>';
</script>

<?php
?>
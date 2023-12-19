<?php
$c=file_get_contents('config.inc.php');
$c=str_replace("$rendergiglistontheleft='no'","$rendergiglistontheleft='yes'",$c);
$c=str_replace("$quotehandling='on'","$quotehandling='DEPRECATED DO NOT USE'",$c);
$c=str_replace("$quotehandling='off'","$quotehandling='DEPRECATED DO NOT USE'",$c);
file_put_contents('config.inc.php',$c);
unlink('corr.php');
?>



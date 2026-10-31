<?php require_once __DIR__.'/legacy-write.inc.php'; ?>
<?php
require ('refreshcookies2.inc.php');
require_once('../config.inc.php');
require_once('functions.inc.php');
$tune=mkascii($_POST['tune']);
setcookie('scrlt', mkid2($tune), time()+600, '/'); 
auth('uppdf');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart('New tune'); ?>

<?php 

if (strlen($tune)<3) die('<script>alert("The name must be at least 3 characters long.");history.go(-1);</script>');

if (file_exists('../library/'.$tune)) die('<script>alert("The tune '.htmlXspecialchars($tune).' already exists.");history.go(-1);</script>');

if (mkdir('../library/'.$tune)) echo '<script>
if(opener)opener.location.reload();
</script>Success, <b>'.htmlXspecialchars($tune).'</b> was created.<script>setTimeout("self.close()",2222);</script>';

?>
</body>
</html>

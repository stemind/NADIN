<?php require_once('../config.inc.php');require_once('functions.inc.php');require_once __DIR__.'/popup-ui.inc.php';nadinPopupStart('Timeout / wrong login'); ?>
Please log in again.
<p>
<a href="javascript:einloggen()">next</a>
<script>
function einloggen() {
if(!opener)window.open('../login.php');
if(opener)opener.location.reload();
if(opener)opener.focus();
document.title='close';
document.getElementById('body').style.display='none';
self.close();
}
</script>
</body>
</html>
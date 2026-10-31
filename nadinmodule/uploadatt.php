<?php require('refreshcookies2.inc.php') ;
auth('mgigs');
?>
<?php require_once('functions.inc.php')?>
<?php
require_once('../config.inc.php');
?>
<?php require_once __DIR__.'/popup-ui.inc.php'; nadinPopupStart(str_replace('.txt','',str_replace('_',' ',$_GET['gig']))); ?>

<h2>Existing pdf attachment</h2>
<iframe title="Existing PDF attachment" src="ATTfiles.php?gig=<?php echo str_replace('.txt','',$_GET['gig'])?>" name="files" frameborder="0" width="100%" height="220"></iframe>


<h2>Upload / replace pdf attachment</h2>
<iframe title="Upload a PDF attachment" src="ATTsendfile.php?gig=<?php echo str_replace('.txt','',$_GET['gig'])?>" frameborder="0" width="100%" height="88"></iframe>
<span style="font-size:70%"><?php include('iniget.inc.php')?></span>
<p>
<script>document.title=document.title.replace(/manage/,'<?php echo $bb ?> - ')</script>
</body>
</html>

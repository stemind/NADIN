<?php 
require('nadinmodule/refreshcookies.inc.php'); 
require_once('config.inc.php');
header('Cache-Control: private, no-store');
if (!isset($_GET['m']) || !is_string($_GET['m']) || !in_array($_GET['m'], array('gigreps', 'gigrepsarchiv', 'reclist', 'library', 'users', 'managegigs'), true)) $_GET['m']='gigreps';

if ($_GET['m']==='library' && ($_GET['c'] ?? '')==='admin') $_GET['m']='managegigs';
if ($_GET['m']==='gigreps' && !role('vgigs')) {
    foreach (array('reclist'=>'vreclist','library'=>'vlibrary','managegigs'=>'mgigs','users'=>'musers') as $module=>$right) if (role($right)) { $_GET['m']=$module; break; }
}
$moduleRights=array('gigreps'=>'vgigs','gigrepsarchiv'=>'varchiv','reclist'=>'vreclist','library'=>'vlibrary','users'=>'musers','managegigs'=>'mgigs');
auth($moduleRights[$_GET['m']]);
if ($_GET['m']==='gigrepsarchiv') auth('vgigs');

?>
<!doctype html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" type="text/css" href="style.css" />
<link rel="icon" href="favicon.ico" type="image/x-icon" /><link rel="icon" href="nadin.svg" type="image/svg+xml" />
<title><?php echo htmlspecialchars($bb,ENT_QUOTES,'UTF-8'); ?> — <?php echo htmlspecialchars(array('gigreps'=>'Current GigReps','gigrepsarchiv'=>'Archived GigReps','library'=>'Score & Media Library','reclist'=>'Recordings','managegigs'=>'Gigs & setlists','users'=>'People & permissions')[$_GET['m']],ENT_QUOTES,'UTF-8'); ?> — NADIN</title>
<?php 
require_once('nadinmodule/functions.inc.php')
?>
<script src="nadinmodule/task.js" defer></script>
</head>
<body class="nadin-page" data-nadin-page="<?php echo htmlspecialchars($_GET['m'],ENT_QUOTES,'UTF-8'); ?>">
<a class="nadin-skip-link" href="#nadin-primary-content">Skip to content</a>
<header class="nadin-header">
<button type="button" class="nadin-sidebar-toggle" id="nadin-sidebar-toggle" aria-expanded="false" aria-controls="nadin-sidebar" aria-label="Open list">&#9776;</button>
<h1>NADIN <small class="nadin-version">V17</small></h1>
<div class="nadin-account"><span class="nadin-account-email"><?php echo htmlXspecialchars($uc) ?></span> | <a href="password.php">password</a> | <a href="logout.php">logout</a></div>
<img src="nadin.svg" width="40" height="40" alt="" />
</header>
<?php
require_once('nadinmodule/navigation.inc.php');

echo '<div id="nadin-primary-content" tabindex="-1">';
if (file_exists('nadinmodule/'.basename($_GET['m']).'.inc.php')) require_once('nadinmodule/'.basename($_GET['m']).'.inc.php');
echo '</div>';

?>
<iframe name="general" title="Background requests" style="display:none"></iframe>
<script src="nadinmodule/layout.js"></script>
<script>setTimeout("location.href='login.php'",<?php echo ($timeout*1000) ?>);</script>
<?php 
require_once('nadinmodule/findpos.js.php');

// Inspect writable storage without creating and deleting a test file on each page view.
$unwritable=array();foreach(array('library','userdatei','gigreps','gigrepsarchiv','gigrepsdeleted','filesdeleted','abo/zip') as $folder)if(!is_dir($folder)||!is_writable($folder))$unwritable[]=$folder;
if($unwritable&&(role('musers')||role('mgigs')))echo '<aside class="nadin-storage-notice" role="alert">The server needs write access to these NADIN folders: '.htmlspecialchars(implode(', ',$unwritable),ENT_QUOTES,'UTF-8').'. Ask the installation administrator to check storage permissions.</aside>';

?>
</body>
</html>

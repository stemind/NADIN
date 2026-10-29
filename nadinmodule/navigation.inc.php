<input type="hidden" name="changes" id="changes" value="0">
<nav class="nadin-navigation" aria-label="Main navigation">
<?php
$navigation=array('gigreps'=>array('vgigs',$aktuellegigs,'Gigs'),'gigrepsarchiv'=>array('varchiv',$archiviertegigs,'Archive'),'reclist'=>array('vreclist',$bbaufnahmen,'Audio'),'library'=>array('vlibrary',$bibliothek,'Library'));
foreach ($navigation as $module=>$item) if (role($item[0])) echo '<a id="'.$module.'" href="?m='.$module.'"'.($_GET['m']===$module?' aria-current="page"':'').'><span class="nadin-nav-long">'.nw($item[1]).'</span><span class="nadin-nav-short">'.$item[2].'</span></a> ';
?>
</nav>
<div class="nadin-admin-navigation">
<?php
if(role('mgigs'))echo '<a id="managegigs" class="nadin-admin-action" href="?m=managegigs"'.($_GET['m']==='managegigs'?' aria-current="page"':'').'>Manage gigs</a>';
if(role('musers'))echo '<a id="manageusers" class="nadin-admin-action" href="?m=users"'.($_GET['m']==='users'?' aria-current="page"':'').'>People & permissions</a>';
?>
</div>

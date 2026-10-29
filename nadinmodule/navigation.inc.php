<input type="hidden" name="changes" id="changes" value="0">
<script>
function alertchanges2() {
if (document.getElementById('changes').value==1) {
if (confirm('There are unsaved changes which got lost if you are proceeding. Proceed?')) return true;
else return false;
}
}
</script>
<h2 class="nadin-navigation">
<a onclick="return alertchanges2()" id="gigreps" href="./?m=gigreps"><span class="nadin-nav-long"><?php echo nw($aktuellegigs) ?></span><span class="nadin-nav-short">Gigs</span></a>
 &nbsp;
<a onclick="return alertchanges2()" id="gigrepsarchiv" href="?m=gigrepsarchiv"><span class="nadin-nav-long"><?php echo nw($archiviertegigs) ?></span><span class="nadin-nav-short">Archive</span></a>
 &nbsp;
<a onclick="return alertchanges2()" id="reclist" href="?m=reclist"><span class="nadin-nav-long"><?php echo nw($bbaufnahmen) ?></span><span class="nadin-nav-short">Audio</span></a>
 &nbsp;
<a onclick="return alertchanges2()" id="library" href="./?m=library"><span class="nadin-nav-long"><?php echo nw($bibliothek) ?></span><span class="nadin-nav-short">Library</span></a>
</h2>

<script>
if (document.getElementById('<?php echo $_GET['m']?>')) document.getElementById('<?php echo $_GET['m']?>').style.background='yellow';

window.onbeforeunload = function() {
if (document.getElementById('changes').value==1) return('You have unsaved changes which will be lost if you are proceeding.');
self.focus();
}
</script>
<?php
if (role('mgigs')) echo '<button id="managegigs" onclick="location.href=\'?m=library&c=admin\'" class="nadin-admin-action nadin-manage-gigs">manage Gigs</button>';
if (role('musers'))echo '<button id="manageusers" onclick="location.href=\'?m=users\'" class="nadin-admin-action nadin-manage-users">manage Users</button>';
?>

<input type="hidden" name="changes" id="changes" value="0">
<script>
function alertchanges2() {
if (document.getElementById('changes').value==1) {
if (confirm('There are unsaved changes which got lost if you are proceeding. Proceed?')) return true;
else return false;	
}
}
</script>
<h2>
<a onclick="return alertchanges2()" id="gigreps" href="./?m=gigreps"><?php echo nw($aktuellegigs) ?></a>   
 &nbsp; 
<a onclick="return alertchanges2()" id="gigrepsarchiv" href="?m=gigrepsarchiv"><?php echo nw($archiviertegigs) ?></a>
 &nbsp; 
<a onclick="return alertchanges2()" id="reclist" href="?m=reclist"><?php echo nw($bbaufnahmen) ?></a>
 &nbsp; 
<a onclick="return alertchanges2()" id="library" href="./?m=library"><?php echo nw($bibliothek) ?></a>
</h2>

<script>
if (document.getElementById('<?php echo $_GET['m']?>')) document.getElementById('<?php echo $_GET['m']?>').style.background='yellow';

window.onbeforeunload = function() {
if (document.getElementById('changes').value==1) return('You have unsaved changes which will be lost if you are proceeding.');
self.focus();
}
</script>
<?php 
if (role('mgigs')) echo '<button id="managegigs" onclick="location.href=\'?m=library&c=admin\'" style="font-size:70%;position:absolute;top:27px;left:222px">manage Gigs</button>';
if (role('musers'))echo '<button id="manageusers" onclick="location.href=\'?m=users\'" style="font-size:70%;position:absolute;top:27px;left:320px">manage Users</button>';
?>
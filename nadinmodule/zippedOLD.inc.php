<?php 
echo '<span id="zipsi">ZIP</span><span id="zippl">s</span><span id="zipdo">:</span><script>z=0</script> ';

$fsz=round(filesize('abo/zip/alllistento/'.str_replace('.txt','',basename($_GET['c'])).'_alllistento.zip')/1024);
if (file_exists('abo/zip/alllistento/'.str_replace('.txt','',basename($_GET['c'])).'_alllistento.zip') && $fsz>50) echo '<a '.$mute.' href="abo/zip/alllistento/'.str_replace('.txt','',basename($_GET['c'])).'_alllistento.zip">Audio <small>'.(ceil($fsz/1024)).' MB</small></a> | <script>z++;</script>';


$mute='';
if (!allowedfor('vscore')) $mute='class="mute"';
$fsz=round(filesize('abo/zip/allscore/'.str_replace('.txt','',basename($_GET['c'])).'_allscore.zip')/1024);
if (file_exists('abo/zip/allscore/'.str_replace('.txt','',basename($_GET['c'])).'_allscore.zip') && $fsz>50) echo '<a '.$mute.' href="abo/zip/allscore/'.str_replace('.txt','',basename($_GET['c'])).'_allscore.zip">'.$score.'&nbsp;<small>'.(ceil($fsz/1024)).'&nbsp;MB</small></a> | <script>z++;</script>';

$mute='';
if (!allowedfor('vgigs')) $mute='class="mute"';
$fsz=round(filesize('abo/zip/allparts/'.str_replace('.txt','',basename($_GET['c'])).'_allparts.zip')/1024);
if (file_exists('abo/zip/allparts/'.str_replace('.txt','',basename($_GET['c'])).'_allparts.zip') && $fsz>50) echo '<a '.$mute.' href="abo/zip/allparts/'.str_replace('.txt','',basename($_GET['c'])).'_allparts.zip">'.$parts.'&nbsp;<small>'.(ceil($fsz/1024)).'&nbsp;MB</small></a> | <script>z++;</script>';


$mute='';
if (!allowedfor('vsaxes')) $mute='class="mute"';
$fsz=round(filesize('abo/zip/allsaxes/'.str_replace('.txt','',basename($_GET['c'])).'_allsaxes.zip')/1024);
if (file_exists('abo/zip/allsaxes/'.str_replace('.txt','',basename($_GET['c'])).'_allsaxes.zip') && $fsz>50) echo '<a '.$mute.' href="abo/zip/allsaxes/'.str_replace('.txt','',basename($_GET['c'])).'_allsaxes.zip">'.$saxes.'&nbsp;<small>'.(ceil($fsz/1024)).'&nbsp;MB</small></a> | <script>z++;</script>';

$mute='';
if (!allowedfor('vbones')) $mute='class="mute"';
$fsz=round(filesize('abo/zip/allbones/'.str_replace('.txt','',basename($_GET['c'])).'_allbones.zip')/1024);
if (file_exists('abo/zip/allbones/'.str_replace('.txt','',basename($_GET['c'])).'_allbones.zip') && $fsz>50) echo '<a '.$mute.' href="abo/zip/allbones/'.str_replace('.txt','',basename($_GET['c'])).'_allbones.zip">'.$bones.'&nbsp;<small>'.(ceil($fsz/1024)).'&nbsp;MB</small></a> | <script>z++;</script>';

$mute='';
if (!allowedfor('vtrumpets')) $mute='class="mute"';
$fsz=round(filesize('abo/zip/alltrumpets/'.str_replace('.txt','',basename($_GET['c'])).'_alltrumpets.zip')/1024);
if (file_exists('abo/zip/alltrumpets/'.str_replace('.txt','',basename($_GET['c'])).'_alltrumpets.zip') && $fsz>50) echo '<a '.$mute.' href="abo/zip/alltrumpets/'.str_replace('.txt','',basename($_GET['c'])).'_alltrumpets.zip">'.$trumpets.'&nbsp;<small>'.(ceil($fsz/1024)).'&nbsp;MB</small></a> | <script>z++;</script>';

$mute='';
if (!allowedfor('vrhythm')) $mute='class="mute"';
$fsz=round(filesize('abo/zip/allrhythm/'.str_replace('.txt','',basename($_GET['c'])).'_allrhythm.zip')/1024);
if (file_exists('abo/zip/allrhythm/'.str_replace('.txt','',basename($_GET['c'])).'_allrhythm.zip') && $fsz>50) echo '<a '.$mute.' href="abo/zip/allrhythm/'.str_replace('.txt','',basename($_GET['c'])).'_allrhythm.zip">'.$rhythm.'&nbsp;<small>'.(ceil($fsz/1024)).'&nbsp;MB</small></a> | <script>z++;</script>';

$mute='';
if (!allowedfor('vother')) $mute='class="mute"';
$fsz=round(filesize('abo/zip/allother/'.str_replace('.txt','',basename($_GET['c'])).'_allother.zip')/1024);
if (file_exists('abo/zip/allother/'.str_replace('.txt','',basename($_GET['c'])).'_allother.zip') && $fsz>50) echo '<a '.$mute.' href="abo/zip/allother/'.str_replace('.txt','',basename($_GET['c'])).'_allother.zip">'.$other.'&nbsp;<small>'.(ceil($fsz/1024)).'&nbsp;MB</small></a> | <script>z++;</script>';

?>
<script>
if (z<1) document.getElementById('zipsi').style.display='none';
if (z<1) document.getElementById('zipdo').style.display='none';
if (z<2) document.getElementById('zippl').style.display='none';
</script>

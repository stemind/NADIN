<?php if ($offeritunesabo=='yes') { ?>
<p class="postscriptum">

<a href="javascript:void(0)" onclick="document.getElementById('tipp').style.display='block';this.style.display='none';document.getElementById('ipad').focus();document.getElementById('ipad').blur()">Subscribe to the recordings of your big band to be viewed on iOS (iPad), Android, iTunes etc.</a>

<div id="tipp">
<h4>The recordings of your big band  as Podcast </h4>
Subscribe for iTunes: <a href="<?php echo str_replace('http://','itpc://',str_replace(basename($nadin),'abo/us.php',$nadin))?>">click here</a> <small>[<a id="itunes" href="http://yuba.ch/sm/itunesanleitungnadin_e/?url=<?php echo str_replace(basename($nadin),'abo/us.php',$nadin)?>" target="_blank">Instructions</a>]</small><br />
<br />Or,  subscribe for another Podcatcher/RSS-Reader using this URL: <a href="<?php echo str_replace(basename($nadin),'abo/us.php',$nadin)?>"><?php echo str_replace(basename($nadin),'abo/us.php',$nadin)?></a><br />
<br />
Subscribe for iOS (iPad/iPhone) or Android: <a id="ipad" href="http://yuba.ch/sm/nadinsubscribe_e/?url=<?php echo str_replace(basename($nadin),'abo/us.php',$nadin)?>" target="_blank">Instructions</a>
<br />
&nbsp;
</div>
</p>
<?php } ?>



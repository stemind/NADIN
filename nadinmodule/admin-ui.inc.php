<?php
require_once __DIR__.'/admin.inc.php';
$people=($_GET['m']==='users'); auth($people ? 'musers' : 'mgigs');
$store=new NadinAdmin(dirname(__DIR__),$delimiter2);
$boot=array('area'=>$people ? 'people' : 'gigs','actor'=>$uc,'csrf'=>nadin_auth()->csrf('admin-v12',nadin_session_token()),'rights'=>NadinAdmin::rights());
$boot['startPane']=!$people&&isset($_GET['c'])?'editor':'list';
if ($people) { $boot['people']=$store->people(); if ($boot['people']['rows']) $boot['selected']=$boot['people']['rows'][0]; }
else { $boot['gigs']=$store->gigs(); $boot['catalog']=$store->catalog(); $selected=$_GET['c'] ?? ($boot['gigs'][0]['id'] ?? ''); if ($selected && $selected!=='admin') { try {$boot['selected']=$store->gig('current',$selected);} catch (Throwable $e) {} } }
?>
<main class="nadin-admin" id="nadin-admin">
<div class="nadin-admin-heading"><h3><span class="nadin-admin-area"><?php echo $people ? 'People & permissions' : 'Gigs & setlists'; ?></span><span id="admin-context"></span></h3><span id="admin-status" role="status" aria-live="polite" data-state="ready">Ready</span></div>
<div id="admin-error" role="alert" tabindex="-1" hidden></div><span id="admin-action-status" class="nadin-sr-only" role="status"></span>
<div class="nadin-workspace-tabs" aria-label="<?php echo $people?'People workspace':'Gig workspace'; ?>"><button type="button" data-workspace-pane="list" aria-controls="admin-browser-panel"><?php echo $people?'People':'Gigs'; ?></button><button type="button" data-workspace-pane="editor" aria-controls="admin-editor"><?php echo $people?'Permissions':'Setlist'; ?></button><?php if(!$people){ ?><button type="button" data-workspace-pane="catalog" aria-controls="admin-catalog-panel">Add tunes <span id="workspace-tune-count"></span></button><?php } ?></div>
<div class="nadin-admin-workspace <?php echo $people ? 'nadin-people-workspace' : ''; ?>">
<aside class="nadin-admin-list" id="admin-browser-panel" aria-label="<?php echo $people?'People list':'Gig list'; ?>">
<div class="nadin-toolbar"><button type="button" id="admin-new" class="nadin-primary"><?php echo $people ? '+ Add person' : '+ New gig'; ?></button><?php if (!$people) { ?><select id="gig-mode" aria-label="Gig list"><option value="current">Current</option><option value="archive">Archive</option><option value="trash">Deleted</option></select><?php } ?></div>
<input type="search" id="admin-search" aria-label="<?php echo $people ? 'Find a person' : 'Find a gig'; ?>" placeholder="<?php echo $people ? 'Find a person…' : 'Find a gig…'; ?>">
<?php if($people){ ?><div class="nadin-toolbar"><label for="people-filter">Show</label><select id="people-filter"><option value="all">Everyone</option><option value="active">Active access</option><option value="expired">Expired access</option><option value="managers">Administrators &amp; managers</option></select></div><?php } ?><p id="admin-count" class="nadin-list-count" role="status"></p>
<div id="admin-items" class="nadin-choice-list"></div>
<?php if($people){ ?><a class="nadin-access-link" href="nadinmodule/p.php" target="_blank" data-nadin-window="access-details">Access details ↗</a><?php } ?>
</aside>
<section id="admin-editor" class="nadin-admin-editor" aria-label="<?php echo $people ? 'Person editor' : 'Gig editor'; ?>"><p>Select an entry or create a new one.</p></section>
<?php if (!$people) { ?><aside class="nadin-catalog" id="admin-catalog-panel"><h4>Score &amp; Media Library</h4><input type="search" id="catalog-search" aria-label="Find a tune to add" placeholder="Find a tune…"><p class="nadin-hint">Add with + or drag to the required position. Repeated tunes are allowed.</p><p id="catalog-count" class="nadin-list-count" role="status"></p><div id="catalog-items" class="nadin-choice-list"></div></aside><?php } ?>
</div>
<noscript><p>The editors need JavaScript. Enable it to edit gigs and permissions.</p></noscript>
</main>
<dialog id="admin-dialog" class="nadin-dialog" aria-labelledby="dialog-title"><form method="dialog" id="admin-dialog-form"><h3 id="dialog-title"></h3><div id="dialog-fields"></div><div class="nadin-dialog-actions"><button type="button" id="dialog-cancel">Cancel</button><button type="button" id="dialog-save" class="nadin-primary" hidden>Save and continue</button><button id="dialog-confirm" value="confirm" class="nadin-primary">Continue</button></div></form></dialog>
<script type="application/json" id="nadin-admin-data"><?php echo json_encode($boot,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE); ?></script>
<script src="nadinmodule/admin.js"></script>

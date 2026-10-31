<?php
// One compact frame for the existing standalone editor windows.
require_once __DIR__.'/ui.inc.php';
function nadinPopupStart($title, $assets = '../', $class = '') {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.nadinHtml($title).' - NADIN</title><link rel="stylesheet" href="'.nadinHtml($assets).'style.css"><link rel="icon" href="'.nadinHtml($assets).'favicon.ico"><script src="'.nadinHtml($assets).'shortcut.js"></script><script>shortcut.add("Ctrl+S",function(){if(document.form)document.form.submit();});</script></head><body id="body" class="nadin-popup '.nadinHtml($class).'">';
    echo '<header class="nadin-popup-header"><span class="nadin-popup-brand">NADIN</span><h1>'.nadinHtml($title).'</h1><button type="button" class="nadin-popup-close" aria-label="Close window" title="Close window" onclick="window.close()">&#215;</button></header>';
}

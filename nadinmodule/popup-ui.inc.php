<?php
// One compact frame for the existing standalone editor windows.
require_once __DIR__.'/ui.inc.php';
function nadinPopupStart($title, $assets = '../', $class = '') {
    global $autoclosepopups;
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.nadinHtml($title).' - NADIN</title><link rel="stylesheet" href="'.nadinHtml($assets).'style.css"><link rel="icon" href="'.nadinHtml($assets).'favicon.ico"><link rel="icon" href="'.nadinHtml($assets).'nadin.svg" type="image/svg+xml"><script src="'.nadinHtml($assets).'nadinmodule/task.js" defer></script></head><body id="body" data-nadin-auto-close="'.($autoclosepopups==='yes'?'yes':'no').'" class="nadin-popup '.nadinHtml($class).'">';
    echo '<header class="nadin-popup-header"><a class="nadin-popup-brand" href="'.nadinHtml($assets).'?m=library" title="Back to NADIN">NADIN <small>V17</small></a><h1>'.nadinHtml($title).'</h1><button type="button" class="nadin-popup-close" aria-label="Close window" title="Close window" data-nadin-close>&#215;</button></header><main id="nadin-task-main" tabindex="-1"><div id="nadin-task-error" class="nadin-task-error" role="alert" tabindex="-1" hidden></div><p id="nadin-task-status" class="nadin-task-status" role="status" hidden></p>';
}

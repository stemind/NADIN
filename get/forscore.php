<?php
require_once('../config.inc.php');
// Override legacy server defaults before authentication or preview output.
header('Content-Type: text/html; charset=UTF-8');
require_once('../nadinmodule/functions.inc.php');
require_once('../nadinmodule/forscore.inc.php');
auth('vgigs');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function fsInput($source, $key, $default = '') {
    if (!isset($source[$key])) return $default;
    if (!is_string($source[$key]) || strlen($source[$key]) > 4096) throw new RuntimeException('Invalid request field.');
    return $source[$key];
}
function fsOptionSummary($option, $profiles, $mode, $workflow) {
    $status = $option['known'] ? ($option['old'] ? 'keep your local version' : 'reuse your existing PDF') : ($mode === 'new' ? 'PDF will be included' : 'reference only; no PDF included');
    if ($workflow === '') $status = 'choose an import option';
    return nadinText($profiles[$option['asset']['type']]['label']) . ' · ' . $status;
}
function fsExportSummary($plan, $mode) {
    $summary = array('entries' => count($plan['entries']), 'headings' => 0, 'included' => 0,
        'reused' => 0, 'references' => 0, 'missing' => 0, 'unresolved' => 0);
    $seen = array();
    foreach ($plan['entries'] as $key) {
        $row = $plan['rows'][$key];
        if ($row['kind'] === 'heading') { $summary['headings']++; continue; }
        if ($row['selected'] === '') { $summary[$row['options'] ? 'unresolved' : 'missing']++; continue; }
        $option = $row['options'][$row['selected']];
        if (isset($seen[$option['path']])) continue;
        $seen[$option['path']] = true;
        $summary[$option['known'] ? 'reused' : ($mode === 'new' ? 'included' : 'references')]++;
    }
    return $summary;
}
function fsInventoryState($encoded) {
    if (!is_string($encoded) || strlen($encoded) > 8 * 1024 * 1024) throw new RuntimeException('Invalid library inventory.');
    $names = json_decode((string)base64_decode($encoded, true), true);
    if (!is_array($names) || count($names) > 50000) throw new RuntimeException('Invalid library inventory.');
    $seen = array();
    foreach ($names as $name) {
        if (!is_string($name) || strlen($name) > 512 || preg_match('~[\\\\/\x00-\x1f]~', $name) || strtolower(substr($name, -4)) !== '.pdf' || isset($seen[$name])) throw new RuntimeException('Invalid filename in the library inventory.');
        $seen[$name] = true;
    }
    return array_keys($seen);
}
function fsSignature($plan, $module, $rep, $profile, $names, $secret) {
    $snapshot = array();
    foreach ($plan['rows'] as $key => $row) {
        $snapshot[$key] = array('kind' => $row['kind'], 'options' => array());
        foreach ($row['options'] as $id => $option) $snapshot[$key]['options'][$id] = array($option['path'], $option['asset']['hash']);
    }
    return hash_hmac('sha256', serialize(array($module, $rep, $profile, $names, $plan['entries'], $snapshot)), $secret);
}

$error = ''; $plan = null; $names = array(); $loaded = false; $profile = ''; $module = 'gigreps'; $rep = '';
$mode = 'references'; $initial = false; $placeholders = false; $missingNotes = false;
$workflow = ''; $inventoryName = ''; $resetChoices = false;
$profiles = array(
    'sx' => array('code' => $saxes, 'label' => $humansaxes, 'right' => 'vsaxes'),
    'tb' => array('code' => $bones, 'label' => $humanbones, 'right' => 'vbones'),
    'tp' => array('code' => $trumpets, 'label' => $humantrumpets, 'right' => 'vtrumpets'),
    'rh' => array('code' => $rhythm, 'label' => $humanrhythm, 'right' => 'vrhythm'),
    'ot' => array('code' => $other, 'label' => $humanother, 'right' => 'vother'),
    'parts' => array('code' => $parts, 'label' => $humanparts, 'right' => 'vgigs'),
    'score' => array('code' => $score, 'label' => $humanscore, 'right' => 'vscore')
);
$rights = array();
foreach (array('vgigs', 'varchiv', 'vlibrary', 'musers', 'vsaxes', 'vbones', 'vtrumpets', 'vrhythm', 'vother', 'vscore') as $right) $rights[$right] = role($right);
$exporter = new NadinForScore(dirname(__DIR__), $delimiter2, $profiles, $rights);
$allowed = $exporter->allowedProfiles();
// Keep only a section preference, separately for each installation and account.
$sectionCookie = 'nadin_forscore_section_' . substr(hash('sha256', __DIR__ . '|' . $uc), 0, 20);
$savedProfile = isset($_COOKIE[$sectionCookie]) && is_string($_COOKIE[$sectionCookie]) && isset($allowed[$_COOKIE[$sectionCookie]])
    ? $_COOKIE[$sectionCookie] : (string)array_key_first($allowed);
$secret = hash('sha256', $salt . '|' . $uc . '|' . $pmd5c);
$csrf = hash_hmac('sha256', 'NADIN forScore export', $secret);
try {
    $post = $_SERVER['REQUEST_METHOD'] === 'POST';
    if ($post && !$_POST) throw new RuntimeException('The request is too large. Check the server upload/post limits or use a smaller CSV.');
    if ($post && fsInput($_POST, 'form_complete') !== 'yes') throw new RuntimeException('The form was truncated. Export fewer tunes or increase the server max_input_vars limit.');
    if ($post && !hash_equals($csrf, fsInput($_POST, 'csrf'))) throw new RuntimeException('The session changed. Reopen the export from NADIN.');
    $input = $post ? $_POST : $_GET;
    $workflow = fsInput($input, 'workflow');
    // Old cached forms/API clients can still use mode/initial; the new UI has one choice.
    $legacyImport = array_key_exists('mode', $input) || array_key_exists('initial', $input);
    if ($legacyImport) {
        $legacyMode = fsInput($input, 'mode', 'references');
        if (!in_array($legacyMode, array('references', 'new'), true)) throw new RuntimeException('Invalid export mode.');
        $workflow = $legacyMode === 'new' ? (fsInput($input, 'initial') === '1' ? 'first' : 'update') : 'references';
    }
    if (!in_array($workflow, array('', 'first', 'update', 'references'), true)) throw new RuntimeException('Invalid import option.');
    $mode = in_array($workflow, array('first', 'update'), true) ? 'new' : 'references';
    $initial = $workflow === 'first';
    $module = fsInput($input, 'm', 'gigreps');
    $rep = $post ? base64_decode(fsInput($input, 'rep'), true) : fsInput($input, 'c');
    if ($rep === false) throw new RuntimeException('Invalid GigRep.');
    $profile = fsInput($input, 'profile', $savedProfile);
    if ($post && isset($_POST['inventory'])) {
        $names = fsInventoryState($_POST['inventory']);
        $loaded = fsInput($_POST, 'inventory_loaded') === '1';
        $inventoryName = fsInput($_POST, 'inventory_name');
    }
    if ($post && isset($_FILES['csv']) && $_FILES['csv']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['csv']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['csv']['tmp_name'])) throw new RuntimeException('CSV upload failed or exceeds the server upload limit.');
        if ($_FILES['csv']['size'] > 5 * 1024 * 1024) throw new RuntimeException('The CSV exceeds 5 MB.');
        $names = NadinForScore::inventory(file_get_contents($_FILES['csv']['tmp_name']));
        $loaded = true;
        $inventoryName = isset($_FILES['csv']['name']) && is_string($_FILES['csv']['name']) && strlen($_FILES['csv']['name']) <= 512 ? basename(str_replace('\\', '/', $_FILES['csv']['name'])) : 'Inventory CSV';
    }
    if ($legacyImport && $loaded && $mode === 'new') { $workflow = 'update'; $initial = false; }
    if ($workflow === 'first') { $resetChoices = $loaded; $names = array(); $loaded = false; $inventoryName = ''; }
    $choices = array();
    if ($post && !$resetChoices && fsInput($_POST, 'reviewed_profile') === $profile && isset($_POST['choice'])) {
        if (!is_array($_POST['choice']) || count($_POST['choice']) > 10000) throw new RuntimeException('Invalid PDF selection.');
        foreach ($_POST['choice'] as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/', $key) || !is_string($value) || strlen($value) > 80) throw new RuntimeException('Invalid PDF selection.');
        }
        // A fresh inventory must determine the initial suggestions again.
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] === UPLOAD_ERR_NO_FILE) $choices = $_POST['choice'];
    }
    $plan = $exporter->plan($module, $rep, $profile, $names, $choices);
    foreach ($plan['rows'] as $row) if ($row['kind'] === 'tune' && !$row['options']) $missingNotes = true;
    $cookiePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/';
    setcookie($sectionCookie, $profile, array('expires' => time() + 365 * 86400,
        'path' => $cookiePath, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax'));
    $signature = fsSignature($plan, $module, $rep, $profile, $names, $secret);
    $placeholders = fsInput($input, 'placeholders') === '1';
    $title = ($module === 'library' ? $bb . ' — Library' : substr(nadinText($rep), 0, -4)) . ' — ' . nadinText($allowed[$profile]['label']);
    if ($post && fsInput($_POST, 'action') === 'download') {
        if ($workflow === '') throw new RuntimeException('Choose an import option before downloading.');
        if (!hash_equals($signature, fsInput($_POST, 'view'))) throw new RuntimeException('The Rep, inventory or PDFs changed since the preview. Review the selection again before downloading.');
        if ($mode === 'new' && !$loaded && !$initial) throw new RuntimeException('Upload a current forScore library CSV, or confirm that this is the first NADIN import into this forScore library.');
        $temporary = tmpfile();
        if (!$temporary) throw new RuntimeException('No temporary export space available.');
        try {
            NadinForScore::writeSetlist($temporary, $title, $plan, $mode === 'new', $placeholders);
            fflush($temporary); rewind($temporary);
            $stat = fstat($temporary);
            $filename = trim(preg_replace('/[^A-Za-z0-9_-]+/', '_', $title), '_');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . substr($filename, 0, 100) . '.4ss"');
            header('Content-Length: ' . $stat['size']);
            fpassthru($temporary);
        } finally { fclose($temporary); }
        exit;
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
    http_response_code(400);
    // A complete recomputed preview can be reviewed again after a stale download.
}
$back = '../?' . http_build_query(array('m' => in_array($module, array('gigreps', 'gigrepsarchiv', 'library'), true) ? $module : 'gigreps', 'c' => $rep));
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/x-icon" href="../favicon.ico">
<link rel="stylesheet" href="../style.css"><title>forScore export — NADIN 7.7</title><style>
/* Local styles: the existing NADIN 7.6 stylesheet remains unchanged. */
.nadin-forscore { margin:8px; }
.nadin-forscore form { max-width:1100px; }
.nadin-forscore-rows { border-top:1px solid #ddd; }
.nadin-forscore-row { padding:7px 3px; border-bottom:1px solid #ddd; display:grid; grid-template-columns:minmax(160px,1fr) minmax(180px,2fr); gap:3px 12px; align-items:center; }
.nadin-forscore-row small { grid-column:2; color:#666; }
.nadin-forscore-row select { max-width:100%; min-width:0; }
.nadin-forscore-heading { background:#eee; display:block; font-style:italic; }
.nadin-forscore-heading small { display:block; }
.nadin-forscore-error,.nadin-forscore-warning { background:lightyellow; padding:5px; }
.nadin-forscore-help { max-width:1100px; }
@media(max-width:600px) { .nadin-forscore-row { display:block; } .nadin-forscore-row label,.nadin-forscore-row small { display:block; } .nadin-forscore input[type=file] { max-width:100%; } }

/* forScore workflow only; colours and controls inherit this branch's theme. */
.nadin-forscore-controls { display:flex;flex-wrap:wrap;align-items:flex-end;gap:8px 12px;margin:10px 0; }
.nadin-forscore-controls label { display:flex;flex-direction:column;gap:3px;max-width:100%; }
.nadin-forscore-controls select { box-sizing:border-box;max-width:100%; }
.nadin-forscore-inventory,.nadin-forscore-summary { padding:7px;border:1px solid var(--nadin-line,#ddd);border-radius:3px;margin:8px 0; }
.nadin-forscore-summary { background:var(--nadin-surface,#eee); }
.nadin-forscore-summary p { margin:5px 0; }
.nadin-forscore-file { overflow-wrap:anywhere;min-width:0; }
.nadin-forscore [hidden] { display:none !important; }
.nadin-forscore form[aria-busy=true] { pointer-events:none;cursor:progress; }
.nadin-forscore [data-review-status] { color:var(--nadin-muted,#666); }
.nadin-forscore button:disabled { opacity:.6;cursor:default; }

/* Separate the action from optional help; validate fields after an attempted download. */
.nadin-forscore .nadin-forscore-help { margin-top:24px;padding-top:12px;border-top:1px solid var(--nadin-line,#ddd); }
.nadin-forscore [aria-invalid=true] { outline:2px solid #b42318;outline-offset:2px; }
.nadin-forscore form[aria-busy=true] button[value=download] { pointer-events:auto; }
</style>
</head>
<body class="nadin-page nadin-forscore">
<h2>forScore <small>— NADIN 7.7</small></h2>
<p><a href="<?php echo nadinHtml($back); ?>">&larr; Back to NADIN</a></p>
<?php if ($error !== '') { ?>
<p role="alert" class="nadin-forscore-error"><?php echo nadinHtml($error); ?></p>
<?php } ?>
<?php if ($plan === null) { ?><p>Return to NADIN and reopen the export to review a fresh selection.</p>
<?php } else { ?>
<p><strong><?php echo nadinHtml($title); ?></strong></p>
<form id="nadin-forscore-form" data-context="<?php echo hash('sha256', $module . '|' . $rep); ?>" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
<input type="hidden" name="csrf" value="<?php echo nadinHtml($csrf); ?>">
<input type="hidden" name="m" value="<?php echo nadinHtml($module); ?>">
<input type="hidden" name="rep" value="<?php echo nadinHtml(base64_encode($rep)); ?>">
<input type="hidden" name="reviewed_profile" value="<?php echo nadinHtml($profile); ?>">
<input type="hidden" name="inventory_name" value="<?php echo nadinHtml($inventoryName); ?>">
<input type="hidden" name="inventory_loaded" value="<?php echo $loaded ? '1' : '0'; ?>">
<input type="hidden" name="inventory" value="<?php echo nadinHtml(base64_encode(json_encode($names))); ?>">
<input type="hidden" name="view" value="<?php echo nadinHtml($signature); ?>">
<p class="postscriptum">Your section is remembered on this browser. Section PDFs are preferred; Parts is used when needed.</p>
<div class="nadin-forscore-controls">
<label>Section <select name="profile" data-auto-review><?php foreach ($allowed as $key => $value) echo '<option value="' . nadinHtml($key) . '"' . ($key === $profile ? ' selected' : '') . '>' . nadinHtml($value['label']) . '</option>'; ?></select></label>
<label>Import <select name="workflow" required data-required-message="Choose how to import this setlist." data-auto-review>
<option value=""<?php if ($workflow === '') echo ' selected'; ?>>Choose an import option</option>
<option value="first"<?php if ($workflow === 'first') echo ' selected'; ?>>First NADIN import - include PDFs</option>
<option value="update"<?php if ($workflow === 'update') echo ' selected'; ?>>Add to existing notes - use inventory CSV</option>
<option value="references"<?php if ($workflow === 'references') echo ' selected'; ?>>Setlist only - no PDFs</option>
</select></label>
<button name="action" value="review" formnovalidate data-review>Update selection</button>
</div>
<noscript><p class="postscriptum">After changing the section, import option or CSV, choose Update selection.</p></noscript>
<p class="postscriptum" data-import-help><?php
if ($workflow === 'first') echo 'All required PDFs for this section will be included. Choose this only if these NADIN notes have not been imported before. No CSV is needed.';
elseif ($workflow === 'update') echo 'A current forScore inventory lets existing PDFs and annotations be reused. Only missing PDFs are included.';
elseif ($workflow === 'references') echo 'This creates the setlist without PDF data. Use it for NADIN PDFs already in forScore.';
else echo 'Choose how to import this setlist. Section and CSV changes update the selection automatically.';
?></p>
<div class="nadin-forscore-inventory" data-inventory-panel<?php if ($workflow !== 'update') echo ' hidden'; ?>>
<label>Current forScore library CSV <input type="file" name="csv" data-required-message="Upload a current forScore library CSV." accept=".csv,.tsv,text/csv,text/tab-separated-values" data-auto-review<?php if ($workflow === 'update' && !$loaded) echo ' required'; ?>></label>
<p class="postscriptum">Export a fresh metadata CSV from forScore’s Backup panel. The CSV stays on this NADIN server for this request; it is not saved.</p>
</div>
<?php if ($loaded) echo '<p class="postscriptum">Inventory loaded' . ($inventoryName !== '' ? ': ' . nadinHtml($inventoryName) : '') . ' - ' . count($names) . ' PDFs identified. Existing filenames do not prove that local PDF contents are unchanged.</p>'; ?>
<p class="postscriptum" role="status" aria-live="polite" data-review-status hidden>Updating selection...</p>
<div class="nadin-forscore-rows">
<?php $occurrences = array_count_values($plan['entries']); foreach ($plan['rows'] as $key => $row) { ?>
<div class="nadin-forscore-row<?php if ($row['kind'] === 'heading') echo ' nadin-forscore-heading'; ?>" data-kind="<?php echo $row['kind']; ?>" data-occurrences="<?php echo $occurrences[$key]; ?>">
<?php if ($row['kind'] === 'heading') { ?>
<strong><?php echo nadinHtml($row['tune']); ?></strong>
<small>Heading — included automatically as a text placeholder.</small>
<?php } else { ?>
<span id="tune-<?php echo $key; ?>"><?php echo nadinHtml(str_replace('_', ' ', $row['tune'])); ?></span>
<?php if (count($row['options']) === 1 && $row['selected'] !== '') { $selected = $row['options'][$row['selected']]; ?>
<input type="hidden" name="choice[<?php echo $key; ?>]" value="<?php echo nadinHtml($row['selected']); ?>" data-pdf-choice data-path="<?php echo nadinHtml($selected['path']); ?>" data-known="<?php echo $selected['known'] ? '1' : '0'; ?>">
<span class="nadin-forscore-file" id="choice-<?php echo $key; ?>"><?php echo nadinHtml($selected['asset']['file']); ?></span>
<?php } elseif ($row['options']) { ?>
<select id="choice-<?php echo $key; ?>" name="choice[<?php echo $key; ?>]" data-pdf-choice required data-required-message="Choose a PDF for this tune." aria-labelledby="tune-<?php echo $key; ?>">
<option value="">Choose PDF</option>
<?php foreach ($row['options'] as $id => $option) echo '<option value="' . nadinHtml($id) . '" data-path="' . nadinHtml($option['path']) . '" data-known="' . ($option['known'] ? '1' : '0') . '" data-summary="' . nadinHtml(fsOptionSummary($option, $profiles, $mode, $workflow)) . '"' . ($id === $row['selected'] ? ' selected' : '') . '>' . nadinHtml($option['label']) . '</option>'; ?>
</select>
<?php } else { ?><span class="nadin-forscore-warning"><?php echo nadinHtml($row['reason']); ?> — placeholder required</span><?php } ?>
<?php if ($row['options']) { ?><small data-pdf-status><?php echo $row['selected'] !== '' ? nadinHtml(fsOptionSummary($row['options'][$row['selected']], $profiles, $mode, $workflow)) : nadinHtml($row['reason']); ?></small><?php } ?>
<?php } ?>
</div>
<?php } ?>
</div>
<?php if ($missingNotes) { ?>
<p><label><input type="checkbox" name="placeholders" value="1" required data-required-message="Confirm that tunes with missing notes may be kept as placeholders."<?php if ($placeholders) echo ' checked'; ?>> Keep tunes with missing or inaccessible notes as visible placeholders.</label></p>
<?php } ?>
<?php $summary = fsExportSummary($plan, $mode); ?>
<div class="nadin-forscore-summary" data-export-summary role="status" aria-live="polite">
<strong>Download summary</strong>
<p data-export-counts<?php if ($workflow === '') echo ' hidden'; ?>>Setlist entries: <span data-count="entries"><?php echo $summary['entries']; ?></span>
<span data-metric="headings"<?php if (!$summary['headings']) echo ' hidden'; ?>> · Headings: <span data-count="headings"><?php echo $summary['headings']; ?></span></span>
<span data-metric="included"<?php if (!$summary['included']) echo ' hidden'; ?>> · PDFs included: <span data-count="included"><?php echo $summary['included']; ?></span></span>
<span data-metric="reused"<?php if (!$summary['reused']) echo ' hidden'; ?>> · Existing PDFs reused: <span data-count="reused"><?php echo $summary['reused']; ?></span></span>
<span data-metric="references"<?php if (!$summary['references']) echo ' hidden'; ?>> · PDF references only: <span data-count="references"><?php echo $summary['references']; ?></span></span>
<span data-metric="missing"<?php if (!$summary['missing']) echo ' hidden'; ?>> · Missing-note entries: <span data-count="missing"><?php echo $summary['missing']; ?></span></span>
<span data-metric="unresolved"<?php if (!$summary['unresolved']) echo ' hidden'; ?>> · PDF choices remaining: <span data-count="unresolved"><?php echo $summary['unresolved']; ?></span></span></p>
<p data-export-next><?php
if ($workflow === '') echo 'Choose an import option above.';
elseif ($workflow === 'update' && !$loaded) echo 'Upload a current forScore inventory CSV above.';
elseif ($summary['unresolved']) echo 'Choose a PDF for each unresolved tune above.';
elseif ($summary['missing'] && !$placeholders) echo 'Confirm the missing-note placeholders above.';
else echo 'Ready. Download the setlist and open it in forScore.';
?></p>
</div>
<button name="action" value="download">Download forScore setlist (.4ss)</button>
<input type="hidden" name="form_complete" value="yes">
</form>
<details class="nadin-forscore-help"><summary>Import and updates</summary>
<p>Rep headings are included automatically as text placeholders, in their original order and with their original text. Only tunes with missing or inaccessible notes require placeholder confirmation.</p>
<p>Open the downloaded .4ss file in forScore. Setlists with embedded PDFs require forScore 14.3 or later; reference-only setlists require forScore 12 or later. With reference-only exports, missing PDFs appear as placeholders.</p>
<p>For later Reps, export a fresh library metadata CSV from forScore and upload it here. Only files absent from that inventory are embedded. A download is not proof of an import. Reusing an outdated or incomplete CSV can create copies.</p>
<p>A changed source PDF receives a new revision filename. If your inventory contains the previous file, that local version stays selected until you choose the current PDF. A later section PDF also does not silently replace an existing Parts file. New files do not overwrite your annotated originals.</p>
<p>All Rep entries retain their order, including repeated tunes. Each new PDF is embedded once; subsequent occurrences and other Reps reuse it. After checking an imported Rep, remove the previous setlist if forScore keeps both; removing a setlist does not remove its notes. Renaming files in forScore can prevent automatic matching.</p>
</details>
<?php } ?>
<script src="../nadinmodule/forscore.js?v=7.7" defer></script>
</body></html>

<?php
require_once('../config.inc.php');
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
$secret = hash('sha256', $salt . '|' . $uc . '|' . $pmd5c);
$csrf = hash_hmac('sha256', 'NADIN forScore export', $secret);
try {
    $post = $_SERVER['REQUEST_METHOD'] === 'POST';
    if ($post && !$_POST) throw new RuntimeException('The request is too large. Check the server upload/post limits or use a smaller CSV.');
    if ($post && fsInput($_POST, 'form_complete') !== 'yes') throw new RuntimeException('The form was truncated. Export fewer tunes or increase the server max_input_vars limit.');
    if ($post && !hash_equals($csrf, fsInput($_POST, 'csrf'))) throw new RuntimeException('The session changed. Reopen the export from NADIN.');
    $input = $post ? $_POST : $_GET;
    $module = fsInput($input, 'm', 'gigreps');
    $rep = $post ? base64_decode(fsInput($input, 'rep'), true) : fsInput($input, 'c');
    if ($rep === false) throw new RuntimeException('Invalid GigRep.');
    $profile = fsInput($input, 'profile', (string)array_key_first($allowed));
    if ($post && isset($_POST['inventory'])) {
        $names = fsInventoryState($_POST['inventory']);
        $loaded = fsInput($_POST, 'inventory_loaded') === '1';
    }
    if ($post && isset($_FILES['csv']) && $_FILES['csv']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['csv']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['csv']['tmp_name'])) throw new RuntimeException('CSV upload failed or exceeds the server upload limit.');
        if ($_FILES['csv']['size'] > 5 * 1024 * 1024) throw new RuntimeException('The CSV exceeds 5 MB.');
        $names = NadinForScore::inventory(file_get_contents($_FILES['csv']['tmp_name']));
        $loaded = true;
    }
    $choices = array();
    if ($post && fsInput($_POST, 'reviewed_profile') === $profile && isset($_POST['choice'])) {
        if (!is_array($_POST['choice']) || count($_POST['choice']) > 10000) throw new RuntimeException('Invalid PDF selection.');
        foreach ($_POST['choice'] as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/', $key) || !is_string($value) || strlen($value) > 80) throw new RuntimeException('Invalid PDF selection.');
        }
        // A fresh inventory must determine the initial suggestions again.
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] === UPLOAD_ERR_NO_FILE) $choices = $_POST['choice'];
    }
    $plan = $exporter->plan($module, $rep, $profile, $names, $choices);
    foreach ($plan['rows'] as $row) if ($row['kind'] === 'tune' && !$row['options']) $missingNotes = true;
    $signature = fsSignature($plan, $module, $rep, $profile, $names, $secret);
    $mode = fsInput($input, 'mode', 'references');
    if (!in_array($mode, array('references', 'new'), true)) throw new RuntimeException('Invalid export mode.');
    $initial = fsInput($input, 'initial') === '1';
    $placeholders = fsInput($input, 'placeholders') === '1';
    $title = ($module === 'library' ? $bb . ' — Library' : substr(nadinText($rep), 0, -4)) . ' — ' . nadinText($allowed[$profile]['label']);
    if ($post && fsInput($_POST, 'action') === 'download') {
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
require_once __DIR__.'/../nadinmodule/popup-ui.inc.php';
nadinPopupStart('forScore export', '../', 'nadin-forscore');
?>
<p><a href="<?php echo nadinHtml($back); ?>">&larr; Back to NADIN</a></p>
<?php if ($error !== '') { ?>
<p role="alert" class="nadin-forscore-error"><?php echo nadinHtml($error); ?></p>
<?php } ?>
<?php if ($plan === null) { ?><p>Return to NADIN and reopen the export to review a fresh selection.</p>
<?php } else { ?>
<p><strong><?php echo nadinHtml($title); ?></strong></p>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?php echo nadinHtml($csrf); ?>">
<input type="hidden" name="m" value="<?php echo nadinHtml($module); ?>">
<input type="hidden" name="rep" value="<?php echo nadinHtml(base64_encode($rep)); ?>">
<input type="hidden" name="reviewed_profile" value="<?php echo nadinHtml($profile); ?>">
<input type="hidden" name="inventory_loaded" value="<?php echo $loaded ? '1' : '0'; ?>">
<input type="hidden" name="inventory" value="<?php echo nadinHtml(base64_encode(json_encode($names))); ?>">
<input type="hidden" name="view" value="<?php echo nadinHtml($signature); ?>">
<p><label>Section <select name="profile"><?php foreach ($allowed as $key => $value) echo '<option value="' . nadinHtml($key) . '"' . ($key === $profile ? ' selected' : '') . '>' . nadinHtml($value['label']) . '</option>'; ?></select></label>
<label>forScore library CSV <input type="file" name="csv" accept=".csv,.tsv,text/csv,text/tab-separated-values"></label>
<button name="action" value="review">Review</button></p>
<p class="postscriptum">Select the section you play. Parts is used when that section is unavailable. Upload a fresh metadata CSV from forScore’s Backup panel to identify existing PDFs. The CSV stays on this NADIN server for this request; it is not saved.</p>
<?php if ($loaded) echo '<p>' . count($names) . ' filenames loaded. Existing filenames do not prove that local PDF contents are unchanged.</p>'; ?>
<div class="nadin-forscore-rows">
<?php foreach ($plan['rows'] as $key => $row) { ?>
<div class="nadin-forscore-row<?php if ($row['kind'] === 'heading') echo ' nadin-forscore-heading'; ?>">
<?php if ($row['kind'] === 'heading') { ?>
<strong><?php echo nadinHtml($row['tune']); ?></strong>
<small>Heading — included automatically as a text placeholder.</small>
<?php } else { ?>
<label for="choice-<?php echo $key; ?>"><?php echo nadinHtml(str_replace('_', ' ', $row['tune'])); ?></label>
<?php if ($row['options']) { ?>
<select id="choice-<?php echo $key; ?>" name="choice[<?php echo $key; ?>]">
<option value="">Choose PDF</option>
<?php foreach ($row['options'] as $id => $option) echo '<option value="' . nadinHtml($id) . '"' . ($id === $row['selected'] ? ' selected' : '') . '>' . nadinHtml($option['label']) . '</option>'; ?>
</select>
<?php } else { ?><span class="nadin-forscore-warning"><?php echo nadinHtml($row['reason']); ?> — placeholder required</span><?php } ?>
<?php if ($row['selected'] !== '') { $selected = $row['options'][$row['selected']]; ?>
<small><?php echo nadinHtml($profiles[$selected['asset']['type']]['label']); ?> · <?php echo $selected['known'] ? ($selected['old'] ? 'keep local file' : 'reuse existing file') : 'new / not in inventory'; ?></small>
<?php } elseif ($row['options']) { ?><small class="nadin-forscore-warning"><?php echo nadinHtml($row['reason']); ?></small><?php } ?>
<?php } ?>
</div>
<?php } ?>
</div>
<p><label>Export <select name="mode"><option value="references"<?php if ($mode === 'references') echo ' selected'; ?>>Setlist only — reuse PDFs</option><option value="new"<?php if ($mode === 'new') echo ' selected'; ?>>Setlist + PDFs missing from the inventory</option></select></label></p>
<p><label><input type="checkbox" name="initial" value="1"<?php if ($initial) echo ' checked'; ?>> This is the first NADIN import into this forScore library (no NADIN PDFs are already present).</label></p>
<?php if ($missingNotes) { ?>
<p><label><input type="checkbox" name="placeholders" value="1"<?php if ($placeholders) echo ' checked'; ?>> Keep tunes with missing or inaccessible notes as visible placeholders.</label></p>
<?php } ?>
<button class="nadin-primary" name="action" value="download">Download .4ss</button>
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
</body></html>

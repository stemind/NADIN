<?php
/** Encoding regressions: actual legacy bytes, not UTF-8-only sample data. */
require_once dirname(__DIR__).'/nadinmodule/encoding.inc.php';
$checks=0;
function encoding_ok($condition,$label) { global $checks;$checks++;if(!$condition)throw new RuntimeException($label); }
foreach (array('Müller, Bär, Zürich, ÄÖÜäöüß', 'Été & crème brûlée', '„Grüezi“ – 25 €', '日本語 🎵') as $text) {
    encoding_ok(nadinText($text)===$text,'UTF-8 is unchanged: '.$text);
    encoding_ok(nadinText(nadinText($text))===$text,'conversion is idempotent');
}
encoding_ok(nadinText("M\xfcller, B\xe4r, Z\xfcrich, \xc4\xd6\xdc\xe4\xf6\xfc\xdf")==='Müller, Bär, Zürich, ÄÖÜäöüß','Latin-1 umlauts');
encoding_ok(nadinText("\x84Gr\xfcezi\x93 \x96 25 \x80")==='„Grüezi“ – 25 €','Windows-1252 punctuation and euro');
encoding_ok(nadinText("Müller / Z\xfcrich / Été / B\xe4r")==='Müller / Zürich / Été / Bär','mixed legacy and UTF-8 runs');
encoding_ok(nadinText("\xef\xbb\xbfMüller")==='Müller','UTF-8 BOM is not displayed');
encoding_ok(nadinInitial('Äpfel')==='Ä' && nadinInitial("\xd6l")==='Ö','alphabet uses whole characters');
encoding_ok(nadinInitial('')==='', 'empty initial');
// Test the real wrappers without invoking authentication or touching installation data.
$functions=file_get_contents(dirname(__DIR__).'/nadinmodule/functions.inc.php');
foreach (array('htmlXspecialchars','htmlXentities','htmlX_entity_decode') as $name) {
    preg_match('/function '.$name.'\(\$that\) \{.*?\n\}/s',$functions,$match);eval($match[0]);
}
encoding_ok(htmlXspecialchars("M\xfcller & <script>\"'")==='Müller &amp; &lt;script&gt;&quot;&#039;','legacy HTML and attributes escaped safely');
encoding_ok(htmlXspecialchars('Müller')==='Müller','UTF-8 is not double encoded');
encoding_ok(htmlX_entity_decode(htmlXentities("B\xe4r & Été"))==='Bär & Été','entities round trip as UTF-8');
foreach (array("M\xfcller",'Müller','100%_&+_é','日本語') as $raw) {
    $_SERVER['SCRIPT_FILENAME']='uploadsave.php';
    $_POST=array('foldername'=>rawurlencode($raw),'_nadin_path_encoding'=>array('foldername'=>'url'));
    nadinRestorePathFields();encoding_ok($_POST['foldername']===$raw,'form preserves exact filename bytes');
}
$root=sys_get_temp_dir().'/nadin-encoding-'.bin2hex(random_bytes(6));mkdir($root);mkdir($root.'/library');
try {
    mkdir($root."/library/M\xfcller");
    encoding_ok(nadinSetlistBytes("Müller\r\nMissing_é\r\nMüller", "M\xfcller\nMissing_\xe9",$root.'/library')==="M\xfcller\r\nMissing_\xe9\r\nM\xfcller",'legacy setlist bytes, missing entries and repetitions preserved');
    mkdir($root.'/library/Müller');$rejected=false;
    try { nadinSetlistBytes('Müller','',$root.'/library'); } catch(DomainException $e) { $rejected=true; }
    encoding_ok($rejected,'ambiguous filenames are not silently exchanged');
} finally {
    foreach (glob($root.'/library/*') as $path) rmdir($path);rmdir($root.'/library');rmdir($root);
}
echo "PASS: $checks encoding checks\n";

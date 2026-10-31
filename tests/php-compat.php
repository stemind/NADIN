<?php
/** Regression fixtures captured from V15 on PHP 8.3; no deprecated calls required. */
require __DIR__.'/runtime-guard.php';
$source=dirname(__DIR__);$temp=sys_get_temp_dir().'/nadin-compat-'.bin2hex(random_bytes(8));
mkdir($temp.'/nadinmodule',0700,true);mkdir($temp.'/userdatei');
foreach (array('functions.inc.php','encoding.inc.php','auth.inc.php','members.inc.php','federation.inc.php') as $file) copy($source.'/nadinmodule/'.$file,$temp.'/nadinmodule/'.$file);
file_put_contents($temp.'/userdatei/userdatei.txt','');
$salt='fixture';$timeout=3600;$sticksessiontoip='no';$_COOKIE=$_POST=array();
$checks=0;
function compat_ok($value,$label) { global $checks; $checks++;if(!$value)throw new RuntimeException($label); }
try {
    require $temp.'/nadinmodule/functions.inc.php';
    foreach (array(
        'Mueller_SXS.pdf'=>'Mueller_SXS.pdf',
        'Müller & Söhne_SXS.pdf'=>'Mueller_u_Soehne_SXS.pdf',
        'Bär Zürich ß é Æ Œ € 🎵.mp3'=>'Baer_Zuerich_ss_e_Ae___.mp3',
        'A/B\\C?.pdf'=>'ABC.pdf',
        ''=>'',
    ) as $input=>$expected) compat_ok(mkascii($input)===$expected,'filename identity: '.$input);
    compat_ok(mkascii(null)==='','null filename has explicit empty default');
    $sub=mb_substitute_character();mb_substitute_character(0x25a1);
    compat_ok(mkascii('A🎵B.pdf')==='AB.pdf','hosting substitution cannot change filenames');
    compat_ok(mb_substitute_character()===0x25a1,'conversion restores hosting substitution');mb_substitute_character($sub);
    compat_ok(mkascii("A\xffB.pdf")==='AB.pdf','malformed UTF-8 keeps legacy safe filename');
    compat_ok(htmlXspecialchars("M\xfcller & Co")==="M\xc3\xbcller &amp; Co",'legacy single-byte HTML is converted to valid UTF-8');
    compat_ok(hms(0)==='00.00' && hms(3661)==='61.01','duration formatting');
    // Verify that the guard observes errors even when the application hides warnings.
    error_reporting(0);
    foreach (array(function(){ return @$undefinedCompatVariable; },function(){return strlen(null);},function(){ $x=array();return @$x['missing']; }) as $probe) {
        $caught=false;try{$probe();}catch(Throwable $e){$caught=true;}
        compat_ok($caught,'future-error guard cannot be bypassed by suppression');
    }
    error_reporting(E_ALL);
    require $source.'/nadinmodule/fpdf/fpdf.php';
    $pdf=new FPDF();$pdf->AddPage();$pdf->SetFont('Arial','',12);$pdf->Cell(0,10,'NADIN');
    $bytes=$pdf->Output('','S');compat_ok(strpos($bytes,'%PDF-1.3')===0 && strpos($bytes,'%%EOF')!==false,'bundled PDF produces a complete document');
    $pdf=new FPDF();$pdf->FPDF('L','mm','A4');$pdf->AddPage();$pdf->SetFont('Arial','',12);$pdf->Cell(0,10,'Legacy constructor');
    compat_ok(strpos($pdf->Output('','S'),'%PDF-')===0,'legacy explicit PDF constructor stays supported');
    echo "PASS: $checks PHP compatibility checks\n";
} finally {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file) {if($file->isDir())rmdir($file->getPathname());else unlink($file->getPathname());}
    rmdir($temp);
}

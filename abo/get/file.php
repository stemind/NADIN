<?php 
require('../../config.inc.php');
require('../../nadinmodule/functions.inc.php');
require('../../nadinmodule/calcbasicauth.inc.php');


$c=$itunespathway.str_replace('/rss/','/',str_replace('/abo/','/library/',secure(rawurldecode($_SERVER['REQUEST_URI']))));

auth('vgigs');
$deliverfile=true;
if (strpos($c,$saxes)>0) if (!allowedfor('vsaxes')) $deliverfile=false;
if (strpos($c,$bones)>0) if (!allowedfor('vbones')) $deliverfile=false;
if (strpos($c,$trumpets)>0) if (!allowedfor('vtrumpets')) $deliverfile=false;
if (strpos($c,$rhythm)>0) if (!allowedfor('vrhythm')) $deliverfile=false;
if (strpos($c,$other)>0) if (!allowedfor('vother')) $deliverfile=false;
if (strpos($c,$score)>0) if (!allowedfor('vscore')) $deliverfile=false;
if (strpos($c,$hidden)>0) if (!allowedfor('musers')) $deliverfile=false;

if (!allowedfor('vlibrary')) require('../../nadinmodule/rssisgoa.inc.php');

if ($c==str_replace('.txt','',$c)) {

if (!file_exists($c)) die('File '.$c.' not found.');
require_once('mime.inc.php');
$ext=explode('.',$c);
$ext=$ext[count($ext)-1];
header("Content-type: ".$m[$ext]); 
header("Content-Disposition:attachment;filename=".basename($c));

if ($deliverfile) header("Content-Length: " . filesize($c));
else $file = header("Content-Length: " . filesize('forbidden.pdf'));

if ($deliverfile) $file = $c;
else $file = 'forbidden.pdf';
readfile($file);
}

if ($c!=str_replace('.txt','',$c)) {

$c=$itunespathway.str_replace('/rss/','/',str_replace('/abo/','/gigreps/',secure(rawurldecode($_SERVER['REQUEST_URI']))));
$c=str_replace('.txt.pdf','.txt',$c);
if (!file_exists($c)) die('File '.$c.' not found.');
$file = file_get_contents($c);

//Baustelle
require('../../nadinmodule/fpdf/fpdf.php');

$pdf = new FPDF('L','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);

$file=explode("\n",$file);

for ($i=0;$i<count($file);$i++) {
$pdf->Cell(40,10,utf8_decode(strip_tags($file[$i])));
$pdf->ln(5);
}

$pdf->Output(basename(str_replace('.txt','.pdf',$c)),'I');

}
?>
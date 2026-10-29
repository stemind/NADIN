<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED ^ E_USER_DEPRECATED);

//IMPORTANT: NADIN emails initial passwords or single-use recovery codes. Personal passwords are never emailed. Therefore, the e-mail dispatch, triggered by PHP on your web hosting server, must function correctly. Achieving this is sometimes not easy, you need to test this thoroughly. First try it with $useheadersinphpmailcommand='no'; if the passwords are not delivered by e-mail this way, switch to 'yes' and set $mymail so that firstly this e-mail has the same domain as your NADIN URL, and secondly that this e-mail inbox actually exists in your web hosting, because certain providers require precisely this. Even stricter providers, but this should be rare, require e-mail to be sent via the SMTP server, which is somewhat complicated, please consult the file HowToSMTP.instructions.html

$useheadersinphpmailcommand='no';
$mymail='enteryour@emailaddress.here'; 


$bb='XYZ'; // Three(!)-character abbreviation of band, no spezial characters
$salt='???'; // Installation secret for existing initial passwords. Preserve on upgrades; never change an established value.

/*
MANDATORY FILE RIGHTS FOR SERVER
- The server must be enabled to write, recursively downline, all dirs and files in the library
- The server must be enabled to overwrite all files in userdatei, gigreps, gigrepsarchiv and gigrepsdeleted as well as filesdeleted and abo/zip.


MANDATORY SECURITY SETTINGS IN THE FILE SYSTEM

1. Direct web access to abo (including abo/zip) is denied. PHP download handlers
serve authorised downloads. Retain abo/zip: it stores gig attachments and ZIPs.

2. In each one of the folders library, userdatei, gigreps, gigrepsarchiv and gigrepsdeleted as well as filesdeleted, there must be an .htaccess file which generally disables visiting:

order deny,allow
deny from all
*/

$timeout=3600; //login timeout after … seconds of inactivity

$sticksessiontoip='no'; //If 'yes', a logout forced when the IP changes

$offerrcmdmp3saszip='yes'; //offer all recommended recordings for a GigReps bundled in a Zip file

$offersheetsaszip='yes'; //offer Zip file containing the sheets (one Zip per section, if sheets are prepared accordingly)

// RSS/iTunes subscriptions are retired in V11. Old related settings are ignored.

$autoclosepopups='no'; //automatically close popup windows when main window (opener) is closed

$allowmultiplepopups='yes'; //when you click the pencil symbol to manage tunes, a popup will be opened; set to $allowmultiplepopups='no' replaces this popup with the next you are going to open

// The left sidebar is now the only layout (the former switch is obsolete).
$ifleftthenthiswidth='300px'; // e. g. $ifleftthenthiswidth='300px';

//IDENTIFICATION OF PDF FILES PER REGISTER ...
$saxes='SXS';     // The name of the PDF file which contains the sax notes must contain this substring
$bones='TBS';     // The name of the PDF file which contains the trombone notes must contain this substring
$trumpets='TPS';  // The name of the PDF file which contains the trumpet notes must contain this substring
$rhythm='RTM';    // The name of the PDF file which contains the rhythm section notes must contain this substring
$other='OTR';     // The name of the PDF file which contains other notes must contain this substring
//...OR PARTS VERSUS SCORE (CAN BE MIXED)
$parts='PRTS';    // The name of the PDF file which contains the instrumental parts must contain this substring
$score='SCRE';    // The name of the PDF file which contains the conducter’s score must contain this substring
//...OR SPECIAL FILES, E.G. FROM SCORE SOFTWARE
$hidden='HDDN';   // Files with this substring are only visible to users with the right musers under $humanfilesandlinks


//OUTPUT OF LINKS FOR USERS
$humansaxes='sx';
$humanbones='tb';
$humantrumpets='tp';
$humanrhythm='rh';
$humanother='ot';

$humanparts='Parts';
$humanscore='Score';

$humantherec='Recording';
$humanfilesandlinks='more';
$humaninfos='Info';

//OUTPUT OF MENU ITEMS IN THE MAIN NAVIGATION FOR THE USERS
$aktuellegigs='Current GigReps';
$archiviertegigs='Archived GigReps';
$bbaufnahmen=$bb.'-Recordings';
$bibliothek='Score & Media Library';


//IDENTIFICATION OF RECORDINGS
$recommended='RCMD'; // The name of the recording recommended for listening must contain this substring

//SYSTEM PARAMETER
$delimiter='|'; // The character defined here must not be used in files because the parser uses it for itself
$delimiter2='* - * - * - * * * - * - * - *'; // // This string indicates the changes of paragraph
$nadin='http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; //Calculation of internet address pointing to itself. If SSL is used, change the protocol to https://

//The external links you'll find in the top right corner of NADIN's yellowish info box may be configured in configlinks.inc.php
?>
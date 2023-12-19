<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

$bb='XYZ'; // Three(!)-character abbreviation of band, no spezial characters
$salt='???'; // Encryption add-on of passwords, random 3-character string except ??? If changed, all passwords need to be ordered again.

$mymail='enteryour@emailaddress.here'; 

/*
MANDATORY FILE RIGHTS FOR SERVER
- The server must be enabled to write, recursively downline, all dirs and files in the library
- The server must be enabled to overwrite all files in userdatei, gigreps, gigrepsarchiv and gigrepsdeleted as well as filesdeleted and abo/zip.


MANDATORY SECURITY SETTINGS IN THE FILE SYSTEM

1. In the folder abo there must be an .htaccess file requiring authentication according to ../../userdatei.htpasswd. This file contains the mod_rewrite rules which are to be enabled ONLY IF PHP runs as CGI instead of as a module (about CGI, also see $autoresolvefastcgiproblems near the bottom of config.inc.php). Enabling means deleting the pound signs (#) EXCEPT IN #http://www.besthostratings.com/articles/http-auth-php-cgi.html

 AuthName "Your NADIN login please"
 AuthType Basic
 AuthUserFile "/var/webs/www.sophisware.ch/hostingwebs/bigbandweb/abb/nadin/userdatei/.htpasswd"
 Require valid-user
 #http://www.besthostratings.com/articles/http-auth-php-cgi.html
 #RewriteEngine on
 #RewriteCond %{HTTP:Authorization} !^$
 #RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]

In the abo/.htaccess file shown above, you need to adapt the AuthUserFile path to your server!
Initially, it is stated there
AuthUserFile "/var/webs/www.sophisware.ch/hostingwebs/bigbandweb/abb/nadin/userdatei/.htpasswd"
Which is incorrect for your installation. Find the correct path by calling http://...nadin/server.php and consult ["SCRIPT_FILENAME"]. If you saw there, for example,
string(60) "/srv/www/studmed.unibe.ch/public_html/nadin/server.php"
the correct path would be
AuthUserFile "/srv/www/studmed.unibe.ch/public_html/nadin/userdatei/.htpasswd"



2. In each one of the folders library, userdatei, gigreps, gigrepsarchiv and gigrepsdeleted as well as filesdeleted, there must be an .htaccess file which generally disables visiting:

order deny,allow
deny from all
*/

$timeout=3600; //login timeout after … seconds of inactivity

$sticksessiontoip='no'; //If 'yes', a logout forced when the IP changes

$offerrcmdmp3saszip='yes'; //offer all recommended recordings for a GigReps bundled in a Zip file

$offersheetsaszip='yes'; //offer Zip file containing the sheets (one Zip per section, if sheets are prepared accordingly)

$offeritunesabo='yes'; //set to 'no' if these don’t work properly

$autoclosepopups='no'; //automatically close popup windows when main window (opener) is closed

$allowmultiplepopups='yes'; //when you click the pencil symbol to manage tunes, a popup will be opened; set to $allowmultiplepopups='no' replaces this popup with the next you are going to open

$rendergiglistontheleft='yes';
$ifleftthenthiswidth='300px'; // e. g. $ifleftthenthiswidth='300px';

/*
CONDITIONS FOR WORKING iTUNES SUBSCRIPTIONS

1. http:/...nadin/abo/get/ must be accessible in a way that the Basic Auth login dialog box is displayed and the username appears in green writing on the target page.
   To enable that, the file userdatei/.htpasswd must contain the appropriate passwords. Do NOT write manually into this file! In the backend, click the button
   manage Users and save there by clicking the s-button; now the file is generated. Attention:  If you are adding new users and - in the same session in the form  -
   are deleting or overwriting the user  with which you are currently logged-in, you can save the form but .htpasswd won't be updated. In that case, log out and
   log in again and then go to the users again and save them again!

2. Point 1 is achieved if in abo there is an .htaccess file customized to find the corresponding ../../userdatei/.htpasswd (see line 23 above).
   IF on your provider’s server PHP runs as CGI instead of as a module, which is rare, point 1 doesn’t work yet. In this case, open the config.inc.php at hand and
   set $autoresolvefastcgiproblems near the bottom to ‘yes’ and in abo/.htaccess delete all pound signs (#) except in the line #http://www.besthostratings.com/articles/http-auth-php-cgi.html.

3. If point 1 works, Apache‘s mod_rewrite engine needs to be checked (is it installed, is it running, is it enabled by the provider?)
   a) visit http://...nadin/abo/rwtest/ohnerw/ - "OK, page found" is displayed.
   b) visit http://...nadin/abo/rwtest/mitrw/ - "OK, page found" should be displayed, too. If not, contact your provider and switch on / enable mod_rewrite.

   iTunes and other Podcatchers get the  NADIN files via the path abo/rss/xyfilename, not directly via abo/get/file.php?c=xyfilename. That is because iTunes can’t handle URLs
   that contain Get parameters such as ?c=...;  So, accordingly, mod_rewrite renames abo/rss/xyfilename to abo/get/file.php?c=xyfilename.

4. After completing points 1 to 3, visit http://...nadin/abo/. An XML File is rendered. Extract from it a URL to a PDF and visit it.
   Keep adapting the itunespathway given below until the PDF is accessible by the URL copied from the XML.
   Some examples;
   http://nadin.xy.ch                needs $itunespathway='../../'
   http://(www.)xy.ch/nadin/         needs $itunespathway='../../../'
   http://(www.)xy.ch/somedir/nadin/  needs $itunespathway='../../../../'
*/

$itunespathway='../../../';


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

// IF on your provider’s server PHP runs as CGI instead of as a module, which is rare, open config.inc.php and set $autoresolvefastcgiproblems near the bottom to ‘yes’ and in abo/.htaccess delete all pound signs (#) except in the line #http://www.besthostratings.com/articles/http-auth-php-cgi.html.
$autoresolvefastcgiproblems='no';  
$basicauthvarname='HTTP_AUTHORIZATION'; // Some servers want $basicauthvarname='REDIRECT_HTTP_AUTHORIZATION'; here, consult https://urltoyournadininstallation/server.php

//The external links you'll find in the top right corner of NADIN's yellowish info box may be configured in configlinks.inc.php
?>
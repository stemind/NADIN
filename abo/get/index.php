<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>ABO TEST</title>
</head>
<body>
<?php 
require('../../config.inc.php');
require('../../nadinmodule/functions.inc.php');
echo 'Usernname for iTunes=<font color=green>'.$_SERVER['PHP_AUTH_USER'].'</font>&lt;&lt;&lt;here has to be the username. If not, you have to change nearly at the end in config.inc.php the variable $autoresolvefastcgiproblems to \'yes\' and then further you have to remove all hash signs (#) in abo/.htaccess except the one hash sign on the line http://www.besthostratings.com/articles/http-auth-php-cgi.html';
?>
</body>
</html>

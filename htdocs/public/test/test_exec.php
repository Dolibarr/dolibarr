<?php
if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOSTYLECHECK')) {
	define('NOSTYLECHECK', '1'); // Do not check style html tag into posted data
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1'); // Do not check anti CSRF attack test
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Do not check anti POST attack test
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1'); // Do not load ajax.lib.php library
}
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1'); // If this page is public (can be called outside logged session)
}
// If you don't need session management (can't be logged if no session used). You must also set
// NOCSRFCHECK, NOTOKENRENEWAL, NOLOGIN
// Disable module with GETPOST('disablemodules') won't work. Variable 'dol_...' will not be set.
// $_SESSION are then simple vars if sessions are not active.
// TODO We can close session with session_write_close() as soon as we just need read access everywhere in code.
if (!defined("NOSESSION")) {
	define("NOSESSION", '1');
}

print "Legend:<br>\n";
print 'PHP_SESSION_DISABLED='.PHP_SESSION_DISABLED."<br>\n";
print 'PHP_SESSION_NONE='.PHP_SESSION_NONE."<br>\n";
print 'PHP_SESSION_ACTIVE='.PHP_SESSION_ACTIVE."<br>\n";
print '<br>';

print 'session_status='.session_status().' (before main.inc.php)';
print '<br>';

require '../../main.inc.php';

// Security
if ($dolibarr_main_prod) {
	accessforbidden('Access forbidden when $dolibarr_main_prod is set to 1');
}


/*
 * View
 */

echo "Test<br>\n";
$out='';
$ret=0;

$file = '/tmp/test.txt';
$f=fopen($file, 'r');
if ($f) {
	$s=fread($f, 4096);
	print $s;
	fclose($f);
} else {
	print "Failed to open file ".$file."<br>\n";
}

print '<br><br>'."\n";

exec('cat /test.txt; ls /dev/std*; sleep 1;', $out, $ret);
print $ret."<br>\n";
print_r($out);

print '<br><br>'."\n";

$ret = 0;
$out = null;
exec('/usr/bin/clamdscan --fdpass filethatdoesnotexists.php', $out, $ret);
print $ret."<br>\n";
print_r($out);

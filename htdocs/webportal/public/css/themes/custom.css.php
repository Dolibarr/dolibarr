<?php

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login.
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

// require_once __DIR__.'/../main.inc.php';
// require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Define css type
// top_httphead('text/css');
header("Content-Type: text/css");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
// if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
/* } else {
	header('Cache-Control: no-cache');
} */


print '/* Here, the content of the common custom CSS defined into Home - Setup - Display - CSS'."*/\n";
print getDolGlobalString('WEBPORTAL_CUSTOM_CSS');


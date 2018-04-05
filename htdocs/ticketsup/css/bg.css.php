<?php

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC')) {
    define('NOREQUIRESOC', '1');
}
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK')) {
    define('NOCSRFCHECK', 1);
}
if (! defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (! defined('NOLOGIN')) {
    define('NOLOGIN', 1);          // File must be accessed by logon page so without login
}if (! defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (! defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (! defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}

session_cache_limiter(false);

require_once '../../../main.inc.php';

// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined)
if (empty($user->id) && ! empty($_SESSION['dol_login'])) {
    $user->fetch('', $_SESSION['dol_login']);
}


// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) {
    header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
    header('Cache-Control: no-cache');
}

// On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) {
    ob_start("ob_gzhandler");
}


print 'html {';
if (! empty($conf->global->TICKETS_SHOW_MODULE_LOGO)) {
    print 'background: url("../public/img/bg_ticket.png") no-repeat 95% 90%;';
}
print '}';

<?php
/*
 * Copyright (C) ---Replace with your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification


// Load Dolibarr environment
require '../../../../../../main.inc.php';


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */


/**
 * INTERFACE FOR JS CONTEXT LANG
 */

if (empty($dolibarr_nocache)) {
	$delaycache = '86400';
	header('Cache-Control: max-age=' . $delaycache . ', public, must-revalidate');
	header('Pragma: cache'); // This is to avoid to have Pragma: no-cache set by proxy or web server
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (int) $delaycache) . ' GMT');    // This is to avoid to have Expires set by proxy or web server
} else {
	// If any cache on files were disable by config file (for test purpose)
	header('Cache-Control: no-cache');
}
header('Content-Type: application/json; charset=utf-8');


$local = GETPOST('local');
if (empty($local)) {
	$local = $langs->getDefaultLang();
}

$domain = GETPOST('domain');
if (empty($domain)) {
	echo json_encode(['error' => 'Missing domain']);
	exit;
}

if (!preg_match('/^[A-Za-z0-9_-]+(?:@[A-Za-z0-9_-]+)?$/', $domain)) {
	echo json_encode(['error' => 'Invalid domain']);
	exit;
}


if (!preg_match('/^[a-z]{1,2}_[A-Z]{1,2}(?:,[a-z]{1,2}_[A-Z]{1,2})*$/', $local)) {
	echo json_encode(['error' => 'Invalid langs codes']);
	exit;
}

/*
Format for  JS:
{
  fr_FR : {KEY:"TEXT", ...},
  en_US : {KEY:"TEXT", ...}
}
*/

$locals = explode(',', $local);
$json = new stdClass();

foreach ($locals as $langCode) {
	$json->$langCode = [];
	$outputlangs = new Translate("", $conf);
	$outputlangs->setDefaultLang($langCode);
	$outputlangs->load($domain);
	foreach ($outputlangs->tab_translate as $k => $v) {
		$json->$langCode[$k] = dolPrintHTML($v); // to escape js and other stuff
	}
}

print json_encode($json, JSON_PRETTY_PRINT);

<?php
/* Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

 /**
 * \file    htdocs/modulebuilder/template/js/mymodule.js.php
 * \ingroup mymodule
 * \brief   JavaScript file for module MyModule.
 */

$defines = array(
	'NOREQUIREDB',				// Do not create database handler $db
	'NOREQUIREUSER',				// Do not load object $user
	'NOREQUIRESOC',				// Do not load object $mysoc
	'NOREQUIRETRAN',				// Do not load object $langs
	// 'NOSCANGETFORINJECTION',		// Do not check injection attack on GET parameters
	// 'NOSCANPOSTFORINJECTION',	// Do not check injection attack on POST parameters
	'NOCSRFCHECK',				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
	'NOTOKENRENEWAL',			// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
	// 'NOSTYLECHECK',				// Do not check style html tag into posted data
	'NOREQUIREMENU',				// If there is no need to load and show top and left menu
	'NOREQUIREHTML',				// If we don't need to load the html.form.class.php
	'NOREQUIREAJAX',				// Do not load ajax.lib.php library
	"NOLOGIN",					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
	// 'NOIPCHECK',					// Do not check IP defined into conf $dolibarr_main_restrict_ip
	// 'NOREDIRECTBYMAINTOLOGIN',	// The main.inc.php does not make a redirect if not logged, instead show simple error message
	// 'CSRFCHECK_WITH_TOKEN',		// Force use of CSRF protection with tokens even for GET
	// 'NOBROWSERNOTIF',			// Disable browser notification
);

//if (! defined("MAIN_LANG_DEFAULT")) {
//	define('MAIN_LANG_DEFAULT', 'auto');	// Force lang to a particular value
//}
//if (! defined("MAIN_AUTHENTICATION_MODE")) {
//	define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//}
//if (! defined("FORCECSP")) {
//	define('FORCECSP', 'none');				// Disable all Content Security Policies
//}

// Load Dolibarr environment
include './config.php';

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>

/* Javascript library of module MyModule */



<?php
/* Copyright (C) 2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Noé Cendrier         <noe.cendrier@altairis.fr>
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

/**
 *       \file       htdocs/mymodule/ajax/myobject.php
 *       \brief      File to return Ajax response on product list request
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php in all parent folders (cope with symlinks)
if (!$res) {
	$dolipath = dirname($_SERVER['SCRIPT_FILENAME']);
	while (!file_exists($dolipath."/main.inc.php")) {
		$abspath = $dolipath;
		$dolipath = dirname($dolipath);
		if ($abspath == $dolipath) { // cope with no main.inc.php all the way to filesystem root
			break;
		}
	}
	$res = @include($dolipath."/main.inc.php");
}
if (!$res) {
	die("Include of main fails");
}

$mode = GETPOST('mode', 'aZ09');

// Security check
restrictedArea($user, 'mymodule', 0, 'myobject');


/*
 * View
 */

dol_syslog("Call ajax mymodule/ajax/myobject.php");

top_httphead('application/json');

$arrayresult = array();

// ....

$db->close();

print json_encode($arrayresult);

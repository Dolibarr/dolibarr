<?php
/* Copyright (C) 2011-2015 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *       \file       htdocs/core/ajax/security.php
 *       \brief      This ajax component is used to generated hash keys for security purposes,
 *                   like the key to use into URL to protect them.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
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
// We need langs because the getRandomPassword may use the user language to define some rules of pass generation
/*if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}*/

// Load Dolibarr environment and check user is logged.
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action');

// Security check
// None. This is public component with no access and effect on data.


/*
 * Action
 */

// None


/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Return a new generated password
if ($action) {
	if ($action == 'getrandompassword') {	// Test on permission not required here. Endpoint can be called by anu logged user.
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
		$generic = GETPOST('generic') ? true : false;
		echo getRandomPassword($generic);
	}
} else {
	if (GETPOST('errorcode') == 'InvalidToken') {
		http_response_code(401);
	}
}

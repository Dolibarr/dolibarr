<?php
/*
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
 *       \file       htdocs/core/ajax/changepositionfields.php
 *       \brief      File for we change position of fields on a list page
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
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1'); // Token is required even in GET mode
}

// Load Dolibarr environment
require '../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09'); // set or del
$contextpage = GETPOST('contextpage');
$postitionfields = GETPOST("positionfields", "array");
$userid = GETPOSTINT('userid');

// Security check
if ($userid != $user->id) {
	httponly_accessforbidden('Bad userid parameter. Must match logged user.');
}


/*
 * Actions
 */

// Registering the new value of constant
if (!empty($action) && !empty($contextpage)) {
	if ($action == "listafterchangingpositionfields") { // Test on permission not required here. Done in security check
		dol_syslog("Ajax changepositionfields contextpage=".$contextpage." postitionfields=".$postitionfields." userid=".$userid, LOG_DEBUG);
		$tabparam = array();
		if (!empty($postitionfields)) {
			$position = "";
			foreach ($postitionfields as $pos => $field) {
				$position .= ($pos != 0 ? "," : "").$field.":".$pos;
			}
			$tabparam["MAIN_POSITIONFIELDS_".$contextpage] = $position;
		} else {
			$tabparam["MAIN_POSITIONFIELDS_".$contextpage] = '';
		}
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$result = dol_set_user_param($db, $conf, $user, $tabparam);
	}
} else {
	httponly_accessforbidden('Param action and contextpage are required', 403);
}


/*
 * View
 */

top_httphead();

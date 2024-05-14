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
 *	\file       /htdocs/webhook/ajax/webhook.php
 *	\brief      File to make Ajax action on webhook
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
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
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
// If we need access without being logged.
if (!empty($_GET['public'])) {	// Keep $_GET here. GETPOST() is not yet defined so we use $_GET
	if (!defined("NOLOGIN")) {
		define("NOLOGIN", '1');
	}
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
include '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/webhook/class/target.class.php';

$action = GETPOST('action', 'aZ09');
$triggercode = GETPOST('triggercode');

// Security check
if (!defined("NOLOGIN")) {	// No need of restrictedArea if not logged: Later the select will filter on public articles only if not logged.
	restrictedArea($user, 'webhook', 0, '', 'webhook_target');
}


/*
 * Actions
 */

// None


/*
 * View
 */

top_httphead('application/json');

if ($action == "getjsonformtrigger") {
	$response = '';
	$objnotfound = 0;
	$json = new stdClass();
	if (!empty($triggercode)) {
		// Clean triggercode to removes keep only Object trigger name
		$objecttriggername = array();
		preg_match('#\((.*?)\)#', $triggercode, $objecttriggername);
		$json->triggercode = !empty($objecttriggername[1]) ? $objecttriggername[1] : $triggercode;
		if ($objecttriggername[1]) {
			$objtype = explode("_", $objecttriggername[1])[0];
			$obj = findobjecttosend($objtype);
			if (is_object($obj)) {
				//TODO: Case if obj is an object
			} else {
				$objnotfound ++;
			}
		} else {
			$objnotfound ++;
		}

		if ($objnotfound) {
			$json->object = new Target($db);
			$json->object->initAsSpecimen();
			unset($json->object->db);
			unset($json->object->fields);
			unset($json->object->error);
			unset($json->object->errors);
		}
	}

	$response = json_encode($json);
	echo $response;
}

/**
 * Find and init a specimen for the given object type
 *
 * @param 	string      $objecttype		Object type to init as a specimen
 * @return object|false
 */
function findobjecttosend($objecttype)
{
	// TODO: Find right object from objecttype and initAsSpecimen
	return false;
}

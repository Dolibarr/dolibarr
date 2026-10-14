<?php
/* Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');
$triggercode = GETPOST('triggercode');

// Security check
if (empty($user->admin)) {
	accessforbidden();
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

		$json->triggercode = empty($objecttriggername[1]) ? $triggercode : $objecttriggername[1];

		if (!empty($json->triggercode)) {
			$objtype = explode("_", $json->triggercode)[0];
			$obj = findobjecttosend($objtype);
			if (is_object($obj)) {
				dol_syslog("Ajax webhook: We clean object fetched");
				$properties = dol_get_object_properties($obj);
				foreach ($properties as $key => $property) {
					if (empty($property)) {
						unset($obj->$key);
					}
				}
				unset($obj->db);
				unset($obj->fields);
				unset($obj->table_element);
				unset($obj->picto);
				unset($obj->isextrafieldmanaged);
				unset($obj->ismultientitymanaged);

				$json->object = $obj;
			} else {
				$objnotfound++;
			}
		} else {
			$objnotfound++;
		}

		if ($objnotfound) {
			dol_syslog("Ajax webhook: Class not found for trigger code ".$json->triggercode);
			$json->object = new stdClass();
			$json->object->field1 = 'field1';
			$json->object->field2 = 'field2';
			$json->object->field3 = 'field3';
		}
	}

	$response = json_encode($json);
	echo $response;
}

/**
 * Find and init a specimen for the given object type
 *
 * @param 	string      $objecttype		Object type to init as a specimen
 * @return CommonObject|false
 */
function findobjecttosend($objecttype)
{
	dol_syslog("Ajax webhook: We fetch object of type = ".$objecttype." and we init it as specimen");
	$obj = fetchObjectByElement(0, dol_strtolower($objecttype));
	if (is_object($obj)) {
		'@phan-var-force CommonObject $obj';
		$obj->initAsSpecimen();
	} else {
		return false;
	}
	return $obj;
}

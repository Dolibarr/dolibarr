<?php
/* Copyright (C) 2026 direct copy from htdocs/societe/ajax/ajaxcompanies.php
 * Copyright (C) 2026		Jon Bendtsen          		<jon.bendtsen.github@jonb.dk>
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
 *       \file       htdocs/adherents/ajax/ajaxmembers.php
 *       \brief      File to return Ajax response on memberss request. Search is done on firstname|lastname
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */


$id = GETPOSTINT('memberid');

$object = new Adherent($db);
if ($id > 0) {
	$object->fetch($id);
}

// Security check
restrictedArea($user, 'member', $object, '&member');


/*
 * View
 */

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead('application/json');

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";


$return_arr = array();

// Define filter on text typed
$memberid = GETPOST('newmember');
if (!$memberid) {
	$memberid = GETPOSTINT('memberid');
}

// Generate list of companies
if (! $memberid) {
	echo json_encode(array('nom' => 'ErrorBadParameter', 'label' => 'ErrorBadParameter', 'key' => 'ErrorBadParameter', 'value' => 'ErrorBadParameter'));
	exit;
}

$sql = "SELECT a.rowid, a.firstname, a.lastname,  a.address, a.zip, a.town, a.email, a.datec, a.photo";
if (getDolGlobalString('MEMBER_SHOW_ADDRESS_SELECTLIST')) {
	$sql .= ", dictp.code as country_code";
}
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as a";
if (getDolGlobalString('MEMBER_SHOW_ADDRESS_SELECTLIST')) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as dictp ON dictp.rowid = a.country";
}
// Filter on active members only (statut = 1) Closed members must not be selectable
$sql .= " WHERE a.entity IN (".getEntity('member').")  AND a.statut = 1";
if ($memberid > 1) {
	$sql .= " AND (";
	// Add criteria on name/code
	if (getDolGlobalString('MEMBER_DONOTSEARCH_ANYWHERE')) {   // Can use index
		$sql .= "a.firstname LIKE '".$db->escape($db->escapeforlike($memberid))."%'";
		$sql .= " OR a.lastname LIKE '".$db->escape($db->escapeforlike($memberid))."%'";
	} else {
		$sql .= "a.firstname LIKE '%".$db->escape($db->escapeforlike($memberid))."%'";
		$sql .= " OR a.lastname LIKE '%".$db->escape($db->escapeforlike($memberid))."%'";
	}
	if (getDolGlobalString('MEMBER_ALLOW_SEARCH_ON_ROWID')) {
		$sql .= " OR a.rowid = ".((int) $memberid);
	}
	$sql .= ")";
}

// Protection for external user access
if ($user->socid > 0) {
	$sql .= " AND a.fk_soc = ".((int) $user->socid);
}
//if (GETPOST("filter")) $sql.= " AND (".GETPOST("filter", "alpha").")"; // Add other filters

$limit = getDolGlobalInt('SEARCH_LIMIT_AJAX') ?: 1000;		// SEARCH_LIMIT_AJAX is a hidden option that has priority on option THIRDPARTY_LIMIT_SIZE if set.
$sql .= $db->plimit($limit, 0);

$sql .= " ORDER BY a.firstname ASC";

//dol_syslog("ajaxmembers", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	while ($row = $db->fetch_array($resql)) {
		$label = $row['firstname']." ".$row['lastname'];

		if (getDolGlobalString('MEMBER_SHOW_ADDRESS_SELECTLIST')) {
			$label .= ($row['address'] ? ' - '.$row['address'] : '').($row['zip'] ? ' - '.$row['zip'] : '').($row['town'] ? ' '.$row['town'] : '');
			if (!empty($row['country_code'])) {
				$label .= ', '.$langs->trans('Country'.$row['country_code']);
			}
		}

		$label = preg_replace('/('.preg_quote($memberid, '/').')/i', '<strong>$1</strong>', $label, 1);
		$row_array = array();
		$row_array['label'] = $label;

		$row_array['value'] = $label;
		$row_array['key'] = $row['rowid'];

		$row_array['address'] = $row['address'];
		$row_array['zip'] = $row['zip'];
		$row_array['town'] = $row['town'];
		$row_array['email'] = $row['email'];
		$row_array['datec'] = $row['datec'];
		$row_array['photo'] = $row['photo'];

		array_push($return_arr, $row_array);
	}

	echo json_encode($return_arr);
} else {
	echo json_encode(array('nom' => 'Error', 'label' => 'Error', 'key' => 'Error', 'value' => 'Error'));
}

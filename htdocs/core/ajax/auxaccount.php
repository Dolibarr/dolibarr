<?php
/* Copyright (C) 2026      Alexandre Spangaro    <alexandre@inovea-conseil.com>
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
 * \file        htdocs/core/ajax/auxaccount.php
 * \ingroup     Accountancy (Double entries)
 * \brief       Ajax endpoint to search auxiliary accounts (thirdparties + users)
 *              Called by select_auxaccount() when ACCOUNTANCY_AUXACCOUNT_USE_SEARCH_TO_SELECT >= 2
 */

// Minimum ajax defines
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
if (!defined('NOHEADERNOFOOTER')) {
	define('NOHEADERNOFOOTER', '1');
}

require '../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// Security — must be logged in
if (!$user->id) {
	accessforbidden();
}

// Security
if (!isModEnabled('accounting') || !$user->hasRight('accounting', 'mouvements', 'lire')) {
	accessforbidden();
}

$htmlname  = GETPOST('htmlname', 'aZ09') ?: 'account_num_aux';
$searchkey = GETPOST(str_replace('.', '_', $htmlname), 'alpha');
$outjson   = GETPOSTINT('outjson');
$limit     = getDolGlobalInt('ACCOUNTANCY_AUXACCOUNT_SEARCH_LIMIT', 100);
$minLength = getDolGlobalInt('ACCOUNTANCY_AUXACCOUNT_USE_SEARCH_TO_SELECT', 2) - 1;

$results = array();
/**
 * @var array<string,mixed> $results
 */

if (strlen($searchkey) >= $minLength) {
	// Search anywhere by default (use LIKE '%term%')
	$prefix = getDolGlobalString('ACCOUNTANCY_AUXACCOUNT_DONOTSEARCH_ANYWHERE') ? '' : '%';

	// --- Thirdparties (customers and/or suppliers) ---
	$sql = "SELECT code_compta AS code_client, code_compta_fournisseur AS code_fourn, nom AS name";
	$sql .= " FROM ".$db->prefix()."societe";
	$sql .= " WHERE entity IN (".getEntity('societe').")";
	$sql .= " AND (client IN (1,3) OR fournisseur = 1)";
	$sql .= " AND (code_compta LIKE '".$db->escape($prefix.$searchkey)."%'";
	$sql .= "   OR code_compta_fournisseur LIKE '".$db->escape($prefix.$searchkey)."%'";
	$sql .= "   OR nom LIKE '".$db->escape($prefix.$searchkey)."%')";
	$sql .= $db->order("nom", "ASC");
	$sql .= $db->plimit($limit, 0);

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			if (!empty($obj->code_client)) {
				$key = $obj->code_client;
				$results[$key] = array(
					'key'           => $key,
					'value'         => $key.' ('.$obj->name.')',      // value shown in input once selected
					'label'         => $key.' ('.$obj->name.')',      // label shown in dropdown list
					'label_name'    => $obj->name,
				);
			}
			if (!empty($obj->code_fourn) && !isset($results[$obj->code_fourn])) {
				$key = $obj->code_fourn;
				$results[$key] = array(
					'key'           => $key,
					'value'         => $key.' ('.$obj->name.')',
					'label'         => $key.' ('.$obj->name.')',
					'label_name'    => $obj->name,
				);
			}
		}
		$db->free($resql);
	} else {
		dol_syslog("auxaccount.php: error societe query ".$db->lasterror(), LOG_ERR);
	}

	// --- Users ---
	$sql2 = "SELECT DISTINCT accountancy_code, lastname, firstname";
	$sql2 .= " FROM ".$db->prefix()."user";
	$sql2 .= " WHERE entity IN (".getEntity('user').")";
	$sql2 .= " AND accountancy_code != ''";
	$sql2 .= " AND (accountancy_code LIKE '".$db->escape($prefix.$searchkey)."%'";
	$sql2 .= "   OR lastname LIKE '".$db->escape($prefix.$searchkey)."%'";
	$sql2 .= "   OR firstname LIKE '".$db->escape($prefix.$searchkey)."%')";
	$sql2 .= $db->order("accountancy_code", "ASC");
	$sql2 .= $db->plimit($limit, 0);

	$resql2 = $db->query($sql2);
	if ($resql2) {
		while ($obj = $db->fetch_object($resql2)) {
			if (!empty($obj->accountancy_code) && !isset($results[$obj->accountancy_code])) {
				$key = $obj->accountancy_code;
				$fullname = dolGetFirstLastname($obj->firstname, $obj->lastname);
				$results[$key] = array(
					'key'           => $key,
					'value'         => $key.' ('.$fullname.')',
					'label'         => $key.' ('.$fullname.')',
					'label_name'    => $fullname,
				);
			}
		}
		$db->free($resql2);
	} else {
		dol_syslog("auxaccount.php: error user query ".$db->lasterror(), LOG_ERR);
	}

	ksort($results);
}

// Output JSON — format expected by ajax_autocompleter(): array of {key, value, label}
top_httphead('application/json');
echo json_encode(array_values($results));

$db->close();

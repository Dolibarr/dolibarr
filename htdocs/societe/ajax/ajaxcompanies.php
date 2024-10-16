<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Cyrille de Lambert   <info@auguria.net>
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
 *       \file       htdocs/societe/ajax/ajaxcompanies.php
 *       \brief      File to return Ajax response on third parties request. Search is done on name|name_alias|code_client|code_fournisseur
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
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$id = GETPOSTINT('socid');
if ($id == 0) {
	$id = GETPOSTINT('id_fourn');
}

$object = new Societe($db);
if ($id > 0) {
	$object->fetch($id);
}

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
	if ($object->socid && $socid != $object->socid) {
		accessforbidden('Not allowed to access thirdparty id '.$id.' with an external user on id '.$socid);
	}
}
restrictedArea($user, 'societe', $object, '&societe');


/*
 * View
 */

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead('application/json');

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";


$return_arr = array();

// Define filter on text typed
$socid = GETPOST('newcompany');
if (!$socid) {
	$socid = GETPOSTINT('socid');
}
if (!$socid) {
	$socid = GETPOSTINT('id_fourn');
}

// Generate list of companies
if (! $socid) {
	echo json_encode(array('nom' => 'ErrorBadParameter', 'label' => 'ErrorBadParameter', 'key' => 'ErrorBadParameter', 'value' => 'ErrorBadParameter'));
	exit;
}

$sql = "SELECT s.rowid, s.nom, s.name_alias, s.code_client, s.code_fournisseur, s.address, s.zip, s.town, s.email, s.siren, s.siret, s.ape, s.idprof4, s.idprof5, s.idprof6, s.client, s.fournisseur, s.datec, s.logo";
if (getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST')) {
	$sql .= ", dictp.code as country_code";
}
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
if (getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST')) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as dictp ON dictp.rowid = s.fk_pays";
}
$sql .= " WHERE s.entity IN (".getEntity('societe').")";
if ($socid) {
	$sql .= " AND (";
	// Add criteria on name/code
	if (getDolGlobalString('COMPANY_DONOTSEARCH_ANYWHERE')) {   // Can use index
		$sql .= "s.nom LIKE '".$db->escape($db->escapeforlike($socid))."%'";
		$sql .= " OR s.name_alias LIKE '".$db->escape($db->escapeforlike($socid))."%'";
		$sql .= " OR s.code_client LIKE '".$db->escape($db->escapeforlike($socid))."%'";
		$sql .= " OR s.code_fournisseur LIKE '".$db->escape($db->escapeforlike($socid))."%'";
	} else {
		$sql .= "s.nom LIKE '%".$db->escape($db->escapeforlike($socid))."%'";
		$sql .= " OR s.name_alias LIKE '%".$db->escape($db->escapeforlike($socid))."%'";
		$sql .= " OR s.code_client LIKE '%".$db->escape($db->escapeforlike($socid))."%'";
		$sql .= " OR s.code_fournisseur LIKE '%".$db->escape($db->escapeforlike($socid))."%'";
	}
	if (getDolGlobalString('SOCIETE_ALLOW_SEARCH_ON_ROWID')) {
		$sql .= " OR s.rowid = ".((int) $socid);
	}
	$sql .= ")";
}
// Protection for external user access
if ($user->socid > 0) {
	$sql .= " AND s.rowid = ".((int) $user->socid);
}
//if (GETPOST("filter")) $sql.= " AND (".GETPOST("filter", "alpha").")"; // Add other filters
$sql .= " ORDER BY s.nom ASC";

//dol_syslog("ajaxcompanies", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	while ($row = $db->fetch_array($resql)) {
		$label = '';
		if (getDolGlobalString('SOCIETE_ADD_REF_IN_LIST')) {
			if (($row['client']) && (!empty($row['code_client']))) {
				$label = $row['code_client'].' - ';
			}
			if (($row['fournisseur']) && (!empty($row['code_fournisseur']))) {
				$label .= $row['code_fournisseur'].' - ';
			}
		}

		$label .= $row['nom'];

		if (getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST')) {
			$label .= ($row['address'] ? ' - '.$row['address'] : '').($row['zip'] ? ' - '.$row['zip'] : '').($row['town'] ? ' '.$row['town'] : '');
			if (!empty($row['country_code'])) {
				$label .= ', '.$langs->trans('Country'.$row['country_code']);
			}
		}
		if ($socid) {
			$label = preg_replace('/('.preg_quote($socid, '/').')/i', '<strong>$1</strong>', $label, 1);
		}
		$row_array = array();
		$row_array['label'] = $label;

		$row_array['value'] = $row['nom'];
		$row_array['key'] = $row['rowid'];

		$row_array['name_alias'] = $row['name_alias'];
		$row_array['client'] = $row['client'];
		$row_array['fournisseur'] = $row['fournisseur'];
		$row_array['code_client'] = $row['code_client'];
		$row_array['code_fournisseur'] = $row['code_fournisseur'];
		$row_array['address'] = $row['address'];
		$row_array['zip'] = $row['zip'];
		$row_array['town'] = $row['town'];
		$row_array['email'] = $row['email'];
		$row_array['siren'] = $row['siren'];
		$row_array['siret'] = $row['siret'];
		$row_array['ape'] = $row['ape'];
		$row_array['idprof4'] = $row['idprof4'];
		$row_array['idprof5'] = $row['idprof5'];
		$row_array['idprof6'] = $row['idprof6'];
		$row_array['datec'] = $row['datec'];
		$row_array['logo'] = $row['logo'];

		array_push($return_arr, $row_array);
	}

	echo json_encode($return_arr);
} else {
	echo json_encode(array('nom' => 'Error', 'label' => 'Error', 'key' => 'Error', 'value' => 'Error'));
}

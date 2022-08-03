<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Cyrille de Lambert   <info@auguria.net>
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
 *       \brief      File to return Ajax response on third parties request
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$id = GETPOST('socid', 'int') || GETPOST('id_fourn', 'int');

$object = new Societe($db);
if ($id > 0) {
	$object->fetch($id);
}

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
	$object->id = $socid;
}
restrictedArea($user, 'societe', $object->id, '&societe');


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";


// Generate list of companies
if (GETPOST('newcompany') || GETPOST('socid', 'int') || GETPOST('id_fourn', 'int')) {
	$return_arr = array();

	// Define filter on text typed
	$socid = GETPOST('newcompany');
	if (!$socid) {
		$socid = GETPOST('socid', 'int');
	}
	if (!$socid) {
		$socid = GETPOST('id_fourn', 'int');
	}

	$sql = "SELECT s.rowid, s.nom";
	if (!empty($conf->global->SOCIETE_ADD_REF_IN_LIST)) {
		$sql .= ", s.client, s.fournisseur, s.code_client, s.code_fournisseur";
	}
	if (!empty($conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST)) {
		$sql .= ", s.address, s.zip, s.town";
		$sql .= ", dictp.code as country_code";
	}
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!empty($conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST)) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as dictp ON dictp.rowid = s.fk_pays";
	}
	$sql .= " WHERE s.entity IN (".getEntity('societe').")";
	if ($socid) {
		$sql .= " AND (";
		// Add criteria on name/code
		if (!empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)) {   // Can use index
			$sql .= "nom LIKE '".$db->escape($socid)."%'";
			$sql .= " OR code_client LIKE '".$db->escape($socid)."%'";
			$sql .= " OR code_fournisseur LIKE '".$db->escape($socid)."%'";
		} else {
			$sql .= "nom LIKE '%".$db->escape($socid)."%'";
			$sql .= " OR code_client LIKE '%".$db->escape($socid)."%'";
			$sql .= " OR code_fournisseur LIKE '%".$db->escape($socid)."%'";
		}
		if (!empty($conf->global->SOCIETE_ALLOW_SEARCH_ON_ROWID)) {
			$sql .= " OR rowid = ".((int) $socid);
		}
		$sql .= ")";
	}
	//if (GETPOST("filter")) $sql.= " AND (".GETPOST("filter", "alpha").")"; // Add other filters
	$sql .= " ORDER BY nom ASC";

	//dol_syslog("ajaxcompanies", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		while ($row = $db->fetch_array($resql)) {
			$label = '';
			if (! empty($conf->global->SOCIETE_ADD_REF_IN_LIST)) {
				if (($row['client']) && (!empty($row['code_client']))) {
					$label = $row['code_client'].' - ';
				}
				if (($row['fournisseur']) && (!empty($row['code_fournisseur']))) {
					$label .= $row['code_fournisseur'].' - ';
				}
			}

			$label .= $row['nom'];

			if (!empty($conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST)) {
				$label .= ($row['address'] ? ' - '.$row['address'] : '').($row['zip'] ? ' - '.$row['zip'] : '').($row['town'] ? ' '.$row['town'] : '');
				if (!empty($row['country_code'])) {
					$label .= ', '.$langs->trans('Country'.$row['country_code']);
				}
			}
			if ($socid) {
				$label = preg_replace('/('.preg_quote($socid, '/').')/i', '<strong>$1</strong>', $label, 1);
			}
			$row_array['label'] = $label;
			$row_array['value'] = $row['nom'];
			$row_array['key'] = $row['rowid'];

			array_push($return_arr, $row_array);
		}

		echo json_encode($return_arr);
	} else {
		echo json_encode(array('nom'=>'Error', 'label'=>'Error', 'key'=>'Error', 'value'=>'Error'));
	}
} else {
	echo json_encode(array('nom'=>'ErrorBadParameter', 'label'=>'ErrorBadParameter', 'key'=>'ErrorBadParameter', 'value'=>'ErrorBadParameter'));
}

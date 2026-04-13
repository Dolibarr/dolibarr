<?php
/* Copyright (C) 2010      Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2023 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file      htdocs/core/ajax/ziptown.php
 *       \ingroup	core
 *       \brief     File to return Ajax response on zipcode or town request
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Security check
if (!getDolGlobalString('MAIN_USE_ZIPTOWN_DICTIONNARY')) {
	// If MAIN_USE_ZIPTOWN_DICTIONNARY is set, we make a search into public data (official list of zip/town). If not we search into company data, so we must check we have read permission.
	$result = restrictedArea($user, 'societe', 0, '&societe', '', 'fk_soc', 'rowid', 0);
}


/*
 * View
 */

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog('ziptown call with MAIN_USE_ZIPTOWN_DICTIONNARY='.getDolGlobalString('MAIN_USE_ZIPTOWN_DICTIONNARY'));

// Generation of list of zip-town
if (GETPOST('zipcode') || GETPOST('town')) {
	top_httphead('application/json');

	$return_arr = array();
	$formcompany = new FormCompany($db);

	// Define filter on text typed
	$zipcode = GETPOST('zipcode');
	$town = GETPOST('town');

	if (getDolGlobalString('MAIN_USE_ZIPTOWN_DICTIONNARY')) {   // Use zip-town table
		$sql = "SELECT z.rowid, z.zip, z.town, z.fk_county as state_id, z.fk_pays as country_id";
		$sql .= ", c.code as country_code, c.label as country_label";
		$sql .= ", d.code_departement as state_code, d.nom as state_label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ziptown as z";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON z.fk_county = d.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r ON d.fk_region = r.code_region,";
		$sql .= " ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE z.fk_pays = c.rowid";
		$sql .= " AND z.active = 1 AND c.active = 1";
		if ($zipcode) {
			$sql .= " AND z.zip LIKE '".$db->escape($db->escapeforlike($zipcode))."%'";
		}
		if ($town) {
			$sql .= " AND z.town LIKE '%".$db->escape($db->escapeforlike($town))."%'";
		}
		$sql .= " ORDER BY z.zip, z.town";
		$sql .= $db->plimit(100); // Avoid pb with bad criteria
	} else { // Use table of third parties
		$sql = "SELECT DISTINCT s.zip, s.town, s.fk_departement as state_id, s.fk_pays as country_id";
		$sql .= ", c.code as country_code, c.label as country_label";
		$sql .= ", d.code_departement as state_code, d.nom as state_label";
		$sql .= " FROM ".MAIN_DB_PREFIX.'societe as s';
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON s.fk_departement = d.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid';
		$sql .= " WHERE";
		if ($zipcode) {
			$sql .= " s.zip LIKE '".$db->escape($db->escapeforlike($zipcode))."%'";
		}
		if ($town) {
			$sql .= " s.town LIKE '%".$db->escape($db->escapeforlike($town))."%'";
		}
		$sql .= " ORDER BY s.fk_pays, s.zip, s.town";
		$sql .= $db->plimit(100); // Avoid pb with bad criteria
	}

	//print $sql;
	$resql = $db->query($sql);
	//var_dump($db);
	if ($resql) {
		while ($row = $db->fetch_array($resql)) {
			$row_array = [];
			$country = $row['country_id'] ? ($langs->transnoentitiesnoconv('Country'.$row['country_code']) != 'Country'.$row['country_code'] ? $langs->transnoentitiesnoconv('Country'.$row['country_code']) : $row['country_label']) : '';
			$county = $row['state_id'] ? ($langs->transnoentitiesnoconv($row['state_code']) != $row['state_code'] ? $langs->transnoentitiesnoconv($row['state_code']) : ($row['state_label'] != '-' ? $row['state_label'] : '')) : '';

			$row_array['label'] = $row['zip'].' '.$row['town'];
			$row_array['label'] .= ($county || $country) ? ' (' : '';
			$row_array['label'] .= $county;
			$row_array['label'] .= ($county && $country ? ' - ' : '');
			$row_array['label'] .= $country;
			$row_array['label'] .= ($county || $country) ? ')' : '';
			if ($zipcode) {
				$row_array['value'] = $row['zip'];
				$row_array['town'] = $row['town'];
			}
			if ($town) {
				$row_array['value'] = $row['town'];
				$row_array['zipcode'] = $row['zip'];
			}
			$row_array['selectcountry_id'] = $row['country_id'];
			$row_array['state_id'] = $row['state_id'];

			// TODO Use a cache here to avoid to make select_state in each pass (this make a SQL and lot of logs)
			$row_array['states'] = $formcompany->select_state(0, $row['country_id'], '');

			array_push($return_arr, $row_array);
		}
	}

	echo json_encode($return_arr);
} elseif (GETPOSTISSET('country_codeid')) {
	top_httphead('text/html');

	$formcompany = new FormCompany($db);
	print $formcompany->select_state(GETPOSTINT('selected', 1), GETPOSTINT('country_codeid', 1), GETPOST('htmlname', 'alpha', 1), GETPOST('morecss', 'alpha', 1));
}

$db->close();

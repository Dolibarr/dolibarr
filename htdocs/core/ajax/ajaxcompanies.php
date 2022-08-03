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
 *       \file       htdocs/core/ajax/ajaxcompanies.php
 *       \brief      File to return Ajax response on third parties request
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');

require '../../main.inc.php';


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog(join(',', $_GET));


// Generation liste des societes
if (GETPOST('newcompany') || GETPOST('socid', 'int') || GETPOST('id_fourn')) {
	$return_arr = array();

	// Define filter on text typed
	$socid = $_GET['newcompany'] ? $_GET['newcompany'] : '';
	if (!$socid) $socid = $_GET['socid'] ? $_GET['socid'] : '';
	if (!$socid) $socid = $_GET['id_fourn'] ? $_GET['id_fourn'] : '';

	$sql = "SELECT s.rowid, s.nom, s.name_alias, s.code_client, s.code_fournisseur, s.address, s.zip, s.town, s.email, s.siren, s.siret, s.ape, s.idprof4, s.client, s.fournisseur, s.datec, s.logo";
	$sql .= " , c.label as country, d.nom as departement";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = s.fk_pays";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON d.rowid = s.fk_departement";
	$sql .= " WHERE s.entity IN (".getEntity('societe').")";
	if ($socid) {
		$sql .= " AND (";
		// Add criteria on name/code
		if (!empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)) {   // Can use index
			$sql .= "s.nom LIKE '".$db->escape($socid)."%'";
			$sql .= " OR s.code_client LIKE '".$db->escape($socid)."%'";
			$sql .= " OR s.code_fournisseur LIKE '".$db->escape($socid)."%'";
		} else {
			$sql .= "s.nom LIKE '%".$db->escape($socid)."%'";
			$sql .= " OR s.code_client LIKE '%".$db->escape($socid)."%'";
			$sql .= " OR s.code_fournisseur LIKE '%".$db->escape($socid)."%'";
		}
		if (!empty($conf->global->SOCIETE_ALLOW_SEARCH_ON_ROWID)) $sql .= " OR s.rowid = '".$db->escape($socid)."'";
		$sql .= ")";
	}
	//if (GETPOST("filter")) $sql.= " AND (".GETPOST("filter", "alpha").")"; // Add other filters
	$sql .= " ORDER BY s.nom ASC";

	//dol_syslog("ajaxcompanies", LOG_DEBUG);
	$resql = $db->query($sql);

	if ($resql) {
		while ($row = $db->fetch_array($resql)) {
			$label = $row['nom'];
			if ($socid) $label = $label;
			$row_array['label'] = $label;
			$row_array['value'] = $row['nom'];
			$row_array['key'] = $row['rowid'];
			$row_array['name_alias'] = $row['name_alias'];
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
			$row_array['client'] = $row['client'];
			$row_array['fournisseur'] = $row['fournisseur'];
			$row_array['datec'] = $row['datec'];
			$row_array['logo'] = $row['logo'];
			$row_array['country'] = $row['country'];
			$row_array['departement'] = $row['departement'];

			array_push($return_arr, $row_array);
		}

		echo json_encode($return_arr);
	} else {
		echo json_encode(array('nom'=>'Error', 'label'=>'Error', 'key'=>'Error', 'value'=>'Error'));
	}
} else {
	echo json_encode(array('nom'=>'ErrorBadParameter', 'label'=>'ErrorBadParameter', 'key'=>'ErrorBadParameter', 'value'=>'ErrorBadParameter'));
}

<?php
/* Copyright (C) 2010      Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2014 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2021 	   Henry Guo <henrynopo@homtail.com>
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
 *       \file      htdocs/core/ajax/locationincoterms.php
 *       \ingroup	core
 *       \brief     File to return Ajax response on location_incoterms request
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog('location_incoterms call with MAIN_USE_LOCATION_INCOTERMS_DICTIONNARY='.(empty($conf->global->MAIN_USE_LOCATION_INCOTERMS_DICTIONNARY) ? '' : $conf->global->MAIN_USE_LOCATION_INCOTERMS_DICTIONNARY));
//var_dump($_GET);

// Generation of list of zip-town
if (GETPOST('location_incoterms')) {
	$return_arr = array();

	// Define filter on text typed
	$location_incoterms = GETPOST('location_incoterms');

	if (!empty($conf->global->MAIN_USE_LOCATION_INCOTERMS_DICTIONNARY)) {   // Use location_incoterms
		$sql = "SELECT z.location as location_incoterms, z.label as label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_location_incoterms as z";
		$sql .= " WHERE z.active = 1  AND UPPER(z.location) LIKE UPPER('%".$db->escape($location_incoterms)."%')";
		$sql .= " ORDER BY z.location";
		$sql .= $db->plimit(100); // Avoid pb with bad criteria
	} else // Use table of commande
	{
		$sql = "SELECT DISTINCT s.location_incoterms FROM ".MAIN_DB_PREFIX.'commande as s';
		$sql .= " WHERE UPPER(s.location_incoterms) LIKE UPPER('%".$db->escape($location_incoterms)."%')";

		//Todo: merge with data from table of supplier order
		/*	$sql .=" UNION";
		$sql .= " SELECT DISTINCT p.location_incoterms FROM ".MAIN_DB_PREFIX.'commande_fournisseur as p';
		$sql .= " WHERE UPPER(p.location_incoterms) LIKE UPPER('%".$db->escape($location_incoterms)."%')";
		*/
		$sql .= " ORDER BY s.location_incoterms";
		$sql .= $db->plimit(100); // Avoid pb with bad criteria
	}

	//print $sql;
	$resql = $db->query($sql);
	//var_dump($db);
	if ($resql) {
		while ($row = $db->fetch_array($resql)) {
			$row_array['label'] = $row['location_incoterms'].($row['label']?' - '.$row['label'] : '');
			if ($location_incoterms) {
				$row_array['value'] = $row['location_incoterms'];
			}
			// TODO Use a cache here to avoid to make select_state in each pass (this make a SQL and lot of logs)

			array_push($return_arr, $row_array);
		}
	}

	echo json_encode($return_arr);
}

$db->close();

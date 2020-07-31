<?php
/* Copyright (C) 2010      Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2014 Laurent Destailleur <eldy@users.sourceforge.net>
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

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';



/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog("GET is ".join(',', $_GET).', MAIN_USE_ZIPTOWN_DICTIONNARY='.(empty($conf->global->MAIN_USE_ZIPTOWN_DICTIONNARY) ? '' : $conf->global->MAIN_USE_ZIPTOWN_DICTIONNARY));
//var_dump($_GET);

// Generation of list of zip-town
if (!empty($_GET['zipcode']) || !empty($_GET['town']))
{
	$return_arr = array();
	$formcompany = new FormCompany($db);

	// Define filter on text typed
	$zipcode = $_GET['zipcode'] ? $_GET['zipcode'] : '';
	$town = $_GET['town'] ? $_GET['town'] : '';

	if (!empty($conf->global->MAIN_USE_ZIPTOWN_DICTIONNARY))   // Use zip-town table
	{
		$sql = "SELECT z.rowid, z.zip, z.town, z.fk_county, z.fk_pays as fk_country";
		$sql .= ", c.rowid as fk_country, c.code as country_code, c.label as country";
		$sql .= ", d.rowid as fk_county, d.code_departement as county_code, d.nom as county";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ziptown as z";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON z.fk_county = d.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r ON d.fk_region = r.code_region,";
		$sql .= " ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE z.fk_pays = c.rowid";
		$sql .= " AND z.active = 1 AND c.active = 1";
		if ($zipcode) $sql .= " AND z.zip LIKE '".$db->escape($zipcode)."%'";
		if ($town)    $sql .= " AND z.town LIKE '%".$db->escape($town)."%'";
		$sql .= " ORDER BY z.zip, z.town";
		$sql .= $db->plimit(100); // Avoid pb with bad criteria
	} else // Use table of third parties
	{
		$sql = "SELECT DISTINCT s.zip, s.town, s.fk_departement as fk_county, s.fk_pays as fk_country";
		$sql .= ", c.code as country_code, c.label as country";
		$sql .= ", d.code_departement as county_code , d.nom as county";
		$sql .= " FROM ".MAIN_DB_PREFIX.'societe as s';
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON s.fk_departement = d.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid';
		$sql .= " WHERE";
		if ($zipcode) $sql .= " s.zip LIKE '".$db->escape($zipcode)."%'";
		if ($town)    $sql .= " s.town LIKE '%".$db->escape($town)."%'";
		$sql .= " ORDER BY s.fk_pays, s.zip, s.town";
		$sql .= $db->plimit(100); // Avoid pb with bad criteria
	}

	//print $sql;
	$resql = $db->query($sql);
	//var_dump($db);
	if ($resql)
	{
		while ($row = $db->fetch_array($resql))
		{
			$country = $row['fk_country'] ? ($langs->transnoentitiesnoconv('Country'.$row['country_code']) != 'Country'.$row['country_code'] ? $langs->transnoentitiesnoconv('Country'.$row['country_code']) : $row['country']) : '';
			$county = $row['fk_county'] ? ($langs->transnoentitiesnoconv($row['county_code']) != $row['county_code'] ? $langs->transnoentitiesnoconv($row['county_code']) : ($row['county'] != '-' ? $row['county'] : '')) : '';

			$row_array['label'] = $row['zip'].' '.$row['town'];
			$row_array['label'] .= ($county || $country) ? ' (' : '';
			$row_array['label'] .= $county;
			$row_array['label'] .= ($county && $country ? ' - ' : '');
			$row_array['label'] .= $country;
			$row_array['label'] .= ($county || $country) ? ')' : '';
			if ($zipcode)
			{
				$row_array['value'] = $row['zip'];
				$row_array['town'] = $row['town'];
			}
			if ($town)
			{
				$row_array['value'] = $row['town'];
				$row_array['zipcode'] = $row['zip'];
			}
			$row_array['selectcountry_id'] = $row['fk_country'];
			$row_array['state_id'] = $row['fk_county'];

			// TODO Use a cache here to avoid to make select_state in each pass (this make a SQL and lot of logs)
			$row_array['states'] = $formcompany->select_state('', $row['fk_country'], '');

			array_push($return_arr, $row_array);
		}
	}

	echo json_encode($return_arr);
} else {
}

$db->close();

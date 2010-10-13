<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/societe/ajaxziptown.php
 *       \brief      File to return Ajax response on zipcode or town request
 *       \version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');

require('../main.inc.php');


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

dol_syslog(join(',',$_GET));


// Generation liste des societes
if (! empty($_GET['zipcode']) || ! empty($_GET['town']))
{
	$return_arr = array();

	// Define filter on text typed
	$zipcode = $_GET['zipcode']?$_GET['zipcode']:'';
	$town = $_GET['town']?$_GET['town']:'';

	$sql = "SELECT z.rowid, z.zip, z.town, z.fk_county";
	$sql.= ", p.rowid as fk_country, p.code as country_code, p.libelle as country";
	$sql.= ", d.rowid as fk_county, d.code_departement as county_code , d.nom as county";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_ziptown as z";
	$sql.= ", ".MAIN_DB_PREFIX ."c_departements as d";
	$sql.= ", ".MAIN_DB_PREFIX."c_regions as r";
	$sql.= ",".MAIN_DB_PREFIX."c_pays as p";
	$sql.= " WHERE z.fk_county = d.rowid";
	$sql.= " AND d.fk_region = r.code_region";
	$sql.= " AND r.fk_pays = p.rowid";
	$sql.= " AND z.active = 1 AND d.active = 1 AND r.active = 1 AND p.active = 1";
	if ($zipcode) " AND z.zip LIKE '" . $db->escape($zipcode) . "%'";
	if ($town) " AND z.town LIKE '%" . $db->escape($town) . "%'";
	$sql.= " ORDER BY z.fk_country, z.zip, z.town";

	//print $sql;
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		
		if (! $num)
		{
			$sql = "SELECT DISTINCT s.cp as zip, s.ville as town, s.fk_departement as fk_county, s.fk_pays as fk_country";
			$sql.= ", p.code as country_code, p.libelle as country";
			$sql.= ", d.code_departement as county_code , d.nom as county";
			$sql.= " FROM ".MAIN_DB_PREFIX.'societe as s';
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX ."c_departements as d ON fk_departement = d.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX.'c_pays as p ON fk_pays = p.rowid';
			$sql.= " WHERE";
			if ($zipcode) $sql.= " s.cp LIKE '".$db->escape($zipcode)."%'";
			if ($town) $sql.= " s.ville LIKE '%" . $db->escape($town) . "%'";
			$sql.= " ORDER BY s.fk_pays, s.cp, s.ville";
			$sql.= $db->plimit(50);	// Avoid pb with bad criteria
			//print $sql;
			$resql=$db->query($sql);
		}
		
		if ($resql)
		{
			while ($row = $db->fetch_array($resql))
			{
				$country = $row['fk_country']?($langs->trans('Country'.$row['country_code'])!='Country'.$row['country_code']?$langs->trans('Country'.$row['country_code']):$row['country']):'';
				$county = $row['fk_county']?($langs->trans($row['county_code'])!=$row['county_code']?$langs->trans($row['county_code']):($row['county']!='-'?$row['county']:'')):'';
				
				$row_array['label'] = $row['zip'].' '.$row['town'];
				$row_array['label'] .= ($county || $country)?' (':'';
                $row_array['label'] .= $county;
				$row_array['label'] .= ($county && $country?' - ':'');
                $row_array['label'] .= $country;
                $row_array['label'] .= ($county || $country)?')':'';
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
				$row_array['selectpays_id'] = $row['fk_country'];
				$row_array['departement_id'] = $row['fk_county'];
				
				array_push($return_arr,$row_array);
			}
		}
	}
	
	echo json_encode($return_arr);
}
else
{

}

?>

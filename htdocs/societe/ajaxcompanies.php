<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Cyrille de Lambert   <info@auguria.net>
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
 *       \file       htdocs/societe/ajaxcompanies.php
 *       \brief      File to return Ajax response on third parties request
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

print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";


//print '<body id="mainbody">';

dol_syslog(join(',',$_GET));


// Generation liste des societes
if (! empty($_POST['newcompany']) || ! empty($_GET['socid']) || ! empty($_POST['id_fourn']))
{
	$return_arr = array();
	
	// Define filter on text typed
	$socid = $_POST['newcompany']?$_POST['newcompany']:'';
	if (! $socid) $socid = $_GET['socid']?$_GET['socid']:'';
	if (! $socid) $socid = $_POST['id_fourn']?$_POST['id_fourn']:'';

	$sql = "SELECT rowid, nom";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE s.entity = ".$conf->entity;
	if ($socid)
	{
		$sql.=" AND (nom LIKE '%" . $socid . "%'";
		$sql.=" OR code_client LIKE '%" . $socid . "%'";
		$sql.=" OR code_fournisseur LIKE '%" . $socid . "%'";
		if ($conf->global->SOCIETE_ALLOW_SEARCH_ON_ROWID) $sql.=" OR rowid = '" . $socid . "'";
		$sql.=")";
	}
	if (! empty($_GET["filter"])) $sql.= " AND ".$_GET["filter"]; // Add other filters
	$sql.= " ORDER BY nom ASC";

	//dol_syslog("ajaxcompanies sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		while ($row = $db->fetch_array($resql)) {
			$row_array['socname'] = $row['nom'];
	        $row_array['socid'] = $row['rowid'];
	 
	        array_push($return_arr,$row_array);
	    }
		/*
		print '<ul>';
		while ($company = $db->fetch_object($resql))
		{
			print '<li>';
			print $company->nom;
			// To output content that will not be inserted into selected field, we use span.
			print '<span id="object" class="informal" style="display:none">'.$company->rowid.'-idcache</span>';
			print '</li>';
		}
		print '</ul>';
		*/
	    $result = array('result' => $return_arr);
	    echo json_encode($result);
	}
}
else
{

}

//print "</body>";
//print "</html>";
?>

<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');

require('../main.inc.php');

top_htmlhead("", "", 1, 1);

print '<body id="mainbody">';

// Generation liste des societes
if (! empty($_POST['newcompany']) || ! empty($_POST['socid']) || ! empty($_POST['id_fourn']))
{
	// Define filter on text typed
	$socid = $_POST['newcompany']?$_POST['newcompany']:'';
	if (! $socid) $socid = $_POST['socid']?$_POST['socid']:'';
	if (! $socid) $socid = $_POST['id_fourn']?$_POST['id_fourn']:'';

	$sql = "SELECT rowid, nom";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE nom LIKE '%" . $socid . "%'";
	if (! empty($_GET["filter"])) $sql.= " AND ".$_GET["filter"]; // Add other filters
	$sql.= " ORDER BY nom ASC";

	//dol_syslog("ajaxcompanies sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		print '<ul>';
		while ($company = $db->fetch_object($resql))
		{
			print '<li id="'.$company->rowid.'">';
			print $company->nom;
			//print '<span id="object" class="informal" style="display:none">'.$company->rowid.'-idcache</span>';
			print '</li>';
		}
		print '</ul>';
	}
}

print "</body>";
print "</html>";
?>

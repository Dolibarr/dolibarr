<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/societe/ajaxcountries.php
        \brief      Fichier de reponse sur evenement Ajax
        \version    $Revision$
*/

require('../main.inc.php');

top_htmlhead("", "", 1, 1);

print '<body id="mainbody">';

// Generation liste des sociétés
if(isset($_POST['newcompany']) && !empty($_POST['newcompany']) || isset($_POST['socid']) && !empty($_POST['socid'])
    || isset($_POST['id_fourn']) && !empty($_POST['id_fourn']))
{
	
	$socid = $_POST['newcompany']?$_POST['newcompany']:'';
	$socid = $_POST['socid']?$_POST['socid']:'';
	$socid = $_POST['id_fourn']?$_POST['id_fourn']:'';

	$sql = "SELECT rowid, nom";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe";
	$sql.= " WHERE nom LIKE '%" . utf8_decode($socid) . "%'";
	$sql.= " ORDER BY nom ASC;";

	$resql=$db->query($sql);

	if ($resql)
	{
		print '<ul>';
		while($company = $db->fetch_object($resql))
		{
			print '<li>';
			print $company->nom;
			print '<span id="object" class="informal"	style="display:none">'.$company->rowid.'-idcache</span>';
			print '</li>';
		}
		print '</ul>';
	}
} 

print "</body>"; 
print "</html>"; 
?>

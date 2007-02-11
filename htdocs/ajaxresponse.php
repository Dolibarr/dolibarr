<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
        \file       htdocs/ajaxresponse.php
        \brief      Fichier de reponse sur evenement Ajax
        \version    $Revision$
*/

require('./master.inc.php');

$langs->load("products");
$langs->load("main");

//header("Content-type: text/html; charset=UTF-8");
header("Content-type: text/html; charset=iso-8859-1");
print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
print "\n<html>";
print "\n<body>";


// Generation liste de produits
if(isset($_GET['keyref']) && !empty($_GET['keyref']) || isset($_GET['keylabel']) && !empty($_GET['keylabel']))
{
	$form = new Form($db);
	$form->select_produits_do("",$_GET["htmlname"],"","",$_GET["price_level"],$_GET["keyref"],$_GET["keylabel"]);
}


// Generation liste des pays
if(isset($_POST['pays']) && !empty($_POST['pays']))
{
	$sql = "SELECT rowid, code, libelle, active";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
	$sql.= " WHERE active = 1 AND libelle LIKE '%" . utf8_decode($_POST['pays']) . "%'";
	$sql.= " ORDER BY libelle ASC;";
	
	$resql=$db->query($sql);

	if ($resql)
	{
		print '<ul>';
		while($data = mysql_fetch_assoc($resql))
		{
			print '<li>';
			print stripslashes($data['libelle']);
			print '<span class="informal" style="display:none">'.$data['rowid'].'-idcache</span>';
			print '</li>';
		}
		print '</ul>';
	}
} 

print "</body>"; 
print "</html>"; 
?>

<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/societe/ajaxcountries.php
 *       \brief      File to return Ajax response on country request
 *       \version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');

require('../main.inc.php');

top_htmlhead("", "", 1, 1);

print '<body id="mainbody">';

// Generation liste des pays
if(isset($_POST['pays']) && !empty($_POST['pays']))
{
	global $langs;
	$langs->load("dict");

	$sql = "SELECT rowid, code, libelle, active";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
	$sql.= " WHERE active = 1 AND libelle LIKE '%" . utf8_decode($_POST['pays']) . "%'";
	$sql.= " ORDER BY libelle ASC;";

	$resql=$db->query($sql);
	if ($resql)
	{
		print '<ul>';
		while($pays = $db->fetch_object($resql))
		{
			print '<li>';
			// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
			print ($pays->code && $langs->trans("Country".$pays->code)!="Country".$pays->code?$langs->trans("Country".$pays->code):($pays->libelle!='-'?$pays->libelle:'&nbsp;'));
			print '<span class="informal" style="display:none">'.$pays->rowid.'-idcache</span>';
			print '</li>';
		}
		print '</ul>';
	}
}

print "</body>";
print "</html>";
?>

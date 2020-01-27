<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/societe/ajaxcountries.php
 *       \brief      File to return Ajax response on country request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');

require '../main.inc.php';

$country=GETPOST('country', 'alpha');


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog(join(',', $_POST));

// Generate list of countries
if (! empty($country))
{
	global $langs;
	$langs->load("dict");

	$sql = "SELECT rowid, code, label, active";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_country";
	$sql.= " WHERE active = 1 AND label LIKE '%" . $db->escape(utf8_decode($country)) . "%'";
	$sql.= " ORDER BY label ASC";

	$resql=$db->query($sql);
	if ($resql)
	{
		print '<ul>';
		while($country = $db->fetch_object($resql))
		{
			print '<li>';
			// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
			print ($country->code && $langs->trans("Country".$country->code)!="Country".$country->code?$langs->trans("Country".$country->code):($country->label!='-'?$country->label:'&nbsp;'));
			print '<span class="informal" style="display:none">'.$country->rowid.'-idcache</span>';
			print '</li>';
		}
		print '</ul>';
	}
}

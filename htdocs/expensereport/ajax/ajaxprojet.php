<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/expensereport/ajax/ajaxprojet.php
 *       \ingroup    expensereport
 *       \brief      File to return Ajax response on third parties request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');

$res=0;
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

dol_syslog(join(',',$_GET));


// Generation liste des projets
if (GETPOST('fk_projet') != '')
{
	$return_arr = array();

	$sql = "SELECT p.rowid, p.ref, p.title, s.nom";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
	if (! empty($_GET["fk_projet"])) $sql.= " WHERE p.ref LIKE '%".$db->escape($_GET["fk_projet"])."%' OR p.title LIKE '%".$db->escape($_GET["fk_projet"])."%' OR s.nom LIKE '%".$db->escape($_GET["fk_projet"])."%'"; // Add other filters
	$sql.= " ORDER BY p.ref ASC";

	$resql=$db->query($sql);
	if ($resql)
	{
		while ($row = $db->fetch_array($resql))
		{
		    $label=$row['ref'].' - '.$row['title'];
		    $row_array['label'] = $label;
			 $row_array['value'] = $label;
	       $row_array['key'] = $row['rowid'];

	        array_push($return_arr,$row_array);
	    }

	    echo json_encode($return_arr);
	}
	else
	{
	    echo json_encode(array('nom'=>'Error','label'=>'Error','key'=>'Error','value'=>'Error'));
	}
}
else
{
    echo json_encode(array('nom'=>'ErrorBadParameter','label'=>'ErrorBadParameter','key'=>'ErrorBadParameter','value'=>'ErrorBadParameter'));
}

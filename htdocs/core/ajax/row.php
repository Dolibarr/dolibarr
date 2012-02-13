<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/ajax/row.php
 *       \brief      File to return Ajax response on Row move
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/genericobject.class.php");


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Registering the location of boxes
if ((isset($_POST['roworder']) && ! empty($_POST['roworder'])) && (isset($_POST['table_element_line']) && ! empty($_POST['table_element_line']))
	&& (isset($_POST['fk_element']) && ! empty($_POST['fk_element'])) && (isset($_POST['element_id']) && ! empty($_POST['element_id'])) )
{
	$roworder = explode(',',GETPOST('roworder','alpha',2));

	foreach($roworder as $value)
	{
		if (! empty($value)) $newroworder[] = $value;
	}

	dol_syslog("AjaxRow roworder=".GETPOST('roworder','alpha',2)." fk_element=".GETPOST('fk_element','int',2), LOG_DEBUG);

	$row=new GenericObject($db);
	$row->table_element_line = GETPOST('table_element_line','alpha',2);
	$row->fk_element = GETPOST('fk_element','int',2);
	$row->id = GETPOST('element_id','int',2);
	$result=$row->line_ajaxorder($newroworder);
	$result=$row->line_order(true);
}

?>

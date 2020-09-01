<?php
/* Copyright (C) 2010-2015 Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/row.php
 *       \brief      File to return Ajax response on Row move.
 *                   This ajax page is called when doing an up or down drag and drop.
 *                   Parameters:
 *                   roworder (Example: '1,3,2,4'),
 *                   table_element_line (Example: 'commandedet')
 *                   fk_element (Example: 'fk_order')
 *                   element_id (Example: 1)
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disable token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Registering the location of boxes
if (GETPOST('roworder', 'alpha') && GETPOST('table_element_line', 'alpha', 2)
	&& GETPOST('fk_element', 'alpha', 2) && GETPOST('element_id', 'int', 2))
{
	$roworder = GETPOST('roworder', 'alpha', 2);
	$table_element_line = GETPOST('table_element_line', 'alpha', 2);
	$fk_element = GETPOST('fk_element', 'alpha', 2);
	$element_id = GETPOST('element_id', 'int', 2);

	dol_syslog("AjaxRow roworder=".$roworder." table_element_line=".$table_element_line." fk_element=".$fk_element." element_id=".$element_id, LOG_DEBUG);

	$rowordertab = explode(',', $roworder);
	$newrowordertab = array();
	foreach ($rowordertab as $value)
	{
		if (!empty($value)) $newrowordertab[] = $value;
	}

	$row = new GenericObject($db);
	$row->table_element_line = $table_element_line;
	$row->fk_element = $fk_element;
	$row->id = $element_id;

	$row->line_ajaxorder($newrowordertab); // This update field rank or position in table row->table_element_line

	// Reorder line to have position of children lines sharing same counter than parent lines
	// This should be useless because there is no need to have children sharing same counter than parent, but well, it's cleaner into database.
	if (in_array($fk_element, array('fk_facture', 'fk_propal', 'fk_commande')))
	{
		$result = $row->line_order(true);
	}
}

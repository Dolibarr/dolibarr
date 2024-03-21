<?php
/* Copyright (C) 2019 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/mrp/lib/mrp.lib.php
 * \ingroup mrp
 * \brief   Library files with common functions for Mrp
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function mrpAdminPrepareHead()
{
	global $langs, $conf, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('mrp_mo');

	$langs->load("mrp");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/admin/mrp.php';
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/admin/mrp_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['mrp_mo']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'mrp_extrafields';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/admin/mrp_production_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$head[$h][2] = 'mrp_production_extrafields';
	$h++;
	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@mrp:/mrp/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@mrp:/mrp/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'mrp');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'mrp', 'remove');

	return $head;
}

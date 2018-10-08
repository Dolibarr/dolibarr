<?php
/* Copyright (C) 2018 SuperAdmin
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    datapolicies/lib/datapolicies.lib.php
 * \ingroup datapolicies
 * \brief   Library files with common functions for datapolicies
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function datapoliciesAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("datapolicies@datapolicies");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/datapolicies/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings_DATAPOLICIES");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/datapolicies/admin/setupmail.php", 1);
	$head[$h][1] = $langs->trans("DATAPOLICIESMail");
	$head[$h][2] = 'settings';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@datapolicies:/datapolicies/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@datapolicies:/datapolicies/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'datapolicies');

	return $head;
}

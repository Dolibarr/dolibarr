<?php
/* Copyright (C) 2022 BERTON Anthony <a.berton@gest-mag.com>
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
 * \file    documentsfunctionality/lib/documentsfunctionality.lib.php
 * \ingroup documentsfunctionality
 * \brief   Library files with common functions for DocumentsFunctionality
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function documentsfunctionalityAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("documentsfunctionality@documentsfunctionality");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/documentsfunctionality/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/documentsfunctionality/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/documentsfunctionality/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@documentsfunctionality:/documentsfunctionality/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@documentsfunctionality:/documentsfunctionality/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'documentsfunctionality@documentsfunctionality');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'documentsfunctionality@documentsfunctionality', 'remove');

	return $head;
}

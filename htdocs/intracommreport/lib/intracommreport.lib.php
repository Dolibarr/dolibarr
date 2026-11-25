<?php
/* Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026       Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 * \file    htdocs/intracommreport/lib/intracommreport.lib.php
 * \ingroup intracommreport
 * \brief   Library files with common functions for intracommreport
 */

/**
 * Prepare admin pages header
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function intracommreportAdminPrepareHead()
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('intracommreport');

	$langs->load("intracommreport");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/intracommreport/admin/intracommreport.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/intracommreport/admin/intracommreport_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = is_countable($extrafields->attributes['intracommreport']['label']) ? count($extrafields->attributes['intracommreport']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'intracommreport_extrafields';
	$h++;
	*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@intracommreport:/intracommreport/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@intracommreport:/intracommreport/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'intracommreportadmin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'intracommreportadmin', 'remove');

	return $head;
}


/**
 * Prepare array of tabs for IntraCommReport
 *
 * @param	IntraCommReport	$object		IntraCommReport
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function intracommreportPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("intracommreport");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/intracommreport/card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("IntraCommReport");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'intracommreport');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'intracommreport', 'remove');

	return $head;
}

<?php
/* Copyright (C) 2026		John BOTELLA
 * Copyright (C) 2025-2026  Frédéric France         <frederic.france@free.fr>
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
 * \file    quickmemo/lib/quickmemo.lib.php
 * \ingroup quickmemo
 * \brief   Library files with common functions for QuickMemo
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function quickmemoAdminPrepareHead()
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');

	$langs->load("quickmemo");

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_DOCUMENT_ROOT."/quickmemo/admin/setup.php");
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;


	//  $head[$h][0] = dolBuildUrl(dol_buildpath("/quickmemo/admin/memo_extrafields.php", 1));
	//  $head[$h][1] = $langs->trans("ExtraFields");
	//  $nbExtrafields = (isset($extrafields->attributes['memo']['label']) && is_countable($extrafields->attributes['memo']['label'])) ? count($extrafields->attributes['memo']['label']) : 0;
	//  if ($nbExtrafields > 0) {
	//      $head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	//  }
	//  $head[$h][2] = 'memo_extrafields';
	//  $h++;

	/*
	$head[$h][0] = dolBuildUrl(dol_buildpath("/quickmemo/admin/myobjectline_extrafields.php", 1));
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$nbExtrafields = (isset($extrafields->attributes['myobjectline']['label']) && is_countable($extrafields->attributes['myobjectline']['label'])) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafieldsline';
	$h++;
	*/


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@quickmemo:/quickmemo/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@quickmemo:/quickmemo/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'quickmemo@quickmemo');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'quickmemo@quickmemo', 'remove');

	return $head;
}

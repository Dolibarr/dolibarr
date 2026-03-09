<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * \file    htdocs/webportal/lib/webportal.lib.php
 * \ingroup webportal
 * \brief   Library files with common functions for WebPortal
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function webportalAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("website");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/webportal/admin/setup.php';
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/webportal/admin/setup_theme.php';
	$head[$h][1] = $langs->trans("SkinAndColors");
	$head[$h][2] = 'themesettings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/webportal/admin/configcss.php';
	$head[$h][1] = $langs->trans("CSSPage");
	$head[$h][2] = 'css';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@webportal:/webportal/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@webportal:/webportal/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'webportal');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'webportal', 'remove');

	return $head;
}

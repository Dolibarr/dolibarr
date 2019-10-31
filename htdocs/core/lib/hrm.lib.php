<?php
/* Copyright (C) 2015 Alexandre Spangaro <aspangaro@open-dsi.fr>
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
 * \file    htdocs/core/lib/hrm.lib.php
 * \ingroup HRM
 * \brief   Library for hrm
 */

/**
 * Return head table for establishment tabs screen
 *
 * @param   Establishment	$object		Object related to tabs
 * @return  array						Array of tabs to show
 */
function establishment_prepare_head($object)
{
	global $langs, $conf;

	$langs->load('hrm');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/hrm/establishment/card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'establishment');

	$head[$h][0] = DOL_URL_ROOT.'/hrm/establishment/info.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'establishment', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @return	array		head
 */
function hrm_admin_prepare_head()
{
    global $langs, $conf, $user;

    $langs->load('hrm');

    $h = 0;
    $head = array();

	$head[$h][0] = DOL_URL_ROOT.'/hrm/admin/admin_hrm.php';
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'parameters';
    $h++;

	$head[$h][0] = DOL_URL_ROOT.'/hrm/admin/admin_establishment.php';
    $head[$h][1] = $langs->trans("Establishments");
    $head[$h][2] = 'establishments';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, '', $head, $h, 'hrm_admin');

    complete_head_from_modules($conf, $langs, '', $head, $h, 'hrm_admin', 'remove');

    return $head;
}

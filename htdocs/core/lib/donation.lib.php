<?php
/* Copyright (C) 2015 	   Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
 * 		\file			htdocs/core/lib/donation.lib.php
 * 		\ingroup		Donation
 * 		\brief			Library of donation functions
 */

/**
 *	Prepare array with list of admin tabs
 *
 *	@param	Donation	$object		Donation
 *	@return	array					Array of tabs to show
 */
function donation_admin_prepare_head($object)
{
	global $langs, $conf;

	$h = 0;
	$head = array ();

	$head[$h][0] = DOL_URL_ROOT . '/don/admin/donation.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h ++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname); to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'donation_admin');
	
	$head[$h][0] = DOL_URL_ROOT . '/don/admin/donation_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'donation_admin', 'remove');

	return $head;
}

/**
 *	Prepare array with list of tabs
 *
 *	@param	Donation	$object		Donation
 *	@return	array					Array of tabs to show
 */
function donation_prepare_head($object)
{
	global $langs, $conf;

	$h = 0;
	$head = array ();

	$head[$h][0] = DOL_URL_ROOT . '/don/card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h ++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname); to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'donation');

	$head[$h][0] = DOL_URL_ROOT . '/don/info.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'donation', 'remove');

	return $head;
}

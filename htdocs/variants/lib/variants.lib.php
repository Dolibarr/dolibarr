<?php
/* Copyright (C) 2022       Open-Dsi		            <support@open-dsi.fr>
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
 * \file    /variants/lib/variants.lib.php
 * \ingroup variants
 * \brief   Library files with common functions for Variants
 */


/**
 * Prepare array with list of tabs
 *
 * @param   ProductAttribute	$object	Object related to tabs
 * @return  array						Array of tabs to show
 */
function productAttributePrepareHead($object)
{
	global $langs, $conf;
	$langs->load("products");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/variants/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ProductAttribute");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'product_attribute');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'product_attribute', 'remove');

	return $head;
}

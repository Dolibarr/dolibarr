<?php
/* Copyright (C) 2022       Open-Dsi		            <support@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
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

/**
 * Prepare array with list of tabs
 *
 * @return  array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function adminProductAttributePrepareHead()
{
	global $langs, $conf, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('product_attribute');
	$extrafields->fetch_name_optionals_label('product_attribute_value');

	$langs->load("products");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/variants/admin/admin.php';
	$head[$h][1] = $langs->trans("ProductAttribute");
	$head[$h][2] = 'admin';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/variants/admin/product_attribute_extrafields.php';
	$head[$h][1] = $langs->trans("ProductAttributeExtrafields");
	$nbExtrafields = $extrafields->attributes['product_attribute']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'product_attribute';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/variants/admin/product_attribute_value_extrafields.php';
	$head[$h][1] = $langs->trans("ProductAttributeValueExtrafields");
	$nbExtrafields = $extrafields->attributes['product_attribute_value']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'product_attribute_value';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'admin_product_attribute');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'admin_product_attribute', 'remove');

	return $head;
}

<?php
/* Copyright (C) 2011 Regis Houssin  <regis.houssin@inodbox.com>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/categories.lib.php
 *	\brief      Ensemble de functions de base pour le module categorie
 *	\ingroup    categorie
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Categorie	$object		Object related to tabs
 * @param	string		$type		Type of category
 * @return  array					Array of tabs to show
 */
function categories_prepare_head(Categorie $object, $type)
{
	global $langs, $conf, $user;

	// Load translation files required by the page
	$langs->loadLangs(array('categories', 'products'));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/categories/viewcat.php?id='.$object->id.'&amp;type='.$type;
	$head[$h][1] = $langs->trans("Category");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/categories/photos.php?id='.$object->id.'&amp;type='.$type;
	$head[$h][1] = $langs->trans("Photos");
	$head[$h][2] = 'photos';
	$h++;

	if (getDolGlobalInt('MAIN_MULTILANGS')) {
		$head[$h][0] = DOL_URL_ROOT.'/categories/traduction.php?id='.$object->id.'&amp;type='.$type;
		$head[$h][1] = $langs->trans("Translation");
		$head[$h][2] = 'translation';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/categories/info.php?id='.$object->id.'&amp;type='.$type;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'categories_'.$type);

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'categories_'.$type, 'remove');

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function categoriesadmin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('categorie');

	$langs->load("categories");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/categories/admin/categorie.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'setup';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/categories/admin/categorie_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsCategories");
	$nbExtrafields = $extrafields->attributes['categorie']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes_categories';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'categoriesadmin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'categoriesadmin', 'remove');

	return $head;
}

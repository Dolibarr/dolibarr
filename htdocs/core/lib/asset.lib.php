<?php
/* Copyright (C) 2018      Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
 * \file    htdocs/core/lib/asset.lib.php
 * \ingroup asset
 * \brief   Library files with common functions for Assets
 */

/**
 * Prepare admin pages header
 *
 * @return array head array with tabs
 */
function asset_admin_prepare_head()
{
	global $langs, $conf;

	$langs->load("assets");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/asset/admin/setup.php';
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@assets:/asset/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@assets:/asset/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'assets_admin');

	$head[$h][0] = DOL_URL_ROOT.'/asset/admin/assets_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/asset/admin/assets_type_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsAssetsType");
	$head[$h][2] = 'attributes_type';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'assets_admin', 'remove');

	return $head;
}

/**
 * Prepare admin pages header
 *
 * @param   Contrat	$object		Object related to tabs
 * @return array head array with tabs
 */
function asset_prepare_head(Asset $object)
{
	global $db, $langs, $conf;

	$langs->load("assets");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/asset/card.php';
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@assets:/asset/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@assets:/asset/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'assets');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->assets->dir_output.'/'.dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/asset/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$nbNote = 0;
	if (!empty($object->note_private)) $nbNote++;
	if (!empty($object->note_public)) $nbNote++;
	$head[$h][0] = DOL_URL_ROOT.'/asset/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Notes");
	if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/asset/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'asset', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	AssetType	$object		Asset
 *  @return array					head
 */
function asset_type_prepare_head(AssetType $object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/asset/type.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'assettype');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'assettype', 'remove');

	return $head;
}

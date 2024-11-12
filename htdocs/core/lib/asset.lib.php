<?php
/* Copyright (C) 2018-2022  OpenDSI                 <support@open-dsi.fr>
 * Copyright (C) 2022-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * @return array<array{0:string,1:string,2:string}> head array with tabs
 */
function assetAdminPrepareHead()
{
	global $langs, $conf, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('asset');
	$extrafields->fetch_name_optionals_label('asset_model');

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
	//	'entity:+tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'asset_admin');

	$head[$h][0] = DOL_URL_ROOT.'/asset/admin/asset_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['asset']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'asset_extrafields';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/asset/admin/assetmodel_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsAssetModel");
	$nbExtrafields = $extrafields->attributes['asset_model']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'assetmodel_extrafields';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'asset_admin', 'remove');

	return $head;
}

/**
 * Prepare array of tabs for Asset
 *
 * @param	Asset	$object		Asset
 * @return 	array<array{0:string,1:string,2:string}>	Array of tabs
 */
function assetPrepareHead(Asset $object)
{
	global $db, $langs, $conf;

	$langs->loadLangs(array("assets", "admin"));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/asset/card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (empty($object->not_depreciated)) {
		$head[$h][0] = DOL_URL_ROOT . '/asset/depreciation_options.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("AssetDepreciationOptions");
		$head[$h][2] = 'depreciation_options';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT . '/asset/accountancy_codes.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("AssetAccountancyCodes");
	$head[$h][2] = 'accountancy_codes';
	$h++;

	if (empty($object->not_depreciated)) {
		$head[$h][0] = DOL_URL_ROOT . '/asset/depreciation.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("AssetDepreciation");
		$head[$h][2] = 'depreciation';
		$h++;
	}

	if (isset($object->disposal_date) && $object->disposal_date !== "") {
		$head[$h][0] = DOL_URL_ROOT . '/asset/disposal.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("AssetDisposal");
		$head[$h][2] = 'disposal';
		$h++;
	}

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT . '/asset/note.php?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->asset->dir_output . "/asset/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT . '/asset/document.php?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/asset/agenda.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'asset');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'asset', 'remove');

	return $head;
}

/**
 * Prepare array of tabs for AssetModel
 *
 * @param	AssetModel	$object		AssetModel
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function assetModelPrepareHead($object)
{
	global $langs, $conf;

	$langs->loadLangs(array("assets", "admin"));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/asset/model/card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT . '/asset/model/note.php?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT . '/asset/model/agenda.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'assetmodel');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'assetmodel', 'remove');

	return $head;
}

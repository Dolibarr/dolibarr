<?php
/* Copyright (C) 2008-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/core/lib/ecm.lib.php
 *  \brief      Ensemble de fonctions de base pour le module ecm
 *  \ingroup    ecm
 */


/**
 * Prepare array with list of different ecm main dashboard
 *
 * @param   object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function ecm_prepare_dasboard_head($object)
{
	global $langs, $conf, $user, $form;
	global $helptext1, $helptext2;

	$h = 0;
	$head = array();
	$helptext = $langs->trans("ECMAreaDesc").'<br>';
	$helptext .= $langs->trans("ECMAreaDesc2");

	$head[$h][0] = DOL_URL_ROOT.'/ecm/index.php';
	$head[$h][1] = $langs->trans("ECMSectionsManual").$form->textwithpicto('', $helptext, 1, 'info', '', 0, 3);
	$head[$h][2] = 'index';
	$h++;

	if (!empty($conf->global->ECM_AUTO_TREE_ENABLED)) {
		$head[$h][0] = DOL_URL_ROOT.'/ecm/index_auto.php';
		$head[$h][1] = $langs->trans("ECMSectionsAuto").$form->textwithpicto('', $helptext, 1, 'info', '', 0, 3);
		$head[$h][2] = 'index_auto';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'ecm');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'ecm', 'remove');

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   object	$object		Object related to tabs
 * @param	string	$module		Module
 * @param	string	$section	Section
 * @return  array				Array of tabs to show
 */
function ecm_prepare_head($object, $module = 'ecm', $section = '')
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	if ($module == 'ecm') {
		$head[$h][0] = DOL_URL_ROOT.'/ecm/dir_card.php?section='.$object->id;
		$head[$h][1] = $langs->trans("Directory");
		$head[$h][2] = 'card';
		$h++;
	} else {
		$head[$h][0] = DOL_URL_ROOT.'/ecm/dir_card.php?section='.$section.'&module='.$module;
		$head[$h][1] = $langs->trans("Directory");
		$head[$h][2] = 'card';
		$h++;
	}

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function ecm_file_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/ecm/file_card.php?section='.$object->section_id.'&urlfile='.urlencode($object->label);
	$head[$h][1] = $langs->trans("File");
	$head[$h][2] = 'card';
	$h++;

	// Notes
	$head[$h][0] = DOL_URL_ROOT.'/ecm/file_note.php?section='.$object->section_id.'&urlfile='.urlencode($object->label);
	$head[$h][1] = $langs->trans("Notes");
	$nbNote = 0;
	if (!empty($object->note_private)) {
		$nbNote++;
	}
	if (!empty($object->note_public)) {
		$nbNote++;
	}
	if ($nbNote > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
	}
	$head[$h][2] = 'note';
	$h++;

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function ecm_prepare_head_fm($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/ecm/index.php?action=file_manager';
	$head[$h][1] = $langs->trans('ECMFileManager');
	$head[$h][2] = 'file_manager';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/ecm/search.php';
	$head[$h][1] = $langs->trans('Search');
	$head[$h][2] = 'search_form';
	$h++;

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array               head array with tabs
 */
function ecm_admin_prepare_head()
{
	global $langs, $conf;
	$langs->load("ecm");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/ecm.php";
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'ecm';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/ecm_files_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsEcmFiles");
	$head[$h][2] = 'attributes_ecm_files';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/ecm_directories_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsEcmDirectories");
	$head[$h][2] = 'attributes_ecm_directories';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'ecm_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'ecm_admin', 'remove');

	return $head;
}

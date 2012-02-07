<?php
/* Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/lib/ecm.lib.php
 *  \brief      Ensemble de fonctions de base pour le module ecm
 *  \ingroup    ecm
 */


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function ecm_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/ecm/docmine.php?section='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function ecm_file_prepare_head($object)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/ecm/docfile.php?section='.$object->section_id.'&urlfile='.urlencode($object->label);
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
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

?>

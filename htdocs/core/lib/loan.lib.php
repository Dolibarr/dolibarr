<?php
/* Copyright (C) 2014		Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
 *      \file       htdocs/core/lib/loan.lib.php
 *      \ingroup    loan
 *      \brief      Library for loan module
 */


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function loan_prepare_head($object)
{
    global $langs, $conf;

    $h = 0;
    $head = array();

	$head[$h][0] = DOL_URL_ROOT.'/loan/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'loan');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->loan->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview\.png)$'));
	$head[$h][0] = DOL_URL_ROOT.'/loan/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if($nbFiles > 0) $head[$h][1].= ' ('.$nbFiles.')';
	$head[$h][2] = 'documents';
	$h++;

    $head[$h][0] = DOL_URL_ROOT.'/loan/info.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'loan','remove');

    return $head;
}
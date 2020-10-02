<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/core/lib/holiday.lib.php
 *		\brief      Ensemble de fonctions de base pour les adherents
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object         Holiday
 *  @return array           		head
 */
function holiday_prepare_head($object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

    $head[$h][0] = DOL_URL_ROOT.'/holiday/card.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Leave");
    $head[$h][2] = 'card';
    $h++;

    // Attachments
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->holiday->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
    $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    $nbLinks = Link::count($db, $object->element, $object->id);
    $head[$h][0] = DOL_URL_ROOT.'/holiday/document.php?id='.$object->id;
    $head[$h][1] = $langs->trans('Documents');
    if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
    $head[$h][2] = 'documents';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'holiday');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'holiday', 'remove');

	return $head;
}


/**
 *  Return array head with list of tabs to view object informations
 *
  *  @return array           		head
 */
function holiday_admin_prepare_head()
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

    $head[$h][0] = DOL_URL_ROOT.'/admin/holiday.php';
    $head[$h][1] = $langs->trans("Setup");
    $head[$h][2] = 'holiday';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'holiday_admin');

    $head[$h][0] = DOL_URL_ROOT.'/admin/holiday_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
    $h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'holiday_admin', 'remove');

	return $head;
}

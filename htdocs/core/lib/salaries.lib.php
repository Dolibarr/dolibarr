<?php
/**
 * Copyright (C) 2015	Charlie BENKE       <charlie@patas-monkey.com>
 * Copyright (C) 2019	Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
 * Returns an array with the tabs for the "salaries" section
 * It loads tabs from modules looking for the entity salaries
 *
 * @param Paiement $object Current salaries object
 * @return array Tabs for the salaries section
 */
function salaries_prepare_head($object)
{

    global $db, $langs, $conf;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/salaries/card.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'salaries');

    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->salaries->dir_output . "/" . dol_sanitizeFileName($object->ref);
    $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    $nbLinks=Link::count($db, $object->element, $object->id);
    $head[$h][0] = DOL_URL_ROOT.'/salaries/document.php?id='.$object->id;
    $head[$h][1] = $langs->trans('Documents');
    if (($nbFiles+$nbLinks) > 0) $head[$h][1].= '<span class="badge ">'.($nbFiles+$nbLinks).'</span>';
    $head[$h][2] = 'documents';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/salaries/info.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'salaries', 'remove');

    return $head;
}

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @return	array		head
 */
function salaries_admin_prepare_head()
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/salaries/admin/salaries.php';
    $head[$h][1] = $langs->trans("Miscellaneous");
    $head[$h][2] = 'general';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, '', $head, $h, 'salaries_admin');

    $head[$h][0] = DOL_URL_ROOT.'/salaries/admin/salaries_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsSalaries");
    $head[$h][2] = 'attributes';
    $h++;

    complete_head_from_modules($conf, $langs, '', $head, $h, 'salaries_admin', 'remove');

    return $head;
}

<?php
/* Copyright (C) 2016	Alexandre Spangaro	<aspangaro@zendsi.com>
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
 *      \file       htdocs/core/lib/vat.lib.php
 *      \ingroup    tax
 *      \brief      Library for tax module (VAT)
 */


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function vat_prepare_head($object)
{
    global $db, $langs, $conf;

    $tab = 0;
    $head = array();

	$head[$tab][0] = DOL_URL_ROOT.'/compta/tva/card.php?id='.$object->id;
	$head[$tab][1] = $langs->trans('Card');
	$head[$tab][2] = 'card';
	$tab++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $tab,'vat');

    $head[$tab][0] = DOL_URL_ROOT.'/compta/tva/info.php?id='.$object->id;
    $head[$tab][1] = $langs->trans("Info");
    $head[$tab][2] = 'info';
    $tab++;

    complete_head_from_modules($conf,$langs,$object,$head,$tab,'vat','remove');

    return $head;
}

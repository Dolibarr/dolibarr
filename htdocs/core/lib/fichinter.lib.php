<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *	\file       htdocs/core/lib/fichinter.lib.php
 *	\brief      Ensemble de fonctions de base pour le module fichinter
 *	\ingroup    fichinter
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function fichinter_prepare_head($object)
{
	global $langs, $conf, $user;
	$langs->load("fichinter");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/contact.php?id='.$object->id;
	$head[$h][1] = $langs->trans('InterventionContact');
	$head[$h][2] = 'contact';
	$h++;

	if (! empty($conf->global->MAIN_USE_PREVIEW_TABS))
	{
		$head[$h][0] = DOL_URL_ROOT.'/fichinter/apercu.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Preview');
		$head[$h][2] = 'preview';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'intervention');

    $head[$h][0] = DOL_URL_ROOT.'/fichinter/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	$head[$h][2] = 'documents';
	$h++;

    $head[$h][0] = DOL_URL_ROOT.'/fichinter/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

?>

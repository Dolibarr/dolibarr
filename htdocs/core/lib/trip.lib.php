<?php
/* Copyright (C) 2011	Regis Houssin	<regis.houssin@inodbox.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/trip.lib.php
 *		\brief      Ensemble de fonctions de base pour les deplacements
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Deplacement	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function trip_prepare_head(Deplacement $object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/compta/deplacement/card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'trip');

	$head[$h][0] = DOL_URL_ROOT.'/compta/deplacement/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/compta/deplacement/info.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'trip','remove');

	return $head;
}

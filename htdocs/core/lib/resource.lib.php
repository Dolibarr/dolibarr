<?php
/* Module to manage locations, buildings, floors and rooms into Dolibarr ERP/CRM
 * Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/core/lib/resource.lib.php
 *	\ingroup	resource
 *	\brief		This file is library for resource module
 */

/**
 * Prepare head for tabs
 *
 * @param	Object	$object		Object
 * @return	array				Array of head entries
 */
function resourcePrepareHead($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/resource/card.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("ResourceCard");
    $head[$h][2] = 'resource';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'resource');


	return $head;
}

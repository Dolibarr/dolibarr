<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    htdocs/modulebuilder/template/lib/myobject.lib.php
 * \ingroup mymodule
 * \brief   Library files with common functions for MyObject
 */

/**
 * Prepare array of tabs for MyObject
 *
 * @param	MyObject	$object		MyObject
 * @return 	array					Array of tabs
 */
function myobjectPrepareHead($object)
{
	global $langs, $conf;

	$langs->load("mymodule@mymodule");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/mymodule/myobject_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;
	if (isset($object->fields['note_public']) || isset($object->fields['note_pricate']))
	{
		$head[$h][0] = dol_buildpath("/mymodule/myobject_note.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Notes");
		$head[$h][2] = 'note';
		$h++;
	}
	$head[$h][0] = dol_buildpath("/mymodule/myobject_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	$head[$h][2] = 'document';
	$h++;
	$head[$h][0] = dol_buildpath("/mymodule/myobject_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'myobject@mymodule');

	return $head;
}

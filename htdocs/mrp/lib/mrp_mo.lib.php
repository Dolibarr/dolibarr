<?php
/* Copyright (C) ---Put here your own copyright and developer email---
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/mrp_mo.lib.php
 * \ingroup mrp
 * \brief   Library files with common functions for Mo
 */

/**
 * Prepare array of tabs for Mo
 *
 * @param	Mo	$object		Mo
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function moPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->loadLangs(array("mrp", "stocks"));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/mrp/mo_card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("ManufacturingOrder");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/mrp/mo_production.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Production");
	$arrayproduced = $object->fetchLinesLinked('produced', 0);
	$nbProduced = 0;
	foreach ($arrayproduced as $lineproduced) {
		$nbProduced += $lineproduced['qty'];
	}
	$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbProduced . ' / ' . $object->qty . '</span>';
	$head[$h][2] = 'production';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/mrp/mo_movements.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("StockMovements");
	$nbMove = $object->countMovements();
	$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbMove . '</span>';
	$head[$h][2] = 'stockmovement';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT . '/mrp/mo_note.php?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbNote . '</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->mrp->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT . '/mrp/mo_document.php?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/mrp/mo_agenda.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@mrp:/mrp/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@mrp:/mrp/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'mo@mrp');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'mo@mrp', 'remove');

	return $head;
}

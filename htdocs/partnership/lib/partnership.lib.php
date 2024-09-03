<?php
/* Copyright (C) 2021       Dorian Laurent              <i.merraha@sofimedmaroc.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file    htdocs/partnership/lib/partnership.lib.php
 * \ingroup partnership
 * \brief   Library files with common functions for Partnership
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function partnershipAdminPrepareHead()
{
	global $langs, $conf, $db;

	$langs->loadLangs(array("members", "partnership"));

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('partnership');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/partnership/admin/setup.php';
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;


	$head[$h][0] = DOL_URL_ROOT . '/partnership/admin/partnership_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['partnership']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'partnership_extrafields';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/partnership/admin/website.php';
	$head[$h][1] = $langs->trans("BlankSubscriptionForm");
	$head[$h][2] = 'website';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@partnership:/partnership/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@partnership:/partnership/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'partnership');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'partnership', 'remove');

	return $head;
}

/**
 * Prepare array of tabs for Partnership
 *
 * @param	Partnership	$object		Partnership
 * @return 	array					Array of tabs
 */
function partnershipPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("partnership");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/partnership/partnership_card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT . '/partnership/partnership_note.php?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->partnership->dir_output . "/partnership/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT . '/partnership/partnership_document.php?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/partnership/partnership_agenda.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@partnership:/partnership/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@partnership:/partnership/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'partnership');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'partnership', 'remove');

	return $head;
}

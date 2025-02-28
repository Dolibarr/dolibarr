<?php
/* Copyright (C) 2023 Alice Adminson <aadminson@example.com>
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
 * \file    htdocs/bookcal/lib/bookcal_calendar.lib.php
 * \ingroup bookcal
 * \brief   Library files with common functions for Calendar
 */

/**
 * Prepare array of tabs for Calendar
 *
 * @param	Calendar	$object		Calendar
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function calendarPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("agenda");

	$showtabofpagecontact = 0;
	$showtabofpagenote = 1;
	$showtabofpagedocument = 0;
	$showtabofpageagenda = 1;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/bookcal/calendar_card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Calendar");
	$head[$h][2] = 'card';
	$h++;

	if ($object->status == Calendar::STATUS_VALIDATED) {
		$head[$h][0] = DOL_URL_ROOT . '/bookcal/booking_list.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("Bookings");
		$head[$h][2] = 'booking';
		$h++;
	}


	if ($showtabofpagecontact) {
		$head[$h][0] = DOL_URL_ROOT . '/bookcal/calendar_contact.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("Contacts");
		$head[$h][2] = 'contact';
		$h++;
	}

	if ($showtabofpagenote) {
		if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
			$nbNote = 0;
			if (!empty($object->note_private)) {
				$nbNote++;
			}
			if (!empty($object->note_public)) {
				$nbNote++;
			}
			$head[$h][0] = DOL_URL_ROOT . '/bookcal/calendar_note.php?id=' . $object->id;
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) {
				$head[$h][1] .= (!getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
			}
			$head[$h][2] = 'note';
			$h++;
		}
	}

	if ($showtabofpagedocument) {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
		$upload_dir = $conf->bookcal->dir_output . "/calendar/" . dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = DOL_URL_ROOT . '/bookcal/calendar_document.php?id=' . $object->id;
		$head[$h][1] = $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
		}
		$head[$h][2] = 'document';
		$h++;
	}

	if ($showtabofpageagenda) {
		$head[$h][0] = DOL_URL_ROOT . '/bookcal/calendar_agenda.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("Events");
		$head[$h][2] = 'agenda';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@bookcal:/bookcal/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@bookcal:/bookcal/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'calendar@bookcal');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'calendar@bookcal', 'remove');

	return $head;
}

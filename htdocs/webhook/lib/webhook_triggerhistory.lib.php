<?php
/* Copyright (C) 2025		Alice Adminson				<myemail@mycompany.com>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 * \file    lib/webhook_triggerhistory.lib.php
 * \ingroup webhook
 * \brief   Library files with common functions for
 */

/**
 * Prepare array of tabs for
 *
 * @param	TriggerHistory	$object 	TriggerHistory
 * @return 	array<array{string,string,string}>	Array of tabs
 */
function triggerhistoryPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("webhook@webhook");

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_DOCUMENT_ROOT.'/webhook/triggerhistory_card.php', ['id' => $object->id]);
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
		$head[$h][0] = dolBuildUrl(DOL_DOCUMENT_ROOT.'/webhook/triggerhistory_note.php', ['id' => $object->id]);
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@webhook:/webhook/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@webhook:/webhook/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'triggerhistory@webhook');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'triggerhistory@webhook', 'remove');

	return $head;
}

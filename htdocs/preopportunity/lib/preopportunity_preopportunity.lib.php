<?php
/* Copyright (C) 2024 Johnson
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
 * \file    lib/preopportunity_preopportunity.lib.php
 * \ingroup preopportunity
 * \brief   Library files with common functions for PreOpportunity
 */

/**
 * Prepare array of tabs for PreOpportunity
 *
 * @param	PreOpportunity	$object		PreOpportunity
 * @return 	array					Array of tabs
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

function preopportunityPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("preopportunity@preopportunity");

	$showtabofpagecontact = 1;
	$showtabofpagenote = 1;
	$showtabofpagedocument = 1;
	$showtabofpageagenda = 1;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/preopportunity/preopportunity_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("PreOpportunity");
	$head[$h][2] = 'card';
	$h++;

	if ($showtabofpagecontact) {
		$nbContacts = 0;
		// Enable caching of preopportunity count Contacts
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_contacts_preopportunity_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);

		if (!is_null($dataretrieved)) {
			$nbContacts = $dataretrieved;
		} else {
			$nbContacts = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
			dol_setcache($cachekey, $nbContacts, 120);	// If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$h][0] = dol_buildpath("/preopportunity/preopportunity_contact.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Contacts");
		if ($nbContacts > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContacts.'</span>';
		}
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
			$head[$h][0] = dol_buildpath('/preopportunity/preopportunity_note.php', 1).'?id='.$object->id;
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) {
				$head[$h][1] .= (!getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
			}
			$head[$h][2] = 'note';
			$h++;
		}
	}

	if ($showtabofpagedocument) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->preopportunity->dir_output."/preopportunity/".dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = dol_buildpath("/preopportunity/preopportunity_document.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;
	}

	if ($showtabofpageagenda) {
		$nbEvent = 0;
		// Enable caching of preopportunity count actioncomm
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_events_preopportunity_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(id) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE elementtype = 'preopportunity@preopportunity' AND fk_element = ".((int) $object->id);
			$sql .= " AND entity IN (".getEntity('agenda').")";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbEvent = $obj->nb;
			} else {
				dol_syslog('Failed to count actioncomm '.$db->lasterror(), LOG_ERR);
			}
			dol_setcache($cachekey, $nbEvent, 120);		// If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$h][0] = dol_buildpath("/preopportunity/preopportunity_agenda.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Events");
		if ($nbEvent > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbEvent.'</span>';
		}
		$head[$h][2] = 'agenda';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@preopportunity:/preopportunity/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@preopportunity:/preopportunity/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'preopportunity@preopportunity');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'preopportunity@preopportunity', 'remove');

	return $head;
}

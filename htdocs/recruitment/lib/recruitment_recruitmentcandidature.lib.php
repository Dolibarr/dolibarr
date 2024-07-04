<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    lib/recruitment_recruitmentcandidature.lib.php
 * \ingroup recruitment
 * \brief   Library files with common functions for RecruitmentCandidature
 */

/**
 * Prepare array of tabs for RecruitmentCandidature
 *
 * @param	RecruitmentCandidature	$object		RecruitmentCandidature
 * @return 	array					Array of tabs
 */
function recruitmentCandidaturePrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("recruitment");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/recruitment/recruitmentcandidature_card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("RecruitmentCandidature");
	$head[$h][2] = 'card';
	$h++;

	//  if ($conf->global->MAIN_FEATURES_LEVEL >= 2) {
	//      $head[$h][0] = DOL_URL_ROOT.'/recruitment/recruitmentrating_card.php?id='.$object->id;
	//      $head[$h][1] = $langs->trans("Rating");
	//      $head[$h][2] = 'rating';
	//      $h++;
	//  }

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT . '/recruitment/recruitmentcandidature_note.php?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbNote . '</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->recruitment->dir_output . "/recruitmentcandidature/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT . '/recruitment/recruitmentcandidature_document.php?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/recruitment/recruitmentcandidature_agenda.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@recruitment:/recruitment/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@recruitment:/recruitment/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'recruitmentcandidature');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'recruitmentcandidature', 'remove');

	return $head;
}

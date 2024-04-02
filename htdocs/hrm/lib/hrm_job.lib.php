<?php
/* Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Grégory BLEMAND <gregory.blemand@atm-consulting.fr>
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
 * \file    lib/hrm_job.lib.php
 * \ingroup hrm
 * \brief   Library files with common functions for Job
 */

/**
 * Prepare array of tabs for Job
 *
 * @param	Job		$object		Job
 * @return 	array				Array of tabs
 */
function jobPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("hrm");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/hrm/job_card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("JobProfile");
	$head[$h][2] = 'job_card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/hrm/skill_tab.php?id=".$object->id.'&objecttype=job';
	$head[$h][1] = $langs->trans("RequiredSkills");
	$nbResources = 0;
	$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."hrm_skillrank WHERE objecttype = 'job' AND fk_object = ".((int) $object->id);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$nbResources = $obj->nb;
		}
	}
	if ($nbResources > 0) {
		$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.($nbResources).'</span>' : '');
	}
	$head[$h][2] = 'skill_tab';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/hrm/position.php?id=".$object->id;
	$head[$h][1] = $langs->trans("PositionsWithThisProfile");
	$nbResources = 0;
	$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."hrm_job_user WHERE fk_job = ".((int) $object->id);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$nbResources = $obj->nb;
		}
	}
	if ($nbResources > 0) {
		$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.($nbResources).'</span>' : '');
	}
	$head[$h][2] = 'position';
	$h++;


	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/hrm/job_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->hrm->dir_output."/job/".dol_sanitizeFileName($object->label);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/hrm/job_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/hrm/job_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@hrm:/hrm/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@hrm:/hrm/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'job@hrm');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'job@hrm', 'remove');

	return $head;
}

<?php
/* Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Grégory BLEMAND <gregory.blemand@atm-consulting.fr>
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
 * \file    lib/hrm_evaluation.lib.php
 * \ingroup hrm
 * \brief   Library files with common functions for Evaluation
 */

/**
 * Prepare array of tabs for Evaluation
 *
 * @param	Evaluation	$object		Evaluation
 * @return 	array<array<int,string>>	Array of tabs
 */
function evaluationPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("hrm");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/hrm/evaluation_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("EvaluationCard");
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
		$head[$h][0] = dol_buildpath('/hrm/evaluation_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->hrm->dir_output."/evaluation/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/hrm/evaluation_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/hrm/evaluation_agenda.php", 1).'?id='.$object->id;
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
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'evaluation@hrm');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'evaluation@hrm', 'remove');

	return $head;
}

/**
 * @return string
 */
function GetLegendSkills()
{
	global $langs;
	$legendSkills = '<div style="font-style:italic;">
		' . $langs->trans('legend') . '
		<table class="border" width="100%">
			<tr>
				<td><span style="vertical-align:middle" class="toohappy diffnote little"></span>
				' . $langs->trans('CompetenceAcquiredByOneOrMore') . '</td>
			</tr>
			<tr>
				<td><span style="vertical-align:middle" class="veryhappy diffnote little"></span>
					' . $langs->trans('MaxlevelGreaterThan') . '</td>
			</tr>
			<tr>
				<td><span style="vertical-align:middle" class="happy diffnote little"></span>
					' . $langs->trans('MaxLevelEqualTo') . '</td>
			</tr>
			<tr>
				<td><span style="vertical-align:middle" class="sad diffnote little"></span>
					' . $langs->trans('MaxLevelLowerThan') . '</td>
			</tr>
			<tr>
				<td><span style="vertical-align:middle" class="toosad diffnote little"></span>
					' . $langs->trans('SkillNotAcquired') . '</td>
			</tr>
		</table>
</div>';
	return $legendSkills;
}

/**
 * @param  Object $obj Object needed to be represented
 * @return string
 */
function getRankOrderResults($obj)
{
	global $langs;

	$results = array(
		'greater' => array(
			'title' => $langs->trans('MaxlevelGreaterThanShort'),
			'style' => 'background-color: #c3e6cb; border:5px solid #3097D1; color: #555; font-weight: 700;'
		),
		'equal' => array(
			'title' => $langs->trans('MaxLevelEqualToShort'),
			'style' => 'background-color: #c3e6cb; color: #555; font-weight: 700;'
		),
		'lesser' => array(
			'title' => $langs->trans('MaxLevelLowerThanShort'),
			'style' => 'background-color: #bd4147; color: #FFFFFF; font-weight: 700;'
		)
	);
	$key = '';
	if ($obj->rankorder > $obj->required_rank) {
		$key = 'greater';
	} elseif ($obj->rankorder == $obj->required_rank) {
		$key = 'equal';
	} elseif ($obj->rankorder < $obj->required_rank) {
		$key = 'lesser';
	}
	return '<span title="'.dol_escape_htmltag($obj->label).': ' .$results[$key]['title']. '" class="radio_js_bloc_number TNote_1" style="' . dol_escape_htmltag($results[$key]['style']) . '">' . dol_trunc($obj->label, 4).'</span>';
}

/**
 * Grouped rows with same ref in array
 *
 * @param   Object[]		$objects	All rows retrieved from sql query
 * @return	array<string|int,Object|Object[]>|int<-1,-1>	Object by group, -1 if error (empty or bad argument)
 */
function getGroupedEval($objects)
{
	if (count($objects) < 0 || !is_array($objects)) {
		return -1;
	}
	// grouped $object by ref
	$grouped = [];
	foreach ($objects as $object) {
		$ref = $object->ref;
		if (!isset($grouped[$ref])) {
			$grouped[$ref] = [];
		}
		$grouped[$ref][] = $object;
	}
	$newArray = [];
	foreach ($grouped as $refs => $objects) {
		if (count($objects) > 1) {
			$newArray[$refs] = $objects;
		}
	}
	$combinedArray = [];
	foreach ($grouped as $refs => $objects) {
		if (count($objects) == 1) {
			$combinedArray[] = $objects[0];
		}
	}
	$resultArray = array_merge($combinedArray, array_values($newArray));
	return $resultArray;
}

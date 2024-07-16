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
 * \file    lib/hrm_skillrank.lib.php
 * \ingroup hrm
 * \brief   Library files with common functions for SkillRank
 */

/**
 * Prepare array of tabs for SkillRank
 *
 * @param	SkillRank	$object		SkillRank
 * @return 	array					Array of tabs
 */
function skillrankPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("hrm");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/hrm/skillrank_card.php", 1).'?id='.$object->id;
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
		$head[$h][0] = dol_buildpath('/hrm/skillrank_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->hrm->dir_output."/skillrank/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/hrm/skillrank_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/hrm/skillrank_agenda.php", 1).'?id='.$object->id;
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
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'skillrank@hrm');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'skillrank@hrm', 'remove');

	return $head;
}

/**
 * Used to print ranks of a skill into several case, view or edit pour js necessary to select a rank
 *
 * @param int $selected_rank rank we want to preselect
 * @param int $fk_skill id of skill we display ranks
 * @param string $inputname html name of input
 * @param string $mode view or edit
 *
 * @return string string result
 */
function displayRankInfos($selected_rank, $fk_skill, $inputname = 'TNote', $mode = 'view')
{
	global $db, $conf, $langs;

	require_once DOL_DOCUMENT_ROOT . '/hrm/class/skill.class.php';
	require_once DOL_DOCUMENT_ROOT . '/hrm/class/skilldet.class.php';

	// On charge les différentes notes possibles pour la compétence $fk_skill
	$skilldet = new Skilldet($db);
	$Lines = $skilldet->fetchAll('ASC', 'rankorder', 0, 0, '(fk_skill:=:'.((int) $fk_skill).')');

	if (!is_array($Lines) && $Lines<0) {
		setEventMessages($skilldet->error, $skilldet->errors, 'errors');
	}
	if (empty($Lines)) {
		return $langs->trans('SkillHasNoLines');
	}

	$ret = '<!-- field jquery --><span title="'.$langs->trans('NA').'" class="radio_js_bloc_number '.$inputname.'_'.$fk_skill.(empty($selected_rank) ? ' selected' : '').'">';
	$ret .= $langs->trans('NA');
	$ret .= '</span>';
	if (is_array($Lines) && !empty($Lines)) {
		foreach ($Lines as $line) {
			$MaxNumberSkill = isset($conf->global->HRM_MAXRANK) ? $conf->global->HRM_MAXRANK : Skill::DEFAULT_MAX_RANK_PER_SKILL;
			if ($line->rankorder > $MaxNumberSkill) {
				continue;
			}

			$ret .= '<span title="' . $line->description . '" class="radio_js_bloc_number ' . $inputname . '_' . $line->fk_skill;
			$ret .= $line->rankorder == $selected_rank ? ' selected' : '';
			$ret .= '">' . $line->rankorder . '</span>';
		}

		if ($mode == 'edit') {
			$ret .= '
		<input type="hidden" id="' . $inputname . '_' . $fk_skill . '" name="' . $inputname . '[' . $fk_skill . ']" value="' . $selected_rank . '">
		<script type="text/javascript">
			$(document).ready(function(){
				$(".radio_js_bloc_number").tooltip();
				var error,same;
				$(".' . $inputname . '_' . $fk_skill . '").on("click",function(){
					same=false;
					val = $(this).html();
					if($(this).hasClass("selected"))same=true;
					$(".' . $inputname . '_' . $fk_skill . '").removeClass("selected");
					if(same)
					{
						$("#' . $inputname . '_' . $fk_skill . '").val("");
					}else {
						$(this).addClass("selected");
						$("#' . $inputname . '_' . $fk_skill . '").val(val);
					}
				});

			});
		</script>';
		}
	}

	return $ret;
}

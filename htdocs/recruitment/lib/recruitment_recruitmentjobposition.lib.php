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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/recruitment_recruitmentjobposition.lib.php
 * \ingroup recruitment
 * \brief   Library files with common functions for RecruitmentJobPosition
 */

/**
 * Prepare array of tabs for RecruitmentJobPosition
 *
 * @param	RecruitmentJobPosition	$object		RecruitmentJobPosition
 * @return 	array					Array of tabs
 */
function recruitmentjobpositionPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("recruitment");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/recruitment/recruitmentjobposition_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("PositionToBeFilled");
	$head[$h][2] = 'card';
	$h++;

	if ($conf->global->MAIN_FEATURES_LEVEL >= 1) {
		$head[$h][0] = dol_buildpath("/recruitment/recruitmentjobposition_applications.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Candidatures");
		$head[$h][2] = 'candidatures';
		$h++;
	}

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/recruitment/recruitmentjobposition_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->recruitment->dir_output."/recruitmentjobposition/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/recruitment/recruitmentjobposition_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/recruitment/recruitmentjobposition_agenda.php", 1).'?id='.$object->id;
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
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'recruitmentjobposition');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'recruitmentjobposition', 'remove');

	return $head;
}


/**
 * Return string with full Url
 *
 * @param   int		$mode		      0=True url, 1=Url formated with colors
 * @param	string	$ref		      Ref of object
 * @param   string  $localorexternal  0=Url for browser, 1=Url for external access
 * @return	string				      Url string
 */
function getPublicJobPositionUrl($mode, $ref = '', $localorexternal = 0)
{
	global $conf, $dolibarr_main_url_root;

	$ref = str_replace(' ', '', $ref);
	$out = '';

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$urltouse = DOL_MAIN_URL_ROOT;
	if ($localorexternal) {
		$urltouse = $urlwithroot;
	}

	$out = $urltouse.'/public/recruitment/view.php?ref='.($mode ? '<span style="color: #666666">' : '').$ref.($mode ? '</span>' : '');
	/*if (!empty($conf->global->RECRUITMENT_SECURITY_TOKEN))
	{
		if (empty($conf->global->RECRUITMENT_SECURITY_TOKEN)) $out .= '&securekey='.urlencode($conf->global->RECRUITMENT_SECURITY_TOKEN);
		else $out .= '&securekey='.urlencode(dol_hash($conf->global->RECRUITMENT_SECURITY_TOKEN, 2));
	}*/

	// For multicompany
	if (!empty($out) && !empty($conf->multicompany->enabled)) {
		$out .= "&entity=".$conf->entity; // Check the entity because we may have the same reference in several entities
	}

	return $out;
}

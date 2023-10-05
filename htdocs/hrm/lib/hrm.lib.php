<?php
/* Copyright (C) 2020 jean-pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Grégory BLEMAND <gregory.blemand@atm-consulting.fr>
 * Copyright (C) 2022       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file    hrm/lib/hrm.lib.php
 * \ingroup hrm
 * \brief   Library files with common functions for Workstation
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function hrmAdminPrepareHead()
{
	global $langs, $conf, $db;

	$langs->load("hrm");

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('hrm_evaluation');
	$extrafields->fetch_name_optionals_label('hrm_job');
	$extrafields->fetch_name_optionals_label('hrm_skill');

	$h = 0;
	$head = array();
	$head[$h][0] = DOL_URL_ROOT . "/admin/hrm.php";
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/hrm/admin/admin_establishment.php';
	$head[$h][1] = $langs->trans("Establishments");
	$head[$h][2] = 'establishments';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/hrm/admin/skill_extrafields.php';
	$head[$h][1] = $langs->trans("SkillsExtraFields");
	$nbExtrafields = $extrafields->attributes['hrm_skill']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'skillsAttributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/hrm/admin/job_extrafields.php';
	$head[$h][1] = $langs->trans("JobsExtraFields");
	$nbExtrafields = $extrafields->attributes['hrm_job']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'jobsAttributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/hrm/admin/evaluation_extrafields.php';
	$head[$h][1] = $langs->trans("EvaluationsExtraFields");
	$nbExtrafields = $extrafields->attributes['hrm_evaluation']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'evaluationsAttributes';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@workstation:/workstation/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@workstation:/workstation/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'hrm_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'hrm_admin', 'remove');

	return $head;
}

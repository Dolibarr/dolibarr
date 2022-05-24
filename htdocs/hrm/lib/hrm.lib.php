<?php
/* Copyright (C) 2020 jean-pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
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
 * \file    hrm/lib/hrm.lib.php
 * \ingroup hr
 * \brief   Library files with common functions for Workstation
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function hrmAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("hrm");

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

		$head[$h][0] = DOL_URL_ROOT . '/hrm/admin/evaluation_extrafields.php';
		$head[$h][1] = $langs->trans("EvaluationsExtraFields");
		$head[$h][2] = 'evaluationsAttributes';
		$h++;

		$head[$h][0] = DOL_URL_ROOT . '/hrm/admin/job_extrafields.php';
		$head[$h][1] = $langs->trans("JobsExtraFields");
		$head[$h][2] = 'jobsAttributes';
		$h++;

		$head[$h][0] = DOL_URL_ROOT . '/hrm/admin/skill_extrafields.php';
		$head[$h][1] = $langs->trans("SkillsExtraFields");
		$head[$h][2] = 'skillsAttributes';
		$h++;

	/*
	$head[$h][0] = dol_buildpath("/workstation/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	/*$head[$h][0] = require_once "/admin/about.php";
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;*/

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

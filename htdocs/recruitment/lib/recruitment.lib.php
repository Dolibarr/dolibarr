<?php
/* Copyright (C) 2019   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2022-2024  Frédéric France         <frederic.france@free.fr>
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
 * \file    htdocs/recruitment/lib/recruitment.lib.php
 * \ingroup recruitment
 * \brief   Library files with common functions for Recruitment
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function recruitmentAdminPrepareHead()
{
	global $langs, $conf, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('recruitment_recruitmentjobposition');
	$extrafields->fetch_name_optionals_label('recruitment_recruitmentcandidature');

	$langs->load("recruitment");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/recruitment/admin/setup.php';
	$head[$h][1] = $langs->trans("JobPositions");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/recruitment/admin/setup_candidatures.php';
	$head[$h][1] = $langs->trans("RecruitmentCandidatures");
	$head[$h][2] = 'settings_candidatures';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/recruitment/admin/public_interface.php';
	$head[$h][1] = $langs->trans("PublicUrl");
	$head[$h][2] = 'publicurl';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/recruitment/admin/jobposition_extrafields.php';
	$head[$h][1] = $langs->trans("ExtrafieldsJobPosition");
	$nbExtrafields = $extrafields->attributes['recruitment_recruitmentjobposition']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'jobposition_extrafields';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/recruitment/admin/candidature_extrafields.php';
	$head[$h][1] = $langs->trans("ExtrafieldsApplication");
	$nbExtrafields = $extrafields->attributes['recruitment_recruitmentcandidature']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'candidature_extrafields';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@recruitment:/recruitment/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@recruitment:/recruitment/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'recruitment');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'recruitment', 'remove');

	return $head;
}

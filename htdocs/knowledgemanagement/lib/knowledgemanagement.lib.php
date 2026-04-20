<?php
/* Copyright (C) 2021 SuperAdmin
 * Copyright (C) 2024		MDW	<mdeweerd@users.noreply.github.com>
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
 * \file    htdocs/knowledgemanagement/lib/knowledgemanagement.lib.php
 * \ingroup knowledgemanagement
 * \brief   Library files with common functions for KnowledgeManagement
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{0:string,1:string,2:string}>
 */
function knowledgemanagementAdminPrepareHead()
{
	global $langs, $conf, $db;

	$langs->load("knowledgemanagement");

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('knowledgemanagement_knowledgerecord');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/knowledgemanagement.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'setup';
	$h++;


	$head[$h][0] = DOL_URL_ROOT.'/admin/knowledgerecord_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['knowledgemanagement_knowledgerecord']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'extra';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@knowledgemanagement:/knowledgemanagement/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@knowledgemanagement:/knowledgemanagement/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'knowledgemanagement');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'knowledgemanagement', 'remove');

	return $head;
}

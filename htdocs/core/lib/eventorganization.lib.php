<?php
/* Copyright (C) 2021		Florian Henry			<florian.henry@scopen.fr>
 * Copyright (C) 2023       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * \file    htdocs/core/lib/eventorganization.lib.php
 * \ingroup eventorganization
 * \brief   Library files with common functions for EventOrganization
 */

/**
 * Prepare admin pages header
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function eventorganizationAdminPrepareHead()
{
	global $langs, $conf, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('actioncomm');
	$extrafields->fetch_name_optionals_label('eventorganization_conferenceorboothattendee');

	$langs->load("eventorganization");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/eventorganization.php';
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;


	$head[$h][0] = DOL_URL_ROOT.'/admin/eventorganization_confbooth_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields")." (".$langs->trans("EventOrganizationConfOrBooth").")";
	$nbExtrafields = $extrafields->attributes['actioncomm']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'eventorganization_extrafields';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/eventorganization_confboothattendee_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields")." (".$langs->trans("Attendees").")";
	$nbExtrafields = $extrafields->attributes['eventorganization_conferenceorboothattendee']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'conferenceorboothattendee_extrafields';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@eventorganization:/eventorganization/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@eventorganization:/eventorganization/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'eventorganization');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'eventorganization', 'remove');

	return $head;
}

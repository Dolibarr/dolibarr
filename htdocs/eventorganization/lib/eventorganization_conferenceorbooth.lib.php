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
 * \file    lib/eventorganization_conferenceorbooth.lib.php
 * \ingroup eventorganization
 * \brief   Library files with common functions for ConferenceOrBooth
 */

/**
 * Prepare array of tabs for ConferenceOrBooth
 *
 * @param	ConferenceOrBooth	$object		ConferenceOrBooth
 * @param	int	$with_project		Add project id to URL
 * @return 	array					Array of tabs
 */
function conferenceorboothPrepareHead($object, $with_project = 0)
{
	global $db, $langs, $conf;

	$langs->load("eventorganization");

	$h = 0;
	$head = array();

	$withProjectUrl='';
	if ($with_project>0) {
		$withProjectUrl = "&withproject=1";
	}

	$head[$h][0] = DOL_URL_ROOT.'/eventorganization/conferenceorbooth_card.php?id='.$object->id.$withProjectUrl;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (!empty($conf->global->MAIN_FEATURES_LEVEL) && $conf->global->MAIN_FEATURES_LEVEL >= 2) {
		$head[$h][0] = DOL_URL_ROOT.'/eventorganization/conferenceorbooth_contact.php?id='.$object->id.$withProjectUrl;
		$head[$h][1] = $langs->trans("ContactsAddresses");
		$head[$h][2] = 'contact';
		$h++;
	}

	/*
	$head[$h][0] = DOL_URL_ROOT.'/eventorganization/conferenceorboothattendee_list.php?conforboothid='.$object->id.$withProjectUrl;
	$head[$h][1] = $langs->trans("Attendees");
	$head[$h][2] = 'attendees';
	// Enable caching of conf or booth count attendees
	$nbAttendees = 0;
	require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
	$cachekey = 'count_attendees_conferenceorbooth_'.$object->id;
	$dataretrieved = dol_getcache($cachekey);
	if (!is_null($dataretrieved)) {
		$nbAttendees = $dataretrieved;
	} else {
		require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
		$attendees=new ConferenceOrBoothAttendee($db);
		$result = $attendees->fetchAll('', '', 0, 0, array('t.fk_actioncomm'=>$object->id));
		if (!is_array($result) && $result<0) {
			setEventMessages($attendees->error, $attendees->errors, 'errors');
		} else {
			$nbAttendees = count($result);
		}
		dol_setcache($cachekey, $nbAttendees, 120);	// If setting cache fails, this is not a problem, so we do not test result.
	}
	if ($nbAttendees > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbAttendees.'</span>';
	}
	$h++;
	*/

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->eventorganization->dir_output."/conferenceorbooth/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/eventorganization/conferenceorbooth_document.php", 1).'?id='.$object->id.$withProjectUrl;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@eventorganization:/eventorganization/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@eventorganization:/eventorganization/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'conferenceorbooth@eventorganization');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'conferenceorbooth@eventorganization', 'remove');

	return $head;
}

/**
 * Prepare array of tabs for ConferenceOrBooth Project tab
 *
 * @param $object Project Project
 * @return array
 */
function conferenceorboothProjectPrepareHead($object)
{

	global $db, $langs, $conf;

	$langs->load("eventorganization");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/eventorganization/conferenceorbooth_list.php", 1).'?projectid='.$object->id;
	$head[$h][1] = $langs->trans("ConferenceOrBooth");
	$head[$h][2] = 'conferenceorbooth';
	// Enable caching of conf or booth count attendees
	$nbAttendees = 0;
	$nbConferenceOrBooth= 0;
	require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
	$cachekey = 'count_conferenceorbooth_project_'.$object->id;
	$dataretrieved = dol_getcache($cachekey);
	if (!is_null($dataretrieved)) {
		$nbAttendees = $dataretrieved;
	} else {
		require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorbooth.class.php';
		$conforbooth=new ConferenceOrBooth($db);
		$result = $conforbooth->fetchAll('', '', 0, 0, array('t.fk_project'=>$object->id));
		if (!is_array($result) && $result<0) {
			setEventMessages($conforbooth->error, $conforbooth->errors, 'errors');
		} else {
			$nbConferenceOrBooth = count($result);
		}
		dol_setcache($cachekey, $nbConferenceOrBooth, 120);	// If setting cache fails, this is not a problem, so we do not test result.
	}
	if ($nbConferenceOrBooth > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbConferenceOrBooth.'</span>';
	}
	$h++;

	$head[$h][0] = dol_buildpath("/eventorganization/conferenceorboothattendee_list.php", 1).'?fk_project='.$object->id.'&withproject=1';
	$head[$h][1] = $langs->trans("Attendees");
	$head[$h][2] = 'attendees';
	// Enable caching of conf or booth count attendees
	$nbAttendees = 0;
	require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
	$cachekey = 'count_attendees_conferenceorbooth_project_'.$object->id;
	$dataretrieved = dol_getcache($cachekey);
	if (!is_null($dataretrieved)) {
		$nbAttendees = $dataretrieved;
	} else {
		require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
		$attendees=new ConferenceOrBoothAttendee($db);
		$result = $attendees->fetchAll('', '', 0, 0, array('t.fk_project'=>$object->id));
		if (!is_array($result) && $result<0) {
			setEventMessages($attendees->error, $attendees->errors, 'errors');
		} else {
			$nbAttendees = count($result);
		}
		dol_setcache($cachekey, $nbAttendees, 120);	// If setting cache fails, this is not a problem, so we do not test result.
	}
	if ($nbAttendees > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbAttendees.'</span>';
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'conferenceorboothproject@eventorganization');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'conferenceorboothproject@eventorganization', 'remove');

	return $head;
}


/**
 * Prepare array of tabs for ConferenceOrBoothAttendees
 *
 * @param	ConferenceOrBoothAttendee	$object		ConferenceOrBoothAttendee
 * @return 	array					Array of tabs
 */
function conferenceorboothAttendeePrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("eventorganization");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/eventorganization/conferenceorboothattendee_card.php?id=".((int) $object->id).($object->fk_actioncomm > 0 ? '&conforboothid='.((int) $object->fk_actioncomm) : '').($object->fk_project > 0 ? '&withproject=1&fk_project='.((int) $object->fk_project) : '');
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	//TODO : Note and docuement

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'conferenceorboothattendee@eventorganization');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'conferenceorboothattendee@eventorganization', 'remove');

	return $head;
}

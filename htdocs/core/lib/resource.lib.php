<?php
/* Module to manage locations, buildings, floors and rooms into Dolibarr ERP/CRM
 * Copyright (C) 2013       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Gilles Poirier          <gilles.poirier@netlogic.fr>
 * Copyright (C) 2023-2026  Frédéric France         <frederic.france@free.fr>
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
 *	\file		htdocs/core/lib/resource.lib.php
 *	\ingroup	resource
 *	\brief		This file is library for resource module
 */

/**
 * Prepare head for tabs
 *
 * @param	Dolresource	$object		Object
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function resource_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/resource/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ResourceCard");
	$head[$h][2] = 'resource';
	$h++;

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB') && (!getDolGlobalString('RESOURCE_HIDE_ADD_CONTACT_USER') || !getDolGlobalString('RESOURCE_HIDE_ADD_CONTACT_THIPARTY'))) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/resource/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'resource', 'add', 'core');

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/resource/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->resource->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$head[$h][0] = DOL_URL_ROOT.'/resource/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if ($nbFiles > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbFiles.'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/resource/agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
	}
	$head[$h][2] = 'agenda';
	$h++;

	/*$head[$h][0] = DOL_URL_ROOT.'/resource/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;*/

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'resource', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'resource', 'remove');

	return $head;
}

/**
 * Prepare head for admin tabs
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function resource_admin_prepare_head()
{
	global $conf, $extrafields, $langs, $user;

	$extrafields->fetch_name_optionals_label('resource');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/resource.php';
	$head[$h][1] = $langs->trans("ResourceSetup");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'resource_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/resource_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['resource']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'resource_admin', 'remove');

	return $head;
}

/**
 * Return the "busy" resource bookings that overlap a given period.
 *
 * Shared conflict-detection query used before linking a resource to an event when the option
 * RESOURCE_USED_IN_EVENT_CHECK is enabled. This function only runs the SELECT and returns the
 * conflicting rows; the caller keeps ownership of the fullday-event date adjustment, of
 * incrementing its own $error and of building the translated ErrorResourcesAlreadyInUse /
 * ErrorResourceUseInEvent message.
 *
 * The date-overlap test uses inclusive comparisons (>=, <=), exactly as the historical inline
 * copies did. Pass $resourceId to check a single resource, or $selfBusyElementId (with
 * $resourceId = 0) to reproduce the "resources currently busy for this event" IN (SELECT ...)
 * sub-query used by the update/drag flows.
 *
 * @param	int		$dateStart					Event start date (timestamp). Required.
 * @param	int		$dateEnd					Event end date (timestamp). 0 if the event has no end.
 * @param	int		$resourceId					If > 0, restrict to this single resource (er.resource_id = X).
 * @param	int		$selfBusyElementId			If > 0 and $resourceId <= 0, restrict to resources currently busy for this element id, via IN (SELECT ...).
 * @param	int		$excludeActionId			If > 0, exclude this actioncomm id (ac.id <> X) so an event does not conflict with itself.
 * @param	string	$resourceType				Value matched on er.resource_type. Default 'dolresource'.
 * @param	string	$elementType				Value matched on er.element_type and on the actioncomm element_type. Default 'action'.
 * @param	int		$matchNullEndOnEndOverlap	If 1, the "event end within booking" clause also matches bookings with a NULL end date. Set to 1 only to reproduce the update_linked_resource behavior.
 * @param	int		$limit						Max number of rows returned (0 = no limit). Does not change the block/no-block decision when >= 1.
 * @return	stdClass[]|null						Conflicting rows (rowid, r_ref, ac_id, ac_label); empty array if none; null on SQL error.
 */
function getBusyResourcesInPeriod($dateStart, $dateEnd, $resourceId = 0, $selfBusyElementId = 0, $excludeActionId = 0, $resourceType = 'dolresource', $elementType = 'action', $matchNullEndOnEndOverlap = 0, $limit = 0)
{
	global $db;

	$sql  = "SELECT er.rowid, r.ref as r_ref, ac.id as ac_id, ac.label as ac_label";
	$sql .= " FROM ".MAIN_DB_PREFIX."element_resources as er";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."resource as r ON r.rowid = er.resource_id AND er.resource_type = '".$db->escape($resourceType)."'";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm as ac ON ac.id = er.element_id AND er.element_type = '".$db->escape($elementType)."'";
	$sql .= " WHERE er.busy = 1";
	if ($resourceId > 0) {
		$sql .= " AND er.resource_id = ".((int) $resourceId);
	} elseif ($selfBusyElementId > 0) {
		$sql .= " AND er.resource_id IN (";
		$sql .= " SELECT resource_id FROM ".MAIN_DB_PREFIX."element_resources";
		$sql .= " WHERE element_id = ".((int) $selfBusyElementId);
		$sql .= " AND element_type = '".$db->escape($elementType)."'";
		$sql .= " AND busy = 1";
		$sql .= ")";
	}
	if ($excludeActionId > 0) {
		$sql .= " AND ac.id <> ".((int) $excludeActionId);
	}
	$sql .= " AND (";
	// event date start between ac.datep and ac.datep2 (if datep2 is null we consider there is no end)
	$sql .= " (ac.datep <= '".$db->idate($dateStart)."' AND (ac.datep2 IS NULL OR ac.datep2 >= '".$db->idate($dateStart)."'))";
	// event date end between ac.datep and ac.datep2
	if (!empty($dateEnd)) {
		if (!empty($matchNullEndOnEndOverlap)) {
			$sql .= " OR (ac.datep <= '".$db->idate($dateEnd)."' AND (ac.datep2 IS NULL OR ac.datep2 >= '".$db->idate($dateEnd)."'))";
		} else {
			$sql .= " OR (ac.datep <= '".$db->idate($dateEnd)."' AND (ac.datep2 >= '".$db->idate($dateEnd)."'))";
		}
	}
	// event date start before ac.datep and event date end after ac.datep2
	$sql .= " OR (";
	$sql .= "ac.datep >= '".$db->idate($dateStart)."'";
	if (!empty($dateEnd)) {
		$sql .= " AND (ac.datep2 IS NOT NULL AND ac.datep2 <= '".$db->idate($dateEnd)."')";
	}
	$sql .= ")";
	$sql .= ")";

	$sql .= $db->plimit($limit);

	$resql = $db->query($sql);
	if (!$resql) {
		return null;
	}

	$conflicts = array();
	while ($obj = $db->fetch_object($resql)) {
		$conflicts[] = $obj;
	}
	$db->free($resql);

	return $conflicts;
}

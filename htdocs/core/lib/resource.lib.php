<?php
/* Module to manage locations, buildings, floors and rooms into Dolibarr ERP/CRM
 * Copyright (C) 2013       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Gilles Poirier          <gilles.poirier@netlogic.fr>
 * Copyright (C) 2023       Frédéric France         <frederic.france@netlogic.fr>
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
 * @return	array				Array of head entries
 */
function resource_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/resource/card.php', 1).'?id='.$object->id;
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
 * @return  array               Array of head entries
 */
function resource_admin_prepare_head()
{
	global $conf, $db, $langs, $user;

	$extrafields = new ExtraFields($db);
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
 * Returns the resources marked "busy" and assigned to an event during the specified time frame.
 * You can specify an array of resources and the function will return the overlap of the two.
 * @param $dateStart string
 * @param $dateEnd string
 * @param $resource_ids array
 * @return array|bool
 */
function get_busy_resource_during(string $dateStart, string $dateEnd, $resource_ids = array())
{
	// MODIFIED CODE FROM htdocs/resource/element_resources.php
	global $db;

	$SELECT_LIMIT = $db->sanitize($db->escape(getDolGlobalString("RESOURCE_SELECT_LIMIT", 100)));
	if (!$db) {
		// false to mimic what getRows returns
		return false;
	}

	$sql  = "SELECT er.rowid, r.ref as r_ref, ac.id as ac_id, ac.label as ac_label";
	$sql .= " FROM ".MAIN_DB_PREFIX."element_resources as er";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."resource as r ON r.rowid = er.resource_id AND er.resource_type = 'dolresource'";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm as ac ON ac.id = er.element_id AND er.element_type = 'action'";
	$sql .= " WHERE er.busy = 1";

	if (!empty($resource_ids)) {
		$escaped_ids = array_map(function (int $v) : string {
			global $db;
			return $db->sanitize($db->escape($v));
		}, array_keys($resource_ids));
		$sql .= " AND er.resource_id IN (". implode(", ", $escaped_ids) . ")";
	}

	$sql .= " AND (";

	// event date start between ac.datep and ac.datep2 (if datep2 is null we consider there is no end)
	$sql .= "(ac.datep <= '".$db->idate($dateStart)."' AND (ac.datep2 IS NULL OR ac.datep2 > '".$db->idate($dateStart)."'))";

	// event date end between ac.datep and ac.datep2
	$sql .= " OR (ac.datep < '".$db->idate($dateEnd)."' AND (ac.datep2 >= '".$db->idate($dateEnd)."'))";

	// event date start before ac.datep and event date end after ac.datep2
	$sql .= " OR (ac.datep >= '".$db->idate($dateStart). "' AND (ac.datep2 IS NOT NULL AND ac.datep2 <= '".$db->idate($dateEnd)."'))";
	$sql .= ") LIMIT " . $SELECT_LIMIT;

	$result = $db->getRows($sql);
	$db->free();
	return $result;
}

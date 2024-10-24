<?php
/* Copyright (C) 2006-2007	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007		    Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2012		    Regis Houssin			    <regis.houssin@inodbox.com>
 * Copyright (C) 2016		    Gilles Poirier 		    <glgpoirier@gmail.com>
 * Copyright (C) 2018-2024	Charlene Benke 		    <charlene@patas-monkey.com>
 * Copyright (C) 2024		    MDW						        <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		    Frédéric France			  <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/fichinter.lib.php
 *	\brief      Ensemble de functions de base pour le module fichinter
 *	\ingroup    fichinter
 */

/**
 * Prepare array with list of tabs
 *
 * @param   CommonObject	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function fichinter_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("interventions");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Intervention");
	$head[$h][2] = 'card';
	$h++;

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/fichinter/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('InterventionContact');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'intervention', 'add', 'core');

	// Tab to link resources
	if (isModEnabled('resource')) {
		require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
		$objectres = new Dolresource($db);
		$linked_resources = $objectres->getElementResources('fichinter', $object->id);
		$nbResource = (is_array($linked_resources) ? count($linked_resources) : 0);
		// if (is_array($objectres->available_resources))
		// {
		// 	foreach ($objectres->available_resources as $modresources => $resources)
		// 	{
		// 		$resources=(array) $resources;  // To be sure $resources is an array
		// 		foreach($resources as $resource_obj)
		// 		{
		// 			$linked_resources = $object->getElementResources('fichinter', $object->id, $resource_obj);
		// 		}
		// 	}
		// }

		$head[$h][0] = DOL_URL_ROOT.'/resource/element_resource.php?element=fichinter&element_id='.$object->id;
		$head[$h][1] = $langs->trans("Resources");
		if ($nbResource > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbResource.'</span>';
		}
		$head[$h][2] = 'resource';
		$h++;
	}

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/fichinter/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->ficheinter->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/fichinter/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Events');
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$nbEvent = 0;
		// Enable caching of thirdparty count actioncomm
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_events_fichinter_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(id) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE fk_element = ".((int) $object->id);
			$sql .= " AND elementtype = 'fichinter'";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbEvent = $obj->nb;
			} else {
				dol_syslog('Failed to count actioncomm '.$db->lasterror(), LOG_ERR);
			}
			dol_setcache($cachekey, $nbEvent, 120);		// If setting cache fails, this is not a problem, so we do not test result.
		}

		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
		if ($nbEvent > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbEvent.'</span>';
		}
	}
	$head[$h][2] = 'agenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'intervention', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'intervention', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array<array{0:string,1:string,2:string}>	head array with tabs
 */
function fichinter_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('fichinter');
	$extrafields->fetch_name_optionals_label('fichinterdet');


	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/fichinter.php";
	$head[$h][1] = $langs->trans("Interventions");
	$head[$h][2] = 'ficheinter';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/fichinter_xcal.php";
	$head[$h][1] = $langs->trans("ExportCal");
	$head[$h][2] = 'xcal';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'fichinter_admin');

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/admin/fichinter_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['fichinter']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/admin/fichinterdet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$nbExtrafields = $extrafields->attributes['fichinterdet']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributesdet';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'fichinter_admin', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   Object  $object     Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function fichinter_rec_prepare_head($object)
{
	global $langs, $conf; //, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/card-rec.php?id='.$object->id;
	$head[$h][1] = $langs->trans("CardFichinter");
	$head[$h][2] = 'card';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'intervention-rec');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'intervention-rec', 'remove');


	return $head;
}

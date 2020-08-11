<?php
/* Module to manage locations, buildings, floors and rooms into Dolibarr ERP/CRM
 * Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016	Gilles Poirier		<glgpoirier@gmail.com>
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
 * @param	Object	$object		Object
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

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && (empty($conf->global->RESOURCE_HIDE_ADD_CONTACT_USER) || empty($conf->global->RESOURCE_HIDE_ADD_CONTACT_THIPARTY)))
	{
	    $nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
	    $head[$h][0] = DOL_URL_ROOT.'/resource/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		$head[$h][2] = 'contact';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'resource');

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$nbNote = 0;
		if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
		$head[$h][0] = DOL_URL_ROOT.'/resource/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->resource->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$head[$h][0] = DOL_URL_ROOT.'/resource/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if($nbFiles > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbFiles.'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/resource/agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (! empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read) ))
	{
		$head[$h][1].= '/';
		$head[$h][1].= $langs->trans("Agenda");
	}
	$head[$h][2] = 'agenda';
	$h++;

	/*$head[$h][0] = DOL_URL_ROOT.'/resource/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;*/

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

	global $langs, $conf, $user;

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
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'resource_admin', 'remove');

	return $head;
}

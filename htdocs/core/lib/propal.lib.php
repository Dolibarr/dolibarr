<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/core/lib/propal.lib.php
 *	\brief      Ensemble de fonctions de base pour le module propal
 *	\ingroup    propal
 */

/**
 * Prepare array with list of tabs
 *
 * @param   object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function propal_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->loadLangs(array('propal', 'compta', 'companies'));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Proposal');
	$head[$h][2] = 'comm';
	$h++;

	if ((empty($conf->commande->enabled) && ((!empty($conf->expedition->enabled) && !empty($conf->expedition_bon->enabled) && $user->rights->expedition->lire)
	    || (!empty($conf->expedition->enabled) && !empty($conf->livraison_bon->enabled) && $user->rights->expedition->livraison->lire))))
	{
		$langs->load("sendings");
		$text = '';
		$head[$h][0] = DOL_URL_ROOT.'/expedition/propal.php?id='.$object->id;
		if ($conf->expedition_bon->enabled) $text = $langs->trans("Shipment");
		if ($conf->livraison_bon->enabled)  $text .= '/'.$langs->trans("Receivings");
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
	    $nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'propal');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
	    $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		$head[$h][2] = 'note';
		$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->propal->multidir_output[$object->entity]."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    $nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'propal', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function propal_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/propal.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'propal_admin');

	$head[$h][0] = DOL_URL_ROOT.'/comm/admin/propal_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/comm/admin/propaldet_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsLines");
    $head[$h][2] = 'attributeslines';
    $h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'propal_admin', 'remove');

	return $head;
}

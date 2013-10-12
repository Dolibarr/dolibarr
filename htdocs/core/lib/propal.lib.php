<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/propal.lib.php
 *	\brief      Ensemble de fonctions de base pour le module propal
 *	\ingroup    propal
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function propal_prepare_head($object)
{
	global $langs, $conf, $user;
	$langs->load("propal");
	$langs->load("compta");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?id='.$object->id;
	$head[$h][1] = $langs->trans('ProposalCard');
	$head[$h][2] = 'comm';
	$h++;

	if ((empty($conf->commande->enabled) &&	((! empty($conf->expedition_bon->enabled) && $user->rights->expedition->lire)
	|| (! empty($conf->livraison_bon->enabled) && $user->rights->expedition->livraison->lire))))
	{
		$langs->load("sendings");
		$head[$h][0] = DOL_URL_ROOT.'/expedition/propal.php?id='.$object->id;
		if ($conf->expedition_bon->enabled) $text=$langs->trans("Shipment");
		if ($conf->livraison_bon->enabled)  $text.='/'.$langs->trans("Receivings");
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}
	if (! empty($conf->global->MAIN_USE_PREVIEW_TABS))
	{
		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/apercu.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Preview");
		$head[$h][2] = 'preview';
		$h++;
	}

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'propal');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
	    $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if($nbNote > 0) $head[$h][1].= ' ('.$nbNote.')';
		$head[$h][2] = 'note';
		$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->propal->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files'));
	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if($nbFiles > 0) $head[$h][1].= ' ('.$nbFiles.')';
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'propal','remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @param	Object	$object		Propal
 *  @return	array   	        head array with tabs
 */
function propal_admin_prepare_head($object)
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
	complete_head_from_modules($conf,$langs,$object,$head,$h,'propal_admin');

	$head[$h][0] = DOL_URL_ROOT.'/comm/admin/propal_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/comm/admin/propaldet_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsLines");
    $head[$h][2] = 'attributeslines';
    $h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'propal_admin','remove');

	return $head;
}


?>
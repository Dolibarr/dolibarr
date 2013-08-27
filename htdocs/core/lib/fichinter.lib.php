<?php
/* Copyright (C) 2006-2007	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/core/lib/fichinter.lib.php
 *	\brief      Ensemble de fonctions de base pour le module fichinter
 *	\ingroup    fichinter
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function fichinter_prepare_head($object)
{
	global $langs, $conf, $user;
	$langs->load("fichinter");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$head[$h][0] = DOL_URL_ROOT.'/fichinter/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('InterventionContact');
		$head[$h][2] = 'contact';
		$h++;
	}

	if (! empty($conf->global->MAIN_USE_PREVIEW_TABS))
	{
		$head[$h][0] = DOL_URL_ROOT.'/fichinter/apercu.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Preview');
		$head[$h][2] = 'preview';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'intervention');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
    	$head[$h][0] = DOL_URL_ROOT.'/fichinter/note.php?id='.$object->id;
    	$head[$h][1] = $langs->trans('Notes');
		if($nbNote > 0) $head[$h][1].= ' ('.$nbNote.')';
    	$head[$h][2] = 'note';
    	$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->ficheinter->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files'));
	$head[$h][0] = DOL_URL_ROOT.'/fichinter/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if($nbFiles > 0) $head[$h][1].= ' ('.$nbFiles.')';
	$head[$h][2] = 'documents';
	$h++;

    $head[$h][0] = DOL_URL_ROOT.'/fichinter/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'intervention','remove');

    return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function fichinter_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT."/admin/fichinter.php";
	$head[$h][1] = $langs->trans("Interventions");
	$head[$h][2] = 'ficheinter';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'fichinter_admin');

	$head[$h][0] = DOL_URL_ROOT.'/fichinter/admin/fichinter_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
    $h++;



	complete_head_from_modules($conf,$langs,$object,$head,$h,'fichinter_admin','remove');

		return $head;
}


?>

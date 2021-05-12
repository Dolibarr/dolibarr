<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * \file       htdocs/core/lib/contract.lib.php
 * \brief      Ensemble de fonctions de base pour le module contrat
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Contrat	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function contract_prepare_head(Contrat $object)
{
	global $db, $langs, $conf;
	
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/contrat/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ContractCard");
	$head[$h][2] = 'card';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
<<<<<<< HEAD
	    $nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
=======
	    $nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	    $head[$h][0] = DOL_URL_ROOT.'/contrat/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ContactsAddresses");
		if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
<<<<<<< HEAD
    complete_head_from_modules($conf,$langs,$object,$head,$h,'contract');
=======
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'contract');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
    	$head[$h][0] = DOL_URL_ROOT.'/contrat/note.php?id='.$object->id;
    	$head[$h][1] = $langs->trans("Notes");
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
    	$head[$h][2] = 'note';
    	$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->contrat->dir_output . "/" . dol_sanitizeFileName($object->ref);
<<<<<<< HEAD
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
=======
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    $nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/contrat/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/contrat/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

<<<<<<< HEAD
    complete_head_from_modules($conf,$langs,$object,$head,$h,'contract','remove');
=======
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'contract', 'remove');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function contract_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/contract.php";
	$head[$h][1] = $langs->trans("Contracts");
	$head[$h][2] = 'contract';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
<<<<<<< HEAD
	complete_head_from_modules($conf,$langs,null,$head,$h,'contract_admin');
=======
	complete_head_from_modules($conf, $langs, null, $head, $h, 'contract_admin');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$head[$h][0] = DOL_URL_ROOT.'/contrat/admin/contract_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/contrat/admin/contractdet_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsLines");
    $head[$h][2] = 'attributeslines';
    $h++;



<<<<<<< HEAD
	complete_head_from_modules($conf,$langs,null,$head,$h,'contract_admin','remove');

		return $head;
}

=======
	complete_head_from_modules($conf, $langs, null, $head, $h, 'contract_admin', 'remove');

		return $head;
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

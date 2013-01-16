<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function contract_prepare_head($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/contrat/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ContractCard");
	$head[$h][2] = 'card';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$head[$h][0] = DOL_URL_ROOT.'/contrat/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ContactsAddresses");
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'contract');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$head[$h][0] = DOL_URL_ROOT.'/contrat/note.php?id='.$object->id;
    	$head[$h][1] = $langs->trans("Note");
    	$head[$h][2] = 'note';
    	$h++;
    }

	$head[$h][0] = DOL_URL_ROOT.'/contrat/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/contrat/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'contract','remove');

	return $head;
}

?>
<?php
/* Copyright (C) 2006-2010	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/core/lib/contact.lib.php
 *		\brief      Ensemble de fonctions de base pour les contacts
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function contact_prepare_head($object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/contact/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (! empty($conf->ldap->enabled) && ! empty($conf->global->LDAP_CONTACT_ACTIVE))
	{
		$langs->load("ldap");

		$head[$h][0] = DOL_URL_ROOT.'/contact/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/contact/perso.php?id='.$object->id;
	$head[$h][1] = $langs->trans("PersonalInformations");
	$head[$h][2] = 'perso';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/contact/exportimport.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ExportImport");
	$head[$h][2] = 'exportimport';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'contact');

    // Notes
    $head[$h][0] = DOL_URL_ROOT.'/contact/note.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Note");
    $head[$h][2] = 'note';
    $h++;
    
    // Info
    $head[$h][0] = DOL_URL_ROOT.'/contact/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'contact','remove');

	return $head;
}

?>
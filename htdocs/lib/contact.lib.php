<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin		<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/lib/contact.lib.php
 *		\brief      Ensemble de fonctions de base pour les contacts
 *		\version    $Id$
 *
 *		Ensemble de fonctions de base de dolibarr sous forme d'include
 */

/**
 * Enter description here...
 *
 * @param   $object
 * @return  array
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

	if ($conf->ldap->enabled && $conf->global->LDAP_CONTACT_ACTIVE)
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

	$head[$h][0] = DOL_URL_ROOT.'/contact/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;
	
	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['contact']))
	{
		$i=0;
		foreach ($conf->tabs_modules['contact'] as $value)
		{
			$values=explode(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i',$object->id,$values[3]),1);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	return $head;
}

?>
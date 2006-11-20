<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/lib/ldap.lib.php
		\brief      Ensemble de fonctions de base pour le module LDAP
        \ingroup    ldap
        \version    $Revision$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function ldap_prepare_head()
{
	global $langs, $conf, $user;
	$langs->load("ldap");

	// Onglets
	$head=array();
	$h = 0;
	
	$head[$h][0] = DOL_URL_ROOT."/admin/ldap.php";
	$head[$h][1] = $langs->trans("LDAPGlobalParameters");
	$head[$h][2] = 'ldap';
	$h++;
	
	if ($conf->global->LDAP_SYNCHRO_ACTIVE)
	{
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_users.php";
		$head[$h][1] = $langs->trans("LDAPUsersSynchro");
		$head[$h][2] = 'users';
		$h++;
	}

	if ($conf->global->LDAP_SYNCHRO_ACTIVE)
	{
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_groups.php";
		$head[$h][1] = $langs->trans("LDAPGroupsSynchro");
		$head[$h][2] = 'groups';
		$h++;
	}
	
	if ($conf->global->LDAP_CONTACT_ACTIVE)
	{
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_contacts.php";
		$head[$h][1] = $langs->trans("LDAPContactsSynchro");
		$head[$h][2] = 'contacts';
		$h++;
	}
	
	if ($conf->global->LDAP_MEMBERS_ACTIVE)
	{
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_members.php";
		$head[$h][1] = $langs->trans("LDAPMembersSynchro");
		$head[$h][2] = 'members';
		$h++;
	}

	return $head;
}

?>
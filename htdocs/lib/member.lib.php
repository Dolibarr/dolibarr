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
	    \file       htdocs/lib/memeber.lib.php
		\brief      Ensemble de fonctions de base pour les adhérents
		\version    $Revision$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function member_prepare_head($member)
{
	global $langs, $conf;
	
	$h = 0;
	$head = array();
	
	$head[$h][0] = DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$member->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'general';
	$h++;
	
	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBERS_ACTIVE)
	{
		$langs->load("ldap");
		
		$head[$h][0] = DOL_URL_ROOT.'/adherents/ldap.php?id='.$member->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	return $head;
}

?>
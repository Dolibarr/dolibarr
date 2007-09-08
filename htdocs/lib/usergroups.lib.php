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
	    \file       htdocs/lib/usergroups.lib.php
		\brief      Ensemble de fonctions de base pour les utilisaterus et groupes
		\version    $Revision$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function user_prepare_head($user)
{
	global $langs, $conf;
	$langs->load("users");
	
	$h = 0;
	$head = array();
	
    $head[$h][0] = DOL_URL_ROOT.'/user/fiche.php?id='.$user->id;
    $head[$h][1] = $langs->trans("UserCard");
    $head[$h][2] = 'user';
    $h++;

	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE)
	{
		$langs->load("ldap");
	    $head[$h][0] = DOL_URL_ROOT.'/user/ldap.php?id='.$user->id;
	    $head[$h][1] = $langs->trans("LDAPCard");
	    $head[$h][2] = 'ldap';
	    $h++;
	}
	
    $head[$h][0] = DOL_URL_ROOT.'/user/perms.php?id='.$user->id;
    $head[$h][1] = $langs->trans("UserRights");
    $head[$h][2] = 'rights';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/user/param_ihm.php?id='.$user->id;
    $head[$h][1] = $langs->trans("UserGUISetup");
    $head[$h][2] = 'guisetup';
    $h++;

    if ($conf->bookmark4u->enabled)
    {
        $head[$h][0] = DOL_URL_ROOT.'/user/addon.php?id='.$user->id;
        $head[$h][1] = $langs->trans("Bookmark4u");
	    $head[$h][2] = 'bookmark4u';
        $h++;
    }

    if ($conf->clicktodial->enabled)
    {
        $head[$h][0] = DOL_URL_ROOT.'/user/clicktodial.php?id='.$user->id;
        $head[$h][1] = $langs->trans("ClickToDial");
	    $head[$h][2] = 'clicktodial';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/user/note.php?id='.$user->id;
    $head[$h][1] = $langs->trans("Note");
    $head[$h][2] = 'note';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/user/info.php?id='.$user->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;

	return $head;
}


function group_prepare_head($group)
{
	global $langs, $conf;
	
	$h = 0;
	$head = array();
	
    $head[$h][0] = DOL_URL_ROOT.'/user/group/fiche.php?id='.$group->id;
    $head[$h][1] = $langs->trans("GroupCard");
    $head[$h][2] = 'group';
    $h++;

	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE)
	{
		$langs->load("ldap");
	    $head[$h][0] = DOL_URL_ROOT.'/user/group/ldap.php?id='.$group->id;
	    $head[$h][1] = $langs->trans("LDAPCard");
	    $head[$h][2] = 'ldap';
	    $h++;
	}
	
    $head[$h][0] = DOL_URL_ROOT.'/user/group/perms.php?id='.$group->id;
    $head[$h][1] = $langs->trans("GroupRights");
    $head[$h][2] = 'rights';
    $h++;

	return $head;
}

?>

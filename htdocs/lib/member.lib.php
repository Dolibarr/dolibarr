<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/lib/member.lib.php
 *		\brief      Ensemble de fonctions de base pour les adhérents
 *		\version    $Id$
 *
 *		Ensemble de fonctions de base de dolibarr sous forme d'include
 */

function member_prepare_head($member)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$member->id;
	$head[$h][1] = $langs->trans("MemberCard");
	$head[$h][2] = 'general';
	$h++;

	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
	{
		$langs->load("ldap");

		$head[$h][0] = DOL_URL_ROOT.'/adherents/ldap.php?id='.$member->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

    if ($user->rights->adherent->cotisation->lire)
	{
		$head[$h][0] = DOL_URL_ROOT.'/adherents/card_subscriptions.php?rowid='.$member->id;
		$head[$h][1] = $langs->trans("Subscriptions");
		$head[$h][2] = 'subscription';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/adherents/note.php?id='.$member->id;
	$head[$h][1] = $langs->trans("Note");
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/adherents/info.php?id='.$member->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/dolibarr/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['member']))
	{
		$i=0;
		foreach ($conf->tabs_modules['member'] as $value)
		{
			$values=split(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = eregi_replace('__ID__',$member->id,$values[3]);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	return $head;
}

?>
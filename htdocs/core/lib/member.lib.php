<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/core/lib/member.lib.php
 *		\brief      Ensemble de fonctions de base pour les adherents
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object         Member
 *  @return array           		head
 */
function member_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("MemberCard");
	$head[$h][2] = 'general';
	$h++;

	if (! empty($conf->ldap->enabled) && ! empty($conf->global->LDAP_MEMBER_ACTIVE))
	{
		$langs->load("ldap");

		$head[$h][0] = DOL_URL_ROOT.'/adherents/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

    if (! empty($user->rights->adherent->cotisation->lire))
	{
		$head[$h][0] = DOL_URL_ROOT.'/adherents/card_subscriptions.php?rowid='.$object->id;
		$head[$h][1] = $langs->trans("Subscriptions");
		$head[$h][2] = 'subscription';
		$h++;
	}

	// Show agenda tab
	if (! empty($conf->agenda->enabled))
	{
	    $head[$h][0] = DOL_URL_ROOT."/adherents/agenda.php?id=".$object->id;
	    $head[$h][1] = $langs->trans('Agenda');
	    $head[$h][2] = 'agenda';
	    $h++;
	}

	// Show category tab
	if (! empty($conf->categorie->enabled) && ! empty($user->rights->categorie->lire))
	{
		$head[$h][0] = DOL_URL_ROOT."/categories/categorie.php?id=".$object->id.'&type=3';
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'member');

    $head[$h][0] = DOL_URL_ROOT.'/adherents/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Note");
	$head[$h][2] = 'note';
	$h++;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/document.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'document';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;


	complete_head_from_modules($conf,$langs,$object,$head,$h,'member','remove');

	return $head;
}


/**
 *  Return array head with list of tabs to view object informations
 *
 *  @return	array		head
 */
function member_admin_prepare_head()
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/adherents/admin/adherent.php';
    $head[$h][1] = $langs->trans("Miscellaneous");
    $head[$h][2] = 'general';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,'',$head,$h,'member_admin');

    $head[$h][0] = DOL_URL_ROOT.'/adherents/admin/adherent_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsMember");
    $head[$h][2] = 'attributes';
    $h++;
    
    $head[$h][0] = DOL_URL_ROOT.'/adherents/admin/adherent_type_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsMemberType");
    $head[$h][2] = 'attributes_type';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/admin/public.php';
    $head[$h][1] = $langs->trans("BlankSubscriptionForm");
    $head[$h][2] = 'public';
    $h++;

    complete_head_from_modules($conf,$langs,'',$head,$h,'member_admin','remove');

    return $head;
}


/**
 *  Return array head with list of tabs to view object stats informations
 *
 *  @param	Object	$object         Member or null
 *  @return	array           		head
 */
function member_stats_prepare_head($object)
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/adherents/stats/index.php';
    $head[$h][1] = $langs->trans("Subscriptions");
    $head[$h][2] = 'statssubscription';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/stats/geo.php?mode=memberbycountry';
    $head[$h][1] = $langs->trans("Country");
    $head[$h][2] = 'statscountry';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/stats/geo.php?mode=memberbystate';
    $head[$h][1] = $langs->trans("State");
    $head[$h][2] = 'statsstate';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/stats/geo.php?mode=memberbytown';
    $head[$h][1] = $langs->trans('Town');
    $head[$h][2] = 'statstown';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/stats/byproperties.php';
    $head[$h][1] = $langs->trans('ByProperties');
    $head[$h][2] = 'statsbyproperties';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'member_stats');

    complete_head_from_modules($conf,$langs,$object,$head,$h,'member_stats','remove');

    return $head;
}
?>

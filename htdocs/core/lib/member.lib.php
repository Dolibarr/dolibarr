<?php
/* Copyright (C) 2006-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2015-2016	Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2015		Raphaël Doursenaud	<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/lib/member.lib.php
 * \brief      Functions for module members
 */

/**
 *  Return array head with list of tabs to view object information
 *
 *  @param	Adherent	$object				Member
 *  @return array<int,array<int,string>>	head links
 */
function member_prepare_head(Adherent $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/card.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("Member");
	$head[$h][2] = 'general';
	$h++;

	if ((isModEnabled('ldap') && getDolGlobalString('LDAP_MEMBER_ACTIVE'))
		&& (!getDolGlobalString('MAIN_DISABLE_LDAP_TAB') || !empty($user->admin))) {
		$langs->load("ldap");

		$head[$h][0] = DOL_URL_ROOT.'/adherents/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	if ($user->hasRight('adherent', 'cotisation', 'lire')) {
		$nbSubscription = is_array($object->subscriptions) ? count($object->subscriptions) : 0;
		$head[$h][0] = DOL_URL_ROOT.'/adherents/subscription.php?rowid='.$object->id;
		$head[$h][1] = $langs->trans("Subscriptions");
		$head[$h][2] = 'subscription';
		if ($nbSubscription > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbSubscription.'</span>';
		}
		$h++;
	}

	if (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') == 'member') {
		if ($user->hasRight('partnership', 'read')) {
			$nbPartnership = is_array($object->partnerships) ? count($object->partnerships) : 0;
			$head[$h][0] = DOL_URL_ROOT.'/partnership/partnership_list.php?rowid='.$object->id;
			$head[$h][1] = $langs->trans("Partnerships");
			$nbNote = 0;
			$sql = "SELECT COUNT(n.rowid) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."partnership as n";
			$sql .= " WHERE fk_member = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbNote = $obj->nb;
			} else {
				dol_print_error($db);
			}
			if ($nbNote > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
			}
			$head[$h][2] = 'partnerships';
			if ($nbPartnership > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbPartnership.'</span>';
			}
			$h++;
		}
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'member', 'add', 'core');

	$nbNote = 0;
	if (!empty($object->note_private)) {
		$nbNote++;
	}
	if (!empty($object->note_public)) {
		$nbNote++;
	}
	$head[$h][0] = DOL_URL_ROOT.'/adherents/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Note");
	$head[$h][2] = 'note';
	if ($nbNote > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
	}
	$h++;

	// Attachments
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->adherent->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 1, $object, 'member');
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/adherents/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	// Show agenda tab
	$head[$h][0] = DOL_URL_ROOT.'/adherents/agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$nbEvent = 0;
		// Enable caching of thirdparty count actioncomm
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_events_member_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(id) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE elementtype = 'member' AND fk_element = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbEvent = $obj->nb;
			} else {
				dol_syslog('Failed to count actioncomm '.$db->lasterror(), LOG_ERR);
			}
			dol_setcache($cachekey, $nbEvent, 120);		// If setting cache fails, this is not a problem, so we do not test result.
		}

		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
		if ($nbEvent > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbEvent.'</span>';
		}
	}
	$head[$h][2] = 'agenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'member', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'member', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information
 *
 *  @param	AdherentType	$object         Member
 *  @return array<int,array<int,string>>	head links
 */
function member_type_prepare_head(AdherentType $object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/type.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("MemberType");
	$head[$h][2] = 'card';
	$h++;

	// Multilangs
	if (getDolGlobalInt('MAIN_MULTILANGS')) {
		$head[$h][0] = DOL_URL_ROOT."/adherents/type_translation.php?rowid=".$object->id;
		$head[$h][1] = $langs->trans("Translation");
		$head[$h][2] = 'translation';
		$h++;
	}

	if ((isModEnabled('ldap') && getDolGlobalString('LDAP_MEMBER_TYPE_ACTIVE'))
		&& (!getDolGlobalString('MAIN_DISABLE_LDAP_TAB') || !empty($user->admin))) {
		$langs->load("ldap");

		$head[$h][0] = DOL_URL_ROOT.'/adherents/type_ldap.php?rowid='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'membertype');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'membertype', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information
 *
 *  @return array<int,array<int,string>>	head links
 */
function member_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('adherent');
	$extrafields->fetch_name_optionals_label('adherent_type');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/admin/member.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/adherents/admin/member_emails.php';
	$head[$h][1] = $langs->trans("EMails");
	$head[$h][2] = 'emails';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'member_admin');

	$head[$h][0] = DOL_URL_ROOT.'/adherents/admin/member_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsMember");
	$nbExtrafields = $extrafields->attributes['adherent']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/adherents/admin/member_type_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsMemberType");
	$nbExtrafields = $extrafields->attributes['adherent_type']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes_type';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/adherents/admin/website.php';
	$head[$h][1] = $langs->trans("BlankSubscriptionForm");
	$head[$h][2] = 'website';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'member_admin', 'remove');

	return $head;
}


/**
 *  Return array head with list of tabs to view object stats information
 *
 *  @param	Adherent	$object         Member or null
 *  @return array<int,array<int,string>>	head links
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

	$head[$h][0] = DOL_URL_ROOT.'/adherents/stats/geo.php?mode=memberbyregion';
	$head[$h][1] = $langs->trans("Region");
	$head[$h][2] = 'statsregion';
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
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'member_stats');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'member_stats', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information
 *
 *  @param	Subscription	$object		Subscription
 *  @return array<int,array<int,string>>	head links
 */
function subscription_prepare_head(Subscription $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/subscription/card.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("Subscription");
	$head[$h][2] = 'general';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/adherents/subscription/info.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'subscription');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'subscription', 'remove');

	return $head;
}

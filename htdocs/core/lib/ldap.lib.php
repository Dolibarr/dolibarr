<?php
/* Copyright (C) 2006		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2021	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * \file       htdocs/core/lib/ldap.lib.php
 * \brief      Ensemble de functions de base pour le module LDAP
 * \ingroup    ldap
 */

/**
 * Initialize the array of tabs for customer invoice
 *
 * @return	array					Array of head tabs
 */
function ldap_prepare_head()
{
	global $langs, $conf, $user;

	$langs->load("ldap");

	// Onglets
	$head = array();
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT."/admin/ldap.php";
	$head[$h][1] = $langs->trans("LDAPGlobalParameters");
	$head[$h][2] = 'ldap';
	$h++;

	if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE')) {
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_users.php";
		$head[$h][1] = $langs->trans("LDAPUsersSynchro");
		$head[$h][2] = 'users';
		$h++;
	}

	if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE')) {
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_groups.php";
		$head[$h][1] = $langs->trans("LDAPGroupsSynchro");
		$head[$h][2] = 'groups';
		$h++;
	}

	if (isModEnabled("societe") && getDolGlobalString('LDAP_CONTACT_ACTIVE')) {
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_contacts.php";
		$head[$h][1] = $langs->trans("LDAPContactsSynchro");
		$head[$h][2] = 'contacts';
		$h++;
	}

	if (isModEnabled('member') && getDolGlobalString('LDAP_MEMBER_ACTIVE')) {
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_members.php";
		$head[$h][1] = $langs->trans("LDAPMembersSynchro");
		$head[$h][2] = 'members';
		$h++;
	}

	if (isModEnabled('member') && getDolGlobalString('LDAP_MEMBER_TYPE_ACTIVE')) {
		$head[$h][0] = DOL_URL_ROOT."/admin/ldap_members_types.php";
		$head[$h][1] = $langs->trans("LDAPMembersTypesSynchro");
		$head[$h][2] = 'memberstypes';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'ldap');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'ldap', 'remove');

	return $head;
}

/**
 *  Show button test LDAP synchro
 *
 *  @param	string	$butlabel		Label
 *  @param	string	$testlabel		Label
 *  @param	string	$key			Key
 *  @param	string	$dn				Dn
 *  @param	string	$objectclass	Class
 *  @return	void
 */
function show_ldap_test_button($butlabel, $testlabel, $key, $dn, $objectclass)
{
	global $langs, $conf, $user;
	//print 'key='.$key.' dn='.$dn.' objectclass='.$objectclass;

	print '<br>';
	if (!function_exists("ldap_connect")) {
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans('LDAPFunctionsNotAvailableOnPHP').'">'.$butlabel.'</a>';
	} elseif (!getDolGlobalString('LDAP_SERVER_HOST')) {
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans('LDAPSetupNotComplete').'">'.$butlabel.'</a>';
	} elseif (empty($key) || empty($dn) || empty($objectclass)) {
		$langs->load("errors");
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans('ErrorLDAPSetupNotComplete').'">'.$butlabel.'</a>';
	} else {
		print '<a class="butAction reposition" href="'.$_SERVER["PHP_SELF"].'?action='.$testlabel.'">'.$butlabel.'</a>';
	}
	print '<br><br>';
}

/**
 * Show a LDAP array into an HTML output array.
 *
 * @param	array<'count'|int|string,int|string|array>	$result	Array to show. This array is already encoded into charset_output
 * @param   int			$level		Level
 * @param   int			$count		Count
 * @param   bool		$var		Var deprecated (replaced by css oddeven)
 * @param   int<0,1>	$hide		Hide
 * @param   int			$subcount	Subcount
 * @return  int
 */
function show_ldap_content($result, $level, $count, $var, $hide = 0, $subcount = 0)
{
	$count--;
	if ($count == 0) {
		return -1; // To stop loop
	}
	if (!is_array($result)) {
		return -1;
	}

	$lastkey = array();

	foreach ($result as $key => $val) {
		if ("$key" == "objectclass") {
			continue;
		}
		if ("$key" == "count") {
			continue;
		}
		if ("$key" == "dn") {
			continue;
		}
		if (!is_array($val) && "$val" == "objectclass") {
			continue;
		}

		$lastkey[$level] = $key;

		if (is_array($val)) {
			$hide = 0;
			if (!is_numeric($key)) {
				print '<tr class="oddeven">';
				print '<td>';
				print $key;
				print '</td><td>';
				if (strtolower($key) == 'userpassword') {
					$hide = 1;
				}
			}
			show_ldap_content($val, $level + 1, $count, $var, $hide, $val["count"]);
		} elseif ($subcount) {
			$subcount--;
			$newstring = dol_htmlentitiesbr($val);
			if ($hide) {
				print preg_replace('/./i', '*', $newstring);
			} else {
				print $newstring;
			}
			print '<br>';
		}
		if (!is_array($val) && "$val" != $lastkey[$level] && !$subcount) {
			print '</td></tr>';
		}
	}
	return 1;
}

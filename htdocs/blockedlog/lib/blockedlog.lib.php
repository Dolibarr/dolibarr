<?php
/* Copyright (C) 2017 ATM Consulting <contact@atm-consulting.fr>
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
 */

/**
 *    \file       htdocs/blockedlog/lib/blockedlog.lib.php
 *    \ingroup    system
 *    \brief      Library for common blockedlog functions
 */

/**
 *  Define head array for tabs of blockedlog tools setup pages
 *
 *  @return	array<array{0:string,1:string,2:string}>	Array of head
 */
function blockedlogadmin_prepare_head()
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog.php?withtab=1";
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'blockedlog';
	$h++;

	$langs->load("blockedlog");
	$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog_list.php?withtab=1";
	$head[$h][1] = $langs->trans("BrowseBlockedLog");

	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
	$b = new BlockedLog($db);
	if ($b->alreadyUsed()) {
		$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">...</span>' : '');
	}
	$head[$h][2] = 'fingerprints';
	$h++;

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'blockedlog');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'blockedlog', 'remove');

	return $head;
}



/**
 * Return if the version is a candidate version to get the LNE certification and if the prerequisites are OK.
 * The difference between isALNECandidateVersion() and isALNERunningVersion() is that the first checks if we it has a sense or not to
 * activate the restrictions (not a strict check) and the second one is a strict check that restrictions were activated so we can't disable then after.
 *
 * @return boolean		True or false
 */
function isALNECandidateVersion()
{
	global $mysoc;

	// For Debug help: Constant set by developer to force all LNE restrictions even if country is not France so we can test them on any dev instance.
	// Note that you can force, with this option, to enabling of the LNE restrictions but you can't force the disabling of the LNE restriction.
	if (defined('CERTIF_LNE') && (int) constant('CERTIF_LNE') === 2) {
		return true;
	}
	if (preg_match('/\-/', DOL_VERSION)) {	// This is not a stable version
		return false;
	}
	if ($mysoc->country_code != 'FR') {
		return false;
	}
	if (!defined('CERTIF_LNE') || (int) constant('CERTIF_LNE') === 0) {
		return false;
	}
	if (!isModEnabled('blockedlog')) {
		return false;
	}

	return true;	// all conditions are ok to become a LNE certified version
}


/**
 * Return if the application is executed with the LNE features on.
 * This function is used to disabled some features like disabling custom receipts, or showing the mandatory information "Certified LNE"
 * on tickets when it is not true.
 *
 * @return boolean		True or false
 */
function isALNERunningVersion()
{
	// For Debug help: Constant set by developer to force all LNE restrictions even if country is not France so we can test them on any dev instance.
	// Note that you can force, with this option, to enabling of the LNE restrictions but you can't force the disabling of the LNE restriction.
	if (defined('CERTIF_LNE') && (int) constant('CERTIF_LNE') === 2) {
		return true;
	}
	if (isBlockedLogused()) {
		return true;
	}

	return false;
}

/**
 * Return if the blocked log was already used to block some events.
 *
 * @param   int<0,1>	$ignoresystem       Ignore system events for the test
 * @return 	boolean							True if blocked log was already used, false if not
 */
function isBlockedLogused($ignoresystem = 0)
{
	global $conf, $db;

	$result = true;	// by default restrictions are on, so we can't disable them

	// For the moment, we don't need this. We already have a feature that does not allow to disable the LNE rstriction by
	// adding an inalterable event in the log.
	if (!isModEnabled('blockedlog')) {
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog";
		$sql .= " WHERE entity = ".((int) $conf->entity);	// Sharing entity in blocked is disallowed
		if ($ignoresystem) {
			$sql .= " AND action NOT IN ('MODULE_SET', 'MODULE_RESET')";
		}
		$sql .= $db->plimit(1);

		$resql = $db->query($sql);
		if ($resql !== false) {
			$obj = $db->fetch_object($resql);
			if (!$obj) {
				$result = false;
			}
		} else {
			dol_print_error($db);
		}
	}

	dol_syslog("isBlockedLogused: ignoresystem=".$ignoresystem." returns ".(string) $result);

	return $result;
}

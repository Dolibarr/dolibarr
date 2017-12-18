<?php
/* Copyright (C) 2017 ATM Consulting <contact@atm-consulting.fr>
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
 */

/**
 *	\file			htdocs/blockedlog/lib/blockedlog.lib.php
 *	\ingroup		system
 *  \brief			Library for common blockedlog functions
 */

/**
 *  Define head array for tabs of blockedlog tools setup pages
 *
 *  @return			Array of head
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

	$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog_list.php?withtab=1";
	$head[$h][1] = $langs->trans("BrowseBlockedLog");

	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
	$b=new BlockedLog($db);
	if ($b->alreadyUsed())
	{
		$head[$h][1].=' <span class="badge">...</span>';
	}
	$head[$h][2] = 'fingerprints';
	$h++;

	$object=new stdClass();

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'blockedlog');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'blockedlog','remove');

    return $head;
}

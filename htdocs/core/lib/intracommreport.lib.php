<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		htdocs/core/lib/intracommreport.lib.php
 * 	\ingroup	Intracomm report
 * 	\brief		Library of intracomm report functions
 */

/**
 *	Prepare array with list of admin tabs
 *
 *	@return	array					Array of tabs to show
 */
function intracommReportAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("intracommreport");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/intracommreport/admin/intracommreport.php';
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname); to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'intracommreport_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'intracommreport_admin', 'remove');
	return $head;
}

/**
 *	Prepare array with list of tabs
 *
 *  @param   Object	$object		Object related to tabs
 *
 *	@return	array					Array of tabs to show
 */
function intracommReportPrepareHead($object)
{
	global $langs, $conf;

	$langs->load("intracommreport");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/intracommreport/card.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname); to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'intracommreport');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'intracommreport', 'remove');
	return $head;
}

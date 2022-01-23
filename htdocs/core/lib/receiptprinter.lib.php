<?php
/* Copyright (C) 2015      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 *  \file           htdocs/core/lib/receiptprinter.lib.php
 *  \ingroup        printing
 *  \brief          Library for receipt printer functions
 */



/**
 *  Define head array for tabs of receipt printer setup pages
 *
 *  @param  string  $mode       Mode
 *  @return                     Array of head
 */
function receiptprinteradmin_prepare_head($mode)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/receiptprinter.php?mode=config";
	$head[$h][1] = $langs->trans("ListPrinters");
	$head[$h][2] = 'config';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/receiptprinter.php?mode=template";
	$head[$h][1] = $langs->trans("SetupReceiptTemplate");
	$head[$h][2] = 'template';
	$h++;

	if ($mode == 'test') {
		$head[$h][0] = DOL_URL_ROOT."/admin/receiptprinter.php?mode=test";
		$head[$h][1] = $langs->trans("TargetedPrinter");
		$head[$h][2] = 'test';
		$h++;
	}


	//$object=new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);                                                   to remove a tab
	//complete_head_from_modules($conf,$langs,$object,$head,$h,'printingadmin');

	//complete_head_from_modules($conf,$langs,$object,$head,$h,'printing','remove');

	return $head;
}

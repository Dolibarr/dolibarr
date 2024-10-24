<?php
/* Copyright (C) 2009       Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2022       Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/core/lib/takepos.lib.php
 *		\brief      Library file with function for TakePOS module
 */

/**
 * Prepare array with list of tabs
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function takepos_admin_prepare_head()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/setup.php';
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'setup';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/appearance.php';
	$head[$h][1] = $langs->trans("Appearance");
	$head[$h][2] = 'appearance';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/receipt.php';
	$head[$h][1] = $langs->trans("Printers").' / '.$langs->trans("Receipt");
	$head[$h][2] = 'receipt';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/bar.php';
	$head[$h][1] = $langs->trans("BarRestaurant");
	$head[$h][2] = 'bar';
	$h++;

	$numterminals = max(1, getDolGlobalInt('TAKEPOS_NUM_TERMINALS', 1));
	for ($i = 1; $i <= $numterminals; $i++) {
		$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/terminal.php?terminal='.$i;
		$head[$h][1] = getDolGlobalString('TAKEPOS_TERMINAL_NAME_'.$i, $langs->trans("TerminalName", $i));
		$head[$h][2] = 'terminal'.$i;
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/other.php';
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'other';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'takepos_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'takepos_admin', 'remove');

	return $head;
}

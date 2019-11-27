<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * @return 	array				Array of tabs
 */
function takepos_prepare_head()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/setup.php';
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'setup';
	$h++;

	if ($conf->global->TAKEPOS_CUSTOM_RECEIPT)
	{
		$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/receipt.php';
		$head[$h][1] = $langs->trans("Receipt");
		$head[$h][2] = 'receipt';
		$h++;
	}

	$numterminals = max(1, $conf->global->TAKEPOS_NUM_TERMINALS);
	for ($i = 1; $i <= $numterminals; $i++)
	{
		$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/terminal.php?terminal='.$i;
		$head[$h][1] = $langs->trans("Terminal"). " ".$i;
		$head[$h][2] = 'terminal'.$i;
		$h++;
	}

    complete_head_from_modules($conf, $langs, null, $head, $h, 'takepos');

    return $head;
}

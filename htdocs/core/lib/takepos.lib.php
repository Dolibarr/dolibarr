<?php
/* Copyright (C) 2009       Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2022       Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026       Jose Martinez       <jose.martinez@pichinov.com>
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
		$label = getDolGlobalString('TAKEPOS_TERMINAL_NAME_'.$i, $langs->trans("TerminalName", $i));
		if (!takeposTerminalIsEnabled($i)) {
			// A disabled terminal is greyed out but still listed, so it can be enabled again
			$label = '<span class="opacitymedium">'.$label.' ('.$langs->trans("Disabled").')</span>';
		}
		$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/terminal.php?terminal='.$i;
		$head[$h][1] = $label;
		$head[$h][2] = 'terminal'.$i;
		$h++;
	}

	/*
	$head[$h][0] = DOL_URL_ROOT.'/takepos/admin/other.php';
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'other';
	$h++;
	*/

	complete_head_from_modules($conf, $langs, null, $head, $h, 'takepos_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'takepos_admin', 'remove');

	return $head;
}

/**
 * Tell if a TakePOS terminal can be used.
 *
 * The feature must first be enabled with TAKEPOS_ALLOW_TERMINAL_DISABLING, so the behaviour
 * of existing setups is strictly unchanged. A terminal is then enabled by default: the
 * constant is only recorded when the terminal
 * is explicitly disabled, so existing setups keep all their terminals available.
 * Disabling a terminal only hides it from the terminal selection: its past invoices
 * and its reference counter are left untouched.
 *
 * @param	int		$terminal	Terminal number (1 to TAKEPOS_NUM_TERMINALS)
 * @return	bool				True if the terminal can be used
 */
function takeposTerminalIsEnabled($terminal)
{
	if (!getDolGlobalInt('TAKEPOS_ALLOW_TERMINAL_DISABLING')) {
		return true;	// Feature not enabled: every terminal stays usable
	}

	return !getDolGlobalInt('TAKEPOS_TERMINAL_DISABLED_'.((int) $terminal));
}

/**
 * Return the list of the TakePOS terminals that can be used.
 *
 * @return	int[]	Array of terminal numbers that are not disabled
 */
function takeposEnabledTerminals()
{
	$result = array();
	$numterminals = max(1, getDolGlobalInt('TAKEPOS_NUM_TERMINALS', 1));
	for ($i = 1; $i <= $numterminals; $i++) {
		if (takeposTerminalIsEnabled($i)) {
			$result[] = $i;
		}
	}
	return $result;
}

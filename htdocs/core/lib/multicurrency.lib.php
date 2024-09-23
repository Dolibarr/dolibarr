<?php
/* Copyright (C) 2015	ATM Consulting	<support@atm-consulting.fr>
 * Copyright (C) 2018	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW				<mdeweerd@users.noreply.github.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/multicurrency.lib.php
 *	\ingroup	multicurency
 *	\brief		This file is an example module library
 *				Put some comments here
 */

/**
 * Prepare array with list of tabs
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function multicurrencyAdminPrepareHead()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/admin/multicurrency.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/multicurrency/multicurrency_rate.php", 1);
	$head[$h][1] = $langs->trans("TabTitleMulticurrencyRate");
	$head[$h][2] = 'ratelist';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'multicurrency');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'multicurrency', 'remove');

	return $head;
}

/**
 * Prepare array with list of currency tabs
 *
 * @param	array	$aCurrencies	Currencies array
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function multicurrencyLimitPrepareHead($aCurrencies)
{
	global $langs;

	$i = 0;
	$head = array();

	foreach ($aCurrencies as $currency) {
		$head[$i][0] = $_SERVER['PHP_SELF'].'?currencycode='.$currency;
		$head[$i][1] = $langs->trans("Currency".$currency).' ('.$langs->getCurrencySymbol($currency).')';
		$head[$i][2] = $currency;

		$i++;
	}

	return $head;
}

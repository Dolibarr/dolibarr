<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014 	   Florian Henry        <florian.henry@open-concept.pro>
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
 * 	\file		htdocs/core/lib/accounting.lib.php
 * 	\ingroup	Advanced accountancy
 * 	\brief		Library of accountancy functions
 */

/**
 *	Prepare array with list of admin tabs
 *
 *	@param	AccountingAccount	$object		Object instance we show card
 *	@return	array				Array of tabs to show
 */
function admin_accounting_prepare_head(AccountingAccount $object=null)
{
	global $langs, $conf;

	$h = 0;
	$head = array ();

	$head[$h][0] = dol_buildpath('/accountancy/admin/index.php', 1);
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h ++;

	$head[$h][0] = DOL_URL_ROOT.'/accountancy/admin/journal.php';
	$head[$h][1] = $langs->trans("Journaux");
	$head[$h][2] = 'journal';
	$h ++;

	$head[$h][0] = DOL_URL_ROOT.'/accountancy/admin/export.php';
	$head[$h][1] = $langs->trans("ExportOptions");
	$head[$h][2] = 'export';
	$h ++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname); to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'accounting_admin');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'accounting_admin', 'remove');

	return $head;
}

/**
 *	Prepare array with list of tabs
 *
 *	@param	AccountingAccount	$object		Accounting account
 *	@return	array				Array of tabs to show
 */
function accounting_prepare_head(AccountingAccount $object)
{
	global $langs, $conf;

	$h = 0;
	$head = array ();

	$head[$h][0] = DOL_URL_ROOT.'/accountancy/admin/card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h ++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname); to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'accounting_account');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'accounting_account', 'remove');

	return $head;
}

/**
 * Return accounting account without zero on the right
 *
 * @param 	string	$account		Accounting account
 * @return	string          		String without zero on the right
 */
function clean_account($account)
{
	$account = rtrim($account,"0");
	
	return $account;
}

/**
 * Return General accounting account with defined length (used for product and miscellaneous)
 *
 * @param 	string	$account		General accounting account
 * @return	string          		String with defined length
 */
function length_accountg($account)
{
	global $conf;

	if ($account < 0 || empty($account)) return '';
	
	if (! empty($conf->global->ACCOUNTING_MANAGE_ZERO)) return $account;
	
	$g = $conf->global->ACCOUNTING_LENGTH_GACCOUNT;
	if (! empty($g)) {
		// Clean parameters
		$i = strlen($account);

		if ($i >= 1) {
			while ( $i < $g ) {
				$account .= '0';

				$i ++;
			}

			return $account;
		} else {
			return $account;
		}
	} else {
		return $account;
	}
}

/**
 * Return Auxiliary accounting account of thirdparties with defined length
 *
 * @param 	string	$accounta		Auxiliary accounting account
 * @return	string          		String with defined length
 */
function length_accounta($accounta)
{
	global $conf, $langs;

	if ($accounta < 0 || empty($accounta)) return '';
	
	if (! empty($conf->global->ACCOUNTING_MANAGE_ZERO)) return $accounta;
	
	$a = $conf->global->ACCOUNTING_LENGTH_AACCOUNT;
	if (! empty($a)) {
		// Clean parameters
		$i = strlen($accounta);

		if ($i >= 1) {
			while ( $i < $a ) {
				$accounta .= '0';

				$i ++;
			}

			return $accounta;
		} else {
			return $accounta;
		}
	} else {
		return $accounta;
	}
}

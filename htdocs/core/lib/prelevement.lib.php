<?php
/* Copyright (C) 2010-2011 	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2010		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011      	Regis Houssin		<regis.houssin@inodbox.com>
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
 *	\file       htdocs/core/lib/prelevement.lib.php
 *	\brief      Ensemble de functions de base pour le module prelevement
 *	\ingroup    propal
 */


/**
 * Prepare array with list of tabs
 *
 * @param   BonPrelevement	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function prelevement_prepare_head(BonPrelevement $object)
{
	global $langs, $conf;

	$salary = $object->checkIfSalaryBonPrelevement();

	$langs->loadLangs(array("bills", "withdrawals"));

	$h = 0;
	$head = array();

	$titleoftab = "WithdrawalsReceipts";
	if ($object->type == 'bank-transfer') {
		$titleoftab = "BankTransferReceipts";
	}

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans($titleoftab);
	$head[$h][2] = 'prelevement';
	$h++;

	$titleoftab = $langs->trans("Bills");
	if ($object->type == 'bank-transfer') {
		$titleoftab = $langs->trans("SupplierBills");
	}
	if ($salary > 0) {
		$titleoftab = $langs->trans("Salaries");
	}

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$object->id;
	$head[$h][1] = $titleoftab;
	$head[$h][2] = 'invoices';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-rejet.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Rejects");
	$head[$h][2] = 'rejects';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Statistics");
	$head[$h][2] = 'statistics';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'prelevement');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'prelevement', 'remove');

	return $head;
}

/**
 *	Check need data to create standigns orders receipt file
 *
 *	@param	string	$type		'bank-transfer' or 'direct-debit'
 *
 *	@return    	int		-1 if ko 0 if ok
 */
function prelevement_check_config($type = 'direct-debit')
{
	global $conf, $db;
	if ($type == 'bank-transfer') {
		if (!getDolGlobalString('PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT')) {
			return -1;
		}
		//if (empty($conf->global->PRELEVEMENT_ICS)) return -1;
		if (!getDolGlobalString('PAYMENTBYBANKTRANSFER_USER')) {
			return -1;
		}
	} else {
		if (!getDolGlobalString('PRELEVEMENT_ID_BANKACCOUNT')) {
			return -1;
		}
		//if (empty($conf->global->PRELEVEMENT_ICS)) return -1;
		if (!getDolGlobalString('PRELEVEMENT_USER')) {
			return -1;
		}
	}
	return 0;
}

	/**
 *  Return array head with list of tabs to view object information
 *
 *  @param	BonPrelevement	$object         	Member
 *  @param  int     		$nbOfInvoices   	No of invoices
 *  @param  int     		$nbOfSalaryInvoice  No of salary invoices
 *  @return array           					head
 */
function bon_prelevement_prepare_head(BonPrelevement $object, $nbOfInvoices, $nbOfSalaryInvoice)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/create.php?type=bank-transfer';
	$head[$h][1] = ($nbOfInvoices <= 0 ? $langs->trans("Invoices") : $langs->trans("Invoices").'<span class="badge marginleftonlyshort">'.$nbOfInvoices.'</span>');
	$head[$h][2] = 'invoice';
	$h++;

	// Salaries

	$head[$h][0] = DOL_URL_ROOT."/compta/prelevement/create.php?type=bank-transfer&sourcetype=salary";
	$head[$h][1] = ($nbOfSalaryInvoice <= 0 ? $langs->trans("Salaries") : $langs->trans("Salaries").'<span class="badge marginleftonlyshort">'.$nbOfSalaryInvoice.'</span>');
	$head[$h][2] = 'salary';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'prelevement');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'prelevement', 'remove');
	return $head;
}

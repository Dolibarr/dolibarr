<?php
/* Copyright (C) 2016       Laurent Destailleur      <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2016-2018  Alexandre Spangaro       <aspangaro@zendsi.com>
=======
 * Copyright (C) 2016-2019  Alexandre Spangaro       <aspangaro@open-dsi.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * \file    htdocs/accountancy/index.php
<<<<<<< HEAD
 * \ingroup Advanced accountancy
=======
 * \ingroup Accountancy (Double entries)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * \brief   Home accounting module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta","bills","other","accountancy","loans","banks","admin","dict"));

// Security check
if ($user->societe_id > 0)
	accessforbidden();

<<<<<<< HEAD
=======
// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('accountancyindex'));

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader('', $langs->trans("AccountancyArea"));

print load_fiche_titre($langs->trans("AccountancyArea"), '', 'title_accountancy');

$step = 0;

if ($conf->accounting->enabled)
{
<<<<<<< HEAD
	print $langs->trans("AccountancyAreaDescIntro")."<br>\n";
	print "<br>\n";print "<br>\n";

	print_fiche_titre('<span class="fa fa-calendar-check-o"></span> '.$langs->trans("AccountancyAreaDescActionOnce"), '', '')."<br>\n";
=======
	print '<span class="opacitymedium">'.$langs->trans("AccountancyAreaDescIntro")."</span><br>\n";
	print "<br>\n";print "<br>\n";

	print load_fiche_titre('<span class="fa fa-calendar-check-o"></span> '.$langs->trans("AccountancyAreaDescActionOnce"), '', '')."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '<hr>';
	print "<br>\n";

	// STEPS
	$step++;
<<<<<<< HEAD
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescJournalSetup", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("AccountingJournals").'</strong>');
	print "<br>\n";
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChartModel", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Pcg_version").'</strong>');
	print "<br>\n";
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChart", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Chartofaccounts").'</strong>');
=======
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescJournalSetup", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/journals_list.php?id=35">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("AccountingJournals").'</strong>'.'</a>');
	print "<br>\n";
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChartModel", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/accountmodel.php">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("Pcg_version").'</strong>'.'</a>');
	print "<br>\n";
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChart", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/account.php">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("Chartofaccounts").'</strong>'.'</a>');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "<br>\n";

	print "<br>\n";
	print $langs->trans("AccountancyAreaDescActionOnceBis");
	print "<br>\n";
	print "<br>\n";

	$step++;
<<<<<<< HEAD
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDefault", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBank", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuBankAccounts").'</strong>')."\n";
	print "<br>\n";

	$step++;
	$textlink = '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("MenuVatAccounts").'</strong>';
=======
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDefault", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/defaultaccounts.php">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>'.'</a>');
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBank", $step, '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuBankAccounts").'</strong>'.'</a>')."\n";
	print "<br>\n";

	$step++;
	$textlink = '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=10&from=accountancy">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuVatAccounts").'</strong>'.'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescVat", $step, $textlink);
	print "<br>\n";
	if (! empty($conf->tax->enabled))
	{
<<<<<<< HEAD
	    $textlink = '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("MenuTaxAccounts").'</strong>';
=======
	     $textlink = '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=7&from=accountancy">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuTaxAccounts").'</strong>'.'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	    $step++;
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescContrib", $step, $textlink);
	    print "<br>\n";
	}
	/*if (! empty($conf->salaries->enabled))
	{
	    $step++;
<<<<<<< HEAD
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescSal", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
=======
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescSal", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").' - '.$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	    // htdocs/admin/salaries.php
	    print "<br>\n";
	    print "<br>\n";
	}*/
	if (! empty($conf->expensereport->enabled))  // TODO Move this in the default account page because this is only one accounting account per purpose, not several.
	{
	    $step++;
<<<<<<< HEAD
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescExpenseReport", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuExpenseReportAccounts").'</strong>');
=======
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescExpenseReport", $step, '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=17&from=accountancy">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuExpenseReportAccounts").'</strong>'.'</a>');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	    print "<br>\n";
	}
	/*
	if (! empty($conf->loan->enabled))
	{
	    $step++;
<<<<<<< HEAD
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescLoan", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuSpecialExpenses").'-'.$langs->transnoentitiesnoconv("Loans").'</strong> '.$langs->transnoentitiesnoconv("or").' <strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
=======
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescLoan", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuSpecialExpenses").' - '.$langs->transnoentitiesnoconv("Loans").'</strong> '.$langs->transnoentitiesnoconv("or").' <strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	    print "<br>\n";
	}
	if (! empty($conf->don->enabled))
	{
	    $step++;
<<<<<<< HEAD
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDonation", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDonationAccounts").'</strong>');
=======
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDonation", $step, '<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	    print "<br>\n";
	}
	if (! empty($conf->adherents->enabled))
	{
	    $step++;
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescSubscription", $step, '<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	    print "<br>\n";
	}*/

	$step++;
<<<<<<< HEAD
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescProd", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("ProductsBinding").'</strong>');
=======
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescProd", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/productaccount.php">'.'<strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("ProductsBinding").'</strong>'.'</a>');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "<br>\n";


	print '<br>';

<<<<<<< HEAD

	print "<br>\n";
	print_fiche_titre('<span class="fa fa-calendar"></span> '.$langs->trans("AccountancyAreaDescActionFreq"), '', '');
=======
    // Step A - E

	print "<br>\n";
	print load_fiche_titre('<span class="fa fa-calendar"></span> '.$langs->trans("AccountancyAreaDescActionFreq"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '<hr>';
	print "<br>\n";
	$step = 0;

	$langs->loadLangs(array('bills', 'trips'));

	$step++;
<<<<<<< HEAD
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("BillsCustomers"), '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("CustomersVentilation").'</strong>')."\n";
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("BillsSuppliers"), '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("SuppliersVentilation").'</strong>')."\n";
=======
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("BillsCustomers"), '<a href="'.DOL_URL_ROOT.'/accountancy/customer/index.php">'.'<strong>'.$langs->transnoentitiesnoconv("TransferInAccounting").' - '.$langs->transnoentitiesnoconv("CustomersVentilation").'</strong>'.'</a>')."\n";
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("BillsSuppliers"), '<a href="'.DOL_URL_ROOT.'/accountancy/supplier/index.php">'.'<strong>'.$langs->transnoentitiesnoconv("TransferInAccounting").' - '.$langs->transnoentitiesnoconv("SuppliersVentilation").'</strong>'.'</a>')."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "<br>\n";

	if (! empty($conf->expensereport->enabled) || ! empty($conf->deplacement->enabled))
	{
		$step++;
<<<<<<< HEAD
		print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("ExpenseReports"), '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("ExpenseReportsVentilation").'</strong>')."\n";
		print "<br>\n";
	}

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescWriteRecords", chr(64+$step), $langs->transnoentitiesnoconv("Journalization"), $langs->transnoentitiesnoconv("WriteBookKeeping"))."\n";
=======
		print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("ExpenseReports"), '<a href="'.DOL_URL_ROOT.'/accountancy/expensereport/index.php">'.'<strong>'.$langs->transnoentitiesnoconv("TransferInAccounting").' - '.$langs->transnoentitiesnoconv("ExpenseReportsVentilation").'</strong>'.'</a>')."\n";
	    print "<br>\n";
	}

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescWriteRecords", chr(64+$step), $langs->transnoentitiesnoconv("TransferInAccounting").' - '.$langs->transnoentitiesnoconv("RegistrationInAccounting"), $langs->transnoentitiesnoconv("WriteBookKeeping"))."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescAnalyze", chr(64+$step))."<br>\n";
	print "<br>\n";
}
else
{
	print $langs->trans("Module10Desc")."<br>\n";
}

<<<<<<< HEAD
=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();

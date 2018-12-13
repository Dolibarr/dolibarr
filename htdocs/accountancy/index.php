<?php
/* Copyright (C) 2016       Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2016-2018  Alexandre Spangaro       <aspangaro@zendsi.com>
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
 * \ingroup Advanced accountancy
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
	print $langs->trans("AccountancyAreaDescIntro")."<br>\n";
	print "<br>\n";print "<br>\n";

	print load_fiche_titre('<span class="fa fa-calendar-check-o"></span> '.$langs->trans("AccountancyAreaDescActionOnce"), '', '')."<br>\n";
	print '<hr>';
	print "<br>\n";

	// STEPS
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescJournalSetup", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/journals_list.php?id=35">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("AccountingJournals").'</strong>'.'</a>');
	print "<br>\n";
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChartModel", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/accountmodel.php">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Pcg_version").'</strong>'.'</a>');
	print "<br>\n";
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChart", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/account.php">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Chartofaccounts").'</strong>'.'</a>');
	print "<br>\n";

	print "<br>\n";
	print $langs->trans("AccountancyAreaDescActionOnceBis");
	print "<br>\n";
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDefault", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/defaultaccounts.php">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>'.'</a>');
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBank", $step, '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuBankAccounts").'</strong>'.'</a>')."\n";
	print "<br>\n";

	$step++;
	$textlink = '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=10&from=accountancy">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("MenuVatAccounts").'</strong>'.'</a>';
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescVat", $step, $textlink);
	print "<br>\n";
	if (! empty($conf->tax->enabled))
	{
	     $textlink = '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=7&from=accountancy">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("MenuTaxAccounts").'</strong>'.'</a>';
	    $step++;
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescContrib", $step, $textlink);
	    print "<br>\n";
	}
	/*if (! empty($conf->salaries->enabled))
	{
	    $step++;
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescSal", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	    // htdocs/admin/salaries.php
	    print "<br>\n";
	    print "<br>\n";
	}*/
	if (! empty($conf->expensereport->enabled))  // TODO Move this in the default account page because this is only one accounting account per purpose, not several.
	{
	    $step++;
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescExpenseReport", $step, '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=17&from=accountancy">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuExpenseReportAccounts").'</strong>'.'</a>');
	    print "<br>\n";
	}
	/*
	if (! empty($conf->loan->enabled))
	{
	    $step++;
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescLoan", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuSpecialExpenses").'-'.$langs->transnoentitiesnoconv("Loans").'</strong> '.$langs->transnoentitiesnoconv("or").' <strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	    print "<br>\n";
	}
	if (! empty($conf->don->enabled))
	{
	    $step++;
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDonation", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDonationAccounts").'</strong>');
	    print "<br>\n";
	}*/

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescProd", $step, '<a href="'.DOL_URL_ROOT.'/accountancy/admin/productaccount.php">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("ProductsBinding").'</strong>'.'</a>');
	print "<br>\n";


	print '<br>';


	print "<br>\n";
	print load_fiche_titre('<span class="fa fa-calendar"></span> '.$langs->trans("AccountancyAreaDescActionFreq"), '', '');
	print '<hr>';
	print "<br>\n";
	$step = 0;

	$langs->loadLangs(array('bills', 'trips'));

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("BillsCustomers"), '<a href="'.DOL_URL_ROOT.'/accountancy/customer/index.php">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("CustomersVentilation").'</strong>'.'</a>')."\n";
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("BillsSuppliers"), '<a href="'.DOL_URL_ROOT.'/accountancy/supplier/index.php">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("SuppliersVentilation").'</strong>'.'</a>')."\n";
	print "<br>\n";

	if (! empty($conf->expensereport->enabled) || ! empty($conf->deplacement->enabled))
	{
		$step++;
		print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("ExpenseReports"), '<a href="'.DOL_URL_ROOT.'/accountancy/expensereport/index.php">'.'<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("ExpenseReportsVentilation").'</strong>'.'</a>')."\n";
	    print "<br>\n";
	}

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescWriteRecords", chr(64+$step), $langs->transnoentitiesnoconv("Journalization"), $langs->transnoentitiesnoconv("WriteBookKeeping"))."\n";
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescAnalyze", chr(64+$step))."<br>\n";
	print "<br>\n";
}
else
{
	print $langs->trans("Module10Desc")."<br>\n";
}

// End of page
llxFooter();
$db->close();

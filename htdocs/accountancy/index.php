<?php
/* Copyright (C) 2016       Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2016       Alexandre Spangaro          <aspangaro@zendsi.com>
 * Copyright (C) 2019       Frédéric France             <frederic.france@netlogic.fr>
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

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");

// Security check
if ($user->societe_id > 0)
	accessforbidden();

$langs->load("admin");
$langs->load("dict");
$langs->load("bills");
$langs->load("accountancy");
$langs->load("compta");
$langs->load("banks");
$langs->load("loans");


/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader('', $langs->trans("AccountancyArea"));

print load_fiche_titre($langs->trans("AccountancyArea"), '', 'title_accountancy');
dol_fiche_head();

$step = 0;

if ($conf->accounting->enabled)
{
	print $langs->trans("AccountancyAreaDescIntro")."<br>\n";
	print "<br>\n";print "<br>\n";

	print_fiche_titre('<span class="fa fa-calendar-check-o"></span> '.$langs->trans("AccountancyAreaDescActionOnce"), '', '')."<br>\n";
	print '<hr>';
	print "<br>\n";

	// STEPS
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescJournalSetup", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("AccountingJournals").'</strong>');
	print "<br>\n";
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChartModel", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Pcg_version").'</strong>');
	print "<br>\n";
	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChart", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Chartofaccounts").'</strong>');
	print "<br>\n";

	print "<br>\n";
	print $langs->trans("AccountancyAreaDescActionOnceBis");
	print "<br>\n";
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescProd", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBank", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuBankAccounts").'</strong>')."\n";
	print "<br>\n";

	$step++;
	$textlink = '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("MenuVatAccounts").'</strong>';
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescVat", $step, $textlink);
	print "<br>\n";
	if (! empty($conf->tax->enabled))
	{
	    $textlink = '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("MenuTaxAccounts").'</strong>';
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
	    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescExpenseReport", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuExpenseReportAccounts").'</strong>');
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
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescProd", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("ProductsBinding").'</strong>');
	print "<br>\n";


	print '<br>';


	print "<br>\n";
	print_fiche_titre('<span class="fa fa-calendar"></span> '.$langs->trans("AccountancyAreaDescActionFreq"), '', '');
	print '<hr>';
	print "<br>\n";
	$step = 0;

	$langs->loadLangs(array('bills', 'trips'));

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("BillsCustomers"), '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("CustomersVentilation").'</strong>')."\n";
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("BillsSuppliers"), '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("SuppliersVentilation").'</strong>')."\n";
	print "<br>\n";

	$step++;
	print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64+$step), $langs->transnoentitiesnoconv("ExpenseReports"), '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("ExpenseReportsVentilation").'</strong>')."\n";
	print "<br>\n";

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
dol_fiche_end();

llxFooter();
$db->close();

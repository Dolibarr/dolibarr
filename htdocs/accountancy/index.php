<?php
/* Copyright (C) 2016     Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2016     Alexandre Spangaro       <aspangaro@zendsi.com>
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


/*
 * View
 */

llxHeader('', $langs->trans("AccountancyArea"));

print load_fiche_titre($langs->trans("AccountancyArea"), '', 'title_accountancy');

$step = 0;

print "<br>\n";

print $langs->trans("AccountancyAreaDescIntro")."<br>\n";
print "<br>\n";print "<br>\n";

print_fiche_titre($langs->trans("AccountancyAreaDescActionOnce"), '', 'object_calendar')."<br>\n";
print '<hr>';
print "<br>\n";

// STEPS
$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChartModel", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Pcg_version").'</strong>');
print "<br>\n";
print "<br>\n";
$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChart", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Chartofaccounts").'</strong>');
print "<br>\n";
print "<br>\n";

print $langs->trans("AccountancyAreaDescActionOnceBis");
print "<br>\n";
print "<br>\n";

$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescMisc", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>')."<br>\n";
print "<br>\n";
$step++;
$textlink = '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("MenuVatAccounts").'</strong>';
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescVat", $step, $textlink);
print "<br>\n";
print "<br>\n";
if (! empty($conf->tax->enabled))
{
    $textlink = '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("MenuTaxAccounts").'</strong>';
    $step++;
    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescContrib", $step, $textlink);
    print "<br>\n";
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
    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescExpenseReport", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuExpenseReportAccounts").'</strong>');
    print "<br>\n";
    print "<br>\n";
}
/*
if (! empty($conf->loan->enabled))
{
    $step++;
    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescLoan", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuSpecialExpenses").'-'.$langs->transnoentitiesnoconv("Loans").'</strong> '.$langs->transnoentitiesnoconv("or").' <strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
    print "<br>\n";
    print "<br>\n";
}
if (! empty($conf->don->enabled))
{
    $step++;
    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDonation", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDonationAccounts").'</strong>');
    print "<br>\n";
    print "<br>\n";
}*/
$step++;
$textlink='<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("MenuBankCash").'</strong>';
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBank", $step, $textlink);
print "<br>\n";
print "<br>\n";

$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescProd", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("ProductsBinding").'</strong>')."<br>\n";
print "<br>\n";

print "<br>\n";
print_fiche_titre($langs->trans("AccountancyAreaDescActionFreq"), '', 'object_calendarweek');
print '<hr>';
print "<br>\n";
$step = 0;

$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescCustomer", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("CustomersVentilation").'</strong>')."<br>\n";
print "<br>\n";

$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescSupplier", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("SuppliersVentilation").'</strong>')."<br>\n";
print "<br>\n";

$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescExpenseReport", $step, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy")."-".$langs->transnoentitiesnoconv("ExpenseReportsVentilation").'</strong>')."<br>\n";
print "<br>\n";

$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescWriteRecords", $step)."<br>\n";
print "<br>\n";

$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescAnalyze", $step)."<br>\n";
print "<br>\n";

llxFooter();
$db->close();

<?php
/* Copyright (C) 2016     Laurent Destailleur      <eldy@users.sourceforge.net>
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

// Validate History
$action = GETPOST('action');

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
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChartModel", $step, '<strong>'.$langs->transnoentitiesnoconv("Financial").'-'.$langs->transnoentitiesnoconv("Accountancy")."-".$langs->transnoentitiesnoconv("Pcg_version").'</strong>');
print "<br>\n";
print "<br>\n";
$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChart", $step, '<strong>'.$langs->transnoentitiesnoconv("Financial").'-'.$langs->transnoentitiesnoconv("Accountancy")."-".$langs->transnoentitiesnoconv("Chartofaccounts").'</strong>');
print "<br>\n";
print "<br>\n";
$step++;
//$textlink='<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("Modules")."-".$langs->transnoentitiesnoconv("Accountancy").'</strong>';
$textlink='<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("MenuBankCash").'</strong>';
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBank", $step, $textlink);
print "<br>\n";
print "<br>\n";
$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescVat", $step, '<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("Dictionaries")."-".$langs->transnoentitiesnoconv("DictionaryVAT").'</strong>');
print "<br>\n";
print "<br>\n";
if (! empty($conf->tax->enabled))
{
    $textlink = '<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("Dictionaries")."-".$langs->transnoentitiesnoconv("DictionarySocialContributions").'</strong>';
    $textlink.= ' '.$langs->trans("and").' ';
    $textlink.= '<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("Modules")."-".$langs->transnoentitiesnoconv("Accountancy").'</strong>';
    $step++;
    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescContrib", $step, $textlink);
    print "<br>\n";
    print "<br>\n";
}
if (! empty($conf->salaries->enabled))
{
    $step++;
    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescSal", $step, '<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("Modules")."-".$langs->transnoentitiesnoconv("Accountancy").'</strong>');
    // htdocs/admin/salaries.php
    print "<br>\n";
    print "<br>\n";
}
if (! empty($conf->loan->enabled))
{
    $step++;
    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescLoan", $step, '<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("Modules")."-".$langs->transnoentitiesnoconv("Loans").'</strong>');
    // htdocs/admin/loan.php
    print "<br>\n";
    print "<br>\n";
}
if (! empty($conf->don->enabled))
{
    $step++;
    print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDonation", $step, '<strong>'.$langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("Setup").'-'.$langs->transnoentitiesnoconv("Modules")."-".$langs->transnoentitiesnoconv("Accountancy").'</strong>');
    print "<br>\n";
    print "<br>\n";
}
// Other: bank transfer, bank accounts

print "<br>\n";
print_fiche_titre($langs->trans("AccountancyAreaDescActionFreq"), '', 'object_calendarweek');
print '<hr>';
print "<br>\n";
$step = 0;

$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescProd", $step, '<strong>'.$langs->transnoentitiesnoconv("Financial").'-'.$langs->transnoentitiesnoconv("Accountancy")."-".$langs->transnoentitiesnoconv("ProductsBinding").'</strong>')."<br>\n";
print "<br>\n";
$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescCustomer", $step, '<strong>'.$langs->transnoentitiesnoconv("Financial").'-'.$langs->transnoentitiesnoconv("Accountancy")."-".$langs->transnoentitiesnoconv("CustomersVentilation").'</strong>')."<br>\n";
print "<br>\n";
$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescSupplier", $step, '<strong>'.$langs->transnoentitiesnoconv("Financial").'-'.$langs->transnoentitiesnoconv("Accountancy")."-".$langs->transnoentitiesnoconv("SuppliersVentilation").'</strong>')."<br>\n";
print "<br>\n";
$step++;
print img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescWriteRecords", $step, '<strong>'.$langs->transnoentitiesnoconv("Financial").'-'.$langs->transnoentitiesnoconv("Accountancy")."-".$langs->transnoentitiesnoconv("SuppliersVentilation").'</strong>')."<br>\n";
print "<br>\n";



llxFooter();
$db->close();

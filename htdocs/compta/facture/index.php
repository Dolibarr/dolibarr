<?php
/* Copyright (C) 2020	Tobias Sekan	<tobias.sekan@startmail.com>
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
 */

/**
 *	\file		htdocs/compat/facture/index.php
*	\ingroup	facture
 *	\brief		Home page of customer invoices area
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';

// Security check
restrictedArea($user, 'facture');

// Load translation files required by the page
$langs->load('bills');

// Filter to show only result of one customer
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

// Maximum elements of the tables
$maxDraftCount = empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;
$maxLatestEditCount = 5;
$maxOpenCount = empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;


/*
 * View
 */

llxHeader("", $langs->trans("CustomersInvoicesArea"), "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes");

print load_fiche_titre($langs->trans("CustomersInvoicesArea"), '', 'bill');

print '<div class="fichecenter">';

print '<div class="fichethirdleft">';
$tmp = getNumberInvoicesPieChart('customers');
if ($tmp) {
	print $tmp;
	print '<br>';
}
$tmp = getCustomerInvoiceDraftTable($max, $socid);
if ($tmp) {
	print $tmp;
	print '<br>';
}

print '</div>';

print '<div class="fichetwothirdright">';
print '<div class="ficheaddleft">';

$tmp = getCustomerInvoiceLatestEditTable($maxLatestEditCount, $socid);
if ($tmp) {
	print $tmp;
	print '<br>';
}

$tmp = getCustomerInvoiceUnpaidOpenTable($max, $socid);
if ($tmp) {
	print $tmp;
	print '<br>';
}

print '</div>';
print '</div>';

print '</div>';

// End of page
llxFooter();
$db->close();

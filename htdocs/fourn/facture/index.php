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
 *	\file		htdocs/fourn/facture/index.php
*	\ingroup	facture
 *	\brief		Home page of customer invoices area
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';

// Load translation files required by the page
$langs->loadLangs(['bills', 'boxes']);

// Filter to show only result of one supplier
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

// Maximum elements of the tables
$maxDraftCount = !getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;
$maxLatestEditCount = 5;
$maxOpenCount = !getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;

// Security check
restrictedArea($user, 'fournisseur', 0, '', 'facture');


/*
 * Actions
 */

// None



/*
 * View
 */

llxHeader("", $langs->trans("SupplierInvoicesArea"), 'EN:Suppliers_Invoices|FR:FactureFournisseur|ES:Facturas_de_proveedores');

print load_fiche_titre($langs->trans("SupplierInvoicesArea"), '', 'supplier_invoice');

print '<div class="fichecenter">';

print '<div class="fichethirdleft">';
$tmp = getNumberInvoicesPieChart('suppliers');
if ($tmp) {
	print $tmp;
	print '<br>';
}

$tmp = getDraftSupplierTable($max, $socid);
if ($tmp) {
	print $tmp;
	print '<br>';
}

print '</div>';

print '<div class="fichetwothirdright">';

$tmp = getPurchaseInvoiceLatestEditTable($maxLatestEditCount, $socid);
if ($tmp) {
	print $tmp;
	print '<br>';
}

$tmp = getPurchaseInvoiceUnpaidOpenTable($max, $socid);
if ($tmp) {
	print $tmp;
	print '<br>';
}

print '</div>';

print '</div>';

// End of page
llxFooter();
$db->close();

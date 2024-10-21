<?php
/* Copyright (C) 2015-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/ajax/selectsearchbox.php
 *      \ingroup    core
 *      \brief      This script returns json array of possible searches or just set the array if called by an include
 */

// This script is called with a POST method or as an include.

if (!isset($usedbyinclude) || empty($usedbyinclude)) {
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1); // Disables token renewal
	}
	if (!defined('NOREQUIREMENU')) {
		define('NOREQUIREMENU', '1');
	}
	if (!defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', '1');
	}
	if (!defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}
	if (!defined('NOREDIRECTBYMAINTOLOGIN')) {
		// Disable redirect to main login because the selectsearch must not ask a login
		define('NOREDIRECTBYMAINTOLOGIN', '1');
	}

	$res = @include '../../main.inc.php';

	// Security check
	// None. Being connected is enough.

	top_httphead('application/json');

	if ($res == 'ERROR_NOT_LOGGED') {
		$langs->load("other");
		$arrayresult = array();
		$arrayresult['jumptologin'] = array('img' => 'object_generic', 'label' => $langs->trans("JumpToLogin"), 'text' => '<span class="fa fa-sign-in"></span> '.$langs->trans("JumpToLogin"), 'url' => DOL_URL_ROOT.'/index.php');
		print json_encode($arrayresult);
		if (is_object($db)) {
			$db->close();
		}
		exit;
	}
}


$hookmanager->initHooks(array('searchform'));

$search_boxvalue = GETPOST('q', 'restricthtml');

$arrayresult = array();

// Define $searchform

if (isModEnabled('member') && !getDolGlobalString('MAIN_SEARCHFORM_ADHERENT_DISABLED') && $user->hasRight('adherent', 'lire')) {
	$arrayresult['searchintomember'] = array('position' => 8, 'shortcut' => 'M', 'img' => 'object_member', 'label' => $langs->trans("SearchIntoMembers", $search_boxvalue), 'text' => img_picto('', 'object_member', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoMembers", $search_boxvalue), 'url' => DOL_URL_ROOT.'/adherents/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}

if (((isModEnabled('societe') && (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') || !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS'))) || isModEnabled('supplier_order') || isModEnabled('supplier_invoice') || isModEnabled('supplier_proposal')) && !getDolGlobalString('MAIN_SEARCHFORM_SOCIETE_DISABLED') && $user->hasRight('societe', 'lire')) {
	$arrayresult['searchintothirdparty'] = array('position' => 10, 'shortcut' => 'T', 'img' => 'object_company', 'label' => $langs->trans("SearchIntoThirdparties", $search_boxvalue), 'text' => img_picto('', 'object_company', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoThirdparties", $search_boxvalue), 'url' => DOL_URL_ROOT.'/societe/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}

if (isModEnabled('societe') && !getDolGlobalString('MAIN_SEARCHFORM_CONTACT_DISABLED') && $user->hasRight('societe', 'lire')) {
	$arrayresult['searchintocontact'] = array('position' => 15, 'shortcut' => 'A', 'img' => 'object_contact', 'label' => $langs->trans("SearchIntoContacts", $search_boxvalue), 'text' => img_picto('', 'object_contact', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoContacts", $search_boxvalue), 'url' => DOL_URL_ROOT.'/contact/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}

if (((isModEnabled('product') && $user->hasRight('product', 'read')) || (isModEnabled('service') && $user->hasRight('service', 'read'))) && !getDolGlobalString('MAIN_SEARCHFORM_PRODUITSERVICE_DISABLED')) {
	$arrayresult['searchintoproduct'] = array('position' => 30, 'shortcut' => 'P', 'img' => 'object_product', 'label' => $langs->trans("SearchIntoProductsOrServices", $search_boxvalue), 'text' => img_picto('', 'object_product', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoProductsOrServices", $search_boxvalue), 'url' => DOL_URL_ROOT.'/product/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
	// search on lot/serial numbers
	if (isModEnabled('productbatch')) {
		$arrayresult['searchintobatch'] = array('position' => 32, 'shortcut' => 'B', 'img' => 'object_lot', 'label' => $langs->trans("SearchIntoBatch", $search_boxvalue), 'text' => img_picto('', 'object_lot', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoBatch", $search_boxvalue), 'url' => DOL_URL_ROOT.'/product/stock/productlot_list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
	}
}

if (isModEnabled('mrp') && $user->hasRight('mrp', 'read') && !getDolGlobalString('MAIN_SEARCHFORM_MRP_DISABLED')) {
	$arrayresult['searchintomo'] = array('position' => 35, 'shortcut' => '', 'img' => 'object_mrp', 'label' => $langs->trans("SearchIntoMO", $search_boxvalue), 'text' => img_picto('', 'object_mrp', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoMO", $search_boxvalue), 'url' => DOL_URL_ROOT.'/mrp/mo_list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('project') && !getDolGlobalString('MAIN_SEARCHFORM_PROJECT_DISABLED') && $user->hasRight('projet', 'lire')) {
	$arrayresult['searchintoprojects'] = array('position' => 40, 'shortcut' => 'Q', 'img' => 'object_project', 'label' => $langs->trans("SearchIntoProjects", $search_boxvalue), 'text' => img_picto('', 'object_project', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoProjects", $search_boxvalue), 'url' => DOL_URL_ROOT.'/projet/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('project') && !getDolGlobalString('MAIN_SEARCHFORM_TASK_DISABLED') && !getDolGlobalString('PROJECT_HIDE_TASKS') && $user->hasRight('projet', 'lire')) {
	$arrayresult['searchintotasks'] = array('position' => 45, 'img' => 'object_projecttask', 'label' => $langs->trans("SearchIntoTasks", $search_boxvalue), 'text' => img_picto('', 'object_projecttask', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoTasks", $search_boxvalue), 'url' => DOL_URL_ROOT.'/projet/tasks/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}

if (isModEnabled('propal') && !getDolGlobalString('MAIN_SEARCHFORM_CUSTOMER_PROPAL_DISABLED') && $user->hasRight('propal', 'lire')) {
	$arrayresult['searchintopropal'] = array('position' => 60, 'img' => 'object_propal', 'label' => $langs->trans("SearchIntoCustomerProposals", $search_boxvalue), 'text' => img_picto('', 'object_propal', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoCustomerProposals", $search_boxvalue), 'url' => DOL_URL_ROOT.'/comm/propal/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('order') && !getDolGlobalString('MAIN_SEARCHFORM_CUSTOMER_ORDER_DISABLED') && $user->hasRight('commande', 'lire')) {
	$arrayresult['searchintoorder'] = array('position' => 70, 'img' => 'object_order', 'label' => $langs->trans("SearchIntoCustomerOrders", $search_boxvalue), 'text' => img_picto('', 'object_order', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoCustomerOrders", $search_boxvalue), 'url' => DOL_URL_ROOT.'/commande/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('shipping') && !getDolGlobalString('MAIN_SEARCHFORM_CUSTOMER_SHIPMENT_DISABLED') && $user->hasRight('expedition', 'lire')) {
	$arrayresult['searchintoshipment'] = array('position' => 80, 'img' => 'object_shipment', 'label' => $langs->trans("SearchIntoCustomerShipments", $search_boxvalue), 'text' => img_picto('', 'object_shipment', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoCustomerShipments", $search_boxvalue), 'url' => DOL_URL_ROOT.'/expedition/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('invoice') && !getDolGlobalString('MAIN_SEARCHFORM_CUSTOMER_INVOICE_DISABLED') && $user->hasRight('facture', 'lire')) {
	$arrayresult['searchintoinvoice'] = array('position' => 90, 'img' => 'object_bill', 'label' => $langs->trans("SearchIntoCustomerInvoices", $search_boxvalue), 'text' => img_picto('', 'object_bill', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoCustomerInvoices", $search_boxvalue), 'url' => DOL_URL_ROOT.'/compta/facture/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}

if (isModEnabled('supplier_proposal') && !getDolGlobalString('MAIN_SEARCHFORM_SUPPLIER_PROPAL_DISABLED') && $user->hasRight('supplier_proposal', 'lire')) {
	$arrayresult['searchintosupplierpropal'] = array('position' => 100, 'img' => 'object_supplier_proposal', 'label' => $langs->trans("SearchIntoSupplierProposals", $search_boxvalue), 'text' => img_picto('', 'object_supplier_proposal', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoSupplierProposals", $search_boxvalue), 'url' => DOL_URL_ROOT.'/supplier_proposal/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (((isModEnabled('fournisseur') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'commande', 'lire')) || (isModEnabled('supplier_order') &&  $user->hasRight('supplier_order', 'lire'))) && !getDolGlobalString('MAIN_SEARCHFORM_SUPPLIER_ORDER_DISABLED')) {
	$arrayresult['searchintosupplierorder'] = array('position' => 110, 'img' => 'object_supplier_order', 'label' => $langs->trans("SearchIntoSupplierOrders", $search_boxvalue), 'text' => img_picto('', 'object_supplier_order', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoSupplierOrders", $search_boxvalue), 'url' => DOL_URL_ROOT.'/fourn/commande/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('reception') && !getDolGlobalString('MAIN_SEARCHFORM_SUPPLIER_RECEPTION_DISABLED') && $user->hasRight('reception', 'lire')) {
	$arrayresult['searchintoreception'] = array('position'=>115, 'img'=>'object_reception', 'label'=>$langs->trans("SearchIntoSupplierReceptions", $search_boxvalue), 'text'=>img_picto('', 'object_reception', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoSupplierReceptions", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/reception/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (((isModEnabled('fournisseur') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'facture', 'lire')) || (isModEnabled('supplier_invoice') && $user->hasRight('supplier_invoice', 'lire'))) && !getDolGlobalString('MAIN_SEARCHFORM_SUPPLIER_INVOICE_DISABLED')) {
	$arrayresult['searchintosupplierinvoice'] = array('position' => 120, 'img' => 'object_supplier_invoice', 'label' => $langs->trans("SearchIntoSupplierInvoices", $search_boxvalue), 'text' => img_picto('', 'object_supplier_invoice', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoSupplierInvoices", $search_boxvalue), 'url' => DOL_URL_ROOT.'/fourn/facture/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}

// Customer payments
if (isModEnabled('invoice') && !getDolGlobalString('MAIN_SEARCHFORM_CUSTOMER_INVOICE_DISABLED') && $user->hasRight('facture', 'lire')) {
	$arrayresult['searchintocustomerpayments'] = array(
		'position' => 170,
		'img' => 'object_payment',
		'label' => $langs->trans("SearchIntoCustomerPayments", $search_boxvalue),
		'text' => img_picto('', 'object_payment', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoCustomerPayments", $search_boxvalue),
		'url' => DOL_URL_ROOT.'/compta/paiement/list.php?leftmenu=customers_bills_payment'.($search_boxvalue ? '&search_all='.urlencode($search_boxvalue) : ''));
}

// Vendor payments
if (((isModEnabled('fournisseur') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'facture', 'lire')) || (isModEnabled('supplier_invoice') && $user->hasRight('supplier_invoice', 'lire'))) && !getDolGlobalString('MAIN_SEARCHFORM_SUPPLIER_INVOICE_DISABLED')) {
	$arrayresult['searchintovendorpayments'] = array(
		'position' => 175,
		'img' => 'object_payment',
		'label' => $langs->trans("SearchIntoVendorPayments", $search_boxvalue),
		'text' => img_picto('', 'object_payment', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoVendorPayments", $search_boxvalue),
		'url' => DOL_URL_ROOT.'/fourn/paiement/list.php?leftmenu=suppliers_bills_payment'.($search_boxvalue ? '&search_all='.urlencode($search_boxvalue) : ''));
}

// Miscellaneous payments
if (isModEnabled('bank') && !getDolGlobalString('MAIN_SEARCHFORM_MISC_PAYMENTS_DISABLED') && $user->hasRight('banque', 'lire')) {
	$arrayresult['searchintomiscpayments'] = array(
		'position' => 180,
		'img' => 'object_payment',
		'label' => $langs->trans("SearchIntoMiscPayments", $search_boxvalue),
		'text' => img_picto('', 'object_payment', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoMiscPayments", $search_boxvalue),
		'url' => DOL_URL_ROOT.'/compta/bank/various_payment/list.php?leftmenu=tax_various'.($search_boxvalue ? '&search_all='.urlencode($search_boxvalue) : ''));
}

if (isModEnabled('contract') && !getDolGlobalString('MAIN_SEARCHFORM_CONTRACT_DISABLED') && $user->hasRight('contrat', 'lire')) {
	$arrayresult['searchintocontract'] = array('position' => 130, 'img' => 'object_contract', 'label' => $langs->trans("SearchIntoContracts", $search_boxvalue), 'text' => img_picto('', 'object_contract', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoContracts", $search_boxvalue), 'url' => DOL_URL_ROOT.'/contrat/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('intervention') && !getDolGlobalString('MAIN_SEARCHFORM_FICHINTER_DISABLED') && $user->hasRight('ficheinter', 'lire')) {
	$arrayresult['searchintointervention'] = array('position' => 140, 'img' => 'object_intervention', 'label' => $langs->trans("SearchIntoInterventions", $search_boxvalue), 'text' => img_picto('', 'object_intervention', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoInterventions", $search_boxvalue), 'url' => DOL_URL_ROOT.'/fichinter/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('knowledgemanagement') && !getDolGlobalString('MAIN_SEARCHFORM_KNOWLEDGEMANAGEMENT_DISABLED') && $user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
	$arrayresult['searchintoknowledgemanagement'] = array('position' => 145, 'img' => 'object_knowledgemanagement', 'label' => $langs->trans("SearchIntoKM", $search_boxvalue), 'text' => img_picto('', 'object_knowledgemanagement', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoKM", $search_boxvalue), 'url' => DOL_URL_ROOT.'/knowledgemanagement/knowledgerecord_list.php?mainmenu=ticket'.($search_boxvalue ? '&search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('ticket') && !getDolGlobalString('MAIN_SEARCHFORM_TICKET_DISABLED') && $user->hasRight('ticket', 'read')) {
	$arrayresult['searchintotickets'] = array('position' => 146, 'img' => 'object_ticket', 'label' => $langs->trans("SearchIntoTickets", $search_boxvalue), 'text' => img_picto('', 'object_ticket', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoTickets", $search_boxvalue), 'url' => DOL_URL_ROOT.'/ticket/list.php?mainmenu=ticket'.($search_boxvalue ? '&search_all='.urlencode($search_boxvalue) : ''));
}

// HR
if (isModEnabled('user') && !getDolGlobalString('MAIN_SEARCHFORM_USER_DISABLED') && $user->hasRight('user', 'user', 'lire')) {
	$arrayresult['searchintouser'] = array('position' => 200, 'shortcut' => 'U', 'img' => 'object_user', 'label' => $langs->trans("SearchIntoUsers", $search_boxvalue), 'text' => img_picto('', 'object_user', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoUsers", $search_boxvalue), 'url' => DOL_URL_ROOT.'/user/list.php'.($search_boxvalue ? '?search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('expensereport') && !getDolGlobalString('MAIN_SEARCHFORM_EXPENSEREPORT_DISABLED') && $user->hasRight('expensereport', 'lire')) {
	$arrayresult['searchintoexpensereport'] = array('position' => 210, 'img' => 'object_trip', 'label' => $langs->trans("SearchIntoExpenseReports", $search_boxvalue), 'text' => img_picto('', 'object_trip', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoExpenseReports", $search_boxvalue), 'url' => DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm'.($search_boxvalue ? '&search_all='.urlencode($search_boxvalue) : ''));
}
if (isModEnabled('holiday') && !getDolGlobalString('MAIN_SEARCHFORM_HOLIDAY_DISABLED') && $user->hasRight('holiday', 'read')) {
	$arrayresult['searchintoleaves'] = array('position' => 220, 'img' => 'object_holiday', 'label' => $langs->trans("SearchIntoLeaves", $search_boxvalue), 'text' => img_picto('', 'object_holiday', 'class="pictofixedwidth"').' '.$langs->trans("SearchIntoLeaves", $search_boxvalue), 'url' => DOL_URL_ROOT.'/holiday/list.php?mainmenu=hrm'.($search_boxvalue ? '&search_all='.urlencode($search_boxvalue) : ''));
}

// Execute hook addSearchEntry
$parameters = array('search_boxvalue' => $search_boxvalue, 'arrayresult' => $arrayresult);
$reshook = $hookmanager->executeHooks('addSearchEntry', $parameters);
if (empty($reshook)) {
	$arrayresult = array_merge($arrayresult, $hookmanager->resArray);
} else {
	$arrayresult = $hookmanager->resArray;
}

// This pushes a search entry to the top
if (getDolGlobalString('DEFAULT_SEARCH_INTO_MODULE')) {
	$key = 'searchinto' . getDolGlobalString('DEFAULT_SEARCH_INTO_MODULE');
	if (array_key_exists($key, $arrayresult)) {
		$arrayresult[$key]['position'] = -1000;
	}
}

// Sort on position
$arrayresult = dol_sort_array($arrayresult, 'position');

// Print output if called by ajax or do nothing (var $arrayresult will be used) if called by an include
if (!isset($usedbyinclude) || empty($usedbyinclude)) {
	print json_encode($arrayresult);
	if (is_object($db)) {
		$db->close();
	}
}

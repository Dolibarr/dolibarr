<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2016  Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015-2021  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2016       Josep Lluís Amador   <joseplluis@lliuretic.cat>
 * Copyright (C) 2021-2023  Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021       Noé Cendrier         <noe.cendrier@altairis.fr>
 * Copyright (C) 2023      	Frédéric France      wfrederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *      \file       htdocs/projet/element.php
 *      \ingroup    projet
 *		\brief      Page of project referrers
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

if (isModEnabled('agenda')) {
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
}
if (isModEnabled('bank')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
}
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}
if (isModEnabled('order')) {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
}
if (isModEnabled('contract')) {
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
}
if (isModEnabled('deplacement')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
}
if (isModEnabled('don')) {
	require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
}
if (isModEnabled('shipping')) {
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
}
if (isModEnabled('expensereport')) {
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
}
if (isModEnabled('invoice')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
}
if (isModEnabled('intervention')) {
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
}
if (isModEnabled('loan')) {
	require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
	require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
}
if (isModEnabled('mrp')) {
	require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
}
if (isModEnabled('propal')) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (isModEnabled('salaries')) {
	require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
}
if (isModEnabled('stock')) {
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
}
if (isModEnabled('supplier_invoice')) {
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
}
if (isModEnabled('supplier_order')) {
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
}
if (isModEnabled('supplier_proposal')) {
	require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
}
if (isModEnabled('tax')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
}
if (isModEnabled('stocktransfer')) {
	require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/class/stocktransfer.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/class/stocktransferline.class.php';
}



// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies', 'suppliers', 'compta'));
if (isModEnabled('invoice')) {
	$langs->load("bills");
}
if (isModEnabled('order')) {
	$langs->load("orders");
}
if (isModEnabled("propal")) {
	$langs->load("propal");
}
if (isModEnabled('intervention')) {
	$langs->load("interventions");
}
if (isModEnabled('deplacement')) {
	$langs->load("trips");
}
if (isModEnabled('expensereport')) {
	$langs->load("trips");
}
if (isModEnabled('don')) {
	$langs->load("donations");
}
if (isModEnabled('loan')) {
	$langs->load("loan");
}
if (isModEnabled('salaries')) {
	$langs->load("salaries");
}
if (isModEnabled('mrp')) {
	$langs->load("mrp");
}
if (isModEnabled('eventorganization')) {
	$langs->load("eventorganization");
}
//if (isModEnabled('stocktransfer')) {
//	$langs->load("stockstransfer");
//}

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$datesrfc = GETPOST('datesrfc');	// deprecated
$dateerfc = GETPOST('dateerfc');	// deprecated
$dates = dol_mktime(0, 0, 0, GETPOST('datesmonth'), GETPOST('datesday'), GETPOST('datesyear'));
$datee = dol_mktime(23, 59, 59, GETPOST('dateemonth'), GETPOST('dateeday'), GETPOST('dateeyear'));
if (empty($dates) && !empty($datesrfc)) {	// deprecated
	$dates = dol_stringtotime($datesrfc);
}
if (empty($datee) && !empty($dateerfc)) {	// deprecated
	$datee = dol_stringtotime($dateerfc);
}
if (!GETPOSTISSET('datesrfc') && !GETPOSTISSET('datesday') && getDolGlobalString('PROJECT_LINKED_ELEMENT_DEFAULT_FILTER_YEAR')) {
	$new = dol_now();
	$tmp = dol_getdate($new);
	//$datee=$now
	//$dates=dol_time_plus_duree($datee, -1, 'y');
	$dates = dol_get_first_day($tmp['year'], 1);
}
if ($id == '' && $ref == '') {
	setEventMessage($langs->trans('ErrorBadParameters'), 'errors');
	header('Location: list.php');
	exit();
}

$mine = GETPOST('mode') == 'mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once
if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($object, 'fetchComments') && empty($object->comments)) {
	$object->fetchComments();
}

// Security check
$socid = $object->socid;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.
$result = restrictedArea($user, 'projet', $object->id, 'projet&project');

$hookmanager->initHooks(array('projectOverview'));


/*
 *	View
 */

$title = $langs->trans('ProjectReferers').' - '.$object->ref.' '.$object->name;
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/projectnameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->ref.' '.$object->name.' - '.$langs->trans('ProjectReferers');
}

$help_url = 'EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos|DE:Modul_Projekte';

llxHeader('', $title, $help_url);

$form = new Form($db);
$formproject = new FormProjets($db);
$formfile = new FormFile($db);

$userstatic = new User($db);

// To verify role of users
$userAccess = $object->restrictedProjectArea($user);

$head = project_prepare_head($object);
print dol_get_fiche_head($head, 'element', $langs->trans("Project"), -1, ($object->public ? 'projectpub' : 'project'));


// Project card

if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['project'])) {
	$tmpurl = $_SESSION['pageforbacktolist']['project'];
	$tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
	$linkback = '<a href="'.$tmpurl.(preg_match('/\?/', $tmpurl) ? '&' : '?'). 'restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
} else {
	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
}

$morehtmlref = '<div class="refidno">';
// Title
$morehtmlref .= $object->title;
// Thirdparty
if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'project');
}
$morehtmlref .= '</div>';

// Define a complementary filter for search of next/prev ref.
if (!$user->hasRight('projet', 'all', 'lire')) {
	$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
	$object->next_prev_filter = "te.rowid IN (".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0').")";
}

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';

// Usage
if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') || !getDolGlobalString('PROJECT_HIDE_TASKS') || isModEnabled('eventorganization')) {
	print '<tr><td class="tdtop">';
	print $langs->trans("Usage");
	print '</td>';
	print '<td>';
	if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
		print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_opportunity ? ' checked="checked"' : '')).'"> ';
		$htmltext = $langs->trans("ProjectFollowOpportunity");
		print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
		print '<br>';
	}
	if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
		print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_task ? ' checked="checked"' : '')).'"> ';
		$htmltext = $langs->trans("ProjectFollowTasks");
		print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
		print '<br>';
	}
	if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
		print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_bill_time ? ' checked="checked"' : '')).'"> ';
		$htmltext = $langs->trans("ProjectBillTimeDescription");
		print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
		print '<br>';
	}
	if (isModEnabled('eventorganization')) {
		print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_organize_event ? ' checked="checked"' : '')).'"> ';
		$htmltext = $langs->trans("EventOrganizationDescriptionLong");
		print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
	}
	print '</td></tr>';
}

// Visibility
print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
if ($object->public) {
	print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
	print $langs->trans('SharedProject');
} else {
	print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
	print $langs->trans('PrivateProject');
}
print '</td></tr>';

if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
	// Opportunity status
	print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
	$code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
	if ($code) {
		print $langs->trans("OppStatus".$code);
	}
	print '</td></tr>';

	// Opportunity percent
	print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
	if (!is_null($object->opp_percent) && strcmp($object->opp_percent, '')) {
		print price($object->opp_percent, 0, $langs, 1, 0).' %';
	}
	print '</td></tr>';

	// Opportunity Amount
	print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
	if (!is_null($object->opp_amount) && strcmp($object->opp_amount, '')) {
		print '<span class="amount">'.price($object->opp_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
		if (strcmp($object->opp_percent, '')) {
			print ' &nbsp; &nbsp; &nbsp; <span title="'.dol_escape_htmltag($langs->trans('OpportunityWeightedAmount')).'"><span class="opacitymedium">'.$langs->trans("OpportunityWeightedAmountShort").'</span>: <span class="amount">'.price($object->opp_amount * $object->opp_percent / 100, 0, $langs, 1, 0, -1, $conf->currency).'</span></span>';
		}
	}
	print '</td></tr>';
}

// Budget
print '<tr><td>'.$langs->trans("Budget").'</td><td>';
if (!is_null($object->budget_amount) && strcmp($object->budget_amount, '')) {
	print '<span class="amount">'.price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
}
print '</td></tr>';

// Date start - end project
print '<tr><td>'.$langs->trans("Dates").'</td><td>';
$start = dol_print_date($object->date_start, 'day');
print($start ? $start : '?');
$end = dol_print_date($object->date_end, 'day');
print ' - ';
print($end ? $end : '?');
if ($object->hasDelay()) {
	print img_warning("Late");
}
print '</td></tr>';

// Other attributes
$cols = 2;
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

print '</table>';

print '</div>';
print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';

// Description
print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
print dol_htmlentitiesbr($object->description);
print '</td></tr>';

// Categories
if (isModEnabled('category')) {
	print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
	print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
	print "</td></tr>";
}

print '</table>';

print '</div>';
print '</div>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();

print '<br>';

/*
 * Referrer types
 */

$listofreferent = array(
	'entrepot' => array(
		'name' => "Warehouse",
		'title' => "ListWarehouseAssociatedProject",
		'class' => 'Entrepot',
		'table' => 'entrepot',
		'datefieldname' => 'date_entrepot',
		'urlnew' => DOL_URL_ROOT.'/product/stock/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'entrepot',
		'buttonnew' => 'AddWarehouse',
		'project_field' => 'fk_project',
		'testnew' => $user->hasRight('stock', 'creer'),
		'test' => isModEnabled('stock') && $user->hasRight('stock', 'lire') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_PROJECT')
	),
	'propal' => array(
		'name' => "Proposals",
		'title' => "ListProposalsAssociatedProject",
		'class' => 'Propal',
		'table' => 'propal',
		'datefieldname' => 'datep',
		'urlnew' => DOL_URL_ROOT.'/comm/propal/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'propal',
		'buttonnew' => 'AddProp',
		'testnew' => $user->hasRight('propal', 'creer'),
		'test' => isModEnabled('propal') && $user->hasRight('propal', 'lire')
	),
	'order' => array(
		'name' => "CustomersOrders",
		'title' => "ListOrdersAssociatedProject",
		'class' => 'Commande',
		'table' => 'commande',
		'datefieldname' => 'date_commande',
		'urlnew' => DOL_URL_ROOT.'/commande/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'orders',
		'buttonnew' => 'CreateOrder',
		'testnew' => $user->hasRight('commande', 'creer'),
		'test' => isModEnabled('order') && $user->hasRight('commande', 'lire')
	),
	'invoice' => array(
		'name' => "CustomersInvoices",
		'title' => "ListInvoicesAssociatedProject",
		'class' => 'Facture',
		'margin' => 'add',
		'table' => 'facture',
		'datefieldname' => 'datef',
		'urlnew' => DOL_URL_ROOT.'/compta/facture/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'bills',
		'buttonnew' => 'CreateBill',
		'testnew' => $user->hasRight('facture', 'creer'),
		'test' => isModEnabled('invoice') && $user->hasRight('facture', 'lire')
	),
	'invoice_predefined' => array(
		'name' => "PredefinedInvoices",
		'title' => "ListPredefinedInvoicesAssociatedProject",
		'class' => 'FactureRec',
		'table' => 'facture_rec',
		'datefieldname' => 'datec',
		'urlnew' => DOL_URL_ROOT.'/compta/facture/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'bills',
		'buttonnew' => 'CreateBill',
		'testnew' => $user->hasRight('facture', 'creer'),
		'test' => isModEnabled('invoice') && $user->hasRight('facture', 'lire')
	),
	'proposal_supplier' => array(
		'name' => "SupplierProposals",
		'title' => "ListSupplierProposalsAssociatedProject",
		'class' => 'SupplierProposal',
		'table' => 'supplier_proposal',
		'datefieldname' => 'date_valid',
		'urlnew' => DOL_URL_ROOT.'/supplier_proposal/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id), // No socid parameter here, the socid is often the customer and we create a supplier object
		'lang' => 'supplier_proposal',
		'buttonnew' => 'AddSupplierProposal',
		'testnew' => $user->hasRight('supplier_proposal', 'creer'),
		'test' => isModEnabled('supplier_proposal') && $user->hasRight('supplier_proposal', 'lire')
	),
	'order_supplier' => array(
		'name' => "SuppliersOrders",
		'title' => "ListSupplierOrdersAssociatedProject",
		'class' => 'CommandeFournisseur',
		'table' => 'commande_fournisseur',
		'datefieldname' => 'date_commande',
		'urlnew' => DOL_URL_ROOT.'/fourn/commande/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id), // No socid parameter here, the socid is often the customer and we create a supplier object
		'lang' => 'suppliers',
		'buttonnew' => 'AddSupplierOrder',
		'testnew' => $user->hasRight('fournisseur', 'commande', 'creer') || $user->hasRight('supplier_order', 'creer'),
		'test' => isModEnabled('supplier_order') && $user->hasRight('fournisseur', 'commande', 'lire') || $user->hasRight('supplier_order', 'lire')
	),
	'invoice_supplier' => array(
		'name' => "BillsSuppliers",
		'title' => "ListSupplierInvoicesAssociatedProject",
		'class' => 'FactureFournisseur',
		'margin' => 'minus',
		'table' => 'facture_fourn',
		'datefieldname' => 'datef',
		'urlnew' => DOL_URL_ROOT.'/fourn/facture/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id), // No socid parameter here, the socid is often the customer and we create a supplier object
		'lang' => 'suppliers',
		'buttonnew' => 'AddSupplierInvoice',
		'testnew' => $user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'creer'),
		'test' => isModEnabled('supplier_invoice') && $user->hasRight('fournisseur', 'facture', 'lire') || $user->hasRight('supplier_invoice', 'lire')
	),
	'contract' => array(
		'name' => "Contracts",
		'title' => "ListContractAssociatedProject",
		'class' => 'Contrat',
		'table' => 'contrat',
		'datefieldname' => 'date_contrat',
		'urlnew' => DOL_URL_ROOT.'/contrat/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'contracts',
		'buttonnew' => 'AddContract',
		'testnew' => $user->hasRight('contrat', 'creer'),
		'test' => isModEnabled('contract') && $user->hasRight('contrat', 'lire')
	),
	'intervention' => array(
		'name' => "Interventions",
		'title' => "ListFichinterAssociatedProject",
		'class' => 'Fichinter',
		'table' => 'fichinter',
		'datefieldname' => 'date_valid',
		'disableamount' => 0,
		'margin' => '',
		'urlnew' => DOL_URL_ROOT.'/fichinter/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'interventions',
		'buttonnew' => 'AddIntervention',
		'testnew' => $user->hasRight('ficheinter', 'creer'),
		'test' => isModEnabled('intervention') && $user->hasRight('ficheinter', 'lire')
	),
	'shipping' => array(
		'name' => "Shippings",
		'title' => "ListShippingAssociatedProject",
		'class' => 'Expedition',
		'table' => 'expedition',
		'datefieldname' => 'date_valid',
		'urlnew' => DOL_URL_ROOT.'/expedition/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'sendings',
		'buttonnew' => 'CreateShipment',
		'testnew' => 0,
		'test' => isModEnabled('shipping') && $user->hasRight('expedition', 'lire')
	),
	'mrp' => array(
		'name' => "MO",
		'title' => "ListMOAssociatedProject",
		'class' => 'Mo',
		'table' => 'mrp_mo',
		'datefieldname' => 'date_valid',
		'urlnew' => DOL_URL_ROOT.'/mrp/mo_card.php?action=create&origin=project&originid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'mrp',
		'buttonnew' => 'CreateMO',
		'testnew' => $user->hasRight('mrp', 'write'),
		'project_field' => 'fk_project',
		'nototal' => 1,
		'test' => isModEnabled('mrp') && $user->hasRight('mrp', 'read')
	),
	'trip' => array(
		'name' => "TripsAndExpenses",
		'title' => "ListExpenseReportsAssociatedProject",
		'class' => 'Deplacement',
		'table' => 'deplacement',
		'datefieldname' => 'dated',
		'margin' => 'minus',
		'disableamount' => 1,
		'urlnew' => DOL_URL_ROOT.'/deplacement/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'trips',
		'buttonnew' => 'AddTrip',
		'testnew' => $user->hasRight('deplacement', 'creer'),
		'test' => isModEnabled('deplacement') && $user->hasRight('deplacement', 'lire')
	),
	'expensereport' => array(
		'name' => "ExpenseReports",
		'title' => "ListExpenseReportsAssociatedProject",
		'class' => 'ExpenseReportLine',
		'table' => 'expensereport_det',
		'datefieldname' => 'date',
		'margin' => 'minus',
		'disableamount' => 0,
		'urlnew' => DOL_URL_ROOT.'/expensereport/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'trips',
		'buttonnew' => 'AddTrip',
		'testnew' => $user->hasRight('expensereport', 'creer'),
		'test' => isModEnabled('expensereport') && $user->hasRight('expensereport', 'lire')
	),
	'donation' => array(
		'name' => "Donation",
		'title' => "ListDonationsAssociatedProject",
		'class' => 'Don',
		'margin' => 'add',
		'table' => 'don',
		'datefieldname' => 'datedon',
		'disableamount' => 0,
		'urlnew' => DOL_URL_ROOT.'/don/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'donations',
		'buttonnew' => 'AddDonation',
		'testnew' => $user->hasRight('don', 'creer'),
		'test' => isModEnabled('don') && $user->hasRight('don', 'lire')
	),
	'loan' => array(
		'name' => "Loan",
		'title' => "ListLoanAssociatedProject",
		'class' => 'Loan',
		'margin' => 'add',
		'table' => 'loan',
		'datefieldname' => 'datestart',
		'disableamount' => 0,
		'urlnew' => DOL_URL_ROOT.'/loan/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'loan',
		'buttonnew' => 'AddLoan',
		'testnew' => $user->hasRight('loan', 'write'),
		'test' => isModEnabled('loan') && $user->hasRight('loan', 'read')
	),
	'chargesociales' => array(
		'name' => "SocialContribution",
		'title' => "ListSocialContributionAssociatedProject",
		'class' => 'ChargeSociales',
		'margin' => 'minus',
		'table' => 'chargesociales',
		'datefieldname' => 'date_ech',
		'disableamount' => 0,
		'urlnew' => DOL_URL_ROOT.'/compta/sociales/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'compta',
		'buttonnew' => 'AddSocialContribution',
		'testnew' => $user->hasRight('tax', 'charges', 'lire'),
		'test' => isModEnabled('tax') && $user->hasRight('tax', 'charges', 'lire')
	),
	'project_task' => array(
		'name' => "TaskTimeSpent",
		'title' => "ListTaskTimeUserProject",
		'class' => 'Task',
		'margin' => 'minus',
		'table' => 'projet_task',
		'datefieldname' => 'element_date',
		'disableamount' => 0,
		'urlnew' => DOL_URL_ROOT.'/projet/tasks/time.php?withproject=1&action=createtime&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'buttonnew' => 'AddTimeSpent',
		'testnew' => $user->hasRight('project', 'creer'),
		'test' => isModEnabled('project') && $user->hasRight('projet', 'lire') && !getDolGlobalString('PROJECT_HIDE_TASKS')
	),
	'stock_mouvement' => array(
		'name' => "MouvementStockAssociated",
		'title' => "ListMouvementStockProject",
		'class' => 'StockTransfer',
		'table' => 'stocktransfer_stocktransfer',
		'datefieldname' => 'datem',
		'margin' => 'minus',
		'project_field' => 'fk_project',
		'disableamount' => 0,
		'test' => isModEnabled('stock') && $user->hasRight('stock', 'mouvement', 'lire') && getDolGlobalString('STOCK_MOVEMENT_INTO_PROJECT_OVERVIEW')
	),
	'salaries' => array(
		'name' => "Salaries",
		'title' => "ListSalariesAssociatedProject",
		'class' => 'Salary',
		'table' => 'salary',
		'datefieldname' => 'datesp',
		'margin' => 'minus',
		'disableamount' => 0,
		'urlnew' => DOL_URL_ROOT.'/salaries/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'salaries',
		'buttonnew' => 'AddSalary',
		'testnew' => $user->hasRight('salaries', 'write'),
		'test' => isModEnabled('salaries') && $user->hasRight('salaries', 'read')
	),
	'variouspayment' => array(
		'name' => "VariousPayments",
		'title' => "ListVariousPaymentsAssociatedProject",
		'class' => 'PaymentVarious',
		'table' => 'payment_various',
		'datefieldname' => 'datev',
		'margin' => 'minus',
		'disableamount' => 0,
		'urlnew' => DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		'lang' => 'banks',
		'buttonnew' => 'AddVariousPayment',
		'testnew' => $user->hasRight('banque', 'modifier'),
		'test' => isModEnabled("bank") && $user->hasRight('banque', 'lire') && !getDolGlobalString('BANK_USE_OLD_VARIOUS_PAYMENT')
	),
		/* No need for this, available on dedicated tab "Agenda/Events"
		 'agenda'=>array(
		 'name'=>"Agenda",
		 'title'=>"ListActionsAssociatedProject",
		 'class'=>'ActionComm',
		 'table'=>'actioncomm',
		 'datefieldname'=>'datep',
		 'disableamount'=>1,
		 'urlnew'=>DOL_URL_ROOT.'/comm/action/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
		 'lang'=>'agenda',
		 'buttonnew'=>'AddEvent',
		 'testnew'=>$user->rights->agenda->myactions->create,
		'test'=> isModEnabled('agenda') && $user->hasRight('agenda', 'myactions', 'read')),
		*/
);

// Change rules for profit/benefit calculation
if (getDolGlobalString('PROJECT_ELEMENTS_FOR_PLUS_MARGIN')) {
	foreach ($listofreferent as $key => $element) {
		if ($listofreferent[$key]['margin'] == 'add') {
			unset($listofreferent[$key]['margin']);
		}
	}
	$newelementforplusmargin = explode(',', getDolGlobalString('PROJECT_ELEMENTS_FOR_PLUS_MARGIN'));
	foreach ($newelementforplusmargin as $value) {
		$listofreferent[trim($value)]['margin'] = 'add';
	}
}
if (getDolGlobalString('PROJECT_ELEMENTS_FOR_MINUS_MARGIN')) {
	foreach ($listofreferent as $key => $element) {
		if ($listofreferent[$key]['margin'] == 'minus') {
			unset($listofreferent[$key]['margin']);
		}
	}
	$newelementforminusmargin = explode(',', getDolGlobalString('PROJECT_ELEMENTS_FOR_MINUS_MARGIN'));
	foreach ($newelementforminusmargin as $value) {
		$listofreferent[trim($value)]['margin'] = 'minus';
	}
}


$parameters = array('listofreferent' => $listofreferent);
$resHook = $hookmanager->executeHooks('completeListOfReferent', $parameters, $object, $action);

if (!empty($hookmanager->resArray)) {
	$listofreferent = array_merge($listofreferent, $hookmanager->resArray);
}

if ($action == "addelement") {
	$tablename = GETPOST("tablename");
	$elementselectid = GETPOST("elementselect");
	$result = $object->update_element($tablename, $elementselectid);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
} elseif ($action == "unlink") {
	$tablename = GETPOST("tablename", "aZ09");
	$projectField = GETPOSTISSET('projectfield') ? GETPOST('projectfield', 'aZ09') : 'fk_projet';
	$elementselectid = GETPOSTINT("elementselect");

	$result = $object->remove_element($tablename, $elementselectid, $projectField);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

$elementuser = new User($db);



$showdatefilter = 0;
// Show the filter on date on top of element list
if (!$showdatefilter) {
	print '<div class="center centpercent">';
	print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="tablename" value="'.(empty($tablename) ? '' : $tablename).'">';
	print '<input type="hidden" name="action" value="view">';
	print '<div class="inline-block">';
	print $form->selectDate($dates, 'dates', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="inline-block">';
	print $form->selectDate($datee, 'datee', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '<div class="inline-block">';
	print '<input type="submit" name="refresh" value="'.$langs->trans("Refresh").'" class="button small">';
	print '</div>';
	print '</form>';
	print '</div>';

	$showdatefilter++;
}



// Show balance for whole project

$langs->loadLangs(array("suppliers", "bills", "orders", "proposals", "margins"));

if (isModEnabled('stock')) {
	$langs->load('stocks');
}

print load_fiche_titre($langs->trans("Profit"), '', 'title_accountancy');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="left" width="200">';
$tooltiponprofit = $langs->trans("ProfitIsCalculatedWith")."<br>\n";
$tooltiponprofitplus = $tooltiponprofitminus = '';
foreach ($listofreferent as $key => $value) {
	if (!empty($value['lang'])) {
		$langs->load($value['lang']);
	}
	$name = $langs->trans($value['name']);
	$qualified = $value['test'];
	$margin = empty($value['margin']) ? '' : $value['margin'];
	if ($qualified && isset($margin)) {		// If this element must be included into profit calculation ($margin is 'minus' or 'add')
		if ($margin === 'add') {
			$tooltiponprofitplus .= ' &gt; '.$name." (+)<br>\n";
		}
		if ($margin === 'minus') {
			$tooltiponprofitminus .= ' &gt; '.$name." (-)<br>\n";
		}
	}
}
$tooltiponprofit .= $tooltiponprofitplus;
$tooltiponprofit .= $tooltiponprofitminus;
print $form->textwithpicto($langs->trans("Element"), $tooltiponprofit);
print '</td>';
print '<td class="right" width="100">'.$langs->trans("Number").'</td>';
print '<td class="right" width="100">'.$langs->trans("AmountHT").'</td>';
print '<td class="right" width="100">'.$langs->trans("AmountTTC").'</td>';
print '</tr>';

$total_revenue_ht = 0;
$balance_ht = 0;
$balance_ttc = 0;

// Loop on each element type (proposal, sale order, invoices, ...)
foreach ($listofreferent as $key => $value) {
	$parameters = array(
		'total_revenue_ht' => & $total_revenue_ht,
		'balance_ht' => & $balance_ht,
		'balance_ttc' => & $balance_ttc,
		'key' => $key,
		'value' => & $value,
		'dates' => $dates,
		'datee' => $datee
	);
	$reshook = $hookmanager->executeHooks('printOverviewProfit', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} elseif ($reshook > 0) {
		print $hookmanager->resPrint;
		continue;
	}

	$name = $langs->trans($value['name']);
	$title = $value['title'];
	$classname = $value['class'];
	$tablename = $value['table'];
	$datefieldname = $value['datefieldname'];
	$qualified = $value['test'];
	$margin = empty($value['margin']) ? 0 : $value['margin'];
	$project_field = empty($value['project_field']) ? '' : $value['project_field'];
	if ($qualified && isset($margin)) {		// If this element must be included into profit calculation ($margin is 'minus' or 'add')
		$element = new $classname($db);

		$elementarray = $object->get_element_list($key, $tablename, $datefieldname, $dates, $datee, !empty($project_field) ? $project_field : 'fk_projet');

		if (is_array($elementarray) && count($elementarray) > 0) {
			$total_ht = 0;
			$total_ttc = 0;

			// Loop on each object for the current element type
			$num = count($elementarray);
			for ($i = 0; $i < $num; $i++) {
				$tmp = explode('_', $elementarray[$i]);
				$idofelement = $tmp[0];
				$idofelementuser = !empty($tmp[1]) ? $tmp[1] : "";

				$element->fetch($idofelement);
				if ($idofelementuser) {
					$elementuser->fetch($idofelementuser);
				}

				// Define if record must be used for total or not
				$qualifiedfortotal = true;
				if ($key == 'invoice') {
					if (!empty($element->close_code) && $element->close_code == 'replaced') {
						$qualifiedfortotal = false; // Replacement invoice, do not include into total
					}
					if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS') && $element->type == Facture::TYPE_DEPOSIT) {
						$qualifiedfortotal = false; // If hidden option to use deposits as payment (deprecated, not recommended to use this), deposits are not included
					}
				}
				if ($key == 'propal') {
					if ($element->status != Propal::STATUS_SIGNED && $element->status != Propal::STATUS_BILLED) {
						$qualifiedfortotal = false; // Only signed proposal must not be included in total
					}
				}

				if ($tablename != 'expensereport_det' && method_exists($element, 'fetch_thirdparty')) {
					$element->fetch_thirdparty();
				}

				// Define $total_ht_by_line
				if ($tablename == 'don' || $tablename == 'chargesociales' || $tablename == 'payment_various' || $tablename == 'salary') {
					$total_ht_by_line = $element->amount;
				} elseif ($tablename == 'fichinter') {
					$total_ht_by_line = $element->getAmount();
				} elseif ($tablename == 'stock_mouvement') {
					$total_ht_by_line = $element->price * abs($element->qty);
				} elseif ($tablename == 'projet_task') {
					$tmp = $element->getSumOfAmount($idofelementuser ? $elementuser : '', $dates, $datee);
					$total_ht_by_line = price2num($tmp['amount'], 'MT');
				} elseif ($key == 'loan') {
					if ((empty($dates) && empty($datee)) || (intval($dates) <= $element->datestart && intval($datee) >= $element->dateend)) {
						// Get total loan
						$total_ht_by_line = -$element->capital;
					} else {
						// Get loan schedule according to date filter
						$total_ht_by_line = 0;
						$loanScheduleStatic = new LoanSchedule($element->db);
						$loanScheduleStatic->fetchAll($element->id);
						if (!empty($loanScheduleStatic->lines)) {
							foreach ($loanScheduleStatic->lines as $loanSchedule) {
								/**
								 * @var $loanSchedule LoanSchedule
								 */
								if (($loanSchedule->datep >= $dates && $loanSchedule->datep <= $datee) // dates filter is defined
									|| !empty($dates) && empty($datee) && $loanSchedule->datep >= $dates && $loanSchedule->datep <= dol_now()
									|| empty($dates) && !empty($datee) && $loanSchedule->datep <= $datee
								) {
									$total_ht_by_line -= $loanSchedule->amount_capital;
								}
							}
						}
					}
				} else {
					$total_ht_by_line = $element->total_ht;
				}

				// Define $total_ttc_by_line
				if ($tablename == 'don' || $tablename == 'chargesociales' || $tablename == 'payment_various' || $tablename == 'salary') {
					$total_ttc_by_line = $element->amount;
				} elseif ($tablename == 'fichinter') {
					$total_ttc_by_line = $element->getAmount();
				} elseif ($tablename == 'stock_mouvement') {
					$total_ttc_by_line = $element->price * abs($element->qty);
				} elseif ($tablename == 'projet_task') {
					$defaultvat = get_default_tva($mysoc, $mysoc);
					$reg = array();
					if (preg_replace('/^(\d+\.)\s\(.*\)/', $defaultvat, $reg)) {
						$defaultvat = $reg[1];
					}
					$total_ttc_by_line = price2num($total_ht_by_line * (1 + ((float) $defaultvat / 100)), 'MT');
				} elseif ($key == 'loan') {
					$total_ttc_by_line = $total_ht_by_line; // For loan there is actually no taxe managed in Dolibarr
				} else {
					$total_ttc_by_line = $element->total_ttc;
				}

				// Change sign of $total_ht_by_line and $total_ttc_by_line for some cases
				if ($tablename == 'payment_various') {
					if ($element->sens == 1) {
						$total_ht_by_line = -$total_ht_by_line;
						$total_ttc_by_line = -$total_ttc_by_line;
					}
				}

				// Add total if we have to
				if ($qualifiedfortotal) {
					$total_ht = $total_ht + $total_ht_by_line;
					$total_ttc = $total_ttc + $total_ttc_by_line;
				}
			}

			// Each element with at least one line is output

			// Calculate margin
			if ($margin) {
				if ($margin === 'add') {
					$total_revenue_ht += $total_ht;
				}

				if ($margin === "minus") {	// Revert sign
					$total_ht = -$total_ht;
					$total_ttc = -$total_ttc;
				}

				$balance_ht += $total_ht;
				$balance_ttc += $total_ttc;
			}

			print '<tr class="oddeven">';
			// Module
			print '<td class="left">'.$name.'</td>';
			// Nb
			print '<td class="right">'.$i.'</td>';
			// Amount HT
			print '<td class="right">';
			if ($key == 'intervention' && !$margin) {
				print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("NA"), $langs->trans("AmountOfInteventionNotIncludedByDefault")).'</span>';
			} else {
				if ($key == 'propal') {
					print '<span class="opacitymedium">'.$form->textwithpicto('', $langs->trans("SignedOnly")).'</span>';
				}
				print price($total_ht);
			}
			print '</td>';
			// Amount TTC
			print '<td class="right">';
			if ($key == 'intervention' && !$margin) {
				print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("NA"), $langs->trans("AmountOfInteventionNotIncludedByDefault")).'</span>';
			} else {
				if ($key == 'propal') {
					print '<span class="opacitymedium">'.$form->textwithpicto('', $langs->trans("SignedOnly")).'</span>';
				}
				print price($total_ttc);
			}
			print '</td>';
			print '</tr>';
		}
	}
}
// and the final balance
print '<tr class="liste_total">';
print '<td class="right" colspan="2">'.$langs->trans("Profit").'</td>';
print '<td class="right">'.price(price2num($balance_ht, 'MT')).'</td>';
print '<td class="right">'.price(price2num($balance_ttc, 'MT')).'</td>';
print '</tr>';

// and the cost per attendee
if ($object->usage_organize_event) {
	require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
	$conforboothattendee = new ConferenceOrBoothAttendee($db);
	$result = $conforboothattendee->fetchAll('', '', 0, 0, ['uss' => '(t.fk_project:=:'.((int) $object->id).') AND (t.status:=:'.ConferenceOrBoothAttendee::STATUS_VALIDATED.')']);

	if (!is_array($result) && $result < 0) {
		setEventMessages($conforboothattendee->error, $conforboothattendee->errors, 'errors');
	} else {
		$nbAttendees = count($result);
	}

	if ($nbAttendees >= 2) {
		$costperattendee_ht = $balance_ht / $nbAttendees;
		$costperattendee_ttc = $balance_ttc / $nbAttendees;
		print '<tr class="liste_total">';
		print '<td class="right" colspan="2">'.$langs->trans("ProfitPerValidatedAttendee").'</td>';
		print '<td class="right">'.price(price2num($costperattendee_ht, 'MT')).'</td>';
		print '<td class="right">'.price(price2num($costperattendee_ttc, 'MT')).'</td>';
		print '</tr>';
	}
}

// and the margin (profit / revenues)
if ($total_revenue_ht) {
	print '<tr class="liste_total">';
	print '<td class="right" colspan="2">'.$langs->trans("Margin").'</td>';
	print '<td class="right">'.round(100 * $balance_ht / $total_revenue_ht, 1).'%</td>';
	print '<td class="right"></td>';
	print '</tr>';
}

print "</table>";


print '<br><br>';
print '<br>';


$total_time = 0;

// Detail
foreach ($listofreferent as $key => $value) {
	$parameters = array(
		'key' => $key,
		'value' => & $value,
		'dates' => $dates,
		'datee' => $datee
	);
	$reshook = $hookmanager->executeHooks('printOverviewDetail', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} elseif ($reshook > 0) {
		print $hookmanager->resPrint;
		continue;
	}

	$title = $value['title'];
	$classname = $value['class'];
	$tablename = $value['table'];
	$datefieldname = $value['datefieldname'];
	$qualified = $value['test'];
	$urlnew = empty($value['urlnew']) ? '' : $value['urlnew'];
	$buttonnew = empty($value['buttonnew']) ? '' : $value['buttonnew'];
	$testnew = empty($value['testnew']) ? '' : $value['testnew'];
	$project_field = empty($value['project_field']) ? '' : $value['project_field'];
	$nototal = empty($value['nototal']) ? 0 : 1;

	$exclude_select_element = array('payment_various');
	if (!empty($value['exclude_select_element'])) {
		$exclude_select_element[] = $value['exclude_select_element'];
	}

	if ($qualified) {
		// If we want the project task array to have details of users
		//if ($key == 'project_task') $key = 'project_task_time';

		$element = new $classname($db);

		$addform = '';

		$idtofilterthirdparty = 0;
		$array_of_element_linkable_with_different_thirdparty = array('facture_fourn', 'commande_fournisseur');
		if (!in_array($tablename, $array_of_element_linkable_with_different_thirdparty)) {
			$idtofilterthirdparty = empty($object->thirdparty->id) ? 0 : $object->thirdparty->id;
			if (getDolGlobalString('PROJECT_OTHER_THIRDPARTY_ID_TO_ADD_ELEMENTS')) {
				$idtofilterthirdparty .= ',' . getDolGlobalString('PROJECT_OTHER_THIRDPARTY_ID_TO_ADD_ELEMENTS');
			}
		}

		$elementarray = $object->get_element_list($key, $tablename, $datefieldname, $dates, $datee, !empty($project_field) ? $project_field : 'fk_projet');


		if (!getDolGlobalString('PROJECT_LINK_ON_OVERWIEW_DISABLED') && $idtofilterthirdparty && !in_array($tablename, $exclude_select_element)) {
			$selectList = $formproject->select_element($tablename, $idtofilterthirdparty, 'minwidth300 minwidth75imp', -2, empty($project_field) ? 'fk_projet' : $project_field, $langs->trans("SelectElement"));
			if ($selectList < 0) {
				setEventMessages($formproject->error, $formproject->errors, 'errors');
			} elseif ($selectList) {
				// Define form with the combo list of elements to link
				$addform .= '<div class="inline-block valignmiddle">';
				$addform .= '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				$addform .= '<input type="hidden" name="token" value="'.newToken().'">';
				$addform .= '<input type="hidden" name="tablename" value="'.$tablename.'">';
				$addform .= '<input type="hidden" name="action" value="addelement">';
				$addform .= '<input type="hidden" name="datesrfc" value="'.dol_print_date($dates, 'dayhourrfc').'">';
				$addform .= '<input type="hidden" name="dateerfc" value="'.dol_print_date($datee, 'dayhourrfc').'">';
				$addform .= '<table><tr>';
				//$addform .= '<td><span class="hideonsmartphone opacitymedium">'.$langs->trans("SelectElement").'</span></td>';
				$addform .= '<td>'.$selectList.'</td>';
				$addform .= '<td><input type="submit" class="button button-linkto smallpaddingimp" value="'.dol_escape_htmltag($langs->trans("LinkToElementShort")).'"></td>';
				$addform .= '</tr></table>';
				$addform .= '</form>';
				$addform .= '</div>';
			}
		}
		if (!getDolGlobalString('PROJECT_CREATE_ON_OVERVIEW_DISABLED') && $urlnew) {
			$addform .= '<div class="inline-block valignmiddle">';
			if ($testnew) {
				$addform .= '<a class="buttonxxx marginleftonly" href="'.$urlnew.'" title="'.dol_escape_htmltag($langs->trans($buttonnew)).'"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
			} elseif (!getDolGlobalString('MAIN_BUTTON_HIDE_UNAUTHORIZED')) {
				$addform .= '<span title="'.dol_escape_htmltag($langs->trans($buttonnew)).'"><a class="buttonxxx marginleftonly buttonRefused" disabled="disabled" href="#"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a></span>';
			}
			$addform .= '<div>';
		}

		if (is_array($elementarray) && count($elementarray) > 0 && $key == "order_supplier") {
			$addform = '<div class="inline-block valignmiddle"><a id="btnShow" class="buttonxxx marginleftonly" href="#" onClick="return false;">
						 <span id="textBtnShow" class="valignmiddle text-plus-circle hideonsmartphone">'.$langs->trans("CanceledShown").'</span><span id="minus-circle" class="fa fa-eye valignmiddle paddingleft"></span>
						 </a>
						 <script>
						 $("#btnShow").on("click", function () {
							console.log("We click to show or hide the canceled lines");
							var attr = $(this).attr("data-canceledarehidden");
							if (typeof attr !== "undefined" && attr !== false) {
								console.log("Show canceled");
								$(".tr_canceled").show();
								$("#textBtnShow").text("'.dol_escape_js($langs->transnoentitiesnoconv("CanceledShown")).'");
								$("#btnShow").removeAttr("data-canceledarehidden");
								$("#minus-circle").removeClass("fa-eye-slash").addClass("fa-eye");
							} else {
								console.log("Hide canceled");
								$(".tr_canceled").hide();
								$("#textBtnShow").text("'.dol_escape_js($langs->transnoentitiesnoconv("CanceledHidden")).'");
								$("#btnShow").attr("data-canceledarehidden", 1);
								$("#minus-circle").removeClass("fa-eye").addClass("fa-eye-slash");
							}
						 });
						 </script></div> '.$addform;
		}

		print load_fiche_titre($langs->trans($title), $addform, '');

		print "\n".'<!-- Table for tablename = '.$tablename.' -->'."\n";
		print '<div class="div-table-responsive">';
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		// Remove link column
		print '<td style="width: 24px"></td>';
		// Ref
		print '<td'.(($tablename != 'actioncomm' && $tablename != 'projet_task') ? ' style="width: 200px"' : '').'>'.$langs->trans("Ref").'</td>';
		// Product and qty on stock_movement
		if ('MouvementStock' == $classname) {
			print '<td style="width: 200px">'.$langs->trans("Product").'</td>';
			print '<td style="width: 50px">'.$langs->trans("Qty").'</td>';
		}
		// Date
		print '<td'.(($tablename != 'actioncomm' && $tablename != 'projet_task') ? ' style="width: 200px"' : '').' class="center">';
		if (in_array($tablename, array('projet_task'))) {
			print $langs->trans("TimeSpent");
		}
		if (!in_array($tablename, array('projet_task'))) {
			print $langs->trans("Date");
		}
		print '</td>';
		// Thirdparty or user
		print '<td>';
		if (in_array($tablename, array('projet_task')) && $key == 'project_task') {
			print ''; // if $key == 'project_task', we don't want details per user
		} elseif (in_array($tablename, array('payment_various'))) {
			print ''; // if $key == 'payment_various', we don't have any thirdparty
		} elseif (in_array($tablename, array('expensereport_det', 'don', 'projet_task', 'stock_mouvement', 'salary'))) {
			print $langs->trans("User");
		} else {
			print $langs->trans("ThirdParty");
		}
		print '</td>';
		// Duration of intervention
		if ($tablename == 'fichinter') {
			print '<td>';
			print $langs->trans("TotalDuration");
			$total_duration = 0;
			print '</td>';
		}
		// Amount HT
		//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td class="right" width="120">'.$langs->trans("AmountHT").'</td>';
		//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td class="right" width="120">'.$langs->trans("Amount").'</td>';
		if ($key == 'loan') {
			print '<td class="right" width="120">'.$langs->trans("LoanCapital").'</td>';
		} elseif (empty($value['disableamount'])) {
			print '<td class="right" width="120">'.$langs->trans("AmountHT").'</td>';
		} else {
			print '<td width="120"></td>';
		}
		// Amount TTC
		//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td class="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		if ($key == 'loan') {
			print '<td class="right" width="120">'.$langs->trans("RemainderToPay").'</td>';
		} elseif (empty($value['disableamount'])) {
			print '<td class="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		} else {
			print '<td width="120"></td>';
		}
		// Status
		if (in_array($tablename, array('projet_task'))) {
			print '<td class="right" width="200">'.$langs->trans("ProgressDeclared").'</td>';
		} else {
			print '<td class="right" width="200">'.$langs->trans("Status").'</td>';
		}
		print '</tr>';

		if (is_array($elementarray) && count($elementarray) > 0) {
			$total_ht = 0;
			$total_ttc = 0;

			$total_ht_by_third = 0;
			$total_ttc_by_third = 0;

			$saved_third_id = 0;
			$breakline = '';

			if (canApplySubtotalOn($tablename)) {
				// Sort
				$elementarray = sortElementsByClientName($elementarray);
			}

			$num = count($elementarray);
			for ($i = 0; $i < $num; $i++) {
				$tmp = explode('_', $elementarray[$i]);
				$idofelement = $tmp[0];
				$idofelementuser = isset($tmp[1]) ? $tmp[1] : "";

				$element->fetch($idofelement);
				if ($idofelementuser) {
					$elementuser->fetch($idofelementuser);
				}

				// Special cases
				if ($tablename != 'expensereport_det') {
					if (method_exists($element, 'fetch_thirdparty')) {
						$element->fetch_thirdparty();
					}
				} else {
					$expensereport = new ExpenseReport($db);
					$expensereport->fetch($element->fk_expensereport);
				}

				//print 'xxx'.$tablename.'yyy'.$classname;

				if ($breakline && $saved_third_id != $element->thirdparty->id) {
					print $breakline;

					$saved_third_id = $element->thirdparty->id;
					$breakline = '';

					$total_ht_by_third = 0;
					$total_ttc_by_third = 0;
				}

				$saved_third_id = empty($element->thirdparty->id) ? 0 : $element->thirdparty->id;

				$qualifiedfortotal = true;
				if ($key == 'invoice') {
					if (!empty($element->close_code) && $element->close_code == 'replaced') {
						$qualifiedfortotal = false; // Replacement invoice, do not include into total
					}
				} elseif ($key == 'order_supplier' && $element->status == 7) {
					$qualifiedfortotal = false; // It makes no sense to include canceled orders in the total
				}

				if ($key == "order_supplier" && $element->status == 7) {
					print '<tr class="oddeven tr_canceled" style=display:none>';
				} else {
					print '<tr class="oddeven" >';
				}


				// Remove link
				print '<td style="width: 24px">';
				if ($tablename != 'projet_task' && $tablename != 'stock_mouvement') {
					if (!getDolGlobalString('PROJECT_DISABLE_UNLINK_FROM_OVERVIEW') || $user->admin) {		// PROJECT_DISABLE_UNLINK_FROM_OVERVIEW is empty by default, so this test true
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=unlink&tablename='.$tablename.'&elementselect='.$element->id.($project_field ? '&projectfield='.$project_field : '').'" class="reposition">';
						print img_picto($langs->trans('Unlink'), 'unlink');
						print '</a>';
					}
				}
				print "</td>\n";

				// Ref
				print '<td class="left nowraponall tdoverflowmax250">';
				if ($tablename == 'expensereport_det') {
					print $expensereport->getNomUrl(1);
				} else {
					// Show ref with link
					if ($element instanceof Task) {
						print $element->getNomUrl(1, 'withproject', 'time');
						print ' - '.dol_trunc($element->label, 48);
					} elseif ($key == 'loan') {
						print $element->getNomUrl(1);
						print ' - '.dol_trunc($element->label, 48);
					} else {
						print $element->getNomUrl(1);
					}

					$element_doc = $element->element;
					$filename = dol_sanitizeFileName($element->ref);
					if (!empty($conf->$element_doc)) {
						$confelementdoc = $conf->$element_doc;
						$filedir = $confelementdoc->multidir_output[$element->entity].'/'.dol_sanitizeFileName($element->ref);
					} else {
						$filedir = '';
					}

					if ($element_doc === 'order_supplier') {
						$element_doc = 'commande_fournisseur';
						$filedir = $conf->fournisseur->commande->multidir_output[$element->entity].'/'.dol_sanitizeFileName($element->ref);
					} elseif ($element_doc === 'invoice_supplier') {
						$element_doc = 'facture_fournisseur';
						$filename = get_exdir($element->id, 2, 0, 0, $element, 'product').dol_sanitizeFileName($element->ref);
						$filedir = $conf->fournisseur->facture->multidir_output[$element->entity].'/'.get_exdir($element->id, 2, 0, 0, $element, 'invoice_supplier').dol_sanitizeFileName($element->ref);
					}

					print '<div class="inline-block valignmiddle">';
					if ($filedir) {
						print $formfile->getDocumentsLink($element_doc, $filename, $filedir);
					}
					print '</div>';

					// Show supplier ref
					if (!empty($element->ref_supplier)) {
						print ' - '.$element->ref_supplier;
					}
					// Show customer ref
					if (!empty($element->ref_customer)) {
						print ' - '.$element->ref_customer;
					}
					// Compatibility propale
					if (empty($element->ref_customer) && !empty($element->ref_client)) {
						print ' - '.$element->ref_client;
					}
				}
				print "</td>\n";
				// Product and qty on stock movement
				if ('MouvementStock' == $classname) {
					$mvsProd = new Product($element->db);
					$mvsProd->fetch($element->product_id);
					print '<td>'.$mvsProd->getNomUrl(1).'</td>';
					print '<td>'.$element->qty.'</td>';
				}
				// Date or TimeSpent
				$date = '';
				$total_time_by_line = null;
				if ($tablename == 'expensereport_det') {
					$date = $element->date; // No draft status on lines
				} elseif ($tablename == 'stock_mouvement') {
					$date = $element->datem;
				} elseif ($tablename == 'salary') {
					$date = $element->datesp;
				} elseif ($tablename == 'payment_various') {
					$date = $element->datev;
				} elseif ($tablename == 'chargesociales') {
					$date = $element->date_ech;
				} elseif (!empty($element->status) || !empty($element->statut) || !empty($element->fk_status)) {
					if ($tablename == 'don') {
						$date = $element->datedon;
					}
					if ($tablename == 'commande_fournisseur' || $tablename == 'supplier_order') {
						$date = ($element->date_commande ? $element->date_commande : $element->date_valid);
					} elseif ($tablename == 'supplier_proposal') {
						$date = $element->date_validation; // There is no other date for this
					} elseif ($tablename == 'fichinter') {
						$date = $element->datev; // There is no other date for this
					} elseif ($tablename == 'projet_task') {
						$date = ''; // We show no date. Showing date of beginning of task make user think it is date of time consumed
					} else {
						$date = $element->date; // invoice, ...
						if (empty($date)) {
							$date = $element->date_contrat;
						}
						if (empty($date)) {
							$date = $element->datev;
						}
						if (empty($date) && !empty($datefieldname)) {
							$date = $element->$datefieldname;
						}
					}
				} elseif ($key == 'loan') {
					$date = $element->datestart;
				}

				print '<td class="center">';
				if ($tablename == 'actioncomm') {
					print dol_print_date($element->datep, 'dayhour');
					if ($element->datef && $element->datef > $element->datep) {
						print " - ".dol_print_date($element->datef, 'dayhour');
					}
				} elseif (in_array($tablename, array('projet_task'))) {
					$tmpprojtime = $element->getSumOfAmount($idofelementuser ? $elementuser : '', $dates, $datee); // $element is a task. $elementuser may be empty
					print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$idofelement.'&withproject=1">';
					print convertSecondToTime($tmpprojtime['nbseconds'], 'allhourmin');
					print '</a>';
					$total_time_by_line = $tmpprojtime['nbseconds'];
				} else {
					print dol_print_date($date, 'day');
				}
				print '</td>';

				// Third party or user
				print '<td class="tdoverflowmax150">';
				if (is_object($element->thirdparty)) {
					print $element->thirdparty->getNomUrl(1, '', 48);
				} elseif ($tablename == 'expensereport_det') {
					$tmpuser = new User($db);
					$tmpuser->fetch($expensereport->fk_user_author);
					print $tmpuser->getNomUrl(1, '', 48);
				} elseif ($tablename == 'salary') {
					$tmpuser = new User($db);
					$tmpuser->fetch($element->fk_user);
					print $tmpuser->getNomUrl(1, '', 48);
				} elseif ($tablename == 'don' || $tablename == 'stock_mouvement') {
					if ($element->fk_user_author > 0) {
						$tmpuser2 = new User($db);
						$tmpuser2->fetch($element->fk_user_author);
						print $tmpuser2->getNomUrl(1, '', 48);
					}
				} elseif ($tablename == 'projet_task' && $key == 'element_time') {	// if $key == 'project_task', we don't want details per user
					print $elementuser->getNomUrl(1);
				}
				print '</td>';

				// Add duration and store it in counter for fichinter
				if ($tablename == 'fichinter') {
					print '<td>';
					print convertSecondToTime($element->duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);
					$total_duration += $element->duration;
					print '</td>';
				}

				// Amount without tax
				$warning = '';
				if (empty($value['disableamount'])) {
					$total_ht_by_line = null;
					$othermessage = '';
					if ($tablename == 'don' || $tablename == 'chargesociales' || $tablename == 'payment_various' || $tablename == 'salary') {
						$total_ht_by_line = $element->amount;
					} elseif ($tablename == 'fichinter') {
						$total_ht_by_line = $element->getAmount();
					} elseif ($tablename == 'stock_mouvement') {
						$total_ht_by_line = $element->price * abs($element->qty);
					} elseif (in_array($tablename, array('projet_task'))) {
						if (isModEnabled('salaries')) {
							// TODO Permission to read daily rate to show value
							$total_ht_by_line = price2num($tmpprojtime['amount'], 'MT');
							if ($tmpprojtime['nblinesnull'] > 0) {
								$langs->load("errors");
								$warning = $langs->trans("WarningSomeLinesWithNullHourlyRate", $conf->currency);
							}
						} else {
							$othermessage = $form->textwithpicto($langs->trans("NotAvailable"), $langs->trans("ModuleSalaryToDefineHourlyRateMustBeEnabled"));
						}
					} elseif ($key == 'loan') {
						$total_ht_by_line = $element->capital;
					} else {
						$total_ht_by_line = $element->total_ht;
					}

					// Change sign of $total_ht_by_line and $total_ttc_by_line for some cases
					if ($tablename == 'payment_various') {
						if ($element->sens == 0) {
							$total_ht_by_line = -$total_ht_by_line;
						}
					}

					print '<td class="right">';
					if ($othermessage) {
						print '<span class="opacitymedium">'.$othermessage.'</span>';
					}
					if (isset($total_ht_by_line)) {
						if (!$qualifiedfortotal) {
							print '<strike>';
						}
						print '<span class="amount">'.price($total_ht_by_line).'</span>';
						if (!$qualifiedfortotal) {
							print '</strike>';
						}
					}
					if ($warning) {
						print ' '.img_warning($warning);
					}
					print '</td>';
				} else {
					print '<td></td>';
				}

				// Amount inc tax
				if (empty($value['disableamount'])) {
					$total_ttc_by_line = null;
					if ($tablename == 'don' || $tablename == 'chargesociales' || $tablename == 'payment_various' || $tablename == 'salary') {
						$total_ttc_by_line = $element->amount;
					} elseif ($tablename == 'fichinter') {
						$total_ttc_by_line = $element->getAmount();
					} elseif ($tablename == 'stock_mouvement') {
						$total_ttc_by_line = $element->price * abs($element->qty);
					} elseif ($tablename == 'projet_task') {
						if (isModEnabled('salaries')) {
							// TODO Permission to read daily rate
							$defaultvat = get_default_tva($mysoc, $mysoc);
							$total_ttc_by_line = price2num($total_ht_by_line * (1 + ($defaultvat / 100)), 'MT');
						} else {
							$othermessage = $form->textwithpicto($langs->trans("NotAvailable"), $langs->trans("ModuleSalaryToDefineHourlyRateMustBeEnabled"));
						}
					} elseif ($key == 'loan') {
						$total_ttc_by_line = $element->capital - $element->getSumPayment();
					} else {
						$total_ttc_by_line = $element->total_ttc;
					}

					// Change sign of $total_ht_by_line and $total_ttc_by_line for some cases
					if ($tablename == 'payment_various') {
						if ($element->sens == 0) {
							$total_ttc_by_line = -$total_ttc_by_line;
						}
					}

					print '<td class="right">';
					if ($othermessage) {
						print $othermessage;
					}
					if (isset($total_ttc_by_line)) {
						if (!$qualifiedfortotal) {
							print '<strike>';
						}
						print '<span class="amount">'.price($total_ttc_by_line).'</span>';
						if (!$qualifiedfortotal) {
							print '</strike>';
						}
					}
					if ($warning) {
						print ' '.img_warning($warning);
					}
					print '</td>';
				} else {
					print '<td></td>';
				}

				// Status
				print '<td class="right">';
				if ($tablename == 'expensereport_det') {
					print $expensereport->getLibStatut(5);
				} elseif ($element instanceof CommonInvoice) {
					//This applies for Facture and FactureFournisseur
					print $element->getLibStatut(5, $element->getSommePaiement());
				} elseif ($element instanceof Task) {
					if ($element->progress != '') {
						print $element->progress.' %';
					}
				} elseif ($tablename == 'stock_mouvement') {
					print $element->getLibStatut(3);
				} else {
					print $element->getLibStatut(5);
				}
				print '</td>';

				print '</tr>';

				if ($qualifiedfortotal) {
					$total_ht = $total_ht + $total_ht_by_line;
					$total_ttc = $total_ttc + $total_ttc_by_line;

					$total_ht_by_third += $total_ht_by_line;
					$total_ttc_by_third += $total_ttc_by_line;

					if (!isset($total_time)) {
						$total_time = $total_time_by_line;
					} else {
						$total_time += $total_time_by_line;
					}
				}

				if (canApplySubtotalOn($tablename)) {
					$breakline = '<tr class="liste_total liste_sub_total">';
					$breakline .= '<td colspan="2">';
					$breakline .= '</td>';
					$breakline .= '<td>';
					$breakline .= '</td>';
					$breakline .= '<td class="right">';
					$breakline .= $langs->trans('SubTotal').' : ';
					if (is_object($element->thirdparty)) {
						$breakline .= $element->thirdparty->getNomUrl(0, '', 48);
					}
					$breakline .= '</td>';
					$breakline .= '<td class="right">'.price($total_ht_by_third).'</td>';
					$breakline .= '<td class="right">'.price($total_ttc_by_third).'</td>';
					$breakline .= '<td></td>';
					$breakline .= '</tr>';
				}

				//var_dump($element->thirdparty->name.' - '.$saved_third_id.' - '.$element->thirdparty->id);
			}

			if ($breakline) {
				print $breakline;
			}

			// Total
			if (empty($nototal)) {
				$colspan = 4;
				if (in_array($tablename, array('projet_task'))) {
					$colspan = 2;
				}

				print '<tr class="liste_total"><td colspan="'.$colspan.'">'.$langs->trans("Number").': '.$i.'</td>';
				if (in_array($tablename, array('projet_task'))) {
					print '<td class="center">';
					print convertSecondToTime($total_time, 'allhourmin');
					print '</td>';
					print '<td>';
					print '</td>';
				}
				//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td class="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
				//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td class="right" width="100">'.$langs->trans("Total").' : '.price($total_ht).'</td>';
				// If fichinter add the total_duration
				if ($tablename == 'fichinter') {
					print '<td class="left">'.convertSecondToTime($total_duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY).'</td>';
				}
				print '<td class="right">';
				if (empty($value['disableamount'])) {
					if ($key == 'loan') {
						print $langs->trans("Total").' '.$langs->trans("LoanCapital").' : '.price($total_ttc);
					} elseif ($tablename != 'projet_task' || isModEnabled('salaries')) {
						print ''.$langs->trans("TotalHT").' : '.price($total_ht);
					}
				}
				print '</td>';
				//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td class="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_ttc).'</td>';
				//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td class="right" width="100"></td>';
				print '<td class="right">';
				if (empty($value['disableamount'])) {
					if ($key == 'loan') {
						print $langs->trans("Total").' '.$langs->trans("RemainderToPay").' : '.price($total_ttc);
					} elseif ($tablename != 'projet_task' || isModEnabled('salaries')) {
						print $langs->trans("TotalTTC").' : '.price($total_ttc);
					}
				}
				print '</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';
			}
		} else {
			if (!is_array($elementarray)) {	// error
				print '<tr><td>'.$elementarray.'</td></tr>';
			} else {
				$colspan = 7;
				if ($tablename == 'fichinter') {
					$colspan++;
				}
				print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</td></tr>';
			}
		}
		print "</table>";
		print '</div>';
		print "<br>\n";
	}
}

// Enhance with select2
if ($conf->use_javascript_ajax) {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
	$comboenhancement = ajax_combobox('.elementselect');

	print $comboenhancement;
}

// End of page
llxFooter();
$db->close();



/**
 * Return if we should do a group by customer with sub-total
 *
 * @param 	string	$tablename		Name of table
 * @return	boolean					True to tell to make a group by sub-total
 */
function canApplySubtotalOn($tablename)
{
	global $conf;

	if (!getDolGlobalString('PROJECT_ADD_SUBTOTAL_LINES')) {
		return false;
	}
	return in_array($tablename, array('facture_fourn', 'commande_fournisseur'));
}

/**
 * sortElementsByClientName
 *
 * @param 	array		$elementarray	Element array
 * @return	array						Element array sorted
 */
function sortElementsByClientName($elementarray)
{
	global $db, $classname;

	$element = new $classname($db);

	$clientname = array();
	foreach ($elementarray as $key => $id) {	// id = id of object
		if (empty($clientname[$id])) {
			$element->fetch($id);
			$element->fetch_thirdparty();

			$clientname[$id] = $element->thirdparty->name;
		}
	}

	//var_dump($clientname);
	asort($clientname); // sort on name

	$elementarray = array();
	foreach ($clientname as $id => $name) {
		$elementarray[] = $id;
	}

	return $elementarray;
}

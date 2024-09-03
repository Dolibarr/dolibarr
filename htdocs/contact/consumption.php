<?php
/* Copyright (C) 2012-2013	Philippe Berthet			<berthet@systune.be>
 * Copyright (C) 2004-2016	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2013-2015	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2015		Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015-2017	Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *	\file       htdocs/contact/consumption.php
 *  \ingroup    societe
 *	\brief      Add a tab on thirdparty view to list all products/services bought or sells by thirdparty
 */


// Load Dolibarr environment
require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search

$id = GETPOSTINT('id');

$object = new Contact($db);
if ($id > 0) {
	$object->fetch($id);
}
if (empty($object->thirdparty)) {
	$object->fetch_thirdparty();
}
$socid = !empty($object->thirdparty->id) ? $object->thirdparty->id : null;

// Sort & Order fields
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = 'DESC';
}
if (!$sortfield) {
	$sortfield = 'dateprint';
}

// Search fields
$sref = GETPOST("sref");
$sprod_fulldescr = GETPOST("sprod_fulldescr");
$month = GETPOSTINT('month');
$year = GETPOSTINT('year');

// Clean up on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$sref = '';
	$sprod_fulldescr = '';
	$year = '';
	$month = '';
}
// Customer or supplier selected in drop box
$thirdTypeSelect = GETPOST("third_select_id");
$type_element = GETPOSTISSET('type_element') ? GETPOST('type_element') : '';

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "orders", "suppliers", "propal", "interventions", "contracts", "products"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('consumptioncontact'));

$result = restrictedArea($user, 'contact', $object->id, 'socpeople&societe');


/*
 * Actions
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


/*
 * View
 */
$form = new Form($db);
$formother = new FormOther($db);
$productstatic = new Product($db);
$objsoc = new Societe($db);

$title = $langs->trans("ContactRelatedItems");
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-societe page-contact-card_consumption');

if (empty($id)) {
	dol_print_error($db);
	exit;
}

$head = contact_prepare_head($object);
print dol_get_fiche_head($head, 'consumption', $langs->trans("ContactsAddresses"), -1, 'contact');

$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$object->id.'" class="refid">';
$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

$morehtmlref .= '<div class="refidno">';
if (!getDolGlobalString('SOCIETE_DISABLE_CONTACTS')) {
	$objsoc->fetch($socid);
	// Thirdparty
	if ($objsoc->id > 0) {
		$morehtmlref .= $objsoc->getNomUrl(1, 'contact');
	} else {
		$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("ContactNotLinkedToCompany").'</span>';
	}
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'id', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', $morehtmlref);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Civility
print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td>';
print $object->getCivilityLabel();
print '</td></tr>';

$thirdTypeArray = array();
$elementTypeArray = array();

if (!empty($object->thirdparty->client)) {
	$thirdTypeArray['customer'] = $langs->trans("customer");
	if (isModEnabled("propal") && $user->hasRight('propal', 'lire')) {
		$elementTypeArray['propal'] = $langs->transnoentitiesnoconv('Proposals');
	}
	if (isModEnabled('order') && $user->hasRight('commande', 'lire')) {
		$elementTypeArray['order'] = $langs->transnoentitiesnoconv('Orders');
	}
	if (isModEnabled('invoice') && $user->hasRight('facture', 'lire')) {
		$elementTypeArray['invoice'] = $langs->transnoentitiesnoconv('Invoices');
	}
	if (isModEnabled('contract') && $user->hasRight('contrat', 'lire')) {
		$elementTypeArray['contract'] = $langs->transnoentitiesnoconv('Contracts');
	}
}

if (isModEnabled('intervention') && $user->hasRight('ficheinter', 'lire')) {
	$elementTypeArray['fichinter'] = $langs->transnoentitiesnoconv('Interventions');
}

if (!empty($object->thirdparty->fournisseur)) {
	$thirdTypeArray['supplier'] = $langs->trans("supplier");
	if ((isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'facture', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))) {
		$elementTypeArray['supplier_invoice'] = $langs->transnoentitiesnoconv('SuppliersInvoices');
	}
	if ((isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'commande', 'lire')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire'))) {
		$elementTypeArray['supplier_order'] = $langs->transnoentitiesnoconv('SuppliersOrders');
	}

	// There are no contact type for supplier proposals
	// if ((isModEnabled("fournisseur") && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && $user->rights->supplier_proposal->lire) $elementTypeArray['supplier_proposal']=$langs->transnoentitiesnoconv('SupplierProposals');
}

print '</table>';

print '</div>';

print dol_get_fiche_end();
print '<br>';


print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

$sql_select = '';
if ($type_element == 'fichinter') { 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	$documentstatic = new Fichinter($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ref as doc_number, \'1\' as doc_type, f.datec as dateprint, f.fk_statut as status, tc.libelle as type_contact_label, ';
	$tables_from = MAIN_DB_PREFIX.'fichinterdet d';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'fichinter as f ON d.fk_fichinter=f.rowid';
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON ec.element_id=f.rowid AND ec.fk_socpeople = '.((int) $object->id);
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX."c_type_contact tc ON (ec.fk_c_type_contact=tc.rowid and tc.element='fichinter' and tc.source='external' and tc.active=1)";
	$where = ' WHERE f.entity IN ('.getEntity('intervention').')';
	$dateprint = 'f.datec';
	$doc_number = 'f.ref';
} elseif ($type_element == 'invoice') { 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$documentstatic = new Facture($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ref as doc_number, f.type as doc_type, f.datef as dateprint, f.fk_statut as status, f.paye as paid, tc.libelle as type_contact_label, ';
	$tables_from = MAIN_DB_PREFIX.'facturedet d';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture as f ON d.fk_facture=f.rowid';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON d.fk_product=p.rowid';
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON ec.element_id=f.rowid AND ec.fk_socpeople = '.((int) $object->id);
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX."c_type_contact tc ON (ec.fk_c_type_contact=tc.rowid and tc.element='facture' and tc.source='external' and tc.active=1)";
	$where = " WHERE f.entity IN (".getEntity('invoice').")";
	$dateprint = 'f.datef';
	$doc_number = 'f.ref';
	$thirdTypeSelect = 'customer';
} elseif ($type_element == 'propal') {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	$documentstatic = new Propal($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.datep as dateprint, c.fk_statut as status, tc.libelle as type_contact_label, ';
	$tables_from = MAIN_DB_PREFIX.'propaldet d';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'propal as c ON d.fk_propal=c.rowid';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON d.fk_product=p.rowid';
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON ec.element_id=c.rowid AND ec.fk_socpeople = '.((int) $object->id);
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX."c_type_contact tc ON (ec.fk_c_type_contact=tc.rowid and tc.element='propal' and tc.source='external' and tc.active=1)";
	$where = ' WHERE c.entity IN ('.getEntity('propal').')';
	$dateprint = 'c.datep';
	$doc_number = 'c.ref';
	$thirdTypeSelect = 'customer';
} elseif ($type_element == 'order') {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	$documentstatic = new Commande($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_commande as dateprint, c.fk_statut as status, tc.libelle as type_contact_label, ';
	$tables_from = MAIN_DB_PREFIX.'commandedet d';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande as c ON d.fk_commande=c.rowid';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON d.fk_product=p.rowid';
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON ec.element_id=c.rowid AND ec.fk_socpeople = '.((int) $object->id);
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX."c_type_contact tc ON (ec.fk_c_type_contact=tc.rowid and tc.element='commande' and tc.source='external' and tc.active=1)";
	$where = ' WHERE c.entity IN ('.getEntity('order').')';
	$dateprint = 'c.date_commande';
	$doc_number = 'c.ref';
	$thirdTypeSelect = 'customer';
} elseif ($type_element == 'supplier_invoice') { 	// Supplier : Show products from invoices.
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	$documentstatic = new FactureFournisseur($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ref as doc_number, \'1\' as doc_type, f.datef as dateprint, f.fk_statut as status, f.paye as paid, tc.libelle as type_contact_label, ';
	$tables_from = MAIN_DB_PREFIX.'facture_fourn_det d';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn as f ON d.fk_facture_fourn=f.rowid';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON d.fk_product=p.rowid';
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON ec.element_id=f.rowid AND ec.fk_socpeople = '.((int) $object->id);
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX."c_type_contact tc ON (ec.fk_c_type_contact=tc.rowid and tc.element='invoice_supplier' and tc.source='external' and tc.active=1)";
	$where = ' WHERE f.entity IN ('.getEntity($documentstatic->element).')';
	$dateprint = 'f.datef';
	$doc_number = 'f.ref';
	$thirdTypeSelect = 'supplier';
	//} elseif ($type_element == 'supplier_proposal') {
	//    require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
	//    $documentstatic=new SupplierProposal($db);
	//    $sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_valid as dateprint, c.fk_statut as status, ';
	//    $tables_from = MAIN_DB_PREFIX."supplier_proposal as c,".MAIN_DB_PREFIX."supplier_proposaldet as d";
	//    $where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".((int) $socid);
	//    $where.= " AND d.fk_supplier_proposal = c.rowid";
	//    $where.= " AND c.entity = ".$conf->entity;
	//    $dateprint = 'c.date_valid';
	//    $doc_number='c.ref';
	//    $thirdTypeSelect='supplier';
	//}
} elseif ($type_element == 'supplier_order') { 	// Supplier : Show products from orders.
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	$documentstatic = new CommandeFournisseur($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_valid as dateprint, c.fk_statut as status, tc.libelle as type_contact_label, ';
	$tables_from = MAIN_DB_PREFIX.'commande_fournisseurdet d';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseur as c ON d.fk_commande=c.rowid';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON d.fk_product=p.rowid';
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON ec.element_id=c.rowid AND ec.fk_socpeople = '.((int) $object->id);
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX."c_type_contact tc ON (ec.fk_c_type_contact=tc.rowid and tc.element='order_supplier' and tc.source='external' and tc.active=1)";
	$where = ' WHERE c.entity IN ('.getEntity($documentstatic->element).')';
	$dateprint = 'c.date_valid';
	$doc_number = 'c.ref';
	$thirdTypeSelect = 'supplier';
} elseif ($type_element == 'contract') { 	// Order
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	$documentstatic = new Contrat($db);
	$documentstaticline = new ContratLigne($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_contrat as dateprint, d.statut as status, tc.libelle as type_contact_label, ';
	$tables_from = MAIN_DB_PREFIX.'contratdet d';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'contrat as c ON d.fk_contrat=c.rowid';
	$tables_from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON d.fk_product=p.rowid';
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON ec.element_id=c.rowid AND ec.fk_socpeople = '.((int) $object->id);
	$tables_from .= ' INNER JOIN '.MAIN_DB_PREFIX."c_type_contact tc ON (ec.fk_c_type_contact=tc.rowid and tc.element='contrat' and tc.source='external' and tc.active=1)";
	$where = ' WHERE c.entity IN ('.getEntity('contrat').')';
	$dateprint = 'c.date_valid';
	$doc_number = 'c.ref';
	$thirdTypeSelect = 'customer';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook

if (!empty($sql_select)) {
	$sql = $sql_select;
	$sql .= ' d.description as description';
	if ($type_element != 'fichinter' && $type_element != 'contract' && $type_element != 'supplier_proposal') {
		$sql .= ', d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.date_start, d.date_end, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
	}
	if ($type_element == 'supplier_proposal') {
		$sql .= ', d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
	}
	if ($type_element == 'contract') {
		$sql .= ', d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.date_ouverture as date_start, d.date_cloture as date_end, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
	}
	if ($type_element != 'fichinter') {
		$sql .= ' p.ref as ref, p.rowid as prod_id, p.rowid as fk_product, p.fk_product_type as prod_type, p.fk_product_type as fk_product_type, p.entity as pentity';
	}
	$sql .= " ";
	if ($type_element != 'fichinter') {
		$sql .= ", p.ref as prod_ref, p.label as product_label";
	}
	$sql .= " FROM "/*.MAIN_DB_PREFIX."societe as s, "*/.$tables_from;
	// if ($type_element != 'fichinter') $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON d.fk_product = p.rowid ';
	$sql .= $where;
	$sql .= dolSqlDateFilter($dateprint, 0, $month, $year);
	if ($sref) {
		$sql .= " AND ".$doc_number." LIKE '%".$db->escape($sref)."%'";
	}
	if ($sprod_fulldescr) {
		$sql .= " AND (d.description LIKE '%".$db->escape($sprod_fulldescr)."%'";
		if (GETPOST('type_element') != 'fichinter') {
			$sql .= " OR p.ref LIKE '%".$db->escape($sprod_fulldescr)."%'";
		}
		if (GETPOST('type_element') != 'fichinter') {
			$sql .= " OR p.label LIKE '%".$db->escape($sprod_fulldescr)."%'";
		}
		$sql .= ")";
	}
	$sql .= $db->order($sortfield, $sortorder);
	$resql = $db->query($sql);
	$totalnboflines = $db->num_rows($resql);

	$sql .= $db->plimit($limit + 1, $offset);
}

$disabled = 0;
$showempty = 2;
if (empty($elementTypeArray) && !$object->thirdparty->client && !$object->thirdparty->fournisseur) {
	$showempty = $langs->trans("ThirdpartyNotCustomerNotSupplierSoNoRef");
	$disabled = 1;
}

// Define type of elements
$typeElementString = $form->selectarray("type_element", $elementTypeArray, GETPOST('type_element'), $showempty, 0, 0, '', 0, 0, $disabled, '', 'maxwidth150onsmartphone');
$button = '<input type="submit" class="button small" name="button_third" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';

$param = '';
$param .= "&sref=".urlencode($sref);
$param .= "&month=".urlencode($month);
$param .= "&year=".urlencode($year);
$param .= "&sprod_fulldescr=".urlencode($sprod_fulldescr);
if (!empty($socid)) {
	$param .= "&socid=".urlencode((string) ($socid));
}
$param .= "&type_element=".urlencode($type_element);

$total_qty = 0;
$num = 0;
if ($sql_select) {
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}

	$num = $db->num_rows($resql);

	$param = (!empty($socid) ? "&socid=".urlencode((string) ($socid)) : "")."&type_element=".urlencode((string) ($type_element))."&id=".urlencode((string) ($id));
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($sprod_fulldescr) {
		$param .= "&sprod_fulldescr=".urlencode($sprod_fulldescr);
	}
	if ($sref) {
		$param .= "&sref=".urlencode($sref);
	}
	if ($month) {
		$param .= "&month=".urlencode((string) ($month));
	}
	if ($year) {
		$param .= "&year=".urlencode((string) ($year));
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}

	print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, '', 0, '', '', $limit);

	print '<div class="div-table-responsive-no-min">';
	print '<table class="liste centpercent">'."\n";

	// Filters
	print '<tr class="liste_titre">';
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
	print '</td>';
	print '<td class="liste_titre nowrap center">'; // date
	print $formother->select_month($month ? $month : -1, 'month', 1, 0, 'valignmiddle');
	print $formother->selectyear($year ? $year : -1, 'year', 1, 20, 1);
	print '</td>';
	print '<td class="liste_titre center">';
	print '</td>';
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="sprod_fulldescr" size="15" value="'.dol_escape_htmltag($sprod_fulldescr).'">';
	print '</td>';
	print '<td class="liste_titre center">'; // TODO: Add filters !
	print '</td>';
	print '<td class="liste_titre center">';
	print '</td>';
	print '<td class="liste_titre center">';
	print '</td>';
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	// Titles with sort buttons
	print '<tr class="liste_titre">';
	print_liste_field_titre('Ref', $_SERVER['PHP_SELF'], 'doc_number', '', $param, '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre('Date', $_SERVER['PHP_SELF'], 'dateprint', '', $param, 'width="150"', $sortfield, $sortorder, 'center ');
	print_liste_field_titre('Status', $_SERVER['PHP_SELF'], 'fk_statut', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre('Product', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre('ContactType', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre('Quantity', $_SERVER['PHP_SELF'], 'prod_qty', '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre('TotalHT', $_SERVER['PHP_SELF'], 'total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre('UnitPrice', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	print "</tr>\n";


	$i = 0;
	$total_qty = 0;
	$total_ht = 0;
	while (($objp = $db->fetch_object($resql)) && $i < min($num, $limit)) {
		$documentstatic->id = $objp->doc_id;
		$documentstatic->ref = $objp->doc_number;
		$documentstatic->type = $objp->doc_type;

		$documentstatic->fk_statut = $objp->status;
		$documentstatic->statut = $objp->status;
		$documentstatic->status = $objp->status;

		$documentstatic->paye = $objp->paid;
		$documentstatic->paid = $objp->paid;

		if (is_object($documentstaticline)) {
			$documentstaticline->statut = $objp->status;
		}

		print '<tr class="oddeven">';
		print '<td class="nobordernopadding nowrap" width="100">';
		print $documentstatic->getNomUrl(1);
		print '</td>';
		print '<td class="center" width="80">'.dol_print_date($db->jdate($objp->dateprint), 'day').'</td>';

		// Status
		print '<td class="center">';
		if ($type_element == 'contract') {
			print $documentstaticline->getLibStatut(2);
		} else {
			print $documentstatic->getLibStatut(2);
		}
		print '</td>';

		print '<td>';

		// Define text, description and type
		$text = '';
		$description = '';
		$type = 0;

		// Code to show product duplicated from commonobject->printObjectLine
		if ($objp->fk_product > 0) {
			$product_static = new Product($db);

			$product_static->type = $objp->fk_product_type;
			$product_static->id = $objp->fk_product;
			$product_static->ref = $objp->ref;
			$product_static->entity = $objp->pentity;
			$text = $product_static->getNomUrl(1);
		}

		// Product
		if ($objp->fk_product > 0) {
			// Define output language
			if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
				$prod = new Product($db);
				$prod->fetch($objp->fk_product);

				$outputlangs = $langs;
				$newlang = '';
				if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (empty($newlang)) {
					$newlang = $object->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $objp->product_label;
			} else {
				$label = $objp->product_label;
			}

			$text .= ' - '.(!empty($objp->label) ? $objp->label : $label);
			$description = (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : dol_htmlentitiesbr($objp->description));
		}

		if (($objp->info_bits & 2) == 2) {
			print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'">';
			$txt = '';
			print img_object($langs->trans("ShowReduc"), 'reduc').' ';
			if ($objp->description == '(DEPOSIT)') {
				$txt = $langs->trans("Deposit");
			} elseif ($objp->description == '(EXCESS RECEIVED)') {
				$txt = $langs->trans("ExcessReceived");
			} elseif ($objp->description == '(EXCESS PAID)') {
				$txt = $langs->trans("ExcessPaid");
			}
			//else $txt=$langs->trans("Discount");
			print $txt;
			print '</a>';
			if ($objp->description) {
				if ($objp->description == '(CREDIT_NOTE)' && $objp->fk_remise_except > 0) {
					$discount = new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo($txt ? ' - ' : '').$langs->transnoentities("DiscountFromCreditNote", $discount->getNomUrl(0));
				}
				if ($objp->description == '(EXCESS RECEIVED)' && $objp->fk_remise_except > 0) {
					$discount = new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo($txt ? ' - ' : '').$langs->transnoentities("DiscountFromExcessReceived", $discount->getNomUrl(0));
				} elseif ($objp->description == '(EXCESS PAID)' && $objp->fk_remise_except > 0) {
					$discount = new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo($txt ? ' - ' : '').$langs->transnoentities("DiscountFromExcessPaid", $discount->getNomUrl(0));
				} elseif ($objp->description == '(DEPOSIT)' && $objp->fk_remise_except > 0) {
					$discount = new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo($txt ? ' - ' : '').$langs->transnoentities("DiscountFromDeposit", $discount->getNomUrl(0));
					// Add date of deposit
					if (getDolGlobalString('INVOICE_ADD_DEPOSIT_DATE')) {
						echo ' ('.dol_print_date($discount->datec).')';
					}
				} else {
					echo($txt ? ' - ' : '').dol_htmlentitiesbr($objp->description);
				}
			}
		} else {
			if ($objp->fk_product > 0) {
				echo $form->textwithtooltip($text, $description, 3, '', '', $i, 0, '');

				// Show range
				echo get_date_range($objp->date_start, $objp->date_end);

				// Add description in form
				if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
					print (!empty($objp->description) && $objp->description != $objp->product_label) ? '<br>'.dol_htmlentitiesbr($objp->description) : '';
				}
			} else {
				if (!empty($objp->label) || !empty($objp->description)) {
					if ($type == 1) {
						$text = img_object($langs->trans('Service'), 'service');
					} else {
						$text = img_object($langs->trans('Product'), 'product');
					}

					if (!empty($objp->label)) {
						$text .= ' <strong>'.$objp->label.'</strong>';
						echo $form->textwithtooltip($text, dol_htmlentitiesbr($objp->description), 3, '', '', $i, 0, '');
					} else {
						echo $text.' '.dol_htmlentitiesbr($objp->description);
					}
				}

				// Show range
				echo get_date_range($objp->date_start, $objp->date_end);
			}
		}

		/*
		$prodreftxt='';
		if ($objp->prod_id > 0)
		{
			$productstatic->id = $objp->prod_id;
			$productstatic->ref = $objp->prod_ref;
			$productstatic->status = $objp->prod_type;
			$prodreftxt = $productstatic->getNomUrl(0);
			if(!empty($objp->product_label)) $prodreftxt .= ' - '.$objp->product_label;
		}
		// Show range
		$prodreftxt .= get_date_range($objp->date_start, $objp->date_end);
		// Add description in form
		if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE'))
		{
			$prodreftxt .= (!empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
		}
		*/
		print '</td>';

		print '<td>'.$objp->type_contact_label.'</td>'; // Type of contact label

		print '<td class="right">'.$objp->prod_qty.'</td>';
		$total_qty += $objp->prod_qty;

		print '<td class="right">'.price($objp->total_ht).'</td>';
		$total_ht += $objp->total_ht;

		print '<td class="right">'.price($objp->total_ht / (empty($objp->prod_qty) ? 1 : $objp->prod_qty)).'</td>';

		print "</tr>\n";
		$i++;
	}

	print '<tr class="liste_total">';
	print '<td>'.$langs->trans('Total').'</td>';
	print '<td colspan="3"></td>';
	print '<td></td>';
	print '<td class="right">'.$total_qty.'</td>';
	print '<td class="right">'.price($total_ht).'</td>';
	print '<td class="right">'.price($total_ht / (empty($total_qty) ? 1 : $total_qty)).'</td>';
	print "</table>";
	print '</div>';

	if ($num > $limit) {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num);
	}
	$db->free($resql);
} elseif (empty($type_element) || $type_element == -1) {
	print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', '');

	print '<table class="liste centpercent">'."\n";
	// Titles with sort buttons
	print '<tr class="liste_titre">';
	print_liste_field_titre('Ref', $_SERVER['PHP_SELF'], 'doc_number', '', $param, '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre('Date', $_SERVER['PHP_SELF'], 'dateprint', '', $param, 'width="150"', $sortfield, $sortorder, 'center ');
	print_liste_field_titre('Status', $_SERVER['PHP_SELF'], 'fk_status', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre('Product', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre('Quantity', $_SERVER['PHP_SELF'], 'prod_qty', '', $param, '', $sortfield, $sortorder, 'right ');
	print "</tr>\n";

	print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans("SelectElementAndClick", $langs->transnoentitiesnoconv("Search")).'</span></td></tr>';

	print "</table>";
} else {
	print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', '');

	print '<table class="liste centpercent">'."\n";

	print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans("FeatureNotYetAvailable").'</span></td></tr>';

	print "</table>";
}

print "</form>";

// End of page
llxFooter();
$db->close();

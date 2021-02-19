<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Teddy Andreotti			<125155@supinfo.com>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2018		Frédéric France			<frederic.france@netlogic.fr>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/fourn/paiment/list.php
*	\ingroup	fournisseur,facture
 *	\brief		Payment list for supplier invoices
 */

require '../../main.inc.php';

// Security check
if ($user->socid) $socid = $user->socid;

// doesn't work :-(
// restrictedArea($user, 'fournisseur');

// doesn't work :-(
// require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
// $object = new PaiementFourn($db);
// restrictedArea($user, $object->element);

if (!$user->rights->fournisseur->facture->lire) {
	accessforbidden();
}

require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';


// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'banks', 'compta'));

$action = GETPOST('action', 'alpha');
$massaction				= GETPOST('massaction', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage			= GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'vendorpaymentlist';

$socid = GETPOST('socid', 'int');

$search_ref				= GETPOST('search_ref', 'alpha');
$search_day				= GETPOST('search_day', 'int');
$search_month = GETPOST('search_month', 'int');
$search_year			= GETPOST('search_year', 'int');
$search_company = GETPOST('search_company', 'alpha');
$search_payment_type	= GETPOST('search_payment_type');
$search_cheque_num = GETPOST('search_cheque_num', 'alpha');
$search_bank_account	= GETPOST('search_bank_account', 'int');
$search_amount = GETPOST('search_amount', 'alpha'); // alpha because we must be able to search on '< x'

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield				= GETPOST('sortfield', 'alpha');
$sortorder				= GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');

if (empty($page) || $page == -1) $page = 0; // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "p.datep";

$search_all = trim(GETPOSTISSET("search_all") ? GETPOST("search_all", 'alpha') : GETPOST('sall'));

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref'=>"RefPayment",
	's.nom'=>"ThirdParty",
	'p.num_paiement'=>"Numero",
	'p.amount'=>"Amount",
);

$arrayfields = array(
	'p.ref'				=>array('label'=>"RefPayment", 'checked'=>1, 'position'=>10),
	'p.datep'			=>array('label'=>"Date", 'checked'=>1, 'position'=>20),
	's.nom'				=>array('label'=>"ThirdParty", 'checked'=>1, 'position'=>30),
	'c.libelle'			=>array('label'=>"Type", 'checked'=>1, 'position'=>40),
	'p.num_paiement'	=>array('label'=>"Numero", 'checked'=>1, 'position'=>50, 'tooltip'=>"ChequeOrTransferNumber"),
	'ba.label'			=>array('label'=>"Account", 'checked'=>1, 'position'=>60, 'enable'=>(!empty($conf->banque->enabled))),
	'p.amount'			=>array('label'=>"Amount", 'checked'=>1, 'position'=>70),
);
$arrayfields = dol_sort_array($arrayfields, 'position');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('paymentsupplierlist'));
$object = new PaiementFourn($db);

/*
* Actions
*/

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {	// All tests are required to be compatible with all browsers
		$search_ref = '';
		$search_day = '';
		$search_month = '';
		$search_year = '';
		$search_company = '';
		$search_payment_type = '';
		$search_cheque_num = '';
		$search_bank_account = '';
		$search_amount = '';
	}
}

/*
 * View
 */

llxHeader('', $langs->trans('ListPayment'));

$form = new Form($db);
$formother = new FormOther($db);
$companystatic = new Societe($db);
$paymentfournstatic = new PaiementFourn($db);

$sql = 'SELECT p.rowid, p.ref, p.datep, p.amount as pamount, p.num_paiement';
$sql .= ', s.rowid as socid, s.nom as name, s.email';
$sql .= ', c.code as paiement_type, c.libelle as paiement_libelle';
$sql .= ', ba.rowid as bid, ba.label';
if (!$user->rights->societe->client->voir) $sql .= ', sc.fk_soc, sc.fk_user';
$sql .= ', SUM(pf.amount)';

$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn AS p';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn AS pf ON p.rowid=pf.fk_paiementfourn';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn AS f ON f.rowid=pf.fk_facturefourn';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement AS c ON p.fk_paiement = c.id';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
if (!$user->rights->societe->client->voir) $sql .= ', '.MAIN_DB_PREFIX.'societe_commerciaux as sc';

$sql .= ' WHERE f.entity = '.$conf->entity;
if (!$user->rights->societe->client->voir) $sql .= ' AND s.rowid = sc.fk_soc AND sc.fk_user = '.$user->id;
if ($socid > 0) $sql .= ' AND f.fk_soc = '.$socid;
if ($search_ref)				$sql .= natural_search('p.ref', $search_ref);
$sql .= dolSqlDateFilter('p.datep', $search_day, $search_month, $search_year);
if ($search_company)			$sql .= natural_search('s.nom', $search_company);
if ($search_payment_type != '')	$sql .= " AND c.code='".$db->escape($search_payment_type)."'";
if ($search_cheque_num != '')	$sql .= natural_search('p.num_paiement', $search_cheque_num);
if ($search_amount)				$sql .= natural_search('p.amount', $search_amount, 1);
if ($search_bank_account > 0)	$sql .= ' AND b.fk_account='.$search_bank_account."'";

if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

$sql .= ' GROUP BY p.rowid, p.ref, p.datep, p.amount, p.num_paiement, s.rowid, s.nom, s.email, c.code, c.libelle, ba.rowid, ba.label';
if (!$user->rights->societe->client->voir) $sql .= ', sc.fk_soc, sc.fk_user';

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {		// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	llxFooter();
	$db->close();
	exit;
}

$num = $db->num_rows($resql);
$i = 0;

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
if ($optioncss != '')		$param .= '&optioncss='.urlencode($optioncss);

if ($search_ref)			$param .= '&search_ref='.urlencode($search_ref);
if ($saerch_day)			$param .= '&search_day='.urlencode($search_day);
if ($saerch_month)			$param .= '&search_month='.urlencode($search_month);
if ($search_year)			$param .= '&search_year='.urlencode($search_year);
if ($search_company)		$param .= '&search_company='.urlencode($search_company);
if ($search_payment_type)	$param .= '&search_company='.urlencode($search_payment_type);
if ($search_cheque_num)		$param .= '&search_cheque_num='.urlencode($search_cheque_num);
if ($search_amount)			$param .= '&search_amount='.urlencode($search_amount);

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($langs->trans('SupplierPayments'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'supplier_invoice', 0, '', '', $limit, 0, 0, 1);

if ($search_all)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if ($moreforfilter) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : '').'">';

print '<tr class="liste_titre_filter">';

// Filter: Ref
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td  class="liste_titre left">';
	print '<input class="flat" type="text" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Filter: Date
if (!empty($arrayfields['p.datep']['checked'])) {
	print '<td class="liste_titre center">';
	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_day" value="'.dol_escape_htmltag($search_day).'">';
	print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_month" value="'.dol_escape_htmltag($search_month).'">';
	$formother->select_year($search_year ? $search_year : -1, 'search_year', 1, 20, 5);
	print '</td>';
}

// Filter: Thirdparty
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="6" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
	print '</td>';
}

// Filter: Payment type
if (!empty($arrayfields['c.libelle']['checked'])) {
	print '<td class="liste_titre">';
	$form->select_types_paiements($search_payment_type, 'search_payment_type', '', 2, 1, 1);
	print '</td>';
}

// Filter: Cheque number (fund transfer)
if (!empty($arrayfields['p.num_paiement']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="4" name="search_cheque_num" value="'.dol_escape_htmltag($search_cheque_num).'">';
	print '</td>';
}

// Filter: Bank account
if (!empty($arrayfields['ba.label']['checked'])) {
	print '<td class="liste_titre">';
	$form->select_comptes($search_bank_account, 'search_bank_account', 0, '', 1);
	print '</td>';
}

// Filter: Amount
if (!empty($arrayfields['p.amount']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Buttons
print '<td class="liste_titre maxwidthsearch">';
print $form->showFilterAndCheckAddButtons(0);
print '</td>';

print '</tr>';

print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST))	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['p.ref']['checked']))				print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.rowid', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['p.datep']['checked']))				print_liste_field_titre($arrayfields['p.datep']['label'], $_SERVER["PHP_SELF"], 'p.datep', '', $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['s.nom']['checked']))				print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['c.libelle']['checked']))			print_liste_field_titre($arrayfields['c.libelle']['label'], $_SERVER["PHP_SELF"], 'c.libelle', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['p.num_paiement']['checked']))		print_liste_field_titre($arrayfields['p.num_paiement']['label'], $_SERVER["PHP_SELF"], "p.num_paiement", '', $param, '', $sortfield, $sortorder, '', $arrayfields['p.num_paiement']['tooltip']);
if (!empty($arrayfields['ba.label']['checked']))			print_liste_field_titre($arrayfields['ba.label']['label'], $_SERVER["PHP_SELF"], 'ba.label', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['p.amount']['checked']))			print_liste_field_titre($arrayfields['p.amount']['label'], $_SERVER["PHP_SELF"], 'p.amount', '', $param, '', $sortfield, $sortorder, 'right ');

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
print '</tr>';

$checkedCount = 0;
foreach ($arrayfields as $column) {
	if ($column['checked']) {
		$checkedCount++;
	}
}

$i = 0;
$totalarray = array();
while ($i < min($num, $limit)) {
	$objp = $db->fetch_object($resql);

	$paymentfournstatic->id = $objp->rowid;
	$paymentfournstatic->ref = $objp->ref;
	$paymentfournstatic->datepaye = $objp->datep;

	$companystatic->id = $objp->socid;
	$companystatic->name = $objp->name;
	$companystatic->email = $objp->email;

	print '<tr class="oddeven">';

	// No
	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
		print '<td>'.(($offset * $limit) + $i).'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Ref
	if (!empty($arrayfields['p.ref']['checked'])) {
		print '<td class="nowrap">'.$paymentfournstatic->getNomUrl(1).'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Date
	if (!empty($arrayfields['p.datep']['checked'])) {
		$dateformatforpayment = 'day';
		if (!empty($conf->global->INVOICE_USE_HOURS_FOR_PAYMENT)) $dateformatforpayment = 'dayhour';
		print '<td class="nowrap center">'.dol_print_date($db->jdate($objp->datep), $dateformatforpayment).'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Thirdparty
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td>';
		if ($objp->socid > 0) {
			print $companystatic->getNomUrl(1, '', 24);
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Pyament type
	if (!empty($arrayfields['c.libelle']['checked'])) {
		$payment_type = $langs->trans("PaymentType".$objp->paiement_type) != ("PaymentType".$objp->paiement_type) ? $langs->trans("PaymentType".$objp->paiement_type) : $objp->paiement_libelle;
		print '<td>'.$payment_type.' '.dol_trunc($objp->num_paiement, 32).'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Cheque number (fund transfer)
	if (!empty($arrayfields['p.num_paiement']['checked'])) {
		print '<td>'.$objp->num_paiement.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Account
	if (!empty($arrayfields['ba.label']['checked']))
	{
		print '<td>';
		if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"), 'account').' '.dol_trunc($objp->label, 24).'</a>';
		else print '&nbsp;';
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Amount
	if (!empty($arrayfields['p.amount']['checked'])) {
		print '<td class="right">'.price($objp->pamount).'</td>';
		if (!$i) $totalarray['nbfield']++;
		$totalarray['pos'][$checkedCount] = 'amount';
		$totalarray['val']['amount'] += $objp->pamount;
	}

	// Buttons
	print '<td></td>';
	if (!$i) $totalarray['nbfield']++;

	print '</tr>';
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

print '</table>';
print '</div>';
print '</form>';

// End of page
llxFooter();
$db->close();

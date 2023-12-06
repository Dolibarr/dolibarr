<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2019       Thibault FOUCART        <support@ptibogxiv.net>
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
 *  \file       htdocs/don/list.php
 *  \ingroup    donations
 *  \brief      List of donations
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'donations'));

$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'create'/'add', 'edit'/'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'sclist';

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$type = GETPOST('type', 'aZ');
$mode = GETPOST('mode', 'alpha');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "d.datedon";
}

$search_status = (GETPOST("search_status", 'intcomma') != '') ? GETPOST("search_status", 'intcomma') : "-4";
$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref = GETPOST('search_ref', 'alpha');
$search_thirdparty = GETPOST('search_thirdparty', 'alpha');
$search_payment = GETPOST('search_payment', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_bank_account	= GETPOST('search_bank_account', 'int');
$optioncss = GETPOST('optioncss', 'alpha');
$moreforfilter = GETPOST('moreforfilter', 'alpha');

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$search_all = "";
	$search_ref = "";
	$search_company = "";
	$search_thirdparty  = "";
	$search_name = "";
	$search_payment = "";
	$search_amount = "";
	$search_status = '';
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('donationlist'));


// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref'=>"RefPayment",
	'p.num_payment'=>"Numero",
	'pd.amount'=>"Amount",
);

// Security check
$result = restrictedArea($user, 'don');

$permissiontoread = $user->hasRight('don', 'read');
$permissiontoadd = $user->hasRight('don', 'write');
$permissiontodelete = $user->hasRight('don', 'delete');

/*
 * Actions
 */


if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

/*
 * View
 */

$form = new Form($db);
$donationstatic = new Don($db);
$companystatic = new Societe($db);
$bankline = new AccountLine($db);
$accountstatic = new Account($db);

$title = $langs->trans("Donations");
$help_url = 'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones|DE:Modul_Spenden';


// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT pd.rowid as payment_id, pd.amount, pd.datep, pd.fk_typepayment, pd.num_payment, pd.amount, pd.fk_bank, ";
$sql .= ' s.rowid as soc_id, s.nom, ';
$sql .= ' d.societe, ';
$sql .= ' c.code as paiement_code, ';
$sql .= ' d.rowid, ba.rowid as bid, ba.ref as bref, ba.label as blabel, ba.number, ba.account_number as account_number, ba.iban_prefix, ba.bic, ba.currency_code, ba.fk_accountancy_journal as accountancy_journal ';

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."payment_donation as pd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."don as d ON (d.rowid = pd.fk_donation)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON (b.rowid = pd.fk_bank)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON (ba.rowid = b.fk_account)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON (s.rowid = d.fk_soc)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON (c.id = pd.fk_typepayment)";

if ($search_status != '' && $search_status != '-4') {
	$sql .= " AND d.fk_statut IN (".$db->sanitize($search_status).")";
}
if (trim($search_ref) != '') {
	$sql .= natural_search('pd.ref', $search_ref);
}
if (trim($search_all) != '') {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if (trim($search_company) != '') {
	$sql .= natural_search('d.societe', $search_company);
}
if ($search_bank_account > 0) {
	$sql .= ' AND b.fk_account = '.((int) $search_bank_account);
}
if (trim($search_payment) != '') {
	$sql .= natural_search("pd.rowid", $search_payment);
}
if ($search_amount) {
	$sql .= natural_search('d.amount', $search_amount, 1);
}


// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);

if (!$resql) {
	dol_print_error($db);
	exit;
}



$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && !getDolGlobalInt('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".dol_buildpath('/mymodule/myobject_card.php', 1).'?id='.$id);
	exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_company) {
	$param .= '&search_company='.urlencode($search_company);
}
if ($search_payment) {
	$param .= '&search_payment='.urlencode($search_payment);
}
if ($search_amount) {
	$param .= '&search_amount='.urlencode($search_amount);
}

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);


print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="type" value="'.$type.'">';

print_barre_liste($langs->trans("DonationsReglement"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'object_donation', 0, $newcardbutton, '', $limit, 0, 0, 1);

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if DONATION_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')) : ''); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.(!empty($moreforfilter) ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalInt('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

print '<td  class="liste_titre left">';
print '<input class="flat" type="text" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
print '</td>';


print '<td class="liste_titre>';
print '<div class="nowrapfordate">';
print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
print '</div>';
print '<div class="nowrapfordate">';
print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
print '</div>';
print '</td>';

print '<td class="liste_titre">';
print '<input class="flat" size="10" type="text" name="search_thirdparty" value="'.$search_thirdparty.'">';
print '</td>';

print '<td class="liste_titre">';
$form->select_types_paiements($search_payment_type, 'search_payment_type', '', 2, 1, 1);
print '</td>';

print '<td class="liste_titre">';
print '<input class="flat" size="10" type="text" name="search_payment" value="'.$search_num_payment.'">';
print '</td>';

print '<td></td>';

print '<td class="liste_titre">';
$form->select_comptes($search_bank_account, 'search_bank_account', 0, '', 1);
print '</td>';

print '<td class="liste_titre">';
print '<input class="flat" size="10" type="text" name="search_thirdparty" value="'.$search_amount.'">';
print '</td>';


$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre('');
	$totalarray['nbfield']++;
}

print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "d.datedon", "", $param, '', $sortfield, $sortorder, );
$totalarray['nbfield']++;
print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "d.fk_soc", "", $param, "", $sortfield, $sortorder,);
$totalarray['nbfield']++;
print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "d.amount", "", $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Numero", $_SERVER["PHP_SELF"], "p.num_paiement", '', $param, '', $sortfield, $sortorder, '', "ChequeOrTransferNumber");
$totalarray['nbfield']++;
print_liste_field_titre("BankTransactionLine", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "d.fk_statut", "", $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "d.fk_statut", "", $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;

if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre('');
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {

	$obj = $db->fetch_object($resql);

	$companystatic->id = $obj->soc_id;
	$companystatic->name = $obj->nom;

	$company = new Societe($db);
	$result = $company->fetch($obj->socid);

	print '<tr class="oddeven">';
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}

	print '<td><a href="'.DOL_URL_ROOT.'/don/payment/card.php?id='.$obj->payment_id.'">'.img_object($langs->trans("Payment"), "payment").' '.$obj->payment_id.'</a></td>';

	$dateformatforpayment = 'dayhour';
	print '<td class="nowraponall">'.dol_print_date($db->jdate($obj->datep), $dateformatforpayment, 'tzuser').'</td>';

	print '<td class="tdoverflowmax125">';
	if ($obj->soc_id > 0) {
		print $companystatic->getNomUrl(1, '', 24);
	} else {
		print $donationstatic->societe = $obj->societe;
	}
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	print '<td>'.$langs->trans("PaymentTypeShort".$obj->paiement_code).'</td>';

	print '<td>'.$obj->num_payment.'</td>';

	print '<td class="tdoverflowmax125">';
	if ($obj->fk_bank > 0) {
		$test = $bankline->fetch($obj->fk_bank);
		print $bankline->getNomUrl(1, 0);
	}
	print '</td>';


	print '<td>';
	if ($obj->bid > 0) {
		$accountstatic->id = $obj->bid;
		$accountstatic->ref = $obj->bref;
		$accountstatic->label = $obj->blabel;
		$accountstatic->number = $obj->number;
		$accountstatic->account_number = $obj->account_number;

		$accountingjournal = new AccountingJournal($db);
		$accountingjournal->fetch($obj->accountancy_journal);
		$accountstatic->accountancy_journal = $accountingjournal->code;

		print $accountstatic->getNomUrl(1);
	}

	print '<td ><span class="amount">'.price($obj->amount).'</span></td>';

	$i++;
}

print "</table>";
print '</div>';
print "</form>\n";
$db->free($resql);

llxFooter();
$db->close();

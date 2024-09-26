<?php
/* Copyright (C) 2013-2014	Olivier Geffroy				<jeff@jeffinfo.com>
 * Copyright (C) 2013-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)			<github@accedinfo.com>
 * Copyright (C) 2013-2021	Florian Henry				<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2016		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2021		Gauthier VERDOL				<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file 		htdocs/accountancy/customer/list.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		Ventilation page from customers invoices
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("bills", "companies", "compta", "accountancy", "other", "productbatch", "products"));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accountancycustomerlist'; // To manage different context of search
$optioncss = GETPOST('optioncss', 'alpha');

$default_account = GETPOSTINT('default_account');

// Select Box
$mesCasesCochees = GETPOST('toselect', 'array');

// Search Getpost
$search_societe = GETPOST('search_societe', 'alpha');
$search_lineid = GETPOST('search_lineid', 'alpha');		// Can be '> 100'
$search_ref = GETPOST('search_ref', 'alpha');
$search_invoice = GETPOST('search_invoice', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$search_vat = GETPOST('search_vat', 'alpha');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_country = GETPOST('search_country', 'aZ09');
$search_tvaintra = GETPOST('search_tvaintra', 'alpha');

// Define begin binding date
if (empty($search_date_start) && getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
	$search_date_start = $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'));
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "f.datef, f.ref, l.rowid";
}
if (!$sortorder) {
	if (getDolGlobalInt('ACCOUNTING_LIST_SORT_VENTILATION_TODO') > 0) {
		$sortorder = "DESC";
	} else {
		$sortorder = "ASC";
	}
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('accountancycustomerlist'));

$formaccounting = new FormAccounting($db);
$accountingAccount = new AccountingAccount($db);

$chartaccountcode = dol_getIdFromCode($db, getDolGlobalString('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

// Security check
if (!isModEnabled('accounting')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'bind', 'write')) {
	accessforbidden();
}


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

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$search_societe = '';
		$search_lineid = '';
		$search_ref = '';
		$search_invoice = '';
		$search_label = '';
		$search_desc = '';
		$search_amount = '';
		$search_account = '';
		$search_vat = '';
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_country = '';
		$search_tvaintra = '';
	}

	// Mass actions
	$objectclass = 'AccountingAccount';
	$permissiontoread = $user->hasRight('accounting', 'read');
	$permissiontodelete = $user->hasRight('accounting', 'delete');
	$uploaddir = $conf->accounting->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


if ($massaction == 'ventil' && $user->hasRight('accounting', 'bind', 'write')) {
	$msg = '';

	//print '<div><span style="color:red">' . $langs->trans("Processing") . '...</span></div>';
	if (!empty($mesCasesCochees)) {
		$msg = '<div>'.$langs->trans("SelectedLines").': '.count($mesCasesCochees).'</div>';
		$msg .= '<div class="detail">';
		$cpt = 0;
		$ok = 0;
		$ko = 0;

		foreach ($mesCasesCochees as $maLigneCochee) {
			$maLigneCourante = explode("_", $maLigneCochee);
			$monId = $maLigneCourante[0];
			$monCompte = GETPOST('codeventil'.$monId);

			if ($monCompte <= 0) {
				$msg .= '<div><span style="color:red">'.$langs->trans("Lineofinvoice").' '.$monId.' - '.$langs->trans("NoAccountSelected").'</span></div>';
				$ko++;
			} else {
				$sql = " UPDATE ".MAIN_DB_PREFIX."facturedet";
				$sql .= " SET fk_code_ventilation = ".((int) $monCompte);
				$sql .= " WHERE rowid = ".((int) $monId);

				$accountventilated = new AccountingAccount($db);
				$accountventilated->fetch($monCompte, '', 1);

				dol_syslog("accountancy/customer/list.php", LOG_DEBUG);
				if ($db->query($sql)) {
					$msg .= '<div><span style="color:green">'.$langs->trans("Lineofinvoice").' '.$monId.' - '.$langs->trans("VentilatedinAccount").' : '.length_accountg($accountventilated->account_number).'</span></div>';
					$ok++;
				} else {
					$msg .= '<div><span style="color:red">'.$langs->trans("ErrorDB").' : '.$langs->trans("Lineofinvoice").' '.$monId.' - '.$langs->trans("NotVentilatedinAccount").' : '.length_accountg($accountventilated->account_number).'<br> <pre>'.$sql.'</pre></span></div>';
					$ko++;
				}
			}

			$cpt++;
		}
		$msg .= '</div>';
		$msg .= '<div>'.$langs->trans("EndProcessing").'</div>';
	}
}

if (GETPOST('sortfield') == 'f.datef, f.ref, l.rowid') {
	$value = (GETPOST('sortorder') == 'asc,asc,asc' ? 0 : 1);
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	$res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_TODO", $value, 'yesno', 0, '', $conf->entity);
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

llxHeader('', $langs->trans("CustomersVentilation"), $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-accountancy accountancy-customer page-list');

if (empty($chartaccountcode)) {
	print $langs->trans("ErrorChartOfAccountSystemNotSelected");
	// End of page
	llxFooter();
	$db->close();
	exit;
}

// Customer Invoice lines
$sql = "SELECT f.rowid as facid, f.ref, f.datef, f.type as ftype, f.situation_cycle_ref, f.fk_facture_source,";
$sql .= " l.rowid, l.fk_product, l.description, l.total_ht, l.situation_percent, l.fk_code_ventilation, l.product_type as type_l, l.tva_tx as tva_tx_line, l.vat_src_code,";
$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.tva_tx as tva_tx_prod,";
if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " ppe.accountancy_code_sell as code_sell, ppe.accountancy_code_sell_intra as code_sell_intra, ppe.accountancy_code_sell_export as code_sell_export,";
	$sql .= " ppe.accountancy_code_buy as code_buy, ppe.accountancy_code_buy_intra as code_buy_intra, ppe.accountancy_code_buy_export as code_buy_export,";
} else {
	$sql .= " p.accountancy_code_sell as code_sell, p.accountancy_code_sell_intra as code_sell_intra, p.accountancy_code_sell_export as code_sell_export,";
	$sql .= " p.accountancy_code_buy as code_buy, p.accountancy_code_buy_intra as code_buy_intra, p.accountancy_code_buy_export as code_buy_export,";
}
$sql .= " p.tosell as status, p.tobuy as status_buy,";
$sql .= " aa.rowid as aarowid, aa2.rowid as aarowid_intra, aa3.rowid as aarowid_export, aa4.rowid as aarowid_thirdparty,";
$sql .= " co.code as country_code, co.label as country_label,";
$sql .= " s.rowid as socid, s.nom as name, s.tva_intra, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur,";
if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
	$sql .= " spe.accountancy_code_customer as code_compta_client,";
	$sql .= " spe.accountancy_code_supplier as code_compta_fournisseur,";
	$sql .= " spe.accountancy_code_sell as company_code_sell";
} else {
	$sql .= " s.code_compta as code_compta_client,";
	$sql .= " s.code_compta_fournisseur,";
	$sql .= " s.accountancy_code_sell as company_code_sell";
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON co.rowid = s.fk_pays ";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."facturedet as l ON f.rowid = l.fk_facture";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = l.fk_product";
if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
}
$alias_societe_perentity = !getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED') ? "s" : "spe";
$alias_product_perentity = !getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p" : "ppe";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa  ON " . $alias_product_perentity . ".accountancy_code_sell = aa.account_number         AND aa.active = 1  AND aa.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND aa.entity = ".$conf->entity;
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa2 ON " . $alias_product_perentity . ".accountancy_code_sell_intra = aa2.account_number  AND aa2.active = 1 AND aa2.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND aa2.entity = ".$conf->entity;
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa3 ON " . $alias_product_perentity . ".accountancy_code_sell_export = aa3.account_number AND aa3.active = 1 AND aa3.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND aa3.entity = ".$conf->entity;
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa4 ON " . $alias_societe_perentity . ".accountancy_code_sell = aa4.account_number        AND aa4.active = 1 AND aa4.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND aa4.entity = ".$conf->entity;

$sql .= " WHERE f.fk_statut > 0 AND l.fk_code_ventilation <= 0";
$sql .= " AND l.product_type <= 2";
// Add search filter like
if ($search_societe) {
	$sql .= natural_search('s.nom', $search_societe);
}
if ($search_lineid) {
	$sql .= natural_search("l.rowid", $search_lineid, 1);
}
if (strlen(trim($search_invoice))) {
	$sql .= natural_search("f.ref", $search_invoice);
}
if (strlen(trim($search_ref))) {
	$sql .= natural_search("p.ref", $search_ref);
}
if (strlen(trim($search_label))) {
	$sql .= natural_search("p.label", $search_label);
}
if (strlen(trim($search_desc))) {
	$sql .= natural_search("l.description", $search_desc);
}
if (strlen(trim($search_amount))) {
	$sql .= natural_search("l.total_ht", $search_amount, 1);
}
if (strlen(trim($search_account))) {
	$sql .= natural_search("aa.account_number", $search_account);
}
if (strlen(trim($search_vat))) {
	$sql .= natural_search("l.tva_tx", price2num($search_vat), 1);
}
if ($search_date_start) {
	$sql .= " AND f.datef >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND f.datef <= '".$db->idate($search_date_end)."'";
}
if (strlen(trim($search_country))) {
	$arrayofcode = getCountriesInEEC();
	$country_code_in_EEC = $country_code_in_EEC_without_me = '';
	foreach ($arrayofcode as $key => $value) {
		$country_code_in_EEC .= ($country_code_in_EEC ? "," : "")."'".$value."'";
		if ($value != $mysoc->country_code) {
			$country_code_in_EEC_without_me .= ($country_code_in_EEC_without_me ? "," : "")."'".$value."'";
		}
	}
	if ($search_country == 'special_allnotme') {
		$sql .= " AND co.code <> '".$db->escape($mysoc->country_code)."'";
	} elseif ($search_country == 'special_eec') {
		$sql .= " AND co.code IN (".$db->sanitize($country_code_in_EEC, 1).")";
	} elseif ($search_country == 'special_eecnotme') {
		$sql .= " AND co.code IN (".$db->sanitize($country_code_in_EEC_without_me, 1).")";
	} elseif ($search_country == 'special_noteec') {
		$sql .= " AND co.code NOT IN (".$db->sanitize($country_code_in_EEC, 1).")";
	} else {
		$sql .= natural_search("co.code", $search_country);
	}
}
if (strlen(trim($search_tvaintra))) {
	$sql .= natural_search("s.tva_intra", $search_tvaintra);
}
if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_SITUATION.")";
} else {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_DEPOSIT.",".Facture::TYPE_SITUATION.")";
}
$sql .= " AND f.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("accountancy/customer/list.php", LOG_DEBUG);
// MAX_JOIN_SIZE can be very low (ex: 300000) on some limited configurations (ex: https://www.online.net/fr/hosting/online-perso)
// This big SELECT command may exceed the MAX_JOIN_SIZE limit => Therefore we use SQL_BIG_SELECTS=1 to disable the MAX_JOIN_SIZE security
if ($db->type == 'mysqli') {
	$db->query("SET SQL_BIG_SELECTS=1");
}

$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($search_societe) {
		$param .= '&search_societe='.urlencode($search_societe);
	}
	if ($search_lineid) {
		$param .= '&search_lineid='.urlencode((string) ($search_lineid));
	}
	if ($search_date_startday) {
		$param .= '&search_date_startday='.urlencode((string) ($search_date_startday));
	}
	if ($search_date_startmonth) {
		$param .= '&search_date_startmonth='.urlencode((string) ($search_date_startmonth));
	}
	if ($search_date_startyear) {
		$param .= '&search_date_startyear='.urlencode((string) ($search_date_startyear));
	}
	if ($search_date_endday) {
		$param .= '&search_date_endday='.urlencode((string) ($search_date_endday));
	}
	if ($search_date_endmonth) {
		$param .= '&search_date_endmonth='.urlencode((string) ($search_date_endmonth));
	}
	if ($search_date_endyear) {
		$param .= '&search_date_endyear='.urlencode((string) ($search_date_endyear));
	}
	if ($search_invoice) {
		$param .= '&search_invoice='.urlencode($search_invoice);
	}
	if ($search_ref) {
		$param .= '&search_ref='.urlencode($search_ref);
	}
	if ($search_label) {
		$param .= '&search_label='.urlencode($search_label);
	}
	if ($search_desc) {
		$param .= '&search_desc='.urlencode($search_desc);
	}
	if ($search_amount) {
		$param .= '&search_amount='.urlencode($search_amount);
	}
	if ($search_vat) {
		$param .= '&search_vat='.urlencode($search_vat);
	}
	if ($search_country) {
		$param .= "&search_country=".urlencode($search_country);
	}
	if ($search_tvaintra) {
		$param .= "&search_tvaintra=".urlencode($search_tvaintra);
	}

	$arrayofmassactions = array(
		'set_default_account' => img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("ConfirmPreselectAccount"),
		'ventil' => img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Ventilate")
		//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
		//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	);
	//if ($user->hasRight('mymodule', 'supprimer')) $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	//if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton = '';
	if ($massaction !== 'set_default_account') {
		$massactionbutton = $form->selectMassAction('ventil', $arrayofmassactions, 1);
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">'."\n";
	print '<input type="hidden" name="action" value="ventil">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("InvoiceLines"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

	if ($massaction == 'set_default_account') {
		$formquestion = array();
		$formquestion[] = array('type' => 'other',
			'name' => 'set_default_account',
			'label' => $langs->trans("AccountancyCode"),
			'value' => $formaccounting->select_account('', 'default_account', 1, array(), 0, 0, 'maxwidth200 maxwidthonsmartphone', 'cachewithshowemptyone'));
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmPreselectAccount"), $langs->trans("ConfirmPreselectAccountQuestion", count($toselect)), "confirm_set_default_account", $formquestion, 1, 0, 200, 500, 1);
	}

	print '<span class="opacitymedium">'.$langs->trans("DescVentilTodoCustomer").'</span></br><br>';

	if (!empty($msg)) {
		print $msg.'<br>';
	}

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// We add search filter
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth25" name="search_lineid" value="'.dol_escape_htmltag($search_lineid).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_invoice" value="'.dol_escape_htmltag($search_invoice).'"></td>';
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_desc" value="'.dol_escape_htmltag($search_desc).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_vat" placeholder="%" size="1" value="'.dol_escape_htmltag($search_vat).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75imp" name="search_societe" value="'.dol_escape_htmltag($search_societe).'"></td>';
	print '<td class="liste_titre">';
	print $form->select_country($search_country, 'search_country', '', 0, 'maxwidth100', 'code2', 1, 0, 1);
	//print '<input type="text" class="flat maxwidth50" name="search_country" value="' . dol_escape_htmltag($search_country) . '">';
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_tvaintra" value="'.dol_escape_htmltag($search_tvaintra).'"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="center liste_titre">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("LineId", $_SERVER["PHP_SELF"], "l.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "f.datef, f.ref, l.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("ProductRef", $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("ProductLabel", $_SERVER["PHP_SELF"], "p.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("ProductDescription", $_SERVER["PHP_SELF"], "l.description", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "l.total_ht", "", $param, '', $sortfield, $sortorder, 'right maxwidth50 ');
	print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], "l.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ', '', 1);
	print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Country", $_SERVER["PHP_SELF"], "co.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("VATIntraShort", $_SERVER["PHP_SELF"], "s.tva_intra", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("DataUsedToSuggestAccount", '', '', '', '', '', '', '', 'nowraponall ');
	print_liste_field_titre("AccountAccountingSuggest", '', '', '', '', '', '', '', 'center ');
	$checkpicto = '';
	if ($massactionbutton) {
		$checkpicto = $form->showCheckAddButtons('checkforselect', 1);
	}
	print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
	print "</tr>\n";

	$thirdpartystatic = new Societe($db);
	$facture_static = new Facture($db);
	$facture_static_det = new FactureLigne($db);
	$product_static = new Product($db);


	$accountingaccount_codetotid_cache = array();
	$suggestedaccountingaccountfor = '';  // Initialise (for static analysis)
	$suggestedaccountingaccountbydefaultfor = '';

	while ($i < min($num_lines, $limit)) {
		$objp = $db->fetch_object($result);

		// product_type: 0 = service, 1 = product
		// if product does not exist we use the value of product_type provided in facturedet to define if this is a product or service
		// issue : if we change product_type value in product DB it should differ from the value stored in facturedet DB !
		$code_sell_l = '';
		$code_sell_p = '';
		$code_sell_t = '';

		$thirdpartystatic->id = $objp->socid;
		$thirdpartystatic->name = $objp->name;
		$thirdpartystatic->client = $objp->client;
		$thirdpartystatic->fournisseur = $objp->fournisseur;
		$thirdpartystatic->code_client = $objp->code_client;
		$thirdpartystatic->code_compta = $objp->code_compta_client;		// For backward compatibility
		$thirdpartystatic->code_compta_client = $objp->code_compta_client;
		$thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
		$thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
		$thirdpartystatic->email = $objp->email;
		$thirdpartystatic->country_code = $objp->country_code;
		$thirdpartystatic->tva_intra = $objp->tva_intra;
		$thirdpartystatic->code_compta_product = $objp->company_code_sell;		// The accounting account for product stored on thirdparty object (for level3 suggestion)

		$product_static->ref = $objp->product_ref;
		$product_static->id = $objp->product_id;
		$product_static->type = $objp->type;
		$product_static->label = $objp->product_label;
		$product_static->status = $objp->status;
		$product_static->status_buy = $objp->status_buy;
		$product_static->accountancy_code_sell = $objp->code_sell;
		$product_static->accountancy_code_sell_intra = $objp->code_sell_intra;
		$product_static->accountancy_code_sell_export = $objp->code_sell_export;
		$product_static->accountancy_code_buy = $objp->code_buy;
		$product_static->accountancy_code_buy_intra = $objp->code_buy_intra;
		$product_static->accountancy_code_buy_export = $objp->code_buy_export;
		$product_static->tva_tx = $objp->tva_tx_prod;

		$facture_static->ref = $objp->ref;
		$facture_static->id = $objp->facid;
		$facture_static->type = $objp->ftype;
		$facture_static->date = $db->jdate($objp->datef);
		$facture_static->fk_facture_source = $objp->fk_facture_source;

		$facture_static_det->id = $objp->rowid;
		$facture_static_det->total_ht = $objp->total_ht;
		$facture_static_det->tva_tx = $objp->tva_tx_line;
		$facture_static_det->vat_src_code = $objp->vat_src_code;
		$facture_static_det->product_type = $objp->type_l;
		$facture_static_det->desc = $objp->description;

		$accountingAccountArray = array(
			'dom' => $objp->aarowid,
			'intra' => $objp->aarowid_intra,
			'export' => $objp->aarowid_export,
			'thirdparty' => $objp->aarowid_thirdparty);

		$code_sell_p_notset = '';
		$code_sell_t_notset = '';

		$suggestedid = 0;

		$return = $accountingAccount->getAccountingCodeToBind($thirdpartystatic, $mysoc, $product_static, $facture_static, $facture_static_det, $accountingAccountArray, 'customer');
		if (!is_array($return) && $return < 0) {
			setEventMessage($accountingAccount->error, 'errors');
		} else {
			$suggestedid = $return['suggestedid'];
			$suggestedaccountingaccountfor = $return['suggestedaccountingaccountfor'];
			$suggestedaccountingaccountbydefaultfor = $return['suggestedaccountingaccountbydefaultfor'];
			$code_sell_l = $return['code_l'];
			$code_sell_p = $return['code_p'];
			$code_sell_t = $return['code_t'];
		}
		//var_dump($return);

		if (!empty($code_sell_p)) {
			// Value was defined previously
		} else {
			$code_sell_p_notset = 'color:orange';
		}
		if (empty($code_sell_l) && empty($code_sell_p)) {
			$code_sell_p_notset = 'color:red';
		}
		if ($suggestedaccountingaccountfor == 'eecwithoutvatnumber' && empty($code_sell_p_notset)) {
			$code_sell_p_notset = 'color:orange';
		}

		// $code_sell_l is now default code of product/service
		// $code_sell_p is now code of product/service
		// $code_sell_t is now code of thirdparty
		//var_dump($code_sell_l.' - '.$code_sell_p.' - '.$code_sell_t.' -> '.$suggestedid.' ('.$suggestedaccountingaccountbydefaultfor.' '.$suggestedaccountingaccountfor.')');

		print '<tr class="oddeven">';

		// Line id
		print '<td>'.$facture_static_det->id.'</td>';

		// Ref Invoice
		print '<td class="nowraponall">'.$facture_static->getNomUrl(1).'</td>';

		print '<td class="center">'.dol_print_date($facture_static->date, 'day').'</td>';

		// Ref Product
		print '<td class="tdoverflowmax100">';
		if ($product_static->id > 0) {
			print $product_static->getNomUrl(1);
		}
		if ($product_static->label) {
			print '<br><span class="opacitymedium small">'.dol_escape_htmltag($product_static->label).'</span>';
		}
		print '</td>';

		// Description of line
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($facture_static_det->desc, 1));
		print '<td class="tdoverflowmax150 small classfortooltip" title="'.dol_escape_htmltag($text).'">';
		$trunclength = getDolGlobalInt('ACCOUNTING_LENGTH_DESCRIPTION');
		print dol_trunc($text, $trunclength);
		print '</td>';

		// Amount
		print '<td class="right nowraponall amount">';

		// Create a compensation rate for old situation invoice feature.
		$situation_ratio = 1;
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
			if ($objp->situation_cycle_ref) {
				// Avoid divide by 0
				if ($objp->situation_percent == 0) {
					$situation_ratio = 0;
				} else {
					$line = new FactureLigne($db);
					$line->fetch($objp->rowid);

					// Situation invoices handling
					$prev_progress = $line->get_prev_progress($objp->facid);

					$situation_ratio = ($objp->situation_percent - $prev_progress) / $objp->situation_percent;
				}
			}
			print price($objp->total_ht * $situation_ratio);
		} else {
			print price($objp->total_ht);
		}
		print '</td>';

		// Vat rate
		$code_vat_differ = '';
		if ($product_static->tva_tx !== $facture_static_det->tva_tx && price2num($product_static->tva_tx) && price2num($facture_static_det->tva_tx)) {	// Note: having a vat rate of 0 is often the normal case when sells is intra b2b or to export
			$code_vat_differ = 'warning bold';
		}
		print '<td class="right'.($code_vat_differ ? ' '.$code_vat_differ : '').'">';
		print vatrate($facture_static_det->tva_tx.($facture_static_det->vat_src_code ? ' ('.$facture_static_det->vat_src_code.')' : ''));
		print '</td>';

		// Thirdparty
		print '<td class="tdoverflowmax100">'.$thirdpartystatic->getNomUrl(1, 'customer').'</td>';

		// Country
		$labelcountry = ($objp->country_code && ($langs->trans("Country".$objp->country_code) != "Country".$objp->country_code)) ? $langs->trans("Country".$objp->country_code) : $objp->country_label;
		print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($labelcountry).'">';
		print dol_escape_htmltag($labelcountry);
		print '</td>';

		// VAT Num
		print '<td class="tdoverflowmax80" title="'.dol_escape_htmltag($objp->tva_intra).'">'.dol_escape_htmltag($objp->tva_intra).'</td>';

		// Found accounts
		print '<td class="small">';
		// First show default account for any products
		$s = '1. '.(($facture_static_det->product_type == 1) ? $langs->trans("DefaultForService") : $langs->trans("DefaultForProduct")).': ';
		$shelp = '';
		$ttype = 'help';
		if ($suggestedaccountingaccountbydefaultfor == 'eec') {
			$shelp .= $langs->trans("SaleEEC");
		} elseif ($suggestedaccountingaccountbydefaultfor == 'eecwithvat') {
			$shelp = $langs->trans("SaleEECWithVAT");
		} elseif ($suggestedaccountingaccountbydefaultfor == 'eecwithoutvatnumber') {
			$shelp = $langs->trans("SaleEECWithoutVATNumber");
			$ttype = 'warning';
		} elseif ($suggestedaccountingaccountbydefaultfor == 'export') {
			$shelp .= $langs->trans("SaleExport");
		}
		$s .= ($code_sell_l > 0 ? length_accountg($code_sell_l) : '<span style="'.$code_sell_p_notset.'">'.$langs->trans("NotDefined").'</span>');
		print $form->textwithpicto($s, $shelp, 1, $ttype, '', 0, 2, '', 1);
		// Now show account for product
		if ($product_static->id > 0) {
			print '<br>';
			$s = '2. '.(($facture_static_det->product_type == 1) ? $langs->trans("ThisService") : $langs->trans("ThisProduct")).': ';
			$shelp = '';
			$ttype = 'help';
			if ($suggestedaccountingaccountfor == 'eec') {
				$shelp = $langs->trans("SaleEEC");
			} elseif ($suggestedaccountingaccountfor == 'eecwithvat') {
				$shelp = $langs->trans("SaleEECWithVAT");
			} elseif ($suggestedaccountingaccountfor == 'eecwithoutvatnumber') {
				$shelp = $langs->trans("SaleEECWithoutVATNumber");
				$ttype = 'warning';
			} elseif ($suggestedaccountingaccountfor == 'export') {
				$shelp = $langs->trans("SaleExport");
			}
			$s .= (empty($code_sell_p) ? '<span style="'.$code_sell_p_notset.'">'.$langs->trans("NotDefined").'</span>' : length_accountg($code_sell_p));
			print $form->textwithpicto($s, $shelp, 1, $ttype, '', 0, 2, '', 1);
		} else {
			print '<br>';
			$s = '2. '.(($objp->type_l == 1) ? $langs->trans("ThisService") : $langs->trans("ThisProduct")).': ';
			$shelp = '';
			$s .= $langs->trans("NotDefined");
			print $form->textwithpicto($s, $shelp, 1, 'help', '', 0, 2, '', 1);
		}
		if (getDolGlobalString('ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY')) {
			print '<br>';
			$s = '3. '.(($facture_static_det->product_type == 1) ? $langs->trans("ServiceForThisThirdparty") : $langs->trans("ProductForThisThirdparty")).': ';
			$shelp = '';
			$s .= ($code_sell_t > 0 ? length_accountg($code_sell_t) : '<span style="'.$code_sell_t_notset.'">'.$langs->trans("NotDefined").'</span>');
			print $form->textwithpicto($s, $shelp, 1, 'help', '', 0, 2, '', 1);
		}
		print '</td>';

		// Suggested accounting account
		print '<td>';
		print $formaccounting->select_account(($default_account > 0 && $confirm === 'yes' && in_array($objp->rowid."_".$i, $toselect)) ? $default_account : $suggestedid, 'codeventil'.$facture_static_det->id, 1, array(), 0, 0, 'codeventil maxwidth150 maxwidthonsmartphone', 'cachewithshowemptyone');
		print '</td>';

		// Column with checkbox
		print '<td class="center">';
		$ischecked = 0;
		if (!empty($suggestedid) && $suggestedaccountingaccountfor != '' && $suggestedaccountingaccountfor != 'eecwithoutvatnumber') {
			$ischecked = 1;
		}

		if (!empty($toselect)) {
			$ischecked = 0;
			if (in_array($objp->rowid."_".$i, $toselect)) {
				$ischecked = 1;
			}
		}

		print '<input type="checkbox" class="flat checkforselect checkforselect'.$facture_static_det->id.'" name="toselect[]" value="'.$facture_static_det->id."_".$i.'"'.($ischecked ? " checked" : "").'/>';
		print '</td>';

		print '</tr>';
		$i++;
	}
	if ($num_lines == 0) {
		print '<tr><td colspan="13"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print '</table>';
	print "</div>";

	print '</form>';
} else {
	print $db->error();
}
if ($db->type == 'mysqli') {
	$db->query("SET SQL_BIG_SELECTS=0"); // Enable MAX_JOIN_SIZE limitation
}

// Add code to auto check the box when we select an account
print '<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(".codeventil").change(function() {
		var s=$(this).attr("id").replace("codeventil", "")
		console.log(s+" "+$(this).val());
		if ($(this).val() == -1) jQuery(".checkforselect"+s).prop("checked", false);
		else jQuery(".checkforselect"+s).prop("checked", true);
	});
});
</script>';

// End of page
llxFooter();
$db->close();

<?php
/* Copyright (C) 2002-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013-2019	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015-2007	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Abbes Bahfir			<bafbes@gmail.com>
 * Copyright (C) 2015-2016	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2017		Josep Lluís Amador		<joseplluis@lliuretic.cat>
 * Copyright (C) 2018-2022	Charlene Benke			<charlene@patas-monkey.com>
 * Copyright (C) 2018-2020	Frédéric France			<frederic.france@netlogic.fr>
 * Copyright (C) 2019-2021	Alexandre Spangaro		<aspangaro@open-dsi.fr>
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
 *       \file       htdocs/fourn/facture/list.php
 *       \ingroup    fournisseur,facture
 *       \brief      List of suppliers invoices
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'bills', 'companies', 'projects'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'supplierinvoicelist';
$mode = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

$socid = GETPOST('socid', 'int');

// Security check
if ($user->socid > 0) {
	$action = '';
	$_GET["action"] = '';
	$socid = $user->socid;
}

$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_label = GETPOST("search_label", "alpha");
$search_amount_no_tax = GETPOST("search_amount_no_tax", "alpha");
$search_amount_all_tax = GETPOST("search_amount_all_tax", "alpha");
$search_ref = GETPOST('sf_ref') ?GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_refsupplier = GETPOST('search_refsupplier', 'alpha');
$search_type = GETPOST('search_type', 'int');
$search_project = GETPOST('search_project', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_company_alias = GETPOST('search_company_alias', 'alpha');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_vat = GETPOST('search_montant_vat', 'alpha');
$search_montant_localtax1 = GETPOST('search_montant_localtax1', 'alpha');
$search_montant_localtax2 = GETPOST('search_montant_localtax2', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_multicurrency_code = GETPOST('search_multicurrency_code', 'alpha');
$search_multicurrency_tx = GETPOST('search_multicurrency_tx', 'alpha');
$search_multicurrency_montant_ht = GETPOST('search_multicurrency_montant_ht', 'alpha');
$search_multicurrency_montant_vat = GETPOST('search_multicurrency_montant_vat', 'alpha');
$search_multicurrency_montant_ttc = GETPOST('search_multicurrency_montant_ttc', 'alpha');
$search_status = GETPOST('search_status', 'int');
$search_paymentmode = GETPOST('search_paymentmode', 'int');
$search_paymentcond = GETPOST('search_paymentcond', 'int');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state");
$search_country = GETPOST("search_country", 'int');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$search_user = GETPOST('search_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_date_start = GETPOSTDATE('search_date_start', '', 'tzserver');
$search_date_end = GETPOSTDATE('search_date_end', '23:59:59', 'tzserver');
$search_datelimit_startday = GETPOST('search_datelimit_startday', 'int');
$search_datelimit_startmonth = GETPOST('search_datelimit_startmonth', 'int');
$search_datelimit_startyear = GETPOST('search_datelimit_startyear', 'int');
$search_datelimit_endday = GETPOST('search_datelimit_endday', 'int');
$search_datelimit_endmonth = GETPOST('search_datelimit_endmonth', 'int');
$search_datelimit_endyear = GETPOST('search_datelimit_endyear', 'int');
$search_datelimit_start = dol_mktime(0, 0, 0, $search_datelimit_startmonth, $search_datelimit_startday, $search_datelimit_startyear);
$search_datelimit_end = dol_mktime(23, 59, 59, $search_datelimit_endmonth, $search_datelimit_endday, $search_datelimit_endyear);
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');
$search_categ_sup = trim(GETPOST("search_categ_sup", 'int'));
$search_product_category = GETPOST('search_product_category', 'int');

$option = GETPOST('search_option');
if ($option == 'late') {
	$search_status = '1';
}
$filter = GETPOST('filtre', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "f.datef,f.rowid";
}

$diroutputmassaction = $conf->fournisseur->facture->dir_output.'/temp/massgeneration/'.$user->id;

$now = dol_now();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new FactureFournisseur($db);
$hookmanager->initHooks(array('supplierinvoicelist'));
$extrafields = new ExtraFields($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.ref'=>'Ref',
	'f.ref_supplier'=>'RefSupplier',
	'f.note_public'=>'NotePublic',
	's.nom'=>"ThirdParty",
	'pd.description'=>'Description',
);
if (empty($user->socid)) {
	$fieldstosearchall["f.note_private"] = "NotePrivate";
}

$checkedtypetiers = 0;
$arrayfields = array(
	'f.ref'=>array('label'=>"Ref", 'checked'=>1),
	'f.ref_supplier'=>array('label'=>"RefSupplier", 'checked'=>1),
	'f.type'=>array('label'=>"Type", 'checked'=>0),
	'f.label'=>array('label'=>"Label", 'checked'=>0),
	'f.datef'=>array('label'=>"DateInvoice", 'checked'=>1),
	'f.date_lim_reglement'=>array('label'=>"DateDue", 'checked'=>1),
	'p.ref'=>array('label'=>"ProjectRef", 'checked'=>0),
	's.nom'=>array('label'=>"ThirdParty", 'checked'=>1, 'position'=>41),
	's.name_alias'=>array('label'=>"AliasNameShort", 'checked'=>0, 'position'=>42),
	's.town'=>array('label'=>"Town", 'checked'=>-1, 'position'=>43),
	's.zip'=>array('label'=>"Zip", 'checked'=>1, 'position'=>44),
	'state.nom'=>array('label'=>"StateShort", 'checked'=>0, 'position'=>45),
	'country.code_iso'=>array('label'=>"Country", 'checked'=>0, 'position'=>46),
	'typent.code'=>array('label'=>"ThirdPartyType", 'checked'=>$checkedtypetiers, 'position'=>49),
	'f.fk_cond_reglement'=>array('label'=>"PaymentTerm", 'checked'=>1, 'position'=>50),
	'f.fk_mode_reglement'=>array('label'=>"PaymentMode", 'checked'=>1, 'position'=>52),
	'f.total_ht'=>array('label'=>"AmountHT", 'checked'=>1, 'position'=>105),
	'f.total_vat'=>array('label'=>"AmountVAT", 'checked'=>0, 'position'=>110),
	'f.total_localtax1'=>array('label'=>$langs->transcountry("AmountLT1", $mysoc->country_code), 'checked'=>0, 'enabled'=>$mysoc->localtax1_assuj == "1", 'position'=>95),
	'f.total_localtax2'=>array('label'=>$langs->transcountry("AmountLT2", $mysoc->country_code), 'checked'=>0, 'enabled'=>$mysoc->localtax2_assuj == "1", 'position'=>100),
	'f.total_ttc'=>array('label'=>"AmountTTC", 'checked'=>0, 'position'=>115),
	'dynamount_payed'=>array('label'=>"Paid", 'checked'=>0, 'position'=>116),
	'rtp'=>array('label'=>"Rest", 'checked'=>0, 'position'=>117),
	'f.multicurrency_code'=>array('label'=>'Currency', 'checked'=>0, 'position'=>205, 'enabled'=>(!isModEnabled("multicurrency") ? 0 : 1)),
	'f.multicurrency_tx'=>array('label'=>'CurrencyRate', 'checked'=>0, 'position'=>206, 'enabled'=>(!isModEnabled("multicurrency") ? 0 : 1)),
	'f.multicurrency_total_ht'=>array('label'=>'MulticurrencyAmountHT', 'position'=>207, 'checked'=>0, 'enabled'=>(!isModEnabled("multicurrency") ? 0 : 1)),
	'f.multicurrency_total_vat'=>array('label'=>'MulticurrencyAmountVAT', 'position'=>208, 'checked'=>0, 'enabled'=>(!isModEnabled("multicurrency") ? 0 : 1)),
	'f.multicurrency_total_ttc'=>array('label'=>'MulticurrencyAmountTTC', 'position'=>209, 'checked'=>0, 'enabled'=>(!isModEnabled("multicurrency") ? 0 : 1)),
	'multicurrency_dynamount_payed'=>array('label'=>'MulticurrencyAlreadyPaid', 'position'=>210, 'checked'=>0, 'enabled'=>(!isModEnabled("multicurrency") ? 0 : 1)),
	'multicurrency_rtp'=>array('label'=>'MulticurrencyRemainderToPay', 'checked'=>0, 'position'=>211, 'enabled'=>(!isModEnabled("multicurrency") ? 0 : 1)), // Not enabled by default because slow
	'u.login'=>array('label'=>"Author", 'checked'=>1, 'position'=>500),
	'f.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>501),
	'f.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>502),
	'f.fk_statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

if (!isModEnabled('supplier_invoice')) {
	accessforbidden();
}
if ((!$user->hasRight("fournisseur", "facture", "lire") && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD))
	|| (!$user->hasRight("supplier_invoice", "lire") && !empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD))) {
	accessforbidden();
}

$permissiontoread = ($user->hasRight("fournisseur", "facture", "lire") || $user->hasRight("supplier_invoice", "lire"));
$permissiontoadd = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"));
$permissiontodelete = ($user->hasRight("fournisseur", "facture", "supprimer") || $user->hasRight("supplier_invoice", "supprimer"));


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

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) {		// All tests must be present to be compatible with all browsers
		$search_all = "";
		$search_user = '';
		$search_sale = '';
		$search_product_category = '';
		$search_ref = "";
		$search_refsupplier = "";
		$search_type = "";
		$search_label = "";
		$search_project = '';
		$search_company = "";
		$search_company_alias = "";
		$search_amount_no_tax = "";
		$search_amount_all_tax = "";
		$search_montant_ht = '';
		$search_montant_vat = '';
		$search_montant_localtax1 = '';
		$search_montant_localtax2 = '';
		$search_montant_ttc = '';
		$search_login = '';
		$search_multicurrency_code = '';
		$search_multicurrency_tx = '';
		$search_multicurrency_montant_ht = '';
		$search_multicurrency_montant_vat = '';
		$search_multicurrency_montant_ttc = '';
		$search_status = '';
		$search_paymentmode = '';
		$search_paymentcond = '';
		$search_town = '';
		$search_zip = "";
		$search_state = "";
		$search_type = '';
		$search_country = '';
		$search_type_thirdparty = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_datelimit_startday = '';
		$search_datelimit_startmonth = '';
		$search_datelimit_startyear = '';
		$search_datelimit_endday = '';
		$search_datelimit_endmonth = '';
		$search_datelimit_endyear = '';
		$search_datelimit_start = '';
		$search_datelimit_end = '';
		$toselect = array();
		$search_array_options = array();
		$search_categ_sup = 0;
		$filter = '';
		$option = '';
		$socid = "";
	}

	// Mass actions
	$objectclass = 'FactureFournisseur';
	$objectlabel = 'SupplierInvoices';
	$uploaddir = $conf->fournisseur->facture->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if ($massaction == 'banktransfertrequest') {
		$langs->load("withdrawals");

		if (!$user->hasRight("paymentbybanktransfer", "create")) {
			$error++;
			setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
		} else {
			//Checking error
			$error = 0;

			$arrayofselected = is_array($toselect) ? $toselect : array();
			$listofbills = array();
			foreach ($arrayofselected as $toselectid) {
				$objecttmp = new FactureFournisseur($db);
				$result = $objecttmp->fetch($toselectid);
				if ($result > 0) {
					$totalpaid = $objecttmp->getSommePaiement();
					$totalcreditnotes = $objecttmp->getSumCreditNotesUsed();
					$totaldeposits = $objecttmp->getSumDepositsUsed();
					$objecttmp->resteapayer = price2num($objecttmp->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');
					if ($objecttmp->statut == FactureFournisseur::STATUS_DRAFT) {
						$error++;
						setEventMessages($objecttmp->ref.' '.$langs->trans("Draft"), $objecttmp->errors, 'errors');
					} elseif ($objecttmp->paye || $objecttmp->resteapayer == 0) {
						$error++;
						setEventMessages($objecttmp->ref.' '.$langs->trans("AlreadyPaid"), $objecttmp->errors, 'errors');
					} elseif ($objecttmp->resteapayer < 0) {
						$error++;
						setEventMessages($objecttmp->ref.' '.$langs->trans("AmountMustBePositive"), $objecttmp->errors, 'errors');
					}

					$rsql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande";
					$rsql .= " , pfd.date_traite as date_traite";
					$rsql .= " , pfd.amount";
					$rsql .= " , u.rowid as user_id, u.lastname, u.firstname, u.login";
					$rsql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
					$rsql .= " , ".MAIN_DB_PREFIX."user as u";
					$rsql .= " WHERE fk_facture_fourn = ".((int) $objecttmp->id);
					$rsql .= " AND pfd.fk_user_demande = u.rowid";
					$rsql .= " AND pfd.traite = 0";
					$rsql .= " ORDER BY pfd.date_demande DESC";

					$result_sql = $db->query($rsql);
					if ($result_sql) {
						$numprlv = $db->num_rows($result_sql);
					}

					if ($numprlv > 0) {
						$error++;
						setEventMessages($objecttmp->ref.' '.$langs->trans("RequestAlreadyDone"), $objecttmp->errors, 'warnings');
					} elseif (!empty($objecttmp->mode_reglement_code) && $objecttmp->mode_reglement_code != 'VIR') {
						$error++;
						setEventMessages($objecttmp->ref.' '.$langs->trans("BadPaymentMethod"), $objecttmp->errors, 'errors');
					} else {
						$listofbills[] = $objecttmp; // $listofbills will only contains invoices with good payment method and no request already done
					}
				}
			}

			// Massive withdraw request for request with no errors
			if (!empty($listofbills)) {
				$nbwithdrawrequestok = 0;
				foreach ($listofbills as $aBill) {
					$db->begin();
					$result = $aBill->demande_prelevement($user, $aBill->resteapayer, 'bank-transfer', 'supplier_invoice');
					if ($result > 0) {
						$db->commit();
						$nbwithdrawrequestok++;
					} else {
						$db->rollback();
						setEventMessages($aBill->error, $aBill->errors, 'errors');
					}
				}
				if ($nbwithdrawrequestok > 0) {
					setEventMessages($langs->trans("WithdrawRequestsDone", $nbwithdrawrequestok), null, 'mesgs');
				}
			}
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic = new Account($db);
$facturestatic = new FactureFournisseur($db);
$formcompany = new FormCompany($db);
$thirdparty = new Societe($db);

$now = dol_now();

$title = $langs->trans("BillsSuppliers").($socid ? ' '.$soc->name : '');
$help_url = 'EN:Suppliers_Invoices|FR:FactureFournisseur|ES:Facturas_de_proveedores';

// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT";
if ($search_all) {
	$sql = 'SELECT DISTINCT';
}
$sql .= " f.rowid as facid, f.ref, f.ref_supplier, f.type, f.datef, f.date_lim_reglement as datelimite, f.fk_mode_reglement, f.fk_cond_reglement,";
$sql .= " f.total_ht, f.total_ttc, f.total_tva as total_vat, f.paye as paye, f.fk_statut as fk_statut, f.libelle as label, f.datec as date_creation, f.tms as date_update,";
$sql .= " f.localtax1 as total_localtax1, f.localtax2 as total_localtax2,";
$sql .= ' f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva as multicurrency_total_vat, f.multicurrency_total_ttc,';
$sql .= " f.note_public, f.note_private,";
$sql .= " f.fk_user_author,";
$sql .= " s.rowid as socid, s.nom as name, s.name_alias as alias, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,";
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " country.code as country_code,";
$sql .= " p.rowid as project_id, p.ref as project_ref, p.title as project_label,";
$sql .= ' u.login, u.lastname, u.firstname, u.email as user_email, u.statut as user_statut, u.entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender';
// We need dynamount_payed to be able to sort on status (value is surely wrong because we can count several lines several times due to other left join or link with contacts. But what we need is just 0 or > 0)
// TODO Better solution to be able to sort on already payed or remain to pay is to store amount_payed in a denormalized field.
if (!$search_all) {
	$sql .= ', SUM(pf.amount) as dynamount_payed';
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= ', '.MAIN_DB_PREFIX.'facture_fourn as f';
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (f.rowid = ef.fk_object)";
}
if (!$search_all) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
}
if ($search_all) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_det as pd ON f.rowid=pd.fk_facture_fourn';
}
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user AS u ON f.fk_user_author = u.rowid';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = f.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->hasRight("societe", "client", "voir") && !$socid)) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
if ($search_user > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' WHERE f.fk_soc = s.rowid';
$sql .= ' AND f.entity IN ('.getEntity('facture_fourn').')';
if (!$user->hasRight("societe", "client", "voir") && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($socid > 0) {
	$sql .= ' AND s.rowid = '.((int) $socid);
}
if ($search_ref) {
	if (is_numeric($search_ref)) {
		$sql .= natural_search(array('f.ref'), $search_ref);
	} else {
		$sql .= natural_search('f.ref', $search_ref);
	}
}
if ($search_ref) {
	$sql .= natural_search('f.ref', $search_ref);
}
if ($search_refsupplier) {
	$sql .= natural_search('f.ref_supplier', $search_refsupplier);
}
if ($search_type != '' && $search_type >= 0) {
	if ($search_type == '0') {
		$sql .= " AND f.type = 0"; // standard
	}
	if ($search_type == '1') {
		$sql .= " AND f.type = 1"; // replacement
	}
	if ($search_type == '2') {
		$sql .= " AND f.type = 2"; // credit note
	}
	if ($search_type == '3') {
		$sql .= " AND f.type = 3"; // deposit
	}
	//if ($search_type == '4') $sql.=" AND f.type = 4";  // proforma
	//if ($search_type == '5') $sql.=" AND f.type = 5";  // situation
}
if ($search_project) {
	$sql .= natural_search('p.ref', $search_project);
}
if (empty($arrayfields['s.name_alias']['checked']) && $search_company) {
	$sql .= natural_search(array("s.nom", "s.name_alias"), $search_company);
} else {
	if ($search_company) {
		$sql .= natural_search('s.nom', $search_company);
	}
	if ($search_company_alias) {
		$sql .= natural_search('s.name_alias', $search_company_alias);
	}
}
if ($search_town) {
	$sql .= natural_search('s.town', $search_town);
}
if ($search_zip) {
	$sql .= natural_search("s.zip", $search_zip);
}
if ($search_state) {
	$sql .= natural_search("state.nom", $search_state);
}
if ($search_country) {
	$sql .= " AND s.fk_pays IN (".$db->sanitize($search_country).')';
}
if ($search_type_thirdparty != '' && $search_type_thirdparty >= 0) {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($search_type_thirdparty).')';
}
if ($search_montant_ht != '') {
	$sql .= natural_search('f.total_ht', $search_montant_ht, 1);
}
if ($search_montant_vat != '') {
	$sql .= natural_search('f.total_tva', $search_montant_vat, 1);
}
if ($search_montant_localtax1 != '') {
	$sql .= natural_search('f.localtax1', $search_montant_localtax1, 1);
}
if ($search_montant_localtax2 != '') {
	$sql .= natural_search('f.localtax2', $search_montant_localtax2, 1);
}
if ($search_montant_ttc != '') {
	$sql .= natural_search('f.total_ttc', $search_montant_ttc, 1);
}
if ($search_multicurrency_code != '') {
	$sql .= " AND f.multicurrency_code = '".$db->escape($search_multicurrency_code)."'";
}
if ($search_multicurrency_tx != '') {
	$sql .= natural_search('f.multicurrency_tx', $search_multicurrency_tx, 1);
}
if ($search_multicurrency_montant_ht != '') {
	$sql .= natural_search('f.multicurrency_total_ht', $search_multicurrency_montant_ht, 1);
}
if ($search_multicurrency_montant_vat != '') {
	$sql .= natural_search('f.multicurrency_total_tva', $search_multicurrency_montant_vat, 1);
}
if ($search_multicurrency_montant_ttc != '') {
	$sql .= natural_search('f.multicurrency_total_ttc', $search_multicurrency_montant_ttc, 1);
}
if ($search_login) {
	$sql .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_login);
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND f.fk_statut = ".((int) $search_status);
}
if ($search_paymentmode > 0) {
	$sql .= " AND f.fk_mode_reglement = ".((int) $search_paymentmode);
}
if ($search_paymentcond > 0) {
	$sql .= " AND f.fk_cond_reglement = ".((int) $search_paymentcond);
}
if ($search_date_start) {
	$sql .= " AND f.datef >= '" . $db->idate($search_date_start) . "'";
}
if ($search_date_end) {
	$sql .= " AND f.datef <= '" . $db->idate($search_date_end) . "'";
}
if ($search_datelimit_start) {
	$sql .= " AND f.date_lim_reglement >= '" . $db->idate($search_datelimit_start) . "'";
}
if ($search_datelimit_end) {
	$sql .= " AND f.date_lim_reglement <= '" . $db->idate($search_datelimit_end) . "'";
}
if ($option == 'late') {
	$sql .= " AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->fournisseur->warning_delay)."'";
}
if ($search_label) {
	$sql .= natural_search('f.libelle', $search_label);
}
$searchCategorySupplierList = $search_categ_sup ? array($search_categ_sup) : array();
$searchCategorySupplierOperator = 0;
// Search for tag/category ($searchCategorySupplierList is an array of ID)
if (!empty($searchCategorySupplierList)) {
	$searchCategorySupplierSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategorySupplierList as $searchCategorySupplier) {
		if (intval($searchCategorySupplier) == -2) {
			$searchCategorySupplierSqlList[] = "NOT EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc)";
		} elseif (intval($searchCategorySupplier) > 0) {
			if ($searchCategorySupplierOperator == 0) {
				$searchCategorySupplierSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie = ".((int) $searchCategorySupplier).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategorySupplier);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategorySupplierSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategorySupplierOperator == 1) {
		if (!empty($searchCategorySupplierSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategorySupplierSqlList).")";
		}
	} else {
		if (!empty($searchCategorySupplierSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategorySupplierSqlList).")";
		}
	}
}
// Search for tag/category ($searchCategoryProductList is an array of ID)
$searchCategoryProductList = $search_product_category ? array($search_product_category) : array();
$searchCategoryProductOperator = 0;
if (!empty($searchCategoryProductList)) {
	$searchCategoryProductSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."facture_fourn_det as fd WHERE fd.fk_facture_fourn = f.rowid AND fd.fk_product = ck.fk_product)";
		} elseif (intval($searchCategoryProduct) > 0) {
			if ($searchCategoryProductOperator == 0) {
				$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."facture_fourn_det as fd WHERE fd.fk_facture_fourn = f.rowid AND fd.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."facture_fourn_det as fd WHERE fd.fk_facture_fourn = f.rowid AND fd.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryProductOperator == 1) {
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
		}
	} else {
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryProductSqlList).")";
		}
	}
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND f.fk_statut = ".((int) $search_status);
}
if ($filter && $filter != -1) {
	$aFilter = explode(',', $filter);
	foreach ($aFilter as $fil) {
		$filt = explode(':', $fil);
		$sql .= " AND ".$db->escape(trim($filt[0]))." = '".$db->escape(trim($filt[1]))."'";
	}
}
if ($search_sale > 0) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $search_sale);
}
if ($search_user > 0) {
	$sql .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='invoice_supplier' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".((int) $search_user);
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if (!$search_all) {
	$sql .= " GROUP BY f.rowid, f.ref, f.ref_supplier, f.type, f.datef, f.date_lim_reglement, f.fk_mode_reglement, f.fk_cond_reglement,";
	$sql .= " f.total_ht, f.total_ttc, f.total_tva, f.paye, f.fk_statut, f.libelle, f.datec, f.tms,";
	$sql .= " f.localtax1, f.localtax2,";
	$sql .= ' f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc,';
	$sql .= " f.note_public, f.note_private,";
	$sql .= " f.fk_user_author,";
	$sql .= ' s.rowid, s.nom, s.name_alias, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,';
	$sql .= " typent.code,";
	$sql .= " state.code_departement, state.nom,";
	$sql .= ' country.code,';
	$sql .= " p.rowid, p.ref, p.title,";
	$sql .= " u.login, u.lastname, u.firstname, u.email, u.statut, u.entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender";
	if (!empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			//prevent error with sql_mode=only_full_group_by
			$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ",ef.".$key : '');
		}
	}
	// Add GroupBy from hooks
	$parameters = array('all' => $search_all, 'fieldstosearchall' => $fieldstosearchall);
	$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
} else {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

// Add HAVING from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= empty($hookmanager->resPrint) ? "" : " HAVING 1=1 ".$hookmanager->resPrint;

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
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}
//print $sql;

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all) {
	$obj = $db->fetch_object($resql);
	$id = $obj->facid;

	header("Location: ".DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$id);
	exit;
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

if ($socid) {
	$soc = new Societe($db);
	$soc->fetch($socid);
	if (empty($search_company)) {
		$search_company = $soc->name;
		$search_company_alias = $soc->name_alias;
	}
}

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '&socid='.$socid;
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
if ($search_all) {
	$param .= '&search_all='.urlencode($search_all);
}
if ($search_date_start) {
	$param .= buildParamDate('search_date_start', null, '', 'tzserver');
}
if ($search_date_end) {
	$param .= buildParamDate('search_date_end', null, '', 'tzserver');
}
if ($search_datelimit_startday) {
	$param .= '&search_datelimit_startday='.urlencode($search_datelimit_startday);
}
if ($search_datelimit_startmonth) {
	$param .= '&search_datelimit_startmonth='.urlencode($search_datelimit_startmonth);
}
if ($search_datelimit_startyear) {
	$param .= '&search_datelimit_startyear='.urlencode($search_datelimit_startyear);
}
if ($search_datelimit_endday) {
	$param .= '&search_datelimit_endday='.urlencode($search_datelimit_endday);
}
if ($search_datelimit_endmonth) {
	$param .= '&search_datelimit_endmonth='.urlencode($search_datelimit_endmonth);
}
if ($search_datelimit_endyear) {
	$param .= '&search_datelimit_endyear='.urlencode($search_datelimit_endyear);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_refsupplier) {
	$param .= '&search_refsupplier='.urlencode($search_refsupplier);
}
if ($search_type != '') {
	$param .= '&search_type='.urlencode($search_type);
}
if ($search_label) {
	$param .= '&search_label='.urlencode($search_label);
}
if ($search_company) {
	$param .= '&search_company='.urlencode($search_company);
}
if ($search_company_alias) {
	$param .= '&search_company_alias='.urlencode($search_company_alias);
}
if ($search_login) {
	$param .= '&search_login='.urlencode($search_login);
}
if ($search_montant_ht != '') {
	$param .= '&search_montant_ht='.urlencode($search_montant_ht);
}
if ($search_montant_vat != '') {
	$param .= '&search_montant_vat='.urlencode($search_montant_vat);
}
if ($search_montant_localtax1 != '') {
	$param .= '&search_montant_localtax1='.urlencode($search_montant_localtax1);
}
if ($search_montant_localtax2 != '') {
	$param .= '&search_montant_localtax2='.urlencode($search_montant_localtax2);
}
if ($search_montant_ttc != '') {
	$param .= '&search_montant_ttc='.urlencode($search_montant_ttc);
}
if ($search_multicurrency_code != '') {
	$param .= '&search_multicurrency_code='.urlencode($search_multicurrency_code);
}
if ($search_multicurrency_tx != '') {
	$param .= '&search_multicurrency_tx='.urlencode($search_multicurrency_tx);
}
if ($search_multicurrency_montant_ht != '') {
	$param .= '&search_multicurrency_montant_ht='.urlencode($search_multicurrency_montant_ht);
}
if ($search_multicurrency_montant_vat != '') {
	$param .= '&search_multicurrency_montant_vat='.urlencode($search_multicurrency_montant_vat);
}
if ($search_multicurrency_montant_ttc != '') {
	$param .= '&search_multicurrency_montant_ttc='.urlencode($search_multicurrency_montant_ttc);
}
if ($search_amount_no_tax) {
	$param .= '&search_amount_no_tax='.urlencode($search_amount_no_tax);
}
if ($search_amount_all_tax) {
	$param .= '&search_amount_all_tax='.urlencode($search_amount_all_tax);
}
if ($search_status >= 0) {
	$param .= "&search_status=".urlencode($search_status);
}
if ($search_paymentmode) {
	$param .= '&search_paymentmode='.urlencode($search_paymentmode);
}
if ($search_paymentcond) {
	$param .= '&search_paymentcond='.urlencode($search_paymentcond);
}
if ($show_files) {
	$param .= '&show_files='.urlencode($show_files);
}
if ($option) {
	$param .= "&search_option=".urlencode($option);
}
if ($search_categ_sup > 0) {
	$param .= '&search_categ_sup='.urlencode($search_categ_sup);
}
if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
	$param .= '&search_type_thirdparty='.urlencode($search_type_thirdparty);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
	'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);

if (isModEnabled('paymentbybanktransfer') && $user->hasRight("paymentbybanktransfer", "create")) {
	$langs->load('withdrawals');
	$arrayofmassactions['banktransfertrequest'] = img_picto('', 'payment', 'class="pictofixedwidth"').$langs->trans("MakeBankTransferOrder");
}
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$url = DOL_URL_ROOT.'/fourn/facture/card.php?action=create';
if (!empty($socid)) {
	$url .= '&socid='.urlencode($socid);
}

$i = 0;
print '<form method="POST" id="searchFormList" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="socid" value="'.$socid.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss'=>'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss'=>'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewBill'), '', 'fa fa-plus-circle', $url, '', ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer")));

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'supplier_invoice', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "SendBillRef";
$modelmail = "invoice_supplier_send";
$objecttmp = new FactureFournisseur($db);
$trackid = 'sinv'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if SUPPLIER_INVOICE_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>'."\n";
}

// If the user can view prospects other than his'
$moreforfilter = '';
if ($user->hasRight("user", "user", "lire")) {
	$langs->load("commercial");
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth200');
	$moreforfilter .= '</div>';
}
// If the user can view prospects other than his'
if ($user->hasRight("user", "user", "lire")) {
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('LinkedToSpecificUsers');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_user, 'search_user', $tmptitle, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
	$moreforfilter .= '</div>';
}
// If the user can view prospects other than his'
if (isModEnabled('categorie') && $user->hasRight('categorie', 'lire') && ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire'))) {
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('IncludingProductWithTag');
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, 0, 'maxwidth300 widthcentpercentminusx', 1);
	$moreforfilter .= '</div>';
}

if (isModEnabled('categorie')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('SuppliersCategoriesShort');
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$formother->select_categories('supplier', $search_categ_sup, 'search_categ_sup', 1, $tmptitle);
	$moreforfilter .= '</div>';
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
// Ref
if (!empty($arrayfields['f.ref']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
// Ref supplier
if (!empty($arrayfields['f.ref_supplier']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_refsupplier" value="'.dol_escape_htmltag($search_refsupplier).'">';
	print '</td>';
}
// Type
if (!empty($arrayfields['f.type']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone">';
	$listtype = array(
		FactureFournisseur::TYPE_STANDARD=>$langs->trans("InvoiceStandard"),
		FactureFournisseur::TYPE_REPLACEMENT=>$langs->trans("InvoiceReplacement"),
		FactureFournisseur::TYPE_CREDIT_NOTE=>$langs->trans("InvoiceAvoir"),
		FactureFournisseur::TYPE_DEPOSIT=>$langs->trans("InvoiceDeposit"),
	);
	/*
	 if (!empty($conf->global->INVOICE_USE_SITUATION))
	 {
	 $listtype[Facture::TYPE_SITUATION] = $langs->trans("InvoiceSituation");
	 }
	 */
	//$listtype[Facture::TYPE_PROFORMA]=$langs->trans("InvoiceProForma");     // A proformat invoice is not an invoice but must be an order.
	print $form->selectarray('search_type', $listtype, $search_type, 1, 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth100');
	print '</td>';
}
// Label
if (!empty($arrayfields['f.label']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';
}
// Date invoice
if (!empty($arrayfields['f.datef']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Date due
if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	/*
	 print $langs->trans('From').' ';
	 print $form->selectDate($search_datelimit_start ? $search_datelimit_start : -1, 'search_datelimit_start', 0, 0, 1);
	 print '</div>';
	 print '<div class="nowrap">';
	 print $langs->trans('to').' ';*/
	print $form->selectDate($search_datelimit_end ? $search_datelimit_end : -1, 'search_datelimit_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("Before"));
	print '<br><input type="checkbox" name="search_option" value="late"'.($option == 'late' ? ' checked' : '').'> '.$langs->trans("Alert");
	print '</div>';
	print '</td>';
}
// Project
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_project" value="'.dol_escape_htmltag($search_project).'"></td>';
}
// Thirpdarty
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_company" value="'.dol_escape_htmltag($search_company).'"'.($socid > 0 ? " disabled" : "").'></td>';
}
// Alias
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_company_alias" value="'.dol_escape_htmltag($search_company_alias).'"></td>';
}
// Town
if (!empty($arrayfields['s.town']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
}
// Zip
if (!empty($arrayfields['s.zip']['checked'])) {
	print '<td class="liste_titre center"><input class="flat maxwidth50" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
}
// State
if (!empty($arrayfields['state.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}
// Country
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
	print '</td>';
}
// Company type
if (!empty($arrayfields['typent.code']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), '', 1);
	print '</td>';
}
// Condition of payment
if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
	print '<td class="liste_titre left">';
	print $form->getSelectConditionsPaiements($search_paymentcond, 'search_paymentcond', -1, 1, 1, 'maxwidth100');
	print '</td>';
}
// Payment mode
if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
	print '<td class="liste_titre left">';
	print $form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 1, 1, 20, 1, 'maxwidth100', 1);
	print '</td>';
}
if (!empty($arrayfields['f.total_ht']['checked'])) {
	// Amount without tax
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_vat']['checked'])) {
	// Amount vat
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_localtax1']['checked'])) {
	// Amount tax 1
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_localtax1" value="'.dol_escape_htmltag($search_montant_localtax1).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_localtax2']['checked'])) {
	// Amount tax 2
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_localtax2" value="'.dol_escape_htmltag($search_montant_localtax2).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_ttc']['checked'])) {
	// Amount inc tac
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
	print '</td>';
}
if (!empty($arrayfields['u.login']['checked'])) {
	// Author
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" size="4" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
	print '</td>';
}
if (!empty($arrayfields['dynamount_payed']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['rtp']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['f.multicurrency_code']['checked'])) {
	// Currency
	print '<td class="liste_titre">';
	print $form->selectMultiCurrency($search_multicurrency_code, 'search_multicurrency_code', 1);
	print '</td>';
}
if (!empty($arrayfields['f.multicurrency_tx']['checked'])) {
	// Currency rate
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_tx" value="'.dol_escape_htmltag($search_multicurrency_tx).'">';
	print '</td>';
}
if (!empty($arrayfields['f.multicurrency_total_ht']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ht" value="'.dol_escape_htmltag($search_multicurrency_montant_ht).'">';
	print '</td>';
}
if (!empty($arrayfields['f.multicurrency_total_vat']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_vat" value="'.dol_escape_htmltag($search_multicurrency_montant_vat).'">';
	print '</td>';
}
if (!empty($arrayfields['f.multicurrency_total_ttc']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ttc" value="'.dol_escape_htmltag($search_multicurrency_montant_ttc).'">';
	print '</td>';
}
if (!empty($arrayfields['multicurrency_dynamount_payed']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['multicurrency_rtp']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (!empty($arrayfields['f.datec']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['f.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (!empty($arrayfields['f.fk_statut']['checked'])) {
	print '<td class="liste_titre right parentonrightofpage">';
	$liststatus = array('0'=>$langs->trans("Draft"), '1'=>$langs->trans("Unpaid"), '2'=>$langs->trans("Paid"));
	print $form->selectarray('search_status', $liststatus, $search_status, 1, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage', 1);
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}

print "</tr>\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.ref']['checked'])) {
	print_liste_field_titre($arrayfields['f.ref']['label'], $_SERVER['PHP_SELF'], 'f.ref,f.rowid', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.ref_supplier']['checked'])) {
	print_liste_field_titre($arrayfields['f.ref_supplier']['label'], $_SERVER["PHP_SELF"], 'f.ref_supplier', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.type']['checked'])) {
	print_liste_field_titre($arrayfields['f.type']['label'], $_SERVER["PHP_SELF"], 'f.type', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.label']['checked'])) {
	print_liste_field_titre($arrayfields['f.label']['label'], $_SERVER['PHP_SELF'], "f.libelle,f.rowid", '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.datef']['checked'])) {
	print_liste_field_titre($arrayfields['f.datef']['label'], $_SERVER['PHP_SELF'], 'f.datef,f.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'], $_SERVER['PHP_SELF'], "f.date_lim_reglement", '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER['PHP_SELF'], "p.ref", '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER['PHP_SELF'], 's.name_alias', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.town']['checked'])) {
	print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.zip']['checked'])) {
	print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['state.nom']['checked'])) {
	print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.name_alias", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['state.name_alias']['checked'])) {
	print_liste_field_titre($arrayfields['state.name_alias']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['typent.code']['checked'])) {
	print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_cond_reglement']['label'], $_SERVER["PHP_SELF"], "f.fk_cond_reglement", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "f.fk_mode_reglement", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_ht']['label'], $_SERVER['PHP_SELF'], 'f.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_vat']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_vat']['label'], $_SERVER['PHP_SELF'], 'f.total_tva', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_localtax1']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_localtax1']['label'], $_SERVER['PHP_SELF'], 'f.localtax1', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_localtax2']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_localtax2']['label'], $_SERVER['PHP_SELF'], 'f.localtax2', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_ttc']['label'], $_SERVER['PHP_SELF'], 'f.total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['u.login']['checked'])) {
	print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, 'align="center"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['dynamount_payed']['checked'])) {
	print_liste_field_titre($arrayfields['dynamount_payed']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['rtp']['checked'])) {
	print_liste_field_titre($arrayfields['rtp']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.multicurrency_code']['checked'])) {
	print_liste_field_titre($arrayfields['f.multicurrency_code']['label'], $_SERVER['PHP_SELF'], 'f.multicurrency_code', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.multicurrency_tx']['checked'])) {
	print_liste_field_titre($arrayfields['f.multicurrency_tx']['label'], $_SERVER['PHP_SELF'], 'f.multicurrency_tx', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.multicurrency_total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['f.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'f.multicurrency_total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.multicurrency_total_vat']['checked'])) {
	print_liste_field_titre($arrayfields['f.multicurrency_total_vat']['label'], $_SERVER['PHP_SELF'], 'f.multicurrency_total_tva', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.multicurrency_total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['f.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'f.multicurrency_total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['multicurrency_dynamount_payed']['checked'])) {
	print_liste_field_titre($arrayfields['multicurrency_dynamount_payed']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['multicurrency_rtp']['checked'])) {
	print_liste_field_titre($arrayfields['multicurrency_rtp']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder, 'totalarray'=>&$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['f.datec']['checked'])) {
	print_liste_field_titre($arrayfields['f.datec']['label'], $_SERVER["PHP_SELF"], "f.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.tms']['checked'])) {
	print_liste_field_titre($arrayfields['f.tms']['label'], $_SERVER["PHP_SELF"], "f.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_statut']['label'], $_SERVER["PHP_SELF"], "fk_statut,paye,type", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
print "</tr>\n";

$facturestatic = new FactureFournisseur($db);
$supplierstatic = new Fournisseur($db);
$projectstatic = new Project($db);
$userstatic = new User($db);

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$totalarray['val'] = array();
$totalarray['val']['f.total_ht']=0;
$totalarray['val']['f.total_vat']=0;
$totalarray['val']['f.total_localtax1']=0;
$totalarray['val']['f.total_localtax1']=0;
$totalarray['val']['f.total_ttc']=0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$datelimit = $db->jdate($obj->datelimite);

	$userstatic->id = $obj->fk_user_author;
	$userstatic->login = $obj->login;
	$userstatic->lastname = $obj->lastname;
	$userstatic->firstname = $obj->firstname;
	$userstatic->email = $obj->user_email;
	$userstatic->statut = $obj->user_statut;
	$userstatic->entity = $obj->entity;
	$userstatic->photo = $obj->photo;
	$userstatic->office_phone = $obj->office_phone;
	$userstatic->office_fax = $obj->office_fax;
	$userstatic->user_mobile = $obj->user_mobile;
	$userstatic->job = $obj->job;
	$userstatic->gender = $obj->gender;

	$facturestatic->id = $obj->facid;
	$facturestatic->ref = $obj->ref;
	$facturestatic->type = $obj->type;
	$facturestatic->ref_supplier = $obj->ref_supplier;
	$facturestatic->date_echeance = $db->jdate($obj->datelimite);
	$facturestatic->statut = $obj->fk_statut;
	$facturestatic->note_public = $obj->note_public;
	$facturestatic->note_private = $obj->note_private;
	$facturestatic->multicurrency_code = $obj->multicurrency_code;
	$facturestatic->multicurrency_tx = $obj->multicurrency_tx;
	$facturestatic->multicurrency_total_ht = $obj->multicurrency_total_ht;
	$facturestatic->multicurrency_total_tva = $obj->multicurrency_total_vat;
	$facturestatic->multicurrency_total_ttc = $obj->multicurrency_total_ttc;

	$thirdparty->id = $obj->socid;
	$thirdparty->name = $obj->name;
	$thirdparty->name_alias = $obj->alias;
	$thirdparty->client = $obj->client;
	$thirdparty->fournisseur = $obj->fournisseur;
	$thirdparty->code_client = $obj->code_client;
	$thirdparty->code_compta_client = $obj->code_compta_client;
	$thirdparty->code_fournisseur = $obj->code_fournisseur;
	$thirdparty->code_compta_fournisseur = $obj->code_compta_fournisseur;
	$thirdparty->email = $obj->email;
	$thirdparty->country_code = $obj->country_code;

	$paiement = $facturestatic->getSommePaiement();
	$totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
	$totaldeposits = $facturestatic->getSumDepositsUsed();
	$totalpay = $paiement + $totalcreditnotes + $totaldeposits;
	$remaintopay = $obj->total_ttc - $totalpay;
	$multicurrency_paiement = $facturestatic->getSommePaiement(1);
	$multicurrency_totalcreditnotes = $facturestatic->getSumCreditNotesUsed(1);
	$multicurrency_totaldeposits = $facturestatic->getSumDepositsUsed(1);
	$multicurrency_totalpay = $multicurrency_paiement + $multicurrency_totalcreditnotes + $multicurrency_totaldeposits;
	$multicurrency_remaintopay = price2num($facturestatic->multicurrency_total_ttc - $multicurrency_totalpay);

	$facturestatic->alreadypaid = ($paiement ? $paiement : 0);
	$facturestatic->paye = $obj->paye;
	$facturestatic->statut = $obj->fk_statut;
	$facturestatic->type = $obj->type;
	$facturestatic->socid = $thirdparty->getNomUrl(1, 'supplier', 3);
	$facturestatic->total_ht = $obj->total_ht;
	$facturestatic->date = $db->jdate($obj->datef);

	$object = $facturestatic;

	//If invoice has been converted and the conversion has been used, we dont have remain to pay on invoice
	if ($facturestatic->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
		if ($facturestatic->isCreditNoteUsed()) {
			$remaintopay = -$facturestatic->getSumFromThisCreditNotesNotUsed();
		}
	}

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}

		$arraydata = array('alreadypaid' => $paiement, 'thirdparty' => $thirdparty->getNomUrl(1, '', 12));
		print $facturestatic->getKanbanView('', $arraydata);
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->facid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->facid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->facid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['f.ref']['checked'])) {
			print '<td class="nowraponall">';

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			// Picto + Ref
			print '<td class="nobordernopadding nowraponall">';
			print $facturestatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);

			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($obj->facid, 2, 0, 0, $facturestatic, 'invoice_supplier').dol_sanitizeFileName($obj->ref);
			$subdir = get_exdir($obj->facid, 2, 0, 0, $facturestatic, 'invoice_supplier').dol_sanitizeFileName($obj->ref);
			print $formfile->getDocumentsLink('facture_fournisseur', $subdir, $filedir);
			print '</td></tr></table>';

			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Supplier ref
		if (!empty($arrayfields['f.ref_supplier']['checked'])) {
			print '<td class="nowrap tdoverflowmax150" title="'.dol_escape_htmltag($obj->ref_supplier).'">';
			print $obj->ref_supplier;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Type
		if (!empty($arrayfields['f.type']['checked'])) {
			print '<td class="nowrap">';
			print $facturestatic->getLibType();
			print "</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Label
		if (!empty($arrayfields['f.label']['checked'])) {
			print '<td class="nowrap">';
			print dol_escape_htmltag($obj->label);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date
		if (!empty($arrayfields['f.datef']['checked'])) {
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->datef), 'day');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date limit
		if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
			print '<td class="center nowraponall">'.dol_print_date($datelimit, 'day');
			if ($facturestatic->hasDelay()) {
				print img_warning($langs->trans('Alert').' - '.$langs->trans('Late'));
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Project
		if (!empty($arrayfields['p.ref']['checked'])) {
			print '<td class="nowrap">';
			if ($obj->project_id > 0) {
				$projectstatic->id = $obj->project_id;
				$projectstatic->ref = $obj->project_ref;
				$projectstatic->title = $obj->project_label;
				print $projectstatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Third party
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax150">';
			print $thirdparty->getNomUrl(1, 'supplier', 0, 0, -1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Alias
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print '<td class="tdoverflowmax150">';
			print dol_escape_htmltag($thirdparty->name_alias);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Town
		if (!empty($arrayfields['s.town']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->town).'">';
			print dol_escape_htmltag($obj->town);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Zip
		if (!empty($arrayfields['s.zip']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->zip).'">';
			print dol_escape_htmltag($obj->zip);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// State
		if (!empty($arrayfields['state.nom']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->state_name).'">';
			print dol_escape_htmltag($obj->state_name);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Country
		if (!empty($arrayfields['country.code_iso']['checked'])) {
			print '<td class="center">';
			$tmparray = getCountry($obj->fk_pays, 'all');
			print $tmparray['label'];
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Type ent
		if (!empty($arrayfields['typent.code']['checked'])) {
			print '<td class="center">';
			if (empty($typenArray)) {
				$typenArray = $formcompany->typent_array(1);
			}
			print $typenArray[$obj->typent_code];
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Payment condition
		if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
			$s = $form->form_conditions_reglement($_SERVER['PHP_SELF'], $obj->fk_cond_reglement, 'none', 1, '', -1, -1, 1);
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($s).'">';
			print dol_escape_htmltag($s);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Payment mode
		if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
			$s = $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1, 0, '', 1);
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($s).'">';
			print dol_escape_htmltag($s);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Amount HT
		if (!empty($arrayfields['f.total_ht']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->total_ht)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'f.total_ht';
			}
			$totalarray['val']['f.total_ht'] += $obj->total_ht;
		}
		// Amount VAT
		if (!empty($arrayfields['f.total_vat']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->total_vat)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'f.total_vat';
			}
			$totalarray['val']['f.total_vat'] += $obj->total_vat;
		}
		// Amount LocalTax1
		if (!empty($arrayfields['f.total_localtax1']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->total_localtax1)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'f.total_localtax1';
			}
			$totalarray['val']['f.total_localtax1'] += $obj->total_localtax1;
		}
		// Amount LocalTax2
		if (!empty($arrayfields['f.total_localtax2']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->total_localtax2)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'f.total_localtax2';
			}
			$totalarray['val']['f.total_localtax2'] += $obj->total_localtax2;
		}
		// Amount TTC
		if (!empty($arrayfields['f.total_ttc']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->total_ttc)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'f.total_ttc';
			}
			$totalarray['val']['f.total_ttc'] += $obj->total_ttc;
		}

		// Author
		if (!empty($arrayfields['u.login']['checked'])) {
			print '<td class="tdoverflowmax150">';
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1);
			} else {
				print '&nbsp;';
			}
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['dynamount_payed']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.(!empty($totalpay) ?price($totalpay, 0, $langs) : '').'</span></td>'; // TODO Use a denormalized field
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'totalam';
			}
			$totalarray['val']['totalam'] += $totalpay;
		}

		if (!empty($arrayfields['rtp']['checked'])) {
			print '<td class="right nowrap">'.(!empty($remaintopay) ?price($remaintopay, 0, $langs) : '&nbsp;').'</td>'; // TODO Use a denormalized field
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'rtp';
			}
			$totalarray['val']['rtp'] += $remaintopay;
		}

		// Currency
		if (!empty($arrayfields['f.multicurrency_code']['checked'])) {
			print '<td class="nowrap">'.$obj->multicurrency_code.' - '.$langs->trans('Currency'.$obj->multicurrency_code)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Currency rate
		if (!empty($arrayfields['f.multicurrency_tx']['checked'])) {
			print '<td class="nowrap">';
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount HT
		if (!empty($arrayfields['f.multicurrency_total_ht']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ht)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount VAT
		if (!empty($arrayfields['f.multicurrency_total_vat']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_vat)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount TTC
		if (!empty($arrayfields['f.multicurrency_total_ttc']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ttc)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['multicurrency_dynamount_payed']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.(!empty($multicurrency_totalpay) ?price($multicurrency_totalpay, 0, $langs) : '').'</span></td>'; // TODO Use a denormalized field
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Pending amount
		if (!empty($arrayfields['multicurrency_rtp']['checked'])) {
			print '<td class="right nowrap"><span class="amount">';
			print (!empty($multicurrency_remaintopay) ? price($multicurrency_remaintopay, 0, $langs) : '');
			print '</span></td>'; // TODO Use a denormalized field
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}


		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'object'=>$object, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Date creation
		if (!empty($arrayfields['f.datec']['checked'])) {
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date modification
		if (!empty($arrayfields['f.tms']['checked'])) {
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['f.fk_statut']['checked'])) {
			print '<td class="right nowrap">';
			print $facturestatic->getLibStatut(5, $paiement);
			print "</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->facid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->facid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->facid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>'."\n";
	}

	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

$hidegeneratedfilelistifempty = 1;
if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
	$hidegeneratedfilelistifempty = 0;
}

// Show list of available documents
$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
$urlsource .= str_replace('&amp;', '&', $param);

$filedir = $diroutputmassaction;
$genallowed = $user->hasRight('facture', 'lire');
$delallowed = $user->hasRight('facture', 'creer');
$title = '';

print $formfile->showdocuments('massfilesarea_supplier_invoice', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);

// End of page
llxFooter();
$db->close();

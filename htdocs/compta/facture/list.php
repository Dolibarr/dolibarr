<?php
/* Copyright (C) 2002-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2020	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2015-2022	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2017		Josep Lluís Amador		<joseplluis@lliuretic.cat>
 * Copyright (C) 2018		Charlene Benke			<charlie@patas-monkey.com>
 * Copyright (C) 2019-2024	Alexandre Spangaro		<alexandre@inovea-conseil.com>
 * Copyright (C) 2021-2024	Anthony Berton			<anthony.berton@bb2a.fr>
 * Copyright (C) 2023		Nick Fragoulis
 * Copyright (C) 2023		Joachim Kueter			<git-jk@bloxera.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		Solution Libre SAS		<contact@solution-libre.fr>
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
 *	\file       htdocs/compta/facture/list.php
 *	\ingroup    invoice
 *	\brief      List of customer invoices
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (isModEnabled('margin')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
if (isModEnabled('order')) {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));

$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'invoicelist';
$mode = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

if ($contextpage == 'poslist') {
	$optioncss = 'print';
}

$userid = GETPOSTINT('userid');
$search_ref = GETPOST('sf_ref') ? GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_refcustomer = GETPOST('search_refcustomer', 'alpha');
$search_type = GETPOST('search_type', 'intcomma');
$search_subtype = GETPOST('search_subtype', 'intcomma');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_project = GETPOST('search_project', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_company_alias = GETPOST('search_company_alias', 'alpha');
$search_parent_name = trim(GETPOST('search_parent_name', 'alphanohtml'));
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
$search_status = GETPOST('search_status', 'intcomma');
$search_paymentmode = GETPOST('search_paymentmode', 'intcomma');
$search_paymentterms = GETPOST('search_paymentterms', 'intcomma');
$search_module_source = GETPOST('search_module_source', 'alpha');
$search_pos_source = GETPOST('search_pos_source', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state");
$search_country = GETPOST("search_country", 'aZ09');
$search_customer_code = GETPOST("search_customer_code", 'alphanohtml');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'intcomma');
$search_user = GETPOST('search_user', 'intcomma');
$search_sale = GETPOST('search_sale', 'intcomma');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear); // Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_date_valid_startday = GETPOSTINT('search_date_valid_startday');
$search_date_valid_startmonth = GETPOSTINT('search_date_valid_startmonth');
$search_date_valid_startyear = GETPOSTINT('search_date_valid_startyear');
$search_date_valid_endday = GETPOSTINT('search_date_valid_endday');
$search_date_valid_endmonth = GETPOSTINT('search_date_valid_endmonth');
$search_date_valid_endyear = GETPOSTINT('search_date_valid_endyear');
$search_date_valid_start = dol_mktime(0, 0, 0, $search_date_valid_startmonth, $search_date_valid_startday, $search_date_valid_startyear); // Use tzserver
$search_date_valid_end = dol_mktime(23, 59, 59, $search_date_valid_endmonth, $search_date_valid_endday, $search_date_valid_endyear);
$search_datelimit_startday = GETPOSTINT('search_datelimit_startday');
$search_datelimit_startmonth = GETPOSTINT('search_datelimit_startmonth');
$search_datelimit_startyear = GETPOSTINT('search_datelimit_startyear');
$search_datelimit_endday = GETPOSTINT('search_datelimit_endday');
$search_datelimit_endmonth = GETPOSTINT('search_datelimit_endmonth');
$search_datelimit_endyear = GETPOSTINT('search_datelimit_endyear');
$search_datelimit_start = dol_mktime(0, 0, 0, $search_datelimit_startmonth, $search_datelimit_startday, $search_datelimit_startyear);
$search_datelimit_end = dol_mktime(23, 59, 59, $search_datelimit_endmonth, $search_datelimit_endday, $search_datelimit_endyear);
$search_categ_cus = GETPOST("search_categ_cus", 'intcomma');
$search_product_category = GETPOST('search_product_category', 'intcomma');
$search_fac_rec_source_title = GETPOST("search_fac_rec_source_title", 'alpha');

$search_option = GETPOST('search_option');
if ($search_option == 'late') {
	$search_status = '1';
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
if (!$sortorder && getDolGlobalString('INVOICE_DEFAULT_UNPAYED_SORT_ORDER') && $search_status == '1') {
	$sortorder = getDolGlobalString('INVOICE_DEFAULT_UNPAYED_SORT_ORDER');
}
if (!$sortorder) {
	$sortorder = 'DESC';
}
if (!$sortfield) {
	$sortfield = 'f.datef';
}
$pageprev = $page - 1;
$pagenext = $page + 1;

$diroutputmassaction = $conf->facture->dir_output.'/temp/massgeneration/'.$user->id;

$now = dol_now();
$error = 0;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Facture($db);
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.ref' => 'Ref',
	'f.ref_client' => 'RefCustomer',
	'f.note_public' => 'NotePublic',
	's.nom' => "ThirdParty",
	's.code_client' => "CustomerCodeShort",
	's.name_alias' => "AliasNameShort",
	's.zip' => "Zip",
	's.town' => "Town",
	'pd.description' => 'Description',
);
if (empty($user->socid)) {
	$fieldstosearchall["f.note_private"] = "NotePrivate";
}

$checkedtypetiers = 0;
$arrayfields = array(
	'f.ref' => array('label' => "Ref", 'checked' => 1, 'position' => 5),
	'f.ref_client' => array('label' => "RefCustomer", 'checked' => -1, 'position' => 10),
	'f.type' => array('label' => "Type", 'checked' => 0, 'position' => 15),
	'f.subtype' => array('label' => "InvoiceSubtype", 'checked' => 0, 'position' => 17),
	'f.datef' => array('label' => "DateInvoice", 'checked' => 1, 'position' => 20),
	'f.date_valid' => array('label' => "DateValidation", 'checked' => 0, 'position' => 22),
	'f.date_lim_reglement' => array('label' => "DateDue", 'checked' => 1, 'position' => 25),
	'f.date_closing' => array('label' => "DateClosing", 'checked' => 0, 'position' => 30),
	'p.ref' => array('label' => "ProjectRef", 'langs' => 'projects', 'checked' => 1, 'enabled' => (!isModEnabled('project') ? 0 : 1), 'position' => 40),
	'p.title' => array('label' => "ProjectLabel", 'checked' => 0, 'enabled' => (!isModEnabled('project') ? 0 : 1), 'position' => 41),
	's.nom' => array('label' => "ThirdParty", 'checked' => 1, 'position' => 50),
	's.name_alias' => array('label' => "AliasNameShort", 'checked' => -1, 'position' => 51),
	's.code_client' => array('label' => "CustomerCodeShort", 'checked' => -1, 'position' => 52),
	's2.nom' => array('label' => 'ParentCompany', 'position' => 32, 'checked' => 0),
	's.town' => array('label' => "Town", 'checked' => -1, 'position' => 55),
	's.zip' => array('label' => "Zip", 'checked' => -1, 'position' => 60),
	'state.nom' => array('label' => "StateShort", 'checked' => 0, 'position' => 65),
	'country.code_iso' => array('label' => "Country", 'checked' => 0, 'position' => 70),
	'typent.code' => array('label' => "ThirdPartyType", 'checked' => $checkedtypetiers, 'position' => 75),
	'f.fk_mode_reglement' => array('label' => "PaymentMode", 'checked' => 1, 'position' => 80),
	'f.fk_cond_reglement' => array('label' => "PaymentConditionsShort", 'checked' => 1, 'position' => 85),
	'f.module_source' => array('label' => "POSModule", 'langs' => 'cashdesk', 'checked' => ($contextpage == 'poslist' ? 1 : 0), 'enabled' => "(isModEnabled('cashdesk') || isModEnabled('takepos') || getDolGlobalInt('INVOICE_SHOW_POS'))", 'position' => 90),
	'f.pos_source' => array('label' => "POSTerminal", 'langs' => 'cashdesk', 'checked' => ($contextpage == 'poslist' ? 1 : 0), 'enabled' => "(isModEnabled('cashdesk') || isModEnabled('takepos') || getDolGlobalInt('INVOICE_SHOW_POS'))", 'position' => 91),
	'f.total_ht' => array('label' => "AmountHT", 'checked' => 1, 'position' => 95),
	'f.total_tva' => array('label' => "AmountVAT", 'checked' => 0, 'position' => 100),
	'f.total_localtax1' => array('label' => $langs->transcountry("AmountLT1", $mysoc->country_code), 'checked' => 0, 'enabled' => ($mysoc->localtax1_assuj == "1"), 'position' => 110),
	'f.total_localtax2' => array('label' => $langs->transcountry("AmountLT2", $mysoc->country_code), 'checked' => 0, 'enabled' => ($mysoc->localtax2_assuj == "1"), 'position' => 120),
	'f.total_ttc' => array('label' => "AmountTTC", 'checked' => 0, 'position' => 130),
	'dynamount_payed' => array('label' => "Received", 'checked' => 0, 'position' => 140),
	'rtp' => array('label' => "Rest", 'checked' => 0, 'position' => 150), // Not enabled by default because slow
	'f.multicurrency_code' => array('label' => 'Currency', 'checked' => 0, 'enabled' => (!isModEnabled('multicurrency') ? 0 : 1), 'position' => 280),
	'f.multicurrency_tx' => array('label' => 'CurrencyRate', 'checked' => 0, 'enabled' => (!isModEnabled('multicurrency') ? 0 : 1), 'position' => 285),
	'f.multicurrency_total_ht' => array('label' => 'MulticurrencyAmountHT', 'checked' => 0, 'enabled' => (!isModEnabled('multicurrency') ? 0 : 1), 'position' => 290),
	'f.multicurrency_total_vat' => array('label' => 'MulticurrencyAmountVAT', 'checked' => 0, 'enabled' => (!isModEnabled('multicurrency') ? 0 : 1), 'position' => 291),
	'f.multicurrency_total_ttc' => array('label' => 'MulticurrencyAmountTTC', 'checked' => 0, 'enabled' => (!isModEnabled('multicurrency') ? 0 : 1), 'position' => 292),
	'multicurrency_dynamount_payed' => array('label' => 'MulticurrencyAlreadyPaid', 'checked' => 0, 'enabled' => (!isModEnabled('multicurrency') ? 0 : 1), 'position' => 295),
	'multicurrency_rtp' => array('label' => 'MulticurrencyRemainderToPay', 'checked' => 0, 'enabled' => (!isModEnabled('multicurrency') ? 0 : 1), 'position' => 296), // Not enabled by default because slow
	'total_pa' => array('label' => ((getDolGlobalString('MARGIN_TYPE') == '1') ? 'BuyingPrice' : 'CostPrice'), 'checked' => 0, 'position' => 300, 'enabled' => (!isModEnabled('margin') || !$user->hasRight('margins', 'liretous') ? 0 : 1)),
	'total_margin' => array('label' => 'Margin', 'checked' => 0, 'position' => 301, 'enabled' => (!isModEnabled('margin') || !$user->hasRight('margins', 'liretous') ? 0 : 1)),
	'total_margin_rate' => array('label' => 'MarginRate', 'checked' => 0, 'position' => 302, 'enabled' => (!isModEnabled('margin') || !$user->hasRight('margins', 'liretous') || !getDolGlobalString('DISPLAY_MARGIN_RATES') ? 0 : 1)),
	'total_mark_rate' => array('label' => 'MarkRate', 'checked' => 0, 'position' => 303, 'enabled' => (!isModEnabled('margin') || !$user->hasRight('margins', 'liretous') || !getDolGlobalString('DISPLAY_MARK_RATES') ? 0 : 1)),
	'f.datec' => array('label' => "DateCreation", 'checked' => 0, 'position' => 500),
	'f.tms' => array('type' => 'timestamp', 'label' => 'DateModificationShort', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 502),
	'u.login' => array('label' => "UserAuthor", 'checked' => 1, 'visible' => -1, 'position' => 504),
	'sale_representative' => array('label' => "SaleRepresentativesOfThirdParty", 'checked' => 0, 'position' => 506),
	//'f.fk_user_author' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-1, 'position'=>506),
	//'f.fk_user_modif' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-1, 'notnull'=>-1, 'position'=>508),
	//'f.fk_user_valid' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserValidation', 'enabled'=>1, 'visible'=>-1, 'position'=>510),
	//'f.fk_user_closing' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserClosing', 'enabled'=>1, 'visible'=>-1, 'position'=>512),
	'f.note_public' => array('label' => 'NotePublic', 'checked' => 0, 'position' => 520, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PUBLIC_NOTES'))),
	'f.note_private' => array('label' => 'NotePrivate', 'checked' => 0, 'position' => 521, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PRIVATE_NOTES'))),
	'f.fk_fac_rec_source' => array('label' => 'GeneratedFromTemplate', 'checked' => 0, 'position' => 530, 'enabled' => '1'),
	'f.fk_statut' => array('label' => "Status", 'checked' => 1, 'position' => 1000),
);

if (getDolGlobalString("INVOICE_USE_SITUATION") && getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
	$arrayfields['f.retained_warranty'] = array('label' => $langs->trans("RetainedWarranty"), 'checked' => 0, 'position' => 86);
}

$subtypearray = $object->getArrayOfInvoiceSubtypes(0);
if (empty($subtypearray)) {
	unset($arrayfields['f.subtype']);
}

// Overwrite $arrayfields from columns into ->fields (transition before removal of $arrayoffields)
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field

	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1, 1, '1');
		$newkey = '';
		if (array_key_exists($key, $arrayfields)) {
			$newkey = $key;
		} elseif (array_key_exists('f.'.$key, $arrayfields)) {
			$newkey = 'f.'.$key;
		} elseif (array_key_exists('s.'.$key, $arrayfields)) {
			$newkey = 's.'.$key;
		}
		if ($newkey) {
			$arrayfields[$newkey] = array(
				'label' => $val['label'],
				'checked' => (($visible < 0) ? 0 : 1),
				'enabled' => (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
				'position' => $val['position'],
				'help' => empty($val['help']) ? '' : $val['help'],
			);
		}
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';


$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}

// Security check
$fieldid = (!empty($ref) ? 'ref' : 'rowid');
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'facture', $id, '', '', 'fk_soc', $fieldid);


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

$parameters = array('socid' => $socid, 'arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_user = '';
	$search_sale = '';
	$search_product_category = '';
	$search_ref = '';
	$search_refcustomer = '';
	$search_type = '';
	$search_subtype = '';
	$search_project_ref = '';
	$search_project = '';
	$search_company = '';
	$search_company_alias = '';
	$search_parent_name = '';
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
	$search_paymentterms = '';
	$search_module_source = '';
	$search_pos_source = '';
	$search_town = '';
	$search_zip = "";
	$search_state = "";
	$search_country = '';
	$search_type_thirdparty = '';
	$search_customer_code = '';
	$search_date_startday = '';
	$search_date_startmonth = '';
	$search_date_startyear = '';
	$search_date_endday = '';
	$search_date_endmonth = '';
	$search_date_endyear = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_date_valid_startday = '';
	$search_date_valid_startmonth = '';
	$search_date_valid_startyear = '';
	$search_date_valid_endday = '';
	$search_date_valid_endmonth = '';
	$search_date_valid_endyear = '';
	$search_date_valid_start = '';
	$search_date_valid_end = '';
	$search_datelimit_startday = '';
	$search_datelimit_startmonth = '';
	$search_datelimit_startyear = '';
	$search_datelimit_endday = '';
	$search_datelimit_endmonth = '';
	$search_datelimit_endyear = '';
	$search_datelimit_start = '';
	$search_datelimit_end = '';
	$search_fac_rec_source_title = '';
	$toselect = array();
	$search_array_options = array();
	$search_categ_cus = 0;
	$search_option = '';
}

if (empty($reshook)) {
	$objectclass = 'Facture';
	$objectlabel = 'Invoices';
	$permissiontoread = $user->hasRight("facture", "lire");
	$permissiontoadd = $user->hasRight("facture", "creer");
	$permissiontodelete = $user->hasRight("facture", "supprimer");
	$uploaddir = $conf->facture->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if ($action == 'makepayment_confirm' && $user->hasRight('facture', 'paiement')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
	$arrayofselected = is_array($toselect) ? $toselect : array();
	if (!empty($arrayofselected)) {
		$bankid = GETPOSTINT('bankid');
		$paiementid = GETPOSTINT('paiementid');
		$paiementdate = dol_mktime(12, 0, 0, GETPOSTINT('datepaimentmonth'), GETPOSTINT('datepaimentday'), GETPOSTINT('datepaimentyear'));
		if (empty($paiementdate)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			$error++;
			$action = 'makepayment';
		}

		if (!$error) {
			foreach ($arrayofselected as $toselectid) {
				$errorpayment = 0;
				$facture = new Facture($db);
				$result = $facture->fetch($toselectid);

				$db->begin();

				if ($result < 0) {
					setEventMessage($facture->error, 'errors');
					$errorpayment++;
				} else {
					if ($facture->type != Facture::TYPE_CREDIT_NOTE && $facture->status == Facture::STATUS_VALIDATED && $facture->paye == 0) {
						$paiementAmount = $facture->getSommePaiement();
						$totalcreditnotes = $facture->getSumCreditNotesUsed();
						$totaldeposits = $facture->getSumDepositsUsed();
						$totalpay = $paiementAmount + $totalcreditnotes + $totaldeposits;
						$remaintopay = price2num($facture->total_ttc - $totalpay);

						// hook to finalize the remaining amount, considering e.g. cash discount agreements
						$parameters = array('remaintopay' => $remaintopay);
						$reshook = $hookmanager->executeHooks('finalizeAmountOfInvoice', $parameters, $facture, $action); // Note that $action and $object may have been modified by some hooks
						if ($reshook > 0) {
							if (!empty($remain = $hookmanager->resArray['remaintopay'])) {
								$remaintopay = $remain;
							}
						} elseif ($reshook < 0) {
							$error++;
							setEventMessages($facture->ref.' '.$langs->trans("ProcessingError"), $hookmanager->errors, 'errors');
						}

						if ($remaintopay != 0) {
							$resultBank = $facture->setBankAccount($bankid);
							if ($resultBank < 0) {
								setEventMessages($facture->error, null, 'errors');
								$errorpayment++;
							} else {
								$paiement = new Paiement($db);
								$paiement->datepaye = $paiementdate;
								$paiement->amounts[$facture->id] = $remaintopay; // Array with all payments dispatching with invoice id
								$paiement->multicurrency_amounts[$facture->id] = $remaintopay;
								$paiement->paiementid = $paiementid;
								$paiement_id = $paiement->create($user, 1, $facture->thirdparty);
								if ($paiement_id < 0) {
									$langs->load("errors");
									setEventMessages($facture->ref.' '.$langs->trans($paiement->error), $paiement->errors, 'errors');
									$errorpayment++;
								} else {
									$result = $paiement->addPaymentToBank($user, 'payment', '', $bankid, '', '');
									if ($result < 0) {
										$langs->load("errors");
										setEventMessages($facture->ref.' '.$langs->trans($paiement->error), $paiement->errors, 'errors');
										$errorpayment++;
									}
								}
							}
						} else {
							setEventMessage($langs->trans('NoPaymentAvailable', $facture->ref), 'warnings');
							$errorpayment++;
						}
					} else {
						setEventMessage($langs->trans('BulkPaymentNotPossibleForInvoice', $facture->ref), 'warnings');
						$errorpayment++;
					}
				}

				if (empty($errorpayment)) {
					setEventMessage($langs->trans('PaymentRegisteredAndInvoiceSetToPaid', $facture->ref));
					$db->commit();
				} else {
					$db->rollback();
				}
			}
		}
	}
} elseif ($massaction == 'withdrawrequest') {
	$langs->load("withdrawals");

	if (!$user->hasRight('prelevement', 'bons', 'creer')) {
		$error++;
		setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
	} else {
		//Checking error
		$error = 0;

		$arrayofselected = is_array($toselect) ? $toselect : array();
		$listofbills = array();
		foreach ($arrayofselected as $toselectid) {
			$objecttmp = new Facture($db);
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$totalpaid = $objecttmp->getSommePaiement();
				$totalcreditnotes = $objecttmp->getSumCreditNotesUsed();
				$totaldeposits = $objecttmp->getSumDepositsUsed();
				$objecttmp->resteapayer = price2num($objecttmp->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');

				// hook to finalize the remaining amount, considering e.g. cash discount agreements
				$parameters = array('remaintopay' => $objecttmp->resteapayer);
				$reshook = $hookmanager->executeHooks('finalizeAmountOfInvoice', $parameters, $objecttmp, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook > 0) {
					if (!empty($remaintopay = $hookmanager->resArray['remaintopay'])) {
						$objecttmp->resteapayer = $remaintopay;
					}
				} elseif ($reshook < 0) {
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("ProcessingError"), $hookmanager->errors, 'errors');
				}

				if ($objecttmp->statut == Facture::STATUS_DRAFT) {
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
				$rsql .= " WHERE fk_facture = ".((int) $objecttmp->id);
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
				} elseif (!empty($objecttmp->mode_reglement_code) && $objecttmp->mode_reglement_code != 'PRE') {
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("BadPaymentMethod"), $objecttmp->errors, 'errors');
				} else {
					$listofbills[] = $objecttmp; // $listofbills will only contains invoices with good payment method and no request already done
				}
			}
		}

		//Massive withdraw request for request with no errors
		if (!empty($listofbills)) {
			$nbwithdrawrequestok = 0;
			foreach ($listofbills as $aBill) {
				$db->begin();
				$result = $aBill->demande_prelevement($user, $aBill->resteapayer, 'direct-debit', 'facture');
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



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formmargin = null;
if (isModEnabled('margin')) {
	$formmargin = new FormMargin($db);
}
$bankaccountstatic = new Account($db);
$facturestatic = new Facture($db);
$formcompany = new FormCompany($db);
$companystatic = new Societe($db);
$companyparent = new Societe($db);

$company_url_list = array();

if ($socid > 0) {
	$soc = new Societe($db);
	$soc->fetch($socid);
	if (empty($search_company)) {
		$search_company = $soc->name;
	}
}

$title = $langs->trans('BillsCustomers').' '.($socid > 0 ? ' - '.$soc->name : '');
$help_url = 'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT';
if ($search_all) {
	$sql = 'SELECT DISTINCT';
}
$sql .= ' f.rowid as id, f.ref, f.ref_client, f.fk_soc, f.type, f.subtype, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.fk_cond_reglement, f.total_ht, f.total_tva, f.total_ttc,';
$sql .= ' f.localtax1 as total_localtax1, f.localtax2 as total_localtax2,';
$sql .= ' f.fk_user_author,';
$sql .= ' f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva as multicurrency_total_vat, f.multicurrency_total_ttc,';
$sql .= ' f.datef, f.date_valid, f.date_lim_reglement as datelimite, f.module_source, f.pos_source,';
$sql .= ' f.paye as paye, f.fk_statut, f.close_code,';
$sql .= ' f.datec as date_creation, f.tms as date_modification, f.date_closing as date_closing,';
$sql .= ' f.retained_warranty, f.retained_warranty_date_limit, f.situation_final, f.situation_cycle_ref, f.situation_counter,';
$sql .= ' s.rowid as socid, s.nom as name, s.name_alias as alias, s.email, s.phone, s.fax, s.address, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,';
$sql .= " s.parent as fk_parent,";
$sql .= " s2.nom as name2,";
$sql .= ' typent.code as typent_code,';
$sql .= ' state.code_departement as state_code, state.nom as state_name,';
$sql .= ' country.code as country_code,';
$sql .= ' f.fk_fac_rec_source,';
$sql .= ' p.rowid as project_id, p.ref as project_ref, p.title as project_label,';
$sql .= ' u.login, u.lastname, u.firstname, u.email as user_email, u.statut as user_statut, u.entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender';
// We need dynamount_payed to be able to sort on status (value is surely wrong because we can count several lines several times due to other left join or link with contacts. But what we need is just 0 or > 0).
// A Better solution to be able to sort on already paid or remain to pay is to store amount_payed in a denormalized field.
// We disable this. It create a bug when searching with search_all and sorting on status. Also it create performance troubles.
/*
if (!$search_all) {
	$sql .= ', SUM(pf.amount) as dynamount_payed, SUM(pf.multicurrency_amount) as multicurrency_dynamount_payed';
}
*/
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
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s2 ON s2.rowid = s.parent";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= ', '.MAIN_DB_PREFIX.'facture as f';
if ($sortfield == "f.datef") {
	$sql .= $db->hintindex('idx_facture_datef');
}
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (f.rowid = ef.fk_object)";
}
if ($search_all) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid = pd.fk_facture';
}
if (!empty($search_fac_rec_source_title)) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_rec as facrec ON f.fk_fac_rec_source = facrec.rowid';
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = f.fk_projet";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user AS u ON f.fk_user_author = u.rowid';
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' WHERE f.fk_soc = s.rowid';
$sql .= ' AND f.entity IN ('.getEntity('invoice').')';
if ($socid > 0) {
	$sql .= ' AND s.rowid = '.((int) $socid);
}
if ($userid) {
	if ($userid == -1) {
		$sql .= ' AND f.fk_user_author IS NULL';
	} else {
		$sql .= ' AND f.fk_user_author = '.((int) $userid);
	}
}
if ($search_ref) {
	$sql .= natural_search('f.ref', $search_ref);
}
if ($search_refcustomer) {
	$sql .= natural_search('f.ref_client', $search_refcustomer);
}
if ($search_type != '' && $search_type != '-1') {
	$sql .= " AND f.type IN (".$db->sanitize($db->escape($search_type)).")";
}
if ($search_subtype != '' && $search_subtype != '-1') {
	$sql .= " AND f.subtype IN (".$db->sanitize($db->escape($search_subtype)).")";
}
if ($search_project_ref) {
	$sql .= natural_search('p.ref', $search_project_ref);
}
if ($search_project) {
	$sql .= natural_search('p.title', $search_project);
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
if ($search_parent_name) {
	$sql .= natural_search('s2.nom', $search_parent_name);
}
if ($search_customer_code) {
	$sql .= natural_search('s.code_client', $search_customer_code);
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
		$sql .= " AND country.code <> '".$db->escape($mysoc->country_code)."'";
	} elseif ($search_country == 'special_eec') {
		$sql .= " AND country.code IN (".$db->sanitize($country_code_in_EEC, 1).")";
	} elseif ($search_country == 'special_eecnotme') {
		$sql .= " AND country.code IN (".$db->sanitize($country_code_in_EEC_without_me, 1).")";
	} elseif ($search_country == 'special_noteec') {
		$sql .= " AND country.code NOT IN (".$db->sanitize($country_code_in_EEC, 1).")";
	} else {
		$sql .= natural_search("country.code", $search_country);
	}
}
if ($search_type_thirdparty != '' && $search_type_thirdparty != '-1') {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($db->escape($search_type_thirdparty)).')';
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
	$sql .= natural_search(array('u.login', 'u.firstname', 'u.lastname'), $search_login);
}
if ($search_status != '-1' && $search_status != '') {
	if (is_numeric($search_status) && $search_status >= 0) {
		if ($search_status == '0') {
			$sql .= " AND f.fk_statut = 0"; // draft
		}
		if ($search_status == '1') {
			$sql .= " AND f.fk_statut = 1"; // unpayed
		}
		if ($search_status == '2') {
			$sql .= " AND f.fk_statut = 2"; // paid     Not that some corrupted data may contains f.fk_statut = 1 AND f.paye = 1 (it means paid too but should not happen. If yes, reopen and reclassify billed)
		}
		if ($search_status == '3') {
			$sql .= " AND f.fk_statut = 3"; // abandoned
		}
	} else {
		$sql .= " AND f.fk_statut IN (".$db->sanitize($db->escape($search_status)).")"; // When search_status is '1,2' for example
	}
}

if ($search_paymentmode > 0) {
	$sql .= " AND f.fk_mode_reglement = ".((int) $search_paymentmode);
}
if ($search_paymentterms > 0) {
	$sql .= " AND f.fk_cond_reglement = ".((int) $search_paymentterms);
}
if ($search_module_source) {
	$sql .= natural_search("f.module_source", $search_module_source);
}
if ($search_pos_source) {
	$sql .= natural_search("f.pos_source", $search_pos_source);
}
if ($search_date_start) {
	$sql .= " AND f.datef >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND f.datef <= '".$db->idate($search_date_end)."'";
}
if ($search_date_valid_start) {
	$sql .= " AND f.date_valid >= '".$db->idate($search_date_valid_start)."'";
}
if ($search_date_valid_end) {
	$sql .= " AND f.date_valid <= '".$db->idate($search_date_valid_end)."'";
}
if ($search_datelimit_start) {
	$sql .= " AND f.date_lim_reglement >= '".$db->idate($search_datelimit_start)."'";
}
if ($search_datelimit_end) {
	$sql .= " AND f.date_lim_reglement <= '".$db->idate($search_datelimit_end)."'";
}
if ($search_option == 'late') {
	$sql .= " AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->client->warning_delay)."'";
}
/*if ($search_sale > 0) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $search_sale);
}*/
if (!empty($search_fac_rec_source_title)) {
	$sql .= natural_search('facrec.titre', $search_fac_rec_source_title);
}
// Search on user
if ($search_user > 0) {
	$sql .= " AND EXISTS (";
	$sql .= " SELECT ec.fk_c_type_contact, ec.element_id, ec.fk_socpeople";
	$sql .= " FROM llx_element_contact as ec";
	$sql .= " INNER JOIN llx_c_type_contact as tc";
	$sql .= " ON ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal'";
	$sql .= " WHERE ec.element_id = f.rowid AND ec.fk_socpeople = ".((int) $search_user).")";
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = f.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = f.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
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
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."facturedet as fd WHERE fd.fk_facture = f.rowid AND fd.fk_product = ck.fk_product)";
		} elseif (intval($searchCategoryProduct) > 0) {
			if ($searchCategoryProductOperator == 0) {
				$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."facturedet as fd WHERE fd.fk_facture = f.rowid AND fd.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."facturedet as fd WHERE fd.fk_facture = f.rowid AND fd.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
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
$searchCategoryCustomerList = $search_categ_cus ? array($search_categ_cus) : array();
$searchCategoryCustomerOperator = 0;
// Search for tag/category ($searchCategoryCustomerList is an array of ID)
if (!empty($searchCategoryCustomerList)) {
	$searchCategoryCustomerSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$searchCategoryCustomerSqlList[] = "NOT EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc)";
		} elseif (intval($searchCategoryCustomer) > 0) {
			if ($searchCategoryCustomerOperator == 0) {
				$searchCategoryCustomerSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie = ".((int) $searchCategoryCustomer).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryCustomer);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryCustomerSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryCustomerOperator == 1) {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryCustomerSqlList).")";
		}
	} else {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryCustomerSqlList).")";
		}
	}
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if ($search_all) {
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

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
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

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$arrayofselected = is_array($toselect) ? $toselect : array();

if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all) {
	$obj = $db->fetch_object($resql);
	$id = $obj->id;

	header("Location: ".DOL_URL_ROOT.'/compta/facture/card.php?id='.$id);
	exit;
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, 0, 0, '', '', '', 'bodyforlist');

$param = '&socid='.urlencode((string) ($socid));
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_all) {
	$param .= '&search_all='.urlencode($search_all);
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
if ($search_date_valid_startday) {
	$param .= '&search_date_valid_startday='.urlencode((string) ($search_date_valid_startday));
}
if ($search_date_valid_startmonth) {
	$param .= '&search_date_valid_startmonth='.urlencode((string) ($search_date_valid_startmonth));
}
if ($search_date_valid_startyear) {
	$param .= '&search_date_valid_startyear='.urlencode((string) ($search_date_valid_startyear));
}
if ($search_date_valid_endday) {
	$param .= '&search_date_valid_endday='.urlencode((string) ($search_date_valid_endday));
}
if ($search_date_valid_endmonth) {
	$param .= '&search_date_valid_endmonth='.urlencode((string) ($search_date_valid_endmonth));
}
if ($search_date_valid_endyear) {
	$param .= '&search_date_valid_endyear='.urlencode((string) ($search_date_valid_endyear));
}
if ($search_datelimit_startday) {
	$param .= '&search_datelimit_startday='.urlencode((string) ($search_datelimit_startday));
}
if ($search_datelimit_startmonth) {
	$param .= '&search_datelimit_startmonth='.urlencode((string) ($search_datelimit_startmonth));
}
if ($search_datelimit_startyear) {
	$param .= '&search_datelimit_startyear='.urlencode((string) ($search_datelimit_startyear));
}
if ($search_datelimit_endday) {
	$param .= '&search_datelimit_endday='.urlencode((string) ($search_datelimit_endday));
}
if ($search_datelimit_endmonth) {
	$param .= '&search_datelimit_endmonth='.urlencode((string) ($search_datelimit_endmonth));
}
if ($search_datelimit_endyear) {
	$param .= '&search_datelimit_endyear='.urlencode((string) ($search_datelimit_endyear));
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_refcustomer) {
	$param .= '&search_refcustomer='.urlencode($search_refcustomer);
}
if ($search_project_ref) {
	$param .= '&search_project_ref='.urlencode($search_project_ref);
}
if ($search_project) {
	$param .= '&search_project='.urlencode($search_project);
}
if ($search_type != '') {
	$param .= '&search_type='.urlencode($search_type);
}
if ($search_subtype != '') {
	$param .= '&search_subtype='.urlencode($search_subtype);
}
if ($search_company) {
	$param .= '&search_company='.urlencode($search_company);
}
if ($search_company_alias) {
	$param .= '&search_company_alias='.urlencode($search_company_alias);
}
if ($search_parent_name != '') {
	$param .= '&search_parent_name='.urlencode($search_parent_name);
}
if ($search_town) {
	$param .= '&search_town='.urlencode($search_town);
}
if ($search_zip) {
	$param .= '&search_zip='.urlencode($search_zip);
}
if ($search_country) {
	$param .= "&search_country=".urlencode($search_country);
}
if ($search_type_thirdparty != '') {
	$param .= '&search_type_thirdparty='.urlencode($search_type_thirdparty);
}
if ($search_customer_code) {
	$param .= '&search_customer_code='.urlencode($search_customer_code);
}
if ($search_sale > 0) {
	$param .= '&search_sale='.urlencode((string) $search_sale);
}
if ($search_user > 0) {
	$param .= '&search_user='.urlencode((string) $search_user);
}
if ($search_login) {
	$param .= '&search_login='.urlencode($search_login);
}
if ($search_product_category > 0) {
	$param .= '&search_product_category='.urlencode((string) $search_product_category);
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
if ($search_status != '') {
	$param .= '&search_status='.urlencode($search_status);
}
if ($search_paymentmode > 0) {
	$param .= '&search_paymentmode='.urlencode((string) ($search_paymentmode));
}
if ($search_paymentterms > 0) {
	$param .= '&search_paymentterms='.urlencode((string) ($search_paymentterms));
}
if ($search_module_source) {
	$param .= '&search_module_source='.urlencode($search_module_source);
}
if ($search_pos_source) {
	$param .= '&search_pos_source='.urlencode($search_pos_source);
}
if ($show_files) {
	$param .= '&show_files='.urlencode((string) ($show_files));
}
if ($search_option) {
	$param .= "&search_option=".urlencode($search_option);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_categ_cus > 0) {
	$param .= '&search_categ_cus='.urlencode((string) ($search_categ_cus));
}
if (!empty($search_fac_rec_source_title)) {
	$param .= '&search_fac_rec_source_title='.urlencode($search_fac_rec_source_title);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

$arrayofmassactions = array(
	'validate' => img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	'presend' => img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);

if ($user->hasRight('facture', 'paiement')) {
	$arrayofmassactions['makepayment'] = img_picto('', 'payment', 'class="pictofixedwidth"').$langs->trans("MakePaymentAndClassifyPayed");
}
if (isModEnabled('prelevement') && $user->hasRight('prelevement', 'bons', 'creer')) {
	$langs->load("withdrawals");
	$arrayofmassactions['withdrawrequest'] = img_picto('', 'payment', 'class="pictofixedwidth"').$langs->trans("MakeWithdrawRequest");
}
if ($user->hasRight('facture', 'supprimer')) {
	if (getDolGlobalString('INVOICE_CAN_REMOVE_DRAFT_ONLY')) {
		$arrayofmassactions['predeletedraft'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Deletedraft");
	} elseif (getDolGlobalString('INVOICE_CAN_ALWAYS_BE_REMOVED')) {	// mass deletion never possible on invoices on such situation
		$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	}
}
if (in_array($massaction, array('presend', 'predelete', 'makepayment'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

// Show the new button only when this page is not opend from the Extended POS
$newcardbutton = '';
if ($contextpage != 'poslist') {
	$url = DOL_URL_ROOT.'/compta/facture/card.php?action=create';
	if (!empty($socid)) {
		$url .= '&socid='.$socid;
	}
	$newcardbutton  = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewBill'), '', 'fa fa-plus-circle', $url, '', $user->hasRight("facture", "creer"));
}

$i = 0;
print '<form method="POST" id="searchFormList" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
if (!in_array($massaction, array('makepayment'))) {
	print '<input type="hidden" name="action" value="list">';
}
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="search_status" value="'.$search_status.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="socid" value="'.$socid.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'bill', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "SendBillRef";
$modelmail = "facture_send";
$objecttmp = new Facture($db);
$trackid = 'inv'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($massaction == 'makepayment') {
	$formconfirm = '';
	$formquestion = array(
		// 'text' => $langs->trans("ConfirmClone"),
		// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
		// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
		array('type' => 'date', 'name' => 'datepaiment', 'label' => $langs->trans("Date"), 'datenow' => 1),
		array('type' => 'other', 'name' => 'paiementid', 'label' => $langs->trans("PaymentMode"), 'value' => $form->select_types_paiements(GETPOST('search_paymentmode'), 'paiementid', '', 0, 0, 1, 0, 1, '', 1)),
		array('type' => 'other', 'name' => 'bankid', 'label' => $langs->trans("BankAccount"), 'value' => $form->select_comptes('', 'bankid', 0, '', 0, '', 0, '', 1)),
		//array('type' => 'other', 'name' => 'invoicesid', 'label' => '', 'value'=>'<input type="hidden" id="invoicesid" name="invoicesid" value="'.implode('#',GETPOST('toselect','array')).'">'),
	);
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('MakePaymentAndClassifyPayed'), $langs->trans('EnterPaymentReceivedFromCustomer'), 'makepayment_confirm', $formquestion, 1, 0, 200, 500, 1);
	print $formconfirm;
}

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
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
// Filter on product tags
if (isModEnabled('category') && $user->hasRight("categorie", "lire") && ($user->hasRight("produit", "lire") || $user->hasRight("service", "lire"))) {
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('IncludingProductWithTag');
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, 0, 'maxwidth300 widthcentpercentminusx', 1);
	$moreforfilter .= '</div>';
}
if (isModEnabled('category') && $user->hasRight("categorie", "lire")) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('CustomersProspectsCategoriesShort');
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$formother->select_categories('customer', $search_categ_cus, 'search_categ_cus', 1, $tmptitle);
	$moreforfilter .= '</div>';
}
// alert on due date
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= '<label for="search_option">'.$langs->trans('Alert').' </label><input type="checkbox" name="search_option" id="search_option" value="late"'.($search_option == 'late' ? ' checked' : '').'>';
$moreforfilter .= '</div>';

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

// Show the massaction checkboxes only when this page is not opend from the Extended POS
if ($massactionbutton && $contextpage != 'poslist') {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch actioncolumn">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Ref
if (!empty($arrayfields['f.ref']['checked'])) {
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50imp" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
// Ref customer
if (!empty($arrayfields['f.ref_client']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50imp" type="text" name="search_refcustomer" value="'.dol_escape_htmltag($search_refcustomer).'">';
	print '</td>';
}
// Type
if (!empty($arrayfields['f.type']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone">';
	$listtype = array(
		Facture::TYPE_STANDARD => $langs->trans("InvoiceStandard"),
		Facture::TYPE_DEPOSIT => $langs->trans("InvoiceDeposit"),
		Facture::TYPE_CREDIT_NOTE => $langs->trans("InvoiceAvoir"),
		Facture::TYPE_REPLACEMENT => $langs->trans("InvoiceReplacement"),
	);
	if (getDolGlobalString('INVOICE_USE_SITUATION')) {
		$listtype[Facture::TYPE_SITUATION] = $langs->trans("InvoiceSituation");
	}
	//$listtype[Facture::TYPE_PROFORMA]=$langs->trans("InvoiceProForma");     // A proformat invoice is not an invoice but must be an order.
	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print $form->selectarray('search_type', $listtype, $search_type, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
	print '</td>';
}
// Invoice Subtype
if (!empty($arrayfields['f.subtype']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone" align="center">';
	print $form->selectarray('search_subtype', $subtypearray, $search_subtype, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
	print '</td>';
}
// Date invoice
if (!empty($arrayfields['f.datef']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Date valid
if (!empty($arrayfields['f.date_valid']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_valid_start ? $search_date_valid_start : -1, 'search_date_valid_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_valid_end ? $search_date_valid_end : -1, 'search_date_valid_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Date due
if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datelimit_start ? $search_datelimit_start : -1, 'search_datelimit_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datelimit_end ? $search_datelimit_end : -1, 'search_datelimit_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Project ref
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50imp" type="text" name="search_project_ref" value="'.dol_escape_htmltag($search_project_ref).'"></td>';
}
// Project label
if (!empty($arrayfields['p.title']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50imp" type="text" name="search_project" value="'.dol_escape_htmltag($search_project).'"></td>';
}
// Thirdparty
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth75imp" type="text" name="search_company" value="'.dol_escape_htmltag($search_company).'"'.($socid > 0 ? " disabled" : "").'></td>';
}
// Alias
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth75imp" type="text" name="search_company_alias" value="'.dol_escape_htmltag($search_company_alias).'"></td>';
}
// Parent company
if (!empty($arrayfields['s2.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="search_parent_name" value="'.dol_escape_htmltag($search_parent_name).'">';
	print '</td>';
}
// Customer Code
if (!empty($arrayfields['s.code_client']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth75imp" type="text" name="search_customer_code" value="'.dol_escape_htmltag($search_customer_code).'"></td>';
}
// Town
if (!empty($arrayfields['s.town']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth75imp" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
}
// Zip
if (!empty($arrayfields['s.zip']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50imp" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
}
// State
if (!empty($arrayfields['state.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50imp" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}
// Country
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth150imp maxwidth150', 'code2', 1, 0, 1, null, 1);
	print '</td>';
}
// Company type
if (!empty($arrayfields['typent.code']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, (!getDolGlobalString('SOCIETE_SORT_ON_TYPEENT') ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), 'maxwidth100', 1);
	print '</td>';
}
// Payment mode
if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
	print '<td class="liste_titre">';
	print $form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 1, 1, 0, 1, 'minwidth100 maxwidth100', 1);
	print '</td>';
}
// Payment terms
if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
	print '<td class="liste_titre left">';
	print $form->getSelectConditionsPaiements($search_paymentterms, 'search_paymentterms', -1, 1, 1, 'minwidth100 maxwidth100');
	print '</td>';
}
// Module source
if (!empty($arrayfields['f.module_source']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_module_source" value="'.dol_escape_htmltag($search_module_source).'">';
	print '</td>';
}
// POS Terminal
if (!empty($arrayfields['f.pos_source']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_pos_source" value="'.dol_escape_htmltag($search_pos_source).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_ht']['checked'])) {
	// Amount without tax
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_tva']['checked'])) {
	// Amount vat
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_localtax1']['checked'])) {
	// Localtax1
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_montant_localtax1" value="'.dol_escape_htmltag($search_montant_localtax1).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_localtax2']['checked'])) {
	// Localtax2
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_montant_localtax2" value="'.dol_escape_htmltag($search_montant_localtax2).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_ttc']['checked'])) {
	// Amount inc tax
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
	print '</td>';
}
if (!empty($arrayfields['u.login']['checked'])) {
	// Author
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" size="4" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
	print '</td>';
}
if (!empty($arrayfields['sale_representative']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['f.retained_warranty']['checked'])) {
	print '<td class="liste_titre" align="right">';
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
if (!empty($arrayfields['total_pa']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['total_margin']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['total_margin_rate']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['total_mark_rate']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
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
// Date closing
if (!empty($arrayfields['f.date_closing']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['f.note_public']['checked'])) {
	// Note public
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['f.note_private']['checked'])) {
	// Note private
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['f.fk_fac_rec_source']['checked'])) {
	// Template Invoice
	print '<td class="liste_titre maxwidthonsmartphone right">';
	print '<input class="flat maxwidth50imp" type="text" name="search_fac_rec_source_title" id="search_fac_rec_source_title" value="'.dol_escape_htmltag($search_fac_rec_source_title).'">';
	print '</td>';
}
// Status
if (!empty($arrayfields['f.fk_statut']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">';
	$liststatus = array('0' => $langs->trans("BillShortStatusDraft"), '0,1' => $langs->trans("BillShortStatusDraft").'+'.$langs->trans("BillShortStatusNotPaid"), '1' => $langs->trans("BillShortStatusNotPaid"), '1,2' => $langs->trans("BillShortStatusNotPaid").'+'.$langs->trans("BillShortStatusPaid"), '2' => $langs->trans("BillShortStatusPaid"), '3' => $langs->trans("BillShortStatusCanceled"));
	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print $form->selectarray('search_status', $liststatus, $search_status, 1, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage', 1);
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch actioncolumn">';
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
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.ref']['checked'])) {
	print_liste_field_titre($arrayfields['f.ref']['label'], $_SERVER['PHP_SELF'], 'f.ref', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.ref_client']['checked'])) {
	print_liste_field_titre($arrayfields['f.ref_client']['label'], $_SERVER["PHP_SELF"], 'f.ref_client', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.type']['checked'])) {
	print_liste_field_titre($arrayfields['f.type']['label'], $_SERVER["PHP_SELF"], 'f.type', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.subtype']['checked'])) {
	print_liste_field_titre($arrayfields['f.subtype']['label'], $_SERVER["PHP_SELF"], 'f.subtype', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['f.datef']['checked'])) {
	print_liste_field_titre($arrayfields['f.datef']['label'], $_SERVER['PHP_SELF'], 'f.datef', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.date_valid']['checked'])) {
	print_liste_field_titre($arrayfields['f.date_valid']['label'], $_SERVER['PHP_SELF'], 'f.date_valid', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'], $_SERVER['PHP_SELF'], "f.date_lim_reglement", '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	$langs->load("projects");
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER['PHP_SELF'], "p.ref", '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.title']['checked'])) {
	print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER['PHP_SELF'], "p.title", '', $param, '', $sortfield, $sortorder);
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
if (!empty($arrayfields['s2.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s2.nom']['label'], $_SERVER['PHP_SELF'], 's2.nom', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.code_client']['checked'])) {
	print_liste_field_titre($arrayfields['s.code_client']['label'], $_SERVER['PHP_SELF'], 's.code_client', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.town']['checked'])) {
	print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.zip']['checked'])) {
	print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['state.nom']['checked'])) {
	print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
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
if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "f.fk_mode_reglement", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_cond_reglement']['label'], $_SERVER["PHP_SELF"], "f.fk_cond_reglement", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.module_source']['checked'])) {
	print_liste_field_titre($arrayfields['f.module_source']['label'], $_SERVER["PHP_SELF"], "f.module_source", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.pos_source']['checked'])) {
	print_liste_field_titre($arrayfields['f.pos_source']['label'], $_SERVER["PHP_SELF"], "f.pos_source", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_ht']['label'], $_SERVER['PHP_SELF'], 'f.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_tva']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_tva']['label'], $_SERVER['PHP_SELF'], 'f.total_tva', '', $param, '', $sortfield, $sortorder, 'right ');
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
	print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['sale_representative']['checked'])) {
	print_liste_field_titre($arrayfields['sale_representative']['label'], $_SERVER["PHP_SELF"], "", "", "$param", '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.retained_warranty']['checked'])) {
	print_liste_field_titre($arrayfields['f.retained_warranty']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
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
	print_liste_field_titre($arrayfields['f.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'f.multicurrency_total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.multicurrency_total_vat']['checked'])) {
	print_liste_field_titre($arrayfields['f.multicurrency_total_vat']['label'], $_SERVER['PHP_SELF'], 'f.multicurrency_total_tva', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.multicurrency_total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['f.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'f.multicurrency_total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['multicurrency_dynamount_payed']['checked'])) {
	print_liste_field_titre($arrayfields['multicurrency_dynamount_payed']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['multicurrency_rtp']['checked'])) {
	print_liste_field_titre($arrayfields['multicurrency_rtp']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['total_pa']['checked'])) {
	print_liste_field_titre($arrayfields['total_pa']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['total_margin']['checked'])) {
	print_liste_field_titre($arrayfields['total_margin']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['total_margin_rate']['checked'])) {
	print_liste_field_titre($arrayfields['total_margin_rate']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['total_mark_rate']['checked'])) {
	print_liste_field_titre($arrayfields['total_mark_rate']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => $totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['f.datec']['checked'])) {
	print_liste_field_titre($arrayfields['f.datec']['label'], $_SERVER["PHP_SELF"], "f.datec", "", $param, '', $sortfield, $sortorder, 'nowraponall center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.tms']['checked'])) {
	print_liste_field_titre($arrayfields['f.tms']['label'], $_SERVER["PHP_SELF"], "f.tms", "", $param, '', $sortfield, $sortorder, 'nowraponall center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.date_closing']['checked'])) {
	print_liste_field_titre($arrayfields['f.date_closing']['label'], $_SERVER["PHP_SELF"], "f.date_closing", "", $param, '', $sortfield, $sortorder, 'nowraponall center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.note_public']['checked'])) {
	print_liste_field_titre($arrayfields['f.note_public']['label'], $_SERVER["PHP_SELF"], "f.note_public", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.note_private']['checked'])) {
	print_liste_field_titre($arrayfields['f.note_private']['label'], $_SERVER["PHP_SELF"], "f.note_private", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_fac_rec_source']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_fac_rec_source']['label'], $_SERVER["PHP_SELF"], "facrec.titre", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_statut']['label'], $_SERVER["PHP_SELF"], "f.fk_statut,f.paye,f.type", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
	$totalarray['nbfield']++;
}

print "</tr>\n";

$projectstatic = new Project($db);
$discount = new DiscountAbsolute($db);
$userstatic = new User($db);

// Loop on record
// --------------------------------------------------------------------
if ($num > 0) {
	$i = 0;
	$savnbfield = $totalarray['nbfield'];
	$totalarray = array();
	$totalarray['nbfield'] = 0;
	$totalarray['val'] = array();
	$totalarray['val']['f.total_ht'] = 0;
	$totalarray['val']['f.total_tva'] = 0;
	$totalarray['val']['f.total_localtax1'] = 0;
	$totalarray['val']['f.total_localtax1'] = 0;
	$totalarray['val']['f.total_ttc'] = 0;
	$totalarray['val']['totalam'] = 0;
	$totalarray['val']['rtp'] = 0;

	$typenArray = $formcompany->typent_array(1);

	$with_margin_info = false;
	if (isModEnabled('margin') && (
		!empty($arrayfields['total_pa']['checked'])
		|| !empty($arrayfields['total_margin']['checked'])
		|| !empty($arrayfields['total_margin_rate']['checked'])
		|| !empty($arrayfields['total_mark_rate']['checked'])
	)
	) {
		$with_margin_info = true;
	}
	$total_ht = 0;
	$total_margin = 0;

	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$obj = $db->fetch_object($resql);

		$datelimit = $db->jdate($obj->datelimite);

		$facturestatic->id = $obj->id;
		$facturestatic->ref = $obj->ref;
		$facturestatic->ref_client = $obj->ref_client;		// deprecated
		$facturestatic->ref_customer = $obj->ref_client;
		$facturestatic->type = $obj->type;
		$facturestatic->subtype = $obj->subtype;
		$facturestatic->total_ht = $obj->total_ht;
		$facturestatic->total_tva = $obj->total_tva;
		$facturestatic->total_ttc = $obj->total_ttc;
		$facturestatic->multicurrency_code = $obj->multicurrency_code;
		$facturestatic->multicurrency_tx = $obj->multicurrency_tx;
		$facturestatic->multicurrency_total_ht = $obj->multicurrency_total_ht;
		$facturestatic->multicurrency_total_tva = $obj->multicurrency_total_vat;
		$facturestatic->multicurrency_total_ttc = $obj->multicurrency_total_ttc;
		$facturestatic->statut = $obj->fk_statut;	// deprecated
		$facturestatic->status = $obj->fk_statut;
		$facturestatic->close_code = $obj->close_code;
		$facturestatic->total_ttc = $obj->total_ttc;
		$facturestatic->paye = $obj->paye;
		$facturestatic->socid = $obj->fk_soc;

		$facturestatic->date = $db->jdate($obj->datef);
		$facturestatic->date_validation = $db->jdate($obj->date_valid);
		$facturestatic->date_lim_reglement = $db->jdate($obj->datelimite);

		$facturestatic->note_public = $obj->note_public;
		$facturestatic->note_private = $obj->note_private;

		if (getDolGlobalString('INVOICE_USE_SITUATION') && getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
			$facturestatic->retained_warranty = $obj->retained_warranty;
			$facturestatic->retained_warranty_date_limit = $obj->retained_warranty_date_limit;
			$facturestatic->situation_final = $obj->retained_warranty_date_limit;
			$facturestatic->situation_final = $obj->retained_warranty_date_limit;
			$facturestatic->situation_cycle_ref = $obj->situation_cycle_ref;
			$facturestatic->situation_counter = $obj->situation_counter;
		}

		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->name;
		$companystatic->name_alias = $obj->alias;
		$companystatic->client = $obj->client;
		$companystatic->fournisseur = $obj->fournisseur;
		$companystatic->code_client = $obj->code_client;
		$companystatic->code_compta_client = $obj->code_compta_client;
		$companystatic->code_fournisseur = $obj->code_fournisseur;
		$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
		$companystatic->email = $obj->email;
		$companystatic->phone = $obj->phone;
		$companystatic->fax = $obj->fax;
		$companystatic->address = $obj->address;
		$companystatic->zip = $obj->zip;
		$companystatic->town = $obj->town;
		$companystatic->country_code = $obj->country_code;

		$projectstatic->id = $obj->project_id;
		$projectstatic->ref = $obj->project_ref;
		$projectstatic->title = $obj->project_label;

		$paiement = $facturestatic->getSommePaiement();
		$totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
		$totaldeposits = $facturestatic->getSumDepositsUsed();
		$totalpay = $paiement + $totalcreditnotes + $totaldeposits;
		$remaintopay = $obj->total_ttc - $totalpay;

		$multicurrency_paiement = $facturestatic->getSommePaiement(1);
		$multicurrency_totalcreditnotes = $facturestatic->getSumCreditNotesUsed(1);
		$multicurrency_totaldeposits = $facturestatic->getSumDepositsUsed(1);

		$totalpay = $paiement + $totalcreditnotes + $totaldeposits;
		$remaintopay = price2num($facturestatic->total_ttc - $totalpay);

		$multicurrency_totalpay = $multicurrency_paiement + $multicurrency_totalcreditnotes + $multicurrency_totaldeposits;
		$multicurrency_remaintopay = price2num($facturestatic->multicurrency_total_ttc - $multicurrency_totalpay);

		if ($facturestatic->status == Facture::STATUS_CLOSED && $facturestatic->close_code == 'discount_vat') {		// If invoice closed with discount for anticipated payment
			$remaintopay = 0;
			$multicurrency_remaintopay = 0;
		}
		if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {		// If credit note closed, we take into account the amount not yet consumed
			$remaincreditnote = $discount->getAvailableDiscounts($companystatic, '', 'rc.fk_facture_source='.$facturestatic->id);
			$remaintopay = -$remaincreditnote;
			$totalpay = price2num($facturestatic->total_ttc - $remaintopay);
			$multicurrency_remaincreditnote = $discount->getAvailableDiscounts($companystatic, '', 'rc.fk_facture_source='.$facturestatic->id, 0, 0, 1);
			$multicurrency_remaintopay = -$multicurrency_remaincreditnote;
			$multicurrency_totalpay = price2num($facturestatic->multicurrency_total_ttc - $multicurrency_remaintopay);
		}

		$facturestatic->alreadypaid = $paiement;
		$facturestatic->totalpaid = $paiement;

		$marginInfo = array();
		if ($with_margin_info === true) {
			$facturestatic->fetch_lines();
			$marginInfo = $formmargin->getMarginInfosArray($facturestatic);
			$total_ht += $obj->total_ht;
			$total_margin += $marginInfo['total_margin'];
		}

		$object = $facturestatic;

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

			$arraydata = array('alreadypaid' => $paiement, 'thirdparty' => $companystatic->getNomUrl(1, '', 12), 'userauthor' => $userstatic->getNomUrl(1), 'selected' => in_array($object->id, $arrayofselected));
			print $facturestatic->getKanbanView('', $arraydata);
			if ($i == ($imaxinloop - 1)) {
				print '</div>';
				print '</td></tr>';
			}
		} else {
			// Show line of result
			$j = 0;
			print '<tr data-rowid="'.$object->id.'" class="oddeven"';
			if ($contextpage == 'poslist') {
				print ' onclick="parent.$(\'#poslines\').load(\'invoice.php?action=history&placeid='.$obj->id.'\', function() {parent.$.colorbox.close();';
				if (strpos($obj->ref, 'PROV') !== false) {
					//If is a draft invoice, load var to be able to add products
					$place = str_replace(")", "", str_replace("(PROV-POS".$_SESSION["takeposterminal"]."-", "", $obj->ref));
					print 'parent.place=\''.dol_escape_js($place).'\'';
				}
				print '});"';
			}
			print '>';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($obj->id, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// No
			if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
				print '<td>'.(($offset * $limit) + $i).'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Ref
			if (!empty($arrayfields['f.ref']['checked'])) {
				print '<td class="nowraponall">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';

				print '<td class="nobordernopadding nowraponall">';
				if ($contextpage == 'poslist') {
					print dol_escape_htmltag($obj->ref);
				} else {
					print $facturestatic->getNomUrl(1, '', 200, 0, '', 0, 1);
				}

				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->id;
				print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td>';
				print '</tr>';
				print '</table>';

				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Customer ref
			if (!empty($arrayfields['f.ref_client']['checked'])) {
				print '<td class="nowrap tdoverflowmax200">';
				print dol_escape_htmltag($obj->ref_client);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Type
			if (!empty($arrayfields['f.type']['checked'])) {
				print '<td class="nowraponall tdoverflowmax100" title="'.$facturestatic->getLibType().'">';
				print $facturestatic->getLibType(2);
				print "</td>";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Invoice Subtype
			if (!empty($arrayfields['f.subtype']['checked'])) {
				$labeltoshow = '';
				if ($facturestatic->subtype > 0) {
					$labeltoshow = $facturestatic->getSubtypeLabel('facture');
				}
				print '<td class="nowraponall tdoverflowmax300" title="'.$labeltoshow.'">';
				print $labeltoshow;
				print "</td>";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Date
			if (!empty($arrayfields['f.datef']['checked'])) {
				print '<td align="center" class="nowraponall">';
				print dol_print_date($db->jdate($obj->datef), 'day');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Date
			if (!empty($arrayfields['f.date_valid']['checked'])) {
				print '<td align="center" class="nowraponall">';
				print dol_print_date($db->jdate($obj->date_valid), 'day');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Date limit
			if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
				print '<td align="center" class="nowraponall">'.dol_print_date($datelimit, 'day');
				if ($facturestatic->hasDelay()) {
					print img_warning($langs->trans('Alert').' - '.$langs->trans('Late'));
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Project ref
			if (!empty($arrayfields['p.ref']['checked'])) {
				print '<td class="nocellnopadd nowraponall">';
				if ($obj->project_id > 0) {
					print $projectstatic->getNomUrl(1);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Project title
			if (!empty($arrayfields['p.title']['checked'])) {
				print '<td class="nowraponall">';
				if ($obj->project_id > 0) {
					print dol_escape_htmltag($projectstatic->title);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Third party
			if (!empty($arrayfields['s.nom']['checked'])) {
				print '<td class="tdoverflowmax150">';
				if ($contextpage == 'poslist') {
					print dol_escape_htmltag($companystatic->name);
				} else {
					print $companystatic->getNomUrl(1, 'customer', 0, 0, -1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Alias
			if (!empty($arrayfields['s.name_alias']['checked'])) {
				print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($companystatic->name_alias).'">';
				print dol_escape_htmltag($companystatic->name_alias);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Parent company
			if (!empty($arrayfields['s2.nom']['checked'])) {
				print '<td class="tdoverflowmax200">';
				if ($obj->fk_parent > 0) {
					if (!isset($company_url_list[$obj->fk_parent])) {
						$companyparent = new Societe($db);
						$res = $companyparent->fetch($obj->fk_parent);
						if ($res > 0) {
							$company_url_list[$obj->fk_parent] = $companyparent->getNomUrl(1);
						}
					}
					if (isset($company_url_list[$obj->fk_parent])) {
						print $company_url_list[$obj->fk_parent];
					}
				}
				print "</td>";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Customer Code
			if (!empty($arrayfields['s.code_client']['checked'])) {
				print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($companystatic->code_client).'">';
				print dol_escape_htmltag($companystatic->code_client);
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
				print '<td class="nowraponall">';
				print dol_escape_htmltag($obj->zip);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// State
			if (!empty($arrayfields['state.nom']['checked'])) {
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->state_name).'">'.dol_escape_htmltag($obj->state_name)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Country
			if (!empty($arrayfields['country.code_iso']['checked'])) {
				$tmparray = getCountry($obj->fk_pays, 'all');
				print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($tmparray['label']).'">';
				print dol_escape_htmltag($tmparray['label']);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Type ent
			if (!empty($arrayfields['typent.code']['checked'])) {
				print '<td class="center">';
				if (!is_array($typenArray) || count($typenArray) == 0) {
					$typenArray = $formcompany->typent_array(1);
				}
				if (!empty($obj->typent_code)) {
					print $typenArray[$obj->typent_code];
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Staff
			if (!empty($arrayfields['staff.code']['checked'])) {
				print '<td class="center">';
				if (!is_array($conf->cache['staff']) || count($conf->cache['staff']) == 0) {
					$conf->cache['staff'] = $formcompany->effectif_array(1);
				}
				print $conf->cache['staff'][$obj->staff_code];
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Payment mode
			if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
				$s = $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1, 0, '', 1);
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($s).'">';
				print $s;
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Payment terms
			if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
				$s = $form->form_conditions_reglement($_SERVER['PHP_SELF'], $obj->fk_cond_reglement, 'none', 0, '', -1, -1, 1);
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($s).'">';
				print $s;
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Module Source
			if (!empty($arrayfields['f.module_source']['checked'])) {
				print '<td>';
				print dol_escape_htmltag($obj->module_source);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// POS Terminal
			if (!empty($arrayfields['f.pos_source']['checked'])) {
				print '<td>';
				print dol_escape_htmltag($obj->pos_source);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Amount HT
			if (!empty($arrayfields['f.total_ht']['checked'])) {
				print '<td class="right nowraponall amount">'.price($obj->total_ht)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'f.total_ht';
				}
				$totalarray['val']['f.total_ht'] += $obj->total_ht;
			}
			// Amount VAT
			if (!empty($arrayfields['f.total_tva']['checked'])) {
				print '<td class="right nowraponall amount">'.price($obj->total_tva)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'f.total_tva';
				}
				$totalarray['val']['f.total_tva'] += $obj->total_tva;
			}
			// Amount LocalTax1
			if (!empty($arrayfields['f.total_localtax1']['checked'])) {
				print '<td class="right nowraponall amount">'.price($obj->total_localtax1)."</td>\n";
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
				print '<td class="right nowraponall amount">'.price($obj->total_localtax2)."</td>\n";
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
				print '<td class="right nowraponall amount">'.price($obj->total_ttc)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'f.total_ttc';
				}
				$totalarray['val']['f.total_ttc'] += $obj->total_ttc;
			}

			$userstatic->id = $obj->fk_user_author;
			$userstatic->login = $obj->login;
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;
			$userstatic->email = $obj->user_email;
			$userstatic->status = $obj->user_statut;
			$userstatic->entity = $obj->entity;
			$userstatic->photo = $obj->photo;
			$userstatic->office_phone = $obj->office_phone;
			$userstatic->office_fax = $obj->office_fax;
			$userstatic->user_mobile = $obj->user_mobile;
			$userstatic->job = $obj->job;
			$userstatic->gender = $obj->gender;

			// Author
			if (!empty($arrayfields['u.login']['checked'])) {
				print '<td class="tdoverflowmax200">';
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

			if (!empty($arrayfields['sale_representative']['checked'])) {
				// Sales representatives
				print '<td>';
				if ($obj->socid > 0) {
					$listsalesrepresentatives = $companystatic->getSalesRepresentatives($user);
					if ($listsalesrepresentatives < 0) {
						dol_print_error($db);
					}
					$nbofsalesrepresentative = count($listsalesrepresentatives);
					if ($nbofsalesrepresentative > 6) {
						// We print only number
						print $nbofsalesrepresentative;
					} elseif ($nbofsalesrepresentative > 0) {
						$j = 0;
						foreach ($listsalesrepresentatives as $val) {
							$userstatic->id = $val['id'];
							$userstatic->lastname = $val['lastname'];
							$userstatic->firstname = $val['firstname'];
							$userstatic->email = $val['email'];
							$userstatic->status = $val['statut'];
							$userstatic->entity = $val['entity'];
							$userstatic->photo = $val['photo'];
							$userstatic->login = $val['login'];
							$userstatic->office_phone = $val['office_phone'];
							$userstatic->office_fax = $val['office_fax'];
							$userstatic->user_mobile = $val['user_mobile'];
							$userstatic->job = $val['job'];
							$userstatic->gender = $val['gender'];
							//print '<div class="float">':
							print ($nbofsalesrepresentative < 2) ? $userstatic->getNomUrl(-1, '', 0, 0, 12) : $userstatic->getNomUrl(-2);
							$j++;
							if ($j < $nbofsalesrepresentative) {
								print ' ';
							}
							//print '</div>';
						}
					}
					//else print $langs->trans("NoSalesRepresentativeAffected");
				} else {
					print '&nbsp;';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			if (!empty($arrayfields['f.retained_warranty']['checked'])) {
				print '<td align="right">'.(!empty($obj->retained_warranty) ? price($obj->retained_warranty).'%' : '&nbsp;').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			if (!empty($arrayfields['dynamount_payed']['checked'])) {
				print '<td class="right nowraponall amount">'.(!empty($totalpay) ? price($totalpay, 0, $langs) : '&nbsp;').'</td>'; // TODO Use a denormalized field
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'totalam';
				}
				$totalarray['val']['totalam'] += $totalpay;
			}

			// Pending amount
			if (!empty($arrayfields['rtp']['checked'])) {
				print '<td class="right nowraponall amount">';
				print(!empty($remaintopay) ? price($remaintopay, 0, $langs) : '&nbsp;');
				print '</td>'; // TODO Use a denormalized field
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
				print '<td class="nowraponall tdoverflowmax125" title="'.dol_escape_htmltag($obj->multicurrency_code.' - '.$langs->transnoentitiesnoconv('Currency'.$obj->multicurrency_code)).'">';
				if (!getDolGlobalString('MAIN_SHOW_ONLY_CODE_MULTICURRENCY')) {
					print $langs->transnoentitiesnoconv('Currency'.$obj->multicurrency_code);
				} else {
					print dol_escape_htmltag($obj->multicurrency_code);
				}
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Currency rate
			if (!empty($arrayfields['f.multicurrency_tx']['checked'])) {
				print '<td class="nowraponall">';
				$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount HT
			if (!empty($arrayfields['f.multicurrency_total_ht']['checked'])) {
				print '<td class="right nowraponall amount">'.price($obj->multicurrency_total_ht)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount VAT
			if (!empty($arrayfields['f.multicurrency_total_vat']['checked'])) {
				print '<td class="right nowraponall amount">'.price($obj->multicurrency_total_vat)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount TTC
			if (!empty($arrayfields['f.multicurrency_total_ttc']['checked'])) {
				print '<td class="right nowraponall amount">'.price($obj->multicurrency_total_ttc)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Dyn amount
			if (!empty($arrayfields['multicurrency_dynamount_payed']['checked'])) {
				print '<td class="right nowraponall amount">'.(!empty($multicurrency_totalpay) ? price($multicurrency_totalpay, 0, $langs) : '&nbsp;').'</td>'; // TODO Use a denormalized field
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Pending amount
			if (!empty($arrayfields['multicurrency_rtp']['checked'])) {
				print '<td class="right nowraponall">';
				print(!empty($multicurrency_remaintopay) ? price($multicurrency_remaintopay, 0, $langs) : '&nbsp;');
				print '</td>'; // TODO Use a denormalized field ?
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Total buying or cost price
			if (!empty($arrayfields['total_pa']['checked'])) {
				print '<td class="right nowrap">'.price($marginInfo['pa_total'], 0, $langs, 1, -1, 'MT').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
					$totalarray['pos'][$totalarray['nbfield']] = 'total_pa';
				}
				if (empty($totalarray['val']['total_pa'])) {
					$totalarray['val']['total_pa'] = 0;
				}
				$totalarray['val']['total_pa'] += $marginInfo['pa_total'];
			}
			// Total margin
			if (!empty($arrayfields['total_margin']['checked'])) {
				print '<td class="right nowrap">'.price($marginInfo['total_margin'], 0, $langs, 1, -1, 'MT').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
					$totalarray['pos'][$totalarray['nbfield']] = 'total_margin';
				}
				if (empty($totalarray['val']['total_margin'])) {
					$totalarray['val']['total_margin'] = 0;
				}
				$totalarray['val']['total_margin'] += $marginInfo['total_margin'];
			}
			// Total margin rate
			if (!empty($arrayfields['total_margin_rate']['checked'])) {
				print '<td class="right nowrap">'.(($marginInfo['total_margin_rate'] == '') ? '' : price($marginInfo['total_margin_rate'], 0, '', 0, 0, 2).'%').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Total mark rate
			if (!empty($arrayfields['total_mark_rate']['checked'])) {
				print '<td class="right nowrap">'.(($marginInfo['total_mark_rate'] == '') ? '' : price($marginInfo['total_mark_rate'], 0, '', 0, 0, 2).'%').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
					$totalarray['pos'][$totalarray['nbfield']] = 'total_mark_rate';
				}
				if ($i >= $imaxinloop - 1) {
					if (!empty($total_ht)) {
						$totalarray['val']['total_mark_rate'] = price2num($total_margin * 100 / $total_ht, 'MT');
					} else {
						$totalarray['val']['total_mark_rate'] = '';
					}
				}
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (!empty($arrayfields['f.datec']['checked'])) {
				print '<td class="nowraponall center">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date modification
			if (!empty($arrayfields['f.tms']['checked'])) {
				print '<td class="nowraponall center">';
				print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date closing
			if (!empty($arrayfields['f.date_closing']['checked'])) {
				print '<td class="nowraponall center">';
				print dol_print_date($db->jdate($obj->date_closing), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Note public
			if (!empty($arrayfields['f.note_public']['checked'])) {
				print '<td class="sensiblehtmlcontent center">';
				print dolPrintHTML($obj->note_public);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Note private
			if (!empty($arrayfields['f.note_private']['checked'])) {
				print '<td class="center">';
				print dolPrintHTML($obj->note_private);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Template Invoice
			if (!empty($arrayfields['f.fk_fac_rec_source']['checked'])) {
				print '<td class="center">';
				if (!empty($obj->fk_fac_rec_source)) {
					$facrec = new FactureRec($db);
					$result = $facrec->fetch($obj->fk_fac_rec_source);
					if ($result < 0) {
						setEventMessages($facrec->error, $facrec->errors, 'errors');
					} else {
						print $facrec->getNomUrl();
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Status
			if (!empty($arrayfields['f.fk_statut']['checked'])) {
				print '<td class="nowrap center">';
				print $facturestatic->getLibStatut(5, $paiement);
				print "</td>";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Action column (Show the massaction button only when this page is not opend from the Extended POS)

			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($obj->id, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
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

	// Use correct digits number for totals
	$totalarray['val']['total_pa'] = (isset($totalarray['val']['total_pa']) ? price2num($totalarray['val']['total_pa'], 'MT') : null);
	$totalarray['val']['total_margin'] = (isset($totalarray['val']['total_margin']) ? price2num($totalarray['val']['total_margin'], 'MT') : null);

	// Show total line
	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
}

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

// Show the file area only when this page is not opend from the Extended POS
if ($contextpage != 'poslist') {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $user->hasRight("facture", "lire");
	$delallowed = $user->hasRight("facture", "creer");
	$title = '';

	print $formfile->showdocuments('massfilesarea_invoices', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();

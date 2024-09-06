<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005		Marc Barilley / Ocebo		<marc@ocebo.com>
 * Copyright (C) 2005-2013	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani			<acianfa@free.fr>
 * Copyright (C) 2010-2011	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2010-2022	Philippe Grand				<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2016-2021	Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2017-2023	Charlene Benke				<charlene@patas-monkey.com>
 * Copyright (C) 2018		Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2019-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2021		Anthony Berton				<anthony.berton@bb2a.fr>
 * Copyright (C) 2021-2024  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2022		Josep Lluís Amador			<joseplluis@lliuretic.cat>
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
 *	\file       	htdocs/comm/propal/list.php
 *	\ingroup    	propal
 *	\brief      	Page of commercial proposals card and list
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (isModEnabled('margin')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'propal', 'compta', 'bills', 'orders', 'products', 'deliveries', 'categories'));
if (isModEnabled("shipping")) {
	$langs->loadLangs(array('sendings'));
}

// Get Parameters
$socid = GETPOSTINT('socid');

$action 	= GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm 	= GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect 	= GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'proposallist';
$optioncss  = GETPOST('optioncss', 'alpha');
$mode 		= GETPOST('mode', 'alpha');

// Search Fields
$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_user 	= GETPOSTINT('search_user');
if ($search_user == -1) {
	$search_user = 0;
}
$search_sale 	= GETPOSTINT('search_sale');
$search_ref 	= GETPOST('sf_ref') ? GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_refcustomer = GETPOST('search_refcustomer', 'alpha');
$search_refproject = GETPOST('search_refproject', 'alpha');
$search_project = GETPOST('search_project', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');
$search_societe_alias = GETPOST('search_societe_alias', 'alpha');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_vat = GETPOST('search_montant_vat', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$search_warehouse = GETPOST('search_warehouse', 'alpha');
$search_multicurrency_code = GETPOST('search_multicurrency_code', 'alpha');
$search_multicurrency_tx = GETPOST('search_multicurrency_tx', 'alpha');
$search_multicurrency_montant_ht = GETPOST('search_multicurrency_montant_ht', 'alpha');
$search_multicurrency_montant_vat = GETPOST('search_multicurrency_montant_vat', 'alpha');
$search_multicurrency_montant_ttc = GETPOST('search_multicurrency_montant_ttc', 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_product_category = GETPOSTINT('search_product_category');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state");
$search_country = GETPOST("search_country", 'aZ09');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'intcomma');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_date_end_startday = GETPOSTINT('search_date_end_startday');
$search_date_end_startmonth = GETPOSTINT('search_date_end_startmonth');
$search_date_end_startyear = GETPOSTINT('search_date_end_startyear');
$search_date_end_endday = GETPOSTINT('search_date_end_endday');
$search_date_end_endmonth = GETPOSTINT('search_date_end_endmonth');
$search_date_end_endyear = GETPOSTINT('search_date_end_endyear');
$search_date_end_start = dol_mktime(0, 0, 0, $search_date_end_startmonth, $search_date_end_startday, $search_date_end_startyear);	// Use tzserver
$search_date_end_end = dol_mktime(23, 59, 59, $search_date_end_endmonth, $search_date_end_endday, $search_date_end_endyear);
$search_date_delivery_startday = GETPOSTINT('search_date_delivery_startday');
$search_date_delivery_startmonth = GETPOSTINT('search_date_delivery_startmonth');
$search_date_delivery_startyear = GETPOSTINT('search_date_delivery_startyear');
$search_date_delivery_endday = GETPOSTINT('search_date_delivery_endday');
$search_date_delivery_endmonth = GETPOSTINT('search_date_delivery_endmonth');
$search_date_delivery_endyear = GETPOSTINT('search_date_delivery_endyear');
$search_date_delivery_start = dol_mktime(0, 0, 0, $search_date_delivery_startmonth, $search_date_delivery_startday, $search_date_delivery_startyear);
$search_date_delivery_end = dol_mktime(23, 59, 59, $search_date_delivery_endmonth, $search_date_delivery_endday, $search_date_delivery_endyear);
$search_availability = GETPOST('search_availability', 'intcomma');
$search_categ_cus = GETPOST("search_categ_cus", 'intcomma');
$search_fk_cond_reglement = GETPOST("search_fk_cond_reglement", 'intcomma');
$search_fk_shipping_method = GETPOST("search_fk_shipping_method", 'intcomma');
$search_fk_input_reason = GETPOST("search_fk_input_reason", 'intcomma');
$search_fk_mode_reglement = GETPOST("search_fk_mode_reglement", 'intcomma');
$search_date_signature_startday = GETPOSTINT('search_date_signature_startday');
$search_date_signature_startmonth = GETPOSTINT('search_date_signature_startmonth');
$search_date_signature_startyear = GETPOSTINT('search_date_signature_startyear');
$search_date_signature_endday = GETPOSTINT('search_date_signature_endday');
$search_date_signature_endmonth = GETPOSTINT('search_date_signature_endmonth');
$search_date_signature_endyear = GETPOSTINT('search_date_signature_endyear');
$search_date_signature_start = dol_mktime(0, 0, 0, $search_date_signature_startmonth, $search_date_signature_startday, $search_date_signature_startyear);
$search_date_signature_end = dol_mktime(23, 59, 59, $search_date_signature_endmonth, $search_date_signature_endday, $search_date_signature_endyear);
$search_status = GETPOST('search_status', 'alpha');
$search_note_public = GETPOST('search_note_public', 'alpha');

// Pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'p.ref';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

// Security check
$module = 'propal';
$dbtable = '';
$objectid = '';
if (!empty($user->socid)) {
	$socid = $user->socid;
}
if (!empty($socid)) {
	$objectid = $socid;
	$module = 'societe';
	$dbtable = '&societe';
}
$hookmanager->initHooks(array('propallist'));
$result = restrictedArea($user, $module, $objectid, $dbtable);

$diroutputmassaction = $conf->propal->multidir_output[$conf->entity].'/temp/massgeneration/'.$user->id;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new Propal($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref' => 'Ref',
	'p.ref_client' => 'RefCustomer',
	'pd.description' => 'Description',
	's.nom' => "ThirdParty",
	's.name_alias' => "AliasNameShort",
	's.zip' => "Zip",
	's.town' => "Town",
	'p.note_public' => 'NotePublic',
);
if (empty($user->socid)) {
	$fieldstosearchall["p.note_private"] = "NotePrivate";
}


$checkedtypetiers = 0;
$arrayfields = array(
	'p.ref' => array('label' => "Ref", 'checked' => 1),
	'p.ref_client' => array('label' => "RefCustomer", 'checked' => -1),
	'pr.ref' => array('label' => "ProjectRef", 'checked' => 1, 'enabled' => (isModEnabled('project') ? 1 : 0)),
	'pr.title' => array('label' => "ProjectLabel", 'checked' => 0, 'enabled' => (isModEnabled('project') ? 1 : 0)),
	's.nom' => array('label' => "ThirdParty", 'checked' => 1),
	's.name_alias' => array('label' => "AliasNameShort", 'checked' => -1),
	's.town' => array('label' => "Town", 'checked' => -1),
	's.zip' => array('label' => "Zip", 'checked' => -1),
	'state.nom' => array('label' => "StateShort", 'checked' => 0),
	'country.code_iso' => array('label' => "Country", 'checked' => 0),
	'typent.code' => array('label' => "ThirdPartyType", 'checked' => $checkedtypetiers),
	'p.date' => array('label' => "DatePropal", 'checked' => 1),
	'p.fin_validite' => array('label' => "DateEnd", 'checked' => 1),
	'p.date_livraison' => array('label' => "DeliveryDate", 'checked' => 0),
	'p.date_signature' => array('label' => "DateSigning", 'checked' => 0),
	'ava.rowid' => array('label' => "AvailabilityPeriod", 'checked' => 0),
	'p.fk_shipping_method' => array('label' => "SendingMethod", 'checked' => 0, 'enabled' => isModEnabled("shipping")),
	'p.fk_input_reason' => array('label' => "Origin", 'checked' => 0, 'enabled' => 1),
	'p.fk_cond_reglement' => array('label' => "PaymentConditionsShort", 'checked' => 0),
	'p.fk_mode_reglement' => array('label' => "PaymentMode", 'checked' => 0),
	'p.total_ht' => array('label' => "AmountHT", 'checked' => 1),
	'p.total_tva' => array('label' => "AmountVAT", 'checked' => 0),
	'p.total_ttc' => array('label' => "AmountTTC", 'checked' => 0),
	'p.total_ht_invoiced' => array('label' => "AmountInvoicedHT", 'checked' => 0, 'enabled' => getDolGlobalString('PROPOSAL_SHOW_INVOICED_AMOUNT')),
	'p.total_invoiced' => array('label' => "AmountInvoicedTTC", 'checked' => 0, 'enabled' => getDolGlobalString('PROPOSAL_SHOW_INVOICED_AMOUNT')),
	'p.multicurrency_code' => array('label' => 'Currency', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'p.multicurrency_tx' => array('label' => 'CurrencyRate', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'p.multicurrency_total_ht' => array('label' => 'MulticurrencyAmountHT', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'p.multicurrency_total_tva' => array('label' => 'MulticurrencyAmountVAT', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'p.multicurrency_total_ttc' => array('label' => 'MulticurrencyAmountTTC', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'p.multicurrency_total_ht_invoiced' => array('label' => 'MulticurrencyAmountInvoicedHT', 'checked' => 0, 'enabled' => isModEnabled("multicurrency") && getDolGlobalString('PROPOSAL_SHOW_INVOICED_AMOUNT')),
	'p.multicurrency_total_invoiced' => array('label' => 'MulticurrencyAmountInvoicedTTC', 'checked' => 0, 'enabled' => isModEnabled("multicurrency") && getDolGlobalString('PROPOSAL_SHOW_INVOICED_AMOUNT')),
	'u.login' => array('label' => "Author", 'checked' => 1, 'position' => 10),
	'sale_representative' => array('label' => "SaleRepresentativesOfThirdParty", 'checked' => -1),
	'total_pa' => array('label' => (getDolGlobalString('MARGIN_TYPE') == '1' ? 'BuyingPrice' : 'CostPrice'), 'checked' => 0, 'position' => 300, 'enabled' => (!isModEnabled('margin') || !$user->hasRight('margins', 'liretous') ? 0 : 1)),
	'total_margin' => array('label' => 'Margin', 'checked' => 0, 'position' => 301, 'enabled' => (!isModEnabled('margin') || !$user->hasRight('margins', 'liretous') ? 0 : 1)),
	'total_margin_rate' => array('label' => 'MarginRate', 'checked' => 0, 'position' => 302, 'enabled' => (!isModEnabled('margin') || !$user->hasRight('margins', 'liretous') || !getDolGlobalString('DISPLAY_MARGIN_RATES') ? 0 : 1)),
	'total_mark_rate' => array('label' => 'MarkRate', 'checked' => 0, 'position' => 303, 'enabled' => (!isModEnabled('margin') || !$user->hasRight('margins', 'liretous') || !getDolGlobalString('DISPLAY_MARK_RATES') ? 0 : 1)),
	'p.datec' => array('label' => "DateCreation", 'checked' => 0, 'position' => 500),
	'p.tms' => array('label' => "DateModificationShort", 'checked' => 0, 'position' => 500),
	'p.date_cloture' => array('label' => "DateClosing", 'checked' => 0, 'position' => 500),
	'p.note_public' => array('label' => 'NotePublic', 'checked' => 0, 'position' => 510, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PUBLIC_NOTES'))),
	'p.note_private' => array('label' => 'NotePrivate', 'checked' => 0, 'position' => 511, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PRIVATE_NOTES'))),
	'p.fk_statut' => array('label' => "Status", 'checked' => 1, 'position' => 1000),
);

// List of fields to search into when doing a "search in all"
/*$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['t.'.$key] = $val['label'];
	}
}*/

// Definition of array of fields for columns
/*$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval((string) $val['visible'], 1);
		$arrayfields['t.'.$key] = array(
			'label'=>$val['label'],
			'checked'=>(($visible < 0) ? 0 : 1),
			'enabled'=>(abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
			'position'=>$val['position'],
			'help'=> isset($val['help']) ? $val['help'] : ''
		);
	}
}*/

if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

// Permissions
$permissiontoread = $user->hasRight('propal', 'lire');
$permissiontoadd = $user->hasRight('propal', 'creer');
$permissiontodelete = $user->hasRight('propal', 'supprimer');
if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$permissiontovalidate = $user->hasRight('propal', 'propal_advance', 'validate');
	$permissiontoclose = $user->hasRight('propal', 'propal_advance', 'close');
	$permissiontosendbymail = $user->hasRight('propal', 'propal_advance', 'send');
} else {
	$permissiontovalidate = $user->hasRight('propal', 'creer');
	$permissiontoclose = $user->hasRight('propal', 'creer');
	$permissiontosendbymail = $user->hasRight('propal', 'lire');
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

$parameters = array('socid' => $socid, 'arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_user = '';
		$search_sale = '';
		$search_ref = '';
		$search_refcustomer = '';
		$search_refproject = '';
		$search_project = '';
		$search_societe = '';
		$search_societe_alias = '';
		$search_montant_ht = '';
		$search_montant_vat = '';
		$search_montant_ttc = '';
		$search_warehouse = '';
		$search_multicurrency_code = '';
		$search_multicurrency_tx = '';
		$search_multicurrency_montant_ht = '';
		$search_multicurrency_montant_vat = '';
		$search_multicurrency_montant_ttc = '';
		$search_login = '';
		$search_product_category = '';
		$search_town = '';
		$search_zip = "";
		$search_state = "";
		$search_type = '';
		$search_country = '';
		$search_type_thirdparty = '';
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_date_end_startday = '';
		$search_date_end_startmonth = '';
		$search_date_end_startyear = '';
		$search_date_end_endday = '';
		$search_date_end_endmonth = '';
		$search_date_end_endyear = '';
		$search_date_end_start = '';
		$search_date_end_end = '';
		$search_date_delivery_startday = '';
		$search_date_delivery_startmonth = '';
		$search_date_delivery_startyear = '';
		$search_date_delivery_endday = '';
		$search_date_delivery_endmonth = '';
		$search_date_delivery_endyear = '';
		$search_date_delivery_start = '';
		$search_date_delivery_end = '';
		$search_availability = '';
		$search_status = '';
		$search_categ_cus = 0;
		$search_fk_cond_reglement = '';
		$search_fk_shipping_method = '';
		$search_fk_input_reason = '';
		$search_fk_mode_reglement = '';
		$search_date_signature_startday = '';
		$search_date_signature_startmonth = '';
		$search_date_signature_startyear = '';
		$search_date_signature_endday = '';
		$search_date_signature_endmonth = '';
		$search_date_signature_endyear = '';
		$search_date_signature_start = '';
		$search_date_signature_end = '';
		$toselect = array();
		$search_array_options = array();
		$socid = 0;
	}

	// Mass actions
	$objectclass = 'Propal';
	$objectlabel = 'Proposals';
	$uploaddir = $conf->propal->multidir_output[$conf->entity];
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if ($action == 'validate' && $permissiontovalidate) {
	if (GETPOST('confirm') == 'yes') {
		$tmpproposal = new Propal($db);
		$db->begin();
		$error = 0;
		foreach ($toselect as $checked) {
			if ($tmpproposal->fetch($checked) > 0) {
				if ($tmpproposal->statut == $tmpproposal::STATUS_DRAFT) {
					if ($tmpproposal->valid($user) > 0) {
						setEventMessages($langs->trans('hasBeenValidated', $tmpproposal->ref), null, 'mesgs');
					} else {
						setEventMessages($tmpproposal->error, $tmpproposal->errors, 'errors');
						$error++;
					}
				} else {
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorIsNotADraft', $tmpproposal->ref), null, 'errors');
					$error++;
				}
			} else {
				setEventMessages($tmpproposal->error, $tmpproposal->errors, 'errors');
				$error++;
			}
		}
		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
		}
	}
}

if ($action == "sign" && $permissiontoclose) {
	if (GETPOST('confirm') == 'yes') {
		$tmpproposal = new Propal($db);
		$db->begin();
		$error = 0;
		foreach ($toselect as $checked) {
			if ($tmpproposal->fetch($checked) > 0) {
				if ($tmpproposal->statut == $tmpproposal::STATUS_VALIDATED) {
					$tmpproposal->statut = $tmpproposal::STATUS_SIGNED;
					if ($tmpproposal->closeProposal($user, $tmpproposal::STATUS_SIGNED) >= 0) {
						setEventMessages($tmpproposal->ref." ".$langs->trans('Signed'), null, 'mesgs');
					} else {
						setEventMessages($tmpproposal->error, $tmpproposal->errors, 'errors');
						$error++;
					}
				} else {
					setEventMessage($langs->trans('MustBeValidatedToBeSigned', $tmpproposal->ref), 'errors');
					$error++;
				}
			} else {
				setEventMessages($tmpproposal->error, $tmpproposal->errors, 'errors');
				$error++;
			}
		}
		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
		}
	}
}

if ($action == "nosign" && $permissiontoclose) {
	if (GETPOST('confirm') == 'yes') {
		$tmpproposal = new Propal($db);
		$db->begin();
		$error = 0;
		foreach ($toselect as $checked) {
			if ($tmpproposal->fetch($checked) > 0) {
				if ($tmpproposal->statut == $tmpproposal::STATUS_VALIDATED || (getDolGlobalString('PROPAL_SKIP_ACCEPT_REFUSE') && $tmpproposal->statut == $tmpproposal::STATUS_DRAFT)) {
					$tmpproposal->statut = $tmpproposal::STATUS_NOTSIGNED;
					if ($tmpproposal->closeProposal($user, $tmpproposal::STATUS_NOTSIGNED) > 0) {
						setEventMessage($tmpproposal->ref." ".$langs->trans('NoSigned'), 'mesgs');
					} else {
						setEventMessages($tmpproposal->error, $tmpproposal->errors, 'errors');
						$error++;
					}
				} else {
					setEventMessage($tmpproposal->ref." ".$langs->trans('CantBeNoSign'), 'errors');
					$error++;
				}
			} else {
				setEventMessages($tmpproposal->error, $tmpproposal->errors, 'errors');
				$error++;
			}
		}
		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
		}
	}
}

// Closed records
if (!$error && $massaction === 'setbilled' && $permissiontoclose) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;
	foreach ($toselect as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			$result = $objecttmp->classifyBilled($user, 0);
			if ($result <= 0) {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
		$db->commit();
	} else {
		$db->rollback();
	}
}



/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$formmargin = null;
if (isModEnabled('margin')) {
	$formmargin = new FormMargin($db);
}
$companystatic = new Societe($db);
$projectstatic = new Project($db);
$formcompany = new FormCompany($db);

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

$sql = 'SELECT';
if ($search_all > 0) {
	$sql = 'SELECT DISTINCT';
}
$sql .= ' s.rowid as socid, s.nom as name, s.name_alias as alias, s.email, s.phone, s.fax , s.address, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client,';
$sql .= " typent.code as typent_code,";
$sql .= " ava.rowid as availability,";
$sql .= " country.code as country_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= ' p.rowid, p.entity as propal_entity, p.note_private, p.total_ht, p.total_tva, p.total_ttc, p.localtax1, p.localtax2, p.ref, p.ref_client, p.fk_statut as status, p.fk_user_author, p.datep as dp, p.fin_validite as dfv, p.date_livraison as ddelivery,';
$sql .= ' p.fk_multicurrency, p.multicurrency_code, p.multicurrency_tx, p.multicurrency_total_ht, p.multicurrency_total_tva, p.multicurrency_total_ttc,';
$sql .= ' p.datec as date_creation, p.tms as date_modification, p.date_cloture as date_cloture,';
$sql .= ' p.date_signature as dsignature,';
$sql .= ' p.note_public, p.note_private,';
$sql .= ' p.fk_cond_reglement,p.deposit_percent,p.fk_mode_reglement,p.fk_shipping_method,p.fk_input_reason,';
$sql .= " pr.rowid as project_id, pr.ref as project_ref, pr.title as project_label,";
$sql .= ' u.login, u.lastname, u.firstname, u.email as user_email, u.statut as user_statut, u.entity as user_entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender';
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
$sql = preg_replace('/, $/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";

$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'propal as p ON p.fk_soc = s.rowid';
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (p.rowid = ef.fk_object)";
}
if ($search_all) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet as pd ON p.rowid = pd.fk_propal';
}
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as pr ON pr.rowid = p.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_availability as ava on (ava.rowid = p.fk_availability)";
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' WHERE';
$sql .= ' p.entity IN ('.getEntity('propal').')';
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
	$sql .= " AND s.fk_pays IN (".$db->sanitize($db->escape($search_country)).')';
}
if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($db->escape($search_type_thirdparty)).')';
}
if ($search_ref) {
	$sql .= natural_search('p.ref', $search_ref);
}
if ($search_refcustomer) {
	$sql .= natural_search('p.ref_client', $search_refcustomer);
}
if ($search_refproject) {
	$sql .= natural_search('pr.ref', $search_refproject);
}
if ($search_project) {
	$sql .= natural_search('pr.title', $search_project);
}
if ($search_availability) {
	$sql .= " AND p.fk_availability IN (".$db->sanitize($db->escape($search_availability)).')';
}
$societe_add_ref_in_list = getDolGlobalInt('SOCIETE_ADD_REF_IN_LIST');
if (empty($arrayfields['s.name_alias']['checked']) && $search_societe) {
	if ($societe_add_ref_in_list == 1) {
		$sql .= natural_search(array("s.nom", "s.name_alias", "s.code_client"), $search_societe);
	} else {
		$sql .= natural_search(array("s.nom", "s.name_alias"), $search_societe);
	}
} else {
	if ($search_societe) {
		if ($societe_add_ref_in_list == 1) {
			$sql .= natural_search(array('s.nom', 's.code_client'), $search_societe);
		} else {
			$sql .= natural_search('s.nom', $search_societe);
		}
	}
	if ($search_societe_alias) {
		$sql .= natural_search('s.name_alias', $search_societe_alias);
	}
}
// Rechercher dans code_client si SOCIETE_ADD_REF_IN_LIST est égal à 1
if ($societe_add_ref_in_list == 1 && $search_code_client && !$search_societe) {
	$sql .= natural_search('s.code_client', $search_code_client);
}
if ($search_login) {
	$sql .= natural_search(array("u.login", "u.firstname", "u.lastname"), $search_login);
}
if ($search_montant_ht != '') {
	$sql .= natural_search("p.total_ht", $search_montant_ht, 1);
}
if ($search_montant_vat != '') {
	$sql .= natural_search("p.total_tva", $search_montant_vat, 1);
}
if ($search_montant_ttc != '') {
	$sql .= natural_search("p.total_ttc", $search_montant_ttc, 1);
}
if ($search_warehouse != '' && $search_warehouse > 0) {
	$sql .= natural_search("p.fk_warehouse", $search_warehouse, 1);
}
if ($search_multicurrency_code != '') {
	$sql .= " AND p.multicurrency_code = '".$db->escape($search_multicurrency_code)."'";
}
if ($search_multicurrency_tx != '') {
	$sql .= natural_search('p.multicurrency_tx', $search_multicurrency_tx, 1);
}
if ($search_multicurrency_montant_ht != '') {
	$sql .= natural_search('p.multicurrency_total_ht', $search_multicurrency_montant_ht, 1);
}
if ($search_multicurrency_montant_vat != '') {
	$sql .= natural_search('p.multicurrency_total_tva', $search_multicurrency_montant_vat, 1);
}
if ($search_multicurrency_montant_ttc != '') {
	$sql .= natural_search('p.multicurrency_total_ttc', $search_multicurrency_montant_ttc, 1);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if ($search_fk_cond_reglement > 0) {
	$sql .= " AND p.fk_cond_reglement = ".((int) $search_fk_cond_reglement);
}
if ($search_fk_shipping_method > 0) {
	$sql .= " AND p.fk_shipping_method = ".((int) $search_fk_shipping_method);
}
if ($search_fk_input_reason > 0) {
	$sql .= " AND p.fk_input_reason = ".((int) $search_fk_input_reason);
}
if ($search_fk_mode_reglement > 0) {
	$sql .= " AND p.fk_mode_reglement = ".((int) $search_fk_mode_reglement);
}
if ($socid > 0) {
	$sql .= ' AND s.rowid = '.((int) $socid);
}
if ($search_status != '' && $search_status != '-1') {
	$sql .= ' AND p.fk_statut IN ('.$db->sanitize($search_status).')';
}
if ($search_date_start) {
	$sql .= " AND p.datep >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND p.datep <= '".$db->idate($search_date_end)."'";
}
if ($search_date_end_start) {
	$sql .= " AND p.fin_validite >= '".$db->idate($search_date_end_start)."'";
}
if ($search_date_end_end) {
	$sql .= " AND p.fin_validite <= '".$db->idate($search_date_end_end)."'";
}
if ($search_date_delivery_start) {
	$sql .= " AND p.date_livraison >= '".$db->idate($search_date_delivery_start)."'";
}
if ($search_date_delivery_end) {
	$sql .= " AND p.date_livraison <= '".$db->idate($search_date_delivery_end)."'";
}
if ($search_date_signature_start) {
	$sql .= " AND p.date_signature >= '".$db->idate($search_date_signature_start)."'";
}
if ($search_date_signature_end) {
	$sql .= " AND p.date_signature <= '".$db->idate($search_date_signature_end)."'";
}
if ($search_note_public) {
	$sql .= " AND p.note_public LIKE '%".$db->escape($db->escapeforlike($search_note_public))."%'";
}
// Search on user
if ($search_user > 0) {
	$sql .= " AND EXISTS (";
	$sql .= " SELECT ec.fk_c_type_contact, ec.element_id, ec.fk_socpeople";
	$sql .= " FROM llx_element_contact as ec";
	$sql .= " INNER JOIN  llx_c_type_contact as tc";
	$sql .= " ON ec.fk_c_type_contact = tc.rowid AND tc.element='propal' AND tc.source='internal'";
	$sql .= " WHERE ec.element_id = p.rowid AND ec.fk_socpeople = ".((int) $search_user).")";
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
// Search for tag/category ($searchCategoryCustomerList is an array of ID)
$searchCategoryCustomerOperator = -1;
$searchCategoryCustomerList = array($search_categ_cus);
if (!empty($searchCategoryCustomerList)) {
	$searchCategoryCustomerSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$searchCategoryCustomerSqlList[] = "NOT EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc)";
		} elseif (intval($searchCategoryCustomer) > 0) {
			if ($searchCategoryCustomerOperator == 0) {
				$searchCategoryCustomerSqlList[] = " EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc AND cs.fk_categorie = ".((int) $searchCategoryCustomer).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryCustomer);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryCustomerSqlList[] = " EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc AND cs.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
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
// Search for tag/category ($searchCategoryProductList is an array of ID)
$searchCategoryProductOperator = -1;
$searchCategoryProductList = array($search_product_category);
if (!empty($searchCategoryProductList)) {
	$searchCategoryProductSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."propaldet as pd WHERE pd.fk_propal = p.rowid AND pd.fk_product = ck.fk_product)";
		} elseif (intval($searchCategoryProduct) > 0) {
			if ($searchCategoryProductOperator == 0) {
				$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."propaldet as pd WHERE pd.fk_propal = p.rowid AND pd.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."propaldet as pd WHERE pd.fk_propal = p.rowid AND pd.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
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
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
//print $sql;

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

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
$sql .= ', p.ref DESC';
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$objectstatic = new Propal($db);
$userstatic = new User($db);

if ($socid > 0) {
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title = $langs->trans('Proposals').' - '.$soc->name;
	if (empty($search_societe)) {
		$search_societe = $soc->name;
	}
} else {
	$title = $langs->trans('Proposals');
}

$num = $db->num_rows($resql);

$arrayofselected = is_array($toselect) ? $toselect : array();

if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all) {
	$obj = $db->fetch_object($resql);

	$id = $obj->rowid;

	header("Location: ".DOL_URL_ROOT.'/comm/propal/card.php?id='.$id);
	exit;
}

$help_url = 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist');

$param = '&search_status='.urlencode($search_status);
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
if ($search_date_end_startday) {
	$param .= '&search_date_end_startday='.urlencode((string) ($search_date_end_startday));
}
if ($search_date_end_startmonth) {
	$param .= '&search_date_end_startmonth='.urlencode((string) ($search_date_end_startmonth));
}
if ($search_date_end_startyear) {
	$param .= '&search_date_end_startyear='.urlencode((string) ($search_date_end_startyear));
}
if ($search_date_end_endday) {
	$param .= '&search_date_end_endday='.urlencode((string) ($search_date_end_endday));
}
if ($search_date_end_endmonth) {
	$param .= '&search_date_end_endmonth='.urlencode((string) ($search_date_end_endmonth));
}
if ($search_date_end_endyear) {
	$param .= '&search_date_end_endyear='.urlencode((string) ($search_date_end_endyear));
}
if ($search_date_delivery_startday) {
	$param .= '&search_date_delivery_startday='.urlencode((string) ($search_date_delivery_startday));
}
if ($search_date_delivery_startmonth) {
	$param .= '&search_date_delivery_startmonth='.urlencode((string) ($search_date_delivery_startmonth));
}
if ($search_date_delivery_startyear) {
	$param .= '&search_date_delivery_startyear='.urlencode((string) ($search_date_delivery_startyear));
}
if ($search_date_delivery_endday) {
	$param .= '&search_date_delivery_endday='.urlencode((string) ($search_date_delivery_endday));
}
if ($search_date_delivery_endmonth) {
	$param .= '&search_date_delivery_endmonth='.urlencode((string) ($search_date_delivery_endmonth));
}
if ($search_date_delivery_endyear) {
	$param .= '&search_date_delivery_endyear='.urlencode((string) ($search_date_delivery_endyear));
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_refcustomer) {
	$param .= '&search_refcustomer='.urlencode($search_refcustomer);
}
if ($search_refproject) {
	$param .= '&search_refproject='.urlencode($search_refproject);
}
if ($search_societe) {
	$param .= '&search_societe='.urlencode($search_societe);
}
if ($search_societe_alias) {
	$param .= '&search_societe_alias='.urlencode($search_societe_alias);
}
if ($search_user > 0) {
	$param .= '&search_user='.urlencode((string) ($search_user));
}
if ($search_sale > 0) {
	$param .= '&search_sale='.urlencode($search_sale);
}
if ($search_montant_ht) {
	$param .= '&search_montant_ht='.urlencode($search_montant_ht);
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
if ($search_login) {
	$param .= '&search_login='.urlencode($search_login);
}
if ($search_town) {
	$param .= '&search_town='.urlencode($search_town);
}
if ($search_zip) {
	$param .= '&search_zip='.urlencode($search_zip);
}
if ($socid > 0) {
	$param .= '&socid='.urlencode((string) ($socid));
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_categ_cus > 0) {
	$param .= '&search_categ_cus='.urlencode((string) ($search_categ_cus));
}
if ($search_product_category != '') {
	$param .= '&search_product_category='.urlencode((string) ($search_product_category));
}
if ($search_fk_cond_reglement > 0) {
	$param .= '&search_fk_cond_reglement='.urlencode((string) ($search_fk_cond_reglement));
}
if ($search_fk_shipping_method > 0) {
	$param .= '&search_fk_shipping_method='.urlencode((string) ($search_fk_shipping_method));
}
if ($search_fk_input_reason > 0) {
	$param .= '&search_fk_input_reason='.urlencode((string) ($search_fk_input_reason));
}
if ($search_fk_mode_reglement > 0) {
	$param .= '&search_fk_mode_reglement='.urlencode((string) ($search_fk_mode_reglement));
}
if ($search_type_thirdparty > 0) {
	$param .= '&search_type_thirdparty='.urlencode((string) ($search_type_thirdparty));
}
if ($search_town) {
	$param .= '&search_town='.urlencode($search_town);
}
if ($search_zip) {
	$param .= '&search_zip='.urlencode($search_zip);
}
if ($search_state) {
	$param .= '&search_state='.urlencode($search_state);
}
if ($search_town) {
	$param .= '&search_town='.urlencode($search_town);
}
if ($search_country) {
	$param .= '&search_country='.urlencode((string) ($search_country));
}
if ($search_date_signature_startday) {
	$param .= '&search_date_signature_startday='.urlencode((string) ($search_date_signature_startday));
}
if ($search_date_signature_startmonth) {
	$param .= '&search_date_signature_startmonth='.urlencode((string) ($search_date_signature_startmonth));
}
if ($search_date_signature_startyear) {
	$param .= '&search_date_signature_startyear='.urlencode((string) ($search_date_signature_startyear));
}
if ($search_date_signature_endday) {
	$param .= '&search_date_signature_endday='.urlencode((string) ($search_date_signature_endday));
}
if ($search_date_signature_endmonth) {
	$param .= '&search_date_signature_endmonth='.urlencode((string) ($search_date_signature_endmonth));
}
if ($search_date_signature_endyear) {
	$param .= '&search_date_signature_endyear='.urlencode((string) ($search_date_signature_endyear));
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
	'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
if ($permissiontosendbymail) {
	$arrayofmassactions['presend'] = img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail");
}
if ($permissiontovalidate) {
	$arrayofmassactions['prevalidate'] = img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate");
}
if ($permissiontoclose) {
	$arrayofmassactions['presign'] = img_picto('', 'propal', 'class="pictofixedwidth"').$langs->trans("Sign");
	$arrayofmassactions['nopresign'] = img_picto('', 'propal', 'class="pictofixedwidth"').$langs->trans("NoSign");
	$arrayofmassactions['setbilled'] = img_picto('', 'bill', 'class="pictofixedwidth"').$langs->trans("ClassifyBilled");
}
if ($permissiontodelete) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}

if (in_array($massaction, array('presend', 'predelete', 'closed'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$url = DOL_URL_ROOT.'/comm/propal/card.php?action=create';
if (!empty($socid)) {
	$url .= '&socid='.$socid;
}
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewPropal'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('propal', 'creer'));

// Fields title search
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="socid" value="'.$socid.'">';
print '<input type="hidden" name="mode"value="'.$mode.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'propal', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "SendPropalRef";
$modelmail = "propal_send";
$objecttmp = new Propal($db);
$trackid = 'pro'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($massaction == 'prevalidate') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassValidation"), $langs->trans("ConfirmMassValidationQuestion"), "validate", null, '', 0, 200, 500, 1);
}

if ($massaction == 'presign') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassSignature"), $langs->trans("ConfirmMassSignatureQuestion"), "sign", null, '', 0, 200, 500, 1);
}

if ($massaction == 'nopresign') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassNoSignature"), $langs->trans("ConfirmMassNoSignatureQuestion"), "nosign", null, '', 0, 200, 500, 1);
}

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
}

$i = 0;

$moreforfilter = '';

// If the user can view prospects other than his'
if ($user->hasRight('user', 'user', 'lire')) {
	$langs->load("commercial");
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth250 widthcentpercentminusx', 1);
	$moreforfilter .= '</div>';
}
// If the user can view prospects other than his'
if ($user->hasRight('user', 'user', 'lire')) {
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('LinkedToSpecificUsers');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers((empty($search_user) ? -2 : 0), 'search_user', $tmptitle, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth250 widthcentpercentminusx');
	$moreforfilter .= '</div>';
}
// If the user can view products
if (isModEnabled('category') && $user->hasRight('categorie', 'read') && ($user->hasRight('product', 'read') || $user->hasRight('service', 'read'))) {
	$searchCategoryProductOperator = -1;
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$tmptitle = $langs->trans('IncludingProductWithTag');
	$formcategory = new FormCategory($db);
	$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_PRODUCT, array($search_product_category), 'maxwidth300', $searchCategoryProductOperator, 0, 0, $tmptitle);
}
if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('CustomersProspectsCategoriesShort');
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$formother->select_categories('customer', $search_categ_cus, 'search_categ_cus', 1, $tmptitle, (empty($conf->dol_optimize_smallscreen) ? 'maxwidth300 widthcentpercentminusx' : 'maxwidth250 widthcentpercentminusx'));
	$moreforfilter .= '</div>';
}
if (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_PROPAL')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	$formproduct = new FormProduct($db);
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('Warehouse');
	$moreforfilter .= img_picto($tmptitle, 'stock', 'class="pictofixedwidth"').$formproduct->selectWarehouses($search_warehouse, 'search_warehouse', '', $tmptitle, 0, 0, $tmptitle);
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

print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
if (!empty($arrayfields['p.ref_client']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_refcustomer" value="'.dol_escape_htmltag($search_refcustomer).'">';
	print '</td>';
}
if (!empty($arrayfields['pr.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_refproject" value="'.dol_escape_htmltag($search_refproject).'">';
	print '</td>';
}
if (!empty($arrayfields['pr.title']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_project" value="'.dol_escape_htmltag($search_project).'">';
	print '</td>';
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth100" type="text" name="search_societe" value="'.dol_escape_htmltag($search_societe).'"'.($socid > 0 ? " disabled" : "").'>';
	print '</td>';
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth100" type="text" name="search_societe_alias" value="'.dol_escape_htmltag($search_societe_alias).'">';
	print '</td>';
}
if (!empty($arrayfields['s.town']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_town" value="'.$search_town.'"></td>';
}
if (!empty($arrayfields['s.zip']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_zip" value="'.$search_zip.'"></td>';
}
// State
if (!empty($arrayfields['state.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}
// Country
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print '<td class="liste_titre" align="center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
	print '</td>';
}
// Company type
if (!empty($arrayfields['typent.code']['checked'])) {
	print '<td class="liste_titre maxwidth100onsmartphone" align="center">';
	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, getDolGlobalString('SOCIETE_SORT_ON_TYPEENT', 'ASC'), 'maxwidth100', 1);
	print ajax_combobox('search_type_thirdparty');
	print '</td>';
}
// Date
if (!empty($arrayfields['p.date']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Date end
if (!empty($arrayfields['p.fin_validite']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end_start ? $search_date_end_start : -1, 'search_date_end_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end_end ? $search_date_end_end : -1, 'search_date_end_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Date delivery
if (!empty($arrayfields['p.date_livraison']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_delivery_start ? $search_date_delivery_start : -1, 'search_date_delivery_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_delivery_end ? $search_date_delivery_end : -1, 'search_date_delivery_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '</td>';
}
// Date Signature
if (!empty($arrayfields['p.date_signature']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_signature_start ? $search_date_signature_start : -1, 'search_date_signature_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_signature_end ? $search_date_signature_end : -1, 'search_date_signature_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '</td>';
}
// Availability
if (!empty($arrayfields['ava.rowid']['checked'])) {
	print '<td class="liste_titre maxwidth100onsmartphone center">';
	$form->selectAvailabilityDelay($search_availability, 'search_availability', '', 1);
	print ajax_combobox('search_availability');
	print '</td>';
}
// Shipping Method
if (!empty($arrayfields['p.fk_shipping_method']['checked'])) {
	print '<td class="liste_titre">';
	$form->selectShippingMethod($search_fk_shipping_method, 'search_fk_shipping_method', '', 1, '', 1);
	print '</td>';
}
// Source - Input reason
if (!empty($arrayfields['p.fk_input_reason']['checked'])) {
	print '<td class="liste_titre">';
	$form->selectInputReason($search_fk_input_reason, 'search_fk_input_reason', '', 1, 'maxwidth125', 1);
	print '</td>';
}
// Payment term
if (!empty($arrayfields['p.fk_cond_reglement']['checked'])) {
	print '<td class="liste_titre">';
	print $form->getSelectConditionsPaiements($search_fk_cond_reglement, 'search_fk_cond_reglement', 1, 1, 1);
	print '</td>';
}
// Payment mode
if (!empty($arrayfields['p.fk_mode_reglement']['checked'])) {
	print '<td class="liste_titre">';
	print $form->select_types_paiements($search_fk_mode_reglement, 'search_fk_mode_reglement', '', 0, 1, 1, 0, -1, '', 1);
	print '</td>';
}
if (!empty($arrayfields['p.total_ht']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
	print '</td>';
}
if (!empty($arrayfields['p.total_tva']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
	print '</td>';
}
if (!empty($arrayfields['p.total_ttc']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
	print '</td>';
}
if (!empty($arrayfields['p.total_ht_invoiced']['checked'])) {
	// Amount invoiced
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['p.total_invoiced']['checked'])) {
	// Amount invoiced
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['p.multicurrency_code']['checked'])) {
	// Currency
	print '<td class="liste_titre">';
	print $form->selectMultiCurrency($search_multicurrency_code, 'search_multicurrency_code', 1);
	print '</td>';
}
if (!empty($arrayfields['p.multicurrency_tx']['checked'])) {
	// Currency rate
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_tx" value="'.dol_escape_htmltag($search_multicurrency_tx).'">';
	print '</td>';
}
if (!empty($arrayfields['p.multicurrency_total_ht']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ht" value="'.dol_escape_htmltag($search_multicurrency_montant_ht).'">';
	print '</td>';
}
if (!empty($arrayfields['p.multicurrency_total_tva']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_vat" value="'.dol_escape_htmltag($search_multicurrency_montant_vat).'">';
	print '</td>';
}
if (!empty($arrayfields['p.multicurrency_total_ttc']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ttc" value="'.dol_escape_htmltag($search_multicurrency_montant_ttc).'">';
	print '</td>';
}
if (!empty($arrayfields['p.multicurrency_total_ht_invoiced']['checked'])) {
	// Amount invoiced
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['p.multicurrency_total_invoiced']['checked'])) {
	// Amount invoiced
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['u.login']['checked'])) {
	// Author
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
	print '</td>';
}
if (!empty($arrayfields['sale_representative']['checked'])) {
	print '<td class="liste_titre"></td>';
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
if (!empty($arrayfields['p.datec']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['p.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date cloture
if (!empty($arrayfields['p.date_cloture']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['p.note_public']['checked'])) {
	// Note public
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_note_public" value="'.dol_escape_htmltag($search_note_public).'">';
	print '</td>';
}
if (!empty($arrayfields['p.note_private']['checked'])) {
	// Note private
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (!empty($arrayfields['p.fk_statut']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">';
	$formpropal->selectProposalStatus($search_status, 1, 0, 1, 'customer', 'search_status', 'search_status width100 onrightofpage');
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print "</tr>\n";

$totalarray = array(
	'nbfield' => 0,
	'val' => array(
		'p.total_ht' => 0,
		'p.total_tva' => 0,
		'p.total_ttc' => 0,
	),
);

// Fields title
print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref_client']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref_client']['label'], $_SERVER["PHP_SELF"], 'p.ref_client', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pr.ref']['checked'])) {
	print_liste_field_titre($arrayfields['pr.ref']['label'], $_SERVER["PHP_SELF"], 'pr.ref', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pr.title']['checked'])) {
	print_liste_field_titre($arrayfields['pr.title']['label'], $_SERVER["PHP_SELF"], 'pr.title', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], 's.name_alias', '', $param, '', $sortfield, $sortorder);
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
if (!empty($arrayfields['p.date']['checked'])) {
	print_liste_field_titre($arrayfields['p.date']['label'], $_SERVER["PHP_SELF"], 'p.datep', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fin_validite']['checked'])) {
	print_liste_field_titre($arrayfields['p.fin_validite']['label'], $_SERVER["PHP_SELF"], 'dfv', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.date_livraison']['checked'])) {
	print_liste_field_titre($arrayfields['p.date_livraison']['label'], $_SERVER["PHP_SELF"], 'p.date_livraison', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.date_signature']['checked'])) {
	print_liste_field_titre($arrayfields['p.date_signature']['label'], $_SERVER["PHP_SELF"], 'p.date_signature', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['ava.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['ava.rowid']['label'], $_SERVER["PHP_SELF"], 'availability', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_shipping_method']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_shipping_method']['label'], $_SERVER["PHP_SELF"], "p.fk_shipping_method", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_input_reason']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_input_reason']['label'], $_SERVER["PHP_SELF"], "p.fk_input_reason", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_cond_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_cond_reglement']['label'], $_SERVER["PHP_SELF"], "p.fk_cond_reglement", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_mode_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "p.fk_mode_reglement", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['p.total_ht']['label'], $_SERVER["PHP_SELF"], 'p.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.total_tva']['checked'])) {
	print_liste_field_titre($arrayfields['p.total_tva']['label'], $_SERVER["PHP_SELF"], 'p.total_tva', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['p.total_ttc']['label'], $_SERVER["PHP_SELF"], 'p.total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.total_ht_invoiced']['checked'])) {
	print_liste_field_titre($arrayfields['p.total_ht_invoiced']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.total_invoiced']['checked'])) {
	print_liste_field_titre($arrayfields['p.total_invoiced']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.multicurrency_code']['checked'])) {
	print_liste_field_titre($arrayfields['p.multicurrency_code']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_code', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.multicurrency_tx']['checked'])) {
	print_liste_field_titre($arrayfields['p.multicurrency_tx']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_tx', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.multicurrency_total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['p.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.multicurrency_total_tva']['checked'])) {
	print_liste_field_titre($arrayfields['p.multicurrency_total_tva']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_total_tva', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.multicurrency_total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['p.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.multicurrency_total_ht_invoiced']['checked'])) {
	print_liste_field_titre($arrayfields['p.multicurrency_total_ht_invoiced']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.multicurrency_total_invoiced']['checked'])) {
	print_liste_field_titre($arrayfields['p.multicurrency_total_invoiced']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['u.login']['checked'])) {
	print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['sale_representative']['checked'])) {
	print_liste_field_titre($arrayfields['sale_representative']['label'], $_SERVER["PHP_SELF"], "", "", "$param", '', $sortfield, $sortorder);
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
$parameters = array(
	'arrayfields' => $arrayfields,
	'param' => $param,
	'sortfield' => $sortfield,
	'sortorder' => $sortorder,
	'totalarray' => &$totalarray,
);

$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

print $hookmanager->resPrint;
if (!empty($arrayfields['p.datec']['checked'])) {
	print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowraponall ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.tms']['checked'])) {
	print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowraponall ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.date_cloture']['checked'])) {
	print_liste_field_titre($arrayfields['p.date_cloture']['label'], $_SERVER["PHP_SELF"], "p.date_cloture", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.note_public']['checked'])) {
	print_liste_field_titre($arrayfields['p.note_public']['label'], $_SERVER["PHP_SELF"], "p.note_public", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.note_private']['checked'])) {
	print_liste_field_titre($arrayfields['p.note_private']['label'], $_SERVER["PHP_SELF"], "p.note_private", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_statut']['label'], $_SERVER["PHP_SELF"], "p.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// Loop on record
// --------------------------------------------------------------------
$typenArray = null;
$now = dol_now();

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

$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$objectstatic->id = $obj->rowid;
	$objectstatic->ref = $obj->ref;
	$objectstatic->ref_customer = $obj->ref_client;
	$objectstatic->note_public = $obj->note_public;
	$objectstatic->note_private = $obj->note_private;
	$objectstatic->statut = $obj->status;
	$objectstatic->status = $obj->status;

	$companystatic->id = $obj->socid;
	$companystatic->name = $obj->name;
	$companystatic->name_alias = $obj->alias;
	$companystatic->client = $obj->client;
	$companystatic->fournisseur = $obj->fournisseur;
	$companystatic->code_client = $obj->code_client;
	$companystatic->email = $obj->email;
	$companystatic->phone = $obj->phone;
	$companystatic->address = $obj->address;
	$companystatic->zip = $obj->zip;
	$companystatic->town = $obj->town;
	$companystatic->country_code = $obj->country_code;

	$projectstatic->id = $obj->project_id;
	$projectstatic->ref = $obj->project_ref;
	$projectstatic->title = $obj->project_label;

	$totalInvoicedHT = 0;
	$totalInvoicedTTC = 0;
	$multicurrency_totalInvoicedHT = 0;
	$multicurrency_totalInvoicedTTC = 0;

	$TInvoiceData = $objectstatic->InvoiceArrayList($obj->rowid);

	if (!empty($TInvoiceData)) {
		foreach ($TInvoiceData as $invoiceData) {
			$invoice = new Facture($db);
			$invoice->fetch($invoiceData->facid);

			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS') && $invoice->type == Facture::TYPE_DEPOSIT) {
				continue;
			}

			$totalInvoicedHT += $invoice->total_ht;
			$totalInvoicedTTC += $invoice->total_ttc;
			$multicurrency_totalInvoicedHT += $invoice->multicurrency_total_ht;
			$multicurrency_totalInvoicedTTC += $invoice->multicurrency_total_ttc;
		}
	}

	$marginInfo = array();
	if ($with_margin_info) {
		$objectstatic->fetch_lines();
		$marginInfo = $formmargin->getMarginInfosArray($objectstatic);
		$total_ht += $obj->total_ht;
		$total_margin += $marginInfo['total_margin'];
	}

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		$objectstatic->thirdparty = $companystatic;
		$userstatic->fetch($obj->fk_user_author);
		$arrayofparams = array('selected' => in_array($object->id, $arrayofselected), 'authorlink' => $userstatic->getNomUrl(-2), 'projectlink' => $projectstatic->getNomUrl(2));
		print $objectstatic->getKanbanView('', $arrayofparams);
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		print '<tr class="oddeven '.((getDolGlobalInt('MAIN_FINISHED_LINES_OPACITY') == 1 && $obj->status > 1) ? 'opacitymedium' : '').'">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap" align="center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['p.ref']['checked'])) {
			print '<td class="nowraponall">';

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			// Picto + Ref
			print '<td class="nobordernopadding nowraponall">';
			print $objectstatic->getNomUrl(1, '', '', 0, 1, (isset($conf->global->PROPAL_LIST_SHOW_NOTES) ? $conf->global->PROPAL_LIST_SHOW_NOTES : 1));
			print '</td>';
			// Warning
			$warnornote = '';
			if ($obj->status == Propal::STATUS_VALIDATED && $db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) {
				$warnornote .= img_warning($langs->trans("Late"));
			}
			if ($warnornote) {
				print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
				print $warnornote;
				print '</td>';
			}
			// Other picto tool
			print '<td width="16" class="nobordernopadding right">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->propal->multidir_output[$obj->propal_entity].'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['p.ref_client']['checked'])) {
			// Customer ref
			print '<td class="nowrap tdoverflowmax200">';
			print dol_escape_htmltag($obj->ref_client);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['pr.ref']['checked'])) {
			// Project ref
			print '<td class="nowraponall">';
			if ($obj->project_id > 0) {
				print $projectstatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['pr.title']['checked'])) {
			// Project label
			print '<td class="nowrap">';
			if ($obj->project_id > 0) {
				print dol_escape_htmltag($projectstatic->title);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Thirdparty
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax150">';
			print $companystatic->getNomUrl(1, 'customer', 0, 0, 1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Alias
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print '<td class="tdoverflowmax200">';
			print $obj->alias;
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
			print '<td class="nocellnopadd">';
			print $obj->zip;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// State
		if (!empty($arrayfields['state.nom']['checked'])) {
			print "<td>".$obj->state_name."</td>\n";
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
			if (!is_array($typenArray) || empty($typenArray)) {
				$typenArray = $formcompany->typent_array(1);
			}

			print '<td class="center">';
			if (!empty($obj->typent_code)) {
				print $typenArray[$obj->typent_code];
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date proposal
		if (!empty($arrayfields['p.date']['checked'])) {
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->dp), 'day');
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date end validity
		if (!empty($arrayfields['p.fin_validite']['checked'])) {
			if ($obj->dfv) {
				print '<td class="center">'.dol_print_date($db->jdate($obj->dfv), 'day');
				print '</td>';
			} else {
				print '<td>&nbsp;</td>';
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date delivery
		if (!empty($arrayfields['p.date_livraison']['checked'])) {
			if ($obj->ddelivery) {
				print '<td class="center">'.dol_print_date($db->jdate($obj->ddelivery), 'day');
				print '</td>';
			} else {
				print '<td>&nbsp;</td>';
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date Signature
		if (!empty($arrayfields['p.date_signature']['checked'])) {
			if ($obj->dsignature) {
				print '<td class="center">'.dol_print_date($db->jdate($obj->dsignature), 'day');
				print '</td>';
			} else {
				print '<td>&nbsp;</td>';
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Availability
		if (!empty($arrayfields['ava.rowid']['checked'])) {
			print '<td class="center">';
			$form->form_availability('', $obj->availability, 'none', 1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Shipping Method
		if (!empty($arrayfields['p.fk_shipping_method']['checked'])) {
			print '<td>';
			$form->formSelectShippingMethod('', $obj->fk_shipping_method, 'none', 1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Source - input reason
		if (!empty($arrayfields['p.fk_input_reason']['checked'])) {
			$labelInputReason = '';
			if ($obj->fk_input_reason > 0) {
				$labelInputReason = $form->cache_demand_reason[$obj->fk_input_reason]['label'];
			}
			print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($labelInputReason).'">';
			print dol_escape_htmltag($labelInputReason);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Payment terms
		if (!empty($arrayfields['p.fk_cond_reglement']['checked'])) {
			print '<td>';
			$form->form_conditions_reglement($_SERVER['PHP_SELF'], $obj->fk_cond_reglement, 'none', 0, '', 1, $obj->deposit_percent);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Payment mode
		if (!empty($arrayfields['p.fk_mode_reglement']['checked'])) {
			print '<td>';
			$form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount HT
		if (!empty($arrayfields['p.total_ht']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($obj->total_ht)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'p.total_ht';
			}
			if (empty($totalarray['val']['p.total_ht'])) {
				$totalarray['val']['p.total_ht'] = $obj->total_ht;
			} else {
				$totalarray['val']['p.total_ht'] += $obj->total_ht;
			}
		}
		// Amount VAT
		if (!empty($arrayfields['p.total_tva']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($obj->total_tva)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'p.total_tva';
			}
			if (empty($totalarray['val']['p.total_tva'])) {
				$totalarray['val']['p.total_tva'] = $obj->total_tva;
			} else {
				$totalarray['val']['p.total_tva'] += $obj->total_tva;
			}
		}
		// Amount TTC
		if (!empty($arrayfields['p.total_ttc']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'p.total_ttc';
			}
			if (empty($totalarray['val']['p.total_ttc'])) {
				$totalarray['val']['p.total_ttc'] = $obj->total_ttc;
			} else {
				$totalarray['val']['p.total_ttc'] += $obj->total_ttc;
			}
		}
		// Amount invoiced HT
		if (!empty($arrayfields['p.total_ht_invoiced']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($totalInvoicedHT)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'p.total_ht_invoiced';
			}
			if (empty($totalarray['val']['p.total_ht_invoiced'])) {
				$totalarray['val']['p.total_ht_invoiced'] = $totalInvoicedHT;
			} else {
				$totalarray['val']['p.total_ht_invoiced'] += $totalInvoicedHT;
			}
		}
		// Amount invoiced TTC
		if (!empty($arrayfields['p.total_invoiced']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($totalInvoicedTTC)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'p.total_invoiced';
			}
			if (empty($totalarray['val']['p.total_invoiced'])) {
				$totalarray['val']['p.total_invoiced'] = $totalInvoicedTTC;
			} else {
				$totalarray['val']['p.total_invoiced'] += $totalInvoicedTTC;
			}
		}
		// Currency
		if (!empty($arrayfields['p.multicurrency_code']['checked'])) {
			print '<td class="nowrap">'.$obj->multicurrency_code.' - '.$langs->trans('Currency'.$obj->multicurrency_code)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Currency rate
		if (!empty($arrayfields['p.multicurrency_tx']['checked'])) {
			print '<td class="nowrap">';
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount HT
		if (!empty($arrayfields['p.multicurrency_total_ht']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ht)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount VAT
		if (!empty($arrayfields['p.multicurrency_total_tva']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_tva)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount TTC
		if (!empty($arrayfields['p.multicurrency_total_ttc']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ttc)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount invoiced
		if (!empty($arrayfields['p.multicurrency_total_ht_invoiced']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($multicurrency_totalInvoicedHT)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount invoiced
		if (!empty($arrayfields['p.multicurrency_total_invoiced']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($multicurrency_totalInvoicedTTC)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		$userstatic->id = $obj->fk_user_author;
		$userstatic->login = $obj->login;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		$userstatic->email = $obj->user_email;
		$userstatic->status = $obj->user_statut;
		$userstatic->entity = $obj->user_entity;
		$userstatic->photo = $obj->photo;
		$userstatic->office_phone = $obj->office_phone;
		$userstatic->office_fax = $obj->office_fax;
		$userstatic->user_mobile = $obj->user_mobile;
		$userstatic->job = $obj->job;
		$userstatic->gender = $obj->gender;

		// Author
		if (!empty($arrayfields['u.login']['checked'])) {
			print '<td class="tdoverflowmax150">';
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1);
			}
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['sale_representative']['checked'])) {
			// Sales representatives
			print '<td class="tdoverflowmax125">';
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
					$userstatic = new User($db);
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

		// Total buying or cost price
		if (!empty($arrayfields['total_pa']['checked'])) {
			print '<td class="right nowrap">'.price($marginInfo['pa_total']).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Total margin
		if (!empty($arrayfields['total_margin']['checked'])) {
			print '<td class="right nowrap">'.price($marginInfo['total_margin']).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'total_margin';
			}
			$totalarray['val']['total_margin'] = $total_margin;
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
			}
			if (!$i) {
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
		if (!empty($arrayfields['p.datec']['checked'])) {
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date modification
		if (!empty($arrayfields['p.tms']['checked'])) {
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date cloture
		if (!empty($arrayfields['p.date_cloture']['checked'])) {
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_cloture), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Note public
		if (!empty($arrayfields['p.note_public']['checked'])) {
			print '<td class="sensiblehtmlcontent center">';
			print dolPrintHTML($obj->note_public);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Note private
		if (!empty($arrayfields['p.note_private']['checked'])) {
			print '<td class="sensiblehtmlcontent center">';
			print dolPrintHTML($obj->note_private);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['p.fk_statut']['checked'])) {
			print '<td class="nowrap center">'.$objectstatic->getLibStatut(5).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap" align="center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
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

if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_proposals', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();

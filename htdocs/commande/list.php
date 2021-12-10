<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016-2021  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Charlene Benke	        <charlie@patas-monkey.com>
 * Copyright (C) 2021	   	Anthony Berton			<anthony.berton@bb2a.fr>
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
 *	\file       htdocs/commande/list.php
 *	\ingroup    commande
 *	\brief      Page to list orders
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array("orders", 'sendings', 'deliveries', 'companies', 'compta', 'bills', 'stocks', 'products'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'orderlist';

$search_datecloture_start = GETPOST('search_datecloture_start', 'int');
if (empty($search_datecloture_start)) {
	$search_datecloture_start = dol_mktime(0, 0, 0, GETPOST('search_datecloture_startmonth', 'int'), GETPOST('search_datecloture_startday', 'int'), GETPOST('search_datecloture_startyear', 'int'));
}
$search_datecloture_end = GETPOST('search_datecloture_end', 'int');
if (empty($search_datecloture_end)) {
	$search_datecloture_end = dol_mktime(23, 59, 59, GETPOST('search_datecloture_endmonth', 'int'), GETPOST('search_datecloture_endday', 'int'), GETPOST('search_datecloture_endyear', 'int'));
}
$search_dateorder_start = dol_mktime(0, 0, 0, GETPOST('search_dateorder_start_month', 'int'), GETPOST('search_dateorder_start_day', 'int'), GETPOST('search_dateorder_start_year', 'int'));
$search_dateorder_end = dol_mktime(23, 59, 59, GETPOST('search_dateorder_end_month', 'int'), GETPOST('search_dateorder_end_day', 'int'), GETPOST('search_dateorder_end_year', 'int'));
$search_datedelivery_start = dol_mktime(0, 0, 0, GETPOST('search_datedelivery_start_month', 'int'), GETPOST('search_datedelivery_start_day', 'int'), GETPOST('search_datedelivery_start_year', 'int'));
$search_datedelivery_end = dol_mktime(23, 59, 59, GETPOST('search_datedelivery_end_month', 'int'), GETPOST('search_datedelivery_end_day', 'int'), GETPOST('search_datedelivery_end_year', 'int'));
$search_product_category = GETPOST('search_product_category', 'int');
$search_ref = GETPOST('search_ref', 'alpha') != '' ?GETPOST('search_ref', 'alpha') : GETPOST('sref', 'alpha');
$search_ref_customer = GETPOST('search_ref_customer', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_company_alias = GETPOST('search_company_alias', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state", 'alpha');
$search_country = GETPOST("search_country", 'int');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$socid = GETPOST('socid', 'int');
$search_user = GETPOST('search_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_total_ht = GETPOST('search_total_ht', 'alpha');
$search_total_vat = GETPOST('search_total_vat', 'alpha');
$search_total_ttc = GETPOST('search_total_ttc', 'alpha');
$search_warehouse = GETPOST('search_warehouse', 'int');
$search_multicurrency_code = GETPOST('search_multicurrency_code', 'alpha');
$search_multicurrency_tx = GETPOST('search_multicurrency_tx', 'alpha');
$search_multicurrency_montant_ht = GETPOST('search_multicurrency_montant_ht', 'alpha');
$search_multicurrency_montant_vat = GETPOST('search_multicurrency_montant_vat', 'alpha');
$search_multicurrency_montant_ttc = GETPOST('search_multicurrency_montant_ttc', 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_categ_cus = GETPOST("search_categ_cus", 'int');
$optioncss = GETPOST('optioncss', 'alpha');
$search_billed = GETPOSTISSET('search_billed') ? GETPOST('search_billed', 'int') : GETPOST('billed', 'int');
$search_status = GETPOST('search_status', 'int');
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_project = GETPOST('search_project', 'alpha');
$search_shippable = GETPOST('search_shippable', 'aZ09');
$search_fk_cond_reglement = GETPOST("search_fk_cond_reglement", 'int');
$search_fk_shipping_method = GETPOST("search_fk_shipping_method", 'int');
$search_fk_mode_reglement = GETPOST("search_fk_mode_reglement", 'int');
$search_fk_input_reason = GETPOST("search_fk_input_reason", 'int');

// Security check
$id = (GETPOST('orderid') ?GETPOST('orderid', 'int') : GETPOST('id', 'int'));
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'commande', $id, '');

$diroutputmassaction = $conf->commande->multidir_output[$conf->entity].'/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'c.ref';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

$show_shippable_command = GETPOST('show_shippable_command', 'aZ09');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Commande($db);
$hookmanager->initHooks(array('orderlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'c.ref'=>'Ref',
	'c.ref_client'=>'RefCustomerOrder',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	's.name_alias'=>"AliasNameShort",
	's.zip'=>"Zip",
	's.town'=>"Town",
	'c.note_public'=>'NotePublic',
);
if (empty($user->socid)) {
	$fieldstosearchall["c.note_private"] = "NotePrivate";
}

$checkedtypetiers = 0;
$arrayfields = array(
	'c.ref'=>array('label'=>"Ref", 'checked'=>1, 'position'=>5),
	'c.ref_client'=>array('label'=>"RefCustomerOrder", 'checked'=>-1, 'position'=>10),
	'p.ref'=>array('label'=>"ProjectRef", 'checked'=>-1, 'enabled'=>(empty($conf->projet->enabled) ? 0 : 1), 'position'=>20),
	'p.title'=>array('label'=>"ProjectLabel", 'checked'=>0, 'enabled'=>(empty($conf->projet->enabled) ? 0 : 1), 'position'=>25),
	's.nom'=>array('label'=>"ThirdParty", 'checked'=>1, 'position'=>30),
	's.name_alias'=>array('label'=>"AliasNameShort", 'checked'=>-1, 'position'=>31),
	's.town'=>array('label'=>"Town", 'checked'=>-1, 'position'=>35),
	's.zip'=>array('label'=>"Zip", 'checked'=>-1, 'position'=>40),
	'state.nom'=>array('label'=>"StateShort", 'checked'=>0, 'position'=>45),
	'country.code_iso'=>array('label'=>"Country", 'checked'=>0, 'position'=>50),
	'typent.code'=>array('label'=>"ThirdPartyType", 'checked'=>$checkedtypetiers, 'position'=>55),
	'c.date_commande'=>array('label'=>"OrderDateShort", 'checked'=>1, 'position'=>60),
	'c.date_delivery'=>array('label'=>"DateDeliveryPlanned", 'checked'=>1, 'enabled'=>empty($conf->global->ORDER_DISABLE_DELIVERY_DATE), 'position'=>65),
	'c.fk_shipping_method'=>array('label'=>"SendingMethod", 'checked'=>-1, 'position'=>66 , 'enabled'=>!empty($conf->expedition->enabled)),
	'c.fk_cond_reglement'=>array('label'=>"PaymentConditionsShort", 'checked'=>-1, 'position'=>67),
	'c.fk_mode_reglement'=>array('label'=>"PaymentMode", 'checked'=>-1, 'position'=>68),
	'c.fk_input_reason'=>array('label'=>"Channel", 'checked'=>-1, 'position'=>69),
	'c.total_ht'=>array('label'=>"AmountHT", 'checked'=>1, 'position'=>75),
	'c.total_vat'=>array('label'=>"AmountVAT", 'checked'=>0, 'position'=>80),
	'c.total_ttc'=>array('label'=>"AmountTTC", 'checked'=>0, 'position'=>85),
	'c.multicurrency_code'=>array('label'=>'Currency', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1), 'position'=>90),
	'c.multicurrency_tx'=>array('label'=>'CurrencyRate', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1), 'position'=>95),
	'c.multicurrency_total_ht'=>array('label'=>'MulticurrencyAmountHT', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1), 'position'=>100),
	'c.multicurrency_total_vat'=>array('label'=>'MulticurrencyAmountVAT', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1), 'position'=>105),
	'c.multicurrency_total_ttc'=>array('label'=>'MulticurrencyAmountTTC', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1), 'position'=>110),
	'u.login'=>array('label'=>"Author", 'checked'=>1, 'position'=>115),
	'c.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>120),
	'c.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>125),
	'c.date_cloture'=>array('label'=>"DateClosing", 'checked'=>0, 'position'=>130),
	'c.note_public'=>array('label'=>'NotePublic', 'checked'=>0, 'enabled'=>(empty($conf->global->MAIN_LIST_ALLOW_PUBLIC_NOTES)), 'position'=>135),
	'c.note_private'=>array('label'=>'NotePrivate', 'checked'=>0, 'enabled'=>(empty($conf->global->MAIN_LIST_ALLOW_PRIVATE_NOTES)), 'position'=>140),
	'shippable'=>array('label'=>"Shippable", 'checked'=>1,'enabled'=>(!empty($conf->expedition->enabled)), 'position'=>990),
	'c.facture'=>array('label'=>"Billed", 'checked'=>1, 'enabled'=>(empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)), 'position'=>995),
	'c.fk_statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000)
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') {
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
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_categ = '';
		$search_user = '';
		$search_sale = '';
		$search_product_category = '';
		$search_ref = '';
		$search_ref_customer = '';
		$search_company = '';
		$search_company_alias = '';
		$search_town = '';
		$search_zip = "";
		$search_state = "";
		$search_type = '';
		$search_country = '';
		$search_type_thirdparty = '';
		$search_total_ht = '';
		$search_total_vat = '';
		$search_total_ttc = '';
		$search_warehouse = '';
		$search_multicurrency_code = '';
		$search_multicurrency_tx = '';
		$search_multicurrency_montant_ht = '';
		$search_multicurrency_montant_vat = '';
		$search_multicurrency_montant_ttc = '';
		$search_login = '';
		$search_dateorder_start = '';
		$search_dateorder_end = '';
		$search_datedelivery_start = '';
		$search_datedelivery_end = '';
		$search_project_ref = '';
		$search_project = '';
		$search_status = '';
		$search_billed = '';
		$toselect = '';
		$search_array_options = array();
		$search_categ_cus = 0;
		$search_datecloture_start = '';
		$search_datecloture_end = '';
		$search_fk_cond_reglement = '';
		$search_fk_shipping_method = '';
		$search_fk_mode_reglement = '';
		$search_fk_input_reason = '';
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
	 || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Commande';
	$objectlabel = 'Orders';
	$permissiontoread = $user->rights->commande->lire;
	$permissiontoadd = $user->rights->commande->creer;
	$permissiontodelete = $user->rights->commande->supprimer;
	if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
		$permissiontovalidate = $user->rights->commande->order_advance->validate;
		$permissiontoclose = $user->rights->commande->order_advance->close;
		$permissiontocancel = $user->rights->commande->order_advance->annuler;
		$permissiontosendbymail = $user->rights->commande->order_advance->send;
	} else {
		$permissiontovalidate = $user->rights->commande->creer;
		$permissiontoclose = $user->rights->commande->creer;
		$permissiontocancel = $user->rights->commande->creer;
		$permissiontosendbymail = $user->rights->commande->creer;
	}
	$uploaddir = $conf->commande->multidir_output[$conf->entity];
	$triggersendname = 'ORDER_SENTBYMAIL';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}
if ($action == 'validate' && $permissiontoadd) {
	if (GETPOST('confirm') == 'yes') {
		$objecttmp = new $objectclass($db);
		$db->begin();
		$error = 0;
		foreach ($toselect as $checked) {
			if ($objecttmp->fetch($checked)) {
				if ($objecttmp->statut == 0) {
					if (!empty($objecttmp->fk_warehouse)) {
						$idwarehouse = $objecttmp->fk_warehouse;
					} else {
						$idwarehouse = 0;
					}
					if ($objecttmp->valid($user, $idwarehouse)) {
						setEventMessage($objecttmp->ref." ".$langs->trans('PassedInOpenStatus'), 'mesgs');
					} else {
						setEventMessage($langs->trans('CantBeValidated'), 'errors');
						$error++;
					}
				} else {
					setEventMessage($objecttmp->ref." ".$langs->trans('IsNotADraft'), 'errors');
					$error++;
				}
			} else {
				dol_print_error($db);
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
if ($action == 'shipped' && $permissiontoadd) {
	if (GETPOST('confirm') == 'yes') {
		$objecttmp = new $objectclass($db);
		$db->begin();
		$error = 0;
		foreach ($toselect as $checked) {
			if ($objecttmp->fetch($checked)) {
				if ($objecttmp->statut == 1) {
					if ($objecttmp->cloture($user)) {
						setEventMessage($objecttmp->ref." ".$langs->trans('PassedInOpenStatus'), 'mesgs');
					} else {
						setEventMessage($langs->trans('CantBeValidated'), 'errors');
						$error++;
					}
				} else {
					setEventMessage($objecttmp->ref." ".$langs->trans('IsNotADraft'), 'errors');
					$error++;
				}
			} else {
				dol_print_error($db);
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
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
		}
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
$companystatic = new Societe($db);
$formcompany = new FormCompany($db);
$projectstatic = new Project($db);

$title = $langs->trans("Orders");
$help_url = "EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
// llxHeader('',$title,$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0 || $search_user > 0) {
	$sql = 'SELECT DISTINCT';
}
$sql .= ' s.rowid as socid, s.nom as name, s.name_alias as alias, s.email, s.phone, s.fax, s.address, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " country.code as country_code,";
$sql .= ' c.rowid, c.ref, c.total_ht, c.total_tva, c.total_ttc, c.ref_client, c.fk_user_author,';
$sql .= ' c.fk_multicurrency, c.multicurrency_code, c.multicurrency_tx, c.multicurrency_total_ht, c.multicurrency_total_tva as multicurrency_total_vat, c.multicurrency_total_ttc,';
$sql .= ' c.date_valid, c.date_commande, c.note_public, c.note_private, c.date_livraison as date_delivery, c.fk_statut, c.facture as billed,';
$sql .= ' c.date_creation as date_creation, c.tms as date_update, c.date_cloture as date_cloture,';
$sql .= ' p.rowid as project_id, p.ref as project_ref, p.title as project_label,';
$sql .= ' u.login, u.lastname, u.firstname, u.email as user_email, u.statut as user_statut, u.entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender,';
$sql .= ' c.fk_cond_reglement,c.fk_mode_reglement,c.fk_shipping_method,';
$sql .= ' c.fk_input_reason';
if (($search_categ_cus > 0) || ($search_categ_cus == -2)) {
	$sql .= ", cc.fk_categorie, cc.fk_soc";
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
if (($search_categ_cus > 0) || ($search_categ_cus == -2)) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cc ON s.rowid = cc.fk_soc"; // We'll need this table joined to the select in order to filter by categ
}
$sql .= ', '.MAIN_DB_PREFIX.'commande as c';
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields as ef on (c.rowid = ef.fk_object)";
}
if ($sall || $search_product_category > 0) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet as pd ON c.rowid=pd.fk_commande';
}
if ($search_product_category > 0) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = c.fk_projet";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON c.fk_user_author = u.rowid';

// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->rights->societe->client->voir && !$socid)) {
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

$sql .= ' WHERE c.fk_soc = s.rowid';
$sql .= ' AND c.entity IN ('.getEntity('commande').')';
if ($search_product_category > 0) {
	$sql .= " AND cp.fk_categorie = ".((int) $search_product_category);
}
if ($socid > 0) {
	$sql .= ' AND s.rowid = '.((int) $socid);
}
if (!$user->rights->societe->client->voir && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($search_ref) {
	$sql .= natural_search('c.ref', $search_ref);
}
if ($search_ref_customer) {
	$sql .= natural_search('c.ref_client', $search_ref_customer);
}
if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if ($search_billed != '' && $search_billed >= 0) {
	$sql .= ' AND c.facture = '.((int) $search_billed);
}
if ($search_status <> '') {
	if ($search_status < 4 && $search_status > -3) {
		if ($search_status == 1 && empty($conf->expedition->enabled)) {
			$sql .= ' AND c.fk_statut IN (1,2)'; // If module expedition disabled, we include order with status 'sending in process' into 'validated'
		} else {
			$sql .= ' AND c.fk_statut = '.$search_status; // brouillon, validee, en cours, annulee
		}
	}
	if ($search_status == 4) {
		$sql .= ' AND c.facture = 1'; // invoice created
	}
	if ($search_status == -2) {	// To process
		//$sql.= ' AND c.fk_statut IN (1,2,3) AND c.facture = 0';
		$sql .= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))"; // If status is 2 and facture=1, it must be selected
	}
	if ($search_status == -3) {	// To bill
		//$sql.= ' AND c.fk_statut in (1,2,3)';
		//$sql.= ' AND c.facture = 0'; // invoice not created
		$sql .= ' AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))'; // validated, in process or closed but not billed
	}
}

if ($search_datecloture_start) {
	$sql .= " AND c.date_cloture >= '".$db->idate($search_datecloture_start)."'";
}
if ($search_datecloture_end) {
	$sql .= " AND c.date_cloture <= '".$db->idate($search_datecloture_end)."'";
}
if ($search_dateorder_start) {
	$sql .= " AND c.date_commande >= '".$db->idate($search_dateorder_start)."'";
}
if ($search_dateorder_end) {
	$sql .= " AND c.date_commande <= '".$db->idate($search_dateorder_end)."'";
}
if ($search_datedelivery_start) {
	$sql .= " AND c.date_livraison >= '".$db->idate($search_datedelivery_start)."'";
}
if ($search_datedelivery_end) {
	$sql .= " AND c.date_livraison <= '".$db->idate($search_datedelivery_end)."'";
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
if ($search_type_thirdparty && $search_type_thirdparty != '-1') {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($search_type_thirdparty).')';
}
if ($search_company) {
	$sql .= natural_search('s.nom', $search_company);
}
if ($search_company_alias) {
	$sql .= natural_search('s.name_alias', $search_company_alias);
}
if ($search_sale > 0) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $search_sale);
}
if ($search_user > 0) {
	$sql .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='commande' AND tc.source='internal' AND ec.element_id = c.rowid AND ec.fk_socpeople = ".((int) $search_user);
}
if ($search_total_ht != '') {
	$sql .= natural_search('c.total_ht', $search_total_ht, 1);
}
if ($search_total_vat != '') {
	$sql .= natural_search('c.total_tva', $search_total_vat, 1);
}
if ($search_total_ttc != '') {
	$sql .= natural_search('c.total_ttc', $search_total_ttc, 1);
}
if ($search_warehouse != '' && $search_warehouse > 0) {
	$sql .= natural_search('c.fk_warehouse', $search_warehouse, 1);
}
if ($search_multicurrency_code != '') {
	$sql .= ' AND c.multicurrency_code = "'.$db->escape($search_multicurrency_code).'"';
}
if ($search_multicurrency_tx != '') {
	$sql .= natural_search('c.multicurrency_tx', $search_multicurrency_tx, 1);
}
if ($search_multicurrency_montant_ht != '') {
	$sql .= natural_search('c.multicurrency_total_ht', $search_multicurrency_montant_ht, 1);
}
if ($search_multicurrency_montant_vat != '') {
	$sql .= natural_search('c.multicurrency_total_tva', $search_multicurrency_montant_vat, 1);
}
if ($search_multicurrency_montant_ttc != '') {
	$sql .= natural_search('c.multicurrency_total_ttc', $search_multicurrency_montant_ttc, 1);
}
if ($search_login) {
	$sql .= natural_search(array("u.login", "u.firstname", "u.lastname"), $search_login);
}
if ($search_project_ref != '') {
	$sql .= natural_search("p.ref", $search_project_ref);
}
if ($search_project != '') {
	$sql .= natural_search("p.title", $search_project);
}
if ($search_categ_cus > 0) {
	$sql .= " AND cc.fk_categorie = ".((int) $search_categ_cus);
}
if ($search_categ_cus == -2) {
	$sql .= " AND cc.fk_categorie IS NULL";
}
if ($search_fk_cond_reglement > 0) {
	$sql .= " AND c.fk_cond_reglement = ".((int) $search_fk_cond_reglement);
}
if ($search_fk_shipping_method > 0) {
	$sql .= " AND c.fk_shipping_method = ".((int) $search_fk_shipping_method);
}
if ($search_fk_mode_reglement > 0) {
	$sql .= " AND c.fk_mode_reglement = ".((int) $search_fk_mode_reglement);
}
if ($search_fk_input_reason > 0) {
	$sql .= " AND c.fk_input_reason = ".((int) $search_fk_input_reason);
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Add HAVING from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= !empty($hookmanager->resPrint) ? (' HAVING 1=1 ' . $hookmanager->resPrint) : '';

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);
//print $sql;

$resql = $db->query($sql);
if ($resql) {
	if ($socid > 0) {
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfOrders').' - '.$soc->name;
		if (empty($search_company)) {
			$search_company = $soc->name;
		}
	} else {
		$title = $langs->trans('ListOfOrders');
	}
	if (strval($search_status) == '0') {
		$title .= ' - '.$langs->trans('StatusOrderDraftShort');
	}
	if ($search_status == 1) {
		$title .= ' - '.$langs->trans('StatusOrderValidatedShort');
	}
	if ($search_status == 2) {
		$title .= ' - '.$langs->trans('StatusOrderSentShort');
	}
	if ($search_status == 3) {
		$title .= ' - '.$langs->trans('StatusOrderToBillShort');
	}
	if ($search_status == 4) {
		$title .= ' - '.$langs->trans('StatusOrderProcessedShort');
	}
	if ($search_status == -1) {
		$title .= ' - '.$langs->trans('StatusOrderCanceledShort');
	}
	if ($search_status == -2) {
		$title .= ' - '.$langs->trans('StatusOrderToProcessShort');
	}
	if ($search_status == -3) {
		$title .= ' - '.$langs->trans('StatusOrderValidated').', '.(empty($conf->expedition->enabled) ? '' : $langs->trans("StatusOrderSent").', ').$langs->trans('StatusOrderToBill');
	}

	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall) {
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".DOL_URL_ROOT.'/commande/card.php?id='.$id);
		exit;
	}

	llxHeader('', $title, $help_url);

	$param = '';

	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.urlencode($limit);
	}
	if ($sall) {
		$param .= '&sall='.urlencode($sall);
	}
	if ($socid > 0) {
		$param .= '&socid='.urlencode($socid);
	}
	if ($search_status != '') {
		$param .= '&search_status='.urlencode($search_status);
	}
	if ($search_datecloture_start) {
		$param .= '&search_datecloture_startday='.dol_print_date($search_datecloture_start, '%d').'&search_datecloture_startmonth='.dol_print_date($search_datecloture_start, '%m').'&search_datecloture_startyear='.dol_print_date($search_datecloture_start, '%Y');
	}
	if ($search_datecloture_end) {
		$param .= '&search_datecloture_endday='.dol_print_date($search_datecloture_end, '%d').'&search_datecloture_endmonth='.dol_print_date($search_datecloture_end, '%m').'&search_datecloture_endyear='.dol_print_date($search_datecloture_end, '%Y');
	}
	if ($search_dateorder_start) {
		$param .= '&search_dateorder_start_day='.dol_print_date($search_dateorder_start, '%d').'&search_dateorder_start_month='.dol_print_date($search_dateorder_start, '%m').'&search_dateorder_start_year='.dol_print_date($search_dateorder_start, '%Y');
	}
	if ($search_dateorder_end) {
		$param .= '&search_dateorder_end_day='.dol_print_date($search_dateorder_end, '%d').'&search_dateorder_end_month='.dol_print_date($search_dateorder_end, '%m').'&search_dateorder_end_year='.dol_print_date($search_dateorder_end, '%Y');
	}
	if ($search_datedelivery_start) {
		$param .= '&search_datedelivery_start_day='.dol_print_date($search_datedelivery_start, '%d').'&search_datedelivery_start_month='.dol_print_date($search_datedelivery_start, '%m').'&search_datedelivery_start_year='.dol_print_date($search_datedelivery_start, '%Y');
	}
	if ($search_datedelivery_end) {
		$param .= '&search_datedelivery_end_day='.dol_print_date($search_datedelivery_end, '%d').'&search_datedelivery_end_month='.dol_print_date($search_datedelivery_end, '%m').'&search_datedelivery_end_year='.dol_print_date($search_datedelivery_end, '%Y');
	}
	if ($search_ref) {
		$param .= '&search_ref='.urlencode($search_ref);
	}
	if ($search_company) {
		$param .= '&search_company='.urlencode($search_company);
	}
	if ($search_company_alias) {
		$param .= '&search_company_alias='.urlencode($search_company_alias);
	}
	if ($search_ref_customer) {
		$param .= '&search_ref_customer='.urlencode($search_ref_customer);
	}
	if ($search_user > 0) {
		$param .= '&search_user='.urlencode($search_user);
	}
	if ($search_sale > 0) {
		$param .= '&search_sale='.urlencode($search_sale);
	}
	if ($search_total_ht != '') {
		$param .= '&search_total_ht='.urlencode($search_total_ht);
	}
	if ($search_total_vat != '') {
		$param .= '&search_total_vat='.urlencode($search_total_vat);
	}
	if ($search_total_ttc != '') {
		$param .= '&search_total_ttc='.urlencode($search_total_ttc);
	}
	if ($search_warehouse != '') {
		$param .= '&search_warehouse='.urlencode($search_warehouse);
	}
	if ($search_login) {
		$param .= '&search_login='.urlencode($search_login);
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
	if ($search_project_ref >= 0) {
		$param .= "&search_project_ref=".urlencode($search_project_ref);
	}
	if ($search_town != '') {
		$param .= '&search_town='.urlencode($search_town);
	}
	if ($search_zip != '') {
		$param .= '&search_zip='.urlencode($search_zip);
	}
	if ($search_state != '') {
		$param .= '&search_state='.urlencode($search_state);
	}
	if ($search_country != '') {
		$param .= '&search_country='.urlencode($search_country);
	}
	if ($search_type_thirdparty && $search_type_thirdparty != '-1') {
		$param .= '&search_type_thirdparty='.urlencode($search_type_thirdparty);
	}
	if ($search_product_category != '') {
		$param .= '&search_product_category='.urlencode($search_product_category);
	}
	if (($search_categ_cus > 0) || ($search_categ_cus == -2)) {
		$param .= '&search_categ_cus='.urlencode($search_categ_cus);
	}
	if ($show_files) {
		$param .= '&show_files='.urlencode($show_files);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}
	if ($search_billed != '') {
		$param .= '&search_billed='.urlencode($search_billed);
	}
	if ($search_fk_cond_reglement > 0) {
		$param .= '&search_fk_cond_reglement='.urlencode($search_fk_cond_reglement);
	}
	if ($search_fk_shipping_method > 0) {
		$param .= '&search_fk_shipping_method='.urlencode($search_fk_shipping_method);
	}
	if ($search_fk_mode_reglement > 0) {
		$param .= '&search_fk_mode_reglement='.urlencode($search_fk_mode_reglement);
	}
	if ($search_fk_input_reason > 0) {
		$param .= '&search_fk_input_reason='.urlencode($search_fk_input_reason);
	}

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// Add $param from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
	$param .= $hookmanager->resPrint;

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
		'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	);
	if ($permissiontovalidate) {
		$arrayofmassactions['prevalidate'] = img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate");
	}
	if ($permissiontosendbymail) {
		$arrayofmassactions['presend'] = img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail");
	}
	if ($permissiontoclose) {
		$arrayofmassactions['preshipped'] = img_picto('', 'dollyrevert', 'class="pictofixedwidth"').$langs->trans("ClassifyShipped");
	}
	if ($permissiontocancel) {
		$arrayofmassactions['cancelorders'] = img_picto('', 'close_title', 'class="pictofixedwidth"').$langs->trans("Cancel");
	}
	if ($user->rights->facture->creer) {
		$arrayofmassactions['createbills'] = img_picto('', 'bill', 'class="pictofixedwidth"').$langs->trans("CreateInvoiceForThisCustomer");
	}
	if ($permissiontoclose) {
		$arrayofmassactions['setbilled'] = img_picto('', 'bill', 'class="pictofixedwidth"').$langs->trans("ClassifyBilled");
	}
	if ($permissiontodelete) {
		$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	}
	if (in_array($massaction, array('presend', 'predelete', 'createbills'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$url = DOL_URL_ROOT.'/commande/card.php?action=create';
	if (!empty($socid)) {
		$url .= '&socid='.$socid;
	}
	$newcardbutton = dolGetButtonTitle($langs->trans('NewOrder'), '', 'fa fa-plus-circle', $url, '', $contextpage == 'orderlist' && $permissiontoadd);

	// Lines of title fields
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
	print '<input type="hidden" name="search_status" value="'.$search_status.'">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'order', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "SendOrderRef";
	$modelmail = "order_send";
	$objecttmp = new Commande($db);
	$trackid = 'ord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($massaction == 'prevalidate') {
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassValidation"), $langs->trans("ConfirmMassValidationQuestion"), "validate", null, '', 0, 200, 500, 1);
	}
	if ($massaction == 'preshipped') {
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("CloseOrder"), $langs->trans("ConfirmCloseOrder"), "shipped", null, '', 0, 200, 500, 1);
	}

	if ($massaction == 'createbills') {
		print '<input type="hidden" name="massaction" value="confirm_createbills">';

		print '<table class="noborder" width="100%" >';
		print '<tr>';
		print '<td class="titlefield">';
		print $langs->trans('DateInvoice');
		print '</td>';
		print '<td>';
		print $form->selectDate('', '', '', '', '', '', 1, 1);
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td>';
		print $langs->trans('CreateOneBillByThird');
		print '</td>';
		print '<td>';
		print $form->selectyesno('createbills_onebythird', '', 1);
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td>';
		print $langs->trans('ValidateInvoices');
		print '</td>';
		print '<td>';
		if (!empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_BILL)) {
			print $form->selectyesno('validate_invoices', 0, 1, 1);
			print ' ('.$langs->trans("AutoValidationNotPossibleWhenStockIsDecreasedOnInvoiceValidation").')';
		} else {
			print $form->selectyesno('validate_invoices', 0, 1);
		}
		if (!empty($conf->workflow->enabled) && !empty($conf->global->WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER)) {
			print ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("IfValidateInvoiceIsNoOrderStayUnbilled").'</span>';
		} else {
			print ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("OptionToSetOrderBilledNotEnabled").'</span>';
		}
		print '</td>';
		print '</tr>';
		print '</table>';

		print '<br>';
		print '<div class="center">';
		print '<input type="submit" class="button" id="createbills" name="createbills" value="'.$langs->trans('CreateInvoiceForThisCustomer').'">  ';
		print '<input type="submit" class="button button-cancel" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
		print '<br>';
	}

	if ($sall) {
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';

	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid) {
		$langs->load("commercial");
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
		$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth250');
		$moreforfilter .= '</div>';
	}
	// If the user can view other users
	if ($user->rights->user->user->lire) {
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('LinkedToSpecificUsers');
		$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_user, 'search_user', $tmptitle, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth250');
		$moreforfilter .= '</div>';
	}
	// If the user can view prospects other than his'
	if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire && ($user->rights->produit->lire || $user->rights->service->lire)) {
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('IncludingProductWithTag');
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
		$moreforfilter .= '</div>';
	}
	if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('CustomersProspectsCategoriesShort');
		$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$formother->select_categories('customer', $search_categ_cus, 'search_categ_cus', 1, $tmptitle);
		$moreforfilter .= '</div>';
	}
	if (!empty($conf->stock->enabled) && !empty($conf->global->WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER)) {
		require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
		$formproduct = new FormProduct($db);
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('Warehouse');
		$moreforfilter .= img_picto($tmptitle, 'stock', 'class="pictofixedwidth"').$formproduct->selectWarehouses($search_warehouse, 'search_warehouse', '', 1, 0, 0, $tmptitle);
		$moreforfilter .= '</div>';
	}
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
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
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	if (GETPOST('autoselectall', 'int')) {
		$selectedfields .= '<script>';
		$selectedfields .= '   $(document).ready(function() {';
		$selectedfields .= '        console.log("Autoclick on checkforselects");';
		$selectedfields .= '   		$("#checkforselects").click();';
		$selectedfields .= '        $("#massaction").val("createbills").change();';
		$selectedfields .= '   });';
		$selectedfields .= '</script>';
	}

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre_filter">';
	// Ref
	if (!empty($arrayfields['c.ref']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	// Ref customer
	if (!empty($arrayfields['c.ref_client']['checked'])) {
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="6" name="search_ref_customer" value="'.dol_escape_htmltag($search_ref_customer).'">';
		print '</td>';
	}
	// Project ref
	if (!empty($arrayfields['p.ref']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_project_ref" value="'.dol_escape_htmltag($search_project_ref).'"></td>';
	}
	// Project title
	if (!empty($arrayfields['p.title']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_project" value="'.dol_escape_htmltag($search_project).'"></td>';
	}
	// Thirpdarty
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td class="liste_titre" align="left">';
		print '<input class="flat maxwidth100" type="text" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
		print '</td>';
	}
	// Alias
	if (!empty($arrayfields['s.name_alias']['checked'])) {
		print '<td class="liste_titre" align="left">';
		print '<input class="flat maxwidth100" type="text" name="search_company_alias" value="'.dol_escape_htmltag($search_company_alias).'">';
		print '</td>';
	}
	// Town
	if (!empty($arrayfields['s.town']['checked'])) {
		print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
	}
	// Zip
	if (!empty($arrayfields['s.zip']['checked'])) {
		print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
	}
	// State
	if (!empty($arrayfields['state.nom']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
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
		print '<td class="liste_titre maxwidthonsmartphone" align="center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), '', 1);
		print '</td>';
	}
	// Date order
	if (!empty($arrayfields['c.date_commande']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_dateorder_start ? $search_dateorder_start : -1, 'search_dateorder_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_dateorder_end ? $search_dateorder_end : -1, 'search_dateorder_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	if (!empty($arrayfields['c.date_delivery']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_datedelivery_start ? $search_datedelivery_start : -1, 'search_datedelivery_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_datedelivery_end ? $search_datedelivery_end : -1, 'search_datedelivery_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	// Shipping Method
	if (!empty($arrayfields['c.fk_shipping_method']['checked'])) {
		print '<td class="liste_titre">';
		$form->selectShippingMethod($search_fk_shipping_method, 'search_fk_shipping_method', '', 1, '', 1);
		print '</td>';
	}
	// Payment term
	if (!empty($arrayfields['c.fk_cond_reglement']['checked'])) {
		print '<td class="liste_titre">';
		$form->select_conditions_paiements($search_fk_cond_reglement, 'search_fk_cond_reglement', -1, 1, 1);
		print '</td>';
	}
	// Payment mode
	if (!empty($arrayfields['c.fk_mode_reglement']['checked'])) {
		print '<td class="liste_titre">';
		$form->select_types_paiements($search_fk_mode_reglement, 'search_fk_mode_reglement', '', 0, 1, 1, 0, -1);
		print '</td>';
	}
	// Channel
	if (!empty($arrayfields['c.fk_input_reason']['checked'])) {
		print '<td class="liste_titre">';
		$form->selectInputReason($search_fk_input_reason, 'search_fk_input_reason', '', 1, '', 1);
		print '</td>';
	}
	if (!empty($arrayfields['c.total_ht']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_total_ht" value="'.dol_escape_htmltag($search_total_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.total_vat']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_total_vat" value="'.dol_escape_htmltag($search_total_vat).'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.total_ttc']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_total_ttc" value="'.$search_total_ttc.'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.multicurrency_code']['checked'])) {
		// Currency
		print '<td class="liste_titre">';
		print $form->selectMultiCurrency($search_multicurrency_code, 'search_multicurrency_code', 1);
		print '</td>';
	}
	if (!empty($arrayfields['c.multicurrency_tx']['checked'])) {
		// Currency rate
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_tx" value="'.dol_escape_htmltag($search_multicurrency_tx).'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.multicurrency_total_ht']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ht" value="'.dol_escape_htmltag($search_multicurrency_montant_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.multicurrency_total_vat']['checked'])) {
		// Amount VAT
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_vat" value="'.dol_escape_htmltag($search_multicurrency_montant_vat).'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.multicurrency_total_ttc']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ttc" value="'.dol_escape_htmltag($search_multicurrency_montant_ttc).'">';
		print '</td>';
	}
	if (!empty($arrayfields['u.login']['checked'])) {
		// Author
		print '<td class="liste_titre" align="center">';
		print '<input class="flat" size="4" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['c.datec']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['c.tms']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date cloture
	if (!empty($arrayfields['c.date_cloture']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_datecloture_start ? $search_datecloture_start : -1, 'search_datecloture_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_datecloture_end ? $search_datecloture_end : -1, 'search_datecloture_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	// Note public
	if (!empty($arrayfields['c.note_public']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Note private
	if (!empty($arrayfields['c.note_private']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Shippable
	if (!empty($arrayfields['shippable']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone" align="center">';
		//print $form->selectyesno('search_shippable', $search_shippable, 1, 0, 1, 1);
		if (!empty($conf->global->ORDER_SHIPABLE_STATUS_DISABLED_BY_DEFAULT)) {
			print '<input type="checkbox" name="show_shippable_command" value="1"'.($show_shippable_command ? ' checked' : '').'>';
			print $langs->trans('ShowShippableStatus');
		} else {
			$show_shippable_command = 1;
		}
		print '</td>';
	}
	// Status billed
	if (!empty($arrayfields['c.facture']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone" align="center">';
		print $form->selectyesno('search_billed', $search_billed, 1, 0, 1, 1);
		print '</td>';
	}
	// Status
	if (!empty($arrayfields['c.fk_statut']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone center">';
		$liststatus = array(
			Commande::STATUS_DRAFT=>$langs->trans("StatusOrderDraftShort"),
			Commande::STATUS_VALIDATED=>$langs->trans("StatusOrderValidated"),
			Commande::STATUS_SHIPMENTONPROCESS=>$langs->trans("StatusOrderSentShort"),
			Commande::STATUS_CLOSED=>$langs->trans("StatusOrderDelivered"),
			-3=>$langs->trans("StatusOrderValidatedShort").'+'.$langs->trans("StatusOrderSentShort").'+'.$langs->trans("StatusOrderDelivered"),
			Commande::STATUS_CANCELED=>$langs->trans("StatusOrderCanceledShort")
		);
		print $form->selectarray('search_status', $liststatus, $search_status, -4, 0, 0, '', 0, 0, 0, '', 'maxwidth100', 1);
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';
	if (!empty($arrayfields['c.ref']['checked'])) {
		print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER["PHP_SELF"], 'c.ref', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.ref_client']['checked'])) {
		print_liste_field_titre($arrayfields['c.ref_client']['label'], $_SERVER["PHP_SELF"], 'c.ref_client', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.ref']['checked'])) {
		print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.title']['checked'])) {
		print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER["PHP_SELF"], "p.title", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['s.nom']['checked'])) {
		print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['s.name_alias']['checked'])) {
		print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], 's.name_alias', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['s.town']['checked'])) {
		print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['s.zip']['checked'])) {
		print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['state.nom']['checked'])) {
		print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['country.code_iso']['checked'])) {
		print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['typent.code']['checked'])) {
		print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['c.date_commande']['checked'])) {
		print_liste_field_titre($arrayfields['c.date_commande']['label'], $_SERVER["PHP_SELF"], 'c.date_commande', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['c.date_delivery']['checked'])) {
		print_liste_field_titre($arrayfields['c.date_delivery']['label'], $_SERVER["PHP_SELF"], 'c.date_livraison', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['c.fk_shipping_method']['checked'])) {
		print_liste_field_titre($arrayfields['c.fk_shipping_method']['label'], $_SERVER["PHP_SELF"], "c.fk_shipping_method", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.fk_cond_reglement']['checked'])) {
		print_liste_field_titre($arrayfields['c.fk_cond_reglement']['label'], $_SERVER["PHP_SELF"], "c.fk_cond_reglement", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.fk_mode_reglement']['checked'])) {
		print_liste_field_titre($arrayfields['c.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "c.fk_mode_reglement", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.fk_input_reason']['checked'])) {
		print_liste_field_titre($arrayfields['c.fk_input_reason']['label'], $_SERVER["PHP_SELF"], "c.fk_input_reason", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.total_ht']['checked'])) {
		print_liste_field_titre($arrayfields['c.total_ht']['label'], $_SERVER["PHP_SELF"], 'c.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['c.total_vat']['checked'])) {
		print_liste_field_titre($arrayfields['c.total_vat']['label'], $_SERVER["PHP_SELF"], 'c.total_tva', '', $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['c.total_ttc']['checked'])) {
		print_liste_field_titre($arrayfields['c.total_ttc']['label'], $_SERVER["PHP_SELF"], 'c.total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['c.multicurrency_code']['checked'])) {
		print_liste_field_titre($arrayfields['c.multicurrency_code']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_code', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.multicurrency_tx']['checked'])) {
		print_liste_field_titre($arrayfields['c.multicurrency_tx']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_tx', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.multicurrency_total_ht']['checked'])) {
		print_liste_field_titre($arrayfields['c.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.multicurrency_total_vat']['checked'])) {
		print_liste_field_titre($arrayfields['c.multicurrency_total_vat']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_total_tva', '', $param, 'class="right"', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.multicurrency_total_ttc']['checked'])) {
		print_liste_field_titre($arrayfields['c.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['u.login']['checked'])) {
		print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, 'align="center"', $sortfield, $sortorder);
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['c.datec']['checked'])) {
		print_liste_field_titre($arrayfields['c.datec']['label'], $_SERVER["PHP_SELF"], "c.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['c.tms']['checked'])) {
		print_liste_field_titre($arrayfields['c.tms']['label'], $_SERVER["PHP_SELF"], "c.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['c.date_cloture']['checked'])) {
		print_liste_field_titre($arrayfields['c.date_cloture']['label'], $_SERVER["PHP_SELF"], "c.date_cloture", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['c.note_public']['checked'])) {
		print_liste_field_titre($arrayfields['c.note_public']['label'], $_SERVER["PHP_SELF"], "c.note_public", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['c.note_private']['checked'])) {
		print_liste_field_titre($arrayfields['c.note_private']['label'], $_SERVER["PHP_SELF"], "c.note_private", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['shippable']['checked'])) {
		print_liste_field_titre($arrayfields['shippable']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['c.facture']['checked'])) {
		print_liste_field_titre($arrayfields['c.facture']['label'], $_SERVER["PHP_SELF"], 'c.facture', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['c.fk_statut']['checked'])) {
		print_liste_field_titre($arrayfields['c.fk_statut']['label'], $_SERVER["PHP_SELF"], "c.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'maxwidthsearch center ');
	print '</tr>'."\n";

	$total = 0;
	$subtotal = 0;
	$productstat_cache = array();
	$productstat_cachevirtual = array();
	$getNomUrl_cache = array();

	$generic_commande = new Commande($db);
	$generic_product = new Product($db);
	$userstatic = new User($db);
	$i = 0;
	$totalarray = array('nbfield' => 0, 'val' => array(), 'pos' => array());
	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		$notshippable = 0;
		$warning = 0;
		$text_info = '';
		$text_warning = '';
		$nbprod = 0;

		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->name;
		$companystatic->name_alias = $obj->alias;
		$companystatic->client = $obj->client;
		$companystatic->code_client = $obj->code_client;
		$companystatic->email = $obj->email;
		$companystatic->phone = $obj->phone;
		$companystatic->address = $obj->address;
		$companystatic->zip = $obj->zip;
		$companystatic->town = $obj->town;
		$companystatic->country_code = $obj->country_code;
		if (!isset($getNomUrl_cache[$obj->socid])) {
			$getNomUrl_cache[$obj->socid] = $companystatic->getNomUrl(1, 'customer');
		}

		$generic_commande->id = $obj->rowid;
		$generic_commande->ref = $obj->ref;
		$generic_commande->statut = $obj->fk_statut;
		$generic_commande->billed = $obj->billed;
		$generic_commande->date = $db->jdate($obj->date_commande);
		$generic_commande->date_livraison = $db->jdate($obj->date_delivery); // deprecated
		$generic_commande->delivery_date = $db->jdate($obj->date_delivery);
		$generic_commande->ref_client = $obj->ref_client;
		$generic_commande->total_ht = $obj->total_ht;
		$generic_commande->total_tva = $obj->total_tva;
		$generic_commande->total_ttc = $obj->total_ttc;
		$generic_commande->note_public = $obj->note_public;
		$generic_commande->note_private = $obj->note_private;

		$projectstatic->id = $obj->project_id;
		$projectstatic->ref = $obj->project_ref;
		$projectstatic->title = $obj->project_label;

		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['c.ref']['checked'])) {
			print '<td class="nowraponall">';
			print $generic_commande->getNomUrl(1, ($search_status != 2 ? 0 : $obj->fk_statut), 0, 0, 0, 1, 1);

			// Warning late icon and note
			if ($generic_commande->hasDelay()) {
				print img_picto($langs->trans("Late").' : '.$generic_commande->showDelay(), "warning");
			}

			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->commande->multidir_output[$conf->entity].'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);

			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref customer
		if (!empty($arrayfields['c.ref_client']['checked'])) {
			print '<td class="nowrap tdoverflowmax200">'.$obj->ref_client.'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Project ref
		if (!empty($arrayfields['p.ref']['checked'])) {
			print '<td class="nowrap">';
			if ($obj->project_id > 0) {
				print $projectstatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Project label
		if (!empty($arrayfields['p.title']['checked'])) {
			print '<td class="nowrap">';
			if ($obj->project_id > 0) {
				print $projectstatic->title;
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Third party
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax200">';
			print $getNomUrl_cache[$obj->socid];

			// If module invoices enabled and user with invoice creation permissions
			if (!empty($conf->facture->enabled) && !empty($conf->global->ORDER_BILLING_ALL_CUSTOMER)) {
				if ($user->rights->facture->creer) {
					if (($obj->fk_statut > 0 && $obj->fk_statut < 3) || ($obj->fk_statut == 3 && $obj->billed == 0)) {
						print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$companystatic->id.'&search_billed=0&autoselectall=1">';
						print img_picto($langs->trans("CreateInvoiceForThisCustomer").' : '.$companystatic->name, 'object_bill', 'hideonsmartphone').'</a>';
					}
				}
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Alias name
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print '<td class="nocellnopadd">';
			print $obj->alias;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Town
		if (!empty($arrayfields['s.town']['checked'])) {
			print '<td class="nocellnopadd">';
			print $obj->town;
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

		// Order date
		if (!empty($arrayfields['c.date_commande']['checked'])) {
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_commande), 'day');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Plannned date of delivery
		if (!empty($arrayfields['c.date_delivery']['checked'])) {
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_delivery), 'dayhour');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Shipping Method
		if (!empty($arrayfields['c.fk_shipping_method']['checked'])) {
			print '<td>';
			$form->formSelectShippingMethod('', $obj->fk_shipping_method, 'none', 1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Payment terms
		if (!empty($arrayfields['c.fk_cond_reglement']['checked'])) {
			print '<td>';
			$form->form_conditions_reglement($_SERVER['PHP_SELF'], $obj->fk_cond_reglement, 'none');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Payment mode
		if (!empty($arrayfields['c.fk_mode_reglement']['checked'])) {
			print '<td>';
			$form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Channel
		if (!empty($arrayfields['c.fk_input_reason']['checked'])) {
			print '<td>';
			$form->formInputReason($_SERVER['PHP_SELF'], $obj->fk_input_reason, 'none', '');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount HT
		if (!empty($arrayfields['c.total_ht']['checked'])) {
			  print '<td class="nowrap right"><span class="amount">'.price($obj->total_ht)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'c.total_ht';
			}
			if (isset($totalarray['val']['c.total_ht'])) {
				$totalarray['val']['c.total_ht'] += $obj->total_ht;
			} else {
				$totalarray['val']['c.total_ht'] = $obj->total_ht;
			}
		}
		// Amount VAT
		if (!empty($arrayfields['c.total_vat']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($obj->total_tva)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'c.total_tva';
			}
			$totalarray['val']['c.total_tva'] += $obj->total_tva;
		}
		// Amount TTC
		if (!empty($arrayfields['c.total_ttc']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'c.total_ttc';
			}
			$totalarray['val']['c.total_ttc'] += $obj->total_ttc;
		}

		// Currency
		if (!empty($arrayfields['c.multicurrency_code']['checked'])) {
			  print '<td class="nowrap">'.$obj->multicurrency_code.' - '.$langs->trans('Currency'.$obj->multicurrency_code)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Currency rate
		if (!empty($arrayfields['c.multicurrency_tx']['checked'])) {
			  print '<td class="nowrap">';
			  $form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
			  print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount HT
		if (!empty($arrayfields['c.multicurrency_total_ht']['checked'])) {
			  print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ht)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount VAT
		if (!empty($arrayfields['c.multicurrency_total_vat']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_vat)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount TTC
		if (!empty($arrayfields['c.multicurrency_total_ttc']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ttc)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

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

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Date creation
		if (!empty($arrayfields['c.datec']['checked'])) {
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date modification
		if (!empty($arrayfields['c.tms']['checked'])) {
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date cloture
		if (!empty($arrayfields['c.date_cloture']['checked'])) {
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_cloture), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Note public
		if (!empty($arrayfields['c.note_public']['checked'])) {
			print '<td align="center" class="nowrap">';
			print dol_escape_htmltag($obj->note_public);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Note private
		if (!empty($arrayfields['c.note_private']['checked'])) {
			print '<td align="center" class="nowrap">';
			print dol_escape_htmltag($obj->note_private);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Show shippable Icon (this creates subloops, so may be slow)
		if (!empty($arrayfields['shippable']['checked'])) {
			print '<td class="center">';
			if (!empty($show_shippable_command) && !empty($conf->stock->enabled)) {
				if (($obj->fk_statut > $generic_commande::STATUS_DRAFT) && ($obj->fk_statut < $generic_commande::STATUS_CLOSED)) {
					$generic_commande->getLinesArray(); // This set ->lines

					$numlines = count($generic_commande->lines); // Loop on each line of order
					for ($lig = 0; $lig < $numlines; $lig++) {
						if ($generic_commande->lines[$lig]->product_type == 0 && $generic_commande->lines[$lig]->fk_product > 0) {  // If line is a product and not a service
							$nbprod++; // order contains real products
							$generic_product->id = $generic_commande->lines[$lig]->fk_product;

							// Get local and virtual stock and store it into cache
							if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product])) {
								$generic_product->load_stock('nobatch'); // ->load_virtual_stock() is already included into load_stock()
								$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_reel;
								$productstat_cachevirtual[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_theorique;
							} else {
								$generic_product->stock_reel = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'];
								$generic_product->stock_theorique = $productstat_cachevirtual[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_theorique;
							}

							if (empty($conf->global->SHIPPABLE_ORDER_ICON_IN_LIST)) {  // Default code. Default should be this case.
								$text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->product_ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
								$text_info .= ' - '.$langs->trans("Stock").': <span class="'.($generic_product->stock_reel > 0 ? 'ok' : 'error').'">'.$generic_product->stock_reel.'</span>';
								$text_info .= ' - '.$langs->trans("VirtualStock").': <span class="'.($generic_product->stock_theorique > 0 ? 'ok' : 'error').'">'.$generic_product->stock_theorique.'</span>';
								$text_info .= '<br>';

								if ($generic_commande->lines[$lig]->qty > $generic_product->stock_reel) {
									$notshippable++;
								}
							} else {  // BUGGED CODE.
								// DOES NOT TAKE INTO ACCOUNT MANUFACTURING. THIS CODE SHOULD BE USELESS. PREVIOUS CODE SEEMS COMPLETE.
								// COUNT STOCK WHEN WE SHOULD ALREADY HAVE VALUE
								// Detailed virtual stock, looks bugged, uncomplete and need heavy load.
								// stock order and stock order_supplier
								$stock_order = 0;
								$stock_order_supplier = 0;
								if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {    // What about other options ?
									if (!empty($conf->commande->enabled)) {
										if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'])) {
											$generic_product->load_stats_commande(0, '1,2');
											$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'] = $generic_product->stats_commande['qty'];
										} else {
											$generic_product->stats_commande['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'];
										}
										$stock_order = $generic_product->stats_commande['qty'];
									}
									if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled)) {
										if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'])) {
											$generic_product->load_stats_commande_fournisseur(0, '3');
											$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'] = $generic_product->stats_commande_fournisseur['qty'];
										} else {
											$generic_product->stats_commande_fournisseur['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'];
										}
										$stock_order_supplier = $generic_product->stats_commande_fournisseur['qty'];
									}
								}
								$text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
								$text_stock_reel = $generic_product->stock_reel.'/'.$stock_order;
								if ($stock_order > $generic_product->stock_reel && !($generic_product->stock_reel < $generic_commande->lines[$lig]->qty)) {
									$warning++;
									$text_warning .= '<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								}
								if ($generic_product->stock_reel < $generic_commande->lines[$lig]->qty) {
									$notshippable++;
									$text_info .= '<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								} else {
									$text_info .= '<span class="ok">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								}
								if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled)) {
									$text_info .= '&nbsp;'.$langs->trans('SupplierOrder').'&nbsp;:&nbsp;'.$stock_order_supplier.'<br>';
								} else {
									$text_info .= '<br>';
								}
							}
						}
					}
					if ($notshippable == 0) {
						$text_icon = img_picto('', 'dolly', '', false, 0, 0, '', 'green paddingleft');
						$text_info = $langs->trans('Shippable').'<br>'.$text_info;
					} else {
						$text_icon = img_picto('', 'dolly', '', false, 0, 0, '', 'error paddingleft');
						$text_info = $langs->trans('NonShippable').'<br>'.$text_info;
					}
				}

				if ($nbprod) {
					print $form->textwithtooltip('', $text_info, 2, 1, $text_icon, '', 2);
				}
				if ($warning) {     // Always false in default mode
					print $form->textwithtooltip('', $langs->trans('NotEnoughForAllOrders').'<br>'.$text_warning, 2, 1, img_picto('', 'error'), '', 2);
				}
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Billed
		if (!empty($arrayfields['c.facture']['checked'])) {
			print '<td class="center">'.yn($obj->billed).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['c.fk_statut']['checked'])) {
			print '<td class="nowrap center">'.$generic_commande->LibStatut($obj->fk_statut, $obj->billed, 5, 1).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Action column
		print '<td class="nowrap center">';
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

		print "</tr>\n";

		$total += $obj->total_ht;
		$subtotal += $obj->total_ht;
		$i++;
	}

	// Show total line
	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

	$db->free($resql);

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>';

	print '</form>'."\n";

	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_orders', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();

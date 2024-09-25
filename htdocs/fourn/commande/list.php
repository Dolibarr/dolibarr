<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2014      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016      Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France		<frederic.france@free.fr>
 * Copyright (C) 2018-2022 Charlene Benke		<charlene@patas-monkey.com>
 * Copyright (C) 2019      Nicolas Zabouri		<info@inovea-conseil.com>
 * Copyright (C) 2021-2023 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
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
 *    \file       htdocs/fourn/commande/list.php
 *    \ingroup    fournisseur
 *    \brief      List of purchase orders
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


// Load translation files required by the page
$langs->loadLangs(array("orders", "sendings", 'deliveries', 'companies', 'compta', 'bills', 'projects', 'suppliers', 'products'));

// Get Parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'supplierorderlist';
$mode = GETPOST('mode', 'alpha');

// Search Criteria
$search_date_order_startday = GETPOSTINT('search_date_order_startday');
$search_date_order_startmonth = GETPOSTINT('search_date_order_startmonth');
$search_date_order_startyear = GETPOSTINT('search_date_order_startyear');
$search_date_order_endday = GETPOSTINT('search_date_order_endday');
$search_date_order_endmonth = GETPOSTINT('search_date_order_endmonth');
$search_date_order_endyear = GETPOSTINT('search_date_order_endyear');
$search_date_order_start = dol_mktime(0, 0, 0, $search_date_order_startmonth, $search_date_order_startday, $search_date_order_startyear);	// Use tzserver
$search_date_order_end = dol_mktime(23, 59, 59, $search_date_order_endmonth, $search_date_order_endday, $search_date_order_endyear);

$search_date_delivery_startday = GETPOSTINT('search_date_delivery_startday');
$search_date_delivery_startmonth = GETPOSTINT('search_date_delivery_startmonth');
$search_date_delivery_startyear = GETPOSTINT('search_date_delivery_startyear');
$search_date_delivery_endday = GETPOSTINT('search_date_delivery_endday');
$search_date_delivery_endmonth = GETPOSTINT('search_date_delivery_endmonth');
$search_date_delivery_endyear = GETPOSTINT('search_date_delivery_endyear');
$search_date_delivery_start = dol_mktime(0, 0, 0, $search_date_delivery_startmonth, $search_date_delivery_startday, $search_date_delivery_startyear);	// Use tzserver
$search_date_delivery_end = dol_mktime(23, 59, 59, $search_date_delivery_endmonth, $search_date_delivery_endday, $search_date_delivery_endyear);

$search_date_valid_startday = GETPOSTINT('search_date_valid_startday');
$search_date_valid_startmonth = GETPOSTINT('search_date_valid_startmonth');
$search_date_valid_startyear = GETPOSTINT('search_date_valid_startyear');
$search_date_valid_endday = GETPOSTINT('search_date_valid_endday');
$search_date_valid_endmonth = GETPOSTINT('search_date_valid_endmonth');
$search_date_valid_endyear = GETPOSTINT('search_date_valid_endyear');
$search_date_valid_start = dol_mktime(0, 0, 0, $search_date_valid_startmonth, $search_date_valid_startday, $search_date_valid_startyear);	// Use tzserver
$search_date_valid_end = dol_mktime(23, 59, 59, $search_date_valid_endmonth, $search_date_valid_endday, $search_date_valid_endyear);

$search_date_approve_startday = GETPOSTINT('search_date_approve_startday');
$search_date_approve_startmonth = GETPOSTINT('search_date_approve_startmonth');
$search_date_approve_startyear = GETPOSTINT('search_date_approve_startyear');
$search_date_approve_endday = GETPOSTINT('search_date_approve_endday');
$search_date_approve_endmonth = GETPOSTINT('search_date_approve_endmonth');
$search_date_approve_endyear = GETPOSTINT('search_date_approve_endyear');
$search_date_approve_start = dol_mktime(0, 0, 0, $search_date_approve_startmonth, $search_date_approve_startday, $search_date_approve_startyear);	// Use tzserver
$search_date_approve_end = dol_mktime(23, 59, 59, $search_date_approve_endmonth, $search_date_approve_endday, $search_date_approve_endyear);

$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

$search_product_category = GETPOSTINT('search_product_category');
$search_ref = GETPOST('search_ref', 'alpha');
$search_refsupp = GETPOST('search_refsupp', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_company_alias = GETPOST('search_company_alias', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state", 'alpha');
$search_country = GETPOST("search_country", 'aZ09');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'intcomma');
$search_user = GETPOST('search_user', 'intcomma');
$search_request_author = GETPOST('search_request_author', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$socid = GETPOSTINT('socid');
$search_sale = GETPOST('search_sale', 'intcomma');
$search_total_ht = GETPOST('search_total_ht', 'alpha');
$search_total_tva = GETPOST('search_total_tva', 'alpha');
$search_total_ttc = GETPOST('search_total_ttc', 'alpha');
$search_multicurrency_code = GETPOST('search_multicurrency_code', 'alpha');
$search_multicurrency_tx = GETPOST('search_multicurrency_tx', 'alpha');
$search_multicurrency_montant_ht = GETPOST('search_multicurrency_montant_ht', 'alpha');
$search_multicurrency_montant_tva = GETPOST('search_multicurrency_montant_tva', 'alpha');
$search_multicurrency_montant_ttc = GETPOST('search_multicurrency_montant_ttc', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$search_billed = GETPOST('search_billed', 'intcomma');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');

if (GETPOSTISARRAY('search_status')) {
	$search_status = implode(',', GETPOST('search_status', 'array:intcomma'));
} else {
	$search_status = (GETPOST('search_status', 'intcomma') != '' ? GETPOST('search_status', 'intcomma') : GETPOST('statut', 'intcomma'));
}
$search_option = GETPOST('search_option', 'alpha');
if ($search_option == 'late') {
	$search_status = '1,2';
}

$diroutputmassaction = $conf->fournisseur->commande->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'cf.ref';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new CommandeFournisseur($db);
$hookmanager->initHooks(array('supplierorderlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['cf.'.$key] = $val['label'];
	}
}
$fieldstosearchall['pd.description'] = 'Description';
$fieldstosearchall['s.nom'] = "ThirdParty";
$fieldstosearchall['s.name_alias'] = "AliasNameShort";
$fieldstosearchall['s.zip'] = "Zip";
$fieldstosearchall['s.town'] = "Town";
if (empty($user->socid)) {
	$fieldstosearchall["cf.note_private"] = "NotePrivate";
}

$checkedtypetiers = 0;

// Definition of array of fields for columns
$arrayfields = array(
	'u.login' => array('label' => "AuthorRequest", 'enabled' => 1, 'position' => 41),
	's.name_alias' => array('label' => "AliasNameShort", 'position' => 51, 'checked' => 0),
	's.town' => array('label' => "Town", 'enabled' => 1, 'position' => 55, 'checked' => 1),
	's.zip' => array('label' => "Zip", 'enabled' => 1, 'position' => 56, 'checked' => 1),
	'state.nom' => array('label' => "StateShort", 'enabled' => 1, 'position' => 57),
	'country.code_iso' => array('label' => "Country", 'enabled' => 1, 'position' => 58),
	'typent.code' => array('label' => "ThirdPartyType", 'enabled' => $checkedtypetiers, 'position' => 59),
	'cf.total_localtax1' => array('label' => $langs->transcountry("AmountLT1", $mysoc->country_code), 'checked' => 0, 'enabled' => ($mysoc->localtax1_assuj == "1"), 'position' => 140),
	'cf.total_localtax2' => array('label' => $langs->transcountry("AmountLT2", $mysoc->country_code), 'checked' => 0, 'enabled' => ($mysoc->localtax2_assuj == "1"), 'position' => 145),
	'cf.note_public' => array('label' => 'NotePublic', 'checked' => 0, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PUBLIC_NOTES')), 'position' => 750),
	'cf.note_private' => array('label' => 'NotePrivate', 'checked' => 0, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PRIVATE_NOTES')), 'position' => 760),
);
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval((string) $val['visible'], 1);
		$arrayfields['cf.'.$key] = array(
			'label' => $val['label'],
			'checked' => (($visible < 0) ? 0 : 1),
			'enabled' => (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
			'position' => $val['position'],
			'help' => isset($val['help']) ? $val['help'] : ''
		);
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

$error = 0;

if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}

// Security check
$orderid = GETPOSTINT('orderid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'fournisseur', $orderid, '', 'commande');

$permissiontoread = ($user->hasRight("fournisseur", "commande", "lire") || $user->hasRight("supplier_order", "lire"));
$permissiontoadd = ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"));
$permissiontodelete = ($user->hasRight("fournisseur", "commande", "supprimer") || $user->hasRight("supplier_order", "supprimer"));
$permissiontovalidate = $permissiontoadd;
$permissiontoapprove = ($user->hasRight("fournisseur", "commande", "approuver") || $user->hasRight("supplier_order", "approuver"));


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createsupplierbills') {
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
		$search_categ = '';
		$search_user = '';
		$search_sale = '';
		$search_product_category = '';
		$search_ref = '';
		$search_refsupp = '';
		$search_company = '';
		$search_company_alias = '';
		$search_town = '';
		$search_zip = "";
		$search_state = "";
		$search_type = '';
		$search_country = '';
		$search_type_thirdparty = '';
		$search_request_author = '';
		$search_total_ht = '';
		$search_total_tva = '';
		$search_total_ttc = '';
		$search_multicurrency_code = '';
		$search_multicurrency_tx = '';
		$search_multicurrency_montant_ht = '';
		$search_multicurrency_montant_tva = '';
		$search_multicurrency_montant_ttc = '';
		$search_project_ref = '';
		$search_status = '';
		$search_option = '';
		$search_date_order_startday = '';
		$search_date_order_startmonth = '';
		$search_date_order_startyear = '';
		$search_date_order_endday = '';
		$search_date_order_endmonth = '';
		$search_date_order_endyear = '';
		$search_date_order_start = '';
		$search_date_order_end = '';
		$search_date_delivery_startday = '';
		$search_date_delivery_startmonth = '';
		$search_date_delivery_startyear = '';
		$search_date_delivery_endday = '';
		$search_date_delivery_endmonth = '';
		$search_date_delivery_endyear = '';
		$search_date_delivery_start = '';
		$search_date_delivery_end = '';
		$search_date_valid_startday = '';
		$search_date_valid_startmonth = '';
		$search_date_valid_startyear = '';
		$search_date_valid_endday = '';
		$search_date_valid_endmonth = '';
		$search_date_valid_endyear = '';
		$search_date_valid_start = '';
		$search_date_valid_end = '';
		$search_date_approve_startday = '';
		$search_date_approve_startmonth = '';
		$search_date_approve_startyear = '';
		$search_date_approve_endday = '';
		$search_date_approve_endmonth = '';
		$search_date_approve_endyear = '';
		$search_date_approve_start = '';
		$search_date_approve_end = '';
		$billed = '';
		$search_billed = '';
		$toselect = array();
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'CommandeFournisseur';
	$objectlabel = 'SupplierOrders';
	$uploaddir = $conf->fournisseur->commande->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if ($action == 'validate' && $permissiontovalidate) {
		if (GETPOST('confirm') == 'yes') {
			$objecttmp = new CommandeFournisseur($db);
			$db->begin();
			$error = 0;

			foreach ($toselect as $checked) {
				if ($objecttmp->fetch($checked)) {
					if ($objecttmp->statut == 0) {
						$objecttmp->date_commande = dol_now();
						$result = $objecttmp->valid($user);
						if ($result >= 0) {
							// If we have permission, and if we don't need to provide the idwarehouse, we go directly on approved step
							if (!getDolGlobalString('SUPPLIER_ORDER_NO_DIRECT_APPROVE') && $permissiontoapprove && !(getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') && $objecttmp->hasProductsOrServices(1))) {
								$result = $objecttmp->approve($user);
								setEventMessages($langs->trans("SupplierOrderValidatedAndApproved"), array($objecttmp->ref));
							} else {
								setEventMessages($langs->trans("SupplierOrderValidated"), array($objecttmp->ref));
							}
						} else {
							setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
							$error++;
						}
					}
				}
			}

			if (!$error) {
				$db->commit();
			} else {
				$db->rollback();
			}
		}
	}

	// Mass action to generate vendor bills
	if ($massaction == 'confirm_createsupplierbills') {
		$orders = GETPOST('toselect', 'array');
		$createbills_onebythird = GETPOSTINT('createbills_onebythird');
		$validate_invoices = GETPOSTINT('validate_invoices');

		$TFact = array();
		/** @var FactureFournisseur[] $TFactThird */
		$TFactThird = array();

		$nb_bills_created = 0;
		$lastid = 0;
		$lastref = '';

		$db->begin();

		$default_ref_supplier = dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		$currentIndex = 0;
		foreach ($orders as $id_order) {
			$cmd = new CommandeFournisseur($db);
			if ($cmd->fetch($id_order) <= 0) {
				continue;
			}

			$objecttmp = new FactureFournisseur($db);
			if (!empty($createbills_onebythird) && !empty($TFactThird[$cmd->socid])) {
				$currentIndex++;
				$objecttmp = $TFactThird[$cmd->socid]; // If option "one bill per third" is set, we use already created supplier invoice.
			} else {
				// Search if the VAT reverse-charge is activated by default in supplier card to resume the information
				if (!empty($cmd->socid) > 0) {
					$societe = new Societe($db);
					$societe->fetch($cmd->socid);
					$objecttmp->vat_reverse_charge = $societe->vat_reverse_charge;
				}
				$objecttmp->socid = $cmd->socid;
				$objecttmp->type = $objecttmp::TYPE_STANDARD;
				$objecttmp->cond_reglement_id	= $cmd->cond_reglement_id;
				$objecttmp->mode_reglement_id	= $cmd->mode_reglement_id;
				$objecttmp->fk_project = $cmd->fk_project;
				$objecttmp->multicurrency_code = $cmd->multicurrency_code;
				$objecttmp->ref_supplier = !empty($cmd->ref_supplier) ? $cmd->ref_supplier : $default_ref_supplier;
				$default_ref_supplier += 1;

				$datefacture = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
				if (empty($datefacture)) {
					$datefacture = dol_now();
				}

				$objecttmp->date = $datefacture;
				$objecttmp->origin    = 'order_supplier';
				$objecttmp->origin_id = $id_order;

				$res = $objecttmp->create($user);

				if ($res > 0) {
					$nb_bills_created++;
					$lastref = $objecttmp->ref;
					$lastid = $objecttmp->id;
				}
			}

			if ($objecttmp->id > 0) {
				if (empty($objecttmp->note_public)) {
					$objecttmp->note_public =  $langs->transnoentities("Orders");
				}

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
				$sql .= "fk_source";
				$sql .= ", sourcetype";
				$sql .= ", fk_target";
				$sql .= ", targettype";
				$sql .= ") VALUES (";
				$sql .= $id_order;
				$sql .= ", '".$db->escape($objecttmp->origin)."'";
				$sql .= ", ".((int) $objecttmp->id);
				$sql .= ", '".$db->escape($objecttmp->element)."'";
				$sql .= ")";

				if (!$db->query($sql)) {
					$error++;
				}

				if (!$error) {
					$lines = $cmd->lines;
					if (empty($lines) && method_exists($cmd, 'fetch_lines')) {
						$cmd->fetch_lines();
						$lines = $cmd->lines;
					}

					$fk_parent_line = 0;
					$num = count($lines);

					for ($i = 0; $i < $num; $i++) {
						$desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->libelle);
						if ($lines[$i]->subprice < 0) {
							require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

							// Negative line, we create a discount line
							$discount = new DiscountAbsolute($db);
							$discount->fk_soc = $objecttmp->socid;
							$discount->socid = $objecttmp->socid;
							$discount->amount_ht = abs($lines[$i]->total_ht);
							$discount->amount_tva = abs($lines[$i]->total_tva);
							$discount->amount_ttc = abs($lines[$i]->total_ttc);
							$discount->tva_tx = $lines[$i]->tva_tx;
							$discount->fk_user = $user->id;
							$discount->description = $desc;
							$discountid = $discount->create($user);
							if ($discountid > 0) {
								$result = $objecttmp->insert_discount($discountid);
								//$result=$discount->link_to_invoice($lineid,$id);
							} else {
								setEventMessages($discount->error, $discount->errors, 'errors');
								$error++;
								break;
							}
						} else {
							// Positive line
							$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
							// Date start
							$date_start = false;
							if ($lines[$i]->date_debut_prevue) {
								$date_start = $lines[$i]->date_debut_prevue;
							}
							if ($lines[$i]->date_debut_reel) {
								$date_start = $lines[$i]->date_debut_reel;
							}
							if ($lines[$i]->date_start) {
								$date_start = $lines[$i]->date_start;
							}
							//Date end
							$date_end = false;
							if ($lines[$i]->date_fin_prevue) {
								$date_end = $lines[$i]->date_fin_prevue;
							}
							if ($lines[$i]->date_fin_reel) {
								$date_end = $lines[$i]->date_fin_reel;
							}
							if ($lines[$i]->date_end) {
								$date_end = $lines[$i]->date_end;
							}
							// Reset fk_parent_line for no child products and special product
							if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
								$fk_parent_line = 0;
							}
							$result = $objecttmp->addline(
								$desc,
								$lines[$i]->subprice,
								$lines[$i]->tva_tx,
								$lines[$i]->localtax1_tx,
								$lines[$i]->localtax2_tx,
								$lines[$i]->qty,
								$lines[$i]->fk_product,
								$lines[$i]->remise_percent,
								$date_start,
								$date_end,
								0,
								$lines[$i]->info_bits,
								'HT',
								$product_type,
								// we don't use the rank from orderline because we may have lines from several orders
								-1,
								false,
								$lines[$i]->array_options,
								$lines[$i]->fk_unit,
								// we use the id of each order, not the id of the first one stored in $objecttmp->origin_id
								$lines[$i]->fk_commande,
								$lines[$i]->pa_ht,
								$lines[$i]->ref_supplier,
								$lines[$i]->special_code,
								$fk_parent_line
							);
							if ($result > 0) {
								$lineid = $result;
							} else {
								$lineid = 0;
								$error++;
								break;
							}
							// Defined the new fk_parent_line
							if ($result > 0 && $lines[$i]->product_type == 9) {
								$fk_parent_line = $result;
							}
						}
					}
				}
			}

			if ($currentIndex <= (getDolGlobalInt("MAXREFONDOC") ? getDolGlobalInt("MAXREFONDOC") : 10)) {
				$objecttmp->note_public = dol_concatdesc($objecttmp->note_public, $langs->transnoentities($cmd->ref).(empty($cmd->ref_supplier) ? '' : ' ('.$cmd->ref_supplier.')'));
				$objecttmp->update($user);
			}

			$cmd->classifyBilled($user); // TODO Move this in workflow like done for sales orders

			if (!empty($createbills_onebythird) && empty($TFactThird[$cmd->socid])) {
				$TFactThird[$cmd->socid] = $objecttmp;
			} else {
				$TFact[$objecttmp->id] = $objecttmp;
			}
		}

		// Build doc with all invoices
		$TAllFact = empty($createbills_onebythird) ? $TFact : $TFactThird;
		$toselect = array();

		if (!$error && $validate_invoices) {
			$massaction = $action = 'builddoc';

			foreach ($TAllFact as &$objecttmp) {
				$objecttmp->validate($user);
				if ($result <= 0) {
					$error++;
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					break;
				}

				$id = $objecttmp->id; // For builddoc action

				// Fac builddoc
				$donotredirect = 1;
				$upload_dir = $conf->fournisseur->facture->dir_output;
				$permissiontoadd = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"));
				//include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
			}

			$massaction = $action = 'confirm_createsupplierbills';
		}

		if (!$error) {
			$db->commit();

			if ($nb_bills_created == 1) {
				$texttoshow = $langs->trans('BillXCreated', '{s1}');
				$texttoshow = str_replace('{s1}', '<a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?id='.urlencode((string) ($lastid)).'">'.$lastref.'</a>', $texttoshow);
				setEventMessages($texttoshow, null, 'mesgs');
			} else {
				setEventMessages($langs->trans('BillCreated', $nb_bills_created), null, 'mesgs');
			}

			// Make a redirect to avoid to bill twice if we make a refresh or back
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
			if ($search_all) {
				$param .= '&search_all='.urlencode($search_all);
			}
			if ($socid > 0) {
				$param .= '&socid='.urlencode((string) ($socid));
			}
			if ($search_status != '') {
				$param .= '&search_status='.urlencode($search_status);
			}
			if ($search_option) {
				$param .= '&search_option='.urlencode($search_option);
			}
			if ($search_date_order_startday) {
				$param .= '&search_date_order_startday='.urlencode((string) ($search_date_order_startday));
			}
			if ($search_date_order_startmonth) {
				$param .= '&search_date_order_startmonth='.urlencode((string) ($search_date_order_startmonth));
			}
			if ($search_date_order_startyear) {
				$param .= '&search_date_order_startyear='.urlencode((string) ($search_date_order_startyear));
			}
			if ($search_date_order_endday) {
				$param .= '&search_date_order_endday='.urlencode((string) ($search_date_order_endday));
			}
			if ($search_date_order_endmonth) {
				$param .= '&search_date_order_endmonth='.urlencode((string) ($search_date_order_endmonth));
			}
			if ($search_date_order_endyear) {
				$param .= '&search_date_order_endyear='.urlencode((string) ($search_date_order_endyear));
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
			if ($search_date_approve_startday) {
				$param .= '&search_date_approve_startday='.urlencode((string) ($search_date_approve_startday));
			}
			if ($search_date_approve_startmonth) {
				$param .= '&search_date_approve_startmonth='.urlencode((string) ($search_date_approve_startmonth));
			}
			if ($search_date_approve_startyear) {
				$param .= '&search_date_approve_startyear='.urlencode((string) ($search_date_approve_startyear));
			}
			if ($search_date_approve_endday) {
				$param .= '&search_date_approve_endday='.urlencode((string) ($search_date_approve_endday));
			}
			if ($search_date_approve_endmonth) {
				$param .= '&search_date_approve_endmonth='.urlencode((string) ($search_date_approve_endmonth));
			}
			if ($search_date_approve_endyear) {
				$param .= '&search_date_approve_endyear='.urlencode((string) ($search_date_approve_endyear));
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
			//if ($search_ref_customer)	$param .= '&search_ref_customer='.urlencode($search_ref_customer);
			if ($search_user > 0) {
				$param .= '&search_user='.urlencode((string) ($search_user));
			}
			if ($search_sale > 0) {
				$param .= '&search_sale='.urlencode($search_sale);
			}
			if ($search_total_ht != '') {
				$param .= '&search_total_ht='.urlencode($search_total_ht);
			}
			if ($search_total_tva != '') {
				$param .= '&search_total_tva='.urlencode($search_total_tva);
			}
			if ($search_total_ttc != '') {
				$param .= '&search_total_ttc='.urlencode($search_total_ttc);
			}
			if ($search_project_ref >= 0) {
				$param .= "&search_project_ref=".urlencode($search_project_ref);
			}
			if ($show_files) {
				$param .= '&show_files='.urlencode((string) ($show_files));
			}
			if ($optioncss != '') {
				$param .= '&optioncss='.urlencode($optioncss);
			}
			if ($billed != '') {
				$param .= '&billed='.urlencode($billed);
			}

			header("Location: ".$_SERVER['PHP_SELF'].'?'.$param);
			exit;
		} else {
			$db->rollback();
			$action = 'create';
			$_GET["origin"] = $_POST["origin"];		// Keep this ?
			$_GET["originid"] = $_POST["originid"];	// Keep this ?
			setEventMessages("Error", null, 'errors');
			$error++;
		}
	}
}


/*
 *	View
 */

$now = dol_now();

$form = new Form($db);
$thirdpartytmp = new Fournisseur($db);
$commandestatic = new CommandeFournisseur($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);

$title = $langs->trans("SuppliersOrders");
if ($socid > 0) {
	$fourn = new Fournisseur($db);
	$fourn->fetch($socid);
	$title .= ' - '.$fourn->name;
}

/*if ($search_status)
{
	if ($search_status == '1,2') $title .= ' - '.$langs->trans("SuppliersOrdersToProcess");
	elseif ($search_status == '3,4') $title .= ' - '.$langs->trans("SuppliersOrdersAwaitingReception");
	elseif ($search_status == '1,2,3') $title .= ' - '.$langs->trans("StatusOrderToProcessShort");
	elseif ($search_status == '6,7') $title .= ' - '.$langs->trans("StatusOrderCanceled");
	elseif (is_numeric($search_status) && $search_status >= 0) $title .= ' - '.$commandestatic->LibStatut($search_status);
}*/
if ($search_billed > 0) {
	$title .= ' - '.$langs->trans("Billed");
}

//$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
$help_url = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

$sql = 'SELECT';
if ($search_all) {
	$sql = 'SELECT DISTINCT';
}
$sql .= ' s.rowid as socid, s.nom as name, s.name_alias as alias, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.email,';
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " cf.rowid, cf.ref, cf.ref_supplier, cf.fk_statut, cf.billed, cf.total_ht, cf.total_tva, cf.total_ttc, cf.fk_user_author, cf.date_commande as date_commande, cf.date_livraison as delivery_date, cf.date_valid, cf.date_approve,";
$sql .= ' cf.localtax1 as total_localtax1, cf.localtax2 as total_localtax2,';
$sql .= ' cf.fk_multicurrency, cf.multicurrency_code, cf.multicurrency_tx, cf.multicurrency_total_ht, cf.multicurrency_total_tva, cf.multicurrency_total_ttc,';
$sql .= ' cf.date_creation as date_creation, cf.tms as date_modification,';
$sql .= ' cf.note_public, cf.note_private,';
$sql .= " p.rowid as project_id, p.ref as project_ref, p.title as project_title,";
$sql .= " u.firstname, u.lastname, u.photo, u.login, u.email as user_email, u.statut as user_status";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (cf.rowid = ef.fk_object)";
}
if ($search_all) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet as pd ON cf.rowid=pd.fk_commande';
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON cf.fk_user_author = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = cf.fk_projet";
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' WHERE cf.fk_soc = s.rowid';
$sql .= ' AND cf.entity IN ('.getEntity('supplier_order').')';
if ($socid > 0) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
if ($search_ref) {
	$sql .= natural_search('cf.ref', $search_ref);
}
if ($search_refsupp) {
	$sql .= natural_search("cf.ref_supplier", $search_refsupp);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
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
if ($search_request_author) {
	$sql .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_request_author);
}
if ($search_billed != '' && $search_billed >= 0) {
	$sql .= " AND cf.billed = ".((int) $search_billed);
	if(getDolGlobalString('HIDE_CANCELLED_ORDERS_BILLABLE') == 1){
		$sql .= ' AND cf.fk_statut < 5';
	}
}
//Required triple check because statut=0 means draft filter
if (GETPOST('statut', 'intcomma') !== '') {
	$sql .= " AND cf.fk_statut IN (".$db->sanitize($db->escape($db->escape(GETPOST('statut', 'intcomma')))).")";
}
if ($search_status != '' && $search_status != '-1') {
	$sql .= " AND cf.fk_statut IN (".$db->sanitize($db->escape($search_status)).")";
}
if ($search_option == 'late') {
	$sql .= " AND cf.date_commande < '".$db->idate(dol_now() - $conf->order->fournisseur->warning_delay)."'";
}
if ($search_date_order_start) {
	$sql .= " AND cf.date_commande >= '".$db->idate($search_date_order_start)."'";
}
if ($search_date_order_end) {
	$sql .= " AND cf.date_commande <= '".$db->idate($search_date_order_end)."'";
}
if ($search_date_delivery_start) {
	$sql .= " AND cf.date_livraison >= '".$db->idate($search_date_delivery_start)."'";
}
if ($search_date_delivery_end) {
	$sql .= " AND cf.date_livraison <= '".$db->idate($search_date_delivery_end)."'";
}
if ($search_date_valid_start) {
	$sql .= " AND cf.date_valid >= '".$db->idate($search_date_valid_start)."'";
}
if ($search_date_valid_end) {
	$sql .= " AND cf.date_valid <= '".$db->idate($search_date_valid_end)."'";
}
if ($search_date_approve_start) {
	$sql .= " AND cf.date_livraison >= '".$db->idate($search_date_approve_start)."'";
}
if ($search_date_approve_end) {
	$sql .= " AND cf.date_livraison <= '".$db->idate($search_date_approve_end)."'";
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
	$sql .= " AND s.fk_pays IN (".$db->sanitize($db->escape($search_country)).')';
}
if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($db->escape($search_type_thirdparty)).')';
}
/*if ($search_sale > 0) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $search_sale);
}*/
if ($search_user > 0) {
	$sql .= " AND EXISTS (";
	$sql .= " SELECT ec.rowid ";
	$sql .= " FROM " . MAIN_DB_PREFIX . "element_contact as ec";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "c_type_contact as tc ON tc.rowid = ec.fk_c_type_contact";
	$sql .= " WHERE ec.element_id = cf.rowid AND ec.fk_socpeople = " . ((int) $search_user);
	$sql .= " AND tc.element = 'order_supplier' AND tc.source = 'internal'";
	$sql .= ")";
}
if ($search_total_ht != '') {
	$sql .= natural_search('cf.total_ht', $search_total_ht, 1);
}
if ($search_total_tva != '') {
	$sql .= natural_search('cf.total_tva', $search_total_tva, 1);
}
if ($search_total_ttc != '') {
	$sql .= natural_search('cf.total_ttc', $search_total_ttc, 1);
}
if ($search_multicurrency_code != '') {
	$sql .= " AND cf.multicurrency_code = '".$db->escape($search_multicurrency_code)."'";
}
if ($search_multicurrency_tx != '') {
	$sql .= natural_search('cf.multicurrency_tx', $search_multicurrency_tx, 1);
}
if ($search_multicurrency_montant_ht != '') {
	$sql .= natural_search('cf.multicurrency_total_ht', $search_multicurrency_montant_ht, 1);
}
if ($search_multicurrency_montant_tva != '') {
	$sql .= natural_search('cf.multicurrency_total_tva', $search_multicurrency_montant_tva, 1);
}
if ($search_multicurrency_montant_ttc != '') {
	$sql .= natural_search('cf.multicurrency_total_ttc', $search_multicurrency_montant_ttc, 1);
}
if ($search_project_ref != '') {
	$sql .= natural_search("p.ref", $search_project_ref);
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = cf.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = cf.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
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
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."commande_fournisseurdet as cd WHERE cd.fk_commande = cf.rowid AND cd.fk_product = ck.fk_product)";
		} elseif (intval($searchCategoryProduct) > 0) {
			if ($searchCategoryProductOperator == 0) {
				$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."commande_fournisseurdet as cd WHERE cd.fk_commande = cf.rowid AND cd.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."commande_fournisseurdet as cd WHERE cd.fk_commande = cf.rowid AND cd.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
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
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

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

$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}
//print $sql;

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all) {
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".DOL_URL_ROOT.'/fourn/commande/card.php?id='.$id);
		exit;
	}

	llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-supplier-order page-list');

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
	if ($search_all) {
		$param .= '&search_all='.urlencode($search_all);
	}
	if ($socid > 0) {
		$param .= '&socid='.urlencode((string) ($socid));
	}
	if ($search_all) {
		$param .= "&search_all=".urlencode($search_all);
	}
	if ($search_date_order_startday) {
		$param .= '&search_date_order_startday='.urlencode((string) ($search_date_order_startday));
	}
	if ($search_date_order_startmonth) {
		$param .= '&search_date_order_startmonth='.urlencode((string) ($search_date_order_startmonth));
	}
	if ($search_date_order_startyear) {
		$param .= '&search_date_order_startyear='.urlencode((string) ($search_date_order_startyear));
	}
	if ($search_date_order_endday) {
		$param .= '&search_date_order_endday='.urlencode((string) ($search_date_order_endday));
	}
	if ($search_date_order_endmonth) {
		$param .= '&search_date_order_endmonth='.urlencode((string) ($search_date_order_endmonth));
	}
	if ($search_date_order_endyear) {
		$param .= '&search_date_order_endyear='.urlencode((string) ($search_date_order_endyear));
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
	if ($search_date_approve_startday) {
		$param .= '&search_date_approve_startday='.urlencode((string) ($search_date_approve_startday));
	}
	if ($search_date_approve_startmonth) {
		$param .= '&search_date_approve_startmonth='.urlencode((string) ($search_date_approve_startmonth));
	}
	if ($search_date_approve_startyear) {
		$param .= '&search_date_approve_startyear='.urlencode((string) ($search_date_approve_startyear));
	}
	if ($search_date_approve_endday) {
		$param .= '&search_date_approve_endday='.urlencode((string) ($search_date_approve_endday));
	}
	if ($search_date_approve_endmonth) {
		$param .= '&search_date_approve_endmonth='.urlencode((string) ($search_date_approve_endmonth));
	}
	if ($search_date_approve_endyear) {
		$param .= '&search_date_approve_endyear='.urlencode((string) ($search_date_approve_endyear));
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
	if ($search_user > 0) {
		$param .= '&search_user='.urlencode((string) ($search_user));
	}
	if ($search_request_author) {
		$param .= '&search_request_author='.urlencode($search_request_author);
	}
	if ($search_sale > 0) {
		$param .= '&search_sale='.urlencode($search_sale);
	}
	if ($search_total_ht != '') {
		$param .= '&search_total_ht='.urlencode($search_total_ht);
	}
	if ($search_total_ttc != '') {
		$param .= "&search_total_ttc=".urlencode($search_total_ttc);
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
	if ($search_multicurrency_montant_tva != '') {
		$param .= '&search_multicurrency_montant_tva='.urlencode($search_multicurrency_montant_tva);
	}
	if ($search_multicurrency_montant_ttc != '') {
		$param .= '&search_multicurrency_montant_ttc='.urlencode($search_multicurrency_montant_ttc);
	}
	if ($search_refsupp) {
		$param .= "&search_refsupp=".urlencode($search_refsupp);
	}
	if ($search_status != '' && $search_status != '-1') {
		$param .= "&search_status=".urlencode($search_status);
	}
	if ($search_option) {
		$param .= "&search_option=".urlencode($search_option);
	}
	if ($search_project_ref >= 0) {
		$param .= "&search_project_ref=".urlencode($search_project_ref);
	}
	if ($search_billed != '') {
		$param .= "&search_billed=".urlencode($search_billed);
	}
	if ($show_files) {
		$param .= '&show_files='.urlencode((string) ($show_files));
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}
	if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
		$param .= '&search_type_thirdparty='.urlencode((string) ($search_type_thirdparty));
	}

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$parameters = array('param' => &$param);
	$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
	$param .= $hookmanager->resPrint;

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
		'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
		'presend' => img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	);

	if ($permissiontovalidate) {
		if ($permissiontoapprove && !getDolGlobalString('SUPPLIER_ORDER_NO_DIRECT_APPROVE')) {
			$arrayofmassactions['prevalidate'] = img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("ValidateAndApprove");
		} else {
			$arrayofmassactions['prevalidate'] = img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate");
		}
	}

	if ($user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight("supplier_invoice", "creer")) {
		$arrayofmassactions['createbills'] = img_picto('', 'supplier_invoice', 'class="pictofixedwidth"').$langs->trans("CreateInvoiceForThisSupplier");
	}
	if ($permissiontodelete) {
		$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	}
	if (in_array($massaction, array('presend', 'predelete', 'createbills'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$url = DOL_URL_ROOT.'/fourn/commande/card.php?action=create';
	if ($socid > 0) {
		$url .= '&socid='.((int) $socid);
		$url .= '&backtopage='.urlencode(DOL_URL_ROOT.'/fourn/commande/list.php?socid='.((int) $socid));
	}
	$newcardbutton = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewSupplierOrderShort'), '', 'fa fa-plus-circle', $url, '', (int) $permissiontoadd);

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
	print '<input type="hidden" name="socid" value="'.$socid.'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'supplier_order', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "SendOrderRef";
	$modelmail = "order_supplier_send";
	$objecttmp = new CommandeFournisseur($db);	// in case $object is not the good object
	$trackid = 'sord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($massaction == 'prevalidate') {
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassValidation"), $langs->trans("ConfirmMassValidationQuestion"), "validate", null, '', 0, 200, 500, 1);
	}

	if ($massaction == 'createbills') {
		//var_dump($_REQUEST);
		print '<input type="hidden" name="massaction" value="confirm_createsupplierbills">';

		print '<table class="noborder centpercent">';
		print '<tr>';
		print '<td class="titlefield">';
		print $langs->trans('DateInvoice');
		print '</td>';
		print '<td>';
		print $form->selectDate('', '', 0, 0, 0, '', 1, 1);
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
		print $form->selectyesno('validate_invoices', 1, 1);
		print '</td>';
		print '</tr>';
		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button" id="createbills" name="createbills" value="'.$langs->trans('CreateInvoiceForThisCustomer').'">  ';
		print '<input type="submit" class="button button-cancel" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
		print '<br>';
		print '<br>';
	}

	if ($search_all) {
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';

	// If the user can view prospects other than his'
	if ($user->hasRight("user", "user", "lire")) {
		$langs->load("commercial");
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
		$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth250 widthcentpercentminusx');
		$moreforfilter .= '</div>';
	}
	// If the user can view other users
	if ($user->hasRight("user", "user", "lire")) {
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('LinkedToSpecificUsers');
		$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_user, 'search_user', $tmptitle, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth250 widthcentpercentminusx');
		$moreforfilter .= '</div>';
	}
	// If the user can view prospects other than his'
	if (isModEnabled('category') && $user->hasRight('categorie', 'lire') && ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire'))) {
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('IncludingProductWithTag');
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, 0, 'maxwidth300 widthcentpercentminusx', 1);
		$moreforfilter .= '</div>';
	}
	// alert on late date
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('Alert').' <input type="checkbox" name="search_option" value="late"'.($search_option == 'late' ? ' checked' : '').'>';
	$moreforfilter .= '</div>';
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
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	if (GETPOSTINT('autoselectall')) {
		$selectedfields .= '<script>';
		$selectedfields .= '   $(document).ready(function() {';
		$selectedfields .= '        console.log("Autoclick on checkforselects");';
		$selectedfields .= '   		$("#checkforselects").click();';
		$selectedfields .= '        $("#massaction").val("createbills").change();';
		$selectedfields .= '   });';
		$selectedfields .= '</script>';
	}

	print '<div class="div-table-responsive">';
	print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre_filter">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre middle">';
		$searchpicto = $form->showFilterButtons('left');
		print $searchpicto;
		print '</td>';
	}
	// Ref
	if (!empty($arrayfields['cf.ref']['checked'])) {
		print '<td class="liste_titre"><input size="8" type="text" class="flat maxwidth75" name="search_ref" value="'.$search_ref.'"></td>';
	}
	// Ref customer
	if (!empty($arrayfields['cf.ref_supplier']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_refsupp" value="'.$search_refsupp.'"></td>';
	}
	// Project ref
	if (!empty($arrayfields['cf.fk_projet']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_project_ref" value="'.$search_project_ref.'"></td>';
	}
	// Request author
	if (!empty($arrayfields['u.login']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="6" name="search_request_author" value="'.$search_request_author.'">';
		print '</td>';
	}
	// Thirpdarty
	if (!empty($arrayfields['cf.fk_soc']['checked'])) {
		print '<td class="liste_titre"><input type="text" size="6" class="flat" name="search_company" value="'.$search_company.'"></td>';
	}
	// Alias
	if (!empty($arrayfields['s.name_alias']['checked'])) {
		print '<td class="liste_titre"><input type="text" size="6" class="flat" name="search_company_alias" value="'.$search_company_alias.'"></td>';
	}
	// Town
	if (!empty($arrayfields['s.town']['checked'])) {
		print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_town" value="'.$search_town.'"></td>';
	}
	// Zip
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
		print '<td class="liste_titre center">';
		print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
		print '</td>';
	}
	// Company type
	if (!empty($arrayfields['typent.code']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, (!getDolGlobalString('SOCIETE_SORT_ON_TYPEENT') ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), '', 1);
		print '</td>';
	}
	// Date order
	if (!empty($arrayfields['cf.date_commande']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_order_start ? $search_date_order_start : -1, 'search_date_order_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_order_end ? $search_date_order_end : -1, 'search_date_order_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	// Date delivery
	if (!empty($arrayfields['cf.date_livraison']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_delivery_start ? $search_date_delivery_start : -1, 'search_date_delivery_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_delivery_end ? $search_date_delivery_end : -1, 'search_date_delivery_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	if (!empty($arrayfields['cf.total_ht']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_total_ht" value="'.$search_total_ht.'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.total_tva']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_total_tva" value="'.$search_total_tva.'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.total_ttc']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_total_ttc" value="'.$search_total_ttc.'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_code']['checked'])) {
		// Currency
		print '<td class="liste_titre">';
		print $form->selectMultiCurrency($search_multicurrency_code, 'search_multicurrency_code', 1);
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_tx']['checked'])) {
		// Currency rate
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_tx" value="'.dol_escape_htmltag($search_multicurrency_tx).'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_total_ht']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ht" value="'.dol_escape_htmltag($search_multicurrency_montant_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_total_tva']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_tva" value="'.dol_escape_htmltag($search_multicurrency_montant_tva).'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_total_ttc']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ttc" value="'.dol_escape_htmltag($search_multicurrency_montant_ttc).'">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['cf.date_creation']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['cf.tms']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Billed
	if (!empty($arrayfields['cf.billed']['checked'])) {
		print '<td class="liste_titre center parentonrightofpage">';
		print $form->selectyesno('search_billed', $search_billed, 1, false, 1, 1, 'search_status width100 onrightofpage');
		print '</td>';
	}
	// Status
	if (!empty($arrayfields['cf.fk_statut']['checked'])) {
		print '<td class="liste_titre center parentonrightofpage">';
		$formorder->selectSupplierOrderStatus($search_status, 1, 'search_status', 'search_status width100 onrightofpage');
		print '</td>';
	}
	// Date valid
	if (!empty($arrayfields['cf.date_valid']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_valid_start ? $search_date_valid_start : -1, 'search_date_valid_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_valid_end ? $search_date_valid_end : -1, 'search_date_valid_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	// Date approve
	if (!empty($arrayfields['cf.date_approve']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_approve_start ? $search_date_approve_start : -1, 'search_date_approve_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_approve_end ? $search_date_approve_end : -1, 'search_date_approve_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	// Note public
	if (!empty($arrayfields['cf.note_public']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Note private
	if (!empty($arrayfields['cf.note_private']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre center">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}

	print "</tr>\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	// Fields title
	print '<tr class="liste_titre">';
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.ref']['checked'])) {
		print_liste_field_titre($arrayfields['cf.ref']['label'], $_SERVER["PHP_SELF"], "cf.ref", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.ref_supplier']['checked'])) {
		print_liste_field_titre($arrayfields['cf.ref_supplier']['label'], $_SERVER["PHP_SELF"], "cf.ref_supplier", "", $param, '', $sortfield, $sortorder, 'tdoverflowmax100imp ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.fk_projet']['checked'])) {
		print_liste_field_titre($arrayfields['cf.fk_projet']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.login']['checked'])) {
		print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], "u.login", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.fk_soc']['checked'])) {
		print_liste_field_titre($arrayfields['cf.fk_soc']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['s.name_alias']['checked'])) {
		print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], "s.name_alias", "", $param, '', $sortfield, $sortorder);
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
	if (!empty($arrayfields['cf.fk_author']['checked'])) {
		print_liste_field_titre($arrayfields['cf.fk_author']['label'], $_SERVER["PHP_SELF"], "cf.fk_author", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.date_commande']['checked'])) {
		print_liste_field_titre($arrayfields['cf.date_commande']['label'], $_SERVER["PHP_SELF"], "cf.date_commande", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.date_livraison']['checked'])) {
		print_liste_field_titre($arrayfields['cf.date_livraison']['label'], $_SERVER["PHP_SELF"], 'cf.date_livraison', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.total_ht']['checked'])) {
		print_liste_field_titre($arrayfields['cf.total_ht']['label'], $_SERVER["PHP_SELF"], "cf.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.total_tva']['checked'])) {
		print_liste_field_titre($arrayfields['cf.total_tva']['label'], $_SERVER["PHP_SELF"], "cf.total_tva", "", $param, '', $sortfield, $sortorder, 'right ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.total_ttc']['checked'])) {
		print_liste_field_titre($arrayfields['cf.total_ttc']['label'], $_SERVER["PHP_SELF"], "cf.total_ttc", "", $param, '', $sortfield, $sortorder, 'right ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.multicurrency_code']['checked'])) {
		print_liste_field_titre($arrayfields['cf.multicurrency_code']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_code', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.multicurrency_tx']['checked'])) {
		print_liste_field_titre($arrayfields['cf.multicurrency_tx']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_tx', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.multicurrency_total_ht']['checked'])) {
		print_liste_field_titre($arrayfields['cf.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.multicurrency_total_tva']['checked'])) {
		print_liste_field_titre($arrayfields['cf.multicurrency_total_tva']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_total_tva', '', $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.multicurrency_total_ttc']['checked'])) {
		print_liste_field_titre($arrayfields['cf.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['cf.date_creation']['checked'])) {
		print_liste_field_titre($arrayfields['cf.date_creation']['label'], $_SERVER["PHP_SELF"], "cf.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowraponall ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.tms']['checked'])) {
		print_liste_field_titre($arrayfields['cf.tms']['label'], $_SERVER["PHP_SELF"], "cf.tms", "", $param, '', $sortfield, $sortorder, 'center nowraponall ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.billed']['checked'])) {
		print_liste_field_titre($arrayfields['cf.billed']['label'], $_SERVER["PHP_SELF"], 'cf.billed', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.fk_statut']['checked'])) {
		print_liste_field_titre($arrayfields['cf.fk_statut']['label'], $_SERVER["PHP_SELF"], "cf.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.date_valid']['checked'])) {
		print_liste_field_titre($arrayfields['cf.date_valid']['label'], $_SERVER["PHP_SELF"], "cf.date_valid", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.date_approve']['checked'])) {
		print_liste_field_titre($arrayfields['cf.date_approve']['label'], $_SERVER["PHP_SELF"], 'cf.date_approve', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.note_public']['checked'])) {
		print_liste_field_titre($arrayfields['cf.note_public']['label'], $_SERVER["PHP_SELF"], "cf.note_public", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['cf.note_private']['checked'])) {
		print_liste_field_titre($arrayfields['cf.note_private']['label'], $_SERVER["PHP_SELF"], "cf.note_private", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		$totalarray['nbfield']++;
	}
	print "</tr>\n";

	$total = 0;
	$subtotal = 0;
	$productstat_cache = array();

	$userstatic = new User($db);
	$objectstatic = new CommandeFournisseur($db);
	$projectstatic = new Project($db);

	$i = 0;
	$savnbfield = $totalarray['nbfield'];
	$totalarray = array('nbfield' => 0, 'val' => array(), 'pos' => array());
	$totalarray['val']['cf.total_ht'] = 0;
	$totalarray['val']['cf.total_ttc'] = 0;
	$totalarray['val']['cf.total_tva'] = 0;

	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$obj = $db->fetch_object($resql);

		$notshippable = 0;
		$warning = 0;
		$text_info = '';
		$text_warning = '';
		$nbprod = 0;

		$objectstatic->id = $obj->rowid;
		$objectstatic->ref = $obj->ref;
		$objectstatic->ref_supplier = $obj->ref_supplier;
		$objectstatic->socid = $obj->socid;
		$objectstatic->total_ht = $obj->total_ht;
		$objectstatic->total_tva = $obj->total_tva;
		$objectstatic->total_ttc = $obj->total_ttc;
		$objectstatic->date_commande = $db->jdate($obj->date_commande);
		$objectstatic->delivery_date = $db->jdate($obj->delivery_date);
		$objectstatic->note_public = $obj->note_public;
		$objectstatic->note_private = $obj->note_private;
		$objectstatic->statut = $obj->fk_statut;

		if ($mode == 'kanban') {
			if ($i == 0) {
				print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
				print '<div class="box-flex-container kanban">';
			}

			$thirdpartytmp->id = $obj->socid;
			$thirdpartytmp->name = $obj->name;
			$thirdpartytmp->email = $obj->email;
			$thirdpartytmp->name_alias = $obj->alias;
			$thirdpartytmp->client = $obj->client;
			$thirdpartytmp->fournisseur = $obj->fournisseur;
			// Output Kanban
			print $objectstatic->getKanbanView('', array('thirdparty' => $thirdpartytmp->getNomUrl('supplier', 0, 0, -1), 'selected' => in_array($objectstatic->id, $arrayofselected)));
			if ($i == ($imaxinloop - 1)) {
				print '</div>';
				print '</td></tr>';
			}
		} else {
			print '<tr class="oddeven '.((getDolGlobalInt('MAIN_FINISHED_LINES_OPACITY') == 1 && $obj->billed == 1) ? 'opacitymedium' : '').'">';
			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($obj->rowid, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
				}
				print '</td>';
			}
			// Ref
			if (!empty($arrayfields['cf.ref']['checked'])) {
				print '<td class="nowrap">';

				// Picto + Ref
				print $objectstatic->getNomUrl(1, '', 0, -1, 1);
				// Other picto tool
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->fournisseur->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);

				print '</td>'."\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Ref Supplier
			if (!empty($arrayfields['cf.ref_supplier']['checked'])) {
				print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->ref_supplier).'">'.dol_escape_htmltag($obj->ref_supplier).'</td>'."\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Project
			if (!empty($arrayfields['cf.fk_projet']['checked'])) {
				$projectstatic->id = $obj->project_id;
				$projectstatic->ref = $obj->project_ref;
				$projectstatic->title = $obj->project_title;
				print '<td>';
				if ($obj->project_id > 0) {
					print $projectstatic->getNomUrl(1);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Author
			$userstatic->id = $obj->fk_user_author;
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;
			$userstatic->login = $obj->login;
			$userstatic->photo = $obj->photo;
			$userstatic->email = $obj->user_email;
			$userstatic->status = $obj->user_status;
			if (!empty($arrayfields['u.login']['checked'])) {
				print '<td class="tdoverflowmax150">';
				if ($userstatic->id) {
					print $userstatic->getNomUrl(1);
				}
				print "</td>";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Thirdparty
			if (!empty($arrayfields['cf.fk_soc']['checked'])) {
				print '<td class="tdoverflowmax150">';
				$thirdpartytmp->id = $obj->socid;
				$thirdpartytmp->name = $obj->name;
				$thirdpartytmp->email = $obj->email;
				$thirdpartytmp->name_alias = $obj->alias;
				$thirdpartytmp->client = $obj->client;
				$thirdpartytmp->fournisseur = $obj->fournisseur;
				print $thirdpartytmp->getNomUrl(1, 'supplier', 0, 0, -1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
				print '</td>'."\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Alias
			if (!empty($arrayfields['s.name_alias']['checked'])) {
				print '<td class="tdoverflowmax150">';
				print $obj->alias;
				print '</td>'."\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Town
			if (!empty($arrayfields['s.town']['checked'])) {
				print '<td>';
				print $obj->town;
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Zip
			if (!empty($arrayfields['s.zip']['checked'])) {
				print '<td>';
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
			if (!empty($arrayfields['cf.date_commande']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_commande), 'day');
				if ($objectstatic->statut != $objectstatic::STATUS_ORDERSENT && $objectstatic->statut != $objectstatic::STATUS_RECEIVED_PARTIALLY) {
					if ($objectstatic->hasDelay()) {
						print ' '.img_picto($langs->trans("Late").' : '.$objectstatic->showDelay(), "warning");
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Plannned date of delivery
			if (!empty($arrayfields['cf.date_livraison']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->delivery_date), 'day');
				if ($objectstatic->statut == $objectstatic::STATUS_ORDERSENT || $objectstatic->statut == $objectstatic::STATUS_RECEIVED_PARTIALLY) {
					if ($objectstatic->hasDelay()) {
						print ' '.img_picto($langs->trans("Late").' : '.$objectstatic->showDelay(), "warning");
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount HT
			if (!empty($arrayfields['cf.total_ht']['checked'])) {
				print '<td class="right"><span class="amount">'.price($obj->total_ht)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'cf.total_ht';
				}
				$totalarray['val']['cf.total_ht'] += $obj->total_ht;
			}
			// Amount VAT
			if (!empty($arrayfields['cf.total_tva']['checked'])) {
				print '<td class="right"><span class="amount">'.price($obj->total_tva)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'cf.total_tva';
				}
				$totalarray['val']['cf.total_tva'] += $obj->total_tva;
			}
			// Amount TTC
			if (!empty($arrayfields['cf.total_ttc']['checked'])) {
				print '<td class="right"><span class="amount">'.price($obj->total_ttc)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'cf.total_ttc';
				}
				$totalarray['val']['cf.total_ttc'] += $obj->total_ttc;
			}

			// Currency
			if (!empty($arrayfields['cf.multicurrency_code']['checked'])) {
				print '<td class="nowrap">'.$obj->multicurrency_code.' - '.$langs->trans('Currency'.$obj->multicurrency_code)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Currency rate
			if (!empty($arrayfields['cf.multicurrency_tx']['checked'])) {
				print '<td class="nowrap">';
				$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount HT
			if (!empty($arrayfields['cf.multicurrency_total_ht']['checked'])) {
				print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ht)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount VAT
			if (!empty($arrayfields['cf.multicurrency_total_tva']['checked'])) {
				print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_tva)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount TTC
			if (!empty($arrayfields['cf.multicurrency_total_ttc']['checked'])) {
				print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ttc)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (!empty($arrayfields['cf.date_creation']['checked'])) {
				print '<td class="center nowraponall">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date modification
			if (!empty($arrayfields['cf.tms']['checked'])) {
				print '<td class="center nowraponall">';
				print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Billed
			if (!empty($arrayfields['cf.billed']['checked'])) {
				print '<td class="center">'.yn($obj->billed).'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Status
			if (!empty($arrayfields['cf.fk_statut']['checked'])) {
				print '<td class="center nowrap">'.$objectstatic->LibStatut($obj->fk_statut, 5, $obj->billed).'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// valid date
			if (!empty($arrayfields['cf.date_valid']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_valid), 'day');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// approve date
			if (!empty($arrayfields['cf.date_approve']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_approve), 'day');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Note public
			if (!empty($arrayfields['cf.note_public']['checked'])) {
				print '<td class="sensiblehtmlcontent center">';
				print dolPrintHTML($obj->note_public);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Note private
			if (!empty($arrayfields['cf.note_private']['checked'])) {
				print '<td class="sensiblehtmlcontent center">';
				print dolPrintHTML($obj->note_private);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($obj->rowid, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
				}
				print '</td>';
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}

			print "</tr>\n";

			$total += $obj->total_ht;
			$subtotal += $obj->total_ht;
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

	print $formfile->showdocuments('massfilesarea_supplier_order', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();

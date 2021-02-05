<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2018-2020 Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
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
 *   \file       htdocs/fourn/commande/list.php
 *   \ingroup    fournisseur
 *   \brief      List of purchase orders
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->loadLangs(array("orders", "sendings", 'deliveries', 'companies', 'compta', 'bills', 'projects', 'suppliers', 'products'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'supplierorderlist';

$search_orderyear = GETPOST("search_orderyear", "int");
$search_ordermonth = GETPOST("search_ordermonth", "int");
$search_orderday = GETPOST("search_orderday", "int");
$search_deliveryyear = GETPOST("search_deliveryyear", "int");
$search_deliverymonth = GETPOST("search_deliverymonth", "int");
$search_deliveryday = GETPOST("search_deliveryday", "int");

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

$search_product_category = GETPOST('search_product_category', 'int');
$search_ref = GETPOST('search_ref', 'alpha');
$search_refsupp = GETPOST('search_refsupp', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state", 'alpha');
$search_country = GETPOST("search_country", 'int');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$search_user = GETPOST('search_user', 'int');
$search_request_author = GETPOST('search_request_author', 'alpha');
$search_ht = GETPOST('search_ht', 'alpha');
$search_ttc = GETPOST('search_ttc', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$socid = GETPOST('socid', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_total_ht = GETPOST('search_total_ht', 'alpha');
$search_total_vat = GETPOST('search_total_vat', 'alpha');
$search_total_ttc = GETPOST('search_total_ttc', 'alpha');
$search_multicurrency_code = GETPOST('search_multicurrency_code', 'alpha');
$search_multicurrency_tx = GETPOST('search_multicurrency_tx', 'alpha');
$search_multicurrency_montant_ht = GETPOST('search_multicurrency_montant_ht', 'alpha');
$search_multicurrency_montant_vat = GETPOST('search_multicurrency_montant_vat', 'alpha');
$search_multicurrency_montant_ttc = GETPOST('search_multicurrency_montant_ttc', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$search_billed = GETPOST('search_billed', 'int');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');

if (is_array(GETPOST('search_status', 'intcomma'))) {
	$search_status = join(',', GETPOST('search_status', 'intcomma'));
} else {
	$search_status = (GETPOST('search_status', 'intcomma') != '' ? GETPOST('search_status', 'intcomma') : GETPOST('statut', 'intcomma'));
}

// Security check
$orderid = GETPOST('orderid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'fournisseur', $orderid, '', 'commande');

$diroutputmassaction = $conf->fournisseur->commande->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'cf.ref';
if (!$sortorder) $sortorder = 'DESC';

if ($search_status == '') $search_status = -1;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new CommandeFournisseur($db);
$hookmanager->initHooks(array('supplierorderlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'cf.ref'=>'Ref',
	'cf.ref_supplier'=>'RefOrderSupplier',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	's.name_alias'=>"AliasNameShort",
	's.zip'=>"Zip",
	's.town'=>"Town",
	'cf.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["cf.note_private"] = "NotePrivate";

$checkedtypetiers = 0;
$arrayfields = array(
	'cf.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'cf.ref_supplier'=>array('label'=>$langs->trans("RefOrderSupplierShort"), 'checked'=>1, 'enabled'=>1),
	'p.project_ref'=>array('label'=>$langs->trans("ProjectRef"), 'checked'=>0, 'enabled'=>1),
	'u.login'=>array('label'=>$langs->trans("AuthorRequest"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'cf.date_commande'=>array('label'=>$langs->trans("OrderDateShort"), 'checked'=>1),
	'cf.date_delivery'=>array('label'=>$langs->trans("DateDeliveryPlanned"), 'checked'=>1, 'enabled'=>empty($conf->global->ORDER_DISABLE_DELIVERY_DATE)),
	'cf.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	'cf.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
	'cf.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
	'cf.multicurrency_code'=>array('label'=>'Currency', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'cf.multicurrency_tx'=>array('label'=>'CurrencyRate', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'cf.multicurrency_total_ht'=>array('label'=>'MulticurrencyAmountHT', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'cf.multicurrency_total_vat'=>array('label'=>'MulticurrencyAmountVAT', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'cf.multicurrency_total_ttc'=>array('label'=>'MulticurrencyAmountTTC', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'cf.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'cf.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'cf.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
	'cf.billed'=>array('label'=>$langs->trans("Billed"), 'checked'=>1, 'position'=>1000, 'enabled'=>1)
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$error = 0;


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createsupplierbills') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$search_categ = '';
		$search_user = '';
		$search_sale = '';
		$search_product_category = '';
		$search_ref = '';
		$search_refsupp = '';
		$search_company = '';
		$search_town = '';
		$search_zip = "";
		$search_state = "";
		$search_type = '';
		$search_country = '';
		$search_type_thirdparty = '';
		$search_request_author = '';
		$search_total_ht = '';
		$search_total_vat = '';
		$search_total_ttc = '';
		$search_multicurrency_code = '';
		$search_multicurrency_tx = '';
		$search_multicurrency_montant_ht = '';
		$search_multicurrency_montant_vat = '';
		$search_multicurrency_montant_ttc = '';
		$search_project_ref = '';
		$search_status = -1;
		$search_orderyear = '';
		$search_ordermonth = '';
		$search_orderday = '';
		$search_deliveryday = '';
		$search_deliverymonth = '';
		$search_deliveryyear = '';
		$billed = '';
		$search_billed = '';
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'CommandeFournisseur';
	$objectlabel = 'SupplierOrders';
	$permissiontoread = $user->rights->fournisseur->commande->lire;
	$permissiontodelete = $user->rights->fournisseur->commande->supprimer;
	$uploaddir = $conf->fournisseur->commande->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	// TODO Move this into mass action include
	if ($massaction == 'confirm_createsupplierbills')
	{
		$orders = GETPOST('toselect', 'array');
		$createbills_onebythird = GETPOST('createbills_onebythird', 'int');
		$validate_invoices = GETPOST('validate_invoices', 'int');

		$TFact = array();
		$TFactThird = array();

		$nb_bills_created = 0;

		$db->begin();

		foreach ($orders as $id_order) {
			$cmd = new CommandeFournisseur($db);
			if ($cmd->fetch($id_order) <= 0) continue;

			$objecttmp = new FactureFournisseur($db);
			if (!empty($createbills_onebythird) && !empty($TFactThird[$cmd->socid])) $objecttmp = $TFactThird[$cmd->socid]; // If option "one bill per third" is set, we use already created order.
			else {
				$objecttmp->socid = $cmd->socid;
				$objecttmp->type = $objecttmp::TYPE_STANDARD;
				$objecttmp->cond_reglement_id	= $cmd->cond_reglement_id;
				$objecttmp->mode_reglement_id	= $cmd->mode_reglement_id;
				$objecttmp->fk_project = $cmd->fk_project;
				$objecttmp->multicurrency_code = $cmd->multicurrency_code;
				if (empty($createbills_onebythird)) $objecttmp->ref_client = $cmd->ref_client;

				$datefacture = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
				if (empty($datefacture))
				{
					$datefacture = dol_now();
				}

				$objecttmp->date = $datefacture;
				$objecttmp->origin    = 'order_supplier';
				$objecttmp->origin_id = $id_order;

				$res = $objecttmp->create($user);

				if ($res > 0) $nb_bills_created++;
			}

			if ($objecttmp->id > 0)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
				$sql .= "fk_source";
				$sql .= ", sourcetype";
				$sql .= ", fk_target";
				$sql .= ", targettype";
				$sql .= ") VALUES (";
				$sql .= $id_order;
				$sql .= ", '".$db->escape($objecttmp->origin)."'";
				$sql .= ", ".$objecttmp->id;
				$sql .= ", '".$db->escape($objecttmp->element)."'";
				$sql .= ")";

				if (!$db->query($sql))
				{
					$erorr++;
				}

				if (!$error)
				{
					$lines = $cmd->lines;
					if (empty($lines) && method_exists($cmd, 'fetch_lines'))
					{
						$cmd->fetch_lines();
						$lines = $cmd->lines;
					}

					$fk_parent_line = 0;
					$num = count($lines);

					for ($i = 0; $i < $num; $i++)
					{
						$desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->libelle);
						if ($lines[$i]->subprice < 0)
						{
							// Negative line, we create a discount line
							$discount = new DiscountAbsolute($db);
							$discount->fk_soc = $objecttmp->socid;
							$discount->amount_ht = abs($lines[$i]->total_ht);
							$discount->amount_tva = abs($lines[$i]->total_tva);
							$discount->amount_ttc = abs($lines[$i]->total_ttc);
							$discount->tva_tx = $lines[$i]->tva_tx;
							$discount->fk_user = $user->id;
							$discount->description = $desc;
							$discountid = $discount->create($user);
							if ($discountid > 0)
							{
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
							if ($lines[$i]->date_debut_prevue) $date_start = $lines[$i]->date_debut_prevue;
							if ($lines[$i]->date_debut_reel) $date_start = $lines[$i]->date_debut_reel;
							if ($lines[$i]->date_start) $date_start = $lines[$i]->date_start;
							//Date end
							$date_end = false;
							if ($lines[$i]->date_fin_prevue) $date_end = $lines[$i]->date_fin_prevue;
							if ($lines[$i]->date_fin_reel) $date_end = $lines[$i]->date_fin_reel;
							if ($lines[$i]->date_end) $date_end = $lines[$i]->date_end;
							// Reset fk_parent_line for no child products and special product
							if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9)
							{
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
								$lines[$i]->rang,
								false,
								$lines[$i]->array_options,
								$lines[$i]->fk_unit,
								$objecttmp->origin_id,
								$lines[$i]->pa_ht,
								$lines[$i]->ref_supplier,
								$lines[$i]->special_code,
								$fk_parent_line
							);
							if ($result > 0)
							{
								$lineid = $result;
							} else {
								$lineid = 0;
								$error++;
								break;
							}
							// Defined the new fk_parent_line
							if ($result > 0 && $lines[$i]->product_type == 9)
							{
								$fk_parent_line = $result;
							}
						}
					}
				}
			}

			$cmd->classifyBilled($user); // TODO Move this in workflow like done for customer orders

			if (!empty($createbills_onebythird) && empty($TFactThird[$cmd->socid])) $TFactThird[$cmd->socid] = $objecttmp;
			else $TFact[$objecttmp->id] = $objecttmp;
		}

		// Build doc with all invoices
		$TAllFact = empty($createbills_onebythird) ? $TFact : $TFactThird;
		$toselect = array();

		if (!$error && $validate_invoices) {
			$massaction = $action = 'builddoc';

			foreach ($TAllFact as &$objecttmp)
			{
				$objecttmp->validate($user);
				if ($result <= 0)
				{
					$error++;
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					break;
				}

				$id = $objecttmp->id; // For builddoc action

				// Fac builddoc
				$donotredirect = 1;
				$upload_dir = $conf->fournisseur->facture->dir_output;
				$permissiontoadd = $user->rights->fournisseur->facture->creer;
				//include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
			}

			$massaction = $action = 'confirm_createsupplierbills';
		}

		if (!$error)
		{
			$db->commit();
			setEventMessages($langs->trans('BillCreated', $nb_bills_created), null, 'mesgs');

			// Make a redirect to avoid to bill twice if we make a refresh or back
			$param = '';
			if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
			if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
			if ($sall)					$param .= '&sall='.urlencode($sall);
			if ($socid > 0)             $param .= '&socid='.urlencode($socid);
			if ($search_status != '')      $param .= '&search_status='.urlencode($search_status);
			if ($search_orderday)      		$param .= '&search_orderday='.urlencode($search_orderday);
			if ($search_ordermonth)      		$param .= '&search_ordermonth='.urlencode($search_ordermonth);
			if ($search_orderyear)       		$param .= '&search_orderyear='.urlencode($search_orderyear);
			if ($search_deliveryday)   		$param .= '&search_deliveryday='.urlencode($search_deliveryday);
			if ($search_deliverymonth)   		$param .= '&search_deliverymonth='.urlencode($search_deliverymonth);
			if ($search_deliveryyear)    		$param .= '&search_deliveryyear='.urlencode($search_deliveryyear);
			if ($search_ref)      		$param .= '&search_ref='.urlencode($search_ref);
			if ($search_company)  		$param .= '&search_company='.urlencode($search_company);
			if ($search_ref_customer)	$param .= '&search_ref_customer='.urlencode($search_ref_customer);
			if ($search_user > 0) 		$param .= '&search_user='.urlencode($search_user);
			if ($search_sale > 0) 		$param .= '&search_sale='.urlencode($search_sale);
			if ($search_total_ht != '') $param .= '&search_total_ht='.urlencode($search_total_ht);
			if ($search_total_vat != '') $param .= '&search_total_vat='.urlencode($search_total_vat);
			if ($search_total_ttc != '') $param .= '&search_total_ttc='.urlencode($search_total_ttc);
			if ($search_project_ref >= 0)  	$param .= "&search_project_ref=".urlencode($search_project_ref);
			if ($show_files)            $param .= '&show_files='.urlencode($show_files);
			if ($optioncss != '')       $param .= '&optioncss='.urlencode($optioncss);
			if ($billed != '')			$param .= '&billed='.urlencode($billed);

			header("Location: ".$_SERVER['PHP_SELF'].'?'.$param);
			exit;
		} else {
			$db->rollback();
			$action = 'create';
			$_GET["origin"] = $_POST["origin"];
			$_GET["originid"] = $_POST["originid"];
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

$title = $langs->trans("ListOfSupplierOrders");
if ($socid > 0)
{
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
if ($search_billed > 0) $title .= ' - '.$langs->trans("Billed");

//$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
$help_url = '';
// llxHeader('',$title,$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0 || $search_user > 0) $sql = 'SELECT DISTINCT';
$sql .= ' s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client, s.email,';
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " cf.rowid, cf.ref, cf.ref_supplier, cf.fk_statut, cf.billed, cf.total_ht, cf.tva as total_tva, cf.total_ttc, cf.fk_user_author, cf.date_commande as date_commande, cf.date_livraison as date_delivery,";
$sql .= ' cf.fk_multicurrency, cf.multicurrency_code, cf.multicurrency_tx, cf.multicurrency_total_ht, cf.multicurrency_total_tva as multicurrency_total_vat, cf.multicurrency_total_ttc,';
$sql .= ' cf.date_creation as date_creation, cf.tms as date_update,';
$sql .= ' cf.note_public, cf.note_private,';
$sql .= " p.rowid as project_id, p.ref as project_ref, p.title as project_title,";
$sql .= " u.firstname, u.lastname, u.photo, u.login, u.email as user_email";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (cf.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet as pd ON cf.rowid=pd.fk_commande';
if ($search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON cf.fk_user_author = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = cf.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->rights->societe->client->voir && !$socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' WHERE cf.fk_soc = s.rowid';
$sql .= ' AND cf.entity IN ('.getEntity('supplier_order').')';
if ($socid > 0) $sql .= " AND s.rowid = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($search_ref) $sql .= natural_search('cf.ref', $search_ref);
if ($search_refsupp) $sql .= natural_search("cf.ref_supplier", $search_refsupp);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_request_author) $sql .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_request_author);
if ($search_billed != '' && $search_billed >= 0) $sql .= " AND cf.billed = ".$db->escape($search_billed);
if ($search_product_category > 0) $sql .= " AND cp.fk_categorie = ".$search_product_category;
//Required triple check because statut=0 means draft filter
if (GETPOST('statut', 'intcomma') !== '')
	$sql .= " AND cf.fk_statut IN (".$db->sanitize($db->escape($db->escape(GETPOST('statut', 'intcomma')))).")";
if ($search_status != '' && $search_status != '-1')
	$sql .= " AND cf.fk_statut IN (".$db->sanitize($db->escape($search_status)).")";
$sql .= dolSqlDateFilter("cf.date_commande", $search_orderday, $search_ordermonth, $search_orderyear);
$sql .= dolSqlDateFilter("cf.date_livraison", $search_deliveryday, $search_deliverymonth, $search_deliveryyear);
if ($search_town)  $sql .= natural_search('s.town', $search_town);
if ($search_zip)   $sql .= natural_search("s.zip", $search_zip);
if ($search_state) $sql .= natural_search("state.nom", $search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$db->sanitize($db->escape($search_country)).')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$db->sanitize($db->escape($search_type_thirdparty)).')';
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_sale > 0) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$db->escape($search_sale);
if ($search_user > 0) $sql .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='supplier_order' AND tc.source='internal' AND ec.element_id = cf.rowid AND ec.fk_socpeople = ".$db->escape($search_user);
if ($search_total_ht != '') $sql .= natural_search('cf.total_ht', $search_total_ht, 1);
if ($search_total_vat != '') $sql .= natural_search('cf.tva', $search_total_vat, 1);
if ($search_total_ttc != '') $sql .= natural_search('cf.total_ttc', $search_total_ttc, 1);
if ($search_multicurrency_code != '')        $sql .= ' AND cf.multicurrency_code = "'.$db->escape($search_multicurrency_code).'"';
if ($search_multicurrency_tx != '')          $sql .= natural_search('cf.multicurrency_tx', $search_multicurrency_tx, 1);
if ($search_multicurrency_montant_ht != '')  $sql .= natural_search('cf.multicurrency_total_ht', $search_multicurrency_montant_ht, 1);
if ($search_multicurrency_montant_vat != '') $sql .= natural_search('cf.multicurrency_total_tva', $search_multicurrency_montant_vat, 1);
if ($search_multicurrency_montant_ttc != '') $sql .= natural_search('cf.multicurrency_total_ttc', $search_multicurrency_montant_ttc, 1);
if ($search_project_ref != '') $sql .= natural_search("p.ref", $search_project_ref);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".DOL_URL_ROOT.'/fourn/commande/card.php?id='.$id);
		exit;
	}

	llxHeader('', $title, $help_url);

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall)					$param .= '&sall='.urlencode($sall);
	if ($socid > 0)             $param .= '&socid='.urlencode($socid);
	if ($sall)					$param .= "&search_all=".urlencode($sall);
	if ($search_orderday)      	$param .= '&search_orderday='.urlencode($search_orderday);
	if ($search_ordermonth)     $param .= '&search_ordermonth='.urlencode($search_ordermonth);
	if ($search_orderyear)      $param .= '&search_orderyear='.urlencode($search_orderyear);
	if ($search_deliveryday)   	$param .= '&search_deliveryday='.urlencode($search_deliveryday);
	if ($search_deliverymonth)  $param .= '&search_deliverymonth='.urlencode($search_deliverymonth);
	if ($search_deliveryyear)   $param .= '&search_deliveryyear='.urlencode($search_deliveryyear);
	if ($search_ref)      		$param .= '&search_ref='.urlencode($search_ref);
	if ($search_company)  		$param .= '&search_company='.urlencode($search_company);
	if ($search_user > 0) 		$param .= '&search_user='.urlencode($search_user);
	if ($search_request_author) $param .= '&search_request_author='.urlencode($search_request_author);
	if ($search_sale > 0) 		$param .= '&search_sale='.urlencode($search_sale);
	if ($search_total_ht != '') $param .= '&search_total_ht='.urlencode($search_total_ht);
	if ($search_total_ttc != '') $param .= "&search_total_ttc=".urlencode($search_total_ttc);
	if ($search_multicurrency_code != '')  $param .= '&search_multicurrency_code='.urlencode($search_multicurrency_code);
	if ($search_multicurrency_tx != '')  $param .= '&search_multicurrency_tx='.urlencode($search_multicurrency_tx);
	if ($search_multicurrency_montant_ht != '')  $param .= '&search_multicurrency_montant_ht='.urlencode($search_multicurrency_montant_ht);
	if ($search_multicurrency_montant_vat != '')  $param .= '&search_multicurrency_montant_vat='.urlencode($search_multicurrency_montant_vat);
	if ($search_multicurrency_montant_ttc != '') $param .= '&search_multicurrency_montant_ttc='.urlencode($search_multicurrency_montant_ttc);
	if ($search_refsupp) 		$param .= "&search_refsupp=".urlencode($search_refsupp);
	if ($search_status != '' && $search_status != '-1') $param .= "&search_status=".urlencode($search_status);
	if ($search_project_ref >= 0) $param .= "&search_project_ref=".urlencode($search_project_ref);
	if ($search_billed != '')   $param .= "&search_billed=".urlencode($search_billed);
	if ($show_files)            $param .= '&show_files='.urlencode($show_files);
	if ($optioncss != '')       $param .= '&optioncss='.urlencode($optioncss);
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
	$param .= $hookmanager->resPrint;

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
		'builddoc'=>$langs->trans("PDFMerge"),
		'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->fournisseur->facture->creer) $arrayofmassactions['createbills'] = $langs->trans("CreateInvoiceForThisSupplier");
	if ($user->rights->fournisseur->commande->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete', 'createbills'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$url = DOL_URL_ROOT.'/fourn/commande/card.php?action=create';
	if (!empty($socid)) $url .= '&socid='.$socid;
	$newcardbutton = dolGetButtonTitle($langs->trans('NewOrder'), '', 'fa fa-plus-circle', $url, '', $user->rights->fournisseur->commande->creer);

	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'supplier_order', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "SendOrderRef";
	$modelmail = "order_supplier_send";
	$objecttmp = new CommandeFournisseur($db);
	$trackid = 'sord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($massaction == 'createbills')
	{
		//var_dump($_REQUEST);
		print '<input type="hidden" name="massaction" value="confirm_createsupplierbills">';

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
		print $form->selectyesno('validate_invoices', 1, 1);
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

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';

	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
		$langs->load("commercial");
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('ThirdPartiesOfSaleRepresentative').': ';
		$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
		$moreforfilter .= '</div>';
	}
	// If the user can view other users
	if ($user->rights->user->user->lire)
	{
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('LinkedToSpecificUsers').': ';
		$moreforfilter .= $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
		$moreforfilter .= '</div>';
	}
	// If the user can view prospects other than his'
	if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('IncludingProductWithTag').': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter .= $form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
		$moreforfilter .= '</div>';
	}
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (!empty($moreforfilter))
	{
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
	if (!empty($arrayfields['cf.ref']['checked']))
	{
		print '<td class="liste_titre"><input size="8" type="text" class="flat maxwidth75" name="search_ref" value="'.$search_ref.'"></td>';
	}
	// Ref customer
	if (!empty($arrayfields['cf.ref_supplier']['checked']))
	{
		print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_refsupp" value="'.$search_refsupp.'"></td>';
	}
	// Project ref
	if (!empty($arrayfields['p.project_ref']['checked']))
	{
		print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_project_ref" value="'.$search_project_ref.'"></td>';
	}
	// Request author
	if (!empty($arrayfields['u.login']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="6" name="search_request_author" value="'.$search_request_author.'">';
		print '</td>';
	}
	// Thirpdarty
	if (!empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre"><input type="text" size="6" class="flat" name="search_company" value="'.$search_company.'"></td>';
	}
	// Town
	if (!empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_town" value="'.$search_town.'"></td>';
	// Zip
	if (!empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_zip" value="'.$search_zip.'"></td>';
	// State
	if (!empty($arrayfields['state.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
		print '</td>';
	}
	// Country
	if (!empty($arrayfields['country.code_iso']['checked']))
	{
		print '<td class="liste_titre center">';
		print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
		print '</td>';
	}
	// Company type
	if (!empty($arrayfields['typent.code']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT));
		print '</td>';
	}
	// Date order
	if (!empty($arrayfields['cf.date_commande']['checked']))
	{
		print '<td class="liste_titre nowraponall center">';
		if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_orderday" value="'.$search_orderday.'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_ordermonth" value="'.$search_ordermonth.'">';
		$formother->select_year($search_orderyear ? $search_orderyear : -1, 'search_orderyear', 1, 20, 5);
		print '</td>';
	}
	// Date delivery
	if (!empty($arrayfields['cf.date_delivery']['checked']))
	{
		print '<td class="liste_titre nowraponall center">';
		if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_deliveryday" value="'.$search_deliveryday.'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_deliverymonth" value="'.$search_deliverymonth.'">';
		$formother->select_year($search_deliveryyear ? $search_deliveryyear : -1, 'search_deliveryyear', 1, 20, 5);
		print '</td>';
	}
	if (!empty($arrayfields['cf.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_total_ht" value="'.$search_total_ht.'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_total_vat" value="'.$search_total_vat.'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_total_ttc" value="'.$search_total_ttc.'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_code']['checked']))
	{
		// Currency
		print '<td class="liste_titre">';
		print $form->selectMultiCurrency($search_multicurrency_code, 'search_multicurrency_code', 1);
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_tx']['checked']))
	{
		// Currency rate
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_tx" value="'.dol_escape_htmltag($search_multicurrency_tx).'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ht" value="'.dol_escape_htmltag($search_multicurrency_montant_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_vat" value="'.dol_escape_htmltag($search_multicurrency_montant_vat).'">';
		print '</td>';
	}
	if (!empty($arrayfields['cf.multicurrency_total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ttc" value="'.dol_escape_htmltag($search_multicurrency_montant_ttc).'">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['cf.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['cf.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (!empty($arrayfields['cf.fk_statut']['checked']))
	{
		print '<td class="liste_titre right">';
		$formorder->selectSupplierOrderStatus($search_status, 1, 'search_status');
		print '</td>';
	}
	// Status billed
	if (!empty($arrayfields['cf.billed']['checked']))
	{
		print '<td class="liste_titre center">';
		print $form->selectyesno('search_billed', $search_billed, 1, 0, 1, 1);
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';
	if (!empty($arrayfields['cf.ref']['checked']))            print_liste_field_titre($arrayfields['cf.ref']['label'], $_SERVER["PHP_SELF"], "cf.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['cf.ref_supplier']['checked']))   print_liste_field_titre($arrayfields['cf.ref_supplier']['label'], $_SERVER["PHP_SELF"], "cf.ref_supplier", "", $param, '', $sortfield, $sortorder, 'tdoverflowmax100imp ');
	if (!empty($arrayfields['p.project_ref']['checked'])) 	   print_liste_field_titre($arrayfields['p.project_ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['u.login']['checked'])) 	       print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], "u.login", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.nom']['checked']))             print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.town']['checked']))            print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.zip']['checked']))             print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['state.nom']['checked']))         print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['country.code_iso']['checked']))  print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['typent.code']['checked']))       print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['cf.fk_author']['checked']))      print_liste_field_titre($arrayfields['cf.fk_author']['label'], $_SERVER["PHP_SELF"], "cf.fk_author", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['cf.date_commande']['checked']))  print_liste_field_titre($arrayfields['cf.date_commande']['label'], $_SERVER["PHP_SELF"], "cf.date_commande", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['cf.date_delivery']['checked']))  print_liste_field_titre($arrayfields['cf.date_delivery']['label'], $_SERVER["PHP_SELF"], 'cf.date_livraison', '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['cf.total_ht']['checked']))       print_liste_field_titre($arrayfields['cf.total_ht']['label'], $_SERVER["PHP_SELF"], "cf.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['cf.total_vat']['checked']))      print_liste_field_titre($arrayfields['cf.total_vat']['label'], $_SERVER["PHP_SELF"], "cf.tva", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['cf.total_ttc']['checked']))      print_liste_field_titre($arrayfields['cf.total_ttc']['label'], $_SERVER["PHP_SELF"], "cf.total_ttc", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['cf.multicurrency_code']['checked']))          print_liste_field_titre($arrayfields['cf.multicurrency_code']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_code', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['cf.multicurrency_tx']['checked']))            print_liste_field_titre($arrayfields['cf.multicurrency_tx']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_tx', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['cf.multicurrency_total_ht']['checked']))      print_liste_field_titre($arrayfields['cf.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['cf.multicurrency_total_vat']['checked']))     print_liste_field_titre($arrayfields['cf.multicurrency_total_vat']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_total_tva', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['cf.multicurrency_total_ttc']['checked']))     print_liste_field_titre($arrayfields['cf.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'cf.multicurrency_total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['cf.datec']['checked']))     print_liste_field_titre($arrayfields['cf.datec']['label'], $_SERVER["PHP_SELF"], "cf.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['cf.tms']['checked']))       print_liste_field_titre($arrayfields['cf.tms']['label'], $_SERVER["PHP_SELF"], "cf.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['cf.fk_statut']['checked'])) print_liste_field_titre($arrayfields['cf.fk_statut']['label'], $_SERVER["PHP_SELF"], "cf.fk_statut", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['cf.billed']['checked']))    print_liste_field_titre($arrayfields['cf.billed']['label'], $_SERVER["PHP_SELF"], 'cf.billed', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$total = 0;
	$subtotal = 0;
	$productstat_cache = array();

	$userstatic = new User($db);
	$objectstatic = new CommandeFournisseur($db);
	$projectstatic = new Project($db);

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$notshippable = 0;
		$warning = 0;
		$text_info = '';
		$text_warning = '';
		$nbprod = 0;

		$objectstatic->id = $obj->rowid;
		$objectstatic->ref = $obj->ref;
		$objectstatic->ref_supplier = $obj->ref_supplier;
		$objectstatic->total_ht = $obj->total_ht;
		$objectstatic->total_tva = $obj->total_tva;
		$objectstatic->total_ttc = $obj->total_ttc;
		$objectstatic->date_commande = $db->jdate($obj->date_commande);
		$objectstatic->date_delivery = $db->jdate($obj->date_delivery);
		$objectstatic->note_public = $obj->note_public;
		$objectstatic->note_private = $obj->note_private;
		$objectstatic->statut = $obj->fk_statut;

		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['cf.ref']['checked']))
		{
			print '<td class="nowrap">';

			// Picto + Ref
			print $objectstatic->getNomUrl(1, '', 0, -1, 1);
			// Other picto tool
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->fournisseur->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);

			print '</td>'."\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Ref Supplier
		if (!empty($arrayfields['cf.ref_supplier']['checked']))
		{
			print '<td>'.$obj->ref_supplier.'</td>'."\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Project
		if (!empty($arrayfields['p.project_ref']['checked']))
		{
			$projectstatic->id = $obj->project_id;
			$projectstatic->ref = $obj->project_ref;
			$projectstatic->title = $obj->project_title;
			print '<td>';
			if ($obj->project_id > 0) print $projectstatic->getNomUrl(1);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Author
		$userstatic->id = $obj->fk_user_author;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		$userstatic->login = $obj->login;
		$userstatic->photo = $obj->photo;
		$userstatic->email = $obj->user_email;
		if (!empty($arrayfields['u.login']['checked']))
		{
			print '<td class="tdoverflowmax150">';
			if ($userstatic->id) print $userstatic->getNomUrl(1);
			print "</td>";
			if (!$i) $totalarray['nbfield']++;
		}
		// Thirdparty
		if (!empty($arrayfields['s.nom']['checked']))
		{
			print '<td class="tdoverflowmax150">';
			$thirdpartytmp->id = $obj->socid;
			$thirdpartytmp->name = $obj->name;
			$thirdpartytmp->email = $obj->email;
			print $thirdpartytmp->getNomUrl(1, 'supplier');
			print '</td>'."\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Town
		if (!empty($arrayfields['s.town']['checked']))
		{
			print '<td>';
			print $obj->town;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Zip
		if (!empty($arrayfields['s.zip']['checked']))
		{
			print '<td>';
			print $obj->zip;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// State
		if (!empty($arrayfields['state.nom']['checked']))
		{
			print "<td>".$obj->state_name."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Country
		if (!empty($arrayfields['country.code_iso']['checked']))
		{
			print '<td class="center">';
			$tmparray = getCountry($obj->fk_pays, 'all');
			print $tmparray['label'];
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Type ent
		if (!empty($arrayfields['typent.code']['checked']))
		{
			print '<td class="center">';
			if (count($typenArray) == 0) $typenArray = $formcompany->typent_array(1);
			print $typenArray[$obj->typent_code];
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Order date
		if (!empty($arrayfields['cf.date_commande']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_commande), 'day');
			if ($objectstatic->hasDelay() && !empty($objectstatic->date_delivery)) {
				print ' '.img_picto($langs->trans("Late").' : '.$objectstatic->showDelay(), "warning");
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Plannned date of delivery
		if (!empty($arrayfields['cf.date_delivery']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_delivery), 'day');
			if ($objectstatic->hasDelay() && !empty($objectstatic->date_delivery)) {
				print ' '.img_picto($langs->trans("Late").' : '.$objectstatic->showDelay(), "warning");
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Amount HT
		if (!empty($arrayfields['cf.total_ht']['checked']))
		{
			  print '<td class="right">'.price($obj->total_ht)."</td>\n";
			  if (!$i) $totalarray['nbfield']++;
			  if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'cf.total_ht';
			  $totalarray['val']['cf.total_ht'] += $obj->total_ht;
		}
		// Amount VAT
		if (!empty($arrayfields['cf.total_vat']['checked']))
		{
			print '<td class="right">'.price($obj->total_tva)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'cf.total_vat';
			$totalarray['val']['cf.total_vat'] += $obj->total_tva;
		}
		// Amount TTC
		if (!empty($arrayfields['cf.total_ttc']['checked']))
		{
			print '<td class="right">'.price($obj->total_ttc)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'cf.total_ttc';
			$totalarray['val']['cf.total_ttc'] += $obj->total_ttc;
		}

		// Currency
		if (!empty($arrayfields['cf.multicurrency_code']['checked']))
		{
			  print '<td class="nowrap">'.$obj->multicurrency_code.' - '.$langs->trans('Currency'.$obj->multicurrency_code)."</td>\n";
			  if (!$i) $totalarray['nbfield']++;
		}

		// Currency rate
		if (!empty($arrayfields['cf.multicurrency_tx']['checked']))
		{
			  print '<td class="nowrap">';
			  $form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
			  print "</td>\n";
			  if (!$i) $totalarray['nbfield']++;
		}
		// Amount HT
		if (!empty($arrayfields['cf.multicurrency_total_ht']['checked']))
		{
			  print '<td class="right nowrap">'.price($obj->multicurrency_total_ht)."</td>\n";
			  if (!$i) $totalarray['nbfield']++;
		}
		// Amount VAT
		if (!empty($arrayfields['cf.multicurrency_total_vat']['checked']))
		{
			print '<td class="right nowrap">'.price($obj->multicurrency_total_vat)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Amount TTC
		if (!empty($arrayfields['cf.multicurrency_total_ttc']['checked']))
		{
			print '<td class="right nowrap">'.price($obj->multicurrency_total_ttc)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['cf.datec']['checked']))
		{
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date modification
		if (!empty($arrayfields['cf.tms']['checked']))
		{
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Status
		if (!empty($arrayfields['cf.fk_statut']['checked']))
		{
			print '<td class="right nowrap">'.$objectstatic->LibStatut($obj->fk_statut, 5, $obj->billed).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Billed
		if (!empty($arrayfields['cf.billed']['checked']))
		{
			print '<td class="center">'.yn($obj->billed).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

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
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $user->rights->fournisseur->commande->lire;
	$delallowed = $user->rights->fournisseur->commande->creer;

	print $formfile->showdocuments('massfilesarea_supplier_order', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();

<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani			<acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2016-2018 Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2017-2018 Charlene Benke			<charlie@patas-monkey.com>
 * Copyright (C) 2018	   Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2019	   Alexandre Spangaro		<aspangaro@open-dsi.fr>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'propal', 'compta', 'bills', 'orders', 'products', 'deliveries', 'categories'));

$socid = GETPOST('socid', 'int');

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'proposallist';

$search_user = GETPOST('search_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_ref = GETPOST('sf_ref') ?GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_refcustomer = GETPOST('search_refcustomer', 'alpha');

$search_refproject = GETPOST('search_refproject', 'alpha');
$search_project = GETPOST('search_project', 'alpha');

$search_societe = GETPOST('search_societe', 'alpha');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_vat = GETPOST('search_montant_vat', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$search_multicurrency_code = GETPOST('search_multicurrency_code', 'alpha');
$search_multicurrency_tx = GETPOST('search_multicurrency_tx', 'alpha');
$search_multicurrency_montant_ht = GETPOST('search_multicurrency_montant_ht', 'alpha');
$search_multicurrency_montant_vat = GETPOST('search_multicurrency_montant_vat', 'alpha');
$search_multicurrency_montant_ttc = GETPOST('search_multicurrency_montant_ttc', 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_product_category = GETPOST('search_product_category', 'int');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state");
$search_country = GETPOST("search_country", 'int');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$search_date_start = dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'));
$search_date_end = dol_mktime(23, 59, 59, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'));
$search_dateend_start = dol_mktime(0, 0, 0, GETPOST('search_dateend_startmonth', 'int'), GETPOST('search_dateend_startday', 'int'), GETPOST('search_dateend_startyear', 'int'));
$search_dateend_end = dol_mktime(23, 59, 59, GETPOST('search_dateend_endmonth', 'int'), GETPOST('search_dateend_endday', 'int'), GETPOST('search_dateend_endyear', 'int'));
$search_datedelivery_start = dol_mktime(0, 0, 0, GETPOST('search_datedelivery_startmonth', 'int'), GETPOST('search_datedelivery_startday', 'int'), GETPOST('search_datedelivery_startyear', 'int'));
$search_datedelivery_end = dol_mktime(23, 59, 59, GETPOST('search_datedelivery_endmonth', 'int'), GETPOST('search_datedelivery_endday', 'int'), GETPOST('search_datedelivery_endyear', 'int'));
$search_availability = GETPOST('search_availability', 'int');
$search_categ_cus = GETPOST("search_categ_cus", 'int');
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');

$search_status = GETPOST('search_status', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$object_statut = GETPOST('search_statut', 'alpha');

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$mesg = (GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));


$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'p.ref';
if (!$sortorder) $sortorder = 'DESC';

// Security check
$module = 'propal';
$dbtable = '';
$objectid = '';
if (!empty($user->socid))	$socid = $user->socid;
if (!empty($socid))
{
	$objectid = $socid;
	$module = 'societe';
	$dbtable = '&societe';
}
$result = restrictedArea($user, $module, $objectid, $dbtable);

$diroutputmassaction = $conf->propal->multidir_output[$conf->entity].'/temp/massgeneration/'.$user->id;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Propal($db);
$hookmanager->initHooks(array('propallist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref'=>'Ref',
	'p.ref_client'=>'CustomerRef',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	's.name_alias'=>"AliasNameShort",
	's.zip'=>"Zip",
	's.town'=>"Town",
	'p.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["p.note_private"] = "NotePrivate";


$checkedtypetiers = 0;
$arrayfields = array(
	'p.ref'=>array('label'=>"Ref", 'checked'=>1),
	'p.ref_client'=>array('label'=>"RefCustomer", 'checked'=>1),
	'pr.ref'=>array('label'=>"ProjectRef", 'checked'=>1, 'enabled'=>(empty($conf->projet->enabled) ? 0 : 1)),
	'pr.title'=>array('label'=>"ProjectLabel", 'checked'=>0, 'enabled'=>(empty($conf->projet->enabled) ? 0 : 1)),
	's.nom'=>array('label'=>"ThirdParty", 'checked'=>1),
	's.town'=>array('label'=>"Town", 'checked'=>1),
	's.zip'=>array('label'=>"Zip", 'checked'=>1),
	'state.nom'=>array('label'=>"StateShort", 'checked'=>0),
	'country.code_iso'=>array('label'=>"Country", 'checked'=>0),
	'typent.code'=>array('label'=>"ThirdPartyType", 'checked'=>$checkedtypetiers),
	'p.date'=>array('label'=>"Date", 'checked'=>1),
	'p.fin_validite'=>array('label'=>"DateEnd", 'checked'=>1),
	'p.date_livraison'=>array('label'=>"DeliveryDate", 'checked'=>0),
	'ava.rowid'=>array('label'=>"AvailabilityPeriod", 'checked'=>0),
	'p.total_ht'=>array('label'=>"AmountHT", 'checked'=>1),
	'p.total_vat'=>array('label'=>"AmountVAT", 'checked'=>0),
	'p.total_ttc'=>array('label'=>"AmountTTC", 'checked'=>0),
	'p.total_ht_invoiced'=>array('label'=>"AmountInvoicedHT", 'checked'=>0, 'enabled'=>!empty($conf->global->PROPOSAL_SHOW_INVOICED_AMOUNT)),
	'p.total_invoiced'=>array('label'=>"AmountInvoicedTTC", 'checked'=>0, 'enabled'=>!empty($conf->global->PROPOSAL_SHOW_INVOICED_AMOUNT)),
	'p.multicurrency_code'=>array('label'=>'Currency', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'p.multicurrency_tx'=>array('label'=>'CurrencyRate', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'p.multicurrency_total_ht'=>array('label'=>'MulticurrencyAmountHT', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'p.multicurrency_total_vat'=>array('label'=>'MulticurrencyAmountVAT', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'p.multicurrency_total_ttc'=>array('label'=>'MulticurrencyAmountTTC', 'checked'=>0, 'enabled'=>(empty($conf->multicurrency->enabled) ? 0 : 1)),
	'p.multicurrency_total_ht_invoiced'=>array('label'=>'MulticurrencyAmountInvoicedHT', 'checked'=>0, 'enabled'=>!empty($conf->multicurrency->enabled) && !empty($conf->global->PROPOSAL_SHOW_INVOICED_AMOUNT)),
	'p.multicurrency_total_invoiced'=>array('label'=>'MulticurrencyAmountInvoicedTTC', 'checked'=>0, 'enabled'=>!empty($conf->multicurrency->enabled) && !empty($conf->global->PROPOSAL_SHOW_INVOICED_AMOUNT)),
	'u.login'=>array('label'=>"Author", 'checked'=>1, 'position'=>10),
	'sale_representative'=>array('label'=>"SaleRepresentativesOfThirdParty", 'checked'=>1),
	'p.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
	'p.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500),
	'p.date_cloture'=>array('label'=>"DateClosing", 'checked'=>0, 'position'=>500),
	'p.note_public'=>array('label'=>'NotePublic', 'checked'=>0, 'position'=>510, 'enabled'=>(empty($conf->global->MAIN_LIST_ALLOW_PUBLIC_NOTES))),
	'p.note_private'=>array('label'=>'NotePrivate', 'checked'=>0, 'position'=>511, 'enabled'=>(empty($conf->global->MAIN_LIST_ALLOW_PRIVATE_NOTES))),
	'p.fk_statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_categ = '';
	$search_user = '';
	$search_sale = '';
	$search_ref = '';
	$search_refcustomer = '';
	$search_refproject = '';
	$search_project = '';
	$search_societe = '';
	$search_montant_ht = '';
	$search_montant_vat = '';
	$search_montant_ttc = '';
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
	$search_date_start = '';
	$search_date_end = '';
	$search_dateend_start = '';
	$search_dateend_end = '';
	$search_datedelivery_start = '';
	$search_datedelivery_end = '';
	$search_availability = '';
	$search_status = '';
	$object_statut = '';
	$toselect = '';
	$search_array_options = array();
	$search_categ_cus = 0;
}
if ($object_statut != '') $search_status = $object_statut;

if (empty($reshook))
{
	$objectclass = 'Propal';
	$objectlabel = 'Proposals';
	$permissiontoread = $user->rights->propal->lire;
	$permissiontodelete = $user->rights->propal->supprimer;
	$permissiontoclose = $user->rights->propal->cloturer;
	$uploaddir = $conf->propal->multidir_output[$conf->entity];
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$companystatic = new Societe($db);
$projectstatic = new Project($db);
$formcompany = new FormCompany($db);

$help_url = 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos';
//llxHeader('',$langs->trans('Proposal'),$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql .= ' s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.code_client, ';
$sql .= " typent.code as typent_code,";
$sql .= " ava.rowid as availability,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= ' p.rowid, p.entity, p.note_private, p.total_ht, p.tva as total_vat, p.total as total_ttc, p.localtax1, p.localtax2, p.ref, p.ref_client, p.fk_statut as status, p.fk_user_author, p.datep as dp, p.fin_validite as dfv,p.date_livraison as ddelivery,';
$sql .= ' p.fk_multicurrency, p.multicurrency_code, p.multicurrency_tx, p.multicurrency_total_ht, p.multicurrency_total_tva as multicurrency_total_vat, p.multicurrency_total_ttc,';
$sql .= ' p.datec as date_creation, p.tms as date_update, p.date_cloture as date_cloture,';
$sql .= ' p.note_public, p.note_private,';
$sql .= " pr.rowid as project_id, pr.ref as project_ref, pr.title as project_label,";
$sql .= ' u.login';
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/, $/', '', $sql);
$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
if (!empty($search_categ_cus)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cc ON s.rowid = cc.fk_soc"; // We'll need this table joined to the select in order to filter by categ
$sql .= ', '.MAIN_DB_PREFIX.'propal as p';
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (p.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet as pd ON p.rowid=pd.fk_propal';
if ($search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as pr ON pr.rowid = p.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_availability as ava on (ava.rowid = p.fk_availability)";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->rights->societe->client->voir && !$socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as c";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql .= ' WHERE p.fk_soc = s.rowid';
$sql .= ' AND p.entity IN ('.getEntity('propal').')';
if (!$user->rights->societe->client->voir && !$socid) //restriction
{
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
}

if ($search_town)					$sql .= natural_search('s.town', $search_town);
if ($search_zip)					$sql .= natural_search("s.zip", $search_zip);
if ($search_state)					$sql .= natural_search("state.nom", $search_state);
if ($search_country)				$sql .= " AND s.fk_pays IN (".$db->sanitize($db->escape($search_country)).')';
if ($search_type_thirdparty)		$sql .= " AND s.fk_typent IN (".$db->sanitize($db->escape($search_type_thirdparty)).')';
if ($search_ref)					$sql .= natural_search('p.ref', $search_ref);
if ($search_refcustomer)			$sql .= natural_search('p.ref_client', $search_refcustomer);
if ($search_refproject)				$sql .= natural_search('pr.ref', $search_refproject);
if ($search_project)				$sql .= natural_search('pr.title', $search_project);
if ($search_availability)			$sql .= " AND p.fk_availability IN (".$db->sanitize($db->escape($search_availability)).')';

if ($search_societe)				$sql .= natural_search('s.nom', $search_societe);
if ($search_login)					$sql .= natural_search("u.login", $search_login);
if ($search_montant_ht != '')		$sql .= natural_search("p.total_ht", $search_montant_ht, 1);
if ($search_montant_vat != '')		$sql .= natural_search("p.tva", $search_montant_vat, 1);
if ($search_montant_ttc != '')		$sql .= natural_search("p.total", $search_montant_ttc, 1);
if ($search_multicurrency_code != '')        $sql .= ' AND p.multicurrency_code = "'.$db->escape($search_multicurrency_code).'"';
if ($search_multicurrency_tx != '')          $sql .= natural_search('p.multicurrency_tx', $search_multicurrency_tx, 1);
if ($search_multicurrency_montant_ht != '')  $sql .= natural_search('p.multicurrency_total_ht', $search_multicurrency_montant_ht, 1);
if ($search_multicurrency_montant_vat != '') $sql .= natural_search('p.multicurrency_total_tva', $search_multicurrency_montant_vat, 1);
if ($search_multicurrency_montant_ttc != '') $sql .= natural_search('p.multicurrency_total_ttc', $search_multicurrency_montant_ttc, 1);
if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if ($search_categ_cus > 0)			$sql .= " AND cc.fk_categorie = ".$db->escape($search_categ_cus);
if ($search_categ_cus == -2)		$sql .= " AND cc.fk_categorie IS NULL";

if ($search_product_category > 0)	$sql .= " AND cp.fk_categorie = ".$db->escape($search_product_category);
if ($socid > 0) $sql .= ' AND s.rowid = '.$socid;
if ($search_status != '' && $search_status != '-1')
{
	$sql .= ' AND p.fk_statut IN ('.$db->sanitize($db->escape($search_status)).')';
}
if ($search_date_start)             $sql .= " AND p.datep >= '".$db->idate($search_date_start)."'";
if ($search_date_end)               $sql .= " AND p.datep <= '".$db->idate($search_date_end)."'";
if ($search_dateend_start)      	$sql .= " AND p.fin_validite >= '".$db->idate($search_dateend_start)."'";
if ($search_dateend_end)            $sql .= " AND p.fin_validite <= '".$db->idate($search_dateend_end)."'";
if ($search_datedelivery_start)     $sql .= " AND p.date_livraison >= '".$db->idate($search_datedelivery_start)."'";
if ($search_datedelivery_end)       $sql .= " AND p.date_livraison <= '".$db->idate($search_datedelivery_end)."'";
if ($search_sale > 0)				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$db->escape($search_sale);
if ($search_user > 0)
{
	$sql .= " AND c.fk_c_type_contact = tc.rowid AND tc.element='propal' AND tc.source='internal' AND c.element_id = p.rowid AND c.fk_socpeople = ".$db->escape($search_user);
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);
$sql .= ', p.ref DESC';

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

$resql = $db->query($sql);

if ($resql)
{
	$objectstatic = new Propal($db);
	$userstatic = new User($db);

	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfProposals').' - '.$soc->name;
		if (empty($search_societe)) $search_societe = $soc->name;
	} else {
		$title = $langs->trans('ListOfProposals');
	}

	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);

		$id = $obj->rowid;

		header("Location: ".DOL_URL_ROOT.'/comm/propal/card.php?id='.$id);
		exit;
	}

	llxHeader('', $langs->trans('Proposal'), $help_url);

	$param = '&search_status='.urlencode($search_status);
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall)				 			$param .= '&sall='.urlencode($sall);
	if ($search_date_start)				$param .= '&search_date_start='.urlencode($search_date_start);
	if ($search_date_end)				$param .= '&search_date_end='.urlencode($search_date_end);
	if ($search_dateend_start)			$param .= '&search_dateend_start='.urlencode($search_dateend_start);
	if ($search_dateend_end)			$param .= '&search_dateend_end='.urlencode($search_dateend_end);
	if ($search_datedelivery_start)		$param .= '&search_datedelivery_start='.urlencode($search_datedelivery_start);
	if ($search_datedelivery_end)		$param .= '&search_datedelivery_end='.urlencode($search_datedelivery_end);
	if ($search_ref)         			$param .= '&search_ref='.urlencode($search_ref);
	if ($search_refcustomer) 			$param .= '&search_refcustomer='.urlencode($search_refcustomer);
	if ($search_refproject)  			$param .= '&search_refproject='.urlencode($search_refproject);
	if ($search_societe)     			$param .= '&search_societe='.urlencode($search_societe);
	if ($search_user > 0)    			$param .= '&search_user='.urlencode($search_user);
	if ($search_sale > 0)    			$param .= '&search_sale='.urlencode($search_sale);
	if ($search_montant_ht)  			$param .= '&search_montant_ht='.urlencode($search_montant_ht);
	if ($search_multicurrency_code != '')  $param .= '&search_multicurrency_code='.urlencode($search_multicurrency_code);
	if ($search_multicurrency_tx != '')  $param .= '&search_multicurrency_tx='.urlencode($search_multicurrency_tx);
	if ($search_multicurrency_montant_ht != '')  $param .= '&search_multicurrency_montant_ht='.urlencode($search_multicurrency_montant_ht);
	if ($search_multicurrency_montant_vat != '')  $param .= '&search_multicurrency_montant_vat='.urlencode($search_multicurrency_montant_vat);
	if ($search_multicurrency_montant_ttc != '') $param .= '&search_multicurrency_montant_ttc='.urlencode($search_multicurrency_montant_ttc);
	if ($search_login)  	 			$param .= '&search_login='.urlencode($search_login);
	if ($search_town)		 			$param .= '&search_town='.urlencode($search_town);
	if ($search_zip)		 			$param .= '&search_zip='.urlencode($search_zip);
	if ($socid > 0)          			$param .= '&socid='.urlencode($socid);
	if ($optioncss != '')    			$param .= '&optioncss='.urlencode($optioncss);
	if ($search_categ_cus > 0)          $param .= '&search_categ_cus='.urlencode($search_categ_cus);
	if ($search_product_category != '') $param .= '&search_product_category='.$search_product_category;

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
		'builddoc'=>$langs->trans("PDFMerge"),
		'presend'=>$langs->trans("SendByMail"),
		'prevalidate'=>$langs->trans("Validate"),
		'presign'=>$langs->trans("Sign"),
	);
	if ($user->rights->propal->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if ($user->rights->propal->cloturer) $arrayofmassactions['closed'] = $langs->trans("Close");
	if (in_array($massaction, array('presend', 'predelete', 'closed'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$url = DOL_URL_ROOT.'/comm/propal/card.php?action=create';
	if (!empty($socid)) $url .= '&socid='.$socid;
	$newcardbutton = dolGetButtonTitle($langs->trans('NewPropal'), '', 'fa fa-plus-circle', $url, '', $user->rights->propal->creer);

	// Fields title search
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'propal', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "SendPropalRef";
	$modelmail = "propal_send";
	$objecttmp = new Propal($db);
	$trackid = 'pro'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($massaction == 'prevalidate')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassValidation"), $langs->trans("ConfirmMassValidationQuestion"), "validate", null, '', 0, 200, 500, 1);
	}

	if ($massaction == 'presign')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassSignature"), $langs->trans("ConfirmMassSignatureQuestion"), "sign", null, '', 0, 200, 500, 1);
	}

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	$i = 0;

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
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('LinkedToSpecificUsers').': ';
		$moreforfilter .= $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
		$moreforfilter .= '</div>';
	}
	// If the user can view products
	if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('IncludingProductWithTag').': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter .= $form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
		$moreforfilter .= '</div>';
	}
	if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('CustomersProspectsCategoriesShort').': ';
		$moreforfilter .= $formother->select_categories('customer', $search_categ_cus, 'search_categ_cus', 1);
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
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.ref_client']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_refcustomer" value="'.dol_escape_htmltag($search_refcustomer).'">';
		print '</td>';
	}
	if (!empty($arrayfields['pr.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_refproject" value="'.dol_escape_htmltag($search_refproject).'">';
		print '</td>';
	}
	if (!empty($arrayfields['pr.title']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_project" value="'.dol_escape_htmltag($search_project).'">';
		print '</td>';
	}
	if (!empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat maxwidth100" type="text" name="search_societe" value="'.dol_escape_htmltag($search_societe).'">';
		print '</td>';
	}
	if (!empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_town" value="'.$search_town.'"></td>';
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
		print '<td class="liste_titre" align="center">';
		print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
		print '</td>';
	}
	// Company type
	if (!empty($arrayfields['typent.code']['checked']))
	{
		print '<td class="liste_titre maxwidth100onsmartphone" align="center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT));
		print ajax_combobox('search_type_thirdparty');
		print '</td>';
	}
	// Date
	if (!empty($arrayfields['p.date']['checked']))
	{
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	// Date end
	if (!empty($arrayfields['p.fin_validite']['checked']))
	{
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_dateend_start ? $search_dateend_start : -1, 'search_dateend_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_dateend_end ? $search_dateend_end : -1, 'search_dateend_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	// Date delivery
	if (!empty($arrayfields['p.date_livraison']['checked']))
	{
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_datedelivery_start ? $search_datedelivery_start : -1, 'search_datedelivery_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_datedelivery_end ? $search_datedelivery_end : -1, 'search_datedelivery_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '</td>';
	}
	// Availability
	if (!empty($arrayfields['ava.rowid']['checked']))
	{
		print '<td class="liste_titre maxwidth100onsmartphone center">';
		$form->selectAvailabilityDelay($search_availability, 'search_availability', '', 1);
		print ajax_combobox('search_availability');
		print '</td>';
	}
	if (!empty($arrayfields['p.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.total_ht_invoiced']['checked']))
	{
		// Amount invoiced
		print '<td class="liste_titre right">';
		print '</td>';
	}
	if (!empty($arrayfields['p.total_invoiced']['checked']))
	{
		// Amount invoiced
		print '<td class="liste_titre right">';
		print '</td>';
	}
	if (!empty($arrayfields['p.multicurrency_code']['checked']))
	{
		// Currency
		print '<td class="liste_titre">';
		print $form->selectMultiCurrency($search_multicurrency_code, 'search_multicurrency_code', 1);
		print '</td>';
	}
	if (!empty($arrayfields['p.multicurrency_tx']['checked']))
	{
		// Currency rate
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_tx" value="'.dol_escape_htmltag($search_multicurrency_tx).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.multicurrency_total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ht" value="'.dol_escape_htmltag($search_multicurrency_montant_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.multicurrency_total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_vat" value="'.dol_escape_htmltag($search_multicurrency_montant_vat).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.multicurrency_total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ttc" value="'.dol_escape_htmltag($search_multicurrency_montant_ttc).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.multicurrency_total_ht_invoiced']['checked']))
	{
		// Amount invoiced
		print '<td class="liste_titre right">';
		print '</td>';
	}
	if (!empty($arrayfields['p.multicurrency_total_invoiced']['checked']))
	{
		// Amount invoiced
		print '<td class="liste_titre right">';
		print '</td>';
	}
	if (!empty($arrayfields['u.login']['checked']))
	{
		// Author
		print '<td class="liste_titre" align="center">';
		print '<input class="flat" size="4" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
		print '</td>';
	}
	if (!empty($arrayfields['sale_representative']['checked']))
	{
		print '<td class="liste_titre"></td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['p.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['p.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date cloture
	if (!empty($arrayfields['p.date_cloture']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (!empty($arrayfields['p.note_public']['checked']))
	{
		// Note public
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (!empty($arrayfields['p.note_private']['checked']))
	{
		// Note private
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (!empty($arrayfields['p.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone right">';
		$formpropal->selectProposalStatus($search_status, 1, 0, 1, 'customer', 'search_statut');
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
	if (!empty($arrayfields['p.ref']['checked']))            	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['p.ref_client']['checked']))     	print_liste_field_titre($arrayfields['p.ref_client']['label'], $_SERVER["PHP_SELF"], 'p.ref_client', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['pr.ref']['checked']))				print_liste_field_titre($arrayfields['pr.ref']['label'], $_SERVER["PHP_SELF"], 'pr.ref', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['pr.title']['checked']))         	print_liste_field_titre($arrayfields['pr.title']['label'], $_SERVER["PHP_SELF"], 'pr.title', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.nom']['checked']))            	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.town']['checked']))           	print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.zip']['checked']))            	print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['state.nom']['checked']))        	print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['country.code_iso']['checked'])) 	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['typent.code']['checked']))      	print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.date']['checked']))           	print_liste_field_titre($arrayfields['p.date']['label'], $_SERVER["PHP_SELF"], 'p.datep', '', $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.fin_validite']['checked']))		print_liste_field_titre($arrayfields['p.fin_validite']['label'], $_SERVER["PHP_SELF"], 'dfv', '', $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.date_livraison']['checked']))	print_liste_field_titre($arrayfields['p.date_livraison']['label'], $_SERVER["PHP_SELF"], 'ddelivery', '', $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['ava.rowid']['checked']))			print_liste_field_titre($arrayfields['ava.rowid']['label'], $_SERVER["PHP_SELF"], 'availability', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['p.total_ht']['checked']))			print_liste_field_titre($arrayfields['p.total_ht']['label'], $_SERVER["PHP_SELF"], 'p.total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.total_vat']['checked']))			print_liste_field_titre($arrayfields['p.total_vat']['label'], $_SERVER["PHP_SELF"], 'p.tva', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.total_ttc']['checked']))			print_liste_field_titre($arrayfields['p.total_ttc']['label'], $_SERVER["PHP_SELF"], 'p.total', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.total_ht_invoiced']['checked'])) print_liste_field_titre($arrayfields['p.total_ht_invoiced']['label'], $_SERVER["PHP_SELF"], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.total_invoiced']['checked']))	print_liste_field_titre($arrayfields['p.total_invoiced']['label'], $_SERVER["PHP_SELF"], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.multicurrency_code']['checked']))      print_liste_field_titre($arrayfields['p.multicurrency_code']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_code', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['p.multicurrency_tx']['checked']))        print_liste_field_titre($arrayfields['p.multicurrency_tx']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_tx', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['p.multicurrency_total_ht']['checked']))  print_liste_field_titre($arrayfields['p.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.multicurrency_total_vat']['checked'])) print_liste_field_titre($arrayfields['p.multicurrency_total_vat']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_total_tva', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.multicurrency_total_ttc']['checked'])) print_liste_field_titre($arrayfields['p.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'p.multicurrency_total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.multicurrency_total_ht_invoiced']['checked'])) print_liste_field_titre($arrayfields['p.multicurrency_total_ht_invoiced']['label'], $_SERVER["PHP_SELF"], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.multicurrency_total_invoiced']['checked']))	print_liste_field_titre($arrayfields['p.multicurrency_total_invoiced']['label'], $_SERVER["PHP_SELF"], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['u.login']['checked']))				print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['sale_representative']['checked']))	print_liste_field_titre($arrayfields['sale_representative']['label'], $_SERVER["PHP_SELF"], "", "", "$param", '', $sortfield, $sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['p.datec']['checked']))     		print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.tms']['checked']))       		print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.date_cloture']['checked']))		print_liste_field_titre($arrayfields['p.date_cloture']['label'], $_SERVER["PHP_SELF"], "p.date_cloture", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (!empty($arrayfields['p.note_public']['checked']))       print_liste_field_titre($arrayfields['p.note_public']['label'], $_SERVER["PHP_SELF"], "p.note_public", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['p.note_private']['checked']))      print_liste_field_titre($arrayfields['p.note_private']['label'], $_SERVER["PHP_SELF"], "p.note_private", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['p.fk_statut']['checked']))			print_liste_field_titre($arrayfields['p.fk_statut']['label'], $_SERVER["PHP_SELF"], "p.fk_statut", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print '</tr>'."\n";

	$now = dol_now();
	$i = 0;
	$totalarray = array();
	$typenArray = null;

	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$objectstatic->id = $obj->rowid;
		$objectstatic->ref = $obj->ref;
		$objectstatic->ref_client = $obj->ref_client;
		$objectstatic->note_public = $obj->note_public;
		$objectstatic->note_private = $obj->note_private;
		$objectstatic->statut = $obj->status;
		$objectstatic->status = $obj->status;

		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->name;
		$companystatic->client = $obj->client;
		$companystatic->code_client = $obj->code_client;
		$companystatic->email = $obj->email;

		$projectstatic->id = $obj->project_id;
		$projectstatic->ref = $obj->project_ref;
		$projectstatic->title = $obj->project_label;

		$totalInvoicedHT = 0;
		$totalInvoicedTTC = 0;
		$multicurrency_totalInvoicedHT = 0;
		$multicurrency_totalInvoicedTTC = 0;

		$TInvoiceData = $objectstatic->InvoiceArrayList($obj->rowid);

		if (!empty($TInvoiceData))
		{
			foreach ($TInvoiceData as $invoiceData)
			{
				$invoice = new Facture($db);
				$invoice->fetch($invoiceData->facid);

				if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS) && $invoice->type == Facture::TYPE_DEPOSIT) continue;

				$totalInvoicedHT += $invoice->total_ht;
				$totalInvoicedTTC += $invoice->total_ttc;
				$multicurrency_totalInvoicedHT += $invoice->multicurrency_total_ht;
				$multicurrency_totalInvoicedTTC += $invoice->multicurrency_total_ttc;
			}
		}

		print '<tr class="oddeven">';

		if (!empty($arrayfields['p.ref']['checked']))
		{
			print '<td class="nowraponall">';

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			// Picto + Ref
			print '<td class="nobordernopadding nowraponall">';
			print $objectstatic->getNomUrl(1, '', '', 0, 1, (isset($conf->global->PROPAL_LIST_SHOW_NOTES) ? $conf->global->PROPAL_LIST_SHOW_NOTES : 1));
			print '</td>';
			// Warning
			$warnornote = '';
			if ($obj->status == Propal::STATUS_VALIDATED && $db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) $warnornote .= img_warning($langs->trans("Late"));
			if ($warnornote)
			{
				print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
				print $warnornote;
				print '</td>';
			}
			// Other picto tool
			print '<td width="16" class="nobordernopadding right">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->propal->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		if (!empty($arrayfields['p.ref_client']['checked']))
		{
			// Customer ref
			print '<td class="nowrap tdoverflowmax200">';
			print $obj->ref_client;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		if (!empty($arrayfields['pr.ref']['checked']))
		{
			// Project ref
			print '<td class="nowrap">';
			if ($obj->project_id > 0) {
				print $projectstatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		if (!empty($arrayfields['pr.title']['checked']))
		{
			// Project label
			print '<td class="nowrap">';
			if ($obj->project_id > 0) {
				print $projectstatic->title;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Thirdparty
		if (!empty($arrayfields['s.nom']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $companystatic->getNomUrl(1, 'customer');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Town
		if (!empty($arrayfields['s.town']['checked']))
		{
			print '<td class="nocellnopadd">';
			print $obj->town;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Zip
		if (!empty($arrayfields['s.zip']['checked']))
		{
			print '<td class="nocellnopadd">';
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
			if (!is_array($typenArray) || empty($typenArray)) $typenArray = $formcompany->typent_array(1);

			print '<td class="center">';
			print $typenArray[$obj->typent_code];
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Date proposal
		if (!empty($arrayfields['p.date']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->dp), 'day');
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Date end validity
		if (!empty($arrayfields['p.fin_validite']['checked']))
		{
			if ($obj->dfv)
			{
				print '<td class="center">'.dol_print_date($db->jdate($obj->dfv), 'day');
				print '</td>';
			} else {
				print '<td>&nbsp;</td>';
			}
			if (!$i) $totalarray['nbfield']++;
		}
		// Date delivery
		if (!empty($arrayfields['p.date_livraison']['checked']))
		{
			if ($obj->ddelivery)
			{
				print '<td class="center">'.dol_print_date($db->jdate($obj->ddelivery), 'day');
				print '</td>';
			} else {
				print '<td>&nbsp;</td>';
			}
			if (!$i) $totalarray['nbfield']++;
		}
		// Availability
		if (!empty($arrayfields['ava.rowid']['checked']))
		{
			print '<td class="center">';
			$form->form_availability('', $obj->availability, 'none', 1);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Amount HT
		if (!empty($arrayfields['p.total_ht']['checked']))
		{
			print '<td class="nowrap right">'.price($obj->total_ht)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'p.total_ht';
			$totalarray['val']['p.total_ht'] += $obj->total_ht;
		}
		// Amount VAT
		if (!empty($arrayfields['p.total_vat']['checked']))
		{
			print '<td class="nowrap right">'.price($obj->total_vat)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'p.total_vat';
			$totalarray['val']['p.total_vat'] += $obj->total_vat;
		}
		// Amount TTC
		if (!empty($arrayfields['p.total_ttc']['checked']))
		{
			print '<td class="nowrap right">'.price($obj->total_ttc)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'p.total_ttc';
			$totalarray['val']['p.total_ttc'] += $obj->total_ttc;
		}
		// Amount invoiced
		if (!empty($arrayfields['p.total_ht_invoiced']['checked']))
		{
			print '<td class="nowrap right">'.price($totalInvoicedHT)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'p.total_ht_invoiced';
			$totalarray['val']['p.total_ht_invoiced'] += $totalInvoicedHT;
		}
		// Amount invoiced
		if (!empty($arrayfields['p.total_invoiced']['checked']))
		{
			print '<td class="nowrap right">'.price($totalInvoicedTTC)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'p.total_invoiced';
			$totalarray['val']['p.total_invoiced'] += $totalInvoicedTTC;
		}

		// Currency
		if (!empty($arrayfields['p.multicurrency_code']['checked']))
		{
			print '<td class="nowrap">'.$obj->multicurrency_code.' - '.$langs->trans('Currency'.$obj->multicurrency_code)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Currency rate
		if (!empty($arrayfields['p.multicurrency_tx']['checked']))
		{
			print '<td class="nowrap">';
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Amount HT
		if (!empty($arrayfields['p.multicurrency_total_ht']['checked']))
		{
			print '<td class="right nowrap">'.price($obj->multicurrency_total_ht)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Amount VAT
		if (!empty($arrayfields['p.multicurrency_total_vat']['checked']))
		{
			print '<td class="right nowrap">'.price($obj->multicurrency_total_vat)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Amount TTC
		if (!empty($arrayfields['p.multicurrency_total_ttc']['checked']))
		{
			print '<td class="right nowrap">'.price($obj->multicurrency_total_ttc)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Amount invoiced
		if (!empty($arrayfields['p.multicurrency_total_ht_invoiced']['checked']))
		{
			print '<td class="nowrap right">'.price($multicurrency_totalInvoicedHT)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Amount invoiced
		if (!empty($arrayfields['p.multicurrency_total_invoiced']['checked']))
		{
			print '<td class="nowrap right">'.price($multicurrency_totalInvoicedTTC)."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		$userstatic->id = $obj->fk_user_author;
		$userstatic->login = $obj->login;

		// Author
		if (!empty($arrayfields['u.login']['checked']))
		{
			print '<td class="center nowraponall">';
			if ($userstatic->id) print $userstatic->getLoginUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		if (!empty($arrayfields['sale_representative']['checked']))
		{
			// Sales representatives
			print '<td>';
			if ($obj->socid > 0)
			{
				$listsalesrepresentatives = $companystatic->getSalesRepresentatives($user);
				if ($listsalesrepresentatives < 0) dol_print_error($db);
				$nbofsalesrepresentative = count($listsalesrepresentatives);
				if ($nbofsalesrepresentative > 3)   // We print only number
				{
					print $nbofsalesrepresentative;
				} elseif ($nbofsalesrepresentative > 0)
				{
					$userstatic = new User($db);
					$j = 0;
					foreach ($listsalesrepresentatives as $val)
					{
						$userstatic->id = $val['id'];
						$userstatic->lastname = $val['lastname'];
						$userstatic->firstname = $val['firstname'];
						$userstatic->email = $val['email'];
						$userstatic->statut = $val['statut'];
						$userstatic->entity = $val['entity'];
						$userstatic->photo = $val['photo'];

						//print '<div class="float">':
						print $userstatic->getNomUrl(-2);
						$j++;
						if ($j < $nbofsalesrepresentative) print ' ';
						//print '</div>';
					}
				}
				//else print $langs->trans("NoSalesRepresentativeAffected");
			} else {
				print '&nbsp';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['p.datec']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date modification
		if (!empty($arrayfields['p.tms']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date cloture
		if (!empty($arrayfields['p.date_cloture']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_cloture), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Note public
		if (!empty($arrayfields['p.note_public']['checked']))
		{
			print '<td class="center">';
			print dol_escape_htmltag($obj->note_public);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Note private
		if (!empty($arrayfields['p.note_private']['checked']))
		{
			print '<td class="center">';
			print dol_escape_htmltag($obj->note_private);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Status
		if (!empty($arrayfields['p.fk_statut']['checked']))
		{
			print '<td class="nowrap right">'.$objectstatic->getLibStatut(5).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Action column
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";

		$i++;
	}

	// Show total line
	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

	$db->free($resql);

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>'."\n";

	print '</form>'."\n";

	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $user->rights->propal->lire;
	$delallowed = $user->rights->propal->creer;

	print $formfile->showdocuments('massfilesarea_proposals', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);

	if ($action == 'validate') {
		if (GETPOST('confirm') == 'yes') {
			$tmpproposal = new Propal($db);
			$db->begin();
			$error = 0;
			foreach ($toselect as $checked) {
				if ($tmpproposal->fetch($checked)) {
					if ($tmpproposal->statut == 0) {
						if ($tmpproposal->valid($user)) {
							setEventMessage($tmpproposal->ref." ".$langs->trans('PassedInOpenStatus'), 'mesgs');
						} else {
							setEventMessage($langs->trans('CantBeValidated'), 'errors');
							$error++;
						}
					} else {
						setEventMessage($tmpproposal->ref." ".$langs->trans('IsNotADraft'), 'errors');
						$error++;
					}
				}
				dol_print_error($db);
				$error++;
			}
			if ($error) {
				$db->rollback();
			} else {
				$db->commit();
			}
		}
	}

	if ($action == "sign") {
		if (GETPOST('confirm') == 'yes') {
			$tmpproposal = new Propal($db);
			$db->begin();
			$error = 0;
			foreach ($toselect as $checked) {
				if ($tmpproposal->fetch($checked)) {
					if ($tmpproposal->statut == 1) {
						$tmpproposal->statut = 2;
						if ($tmpproposal->update($user)) {
							setEventMessage($tmpproposal->ref." ".$langs->trans('Signed'), 'mesgs');
						} else {
							dol_print_error($db);
							$error++;
						}
					} else {
						setEventMessage($tmpproposal->ref." ".$langs->trans('CantBeSign'), 'errors');
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
} else {
		dol_print_error($db);
}

	// End of page
	llxFooter();
	$db->close();

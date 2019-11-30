<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2018 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015-2007 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015 	   Abbes Bahfir         <bafbes@gmail.com>
 * Copyright (C) 2015-2016 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2017      Josep Lluís Amador   <joseplluis@lliuretic.cat>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/fourn/facture/list.php
 *       \ingroup    fournisseur,facture
 *       \brief      List of suppliers invoices
 */

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

if (!$user->rights->fournisseur->facture->lire) accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array('products', 'bills', 'companies', 'projects'));

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$optioncss = GETPOST('optioncss','alpha');
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'supplierinvoicelist';

$socid = GETPOST('socid','int');

// Security check
if ($user->societe_id > 0)
{
	$action='';
	$_GET["action"] = '';
	$socid = $user->societe_id;
}

$mode=GETPOST("mode");

$search_all = trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_label = GETPOST("search_label","alpha");
$search_amount_no_tax = GETPOST("search_amount_no_tax","alpha");
$search_amount_all_tax = GETPOST("search_amount_all_tax","alpha");
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refsupplier=GETPOST('search_refsupplier','alpha');
$search_type=GETPOST('search_type','int');
$search_project=GETPOST('search_project','alpha');
$search_company=GETPOST('search_company','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_vat=GETPOST('search_montant_vat','alpha');
$search_montant_localtax1=GETPOST('search_montant_localtax1','alpha');
$search_montant_localtax2=GETPOST('search_montant_localtax2','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');
$search_status=GETPOST('search_status','int');
$search_paymentmode=GETPOST('search_paymentmode','int');
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_user = GETPOST('search_user','int');
$search_sale = GETPOST('search_sale','int');
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');
$day_lim	= GETPOST('day_lim','int');
$month_lim	= GETPOST('month_lim','int');
$year_lim	= GETPOST('year_lim','int');
$toselect = GETPOST('toselect', 'array');
$search_btn=GETPOST('button_search','alpha');
$search_remove_btn=GETPOST('button_removefilter','alpha');

$option = GETPOST('option');
if ($option == 'late') {
	$search_status = '1';
}
$filter = GETPOST('filtre','alpha');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page=GETPOST("page",'int');
if ($page == -1 || $page == null || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0 ; }
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datef,f.rowid";

$diroutputmassaction=$conf->fournisseur->facture->dir_output . '/temp/massgeneration/'.$user->id;

$now=dol_now();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new FactureFournisseur($db);
$hookmanager->initHooks(array('supplierinvoicelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('facture_fourn');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.ref'=>'Ref',
	'f.ref_supplier'=>'RefSupplier',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	'f.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["f.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
	'f.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'f.ref_supplier'=>array('label'=>$langs->trans("RefSupplier"), 'checked'=>1),
	'f.type'=>array('label'=>$langs->trans("Type"), 'checked'=>0),
	'f.label'=>array('label'=>$langs->trans("Label"), 'checked'=>0),
	'f.datef'=>array('label'=>$langs->trans("DateInvoice"), 'checked'=>1),
	'f.date_lim_reglement'=>array('label'=>$langs->trans("DateDue"), 'checked'=>1),
	'p.ref'=>array('label'=>$langs->trans("ProjectRef"), 'checked'=>0),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'f.fk_mode_reglement'=>array('label'=>$langs->trans("PaymentMode"), 'checked'=>1),
	'f.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	'f.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
	'f.total_localtax1'=>array('label'=>$langs->transcountry("AmountLT1", $mysoc->country_code), 'checked'=>0, 'enabled'=>$mysoc->localtax1_assuj=="1"),
	'f.total_localtax2'=>array('label'=>$langs->transcountry("AmountLT2", $mysoc->country_code), 'checked'=>0, 'enabled'=>$mysoc->localtax2_assuj=="1"),
	'f.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
	'dynamount_payed'=>array('label'=>$langs->trans("Payed"), 'checked'=>0),
	'rtp'=>array('label'=>$langs->trans("Rest"), 'checked'=>0),
	'f.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'f.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'f.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
	}
}


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter','alpha') || GETPOST('button_removefilter.x','alpha'))		// All tests must be present to be compatible with all browsers
	{
		$search_all="";
		$search_user='';
		$search_sale='';
		$search_product_category='';
		$search_ref="";
		$search_refsupplier="";
		$search_type="";
		$search_label="";
		$search_project='';
		$search_company="";
		$search_amount_no_tax="";
		$search_amount_all_tax="";
		$search_montant_ht='';
		$search_montant_vat='';
		$search_montant_localtax1='';
		$search_montant_localtax2='';
		$search_montant_ttc='';
		$search_status='';
		$search_paymentmode='';
		$search_town='';
		$search_zip="";
		$search_state="";
		$search_type='';
		$search_country='';
		$search_type_thirdparty='';
		$year="";
		$month="";
		$day="";
		$year_lim="";
		$month_lim="";
		$day_lim="";
		$toselect='';
		$search_array_options=array();
		$filter='';
		$option='';
	}

	// Mass actions
	$objectclass='FactureFournisseur';
	$objectlabel='SupplierInvoices';
	$permtoread = $user->rights->fournisseur->facture->lire;
	$permtocreate = $user->rights->fournisseur->facture->creer;
	$permtodelete = $user->rights->fournisseur->facture->supprimer;
	$uploaddir = $conf->fournisseur->facture->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);
$facturestatic=new FactureFournisseur($db);
$formcompany=new FormCompany($db);
$thirdparty=new Societe($db);

llxHeader('',$langs->trans("SuppliersInvoices"),'EN:Suppliers_Invoices|FR:FactureFournisseur|ES:Facturas_de_proveedores');

$sql = "SELECT";
if ($search_all || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= " f.rowid as facid, f.ref, f.ref_supplier, f.type, f.datef, f.date_lim_reglement as datelimite, f.fk_mode_reglement,";
$sql.= " f.total_ht, f.total_ttc, f.total_tva as total_vat, f.paye as paye, f.fk_statut as fk_statut, f.libelle as label, f.datec as date_creation, f.tms as date_update,";
$sql.= " f.localtax1 as total_localtax1, f.localtax2 as total_localtax2,";
$sql.= " s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,";
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= " country.code as country_code,";
$sql.= " p.rowid as project_id, p.ref as project_ref, p.title as project_label";
// We need dynamount_payed to be able to sort on status (value is surely wrong because we can count several lines several times due to other left join or link with contacts. But what we need is just 0 or > 0)
// TODO Better solution to be able to sort on already payed or remain to pay is to store amount_payed in a denormalized field.
if (! $search_all) $sql.= ', SUM(pf.amount) as dynamount_payed';
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql.= ', '.MAIN_DB_PREFIX.'facture_fourn as f';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn_extrafields as ef on (f.rowid = ef.fk_object)";
if (! $search_all) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
if ($search_all || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_det as pd ON f.rowid=pd.fk_facture_fourn';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = f.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE f.fk_soc = s.rowid';
$sql.= ' AND f.entity IN ('.getEntity('facture_fourn').')';
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql .= ' AND s.rowid = '.$socid;
if ($search_ref)
{
	if (is_numeric($search_ref)) $sql .= natural_search(array('f.ref'), $search_ref);
	else $sql .= natural_search('f.ref', $search_ref);
}
if ($search_ref) $sql .= natural_search('f.ref', $search_ref);
if ($search_refsupplier) $sql .= natural_search('f.ref_supplier', $search_refsupplier);
if ($search_type != '' && $search_type >= 0)
{
	if ($search_type == '0') $sql.=" AND f.type = 0";  // standard
	if ($search_type == '1') $sql.=" AND f.type = 1";  // replacement
	if ($search_type == '2') $sql.=" AND f.type = 2";  // credit note
	if ($search_type == '3') $sql.=" AND f.type = 3";  // deposit
	//if ($search_type == '4') $sql.=" AND f.type = 4";  // proforma
	//if ($search_type == '5') $sql.=" AND f.type = 5";  // situation
}
if ($search_project) $sql .= natural_search('p.ref', $search_project);
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_montant_ht != '') $sql.= natural_search('f.total_ht', $search_montant_ht, 1);
if ($search_montant_vat != '') $sql.= natural_search('f.total_tva', $search_montant_vat, 1);
if ($search_montant_localtax1 != '') $sql.= natural_search('f.localtax1', $search_montant_localtax1, 1);
if ($search_montant_localtax2 != '') $sql.= natural_search('f.localtax2', $search_montant_localtax2, 1);
if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_status != '' && $search_status >= 0) $sql.= " AND f.fk_statut = ".$db->escape($search_status);
if ($search_paymentmode > 0) $sql .= " AND f.fk_mode_reglement = ".$search_paymentmode."";
if ($month > 0)
{
	if ($year > 0 && empty($day))
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
	else if ($year > 0 && ! empty($day))
		$sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
	else
	$sql.= " AND date_format(f.datef, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($month_lim > 0)
{
	if ($year_lim > 0 && empty($day_lim))
		$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
	else if ($year_lim > 0 && ! empty($day_lim))
		$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $day_lim, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $day_lim, $year_lim))."'";
	else
		$sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$db->escape($month_lim)."'";
}
else if ($year_lim > 0)
{
	$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
}
if ($option == 'late') $sql.=" AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->fournisseur->warning_delay)."'";
if ($search_label) $sql .= natural_search('f.libelle', $search_label);
if ($search_status != '' && $search_status >= 0)
{
	$sql.= " AND f.fk_statut = ".$search_status;
}
if ($filter && $filter != -1)
{
	$aFilter = explode(',', $filter);
	foreach ($aFilter as $fil)
	{
		$filt = explode(':', $fil);
		$sql .= ' AND ' . $db->escape(trim($filt[0])) . ' = ' . $db->escape(trim($filt[1]));
	}
}
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$db->escape($search_sale);
if ($search_user > 0)
{
	$sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='invoice_supplier' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

if (! $search_all)
{
	$sql.= " GROUP BY f.rowid, f.ref, f.ref_supplier, f.type, f.datef, f.date_lim_reglement, f.fk_mode_reglement,";
	$sql.= " f.total_ht, f.total_ttc, f.total_tva, f.paye, f.fk_statut, f.libelle, f.datec, f.tms,";
	$sql.= " f.localtax1, f.localtax2,";
	$sql.= ' s.rowid, s.nom, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,';
	$sql.= " typent.code,";
	$sql.= " state.code_departement, state.nom,";
	$sql.= ' country.code,';
	$sql.= " p.rowid, p.ref, p.title";

	foreach ($extrafields->attribute_label as $key => $val) //prevent error with sql_mode=only_full_group_by
	{
		$sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
	}
}
else
{
	$sql.= natural_search(array_keys($fieldstosearchall), $search_all);
}

$sql.= $db->order($sortfield,$sortorder);

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

$sql.= $db->plimit($limit+1, $offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($socid)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		if (empty($search_company)) $search_company = $soc->name;
	}

	$param='&socid='.$socid;
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($search_all)			$param.='&search_all='.urlencode($search_all);
	if ($day) 					$param.='&day='.urlencode($day);
	if ($month) 				$param.='&month='.urlencode($month);
	if ($year)  				$param.='&year=' .urlencode($year);
	if ($day_lim) 				$param.='&day_lim='.urlencode($day_lim);
	if ($month_lim) 			$param.='&month_lim='.urlencode($month_lim);
	if ($year_lim)  			$param.='&year_lim=' .urlencode($year_lim);
	if ($search_ref)          	$param.='&search_ref='.urlencode($search_ref);
	if ($search_refsupplier) 	$param.='&search_refsupplier='.urlencode($search_refsupplier);
	if ($search_type != '')		$param.='&search_type='.urlencode($search_type);
	if ($search_label)      	$param.='&search_label='.urlencode($search_label);
	if ($search_company)      	$param.='&search_company='.urlencode($search_company);
	if ($search_montant_ht != '')  $param.='&search_montant_ht='.urlencode($search_montant_ht);
	if ($search_montant_vat != '')  $param.='&search_montant_vat='.urlencode($search_montant_vat);
	if ($search_montant_localtax1 != '')  $param.='&search_montant_localtax1='.urlencode($search_montant_localtax1);
	if ($search_montant_localtax2 != '')  $param.='&search_montant_localtax2='.urlencode($search_montant_localtax2);
	if ($search_montant_ttc != '') $param.='&search_montant_ttc='.urlencode($search_montant_ttc);
	if ($search_amount_no_tax)	$param.='&search_amount_no_tax='.urlencode($search_amount_no_tax);
	if ($search_amount_all_tax)	$param.='&search_amount_all_tax='.urlencode($search_amount_all_tax);
	if ($search_status >= 0)  	$param.="&search_status=".urlencode($search_status);
	if ($show_files)            $param.='&show_files=' .$show_files;
	if ($option)                $param.="&option=".$option;
	if ($optioncss != '')       $param.='&optioncss='.$optioncss;
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array(
		'validate'=>$langs->trans("Validate"),
		//'presend'=>$langs->trans("SendByMail"),
		//'builddoc'=>$langs->trans("PDFMerge"),
	);
	//if($user->rights->fournisseur->facture->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
	if ($user->rights->fournisseur->facture->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
	if (in_array($massaction, array('presend','predelete','createbills'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if ($user->rights->fournisseur->facture->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create"><span class="valignmiddle">'.$langs->trans('NewBill').'</span>';
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}

	$i = 0;
	print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';

	print_barre_liste($langs->trans("BillsSuppliers").($socid?' '.$soc->name:''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit);

	$topicmail="SendBillRef";
	$modelmail="invoice_supplier_send";
	$objecttmp=new FactureFournisseur($db);
	$trackid='sinv'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($massaction == 'createbills')
	{
		//var_dump($_REQUEST);
		print '<input type="hidden" name="massaction" value="confirm_createbills">';

		print '<table class="border" width="100%" >';
		print '<tr>';
		print '<td class="titlefieldmiddle">';
		print $langs->trans('DateInvoice');
		print '</td>';
		print '<td>';
		print $form->select_date('', '', '', '', '', '', 1, 1);
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
		print $form->selectyesno('valdate_invoices', 1, 1);
		print '</td>';
		print '</tr>';
		print '</table>';

		print '<br>';
		print '<div class="center">';
		print '<input type="submit" class="button" id="createbills" name="createbills" value="'.$langs->trans('CreateInvoiceForThisCustomer').'">  ';
		print '<input type="submit" class="button" id="cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
		print '</div>';
		print '<br>';
	}

	if ($search_all)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall).'</div>';
	}

 	// If the user can view prospects other than his'
	$moreforfilter='';
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
 		$moreforfilter.='<div class="divsearchfield">';
 		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
	 	$moreforfilter.='</div>';
 	}
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
		$moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
	 	$moreforfilter.='</div>';
	}
	// If the user can view prospects other than his'
	if ($conf->categorie->enabled && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
		$moreforfilter.='</div>';
	}
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if ($moreforfilter)
	{
   		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	// Line for filters
	print '<tr class="liste_titre_filter">';
	// Ref
	if (! empty($arrayfields['f.ref']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	}
	// Ref supplier
	if (! empty($arrayfields['f.ref_supplier']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_refsupplier" value="'.$search_refsupplier.'">';
		print '</td>';
	}
	// Type
	if (! empty($arrayfields['f.type']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone">';
		$listtype=array(
				FactureFournisseur::TYPE_STANDARD=>$langs->trans("InvoiceStandard"),
				FactureFournisseur::TYPE_REPLACEMENT=>$langs->trans("InvoiceReplacement"),
				FactureFournisseur::TYPE_CREDIT_NOTE=>$langs->trans("InvoiceAvoir"),
				FactureFournisseur::TYPE_DEPOSIT=>$langs->trans("InvoiceDeposit"),
		);
/*
		if (! empty($conf->global->INVOICE_USE_SITUATION))
		{
			$listtype[Facture::TYPE_SITUATION] = $langs->trans("InvoiceSituation");
		}
*/
		//$listtype[Facture::TYPE_PROFORMA]=$langs->trans("InvoiceProForma");     // A proformat invoice is not an invoice but must be an order.
		print $form->selectarray('search_type', $listtype, $search_type, 1, 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth100');
		print '</td>';
	}
	// Label
	if (! empty($arrayfields['f.label']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_label" value="'.$search_label.'">';
		print '</td>';
	}
	// Date invoice
	if (! empty($arrayfields['f.datef']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="day" value="'.dol_escape_htmltag($day).'">';
		print '<input class="flat width25 valignmiddle" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
		$formother->select_year($year?$year:-1,'year',1, 20, 5);
		print '</td>';
	}
	// Date due
	if (! empty($arrayfields['f.date_lim_reglement']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="day_lim" value="'.dol_escape_htmltag($day_lim).'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="month_lim" value="'.$month_lim.'">';
		$formother->select_year($year_lim?$year_lim:-1,'year_lim',1, 20, 5);
		print '<br><input type="checkbox" name="option" value="late"'.($option == 'late'?' checked':'').'> '.$langs->trans("Late");
		print '</td>';
	}
	// Project
	if (! empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_project" value="'.$search_project.'"></td>';
	}
	// Thirpdarty
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_company" value="'.$search_company.'"></td>';
	}
	// Town
	if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
	// Zip
	if (! empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
	// State
	if (! empty($arrayfields['state.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
		print '</td>';
	}
	// Country
	if (! empty($arrayfields['country.code_iso']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		print $form->select_country($search_country,'search_country','',0,'maxwidth100');
		print '</td>';
	}
	// Company type
	if (! empty($arrayfields['typent.code']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT));
		print '</td>';
	}
	// Payment mode
	if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		$form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 1, 1, 10);
		print '</td>';
	}
	if (! empty($arrayfields['f.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_localtax1']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_localtax1" value="'.$search_montant_localtax1.'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_localtax2']['checked']))
	{
		// Amount
 		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_localtax2" value="'.$search_montant_localtax2.'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
		print '</td>';
	}
	if (! empty($arrayfields['dynamount_payed']['checked']))
	{
		print '<td class="liste_titre" align="right">';
		print '</td>';
	}
	if (! empty($arrayfields['rtp']['checked']))
	{
		print '<td class="liste_titre" align="right">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['f.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['f.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (! empty($arrayfields['f.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="right">';
		$liststatus=array('0'=>$langs->trans("Draft"),'1'=>$langs->trans("Unpaid"), '2'=>$langs->trans("Paid"));
		print $form->selectarray('search_status', $liststatus, $search_status, 1);
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['f.ref']['checked']))                print_liste_field_titre($arrayfields['f.ref']['label'],$_SERVER['PHP_SELF'],'f.ref,f.rowid','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.ref_supplier']['checked']))       print_liste_field_titre($arrayfields['f.ref_supplier']['label'],$_SERVER["PHP_SELF"],'f.ref_supplier','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.type']['checked']))               print_liste_field_titre($arrayfields['f.type']['label'],$_SERVER["PHP_SELF"],'f.type','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.label']['checked']))              print_liste_field_titre($arrayfields['f.label']['label'],$_SERVER['PHP_SELF'],"f.libelle,f.rowid",'',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.datef']['checked']))              print_liste_field_titre($arrayfields['f.datef']['label'],$_SERVER['PHP_SELF'],'f.datef,f.rowid','',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.date_lim_reglement']['checked'])) print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'],$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.ref']['checked']))                print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER['PHP_SELF'],"p.ref",'',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))                print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))               print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))                print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))            print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked']))     print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))          print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_mode_reglement']['checked']))  print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'],$_SERVER["PHP_SELF"],"f.fk_mode_reglement","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_ht']['checked']))           print_liste_field_titre($arrayfields['f.total_ht']['label'],$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_vat']['checked']))          print_liste_field_titre($arrayfields['f.total_vat']['label'],$_SERVER['PHP_SELF'],'f.tva','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_localtax1']['checked']))    print_liste_field_titre($arrayfields['f.total_localtax1']['label'],$_SERVER['PHP_SELF'],'f.localtax1','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_localtax2']['checked']))    print_liste_field_titre($arrayfields['f.total_localtax2']['label'],$_SERVER['PHP_SELF'],'f.localtax2','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_ttc']['checked']))          print_liste_field_titre($arrayfields['f.total_ttc']['label'],$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['dynamount_payed']['checked']))      print_liste_field_titre($arrayfields['dynamount_payed']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['rtp']['checked']))                  print_liste_field_titre($arrayfields['rtp']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['f.datec']['checked']))     print_liste_field_titre($arrayfields['f.datec']['label'],$_SERVER["PHP_SELF"],"f.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.tms']['checked']))       print_liste_field_titre($arrayfields['f.tms']['label'],$_SERVER["PHP_SELF"],"f.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_statut']['checked'])) print_liste_field_titre($arrayfields['f.fk_statut']['label'],$_SERVER["PHP_SELF"],"fk_statut,paye,type","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	$facturestatic=new FactureFournisseur($db);
	$supplierstatic=new Fournisseur($db);
	$projectstatic=new Project($db);

	if ($num > 0)
	{
		$i=0;

		$totalarray=array();
		while ($i < min($num,$limit))
		{
			$obj = $db->fetch_object($resql);

			$datelimit=$db->jdate($obj->datelimite);
			$facturestatic->id=$obj->facid;
			$facturestatic->ref=$obj->ref;
			$facturestatic->type=$obj->type;
			$facturestatic->ref_supplier=$obj->ref_supplier;
			$facturestatic->date_echeance = $db->jdate($obj->datelimite);
			$facturestatic->statut = $obj->fk_statut;


			$thirdparty->id=$obj->socid;
			$thirdparty->name=$obj->name;
			$thirdparty->client=$obj->client;
			$thirdparty->fournisseur=$obj->fournisseur;
			$thirdparty->code_client=$obj->code_client;
			$thirdparty->code_compta_client=$obj->code_compta_client;
			$thirdparty->code_fournisseur=$obj->code_fournisseur;
			$thirdparty->code_compta_fournisseur=$obj->code_compta_fournisseur;
			$thirdparty->email=$obj->email;
			$thirdparty->country_code=$obj->country_code;

			$paiement = $facturestatic->getSommePaiement();
			$totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
			$totaldeposits = $facturestatic->getSumDepositsUsed();
			$totalpay = $paiement + $totalcreditnotes + $totaldeposits;
			$remaintopay = $obj->total_ttc - $totalpay;

            //If invoice has been converted and the conversion has been used, we dont have remain to pay on invoice
            if($facturestatic->type == FactureFournisseur::TYPE_CREDIT_NOTE) {

                if($facturestatic->isCreditNoteUsed()){
                    $remaintopay=-$facturestatic->getSumFromThisCreditNotesNotUsed();
                }
            }

			print '<tr class="oddeven">';
			if (! empty($arrayfields['f.ref']['checked']))
			{
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				// Picto + Ref
				print '<td class="nobordernopadding nowrap">';
				print $facturestatic->getNomUrl(1);
				print '</td>';
				// Warning
				//print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
				//print '</td>';
				// Other picto tool
				print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->fournisseur->facture->dir_output.'/'.get_exdir($obj->facid,2,0,0,$facturestatic,'invoice_supplier').dol_sanitizeFileName($obj->ref);
				$subdir = get_exdir($obj->facid,2,0,0,$facturestatic,'invoice_supplier').dol_sanitizeFileName($obj->ref);
				print $formfile->getDocumentsLink('facture_fournisseur', $subdir, $filedir);
				print '</td></tr></table>';

				print "</td>\n";
				if (! $i) $totalarray['nbfield']++;
			}

			// Supplier ref
			if (! empty($arrayfields['f.ref_supplier']['checked']))
			{
				print '<td class="nowrap">';
				print $obj->ref_supplier;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Type
			if (! empty($arrayfields['f.type']['checked']))
			{
				print '<td class="nowrap">';
				print $facturestatic->getLibType();
				print "</td>";
				if (! $i) $totalarray['nbfield']++;
			}

			// Label
			if (! empty($arrayfields['f.label']['checked']))
			{
				print '<td class="nowrap">';
				print $obj->label;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Date
			if (! empty($arrayfields['f.datef']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->datef),'day');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Date limit
			if (! empty($arrayfields['f.date_lim_reglement']['checked']))
			{
				print '<td align="center" class="nowrap">'.dol_print_date($datelimit,'day');
				if ($facturestatic->hasDelay())
				{
					print img_warning($langs->trans('Late'));
				}
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Project
			if (! empty($arrayfields['p.ref']['checked']))
			{
				print '<td class="nowrap">';
				if ($obj->project_id > 0)
				{
					$projectstatic->id=$obj->project_id;
					$projectstatic->ref=$obj->project_ref;
					$projectstatic->title=$obj->project_label;
					print $projectstatic->getNomUrl(1);
				}
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Third party
			if (! empty($arrayfields['s.nom']['checked']))
			{
				print '<td class="tdoverflowmax200">';
				print $thirdparty->getNomUrl(1,'supplier');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Town
			if (! empty($arrayfields['s.town']['checked']))
			{
				print '<td class="nocellnopadd">';
				print $obj->town;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Zip
			if (! empty($arrayfields['s.zip']['checked']))
			{
				print '<td class="nocellnopadd">';
				print $obj->zip;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// State
			if (! empty($arrayfields['state.nom']['checked']))
			{
				print "<td>".$obj->state_name."</td>\n";
				if (! $i) $totalarray['nbfield']++;
			}
			// Country
			if (! empty($arrayfields['country.code_iso']['checked']))
			{
				print '<td align="center">';
				$tmparray=getCountry($obj->fk_pays,'all');
				print $tmparray['label'];
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Type ent
			if (! empty($arrayfields['typent.code']['checked']))
			{
				print '<td align="center">';
				if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
				print $typenArray[$obj->typent_code];
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Payment mode
			if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
			{
				print '<td>';
				$form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Amount HT
			if (! empty($arrayfields['f.total_ht']['checked']))
			{
				  print '<td align="right">'.price($obj->total_ht)."</td>\n";
				  if (! $i) $totalarray['nbfield']++;
				  if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
				  $totalarray['totalht'] += $obj->total_ht;
			}
			// Amount VAT
			if (! empty($arrayfields['f.total_vat']['checked']))
			{
				print '<td align="right">'.price($obj->total_vat)."</td>\n";
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
				$totalarray['totalvat'] += $obj->total_vat;
			}
			// Amount LocalTax1
			if (! empty($arrayfields['f.total_localtax1']['checked']))
			{
				print '<td align="right">'.price($obj->total_localtax1)."</td>\n";
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totallocaltax1field']=$totalarray['nbfield'];
				$totalarray['totallocaltax1'] += $obj->total_localtax1;
			}
			// Amount LocalTax2
			if (! empty($arrayfields['f.total_localtax2']['checked']))
			{
				print '<td align="right">'.price($obj->total_localtax2)."</td>\n";
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totallocaltax2field']=$totalarray['nbfield'];
				$totalarray['totallocaltax2'] += $obj->total_localtax2;
			}
			// Amount TTC
			if (! empty($arrayfields['f.total_ttc']['checked']))
			{
				print '<td align="right">'.price($obj->total_ttc)."</td>\n";
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
				$totalarray['totalttc'] += $obj->total_ttc;
			}

			if (! empty($arrayfields['dynamount_payed']['checked']))
			{
				print '<td align="right">'.(! empty($totalpay)?price($totalpay,0,$langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totalamfield']=$totalarray['nbfield'];
				$totalarray['totalam'] += $totalpay;
			}

			if (! empty($arrayfields['rtp']['checked']))
			{
				print '<td align="right">'.(! empty($remaintopay)?price($remaintopay,0,$langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totalrtpfield']=$totalarray['nbfield'];
				$totalarray['totalrtp'] += $remaintopay;
			}


			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
			$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (! empty($arrayfields['f.datec']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Date modification
			if (! empty($arrayfields['f.tms']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Status
			if (! empty($arrayfields['f.fk_statut']['checked']))
			{
				print '<td align="right" class="nowrap">';
				// TODO $paiement is not yet defined
				print $facturestatic->LibStatut($obj->paye,$obj->fk_statut,5,$paiement,$obj->type);
				print "</td>";
				if (! $i) $totalarray['nbfield']++;
			}

			// Action column
			print '<td class="nowrap" align="center">';
			if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			{
				$selected=0;
				if (in_array($obj->facid, $arrayofselected)) $selected=1;
				print '<input id="cb'.$obj->facid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->facid.'"'.($selected?' checked="checked"':'').'>';
			}
			print '</td>';
			if (! $i) $totalarray['nbfield']++;

			print "</tr>\n";

			$i++;
		}

		// Show total line
		if (isset($totalarray['totalhtfield'])
 	   || isset($totalarray['totalvatfield'])
 	   || isset($totalarray['totallocaltax1field'])
 	   || isset($totalarray['totallocaltax2field'])
 	   || isset($totalarray['totalttcfield'])
 	   || isset($totalarray['totalamfield'])
 	   || isset($totalarray['totalrtpfield'])
 	   )
		{
			print '<tr class="liste_total">';
			$i=0;
			while ($i < $totalarray['nbfield'])
			{
			   $i++;
			   if ($i == 1)
			   {
					if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
					else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
			   }
			   elseif ($totalarray['totalhtfield'] == $i)  print '<td align="right">'.price($totalarray['totalht']).'</td>';
			   elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
			   elseif ($totalarray['totallocaltax1field'] == $i) print '<td align="right">'.price($totalarray['totallocaltax1']).'</td>';
			   elseif ($totalarray['totallocaltax2field'] == $i) print '<td align="right">'.price($totalarray['totallocaltax2']).'</td>';
			   elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
			   elseif ($totalarray['totalamfield'] == $i)  print '<td align="right">'.price($totalarray['totalam']).'</td>';
			   elseif ($totalarray['totalrtpfield'] == $i)  print '<td align="right">'.price($totalarray['totalrtp']).'</td>';
			   else print '<td></td>';
			}
			print '</tr>';

		}
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>\n";
	print '</div>';

	print "</form>\n";

	/*
	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

    // Show list of available documents
    $urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
    $urlsource.=str_replace('&amp;','&',$param);

    $filedir=$diroutputmassaction;
    $genallowed=$user->rights->facture->lire;
    $delallowed=$user->rights->facture->creer;

    print $formfile->showdocuments('massfilesarea_invoices','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'','','',null,$hidegeneratedfilelistifempty);
    */
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();

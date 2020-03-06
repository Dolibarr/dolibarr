<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016-2018 Ferran Marcet	 <fmarcet@2byte.es>
 * Copyright (C) 2017-2018 Charlene Benke	 <charlie@patas-monkey.com>
 * Copyright (C) 2018	   Nicolas ZABOURI	 <info@inovea-conseil.com>
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

$socid=GETPOST('socid','int');

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'proposallist';

$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refcustomer=GETPOST('search_refcustomer','alpha');

$search_refproject=GETPOST('search_refproject','alpha');

$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_vat=GETPOST('search_montant_vat','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');
$search_login=GETPOST('search_login','alpha');
$search_product_category=GETPOST('search_product_category','int');
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_day=GETPOST("search_day","int");
$search_month=GETPOST("search_month","int");
$search_year=GETPOST("search_year","int");
$search_dayfin=GETPOST("search_dayfin","int");
$search_month_end=GETPOST("search_month_end","int");
$search_yearfin=GETPOST("search_yearfin","int");
$search_daydelivery=GETPOST("search_daydelivery","int");
$search_monthdelivery=GETPOST("search_monthdelivery","int");
$search_yeardelivery=GETPOST("search_yeardelivery","int");
$search_availability=GETPOST('search_availability','int');
$search_categ_cus=trim(GETPOST("search_categ_cus",'int'));
$search_btn=GETPOST('button_search','alpha');
$search_remove_btn=GETPOST('button_removefilter','alpha');

$viewstatut=GETPOST('viewstatut','alpha');
$optioncss = GETPOST('optioncss','alpha');
$object_statut=GETPOST('search_statut','alpha');

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$mesg=(GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));


$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='p.ref';
if (! $sortorder) $sortorder='DESC';

// Security check
$module='propal';
$dbtable='';
$objectid='';
if (! empty($user->societe_id))	$socid=$user->societe_id;
if (! empty($socid))
{
	$objectid=$socid;
	$module='societe';
	$dbtable='&societe';
}
$result = restrictedArea($user, $module, $objectid, $dbtable);

$diroutputmassaction=$conf->propal->multidir_output[$conf->entity] . '/temp/massgeneration/'.$user->id;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Propal($db);
$hookmanager->initHooks(array('propallist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('propal');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref'=>'Ref',
	'p.ref_client'=>'CustomerRef',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	'p.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["p.note_private"]="NotePrivate";


$checkedtypetiers=0;
$arrayfields=array(
	'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'p.ref_client'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
	'pr.ref'=>array('label'=>$langs->trans("ProjectRef"), 'checked'=>1, 'enabled'=>(empty($conf->projet->enabled)?0:1)),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'p.date'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
	'p.fin_validite'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
	'p.date_livraison'=>array('label'=>$langs->trans("DeliveryDate"), 'checked'=>0),
	'ava.rowid'=>array('label'=>$langs->trans("AvailabilityPeriod"), 'checked'=>0),
	'p.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	'p.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
	'p.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
	'u.login'=>array('label'=>$langs->trans("Author"), 'checked'=>1, 'position'=>10),
	'sale_representative'=>array('label'=>$langs->trans("SaleRepresentativesOfThirdParty"), 'checked'=>1),
	'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'p.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
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
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
	$search_categ='';
	$search_user='';
	$search_sale='';
	$search_ref='';
	$search_refcustomer='';
	$search_refproject='';
	$search_societe='';
	$search_montant_ht='';
	$search_montant_vat='';
	$search_montant_ttc='';
	$search_login='';
	$search_product_category='';
	$search_town='';
	$search_zip="";
	$search_state="";
	$search_type='';
	$search_country='';
	$search_type_thirdparty='';
	$search_year='';
	$search_month='';
	$search_day='';
	$search_yearfin='';
	$search_month_end='';
	$search_dayfin='';
	$search_yeardelivery='';
	$search_monthdelivery='';
	$search_daydelivery='';
	$search_availability='';
	$viewstatut='';
	$object_statut='';
	$toselect='';
	$search_array_options=array();
	$search_categ_cus=0;
}
if ($object_statut != '') $viewstatut=$object_statut;

if (empty($reshook))
{
	$objectclass='Propal';
	$objectlabel='Proposals';
	$permtoread = $user->rights->propal->lire;
	$permtodelete = $user->rights->propal->supprimer;
	$permtoclose = $user->rights->propal->cloturer;
	$uploaddir = $conf->propal->multidir_output[$conf->entity];
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$companystatic=new Societe($db);
$projectstatic=new Project($db);
$formcompany=new FormCompany($db);

$help_url='EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos';
//llxHeader('',$langs->trans('Proposal'),$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.code_client, ';
$sql.= " typent.code as typent_code,";
$sql.= " ava.rowid as availability,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= ' p.rowid, p.entity, p.note_private, p.total_ht, p.tva as total_vat, p.total as total_ttc, p.localtax1, p.localtax2, p.ref, p.ref_client, p.fk_statut, p.fk_user_author, p.datep as dp, p.fin_validite as dfv,p.date_livraison as ddelivery,';
$sql.= ' p.datec as date_creation, p.tms as date_update,';
$sql.= " pr.rowid as project_id, pr.ref as project_ref, pr.title as project_label,";
$sql.= ' u.login';
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user";
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
if (! empty($search_categ_cus)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cc ON s.rowid = cc.fk_soc"; // We'll need this table joined to the select in order to filter by categ

$sql.= ', '.MAIN_DB_PREFIX.'propal as p';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields as ef on (p.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet as pd ON p.rowid=pd.fk_propal';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as pr ON pr.rowid = p.fk_projet";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_availability as ava on (ava.rowid = p.fk_availability)";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as c";
	$sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE p.fk_soc = s.rowid';
$sql.= ' AND p.entity IN ('.getEntity('propal').')';
if (! $user->rights->societe->client->voir && ! $socid) //restriction
{
	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
}
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip", $search_zip);
if ($search_state) $sql.= natural_search("state.nom", $search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$db->escape($search_country).')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$db->escape($search_type_thirdparty).')';
if ($search_ref)         $sql .= natural_search('p.ref', $search_ref);
if ($search_refcustomer) $sql .= natural_search('p.ref_client', $search_refcustomer);
if ($search_refproject)  $sql .= natural_search('pr.ref', $search_refproject);
if ($search_availability) $sql .= " AND p.fk_availability IN (".$db->escape($search_availability).')';

if ($search_societe)     $sql .= natural_search('s.nom', $search_societe);
if ($search_login)       $sql .= natural_search("u.login", $search_login);
if ($search_montant_ht != '')  $sql.= natural_search("p.total_ht", $search_montant_ht, 1);
if ($search_montant_vat != '') $sql.= natural_search("p.tva", $search_montant_vat, 1);
if ($search_montant_ttc != '') $sql.= natural_search("p.total", $search_montant_ttc, 1);
if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if ($search_categ_cus > 0) $sql.= " AND cc.fk_categorie = ".$db->escape($search_categ_cus);
if ($search_categ_cus == -2)   $sql.= " AND cc.fk_categorie IS NULL";

if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$db->escape($search_product_category);
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if ($viewstatut != '' && $viewstatut != '-1')
{
	$sql.= ' AND p.fk_statut IN ('.$db->escape($viewstatut).')';
}
if ($search_month > 0)
{
	if ($search_year > 0 && empty($search_day))
	$sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($search_year,$search_month,false))."' AND '".$db->idate(dol_get_last_day($search_year,$search_month,false))."'";
	else if ($search_year > 0 && ! empty($search_day))
	$sql.= " AND p.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $search_year))."'";
	else
	$sql.= " AND date_format(p.datep, '%m') = '".$db->escape($search_month)."'";
}
else if ($search_year > 0)
{
	$sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($search_year,1,false))."' AND '".$db->idate(dol_get_last_day($search_year,12,false))."'";
}
if ($search_month_end > 0)
{
	if ($search_yearfin > 0 && empty($search_dayfin))
		$sql.= " AND p.fin_validite BETWEEN '".$db->idate(dol_get_first_day($search_yearfin,$search_month_end,false))."' AND '".$db->idate(dol_get_last_day($search_yearfin,$search_month_end,false))."'";
	else if ($search_yearfin > 0 && ! empty($search_dayfin))
		$sql.= " AND p.fin_validite BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month_end, $search_dayfin, $search_yearfin))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month_end, $search_dayfin, $search_yearfin))."'";
	else
		$sql.= " AND date_format(p.fin_validite, '%m') = '".$db->escape($search_month_end)."'";
}
else if ($search_yearfin > 0)
{
	$sql.= " AND p.fin_validite BETWEEN '".$db->idate(dol_get_first_day($search_yearfin,1,false))."' AND '".$db->idate(dol_get_last_day($search_yearfin,12,false))."'";
}
if ($search_monthdelivery > 0)
{
	if ($search_yeardelivery > 0 && empty($search_daydelivery))
		$sql.= " AND p.date_livraison BETWEEN '".$db->idate(dol_get_first_day($search_yeardelivery,$search_monthdelivery,false))."' AND '".$db->idate(dol_get_last_day($search_yeardelivery,$search_monthdelivery,false))."'";
	else if ($search_yeardelivery > 0 && ! empty($search_daydelivery))
		$sql.= " AND p.date_livraison BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_monthdelivery, $search_daydelivery, $search_yeardelivery))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_monthdelivery, $search_daydelivery, $search_yeardelivery))."'";
	else
		$sql.= " AND date_format(p.date_livraison, '%m') = '".$db->escape($search_monthdelivery)."'";
}
else if ($search_yeardelivery > 0)
{
	$sql.= " AND p.date_livraison BETWEEN '".$db->idate(dol_get_first_day($search_yeardelivery,1,false))."' AND '".$db->idate(dol_get_last_day($search_yeardelivery,12,false))."'";
}
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$db->escape($search_sale);
if ($search_user > 0)
{
	$sql.= " AND c.fk_c_type_contact = tc.rowid AND tc.element='propal' AND tc.source='internal' AND c.element_id = p.rowid AND c.fk_socpeople = ".$db->escape($search_user);
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);
$sql.=', p.ref DESC';

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

$sql.= $db->plimit($limit+1, $offset);

$resql=$db->query($sql);

if ($resql)
{
	$objectstatic=new Propal($db);
	$userstatic=new User($db);

 	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfProposals') . ' - '.$soc->name;
		if (empty($search_societe)) $search_societe = $soc->name;
	}
	else
	{
		$title = $langs->trans('ListOfProposals');
	}

	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);

		$id = $obj->rowid;

		header("Location: ".DOL_URL_ROOT.'/comm/propal/card.php?id='.$id);
		exit;
	}

	llxHeader('',$langs->trans('Proposal'),$help_url);

	$param='&viewstatut='.urlencode($viewstatut);
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	if ($sall)				 $param.='&sall='.urlencode($sall);
	if ($search_day)         $param.='&search_day='.urlencode($search_day);
	if ($search_month)       $param.='&search_month='.urlencode($search_month);
	if ($search_year)        $param.='&search_year='.urlencode($search_year);
	if ($search_ref)         $param.='&search_ref='.urlencode($search_ref);
	if ($search_refcustomer) $param.='&search_refcustomer='.urlencode($search_refcustomer);
	if ($search_refproject)  $param.='&search_refproject='.urlencode($search_refproject);
	if ($search_societe)     $param.='&search_societe='.urlencode($search_societe);
	if ($search_user > 0)    $param.='&search_user='.urlencode($search_user);
	if ($search_sale > 0)    $param.='&search_sale='.urlencode($search_sale);
	if ($search_montant_ht)  $param.='&search_montant_ht='.urlencode($search_montant_ht);
	if ($search_login)  	 $param.='&search_login='.urlencode($search_login);
	if ($search_town)		 $param.='&search_town='.urlencode($search_town);
	if ($search_zip)		 $param.='&search_zip='.urlencode($search_zip);
	if ($socid > 0)          $param.='&socid='.urlencode($socid);
	if ($optioncss != '')    $param.='&optioncss='.urlencode($optioncss);
	if ($search_categ_cus > 0)          $param.='&search_categ_cus='.urlencode($search_categ_cus);
	if ($search_product_category != '') $param.='&search_product_category='.$search_product_category;

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array(
		'presend'=>$langs->trans("SendByMail"),
		'builddoc'=>$langs->trans("PDFMerge"),
	);
	if ($user->rights->propal->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
	if ($user->rights->propal->cloturer) $arrayofmassactions['closed']=$langs->trans("Close");
	if (in_array($massaction, array('presend','predelete','closed'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if ($user->rights->propal->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/comm/propal/card.php?action=create"><span class="valignmiddle">'.$langs->trans('NewPropal').'</span>';
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}

	// Lignes des champs de filtre
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_commercial.png', 0, $newcardbutton, '', $limit);

	$topicmail="SendPropalRef";
	$modelmail="proposal_send";
	$objecttmp=new Propal($db);
	$trackid='pro'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
	}

	$i = 0;

	$moreforfilter='';

 	// If the user can view prospects other than his'
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
	// If the user can view products
	if ($conf->categorie->enabled && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
		$moreforfilter.='</div>';
	}
	if (! empty($conf->categorie->enabled))
	{
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
	 	$moreforfilter.=$langs->trans('CustomersProspectsCategoriesShort').': ';
		$moreforfilter.=$formother->select_categories('customer',$search_categ_cus,'search_categ_cus',1);
	 	$moreforfilter.='</div>';
	}
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (! empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	$selectedfields.=(count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre_filter">';
	if (! empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
 		print '</td>';
	}
	if (! empty($arrayfields['p.ref_client']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.dol_escape_htmltag($search_refcustomer).'">';
	   print '</td>';
	}
	if (! empty($arrayfields['pr.ref']['checked']))
	{
    	print '<td class="liste_titre">';
    	print '<input class="flat" size="6" type="text" name="search_refproject" value="'.dol_escape_htmltag($search_refproject).'">';
	   print '</td>';
	}
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="10" name="search_societe" value="'.dol_escape_htmltag($search_societe).'">';
	   print '</td>';
	}
	if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';
	if (! empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.$search_zip.'"></td>';
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
		print ajax_combobox('search_type_thirdparty');
		print '</td>';
	}
	// Date
	if (! empty($arrayfields['p.date']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		//print $langs->trans('Month').': ';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25" type="text" maxlength="2" name="search_day" value="'.dol_escape_htmltag($search_day).'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_month" value="'.dol_escape_htmltag($search_month).'">';
		//print '&nbsp;'.$langs->trans('Year').': ';
		$formother->select_year($search_year,'search_year',1, 20, 5);
		print '</td>';
	}
	// Date end
	if (! empty($arrayfields['p.fin_validite']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		//print $langs->trans('Month').': ';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25" type="text" maxlength="2" name="search_dayfin" value="'.dol_escape_htmltag($search_dayfin).'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_month_end" value="'.dol_escape_htmltag($search_month_end).'">';
		//print '&nbsp;'.$langs->trans('Year').': ';
		$formother->select_year($search_yearfin,'search_yearfin',1, 20, 5);
		print '</td>';
	}
	// Date delivery
	if (! empty($arrayfields['p.date_livraison']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		//print $langs->trans('Month').': ';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25" type="text" size="1" maxlength="2" name="search_daydelivery" value="'.dol_escape_htmltag($search_daydelivery).'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_monthdelivery" value="'.dol_escape_htmltag($search_monthdelivery).'">';
		//print '&nbsp;'.$langs->trans('Year').': ';
		$formother->select_year($search_yeardelivery,'search_yeardelivery',1, 20, 5);
		print '</td>';
	}
	// Availability
	if (! empty($arrayfields['ava.rowid']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="center">';
		print $form->selectAvailabilityDelay($search_availability, 'search_availability', '', 1);
		print ajax_combobox('search_availability');
		print '</td>';
	}
	if (! empty($arrayfields['p.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
		print '</td>';
	}
	if (! empty($arrayfields['p.total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
		print '</td>';
	}
	if (! empty($arrayfields['p.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
		print '</td>';
	}
	if (! empty($arrayfields['u.login']['checked']))
	{
		// Author
		print '<td class="liste_titre" align="center">';
		print '<input class="flat" size="4" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
		print '</td>';
	}
	if (! empty($arrayfields['sale_representative']['checked']))
	{
		print '<td class="liste_titre"></td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['p.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['p.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (! empty($arrayfields['p.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="right">';
		$formpropal->selectProposalStatus($viewstatut, 1, 0, 1, 'customer', 'search_statut');
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";


	// Fields title
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['p.ref']['checked']))            print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['p.ref_client']['checked']))     print_liste_field_titre($arrayfields['p.ref_client']['label'],$_SERVER["PHP_SELF"],'p.ref_client','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['pr.ref']['checked']))     	print_liste_field_titre($arrayfields['pr.ref']['label'],$_SERVER["PHP_SELF"],'pr.ref','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.date']['checked']))           print_liste_field_titre($arrayfields['p.date']['label'],$_SERVER["PHP_SELF"],'p.datep','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.fin_validite']['checked']))   print_liste_field_titre($arrayfields['p.fin_validite']['label'],$_SERVER["PHP_SELF"],'dfv','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.date_livraison']['checked'])) print_liste_field_titre($arrayfields['p.date_livraison']['label'],$_SERVER["PHP_SELF"],'ddelivery','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['ava.rowid']['checked']))        print_liste_field_titre($arrayfields['ava.rowid']['label'],$_SERVER["PHP_SELF"],'availability','',$param, '',$sortfield,$sortorder);
	if (! empty($arrayfields['p.total_ht']['checked']))       print_liste_field_titre($arrayfields['p.total_ht']['label'],$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.total_vat']['checked']))      print_liste_field_titre($arrayfields['p.total_vat']['label'],$_SERVER["PHP_SELF"],'p.tva','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.total_ttc']['checked']))      print_liste_field_titre($arrayfields['p.total_ttc']['label'],$_SERVER["PHP_SELF"],'p.total','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['u.login']['checked']))       	  print_liste_field_titre($arrayfields['u.login']['label'],$_SERVER["PHP_SELF"],'u.login','',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['sale_representative']['checked'])) print_liste_field_titre($arrayfields['sale_representative']['label'], $_SERVER["PHP_SELF"], "","","$param",'',$sortfield,$sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['p.datec']['checked']))     print_liste_field_titre($arrayfields['p.datec']['label'],$_SERVER["PHP_SELF"],"p.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.tms']['checked']))       print_liste_field_titre($arrayfields['p.tms']['label'],$_SERVER["PHP_SELF"],"p.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.fk_statut']['checked'])) print_liste_field_titre($arrayfields['p.fk_statut']['label'],$_SERVER["PHP_SELF"],"p.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print '</tr>'."\n";

	$now = dol_now();
	$i=0;
	$totalarray=array();
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

		$objectstatic->id=$obj->rowid;
		$objectstatic->ref=$obj->ref;

		$companystatic->id=$obj->socid;
		$companystatic->name=$obj->name;
		$companystatic->client=$obj->client;
		$companystatic->code_client=$obj->code_client;
		$companystatic->email=$obj->email;

		print '<tr class="oddeven">';

		if (! empty($arrayfields['p.ref']['checked']))
		{
			print '<td class="nowrap">';

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			// Picto + Ref
			print '<td class="nobordernopadding nowrap">';
			print $objectstatic->getNomUrl(1, '', '', 0, 1);
			print '</td>';
			// Warning
			$warnornote='';
			if ($obj->fk_statut == 1 && $db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) $warnornote.=img_warning($langs->trans("Late"));
			if (! empty($obj->note_private))
			{
				$warnornote.=($warnornote?' ':'');
				$warnornote.= '<span class="note">';
				$warnornote.= '<a href="note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
				$warnornote.= '</span>';
			}
			if ($warnornote)
			{
				print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
				print $warnornote;
				print '</td>';
			}
			// Other picto tool
			print '<td width="16" align="right" class="nobordernopadding">';
			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->propal->multidir_output[$obj->entity] . '/' . dol_sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		if (! empty($arrayfields['p.ref_client']['checked']))
		{
			// Customer ref
			print '<td class="nocellnopadd nowrap">';
			print $obj->ref_client;
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		if (! empty($arrayfields['pr.ref']['checked']))
		{
    		// Project ref
    		print '<td class="nocellnopadd nowrap">';
    		if ($obj->project_id > 0) {
    		    $projectstatic->id=$obj->project_id;
    		    $projectstatic->ref=$obj->project_ref;
    		    $projectstatic->title=$obj->project_label;
				print $projectstatic->getNomUrl(1);
			}
    		print '</td>';
    		if (! $i) $totalarray['nbfield']++;
		}

		// Thirdparty
		if (! empty($arrayfields['s.nom']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $companystatic->getNomUrl(1,'customer');
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

		// Date proposal
		if (! empty($arrayfields['p.date']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->dp), 'day');
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Date end validity
		if (! empty($arrayfields['p.fin_validite']['checked']))
		{
			if ($obj->dfv)
			{
				print '<td align="center">'.dol_print_date($db->jdate($obj->dfv),'day');
				print '</td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
			if (! $i) $totalarray['nbfield']++;
		}
		// Date delivery
		if (! empty($arrayfields['p.date_livraison']['checked']))
		{
			if ($obj->ddelivery)
			{
				print '<td align="center">'.dol_print_date($db->jdate($obj->ddelivery),'day');
				print '</td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
			if (! $i) $totalarray['nbfield']++;
		}
		// Availability
		if (! empty($arrayfields['ava.rowid']['checked']))
		{
			print '<td align="center">';
			$form->form_availability('', $obj->availability, 'none', 1);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Amount HT
		if (! empty($arrayfields['p.total_ht']['checked']))
		{
			  print '<td align="right">'.price($obj->total_ht)."</td>\n";
			  if (! $i) $totalarray['nbfield']++;
			  if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
			  $totalarray['totalht'] += $obj->total_ht;
		}
		// Amount VAT
		if (! empty($arrayfields['p.total_vat']['checked']))
		{
			print '<td align="right">'.price($obj->total_vat)."</td>\n";
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
			$totalarray['totalvat'] += $obj->total_vat;
		}
		// Amount TTC
		if (! empty($arrayfields['p.total_ttc']['checked']))
		{
			print '<td align="right">'.price($obj->total_ttc)."</td>\n";
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
			$totalarray['totalttc'] += $obj->total_ttc;
		}

		$userstatic->id=$obj->fk_user_author;
		$userstatic->login=$obj->login;

		// Author
		if (! empty($arrayfields['u.login']['checked']))
		{
			print '<td align="center">';
			if ($userstatic->id) print $userstatic->getLoginUrl(1);
			else print '&nbsp;';
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		if (! empty($arrayfields['sale_representative']['checked']))
		{
			// Sales representatives
			print '<td>';
			if ($obj->socid > 0)
			{
				$listsalesrepresentatives=$companystatic->getSalesRepresentatives($user);
				if ($listsalesrepresentatives < 0) dol_print_error($db);
				$nbofsalesrepresentative=count($listsalesrepresentatives);
				if ($nbofsalesrepresentative > 3)   // We print only number
				{
					print $nbofsalesrepresentative;
				}
				else if ($nbofsalesrepresentative > 0)
				{
					$userstatic=new User($db);
					$j=0;
					foreach($listsalesrepresentatives as $val)
					{
						$userstatic->id=$val['id'];
						$userstatic->lastname=$val['lastname'];
						$userstatic->firstname=$val['firstname'];
						$userstatic->email=$val['email'];
						$userstatic->statut=$val['statut'];
						$userstatic->entity=$val['entity'];
						$userstatic->photo=$val['photo'];

						//print '<div class="float">':
						print $userstatic->getNomUrl(-2);
						$j++;
						if ($j < $nbofsalesrepresentative) print ' ';
						//print '</div>';
					}
				}
				//else print $langs->trans("NoSalesRepresentativeAffected");
			}
			else
			{
				print '&nbsp';
			}
			print '</td>';
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (! empty($arrayfields['p.datec']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date modification
		if (! empty($arrayfields['p.tms']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Status
		if (! empty($arrayfields['p.fk_statut']['checked']))
		{
			print '<td align="right" class="nowrap">'.$objectstatic->LibStatut($obj->fk_statut,5).'</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Action column
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected=0;
			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";

		$i++;
	}

	// Show total line
	if (isset($totalarray['totalhtfield'])
 	   || isset($totalarray['totalvatfield'])
 	   || isset($totalarray['totalttcfield'])
 	   || isset($totalarray['totalamfield'])
 	   || isset($totalarray['totalrtpfield'])
 	   || isset($totalarray['totalizable'])
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
		    elseif ($totalarray['totalhtfield'] == $i) print '<td align="right">'.price($totalarray['totalht']).'</td>';
		    elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
		    elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
		    elseif ($totalarray['totalizable']) {
                $printed = false;
                foreach ($totalarray['totalizable'] as $totalizable) {
                    if ($totalizable['pos']==$i && ! $printed) {
                        print '<td align="right">'.price($totalizable['total']).'</td>';
                        $printed = true;
                    }
                }
                if (! $printed) {
                    print '<td></td>';
                }
            }
		    else print '<td></td>';
		}
		print '</tr>';
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>'."\n";

	print '</form>'."\n";

	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->propal->lire;
	$delallowed=$user->rights->propal->creer;

	print $formfile->showdocuments('massfilesarea_proposals','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'','','',null,$hidegeneratedfilelistifempty);
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();

<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville   <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin          <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Juanjo Menent          <jmenent@2byte.es>
 * Copyright (C) 2013      Christophe Battarel    <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador        <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2015      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry    <jfefe@aternatik.fr>
 * Copyright (C) 2016	   Ferran Marcet		  <fmarcet@2byte.es>
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
$langs->loadLangs(array("orders",'sendings','deliveries','companies','compta','bills'));

$action=GETPOST('action','aZ09');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'orderlist';

$search_orderyear=GETPOST("search_orderyear","int");
$search_ordermonth=GETPOST("search_ordermonth","int");
$search_orderday=GETPOST("search_orderday","int");
$search_deliveryyear=GETPOST("search_deliveryyear","int");
$search_deliverymonth=GETPOST("search_deliverymonth","int");
$search_deliveryday=GETPOST("search_deliveryday","int");
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('search_ref','alpha')!=''?GETPOST('search_ref','alpha'):GETPOST('sref','alpha');
$search_ref_customer=GETPOST('search_ref_customer','alpha');
$search_company=GETPOST('search_company','alpha');
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$socid=GETPOST('socid','int');
$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_total_ht=GETPOST('search_total_ht','alpha');
$search_categ_cus=trim(GETPOST("search_categ_cus",'int'));
$optioncss = GETPOST('optioncss','alpha');
$billed = GETPOST('billed','int');
$viewstatut=GETPOST('viewstatut');
$search_btn=GETPOST('button_search','alpha');
$search_remove_btn=GETPOST('button_removefilter','alpha');
$search_project_ref=GETPOST('search_project_ref','alpha');

// Security check
$id = (GETPOST('orderid')?GETPOST('orderid','int'):GETPOST('id','int'));
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande', $id,'');

$diroutputmassaction=$conf->commande->dir_output . '/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='c.ref';
if (! $sortorder) $sortorder='DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Commande($db);
$hookmanager->initHooks(array('orderlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('commande');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'c.ref'=>'Ref',
	'c.ref_client'=>'RefCustomerOrder',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	'c.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["c.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
	'c.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'c.ref_client'=>array('label'=>$langs->trans("RefCustomerOrder"), 'checked'=>1),
	'p.project_ref'=>array('label'=>$langs->trans("ProjectRef"), 'checked'=>0, 'enabled'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'c.date_commande'=>array('label'=>$langs->trans("OrderDateShort"), 'checked'=>1),
	'c.date_delivery'=>array('label'=>$langs->trans("DateDeliveryPlanned"), 'checked'=>1, 'enabled'=>empty($conf->global->ORDER_DISABLE_DELIVERY_DATE)),
	'c.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	'c.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
	'c.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
	'c.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'c.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'c.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
	'c.facture'=>array('label'=>$langs->trans("Billed"), 'checked'=>1, 'position'=>1000, 'enabled'=>(empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
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
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		$search_categ='';
		$search_user='';
		$search_sale='';
		$search_product_category='';
		$search_ref='';
		$search_ref_customer='';
		$search_company='';
		$search_town='';
		$search_zip="";
		$search_state="";
		$search_type='';
		$search_country='';
		$search_type_thirdparty='';
		$search_total_ht='';
		$search_total_vat='';
		$search_total_ttc='';
		$search_orderyear='';
		$search_ordermonth='';
		$search_orderday='';
		$search_deliveryday='';
		$search_deliverymonth='';
		$search_deliveryyear='';
		$search_project_ref='';
		$viewstatut='';
		$billed='';
		$toselect='';
		$search_array_options=array();
		$search_categ_cus=0;
	}
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')
	 || GETPOST('button_search_x','alpha') || GETPOST('button_search.x','alpha') || GETPOST('button_search','alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass='Commande';
	$objectlabel='Orders';
	$permtoread = $user->rights->commande->lire;
	$permtodelete = $user->rights->commande->supprimer;
	$uploaddir = $conf->commande->dir_output;
	$trigger_name='ORDER_SENTBYMAIL';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

}


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);
$formcompany=new FormCompany($db);
$projectstatic=new Project($db);

$title=$langs->trans("Orders");
$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
llxHeader('',$title,$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= ' c.rowid, c.ref, c.total_ht, c.tva as total_tva, c.total_ttc, c.ref_client,';
$sql.= ' c.date_valid, c.date_commande, c.note_private, c.date_livraison as date_delivery, c.fk_statut, c.facture as billed,';
$sql.= ' c.date_creation as date_creation, c.tms as date_update,';
$sql.= " p.rowid as project_id, p.ref as project_ref";
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";

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
if (! empty($search_categ_cus)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cc ON s.rowid = cc.fk_soc"; // We'll need this table joined to the select in order to filter by categ
$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields as ef on (c.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet as pd ON c.rowid=pd.fk_commande';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = c.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE c.fk_soc = s.rowid';
$sql.= ' AND c.entity IN ('.getEntity('commande').')';
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_ref) $sql .= natural_search('c.ref', $search_ref);
if ($search_ref_customer) $sql.= natural_search('c.ref_client', $search_ref_customer);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($billed != '' && $billed >= 0) $sql.=' AND c.facture = '.$billed;
if ($viewstatut <> '')
{
	if ($viewstatut < 4 && $viewstatut > -3)
	{
		if ($viewstatut == 1 && empty($conf->expedition->enabled)) $sql.= ' AND c.fk_statut IN (1,2)';	// If module expedition disabled, we include order with status 'sending in process' into 'validated'
		else $sql.= ' AND c.fk_statut = '.$viewstatut; // brouillon, validee, en cours, annulee
	}
	if ($viewstatut == 4)
	{
		$sql.= ' AND c.facture = 1'; // invoice created
	}
	if ($viewstatut == -2)	// To process
	{
		//$sql.= ' AND c.fk_statut IN (1,2,3) AND c.facture = 0';
		$sql.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";    // If status is 2 and facture=1, it must be selected
	}
	if ($viewstatut == -3)	// To bill
	{
		//$sql.= ' AND c.fk_statut in (1,2,3)';
		//$sql.= ' AND c.facture = 0'; // invoice not created
		$sql .= ' AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))'; // validated, in process or closed but not billed
	}
}
if ($search_ordermonth > 0)
{
	if ($search_orderyear > 0 && empty($search_orderday))
	$sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_get_first_day($search_orderyear,$search_ordermonth,false))."' AND '".$db->idate(dol_get_last_day($search_orderyear,$search_ordermonth,false))."'";
	else if ($search_orderyear > 0 && ! empty($search_orderday))
	$sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_ordermonth, $search_orderday, $search_orderyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_ordermonth, $search_orderday, $search_orderyear))."'";
	else
	$sql.= " AND date_format(c.date_commande, '%m') = '".$search_ordermonth."'";
}
else if ($search_orderyear > 0)
{
	$sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_get_first_day($search_orderyear,1,false))."' AND '".$db->idate(dol_get_last_day($search_orderyear,12,false))."'";
}
if ($search_deliverymonth > 0)
{
	if ($search_deliveryyear > 0 && empty($search_deliveryday))
	$sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_get_first_day($search_deliveryyear,$search_deliverymonth,false))."' AND '".$db->idate(dol_get_last_day($search_deliveryyear,$search_deliverymonth,false))."'";
	else if ($search_deliveryyear > 0 && ! empty($search_deliveryday))
	$sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_deliverymonth, $search_deliveryday, $search_deliveryyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_deliverymonth, $search_deliveryday, $search_deliveryyear))."'";
	else
	$sql.= " AND date_format(c.date_livraison, '%m') = '".$search_deliverymonth."'";
}
else if ($search_deliveryyear > 0)
{
	$sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_get_first_day($search_deliveryyear,1,false))."' AND '".$db->idate(dol_get_last_day($search_deliveryyear,12,false))."'";
}
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0) $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='commande' AND tc.source='internal' AND ec.element_id = c.rowid AND ec.fk_socpeople = ".$search_user;
if ($search_total_ht != '') $sql.= natural_search('c.total_ht', $search_total_ht, 1);
if ($search_project_ref != '') $sql.= natural_search("p.ref",$search_project_ref);
if ($search_categ_cus > 0) $sql.= " AND cc.fk_categorie = ".$db->escape($search_categ_cus);
if ($search_categ_cus == -2)   $sql.= " AND cc.fk_categorie IS NULL";
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);

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

$sql.= $db->plimit($limit + 1,$offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfOrders') . ' - '.$soc->name;
		if (empty($search_company)) $search_company = $soc->name;
	}
	else
	{
		$title = $langs->trans('ListOfOrders');
	}
	if (strval($viewstatut) == '0')
	$title.=' - '.$langs->trans('StatusOrderDraftShort');
	if ($viewstatut == 1)
	$title.=' - '.$langs->trans('StatusOrderValidatedShort');
	if ($viewstatut == 2)
	$title.=' - '.$langs->trans('StatusOrderSentShort');
	if ($viewstatut == 3)
	$title.=' - '.$langs->trans('StatusOrderToBillShort');
	if ($viewstatut == 4)
	$title.=' - '.$langs->trans('StatusOrderProcessedShort');
	if ($viewstatut == -1)
	$title.=' - '.$langs->trans('StatusOrderCanceledShort');
	if ($viewstatut == -2)
	$title.=' - '.$langs->trans('StatusOrderToProcessShort');
	if ($viewstatut == -3)
	$title.=' - '.$langs->trans('StatusOrderValidated').', '.(empty($conf->expedition->enabled)?'':$langs->trans("StatusOrderSent").', ').$langs->trans('StatusOrderToBill');

	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	$param='';

	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	if ($sall)					$param.='&sall='.urlencode($sall);
	if ($socid > 0)             $param.='&socid='.urlencode($socid);
	if ($viewstatut != '')      $param.='&viewstatut='.urlencode($viewstatut);
	if ($search_orderday)      		$param.='&search_orderday='.urlencode($search_orderday);
	if ($search_ordermonth)      		$param.='&search_ordermonth='.urlencode($search_ordermonth);
	if ($search_orderyear)       		$param.='&search_orderyear='.urlencode($search_orderyear);
	if ($search_deliveryday)   		$param.='&search_deliveryday='.urlencode($search_deliveryday);
	if ($search_deliverymonth)   		$param.='&search_deliverymonth='.urlencode($search_deliverymonth);
	if ($search_deliveryyear)    		$param.='&search_deliveryyear='.urlencode($search_deliveryyear);
	if ($search_ref)      		$param.='&search_ref='.urlencode($search_ref);
	if ($search_company)  		$param.='&search_company='.urlencode($search_company);
	if ($search_ref_customer)	$param.='&search_ref_customer='.urlencode($search_ref_customer);
	if ($search_user > 0) 		$param.='&search_user='.urlencode($search_user);
	if ($search_sale > 0) 		$param.='&search_sale='.urlencode($search_sale);
	if ($search_total_ht != '') $param.='&search_total_ht='.urlencode($search_total_ht);
	if ($search_total_vat != '')  $param.='&search_total_vat='.urlencode($search_total_vat);
	if ($search_total_ttc != '')  $param.='&search_total_ttc='.urlencode($search_total_ttc);
	if ($search_project_ref >= 0) $param.="&search_project_ref=".urlencode($search_project_ref);
	if ($search_town != '')       $param.='&search_town='.urlencode($search_town);
	if ($search_zip != '')        $param.='&search_zip='.urlencode($search_zip);
	if ($search_state != '')      $param.='&search_state='.urlencode($search_state);
	if ($search_country != '')    $param.='&search_country='.urlencode($search_country);
	if ($search_type_thirdparty != '')  $param.='&search_type_thirdparty='.urlencode($search_type_thirdparty);
	if ($search_product_category != '') $param.='&search_product_category='.urlencode($search_product_category);
	if ($search_categ_cus > 0)          $param.='&search_categ_cus='.urlencode($search_categ_cus);
	if ($show_files)            $param.='&show_files=' .urlencode($show_files);
	if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);
	if ($billed != '')			$param.='&billed='.urlencode($billed);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array(
		'presend'=>$langs->trans("SendByMail"),
		'builddoc'=>$langs->trans("PDFMerge"),
		'cancelorders'=>$langs->trans("Cancel"),

	);
	if($user->rights->facture->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
	if ($user->rights->commande->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
	if (in_array($massaction, array('presend','predelete','createbills'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if ($contextpage == 'orderlist' && $user->rights->commande->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/commande/card.php?action=create"><span class="valignmiddle">'.$langs->trans('NewOrder').'</span>';
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}

	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';


	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_commercial.png', 0, $newcardbutton, '', $limit);

	$topicmail="SendOrderRef";
	$modelmail="order_send";
	$objecttmp=new Commande($db);
	$trackid='ord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($massaction == 'createbills')
	{
		//var_dump($_REQUEST);
		print '<input type="hidden" name="massaction" value="confirm_createbills">';

		print '<table class="noborder" width="100%" >';
		print '<tr>';
		print '<td class="titlefield">';
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
		if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_BILL))
		{
			print $form->selectyesno('valdate_invoices', 0, 1, 1);
			print ' ('.$langs->trans("AutoValidationNotPossibleWhenStockIsDecreasedOnInvoiceValidation").')';
		}
		else
		{
			print $form->selectyesno('valdate_invoices', 0, 1);
		}
		if (! empty($conf->workflow->enabled) && ! empty($conf->global->WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER)) print ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("IfValidateInvoiceIsNoOrderStayUnbilled").'</span>';
		else print ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("OptionToSetOrderBilledNotEnabled").'</span>';
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

	if ($sall)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
	}

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
	// If the user can view other users
	if ($user->rights->user->user->lire)
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
	$selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre_filter">';
	// Ref
	if (! empty($arrayfields['c.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	}
	// Ref customer
	if (! empty($arrayfields['c.ref_client']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="6" name="search_ref_customer" value="'.$search_ref_customer.'">';
		print '</td>';
	}
	// Project ref
	if (! empty($arrayfields['p.project_ref']['checked']))
	{
		print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_project_ref" value="'.$search_project_ref.'"></td>';
	}
	// Thirpdarty
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_company" value="'.$search_company.'">';
		print '</td>';
	}
	// Town
	if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_town" value="'.$search_town.'"></td>';
	// Zip
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
		print '</td>';
	}
	// Date order
	if (! empty($arrayfields['c.date_commande']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_orderday" value="'.$search_orderday.'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_ordermonth" value="'.$search_ordermonth.'">';
		$formother->select_year($search_orderyear?$search_orderyear:-1,'search_orderyear',1, 20, 5);
		print '</td>';
	}
	if (! empty($arrayfields['c.date_delivery']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_deliveryday" value="'.$search_deliveryday.'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_deliverymonth" value="'.$search_deliverymonth.'">';
		$formother->select_year($search_deliveryyear?$search_deliveryyear:-1,'search_deliveryyear',1, 20, 5);
		print '</td>';
	}
	if (! empty($arrayfields['c.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="4" name="search_total_ht" value="'.$search_total_ht.'">';
		print '</td>';
	}
	if (! empty($arrayfields['c.total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="4" name="search_total_vat" value="'.$search_total_vat.'">';
		print '</td>';
	}
	if (! empty($arrayfields['c.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_total_ttc" value="'.$search_total_ttc.'">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['c.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['c.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (! empty($arrayfields['c.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="right">';
		$liststatus=array(
			Commande::STATUS_DRAFT=>$langs->trans("StatusOrderDraftShort"),
			Commande::STATUS_VALIDATED=>$langs->trans("StatusOrderValidated"),
			Commande::STATUS_SHIPMENTONPROCESS=>$langs->trans("StatusOrderSentShort"),
			Commande::STATUS_CLOSED=>$langs->trans("StatusOrderDelivered"),
			-3=>$langs->trans("StatusOrderValidatedShort").'+'.$langs->trans("StatusOrderSentShort").'+'.$langs->trans("StatusOrderDelivered"),
			Commande::STATUS_CANCELED=>$langs->trans("StatusOrderCanceledShort")
		);
		print $form->selectarray('viewstatut', $liststatus, $viewstatut, -4, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
		print '</td>';
	}
	// Status billed
	if (! empty($arrayfields['c.facture']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="center">';
		print $form->selectyesno('billed', $billed, 1, 0, 1);
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
	if (! empty($arrayfields['c.ref']['checked']))            print_liste_field_titre($arrayfields['c.ref']['label'],$_SERVER["PHP_SELF"],'c.ref','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['c.ref_client']['checked']))     print_liste_field_titre($arrayfields['c.ref_client']['label'],$_SERVER["PHP_SELF"],'c.ref_client','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['p.project_ref']['checked'])) 	  print_liste_field_titre($arrayfields['p.project_ref']['label'],$_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.date_commande']['checked']))  print_liste_field_titre($arrayfields['c.date_commande']['label'],$_SERVER["PHP_SELF"],'c.date_commande','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.date_delivery']['checked']))  print_liste_field_titre($arrayfields['c.date_delivery']['label'],$_SERVER["PHP_SELF"],'c.date_livraison','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.total_ht']['checked']))       print_liste_field_titre($arrayfields['c.total_ht']['label'],$_SERVER["PHP_SELF"],'c.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.total_vat']['checked']))      print_liste_field_titre($arrayfields['c.total_vat']['label'],$_SERVER["PHP_SELF"],'c.tva','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.total_ttc']['checked']))      print_liste_field_titre($arrayfields['c.total_ttc']['label'],$_SERVER["PHP_SELF"],'c.total_ttc','',$param, 'align="right"',$sortfield,$sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['c.datec']['checked']))     print_liste_field_titre($arrayfields['c.datec']['label'],$_SERVER["PHP_SELF"],"c.date_creation","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.tms']['checked']))       print_liste_field_titre($arrayfields['c.tms']['label'],$_SERVER["PHP_SELF"],"c.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.fk_statut']['checked'])) print_liste_field_titre($arrayfields['c.fk_statut']['label'],$_SERVER["PHP_SELF"],"c.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.facture']['checked']))   print_liste_field_titre($arrayfields['c.facture']['label'],$_SERVER["PHP_SELF"],'c.facture','',$param,'align="center"',$sortfield,$sortorder,'');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'',$param,'align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print '</tr>'."\n";

	$total=0;
	$subtotal=0;
	$productstat_cache=array();

	$generic_commande = new Commande($db);
	$generic_product = new Product($db);

	$i=0;
	$totalarray=array();
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

		$notshippable=0;
		$warning = 0;
		$text_info='';
		$text_warning='';
		$nbprod=0;

		$companystatic->id=$obj->socid;
		$companystatic->code_client = $obj->code_client;
		$companystatic->name=$obj->name;
		$companystatic->client=$obj->client;
		$companystatic->email=$obj->email;

		$generic_commande->id=$obj->rowid;
		$generic_commande->ref=$obj->ref;
		$generic_commande->statut = $obj->fk_statut;
		$generic_commande->date_commande = $db->jdate($obj->date_commande);
		$generic_commande->date_livraison = $db->jdate($obj->date_delivery);
		$generic_commande->ref_client = $obj->ref_client;
		$generic_commande->total_ht = $obj->total_ht;
		$generic_commande->total_tva = $obj->total_tva;
		$generic_commande->total_ttc = $obj->total_ttc;

		print '<tr class="oddeven">';

		// Ref
		if (! empty($arrayfields['c.ref']['checked']))
		{
			print '<td class="nowrap">';

			$generic_commande->lines=array();
			$generic_commande->getLinesArray();

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding nowrap">';
			print $generic_commande->getNomUrl(1, ($viewstatut != 2?0:$obj->fk_statut), 0, 0, 0, 1);
			print '</td>';

			// Show shippable Icon (create subloop, so may be slow)
			if ($conf->stock->enabled)
			{
				$langs->load("stocks");
				if (($obj->fk_statut > 0) && ($obj->fk_statut < 3))
				{
					$numlines = count($generic_commande->lines); // Loop on each line of order
					for ($lig=0; $lig < $numlines; $lig++)
					{
						if ($generic_commande->lines[$lig]->product_type == 0 && $generic_commande->lines[$lig]->fk_product > 0)  // If line is a product and not a service
						{
							$nbprod++; // order contains real products
							$generic_product->id = $generic_commande->lines[$lig]->fk_product;

							// Get local and virtual stock and store it into cache
							if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product])) {
								$generic_product->load_stock('nobatch');
								//$generic_product->load_virtual_stock();   Already included into load_stock
								$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_reel;
								$productstat_cachevirtual[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_theorique;
							} else {
								$generic_product->stock_reel = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'];
								$generic_product->stock_theorique = $productstat_cachevirtual[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_theorique;
							}

							if (empty($conf->global->SHIPPABLE_ORDER_ICON_IN_LIST))  // Default code. Default is when this option is not set, setting it create strange result
							{
								$text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
								$text_info .= ' - '.$langs->trans("Stock").': '.$generic_product->stock_reel;
								$text_info .= ' - '.$langs->trans("VirtualStock").': '.$generic_product->stock_theorique;
								$text_info .= '<br>';

								if ($generic_commande->lines[$lig]->qty > $generic_product->stock_reel)
								{
									$notshippable++;
								}
							}
							else {  // Detailed code, looks bugged
								// stock order and stock order_supplier
								$stock_order=0;
								$stock_order_supplier=0;
								if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE))    // What about other options ?
								{
									if (! empty($conf->commande->enabled))
									{
										if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'])) {
											$generic_product->load_stats_commande(0,'1,2');
											$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'] = $generic_product->stats_commande['qty'];
										} else {
											$generic_product->stats_commande['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'];
										}
										$stock_order=$generic_product->stats_commande['qty'];
									}
									if (! empty($conf->fournisseur->enabled))
									{
										if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'])) {
											$generic_product->load_stats_commande_fournisseur(0,'3');
											$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'] = $generic_product->stats_commande_fournisseur['qty'];
										} else {
											$generic_product->stats_commande_fournisseur['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'];
										}
										$stock_order_supplier=$generic_product->stats_commande_fournisseur['qty'];
									}
								}
								$text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
								$text_stock_reel = $generic_product->stock_reel.'/'.$stock_order;
								if ($stock_order > $generic_product->stock_reel && ! ($generic_product->stock_reel < $generic_commande->lines[$lig]->qty)) {
									$warning++;
									$text_warning.='<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								}
								if ($generic_product->stock_reel < $generic_commande->lines[$lig]->qty) {
									$notshippable++;
									$text_info.='<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								} else {
									$text_info.='<span class="ok">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								}
								if (! empty($conf->fournisseur->enabled)) {
									$text_info.= '&nbsp;'.$langs->trans('SupplierOrder').'&nbsp;:&nbsp;'.$stock_order_supplier.'<br>';
								} else {
									$text_info.= '<br>';
								}
							}
						}
					}
					if ($notshippable==0) {
						$text_icon = img_picto('', 'object_sending');
						$text_info = $langs->trans('Shippable').'<br>'.$text_info;
					} else {
						$text_icon = img_picto('', 'error');
						$text_info = $langs->trans('NonShippable').'<br>'.$text_info;
					}
				}

				print '<td>';
				if ($nbprod)
				{
					print $form->textwithtooltip('',$text_info,2,1,$text_icon,'',2);
				}
				if ($warning) {     // Always false in default mode
					print $form->textwithtooltip('', $langs->trans('NotEnoughForAllOrders').'<br>'.$text_warning, 2, 1, img_picto('', 'error'),'',2);
				}
				print '</td>';
			}

			// Warning late icon and note
			print '<td class="nobordernopadding nowrap">';
			if ($generic_commande->hasDelay()) {
				print img_picto($langs->trans("Late").' : '.$generic_commande->showDelay(), "warning");
			}
			if (!empty($obj->note_private) || !empty($obj->note_public))
			{
				print ' <span class="note">';
				print '<a href="'.DOL_URL_ROOT.'/commande/note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
				print '</span>';
			}
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);
			print '</td>';
			print '</tr></table>';

			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Ref customer
		if (! empty($arrayfields['c.ref_client']['checked']))
		{
			print '<td>'.$obj->ref_client.'</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Project
		if (! empty($arrayfields['p.project_ref']['checked']))
		{
			$projectstatic->id=$obj->project_id;
			$projectstatic->ref=$obj->project_ref;
			print '<td>';
			if ($obj->project_id > 0) print $projectstatic->getNomUrl(1);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Third party
		if (! empty($arrayfields['s.nom']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $companystatic->getNomUrl(1,'customer');

			// If module invoices enabled and user with invoice creation permissions
			if (! empty($conf->facture->enabled) && ! empty($conf->global->ORDER_BILLING_ALL_CUSTOMER))
			{
				if ($user->rights->facture->creer)
				{
					if (($obj->fk_statut > 0 && $obj->fk_statut < 3) || ($obj->fk_statut == 3 && $obj->billed == 0))
					{
						print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$companystatic->id.'">';
						print img_picto($langs->trans("CreateInvoiceForThisCustomer").' : '.$companystatic->name, 'object_bill', 'hideonsmartphone').'</a>';
					}
				}
			}
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

		// Order date
		if (! empty($arrayfields['c.date_commande']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->date_commande), 'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Plannned date of delivery
		if (! empty($arrayfields['c.date_delivery']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->date_delivery), 'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Amount HT
		if (! empty($arrayfields['c.total_ht']['checked']))
		{
			  print '<td align="right">'.price($obj->total_ht)."</td>\n";
			  if (! $i) $totalarray['nbfield']++;
			  if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
			  $totalarray['totalht'] += $obj->total_ht;
		}
		// Amount VAT
		if (! empty($arrayfields['c.total_vat']['checked']))
		{
			print '<td align="right">'.price($obj->total_tva)."</td>\n";
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
			$totalarray['totalvat'] += $obj->total_tva;
		}
		// Amount TTC
		if (! empty($arrayfields['c.total_ttc']['checked']))
		{
			print '<td align="right">'.price($obj->total_ttc)."</td>\n";
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
			$totalarray['totalttc'] += $obj->total_ttc;
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (! empty($arrayfields['c.datec']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date modification
		if (! empty($arrayfields['c.tms']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Status
		if (! empty($arrayfields['c.fk_statut']['checked']))
		{
			print '<td align="right" class="nowrap">'.$generic_commande->LibStatut($obj->fk_statut, $obj->billed, 5, 1).'</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Billed
		if (! empty($arrayfields['c.facture']['checked']))
		{
			print '<td align="center">'.yn($obj->billed).'</td>';
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

		$total+=$obj->total_ht;
		$subtotal+=$obj->total_ht;
		$i++;
	}

	// Show total line
	if (isset($totalarray['totalhtfield'])
 	   || isset($totalarray['totalvatfield'])
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
			elseif ($totalarray['totalhtfield'] == $i) print '<td align="right">'.price($totalarray['totalht']).'</td>';
			elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
			elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
			else print '<td></td>';
		}
		print '</tr>';
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>';

	print '</form>'."\n";

	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->commande->lire;
	$delallowed=$user->rights->commande->creer;

	print $formfile->showdocuments('massfilesarea_orders','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'','','',null,$hidegeneratedfilelistifempty);
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();

<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016      Ferran Marcet        <fmarcet@2byte.es>
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
 *   \file       htdocs/fourn/commande/list.php
 *   \ingroup    fournisseur
 *   \brief      List of suppliers orders
 */


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load("orders");
$langs->load("sendings");
$langs->load('deliveries');
$langs->load('companies');
$langs->load('compta');
$langs->load('bills');
$langs->load('projects');

$orderyear=GETPOST("orderyear","int");
$ordermonth=GETPOST("ordermonth","int");
$orderday=GETPOST("orderday","int");
$deliveryyear=GETPOST("deliveryyear","int");
$deliverymonth=GETPOST("deliverymonth","int");
$deliveryday=GETPOST("deliveryday","int");
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('search_ref');
$search_refsupp=GETPOST('search_refsupp');
$search_company=GETPOST('search_company','alpha');
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_user=GETPOST('search_user','int');
$search_request_author=GETPOST('search_request_author','alpha');
$search_ht=GETPOST('search_ht');
$search_ttc=GETPOST('search_ttc');
$search_status=(GETPOST('search_status','alpha')!=''?GETPOST('search_status','alpha'):GETPOST('statut','alpha'));	// alpha and not intbecause it can be '6,7'
$optioncss = GETPOST('optioncss','alpha');
$sall=GETPOST('search_all');
$socid = GETPOST('socid','int');
$search_sale=GETPOST('search_sale','int');
$search_total_ht=GETPOST('search_total_ht','alpha');
$search_total_vat=GETPOST('search_total_vat','alpha');
$search_total_ttc=GETPOST('search_total_ttc','alpha');
$optioncss = GETPOST('optioncss','alpha');
$billed = GETPOST('billed','int');
$search_project_ref=GETPOST('search_project_ref','alpha');

$page  = GETPOST('page','int');
$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');

$status=GETPOST('statut','alpha');
$billed=GETPOST('billed','int');
$viewstatut=GETPOST('viewstatut');

// Security check
$orderid = GETPOST('orderid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $orderid, '', 'commande');

$diroutputmassaction=$conf->commande->dir_output . '/temp/massgeneration/'.$user->id;

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='cf.ref';
if (! $sortorder) $sortorder='DESC';

if ($search_status == '') $search_status=-1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='supplierorderlist';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('orderlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('commande_fournisseur');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'cf.ref'=>'Ref',
    'cf.ref_supplier'=>'RefSupplierOrder',
    'pd.description'=>'Description',
    's.nom'=>"ThirdParty",
    'cf.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["cf.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
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
    'cf.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'cf.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'cf.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
    'cf.billed'=>array('label'=>$langs->trans("Billed"), 'checked'=>1, 'position'=>1000, 'enabled'=>1)
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}



/*
 * Actions
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction')) { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $ordermonth='';
    $orderyear='';
    $orderday='';
    $search_categ='';
    $search_user='';
    $search_sale='';
    $search_product_category='';
    $search_ref='';
    $search_refsupp='';
    $search_company='';
    $search_town='';
    $search_zip="";
    $search_state="";
    $search_type='';
    $search_country='';
    $search_type_thirdparty='';
    $search_request_author='';
    $search_total_ht='';
    $search_total_vat='';
    $search_total_ttc='';
    $search_status=-1;
    $orderyear='';
    $ordermonth='';
    $orderday='';
    $deliveryday='';
    $deliverymonth='';
    $deliveryyear='';
    $billed='';
	$search_project_ref='';
    $search_array_options=array();

}

if (empty($reshook))
{
    // Mass actions. Controls on number of lines checked
    $maxformassaction=1000;
    if (! empty($massaction) && count($toselect) < 1)
    {
        $error++;
        setEventMessages($langs->trans("NoLineChecked"), null, "warnings");
    }
    if (! $error && count($toselect) > $maxformassaction)
    {
        setEventMessages($langs->trans('TooManyRecordForMassAction',$maxformassaction), null, 'errors');
        $error++;
    }

}



/*
 *	View
 */

$now=dol_now();

$form=new Form($db);
$thirdpartytmp = new Fournisseur($db);
$commandestatic=new CommandeFournisseur($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);
$formother = new FormOther($db);
$formcompany=new FormCompany($db);

$title = $langs->trans("SuppliersOrders");
if ($socid > 0)
{
	$fourn = new Fournisseur($db);
	$fourn->fetch($socid);
	$title .= ' - '.$fourn->name;
}
if ($status)
{
    if ($status == '1,2,3') $title.=' - '.$langs->trans("StatusOrderToProcessShort");
    if ($status == '6,7') $title.=' - '.$langs->trans("StatusOrderCanceled");
    else $title.=' - '.$langs->trans($commandestatic->statuts[$status]);
}
if ($billed > 0) $title.=' - '.$langs->trans("Billed");

//$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
$help_url='';
llxHeader('',$title,$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= " cf.rowid, cf.ref, cf.ref_supplier, cf.fk_statut, cf.billed, cf.total_ht, cf.tva as total_tva, cf.total_ttc, cf.fk_user_author, cf.date_commande as date_commande, cf.date_livraison as date_delivery,";
$sql.= ' cf.date_creation as date_creation, cf.tms as date_update,';
$sql.= " p.rowid as project_id, p.ref as project_ref,";
$sql.= " u.firstname, u.lastname, u.photo, u.login";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur_extrafields as ef on (cf.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet as pd ON cf.rowid=pd.fk_commande';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON cf.fk_user_author = u.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = cf.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->rights->societe->client->voir && ! $socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE cf.fk_soc = s.rowid';
$sql.= ' AND cf.entity IN ('.getEntity('supplier_order', 1).')';
if ($socid > 0) $sql.= " AND s.rowid = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_ref) $sql .= natural_search('cf.ref', $search_ref);
if ($search_refsupp) $sql.= natural_search("cf.ref_supplier", $search_refsupp);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_request_author) $sql.=natural_search(array('u.lastname','u.firstname','u.login'), $search_request_author) ;
if ($billed != '' && $billed >= 0) $sql .= " AND cf.billed = ".$billed;

//Required triple check because statut=0 means draft filter
if (GETPOST('statut', 'alpha') !== '')
{
	$sql .= " AND cf.fk_statut IN (".GETPOST('statut', 'alpha').")";
}
if ($search_status != '' && $search_status >= 0)
{
	if (strstr($search_status, ',')) $sql.=" AND cf.fk_statut IN (".$db->escape($search_status).")";
	else $sql.=" AND cf.fk_statut = ".$search_status;
}
if ($ordermonth > 0)
{
    if ($orderyear > 0 && empty($orderday))
        $sql.= " AND cf.date_commande BETWEEN '".$db->idate(dol_get_first_day($orderyear,$ordermonth,false))."' AND '".$db->idate(dol_get_last_day($orderyear,$ordermonth,false))."'";
    else if ($orderyear > 0 && ! empty($orderday))
        $sql.= " AND cf.date_commande BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $ordermonth, $orderday, $orderyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $ordermonth, $orderday, $orderyear))."'";
    else
        $sql.= " AND date_format(cf.date_commande, '%m') = '".$ordermonth."'";
}
else if ($orderyear > 0)
{
    $sql.= " AND cf.date_commande BETWEEN '".$db->idate(dol_get_first_day($orderyear,1,false))."' AND '".$db->idate(dol_get_last_day($orderyear,12,false))."'";
}
if ($deliverymonth > 0)
{
    if ($deliveryyear > 0 && empty($deliveryday))
        $sql.= " AND cf.date_livraison BETWEEN '".$db->idate(dol_get_first_day($deliveryyear,$deliverymonth,false))."' AND '".$db->idate(dol_get_last_day($deliveryyear,$deliverymonth,false))."'";
        else if ($deliveryyear > 0 && ! empty($deliveryday))
            $sql.= " AND cf.date_livraison BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $deliverymonth, $deliveryday, $deliveryyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $deliverymonth, $deliveryday, $deliveryyear))."'";
            else
                $sql.= " AND date_format(cf.date_livraison, '%m') = '".$deliverymonth."'";
}
else if ($deliveryyear > 0)
{
    $sql.= " AND cf.date_livraison BETWEEN '".$db->idate(dol_get_first_day($deliveryyear,1,false))."' AND '".$db->idate(dol_get_last_day($deliveryyear,12,false))."'";
}
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0) $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='supplier_order' AND tc.source='internal' AND ec.element_id = cf.rowid AND ec.fk_socpeople = ".$search_user;
if ($search_total_ht != '') $sql.= natural_search('cf.total_ht', $search_total_ht, 1);
if ($search_total_vat != '') $sql.= natural_search('cf.tva', $search_total_vat, 1);
if ($search_total_ttc != '') $sql.= natural_search('cf.total_ttc', $search_total_ttc, 1);
if ($search_project_ref != '') $sql.= natural_search("p.ref",$search_project_ref);

// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit)))
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);
$resql = $db->query($sql);
if ($resql)
{
	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfSupplierOrders') . ' - '.$soc->name;
	}
	else
	{
		$title = $langs->trans('ListOfSupplierOrders');
	}

	$num = $db->num_rows($resql);

	$param='';
	if ($socid > 0)             $param.='&socid='.$socid;
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)					$param.="&search_all=".$sall;
	if ($orderday)      		$param.='&orderday='.$orderday;
	if ($ordermonth)      		$param.='&ordermonth='.$ordermonth;
	if ($orderyear)       		$param.='&orderyear='.$orderyear;
	if ($deliveryday)   		$param.='&deliveryday='.$deliveryday;
	if ($deliverymonth)   		$param.='&deliverymonth='.$deliverymonth;
	if ($deliveryyear)    		$param.='&deliveryyear='.$deliveryyear;
	if ($search_ref)      		$param.='&search_ref='.$search_ref;
	if ($search_company)  		$param.='&search_company='.$search_company;
	if ($search_user > 0) 		$param.='&search_user='.$search_user;
	if ($search_request_author) $param.='&search_request_author='.$search_request_author;
	if ($search_sale > 0) 		$param.='&search_sale='.$search_sale;
	if ($search_total_ht != '') $param.='&search_total_ht='.$search_total_ht;
	if ($search_total_ttc != '') $param.="&search_total_ttc=".$search_total_ttc;
	if ($search_refsupp) 		$param.="&search_refsupp=".$search_refsupp;
	if ($search_status >= 0)  	$param.="&search_status=".$search_status;
	if ($billed != '')          $param.="&billed=".$billed;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
	// Add $param from extra fields
	foreach ($search_array_options as $key => $val)
	{
	    $crit=$val;
	    $tmpkey=preg_replace('/search_options_/','',$key);
	    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
	}

	//$massactionbutton=$form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail"), 'builddoc'=>$langs->trans("PDFMerge")));

	// Lignes des champs de filtre
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_commercial.png', 0, '', '', $limit);

	if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }

    $moreforfilter='';

    // If the user can view prospects other than his'
    if ($user->rights->societe->client->voir || $socid)
    {
        $langs->load("commercial");
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
        $moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth300');
        $moreforfilter.='</div>';
    }
    // If the user can view other users
    if ($user->rights->user->user->lire)
    {
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
        $moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
        $moreforfilter.='</div>';
    }
    // If the user can view prospects other than his'
    if ($conf->categorie->enabled && ($user->rights->produit->lire || $user->rights->service->lire))
    {
        include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
        $moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, '', 1);
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

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['cf.ref']['checked']))            print_liste_field_titre($arrayfields['cf.ref']['label'],$_SERVER["PHP_SELF"],"cf.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['cf.ref_supplier']['checked']))   print_liste_field_titre($arrayfields['cf.ref_supplier']['label'],$_SERVER["PHP_SELF"],"cf.ref_supplier","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['p.project_ref']['checked'])) 	   print_liste_field_titre($arrayfields['p.project_ref']['label'],$_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['u.login']['checked'])) 	       print_liste_field_titre($arrayfields['u.login']['label'],$_SERVER["PHP_SELF"],"u.login","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))             print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],"s.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))            print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))             print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))         print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked']))  print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))       print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['cf.fk_author']['checked']))      print_liste_field_titre($arrayfields['cf.fk_author']['label'],$_SERVER["PHP_SELF"],"cf.fk_author","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['cf.date_commande']['checked']))  print_liste_field_titre($arrayfields['cf.date_commande']['label'],$_SERVER["PHP_SELF"],"cf.date_commande","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['cf.date_delivery']['checked']))  print_liste_field_titre($arrayfields['cf.date_delivery']['label'],$_SERVER["PHP_SELF"],'cf.date_livraison','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['cf.total_ht']['checked']))       print_liste_field_titre($arrayfields['cf.total_ht']['label'],$_SERVER["PHP_SELF"],"cf.total_ht","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['cf.total_vat']['checked']))      print_liste_field_titre($arrayfields['cf.total_vat']['label'],$_SERVER["PHP_SELF"],"cf.tva","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['cf.total_ttc']['checked']))      print_liste_field_titre($arrayfields['cf.total_ttc']['label'],$_SERVER["PHP_SELF"],"cf.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
	   foreach($extrafields->attribute_label as $key => $val)
	   {
           if (! empty($arrayfields["ef.".$key]['checked']))
           {
				$align=$extrafields->getAlignFlag($key);
				print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
           }
	   }
	}
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (! empty($arrayfields['cf.datec']['checked']))     print_liste_field_titre($arrayfields['cf.datec']['label'],$_SERVER["PHP_SELF"],"cf.date_creation","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
    if (! empty($arrayfields['cf.tms']['checked']))       print_liste_field_titre($arrayfields['cf.tms']['label'],$_SERVER["PHP_SELF"],"cf.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
    if (! empty($arrayfields['cf.fk_statut']['checked'])) print_liste_field_titre($arrayfields['cf.fk_statut']['label'],$_SERVER["PHP_SELF"],"cf.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['cf.billed']['checked']))    print_liste_field_titre($arrayfields['cf.billed']['label'],$_SERVER["PHP_SELF"],'cf.billed','',$param,'align="center"',$sortfield,$sortorder,'');
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	// Ref
	if (! empty($arrayfields['cf.ref']['checked']))
	{
		print '<td class="liste_titre"><input size="8" type="text" class="flat" name="search_ref" value="'.$search_ref.'"></td>';
	}
	// Ref customer
	if (! empty($arrayfields['cf.ref_supplier']['checked']))
	{
		print '<td class="liste_titre"><input type="text" class="flat" size="8" name="search_refsupp" value="'.$search_refsupp.'"></td>';
	}
	// Project ref
	if (! empty($arrayfields['p.project_ref']['checked']))
	{
		print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_project_ref" value="'.$search_project_ref.'"></td>';
	}
	// Request author
	if (! empty($arrayfields['u.login']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="6" name="search_request_author" value="'.$search_request_author.'">';
		print '</td>';
	}
	// Thirpdarty
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre"><input type="text" size="6" class="flat" name="search_company" value="'.$search_company.'"></td>';
	}
	// Town
	if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';
	// Zip
	if (! empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_zip" value="'.$search_zip.'"></td>';
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
	if (! empty($arrayfields['cf.date_commande']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="orderday" value="'.$orderday.'">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="ordermonth" value="'.$ordermonth.'">';
		$formother->select_year($orderyear?$orderyear:-1,'orderyear',1, 20, 5);
		print '</td>';
	}
	// Date delivery
	if (! empty($arrayfields['cf.date_delivery']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="deliveryday" value="'.$deliveryday.'">';
        print '<input class="flat" type="text" size="1" maxlength="2" name="deliverymonth" value="'.$deliverymonth.'">';
        $formother->select_year($deliveryyear?$deliveryyear:-1,'deliveryyear',1, 20, 5);
    	print '</td>';
	}
	if (! empty($arrayfields['cf.total_ht']['checked']))
	{
	    // Amount
	    print '<td class="liste_titre" align="right">';
	    print '<input class="flat" type="text" size="5" name="search_total_ht" value="'.$search_total_ht.'">';
	    print '</td>';
	}
	if (! empty($arrayfields['cf.total_vat']['checked']))
	{
	    // Amount
	    print '<td class="liste_titre" align="right">';
	    print '<input class="flat" type="text" size="5" name="search_total_vat" value="'.$search_total_vat.'">';
	    print '</td>';
	}
	if (! empty($arrayfields['cf.total_ttc']['checked']))
	{
	    // Amount
	    print '<td class="liste_titre" align="right">';
	    print '<input class="flat" type="text" size="5" name="search_total_ttc" value="'.$search_total_ttc.'">';
	    print '</td>';
	}
	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
	    foreach($extrafields->attribute_label as $key => $val)
	    {
	        if (! empty($arrayfields["ef.".$key]['checked']))
	        {
	            $align=$extrafields->getAlignFlag($key);
	            $typeofextrafield=$extrafields->attribute_type[$key];
	            print '<td class="liste_titre'.($align?' '.$align:'').'">';
	            if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
	            {
	                $crit=$val;
	                $tmpkey=preg_replace('/search_options_/','',$key);
	                $searchclass='';
	                if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
	                if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
	                print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
	            }
	            print '</td>';
	        }
	    }
	}
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['cf.datec']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['cf.tms']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Status
	if (! empty($arrayfields['cf.fk_statut']['checked']))
	{
		print '<td class="liste_titre" align="right">';
		$formorder->selectSupplierOrderStatus((strstr($search_status, ',')?-1:$search_status),1,'search_status');
		print '</td>';
	}
	// Status billed
	if (! empty($arrayfields['cf.billed']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		print $form->selectyesno('billed', $billed, 1, 0, 1);
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpitco=$form->showFilterAndCheckAddButtons(0);
	print $searchpitco;
	print '</td>';

	print "</tr>\n";

	$total=0;
	$subtotal=0;
    $productstat_cache=array();

    $userstatic = new User($db);
	$objectstatic=new CommandeFournisseur($db);
	$projectstatic=new Project($db);

	$i=0;
	$var=true;
	$totalarray=array();
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

        $objectstatic->id=$obj->rowid;
        $objectstatic->ref=$obj->ref;
        $objectstatic->ref_supplier = $obj->ref_supplier;
        $objectstatic->total_ht = $obj->total_ht;
        $objectstatic->total_tva = $obj->total_tva;
        $objectstatic->total_ttc = $obj->total_ttc;
        $objectstatic->date_delivery = $db->jdate($obj->date_delivery);
        $objectstatic->statut = $obj->fk_statut;

		print "<tr ".$bc[$var].">";

		// Ref
        if (! empty($arrayfields['cf.ref']['checked']))
        {
            print '<td class="nowrap">';
            
            print '<table class="nobordernopadding"><tr class="nocellnopadd">';
            // Picto + Ref
            print '<td class="nobordernopadding nowrap">';
    	    print $objectstatic->getNomUrl(1);
    	    print '</td>';
    	    // Warning
    	    //print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
    	    //print '</td>';
    	    // Other picto tool
    	    print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->fournisseur->dir_output.'/commande' . '/' . dol_sanitizeFileName($obj->ref);
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			print '</td></tr></table>';
			
			print '</td>'."\n";
            if (! $i) $totalarray['nbfield']++;
        }
		// Ref Supplier
        if (! empty($arrayfields['cf.ref_supplier']['checked']))
        {
        	print '<td>'.$obj->ref_supplier.'</td>'."\n";
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
		// Author
		$userstatic->id = $obj->fk_user_author;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		$userstatic->login = $obj->login;
		$userstatic->photo = $obj->photo;
		if (! empty($arrayfields['u.login']['checked']))
		{
		    print "<td>";
		    if ($userstatic->id) print $userstatic->getNomUrl(1);
		    else print "&nbsp;";
		    print "</td>";
            if (! $i) $totalarray['nbfield']++;
		}
        // Thirdparty
        if (! empty($arrayfields['s.nom']['checked']))
        {
        	print '<td>';
			$thirdpartytmp->id = $obj->socid;
			$thirdpartytmp->name = $obj->name;
			print $thirdpartytmp->getNomUrl(1,'supplier');
			print '</td>'."\n";
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
		if (! empty($arrayfields['cf.date_commande']['checked']))
		{
    		print '<td align="center">';
    		if ($obj->date_commande) print dol_print_date($db->jdate($obj->date_commande), 'day');
    		else print '';
    		print '</td>';
    		if (! $i) $totalarray['nbfield']++;
		}
		// Plannned date of delivery
		if (! empty($arrayfields['cf.date_delivery']['checked']))
		{
    		print '<td align="center">';
    		print dol_print_date($db->jdate($obj->date_delivery), 'day');
    		if ($objectstatic->hasDelay() && ! empty($objectstatic->date_delivery)) {
    		    print ' '.img_picto($langs->trans("Late").' : '.$objectstatic->showDelay(), "warning");
    		}
    		print '</td>';
    		if (! $i) $totalarray['nbfield']++;
		}
        // Amount HT
        if (! empty($arrayfields['cf.total_ht']['checked']))
        {
		      print '<td align="right">'.price($obj->total_ht)."</td>\n";
		      if (! $i) $totalarray['nbfield']++;
		      if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
		      $totalarray['totalht'] += $obj->total_ht;
        }
        // Amount VAT
        if (! empty($arrayfields['cf.total_vat']['checked']))
        {
            print '<td align="right">'.price($obj->total_tva)."</td>\n";
            if (! $i) $totalarray['nbfield']++;
		    if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
		    $totalarray['totalvat'] += $obj->total_tva;
        }
        // Amount TTC
        if (! empty($arrayfields['cf.total_ttc']['checked']))
        {
            print '<td align="right">'.price($obj->total_ttc)."</td>\n";
            if (! $i) $totalarray['nbfield']++;
		    if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
		    $totalarray['totalttc'] += $obj->total_ttc;
        }

        // Extra fields
        if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
        {
            foreach($extrafields->attribute_label as $key => $val)
            {
                if (! empty($arrayfields["ef.".$key]['checked']))
                {
                    print '<td';
                    $align=$extrafields->getAlignFlag($key);
                    if ($align) print ' align="'.$align.'"';
                    print '>';
                    $tmpkey='options_'.$key;
                    print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                    print '</td>';
                    if (! $i) $totalarray['nbfield']++;
                }
            }
        }
        // Fields from hook
        $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
        $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        // Date creation
        if (! empty($arrayfields['cf.datec']['checked']))
        {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Date modification
        if (! empty($arrayfields['cf.tms']['checked']))
        {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->date_update), 'dayhour');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Status
        if (! empty($arrayfields['cf.fk_statut']['checked']))
        {
            print '<td align="right" class="nowrap">'.$objectstatic->LibStatut($obj->fk_statut, 5, $obj->billed, 1).'</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Billed
        if (! empty($arrayfields['cf.billed']['checked']))
        {
            print '<td align="center">'.yn($obj->billed).'</td>';
            if (! $i) $totalarray['nbfield']++;
        }

        // Action column
        print '<td></td>';
        if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}
	print "</table>\n";
	print '</div>';
	print "</form>\n";

	if (! empty($conf->facture->enable)) print '<br>'.img_help(1,'').' '.$langs->trans("ToBillSeveralOrderSelectCustomer", $langs->transnoentitiesnoconv("CreateInvoiceForThisCustomer")).'<br>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();

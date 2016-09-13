<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016      Ferran Marcet	     <fmarcet@2byte.es>
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
if (! empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');

$socid=GETPOST('socid','int');

$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refcustomer=GETPOST('search_refcustomer','alpha');
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
$viewstatut=GETPOST('viewstatut');
$optioncss = GETPOST('optioncss','alpha');
$object_statut=GETPOST('propal_statut');

$sall=GETPOST("sall");
$mesg=(GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));
$day=GETPOST("day","int");
$year=GETPOST("year","int");
$month=GETPOST("month","int");

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='p.ref';
if (! $sortorder) $sortorder='DESC';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='proposallist';

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

$diroutputmassaction=$conf->propal->dir_output . '/temp/massgeneration/'.$user->id;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('propallist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('propal');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

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
    's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
    's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
    's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
    'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
    'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
    'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
    'p.date'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
    'p.fin_validite'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
    'p.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
    'p.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
    'p.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
    'u.login'=>array('label'=>$langs->trans("Author"), 'checked'=>1, 'position'=>10),
    'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'p.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
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

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_categ='';
    $search_user='';
    $search_sale='';
    $search_ref='';
    $search_refcustomer='';
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
	$year='';
    $month='';
    $day='';
	$viewstatut='';
	$object_statut='';
    $search_array_options=array();
}
if ($object_statut != '') $viewstatut=$object_statut;

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
 * View
 */

llxHeader('',$langs->trans('Proposal'),'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$companystatic=new Societe($db);
$formcompany=new FormCompany($db);

$now=dol_now();

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' s.rowid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client, ';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= ' p.rowid as propalid, p.note_private, p.total_ht, p.tva as total_vat, p.total as total_ttc, p.localtax1, p.localtax2, p.ref, p.ref_client, p.fk_statut, p.fk_user_author, p.datep as dp, p.fin_validite as dfv,';
$sql.= ' p.datec as date_creation, p.tms as date_update,';
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " sc.fk_soc, sc.fk_user,";
$sql.= ' u.login';
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
$sql.= ', '.MAIN_DB_PREFIX.'propal as p';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields as ef on (p.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet as pd ON p.rowid=pd.fk_propal';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid';
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as c";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE p.fk_soc = s.rowid';
$sql.= ' AND p.entity IN ('.getEntity('propal', 1).')';
if (! $user->rights->societe->client->voir && ! $socid) //restriction
{
	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
}
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_ref)   $sql .= natural_search('p.ref', $search_ref);
if ($search_refcustomer) $sql .= natural_search('p.ref_client', $search_refcustomer);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_login) $sql.= " AND u.login LIKE '%".$db->escape(trim($search_login))."%'";
if ($search_montant_ht != '')  $sql.= natural_search("p.total_ht", $search_montant_ht, 1);
if ($search_montant_vat != '') $sql.= natural_search("p.tva", $search_montant_vat, 1);
if ($search_montant_ttc != '') $sql.= natural_search("p.total", $search_montant_ttc, 1);
if ($sall) {
    $sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if ($viewstatut <> '')
{
	$sql.= ' AND p.fk_statut IN ('.$viewstatut.')';
}
if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND p.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(p.datep, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND c.fk_c_type_contact = tc.rowid AND tc.element='propal' AND tc.source='internal' AND c.element_id = p.rowid AND c.fk_socpeople = ".$search_user;
}
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
$sql.=', p.ref DESC';

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result)
{
	$objectstatic=new Propal($db);
	$userstatic=new User($db);
	$num = $db->num_rows($result);

 	if ($socid)
	{
		$soc = new Societe($db);
		 $soc->fetch($socid);
	}

	$param='&socid='.$socid.'&viewstatut='.$viewstatut;
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)				 $param.='&sall='.$sall;
	if ($month)              $param.='&month='.$month;
	if ($year)               $param.='&year='.$year;
    if ($search_ref)         $param.='&search_ref=' .$search_ref;
    if ($search_refcustomer) $param.='&search_refcustomer=' .$search_refcustomer;
    if ($search_societe)     $param.='&search_societe=' .$search_societe;
	if ($search_user > 0)    $param.='&search_user='.$search_user;
	if ($search_sale > 0)    $param.='&search_sale='.$search_sale;
	if ($search_montant_ht)  $param.='&search_montant_ht='.$search_montant_ht;
	if ($search_login)  	 $param.='&search_login='.$search_login;
	if ($search_town)		 $param.='&search_town='.$search_town;
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

	print_barre_liste($langs->trans('ListOfProposals').' '.($socid?'- '.$soc->name:''), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_commercial.png', 0, '', '', $limit);
	
	if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        //sort($fieldstosearchall);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
	
	$i = 0;

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
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
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
	if (! empty($moreforfilter))
	{
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
	    print $moreforfilter;
	    print '</div>';
	}

    $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
	
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['p.ref']['checked']))            print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['p.ref_client']['checked']))     print_liste_field_titre($arrayfields['p.ref_client']['label'],$_SERVER["PHP_SELF"],'p.ref_client','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.date']['checked']))           print_liste_field_titre($arrayfields['p.date']['label'],$_SERVER["PHP_SELF"],'p.datep','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.fin_validite']['checked']))   print_liste_field_titre($arrayfields['p.fin_validite']['label'],$_SERVER["PHP_SELF"],'dfv','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.total_ht']['checked']))       print_liste_field_titre($arrayfields['p.total_ht']['label'],$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.total_vat']['checked']))      print_liste_field_titre($arrayfields['p.total_vat']['label'],$_SERVER["PHP_SELF"],'p.tva','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.total_ttc']['checked']))      print_liste_field_titre($arrayfields['p.total_ttc']['label'],$_SERVER["PHP_SELF"],'p.total','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['u.login']['checked']))       	  print_liste_field_titre($arrayfields['u.login']['label'],$_SERVER["PHP_SELF"],'u.login','',$param,'align="center"',$sortfield,$sortorder);
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
	if (! empty($arrayfields['p.datec']['checked']))     print_liste_field_titre($arrayfields['p.datec']['label'],$_SERVER["PHP_SELF"],"p.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.tms']['checked']))       print_liste_field_titre($arrayfields['p.tms']['label'],$_SERVER["PHP_SELF"],"p.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.fk_statut']['checked'])) print_liste_field_titre($arrayfields['p.fk_statut']['label'],$_SERVER["PHP_SELF"],"p.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['p.ref']['checked']))            
	{
	    print '<td class="liste_titre">';
    	print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
	   print '</td>';
	}
	if (! empty($arrayfields['p.ref_client']['checked']))
	{
    	print '<td class="liste_titre">';
	   print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
	   print '</td>';
	}
	if (! empty($arrayfields['s.nom']['checked']))
	{
	    print '<td class="liste_titre" align="left">';
    	print '<input class="flat" type="text" size="12" name="search_societe" value="'.$search_societe.'">';
	   print '</td>';
	}
	if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';
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
	// Date
	if (! empty($arrayfields['p.date']['checked'])) 
	{
	    print '<td class="liste_titre" colspan="1" align="center">';
    	//print $langs->trans('Month').': ';
    	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
    	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
    	//print '&nbsp;'.$langs->trans('Year').': ';
    	$syear = $year;
    	$formother->select_year($syear,'year',1, 20, 5);
    	print '</td>';
	}
	// Date end
	if (! empty($arrayfields['p.fin_validite']['checked'])) 
	{
	   print '<td class="liste_titre" colspan="1">&nbsp;</td>';
	}
	if (! empty($arrayfields['p.total_ht']['checked']))
	{
    	// Amount
    	print '<td class="liste_titre" align="right">';
    	print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.$search_montant_ht.'">';
    	print '</td>';
	}
	if (! empty($arrayfields['p.total_vat']['checked']))
	{
    	// Amount
    	print '<td class="liste_titre" align="right">';
    	print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.$search_montant_vat.'">';
    	print '</td>';
	}
	if (! empty($arrayfields['p.total_ttc']['checked']))
	{
    	// Amount
    	print '<td class="liste_titre" align="right">';
    	print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.$search_montant_ttc.'">';
    	print '</td>';
	}
	if (! empty($arrayfields['u.login']['checked']))
	{
    	// Author
    	print '<td class="liste_titre" align="center">';
    	print '<input class="flat" size="4" type="text" name="search_login" value="'.$search_login.'">';
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
	    $formpropal->selectProposalStatus($viewstatut,1);
	    print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpitco=$form->showFilterAndCheckAddButtons(0);
	print $searchpitco;
	print '</td>';
	
	print "</tr>\n";

	$now = dol_now();
	$i=0;
	$var=true;
	$totalarray=array();
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($result);
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		
		if (! empty($arrayfields['p.ref']['checked']))
		{
    		print '<td class="nowrap">';
    
    		$objectstatic->id=$obj->propalid;
    		$objectstatic->ref=$obj->ref;
    
    		print '<table class="nobordernopadding"><tr class="nocellnopadd">';
    		print '<td class="nobordernopadding nowrap">';
    		print $objectstatic->getNomUrl(1);
    		print '</td>';
    
    		print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
    		if ($obj->fk_statut == 1 && $db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
    		if (! empty($obj->note_private))
    		{
    			print ' <span class="note">';
    			print '<a href="'.DOL_URL_ROOT.'/comm/propal/note.php?id='.$obj->propalid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
    			print '</span>';
    		}
    		print '</td>';
    
    		// Ref
    		print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
    		$filename=dol_sanitizeFileName($obj->ref);
    		$filedir=$conf->propal->dir_output . '/' . dol_sanitizeFileName($obj->ref);
    		$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->propalid;
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
		
		$url = DOL_URL_ROOT.'/comm/card.php?socid='.$obj->rowid;

		$companystatic->id=$obj->rowid;
		$companystatic->name=$obj->name;
		$companystatic->client=$obj->client;
		$companystatic->code_client=$obj->code_client;
		
		// Thirdparty
		if (! empty($arrayfields['s.nom']['checked']))
		{
    		print '<td>';
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
        if (! empty($arrayfields['p.datec']['checked']))
        {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Date modification
        if (! empty($arrayfields['p.tms']['checked']))
        {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->date_update), 'dayhour');
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
        print '<td></td>';
        if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";

		$i++;
	}

	// Show total line
	if (isset($totalarray['totalhtfield']))
	{
		print '<tr class="liste_total">';
		$i=0;
		while ($i < $totalarray['nbfield'])
		{
		   $i++;
		   if ($i == 1)
	       {
        		if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
        		else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
	       }
		   elseif ($totalarray['totalhtfield'] == $i) print '<td align="right">'.price($totalarray['totalht']).'</td>';
		   elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
		   elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
		   else print '<td></td>';
		}
		print '</tr>';
		
	}

	$db->free($result);
	
	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
				
	print '</table>';

	print '</form>';
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();

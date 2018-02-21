<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015	   Claudio Aschieri		<c.aschieri@19.coop>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *       \file       htdocs/contrat/list.php
 *       \ingroup    contrat
 *       \brief      Page liste des contrats
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->load("contracts");
$langs->load("products");
$langs->load("companies");
$langs->load("compta");

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$search_name=GETPOST('search_name');
$search_email=GETPOST('search_email');
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_contract=GETPOST('search_contract');
$search_ref_customer=GETPOST('search_ref_customer','alpha');
$search_ref_supplier=GETPOST('search_ref_supplier','alpha');
$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_status=GETPOST('search_status');
$socid=GETPOST('socid');
$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_product_category=GETPOST('search_product_category','int');
$search_dfmonth=GETPOST('search_dfmonth','int');
$search_dfyear=GETPOST('search_dfyear','int');
$search_op2df=GETPOST('search_op2df','alpha');
$day=GETPOST("day","int");
$year=GETPOST("year","int");
$month=GETPOST("month","int");

$optioncss = GETPOST('optioncss','alpha');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='c.ref';
if (! $sortorder) $sortorder='DESC';

// Security check
$id=GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $id);

$diroutputmassaction=$conf->contrat->dir_output . '/temp/massgeneration/'.$user->id;

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);

if ($search_status == '') $search_status=1;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$contextpage='contractlist';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('contrat');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');
// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'c.ref'=>'Ref',
	'c.ref_customer'=>'RefCustomer',
	'c.ref_supplier'=>'RefSupplier',
	's.nom'=>"ThirdParty",
	'cd.description'=>'Description',
	'c.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["c.note_private"]="NotePrivate";

$arrayfields=array(
	'c.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'c.ref_customer'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
	'c.ref_supplier'=>array('label'=>$langs->trans("RefSupplier"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.email'=>array('label'=>$langs->trans("ThirdPartyEmail"), 'checked'=>0),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>0),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>0),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'sale_representative'=>array('label'=>$langs->trans("SaleRepresentativesOfThirdParty"), 'checked'=>1),
	'c.date_contrat'=>array('label'=>$langs->trans("DateContract"), 'checked'=>1),
	'c.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'c.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'lower_planned_end_date'=>array('label'=>$langs->trans("LowerDateEndPlannedShort"), 'checked'=>1, 'position'=>900, 'help'=>$langs->trans("LowerDateEndPlannedShort")),
	'status'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
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
 * Action
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
{
	$day='';
	$month='';
	$year='';
	$search_dfmonth='';
	$search_dfyear='';
	$search_op2df='';
	$search_name="";
	$search_email="";
	$search_town='';
	$search_zip="";
	$search_state="";
	$search_type='';
	$search_country='';
	$search_contract="";
	$search_ref_customer="";
	$search_ref_supplier="";
	$search_user='';
	$search_sale='';
	$search_product_category='';
	$sall="";
	$search_status="";
	$toselect='';
	$search_array_options=array();
}

if (empty($reshook))
{
	$objectclass='Contrat';
	$objectlabel='Contracts';
	$permtoread = $user->rights->contrat->lire;
	$permtodelete = $user->rights->contrat->supprimer;
	$uploaddir = $conf->contrat->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$now=dol_now();
$form = new Form($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);
$socstatic = new Societe($db);
$contracttmp = new Contrat($db);

llxHeader('', $langs->trans("Contracts"));

$sql = 'SELECT';
$sql.= " c.rowid, c.ref, c.datec as date_creation, c.tms as date_update, c.date_contrat, c.statut, c.ref_customer, c.ref_supplier, c.note_private, c.note_public,";
$sql.= ' s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= " MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") as lower_planned_end_date,";
$sql.= ' SUM('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
$sql.= ' SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')",1,0).') as nb_running,';
$sql.= ' SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')",1,0).') as nb_expired,';
$sql.= ' SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now - $conf->contrat->services->expires->warning_delay)."')",1,0).') as nb_late,';
$sql.= ' SUM('.$db->ifsql("cd.statut=5",1,0).') as nb_closed';
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
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat_extrafields as ef on (c.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=cd.fk_product';
if ($search_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= " WHERE c.fk_soc = s.rowid ";
$sql.= ' AND c.entity IN ('.getEntity('contract').')';
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid) $sql.= " AND s.rowid = ".$db->escape($socid);
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($month > 0)
{
	if ($year > 0 && empty($day))
	$sql.= " AND c.date_contrat BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
	else if ($year > 0 && ! empty($day))
	$sql.= " AND c.date_contrat BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
	else
	$sql.= " AND date_format(c.date_contrat, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND c.date_contrat BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($search_name) $sql .= natural_search('s.nom', $search_name);
if ($search_email) $sql .= natural_search('s.email', $search_name);
if ($search_contract) $sql .= natural_search(array('c.rowid', 'c.ref'), $search_contract);
if (!empty($search_ref_customer)) $sql .= natural_search(array('c.ref_customer'), $search_ref_customer);
if (!empty($search_ref_supplier)) $sql .= natural_search(array('c.ref_supplier'), $search_ref_supplier);
if ($search_sale > 0)
{
	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
}
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($search_user > 0) $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='contrat' AND tc.source='internal' AND ec.element_id = c.rowid AND ec.fk_socpeople = ".$search_user;
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " GROUP BY c.rowid, c.ref, c.datec, c.tms, c.date_contrat, c.statut, c.ref_customer, c.ref_supplier, c.note_private, c.note_public,";
$sql.= ' s.rowid, s.nom, s.email, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql.= " typent.code,";
$sql.= " state.code_departement, state.nom";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListGroupBy',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
if ($search_dfyear > 0 && $search_op2df)
{
	if ($search_op2df == '<=') $sql.= " HAVING MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") <= '".$db->idate(dol_get_last_day($search_dfyear,$search_dfmonth,false))."'";
	elseif ($search_op2df == '>=') $sql.= " HAVING MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") >= '".$db->idate(dol_get_first_day($search_dfyear,$search_dfmonth,false))."'";
	else $sql.= " HAVING MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") <= '".$db->idate(dol_get_last_day($search_dfyear,$search_dfmonth,false))."' AND MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") >= '".$db->idate(dol_get_first_day($search_dfyear,$search_dfmonth,false))."'";
}
$sql.= $db->order($sortfield,$sortorder);
//print $sql;

$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
	$totalnboflines = $db->num_rows($result);
}

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit + 1, $offset);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		if (empty($search_name)) $search_name = $soc->name;
	}

	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall != '')                $param.='&sall='.urlencode($sall);
	if ($search_contract != '')     $param.='&search_contract='.urlencode($search_contract);
	if ($search_name != '')         $param.='&search_name='.urlencode($search_name);
	if ($search_email != '')        $param.='&search_email='.urlencode($search_email);
	if ($search_ref_customer != '') $param.='&search_ref_customer='.urlencode($search_ref_customer);
	if ($search_ref_supplier != '') $param.='&search_ref_supplier='.urlencode($search_ref_supplier);
	if ($search_op2df != '') 		$param.='&search_op2df='.urlencode($search_op2df);
	if ($search_dfyear != '')       $param.='&search_dfyear='.urlencode($search_dfyear);
	if ($search_dfmonth != '')      $param.='&search_dfmonth='.urlencode($search_dfmonth);
	if ($search_sale != '')         $param.='&search_sale=' .urlencode($search_sale);
	if ($search_user != '')			$param.='&search_user=' .urlencode($search_user);
	if ($search_product_category != '')	$param.='&search_product_category=' .urlencode($search_product_category);
	if ($show_files)                $param.='&show_files=' .urlencode($show_files);
	if ($optioncss != '')           $param.='&optioncss='.urlencode($optioncss);
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array(
		'presend'=>$langs->trans("SendByMail"),
		'builddoc'=>$langs->trans("PDFMerge"),
	);
	if ($user->rights->contrat->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
	if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("ListOfContracts"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $totalnboflines, 'title_commercial.png', 0, '', '', $limit);

	$topicmail="SendContractRef";
	$modelmail="contract";
	$objecttmp=new Contrat($db);
	$trackid='con'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

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
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user,0,1,'maxwidth200');
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
	// If the user can view categories of products
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

	if (! empty($moreforfilter))
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

	print '<tr class="liste_titre_filter">';
	if (! empty($arrayfields['c.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="3" name="search_contract" value="'.dol_escape_htmltag($search_contract).'">';
		print '</td>';
	}
	if (! empty($arrayfields['c.ref_customer']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="6" name="search_ref_customer" value="'.dol_escape_htmltag($search_ref_customer).'">';
		print '</td>';
	}
	if (! empty($arrayfields['c.ref_supplier']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="6" name="search_ref_supplier" value="'.dol_escape_htmltag($search_ref_supplier).'">';
		print '</td>';
	}
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="8" name="search_name" value="'.dol_escape_htmltag($search_name).'">';
		print '</td>';
	}
	if (! empty($arrayfields['s.email']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="6" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
		print '</td>';
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
	if (! empty($arrayfields['sale_representative']['checked']))
	{
		print '<td class="liste_titre"></td>';
	}
	if (! empty($arrayfields['c.date_contrat']['checked']))
	{
		// Date contract
		print '<td class="liste_titre center">';
		//print $langs->trans('Month').': ';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
		//print '&nbsp;'.$langs->trans('Year').': ';
		$syear = $year;
		$formother->select_year($syear,'year',1, 20, 5);
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
	// First end date
	if (! empty($arrayfields['lower_planned_end_date']['checked']))
	{
			print '<td class="liste_titre" align="center">';
			$arrayofoperators=array('0'=>'','='=>'=','<='=>'<=','>='=>'>=');
			print $form->selectarray('search_op2df',$arrayofoperators,$search_op2df,0);
			print '</br>';
			print $formother->select_month($search_dfmonth, 'search_dfmonth', 1);
			print ' ';
			$formother->select_year($search_dfyear, 'search_dfyear', 1, 20, 5);
			print '</td>';
	}
	// Status
	if (! empty($arrayfields['status']['checked']))
	{
		print '<td class="liste_titre" colspan="4" align="right"></td>';
	}
	print '<td class="liste_titre" align="middle">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['c.ref']['checked']))               print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER["PHP_SELF"], "c.ref","","$param",'',$sortfield,$sortorder);
	if (! empty($arrayfields['c.ref_customer']['checked']))      print_liste_field_titre($arrayfields['c.ref_customer']['label'], $_SERVER["PHP_SELF"], "c.ref_customer","","$param",'',$sortfield,$sortorder);
	if (! empty($arrayfields['c.ref_supplier']['checked']))      print_liste_field_titre($arrayfields['c.ref_supplier']['label'], $_SERVER["PHP_SELF"], "c.ref_supplier","","$param",'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))               print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom","","$param",'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.email']['checked']))             print_liste_field_titre($arrayfields['s.email']['label'], $_SERVER["PHP_SELF"], "s.email","","$param",'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))              print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))               print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))           print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked']))    print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))         print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['sale_representative']['checked'])) print_liste_field_titre($arrayfields['sale_representative']['label'], $_SERVER["PHP_SELF"], "","","$param",'',$sortfield,$sortorder);
	if (! empty($arrayfields['c.date_contrat']['checked']))      print_liste_field_titre($arrayfields['c.date_contrat']['label'], $_SERVER["PHP_SELF"], "c.date_contrat","","$param",'align="center"',$sortfield,$sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['c.datec']['checked']))     print_liste_field_titre($arrayfields['c.datec']['label'],$_SERVER["PHP_SELF"],"c.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.tms']['checked']))       print_liste_field_titre($arrayfields['c.tms']['label'],$_SERVER["PHP_SELF"],"c.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['lower_planned_end_date']['checked']))       print_liste_field_titre($arrayfields['lower_planned_end_date']['label'],$_SERVER["PHP_SELF"],"lower_planned_end_date","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['status']['checked']))
	{
		print_liste_field_titre($staticcontratligne->LibStatut(0,3), '', '', '', '', 'width="16"');
		print_liste_field_titre($staticcontratligne->LibStatut(4,3,0), '', '', '', '', 'width="16"');
		print_liste_field_titre($staticcontratligne->LibStatut(4,3,1), '', '', '', '', 'width="16"');
		print_liste_field_titre($staticcontratligne->LibStatut(5,3), '', '', '', '', 'width="16"');
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

		$contracttmp->ref=$obj->ref;
		$contracttmp->id=$obj->rowid;
		$contracttmp->ref_customer=$obj->ref_customer;
		$contracttmp->ref_supplier=$obj->ref_supplier;

		if ($obj->socid > 0)
		{
			$result=$socstatic->fetch($obj->socid);
		}

		print '<tr class="oddeven">';
		if (! empty($arrayfields['c.ref']['checked']))
		{
			print '<td class="nowrap">';
			print $contracttmp->getNomUrl(1);
			if ($obj->nb_late) print img_warning($langs->trans("Late"));
			if (!empty($obj->note_private) || !empty($obj->note_public))
			{
				print ' <span class="note">';
				print '<a href="'.DOL_URL_ROOT.'/contrat/note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
				print '</span>';
			}

			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->contrat->dir_output . '/' . dol_sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($contracttmp->element, $filename, $filedir);
			print '</td>';

			print '</td>';
		}
		if (! empty($arrayfields['c.ref_customer']['checked']))
		{
			print '<td>'.$obj->ref_customer.'</td>';
		}
		if (! empty($arrayfields['c.ref_supplier']['checked']))
		{
			print '<td>'.$obj->ref_supplier.'</td>';
		}
		if (! empty($arrayfields['s.nom']['checked']))
		{
			print '<td>';
			//print '<a href="../comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->name.'</a>';
			if ($obj->socid > 0)
			{
				print $socstatic->getNomUrl(1, '');
			}
			print '</td>';
		}
		if (! empty($arrayfields['s.email']['checked']))
		{
			print '<td>'.$obj->email.'</td>';
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
		if (! empty($arrayfields['sale_representative']['checked']))
		{
			// Sales representatives
			print '<td>';
			if ($obj->socid > 0)
			{
				$listsalesrepresentatives=$socstatic->getSalesRepresentatives($user);
				if ($listsalesrepresentatives < 0) dol_print_error($db);
				$nbofsalesrepresentative=count($listsalesrepresentatives);
				if ($nbofsalesrepresentative > 3)   // We print only number
				{
					print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$socstatic->id.'">';
					print $nbofsalesrepresentative;
					print '</a>';
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
		// Date
		if (! empty($arrayfields['c.date_contrat']['checked']))
		{
			print '<td align="center">'.dol_print_date($db->jdate($obj->date_contrat), 'day', 'tzuser').'</td>';
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
		// Date lower end date
		if (! empty($arrayfields['lower_planned_end_date']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($obj->lower_planned_end_date), 'day', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Status
		if (! empty($arrayfields['status']['checked']))
		{
			print '<td align="center">'.($obj->nb_initial>0?$obj->nb_initial:'').'</td>';
			print '<td align="center">'.($obj->nb_running>0?$obj->nb_running:'').'</td>';
			print '<td align="center">'.($obj->nb_expired>0?$obj->nb_expired:'').'</td>';
			print '<td align="center">'.($obj->nb_closed>0 ?$obj->nb_closed:'').'</td>';
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
	$db->free($resql);

	print '</table>';
	print '</div>';

	print '</form>';

	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->contrat->lire;
	$delallowed=$user->rights->contrat->lire;

	print $formfile->showdocuments('massfilesarea_contract','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'','','',null,$hidegeneratedfilelistifempty);
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();

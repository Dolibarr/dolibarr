<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2019 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2016	   Ferran Marcet         <fmarcet@2byte.es>
 * Copyright (C) 2018	   Charlene Benke        <charlie@patas-monkey.com>
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
 *	\file       	htdocs/supplier_proposal/list.php
 *	\ingroup    	supplier_proposal
 *	\brief      	Page of supplier proposals card and list
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
if (! empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'propal', 'supplier_proposal', 'compta', 'bills', 'orders', 'products'));

$socid=GETPOST('socid', 'int');

$action=GETPOST('action', 'alpha');
$massaction=GETPOST('massaction', 'alpha');
$show_files=GETPOST('show_files', 'int');
$confirm=GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage=GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'supplierproposallist';

$search_user=GETPOST('search_user', 'int');
$search_sale=GETPOST('search_sale', 'int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref', 'alpha'):GETPOST('search_ref', 'alpha');
$search_societe=GETPOST('search_societe', 'alpha');
$search_author=GETPOST('search_author', 'alpha');
$search_town=GETPOST('search_town', 'alpha');
$search_zip=GETPOST('search_zip', 'alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country", 'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty", 'int');
$search_montant_ht=GETPOST('search_montant_ht', 'alpha');
$search_montant_vat=GETPOST('search_montant_vat', 'alpha');
$search_montant_ttc=GETPOST('search_montant_ttc', 'alpha');
$search_status=GETPOST('viewstatut', 'alpha')?GETPOST('viewstatut', 'alpha'):GETPOST('search_status', 'int');
$object_statut=$db->escape(GETPOST('supplier_proposal_statut'));
$search_btn=GETPOST('button_search', 'alpha');
$search_remove_btn=GETPOST('button_removefilter', 'alpha');

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));

$mesg=(GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));
$year=GETPOST("year");
$month=GETPOST("month");
$day=GETPOST("day");
$yearvalid=GETPOST("yearvalid");
$monthvalid=GETPOST("monthvalid");
$dayvalid=GETPOST("dayvalid");

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='sp.date_livraison';
if (! $sortorder) $sortorder='DESC';

if ($object_statut != '') $search_status=$object_statut;

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

// Security check
$module='supplier_proposal';
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

$diroutputmassaction=$conf->supplier_proposal->dir_output . '/temp/massgeneration/'.$user->id;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new SupplierProposal($db);
$hookmanager->initHooks(array('supplier_proposallist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('supplier_proposal');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');


// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'sp.ref'=>'Ref',
	's.nom'=>'Supplier',
	'pd.description'=>'Description',
	'sp.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["p.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
	'sp.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("Supplier"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'sp.date_valid'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
	'sp.date_livraison'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
	'sp.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	'sp.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
	'sp.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
	'u.login'=>array('label'=>$langs->trans("Author"), 'checked'=>1, 'position'=>10),
	'sp.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'sp.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'sp.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
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

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') ||GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_categ='';
	$search_user='';
	$search_sale='';
	$search_ref='';
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
	$search_author='';
	$yearvalid='';
	$monthvalid='';
	$dayvalid='';
	$year='';
	$month='';
	$day='';
	$search_status='';
	$object_statut='';
}

if (empty($reshook))
{
	$objectclass='SupplierProposal';
	$objectlabel='SupplierProposals';
	$permtoread = $user->rights->supplier_proposal->lire;
	$permtodelete = $user->rights->supplier_proposal->supprimer;
	$uploaddir = $conf->supplier_proposal->dir_output;
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
$formcompany=new FormCompany($db);

$help_url='EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur';
//llxHeader('',$langs->trans('CommRequest'),$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= ' sp.rowid, sp.note_private, sp.total_ht, sp.tva as total_vat, sp.total as total_ttc, sp.localtax1, sp.localtax2, sp.ref, sp.fk_statut, sp.fk_user_author, sp.date_valid, sp.date_livraison as dp,';
$sql.= ' sp.datec as date_creation, sp.tms as date_update,';
$sql.= " p.rowid as project_id, p.ref as project_ref,";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " sc.fk_soc, sc.fk_user,";
$sql.= " u.firstname, u.lastname, u.photo, u.login";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql.= ', '.MAIN_DB_PREFIX.'supplier_proposal as sp';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."supplier_proposal_extrafields as ef on (sp.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'supplier_proposaldet as pd ON sp.rowid=pd.fk_supplier_proposal';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON sp.fk_user_author = u.rowid';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = sp.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as c";
	$sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE sp.fk_soc = s.rowid';
$sql.= ' AND sp.entity IN ('.getEntity('supplier_proposal').')';
if (! $user->rights->societe->client->voir && ! $socid) //restriction
{
	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
}
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip", $search_zip);
if ($search_state) $sql.= natural_search("state.nom", $search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_ref)     $sql .= natural_search('sp.ref', $search_ref);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_author)  $sql .= natural_search('u.login', $search_author);
if ($search_montant_ht) $sql.= natural_search('sp.total_ht=', $search_montant_ht, 1);
if ($search_montant_vat != '') $sql.= natural_search("sp.tva", $search_montant_vat, 1);
if ($search_montant_ttc != '') $sql.= natural_search("sp.total", $search_montant_ttc, 1);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($socid) $sql.= ' AND s.rowid = '.$socid;
if ($search_status >= 0 && $search_status != '') $sql.= ' AND sp.fk_statut IN ('.$db->escape($search_status).')';
$sql.= dolSqlDateFilter("sp.date_livraison", $day, $month, $year);
$sql.= dolSqlDateFilter("sp.date_valid", $dayvalid, $monthvalid, $yearvalid);
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
	$sql.= " AND c.fk_c_type_contact = tc.rowid AND tc.element='supplier_proposal' AND tc.source='internal' AND c.element_id = sp.rowid AND c.fk_socpeople = ".$search_user;
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield, $sortorder);
$sql.=', sp.ref DESC';

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit + 1, $offset);

$resql=$db->query($sql);
if ($resql)
{
	$objectstatic=new SupplierProposal($db);
	$userstatic=new User($db);

	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfSupplierProposals') . ' - '.$soc->name;
	}
	else
	{
		$title = $langs->trans('ListOfSupplierProposals');
	}

	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);

		$id = $obj->rowid;

		header("Location: ".DOL_URL_ROOT.'/supplier_proposal/card.php?id='.$id);

		exit;
	}

	llxHeader('', $langs->trans('CommRequest'), $help_url);

	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)				 $param.='&sall='.$sall;
	if ($month)              $param.='&month='.$month;
	if ($year)               $param.='&year='.$year;
	if ($search_ref)         $param.='&search_ref=' .$search_ref;
	if ($search_societe)     $param.='&search_societe=' .$search_societe;
	if ($search_user > 0)    $param.='&search_user='.$search_user;
	if ($search_sale > 0)    $param.='&search_sale='.$search_sale;
	if ($search_montant_ht)  $param.='&search_montant_ht='.$search_montant_ht;
	if ($search_author)  	 $param.='&search_author='.$search_author;
	if ($search_town)		 $param.='&search_town='.$search_town;
	if ($search_zip)		 $param.='&search_zip='.$search_zip;
	if ($socid > 0)          $param.='&socid='.$socid;
	if ($search_status != '') $param.='&search_status='.$search_status;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
		'builddoc'=>$langs->trans("PDFMerge"),
	    //'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->supplier_proposal->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if($user->rights->supplier_proposal->creer)
	{
        $newcardbutton.= dolGetButtonTitle($langs->trans('NewAskPrice'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/supplier_proposal/card.php?action=create');
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

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_commercial.png', 0, $newcardbutton, '', $limit);

	$topicmail="SendSupplierProposalRef";
	$modelmail="supplier_proposal_send";
	$objecttmp=new SupplierProposal($db);
	$trackid='spro'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall).'</div>';
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
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
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
	if (! empty($arrayfields['sp.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="12" name="search_societe" value="'.dol_escape_htmltag($search_societe).'">';
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
		print '<td class="liste_titre center">';
		print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
		print '</td>';
	}
	// Company type
	if (! empty($arrayfields['typent.code']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT));
		print '</td>';
	}
	// Date
	if (! empty($arrayfields['sp.date_valid']['checked']))
	{
		print '<td class="liste_titre center" colspan="1">';
		//print $langs->trans('Month').': ';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="monthvalid" value="'.dol_escape_htmltag($monthvalid).'">';
		//print '&nbsp;'.$langs->trans('Year').': ';
		$syearvalid = $yearvalid;
		$formother->select_year($syearvalid, 'yearvalid', 1, 20, 5);
		print '</td>';
	}
	// Date
	if (! empty($arrayfields['sp.date_livraison']['checked']))
	{
		print '<td class="liste_titre center" colspan="1">';
		//print $langs->trans('Month').': ';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="month" value="'.dol_escape_htmltag($month).'">';
		//print '&nbsp;'.$langs->trans('Year').': ';
		$syear = $year;
		$formother->select_year($syear, 'year', 1, 20, 5);
		print '</td>';
	}

	if (! empty($arrayfields['sp.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
		print '</td>';
	}
	if (! empty($arrayfields['sp.total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
		print '</td>';
	}
	if (! empty($arrayfields['sp.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
		print '</td>';
	}
	if (! empty($arrayfields['u.login']['checked']))
	{
		// Author
		print '<td class="liste_titre center">';
		print '<input class="flat" size="4" type="text" name="search_login" value="'.dol_escape_htmltag($search_author).'">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['sp.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['sp.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (! empty($arrayfields['sp.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone right">';
		$formpropal->selectProposalStatus($search_status, 1, 0, 1, 'supplier', 'search_status');
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre middle">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['sp.ref']['checked']))           print_liste_field_titre($arrayfields['sp.ref']['label'], $_SERVER["PHP_SELF"], 'sp.ref', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	if (! empty($arrayfields['sp.date_valid']['checked']))      print_liste_field_titre($arrayfields['sp.date_valid']['label'], $_SERVER["PHP_SELF"], 'sp.date_valid', '', $param, '', $sortfield, $sortorder, 'center ');
	if (! empty($arrayfields['sp.date_livraison']['checked']))  print_liste_field_titre($arrayfields['sp.date_livraison']['label'], $_SERVER["PHP_SELF"], 'sp.date_livraison', '', $param, '', $sortfield, $sortorder, 'center ');
	if (! empty($arrayfields['sp.total_ht']['checked']))        print_liste_field_titre($arrayfields['sp.total_ht']['label'], $_SERVER["PHP_SELF"], 'sp.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	if (! empty($arrayfields['sp.total_vat']['checked']))       print_liste_field_titre($arrayfields['sp.total_vat']['label'], $_SERVER["PHP_SELF"], 'sp.total_vat', '', $param, '', $sortfield, $sortorder, 'right ');
	if (! empty($arrayfields['sp.total_ttc']['checked']))       print_liste_field_titre($arrayfields['sp.total_ttc']['label'], $_SERVER["PHP_SELF"], 'sp.total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
	if (! empty($arrayfields['u.login']['checked']))            print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, '', $sortfield, $sortorder, 'center ');
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['sp.datec']['checked']))     print_liste_field_titre($arrayfields['sp.datec']['label'], $_SERVER["PHP_SELF"], "sp.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (! empty($arrayfields['sp.tms']['checked']))       print_liste_field_titre($arrayfields['sp.tms']['label'], $_SERVER["PHP_SELF"], "sp.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap');
	if (! empty($arrayfields['sp.fk_statut']['checked'])) print_liste_field_titre($arrayfields['sp.fk_statut']['label'], $_SERVER["PHP_SELF"], "sp.fk_statut", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print '</tr>'."\n";

	$now = dol_now();
	$i=0;
	$total=0;
	$subtotal=0;
	$totalarray=array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$objectstatic->id=$obj->rowid;
		$objectstatic->ref=$obj->ref;

		print '<tr class="oddeven">';

		if (! empty($arrayfields['sp.ref']['checked']))
		{
			print '<td class="nowrap">';

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			// Picto + Ref
			print '<td class="nobordernopadding nowrap">';
			print $objectstatic->getNomUrl(1);
			print '</td>';
			// Warning
			$warnornote='';
			if ($obj->fk_statut == 1 && $db->jdate($obj->date_valid) < ($now - $conf->supplier_proposal->warning_delay)) $warnornote.=img_warning($langs->trans("Late"));
			if (! empty($obj->note_private))
			{
				$warnornote.=($warnornote?' ':'');
				$warnornote.= '<span class="note">';
				$warnornote.= '<a href="note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"), 'object_generic').'</a>';
				$warnornote.= '</span>';
			}
			if ($warnornote)
			{
				print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
				print $warnornote;
				print '</td>';
			}
			// Other picto tool
			print '<td width="16" class="right nobordernopadding hideonsmartphone">';
			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		$url = DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid;

		// Company
		$companystatic->id=$obj->socid;
		$companystatic->name=$obj->name;
		$companystatic->client=$obj->client;
		$companystatic->code_client=$obj->code_client;

		// Thirdparty
		if (! empty($arrayfields['s.nom']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $companystatic->getNomUrl(1, 'customer');
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
			print '<td class="center">';
			$tmparray=getCountry($obj->fk_pays, 'all');
			print $tmparray['label'];
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Type ent
		if (! empty($arrayfields['typent.code']['checked']))
		{
			print '<td class="center">';
			if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
			print $typenArray[$obj->typent_code];
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Date proposal
		if (! empty($arrayfields['sp.date_valid']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_valid), 'day');
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Date delivery
		if (! empty($arrayfields['sp.date_livraison']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->dp), 'day');
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Amount HT
		if (! empty($arrayfields['sp.total_ht']['checked']))
		{
			  print '<td class="right">'.price($obj->total_ht)."</td>\n";
			  if (! $i) $totalarray['nbfield']++;
			  if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
			  $totalarray['totalht'] += $obj->total_ht;
		}
		// Amount VAT
		if (! empty($arrayfields['sp.total_vat']['checked']))
		{
			print '<td class="right">'.price($obj->total_vat)."</td>\n";
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
			$totalarray['totalvat'] += $obj->total_vat;
		}
		// Amount TTC
		if (! empty($arrayfields['sp.total_ttc']['checked']))
		{
			print '<td class="right">'.price($obj->total_ttc)."</td>\n";
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
			$totalarray['totalttc'] += $obj->total_ttc;
		}

		$userstatic->id=$obj->fk_user_author;
		$userstatic->login=$obj->login;

		// Author
		if (! empty($arrayfields['u.login']['checked']))
		{
			print '<td class="center">';
			if ($userstatic->id) print $userstatic->getLoginUrl(1);
			else print '&nbsp;';
			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (! empty($arrayfields['sp.datec']['checked']))
		{
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date modification
		if (! empty($arrayfields['sp.tms']['checked']))
		{
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Status
		if (! empty($arrayfields['sp.fk_statut']['checked']))
		{
			print '<td class="right">'.$objectstatic->LibStatut($obj->fk_statut, 5)."</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected=0;
			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";

		$total += $obj->total_ht;
		$subtotal += $obj->total_ht;

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
				if ($num < $limit && empty($offset)) print '<td class="left">'.$langs->trans("Total").'</td>';
				else print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
			}
			elseif ($totalarray['totalhtfield'] == $i) print '<td class="right">'.price($totalarray['totalht']).'</td>';
			elseif ($totalarray['totalvatfield'] == $i) print '<td class="right">'.price($totalarray['totalvat']).'</td>';
			elseif ($totalarray['totalttcfield'] == $i) print '<td class="right">'.price($totalarray['totalttc']).'</td>';
			else print '<td></td>';
		}
		print '</tr>';
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>'."\n";

	print '</form>'."\n";

	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;', '&', $param);

	$filedir=$diroutputmassaction;

	$genallowed=$user->rights->supplier_proposal->lire;
	$delallowed=$user->rights->supplier_proposal->creer;

	print $formfile->showdocuments('massfilesarea_supplier_proposal', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();

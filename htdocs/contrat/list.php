<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("contracts");
$langs->load("products");
$langs->load("companies");
$langs->load("compta");

$search_name=GETPOST('search_name');
$search_contract=GETPOST('search_contract');
$search_ref_supplier=GETPOST('search_ref_supplier','alpha');
$sall=GETPOST('sall');
$search_status=GETPOST('search_status');
$socid=GETPOST('socid');
$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_product_category=GETPOST('search_product_category','int');
$day=GETPOST("day","int");
$year=GETPOST("year","int");
$month=GETPOST("month","int");

$optioncss = GETPOST('optioncss','alpha');

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='c.ref';
if (! $sortorder) $sortorder='DESC';

// Security check
$id=GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $id);

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);

if ($search_status == '') $search_status=1;

$contextpage='contractlist';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('contract');
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


/*
 * Action
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (empty($reshook))
{

}

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_name="";
	$search_contract="";
	$search_ref_supplier="";
    $search_user='';
    $search_sale='';
    $search_product_category='';
	$sall="";
	$search_status="";
	$search_array_options=array();
	$day='';
	$month='';
	$year='';
}


/*
 * View
 */

$now=dol_now();
$form=new Form($db);
$formother = new FormOther($db);
$socstatic = new Societe($db);

llxHeader();

$sql = 'SELECT';
$sql.= " c.rowid as cid, c.ref, c.datec, c.date_contrat, c.statut, c.ref_customer, c.ref_supplier,";
$sql.= " s.nom as name, s.rowid as socid,";
$sql.= ' SUM('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
$sql.= ' SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')",1,0).') as nb_running,';
$sql.= ' SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')",1,0).') as nb_expired,';
$sql.= ' SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now - $conf->contrat->services->expires->warning_delay)."')",1,0).') as nb_late,';
$sql.= ' SUM('.$db->ifsql("cd.statut=5",1,0).') as nb_closed';
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=cd.fk_product';
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= " WHERE c.fk_soc = s.rowid ";
$sql.= ' AND c.entity IN ('.getEntity('contract', 1).')';
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
if ($search_contract) $sql .= natural_search(array('c.rowid', 'c.ref'), $search_contract);
if (!empty($search_ref_supplier)) $sql .= natural_search(array('c.ref_supplier'), $search_ref_supplier);
if ($search_sale > 0)
{
	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
}
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($search_user > 0) $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='contrat' AND tc.source='internal' AND ec.element_id = c.rowid AND ec.fk_socpeople = ".$search_user;
$sql.= " GROUP BY c.rowid, c.ref, c.datec, c.date_contrat, c.statut, c.ref_customer, c.ref_supplier, s.nom, s.rowid";
$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}
$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = 0;
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

    $param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
    if ($sall != '')                $param.='&sall='.$sall;
    if ($search_contract != '')     $param.='&search_contract='.$search_contract;
    if ($search_name != '')         $param.='&search_name='.$search_name;
    if ($search_ref_supplier != '') $param.='&search_ref_supplier='.$search_ref_supplier;
    if ($search_sale != '')         $param.='&search_sale=' .$search_sale;
    if ($optioncss != '')           $param.='&optioncss='.$optioncss;
    
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

    print_barre_liste($langs->trans("ListOfContracts"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num,$totalnboflines,'title_commercial.png', 0, '', '', $limit);

	if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
    
    // If the user can view prospects other than his'
    $moreforfilter='';
    if ($user->rights->societe->client->voir || $socid)
    {
    	$langs->load("commercial");
    	$moreforfilter.='<div class="divsearchfield">';
    	$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
    	$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user,0,1,'maxwidth300');
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
	// If the user can view categories of products
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

    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';
    print '<tr class="liste_titre">';

    print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "c.ref","","$param",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("RefCustomer"), $_SERVER["PHP_SELF"], "c.ref_customer","","$param",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("RefSupplier"), $_SERVER["PHP_SELF"], "c.ref_supplier","","$param",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ThirdParty"), $_SERVER["PHP_SELF"], "s.nom","","$param",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("SalesRepresentative"), $_SERVER["PHP_SELF"], "","","$param",'',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("DateCreation"), $_SERVER["PHP_SELF"], "c.datec","","$param",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateContract"), $_SERVER["PHP_SELF"], "c.date_contrat","","$param",'align="center"',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "c.statut","","$param",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($staticcontratligne->LibStatut(0,3), '', '', '', '', 'width="16"');
    print_liste_field_titre($staticcontratligne->LibStatut(4,3,0), '', '', '', '', 'width="16"');
    print_liste_field_titre($staticcontratligne->LibStatut(4,3,1), '', '', '', '', 'width="16"');
    print_liste_field_titre($staticcontratligne->LibStatut(5,3), '', '', '', '', 'width="16"');
    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="3" name="search_contract" value="'.dol_escape_htmltag($search_contract).'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="6" name="search_ref_customer value="'.dol_escape_htmltag($search_ref_customer).'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="6" name="search_ref_supplier value="'.dol_escape_htmltag($search_ref_supplier).'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="8" name="search_name" value="'.dol_escape_htmltag($search_name).'">';
    print '</td>';
    print '<td></td>';
    // Date contract
    print '<td class="liste_titre center">';
  	//print $langs->trans('Month').': ';
   	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
   	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
   	//print '&nbsp;'.$langs->trans('Year').': ';
   	$syear = $year;
   	$formother->select_year($syear,'year',1, 20, 5);
    print '</td>';
    print '<td class="liste_titre" colspan="4" align="right"></td>';
    print '<td>';
    $searchpitco=$form->showFilterAndCheckAddButtons(0);
    print $searchpitco;
    print '</td>';
    print "</tr>\n";

    $var=true;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td class="nowrap"><a href="card.php?id='.$obj->cid.'">';
        print img_object($langs->trans("ShowContract"),"contract").' '.(isset($obj->ref) ? $obj->ref : $obj->cid) .'</a>';
        if ($obj->nb_late) print img_warning($langs->trans("Late"));
        print '</td>';
        print '<td>'.$obj->ref_customer.'</td>';
        print '<td>'.$obj->ref_supplier.'</td>';
        print '<td><a href="../comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->name.'</a></td>';
        //print '<td align="center">'.dol_print_date($obj->datec).'</td>';

        // Sales Rapresentatives
        print '<td>';
        if($obj->socid)
        {
        	$result=$socstatic->fetch($obj->socid);
        	if ($result < 0)
        	{
        		dol_print_error($db);
        		exit;
        	}
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
        			print '<div class="float">'.$userstatic->getNomUrl(1);
        			$j++;
        			if ($j < $nbofsalesrepresentative) print ', ';
        			print '</div>';
        		}
        	}
        	//else print $langs->trans("NoSalesRepresentativeAffected");
        }
        else
        {
        	print '&nbsp';
        }
        print '</td>';


        print '<td align="center">'.dol_print_date($db->jdate($obj->date_contrat), 'day').'</td>';
        //print '<td align="center">'.$staticcontrat->LibStatut($obj->statut,3).'</td>';
        print '<td align="center">'.($obj->nb_initial>0?$obj->nb_initial:'').'</td>';
        print '<td align="center">'.($obj->nb_running>0?$obj->nb_running:'').'</td>';
        print '<td align="center">'.($obj->nb_expired>0?$obj->nb_expired:'').'</td>';
        print '<td align="center">'.($obj->nb_closed>0 ?$obj->nb_closed:'').'</td>';
        print '<td></td>';
        print "</tr>\n";
        $i++;
    }
    $db->free($resql);

    print '</table>';
    print '</form>';
}
else
{
    dol_print_error($db);
}


llxFooter();
$db->close();

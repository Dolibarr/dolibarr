<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2015 Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 *
 * This program is freei software; you can redistribute it and/or modify
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
 *	\file       htdocs/comm/list.php
 *	\ingroup    commercial societe
 *	\brief      List of customers
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("commercial");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page=GETPOST('page','int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$search_company = GETPOST("search_company");
$search_zipcode = GETPOST("search_zipcode");
$search_town = GETPOST("search_town");
$search_code = GETPOST("search_code");
$search_compta = GETPOST("search_compta");
$search_status = GETPOST("search_status",'int');
$search_country	= GETPOST("search_country",'int');
$search_type_thirdparty	= GETPOST("search_type_thirdparty",'int');
$optioncss = GETPOST('optioncss','alpha');

// Load sale and categ filters
$search_sale  = GETPOST("search_sale",'int');
$search_categ = GETPOST("search_categ",'int');
$catid        = GETPOST("catid",'int');
// If the internal user must only see his customers, force searching by him
if (!$user->rights->societe->client->voir && !$socid) $search_sale = $user->id;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('customerlist'));
$extrafields = new ExtraFields($db);


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_sale="";
	$search_categ="";
	$catid="";
    $search_company="";
    $search_zipcode="";
    $search_town="";
    $search_code='';
    $search_compta='';
    $search_status='';
    $search_country="";
    $search_type_thirdparty='';
}

if ($search_status=='') $search_status=1; // always display activ customer first


/*
 * view
 */

$formother=new FormOther($db);
$form = new Form($db);
$thirdpartystatic=new Societe($db);
$formcompany=new FormCompany($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$sql = "SELECT s.rowid, s.nom as name, s.name_alias, s.client, s.zip, s.town, st.libelle as stcomm, s.prefix_comm, s.code_client, s.code_compta, s.status as status,";
$sql.= " s.datec, s.canvas";
$sql.= ",s.fk_pays";
$sql.= ",typent.code as typent_code";
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
// Add fields for extrafields
foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc"; // We need this table joined to the select in order to filter by categ
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays) ";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent) ";
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (1, 3)";
$sql.= ' AND s.entity IN ('.getEntity('societe', 1).')';
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale) $sql.= " AND s.rowid = sc.fk_soc";
if ($socid) $sql.= " AND s.rowid = ".$socid;
if ($search_sale > 0)    $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
if ($catid > 0)          $sql.= " AND cs.fk_categorie = ".$catid;
if ($catid == -2)        $sql.= " AND cs.fk_categorie IS NULL";
if ($search_categ > 0)   $sql.= " AND cs.fk_categorie = ".$search_categ;
if ($search_categ == -2) $sql.= " AND cs.fk_categorie IS NULL";
if ($search_company)     $sql.= natural_search(array('s.nom', 's.name_alias'), $search_company);
if ($search_zipcode)     $sql.= natural_search("s.zip", $search_zipcode);
if ($search_town)        $sql.= natural_search('s.town', $search_town);
if ($search_code)        $sql.= natural_search("s.code_client", $search_code);
if ($search_compta)      $sql.= natural_search("s.code_compta", $search_compta);
if ($search_status!='')  $sql.= " AND s.status = ".$db->escape($search_status);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_sale > 0)    $sql.= " AND sc.fk_user = ".$search_sale;
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit +1, $offset);

dol_syslog('comm/list.php:', LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$param = "&amp;search_company=".htmlspecialchars($search_company);
	$param.="&amp;search_code=".htmlspecialchars($search_code);
	$param.="&amp;search_zipcode=".htmlspecialchars($search_zipcode);
	$param.="&amp;search_town=".htmlspecialchars($search_town);
 	if ($search_categ != '') $param.='&amp;search_categ='.htmlspecialchars($search_categ);
 	if ($search_sale > 0)	$param.='&amp;search_sale='.htmlspecialchars($search_sale);
 	if ($search_status != '') $param.='&amp;search_status='.htmlspecialchars($search_status);
 	if ($search_country != '') $param.='&amp;search_country='.htmlspecialchars($search_country);
 	if ($search_type_thirdparty != '') $param.='&amp;search_type_thirdparty='.htmlspecialchars($search_type_thirdparty);
	if ($optioncss != '') $param.='&amp;optioncss='.$optioncss;

	print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_companies.png');

	$i = 0;

	print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';

	// Filter on categories
 	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
	 	$moreforfilter.='<div class="divsearchfield">';
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(2,$search_categ,'search_categ',1);
	 	$moreforfilter.='</div>';
	}
 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
	 	$moreforfilter.='<div class="divsearchfield">';
	 	$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
		$moreforfilter.='</div>';
 	}
 	if ($moreforfilter)
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
	    print $moreforfilter;
    	$parameters=array();
    	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    	print $hookmanager->resPrint;
	    print '</div>';
	}

    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Country"),$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ThirdPartyType"),$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AccountancyCode"),$_SERVER["PHP_SELF"],"s.code_compta","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"datec","",$param,'align="right"',$sortfield,$sortorder);
    $parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company" value="'.$search_company.'" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_zipcode" value="'.$search_zipcode.'" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_town" value="'.$search_town.'" size="10">';
    print '</td>';

    print '<td class="liste_titre" align="center">';
    print $form->select_country($search_country,'search_country','',0,'maxwidth100');
    print '</td>';
    
    print '<td class="liste_titre" align="center">';
    print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT));
    print '</td>';

    print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_code" value="'.$search_code.'" size="10">';
    print '</td>';

    print '<td align="left" class="liste_titre">';
    print '<input type="text" class="flat" name="search_compta" value="'.$search_compta.'" size="10">';
    print '</td>';

    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';

    $parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

    print '<td class="liste_titre" align="center">';
    print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$search_status);
    print '</td>';

    print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';

    print '</tr>'."\n";


	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		$var=!$var;

		print "<tr ".$bc[$var].">";
		print '<td>';
		$thirdpartystatic->id=$obj->rowid;
        $thirdpartystatic->name=$obj->name;
        $thirdpartystatic->client=$obj->client;
        $thirdpartystatic->code_client=$obj->code_client;
        $thirdpartystatic->canvas=$obj->canvas;
        $thirdpartystatic->status=$obj->status;
        $thirdpartystatic->name_alias=$obj->name_alias;
        print $thirdpartystatic->getNomUrl(1);
		print '</td>';
		print '<td>'.$obj->zip.'</td>';
        print '<td>'.$obj->town.'</td>';
		//Country
        print '<td align="center">';
        $tmparray=getCountry($obj->fk_pays,'all');
        print $tmparray['label'];
        print '</td>';
        //Type ent
        print '<td align="center">';
        if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
        print $typenArray[$obj->typent_code];
        print '</td>';
        print '<td>'.$obj->code_client.'</td>';
        print '<td>'.$obj->code_compta.'</td>';
        print '<td align="right">'.dol_print_date($db->jdate($obj->datec),'day').'</td>';

        $parameters=array('obj' => $obj);
        $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
	    print $hookmanager->resPrint;

        print '<td align="center">'.$thirdpartystatic->getLibStatut(3);
        print '</td>';

        print '<td></td>';

        print "</tr>\n";
		$i++;
	}
	$db->free($result);

	$parameters=array('sql' => $sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>\n";
	print "</form>\n";
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();

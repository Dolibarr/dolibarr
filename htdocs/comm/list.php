<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/comm/list.php
 *	\ingroup    commercial societe
 *	\brief      List of customers
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

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

$search_nom=GETPOST("search_nom");
$search_zipcode=GETPOST("search_zipcode");
$search_town=GETPOST("search_town");
$search_code=GETPOST("search_code");
$search_compta=GETPOST("search_compta");

// Load sale and categ filters
$search_sale  = GETPOST("search_sale");
$search_categ = GETPOST("search_categ",'int');
$catid        = GETPOST("catid",'int');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('customerlist'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $search_categ='';
    $catid='';
    $search_sale='';
    $socname="";
    $search_nom="";
    $search_zipcode="";
    $search_town="";
    $search_idprof1='';
    $search_idprof2='';
    $search_idprof3='';
    $search_idprof4='';
}



/*
 * view
 */

$formother=new FormOther($db);
$thirdpartystatic=new Societe($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$sql = "SELECT s.rowid, s.nom as name, s.client, s.zip, s.town, st.libelle as stcomm, s.prefix_comm, s.code_client, s.code_compta, s.status as status,";
$sql.= " s.datec, s.datea, s.canvas";
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_societe"; // We need this table joined to the select in order to filter by categ
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || !$user->rights->societe->client->voir) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (1, 3)";
$sql.= ' AND s.entity IN ('.getEntity('societe', 1).')';
if (!$user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
if ($catid > 0)          $sql.= " AND cs.fk_categorie = ".$catid;
if ($catid == -2)        $sql.= " AND cs.fk_categorie IS NULL";
if ($search_categ > 0)   $sql.= " AND cs.fk_categorie = ".$search_categ;
if ($search_categ == -2) $sql.= " AND cs.fk_categorie IS NULL";
if ($search_nom)   $sql.= " AND s.nom LIKE '%".$db->escape($search_nom)."%'";
if ($search_zipcode) $sql.= " AND s.zip LIKE '".$db->escape($search_zipcode)."%'";
if ($search_town) $sql.= " AND s.town LIKE '%".$db->escape($search_town)."%'";
if ($search_code)  $sql.= " AND s.code_client LIKE '%".$db->escape($search_code)."%'";
if ($search_compta) $sql.= " AND s.code_compta LIKE '%".$db->escape($search_compta)."%'";
// Insert sale filter
if ($search_sale)
{
	$sql .= " AND sc.fk_user = ".$search_sale;
}

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit +1, $offset);

dol_syslog('comm:list.php: sql='.$sql,LOG_DEBUG);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$param = "&amp;search_nom=".$search_nom."&amp;search_code=".$search_code."&amp;search_zipcode=".$search_zipcode."&amp;search_town=".$search_town;
 	if ($search_categ != '') $param.='&amp;search_categ='.$search_categ;
 	if ($search_sale != '')	$param.='&amp;search_sale='.$search_sale;

	print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	$i = 0;

	print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	// Filter on categories
 	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(2,$search_categ,'search_categ',1);
	 	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
	 	$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
 	}
 	if ($moreforfilter)
	{
		print '<div class="liste_titre">';
	    print $moreforfilter;
	    print '</div>';
	}

	print '<table class="liste" width="100%">'."\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AccountancyCode"),$_SERVER["PHP_SELF"],"s.code_compta","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"datec","",$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$param,'align="right"',$sortfield,$sortorder);

    $parameters=array();
    $formconfirm=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook

    print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_nom" value="'.$search_nom.'" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_zipcode" value="'.$search_zipcode.'" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_town" value="'.$search_town.'" size="10">';
    print '</td>';

    print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_code" value="'.$search_code.'" size="10">';
    print '</td>';

    print '<td align="left" class="liste_titre">';
    print '<input type="text" class="flat" name="search_compta" value="'.$search_compta.'" size="10">';
    print '</td>';

    print '</td><td>&nbsp;</td>';

    print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '&nbsp; ';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';

    $parameters=array();
    $formconfirm=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook

    print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		$var=!$var;

		print "<tr $bc[$var]>";
		print '<td>';
		$thirdpartystatic->id=$obj->rowid;
        $thirdpartystatic->name=$obj->name;
        $thirdpartystatic->client=$obj->client;
        $thirdpartystatic->canvas=$obj->canvas;
        $thirdpartystatic->status=$obj->status;
        print $thirdpartystatic->getNomUrl(1);
		print '</td>';
		print '<td>'.$obj->zip.'</td>';
        print '<td>'.$obj->town.'</td>';
        print '<td>'.$obj->code_client.'</td>';
        print '<td>'.$obj->code_compta.'</td>';
        print '<td align="right">'.dol_print_date($db->jdate($obj->datec),'day').'</td>';
        print '<td align="right">'.$thirdpartystatic->getLibStatut(3);
        print '</td>';

        $parameters=array('obj' => $obj);
        $formconfirm=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook

        print "</tr>\n";
		$i++;
	}
	//print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],'',$sortfield,$sortorder,'',$num);
	print "</table>\n";
	print "</form>\n";
	$db->free($result);

	$parameters=array('sql' => $sql);
	$formconfirm=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
?>

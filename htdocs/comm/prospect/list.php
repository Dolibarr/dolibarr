<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/comm/prospect/list.php
 *	\ingroup    prospect
 *	\brief      Page to list prospects
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/prospect/class/prospect.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("propal");
$langs->load("companies");

// Security check
$socid = GETPOST("socid",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$socname            = GETPOST("socname",'alpha');
$stcomm             = GETPOST("stcomm",'int');
$search_nom         = GETPOST("search_nom");
$search_ville       = GETPOST("search_ville");
$search_departement = GETPOST("search_departement");
$search_datec       = GETPOST("search_datec");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page      = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$search_level_from = GETPOST("search_level_from","alpha");
$search_level_to   = GETPOST("search_level_to","alpha");

// If both parameters are set, search for everything BETWEEN them
if ($search_level_from != '' && $search_level_to != '')
{
	// Ensure that these parameters are numbers
	$search_level_from = (int) $search_level_from;
	$search_level_to = (int) $search_level_to;

	// If from is greater than to, reverse orders
	if ($search_level_from > $search_level_to)
	{
		$tmp = $search_level_to;
		$search_level_to = $search_level_from;
		$search_level_from = $tmp;
	}

	// Generate the SQL request
	$sortwhere = '(sortorder BETWEEN '.$search_level_from.' AND '.$search_level_to.') AS is_in_range';
}
// If only "from" parameter is set, search for everything GREATER THAN it
else if ($search_level_from != '')
{
	// Ensure that this parameter is a number
	$search_level_from = (int) $search_level_from;

	// Generate the SQL request
	$sortwhere = '(sortorder >= '.$search_level_from.') AS is_in_range';
}
// If only "to" parameter is set, search for everything LOWER THAN it
else if ($search_level_to != '')
{
	// Ensure that this parameter is a number
	$search_level_to = (int) $search_level_to;

	// Generate the SQL request
	$sortwhere = '(sortorder <= '.$search_level_to.') AS is_in_range';
}
// If no parameters are set, dont search for anything
else
{
	$sortwhere = '0 as is_in_range';
}

// Select every potentiels, and note each potentiels which fit in search parameters
dol_syslog('prospects::prospects_prospect_level',LOG_DEBUG);
$sql = "SELECT code, label, sortorder, ".$sortwhere;
$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
$sql.= " WHERE active > 0";
$sql.= " ORDER BY sortorder";

$resql = $db->query($sql);
if ($resql)
{
	$tab_level = array();
	$search_levels = array();

	while ($obj = $db->fetch_object($resql))
	{
		// Compute level text
		$level=$langs->trans($obj->code);
		if ($level == $obj->code) $level=$langs->trans($obj->label);

		// Put it in the array sorted by sortorder
		$tab_level[$obj->sortorder] = $level;

		// If this potentiel fit in parameters, add its code to the $search_levels array
		if ($obj->is_in_range == 1)
		{
			$search_levels[] = '"'.preg_replace('[^A-Za-z0-9_-]', '', $obj->code).'"';
		}

		$i++;
	}

	// Implode the $search_levels array so that it can be use in a "IN (...)" where clause.
	// If no paramters was set, $search_levels will be empty
	$search_levels = implode(',', $search_levels);
}
else dol_print_error($db);

// Load sale and categ filters
$search_sale = isset($_GET["search_sale"])?$_GET["search_sale"]:$_POST["search_sale"];
$search_categ = isset($_GET["search_categ"])?$_GET["search_categ"]:$_POST["search_categ"];
// If the user must only see his prospect, force searching by him
if (!$user->rights->societe->client->voir && !$socid) $search_sale = $user->id;

// List of avaible states; we'll need that for each lines (quick changing prospect states) and for search bar (filter by prospect state)
$sts = array(-1,0,1,2,3);

/*
 * Actions
 */
if ($_GET["action"] == 'cstc')
{
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["pstcomm"];
	$sql .= " WHERE rowid = ".$_GET["socid"];
	$result=$db->query($sql);
}


/*
 * View
 */

$formother=new FormOther($db);

$sql = "SELECT s.rowid, s.nom, s.ville, s.datec, s.datea, s.status as status,";
$sql.= " st.libelle as stcomm, s.prefix_comm, s.fk_stcomm, s.fk_prospectlevel,";
$sql.= " d.nom as departement";
// Updated by Matelli
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= ", cs.fk_categorie, cs.fk_societe";
$sql .= " FROM ".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || !$user->rights->societe->client->voir) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d on (d.rowid = s.fk_departement)";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (2, 3)";
$sql.= ' AND s.entity IN ('.getEntity('societe', 1).')';
if ($user->societe_id) $sql.= " AND s.rowid = " .$user->societe_id;
if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
if ($search_categ) $sql.= " AND s.rowid = cs.fk_societe";	// Join for the needed table to filter by categ
if (isset($stcomm) && $stcomm != '') $sql.= " AND s.fk_stcomm=".$stcomm;

if ($search_nom)   $sql .= " AND s.nom LIKE '%".$db->escape(strtolower($search_nom))."%'";
if ($search_ville) $sql .= " AND s.ville LIKE '%".$db->escape(strtolower($search_ville))."%'";
if ($search_departement) $sql .= " AND d.nom LIKE '%".$db->escape(strtolower($search_departement))."%'";
if ($search_datec) $sql .= " AND s.datec LIKE '%".$db->escape($search_datec)."%'";
// Insert levels filters
if ($search_levels)
{
	$sql .= " AND s.fk_prospectlevel IN (".$search_levels.')';
}
// Insert sale filter
if ($search_sale)
{
	$sql .= " AND sc.fk_user = ".$db->escape($search_sale);
}
// Insert categ filter
if ($search_categ)
{
	$sql .= " AND cs.fk_categorie = ".$db->escape($search_categ);
}
if ($socname)
{
	$sql .= " AND s.nom LIKE '%".$db->escape($socname)."%'";
	$sortfield = "s.nom";
	$sortorder = "ASC";
}

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= " ORDER BY $sortfield $sortorder, s.nom ASC";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	if ($num == 1 && $socname)
	{
		$obj = $db->fetch_object($resql);
		Header("Location: fiche.php?socid=".$obj->rowid);
		exit;
	}
	else
	{
        $help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
        llxHeader('',$langs->trans("ThirdParty"),$help_url);
	}

	$param='&amp;stcomm='.$stcomm.'&amp;search_nom='.urlencode($search_nom).'&amp;search_ville='.urlencode($search_ville);
 	// Added by Matelli
 	// Store the status filter in the URL
 	if (isSet($search_cstc))
 	{
 		foreach ($search_cstc as $key => $value)
 		{
 			if ($value == 'true')
 				$param.='&amp;search_cstc['.((int) $key).']=true';
 			else
 				$param.='&amp;search_cstc['.((int) $key).']=false';
 		}
 	}
 	if ($search_level_from != '') $param.='&amp;search_level_from='.$search_level_from;
 	if ($search_level_to != '') $param.='&amp;search_level_to='.$search_level_to;
 	if ($search_categ != '') $param.='&amp;search_categ='.$search_categ;
 	if ($search_sale != '') $param.='&amp;search_sale='.$search_sale;
 	// $param and $urladd should have the same value
 	$urladd = $param;

	print_barre_liste($langs->trans("ListOfProspects"), $page, $_SERVER["PHP_SELF"], $param, $sortfield,$sortorder,'',$num,$nbtotalofrecords);


 	// Print the search-by-sale and search-by-categ filters
 	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'" id="formulaire_recherche">';

	print '<table class="liste" width="100%">';

	// Filter on categories
 	$moreforfilter='';
	if ($conf->categorie->enabled)
	{
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(2,$search_categ,'search_categ');
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
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" colspan="8">';
	    print $moreforfilter;
	    print '</td></tr>';
	}

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.ville","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("State"),$_SERVER["PHP_SELF"],"s.fk_departement","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"s.datec","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ProspectLevelShort"),$_SERVER["PHP_SELF"],"s.fk_prospectlevel","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("StatusProsp"),$_SERVER["PHP_SELF"],"s.fk_stcomm","",$param,'align="center"',$sortfield,$sortorder);
	print '<td class="liste_titre">&nbsp;</td>';
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$params,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_nom" size="10" value="'.$search_nom.'">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ville" size="10" value="'.$search_ville.'">';
	print '</td>';
 	print '<td class="liste_titre" align="center">';
    print '<input type="text" class="flat" name="search_departement" size="10" value="'.$search_departement.'">';
    print '</td>';
    print '<td align="center" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_datec" value="'.$search_datec.'">';
    print '</td>';

 	// Added by Matelli
 	print '<td class="liste_titre" align="center">';
 	// Generate in $options_from the list of each option sorted
 	$options_from = '<option value="">&nbsp;</option>';
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_from .= '<option value="'.$tab_level_sortorder.'"'.($search_level_from == $tab_level_sortorder ? ' selected="selected"':'').'>';
 		$options_from .= $langs->trans($tab_level_label);
 		$options_from .= '</option>';
 	}

 	// Reverse the list
 	array_reverse($tab_level, true);

 	// Generate in $options_to the list of each option sorted in the reversed order
 	$options_to = '<option value="">&nbsp;</option>';
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_to .= '<option value="'.$tab_level_sortorder.'"'.($search_level_to == $tab_level_sortorder ? ' selected="selected"':'').'>';
 		$options_to .= $langs->trans($tab_level_label);
 		$options_to .= '</option>';
 	}

 	// Print these two select
 	print $langs->trans("From").' <select class="flat" name="search_level_from">'.$options_from.'</select>';
 	print ' ';
 	print $langs->trans("To").' <select class="flat" name="search_level_to">'.$options_to.'</select>';

    print '</td>';

    print '<td class="liste_titre" align="center">';
	print '&nbsp;';
    print '</td>';

    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';

    // Print the search button
    print '<td class="liste_titre" align="right">';
	print '<input class="liste_titre" name="button_search" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';

	print "</tr>\n";

	$i = 0;
	$var=true;

	$prospectstatic=new Prospect($db);
	$prospectstatic->client=2;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);

		$var=!$var;

		print '<tr '.$bc[$var].'>';
		print '<td>';
		$prospectstatic->id=$obj->rowid;
		$prospectstatic->nom=$obj->nom;
        $prospectstatic->status=$obj->status;
		print $prospectstatic->getNomUrl(1,'prospect');
        print '</td>';
		print "<td>".$obj->ville."&nbsp;</td>";
		print '<td align="center">'.$obj->departement.'</td>';
		// Creation date
		print '<td align="center">'.dol_print_date($db->jdate($obj->datec)).'</td>';
		// Level
		print '<td align="center">';
		print $prospectstatic->LibLevel($obj->fk_prospectlevel);
		print "</td>";
		// Statut
		print '<td align="center" nowrap="nowrap">';
		print $prospectstatic->LibProspStatut($obj->fk_stcomm,2);
		print "</td>";

		//$sts = array(-1,0,1,2,3);
		print '<td align="right" nowrap="nowrap">';
		foreach ($sts as $key => $value)
		{
			if ($value <> $obj->fk_stcomm)
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$obj->rowid.'&amp;pstcomm='.$value.'&amp;action=cstc&amp;'.$param.($page?'&amp;page='.$page:'').'">';
				print img_action(0,$value);
				print '</a>&nbsp;';
			}
		}
		print '</td>';

        print '<td align="right">';
		print $prospectstatic->getLibStatut(3);
        print '</td>';

        print "</tr>\n";
		$i++;
	}

	if ($num > $conf->liste_limit || $page > 0) print_barre_liste('', $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	print "</table>";

	print "</form>";

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter();
?>
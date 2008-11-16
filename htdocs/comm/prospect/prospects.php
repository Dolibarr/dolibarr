<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/comm/prospect/prospects.php
 *	\ingroup    prospect
 *	\brief      Page de la liste des prospects
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/prospect.class.php");

$langs->load("propal");
$langs->load("companies");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$socname=isset($_GET["socname"])?$_GET["socname"]:$_POST["socname"];
$stcomm=isset($_GET["stcomm"])?$_GET["stcomm"]:$_POST["stcomm"];

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

// Added by Matelli (see http://matelli.fr/showcases/patchs-dolibarr/enhance-prospect-searching.html)
// Load potentiels filters
$search_level_from = isset($_GET["search_level_from"])?$_GET["search_level_from"]:(isSet($_POST["search_level_from"])?$_POST["search_level_from"]:'');
$search_level_to = isset($_GET["search_level_to"])?$_GET["search_level_to"]:(isSet($_POST["search_level_to"])?$_POST["search_level_to"]:'');

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
dolibarr_syslog('prospects::prospects_prospect_level',LOG_DEBUG);
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
else dolibarr_print_error($db);

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
 * Affichage liste
 */

$sql = "SELECT s.rowid, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,";
$sql.= " st.libelle as stcomm, s.prefix_comm, s.fk_stcomm, s.fk_prospectlevel,";
$sql.= " d.nom as departement";
// Updated by Matelli (see http://matelli.fr/showcases/patchs-dolibarr/enhance-prospect-searching.html)
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= ", cs.fk_categorie, cs.fk_societe";
$sql .= " FROM ".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql .= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d on (d.rowid = s.fk_departement)";
$sql.= " WHERE s.fk_stcomm = st.id AND s.client = 2";
// Join for the needed table to filter by sale
if ($search_sale) $sql .= " AND s.rowid = sc.fk_soc";
// Join for the needed table to filter by categ
if ($search_categ) $sql .= " AND s.rowid = cs.fk_societe";

if (isset($stcomm) && $stcomm != '')
{
	$sql .= " AND s.fk_stcomm=".$stcomm;
}
if ($user->societe_id)
{
	$sql .= " AND s.rowid = " .$user->societe_id;
}

if ($_GET["search_nom"])   $sql .= " AND s.nom like '%".addslashes(strtolower($_GET["search_nom"]))."%'";
if ($_GET["search_ville"]) $sql .= " AND s.ville like '%".addslashes(strtolower($_GET["search_ville"]))."%'";
// Insert levels filters
if ($search_levels)
{
	$sql .= " AND s.fk_prospectlevel IN (".$search_levels.')';
}
// Insert salee filter
if ($search_sale)
{
	$sql .= " AND sc.fk_user = ".$search_sale;
}
// Insert categ filter
if ($search_categ)
{
	$sql .= " AND cs.fk_categorie = ".$search_categ;
}

if ($socname)
{
	$sql .= " AND s.nom like '%".addslashes(strtolower($socname))."%'";
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

$sql .= " ORDER BY $sortfield $sortorder, s.nom ASC";
$sql .= $db->plimit($conf->liste_limit+1, $offset);

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
		llxHeader();
	}

	$param='&amp;stcomm='.$stcomm.'&amp;search_nom='.urlencode($_GET["search_nom"]).'&amp;search_ville='.urlencode($_GET["search_ville"]);
 	// Added by Matelli (see http://matelli.fr/showcases/patchs-dolibarr/enhance-prospect-searching.html)
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
 	// Store the potentiels filters in the URL
 	if ($search_level_from != '')
 	{
 		$param.='&amp;search_level_from='.$search_level_from;
 	}
 	if ($search_level_to != '')
 	{
 		$param.='&amp;search_level_to='.$search_level_to;
 	}
 	// Store the categ filter in the URL
 	if ($search_categ != '')
 	{
 		$param.='&amp;search_categ='.$search_categ;
 	}
 	// Store the sale filter in the URL
 	if ($search_sale != '')
 	{
 		$param.='&amp;search_sale='.$search_sale;
 	}
 	// $param and $urladd should have the same value
 	$urladd = $param;
	
	print_barre_liste($langs->trans("ListOfProspects"), $page, $_SERVER["PHP_SELF"], $param, $sortfield,$sortorder,'',$num,$nbtotalofrecords);

 	
 	// Print the search-by-sale and search-by-categ filters
 	print '<form method="get" action="prospects.php" id="formulaire_recherche">';
 	
 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		// Select each sales and print them in a select input
 		print $langs->trans('SalesRepresentatives'). ': ';
 		print '<select class="flat" name="search_sale">';
 		print '<option value="">'.$langs->trans('All').'</option>';
 		
 		$sql_usr = "SELECT u.rowid, u.name, u.firstname, u.login";
 		$sql_usr .= " FROM ".MAIN_DB_PREFIX."user as u";
 		$sql_usr .= " ORDER BY u.name ASC ";
 	    
 		$resql_usr = $db->query($sql_usr);
 		if ($resql_usr)
 		{
 			while ($obj_usr = $db->fetch_object($resql_usr))
 			{			
 				print '<option value="'.$obj_usr->rowid.'"';
 				
 				if ($obj_usr->rowid == $search_sale)
 					print ' selected="true"';
 				
 				print '>';
 				print stripslashes($obj_usr->firstname)." ".stripslashes($obj_usr->name)." (".$obj_usr->login.')';
 				print '</option>';
 				$i++;
 			}
 			$db->free($resql_usr);
 		}
 		else
 		{
 			dolibarr_print_error($db);
 		}
 		print '</select> &nbsp;  &nbsp;  &nbsp; ';
 	}
 	
 	// Include Categorie class
	if ($conf->categorie->enabled)
	{
	 	require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");
	 
	 	// Load list of "categories"
	 	$static_categs = new Categorie($db);
	 	$tab_categs = $static_categs->get_full_arbo(2);
	 	
	 	// Print a select with each of them
	 	print $langs->trans('Categories'). ': ';
	 	print '<select class="flat" name="search_categ">';
	 	print '<option value="">'.$langs->trans('All').'</option>';
	 	
	 	if (is_array($tab_categs))
	 	{
	 		foreach ($tab_categs as $categ)
	 		{
	 			print '<option value="'.$categ['id'].'"';
	 			if ($categ['id'] == $search_categ)
	 				print ' selected="true"';
	 			print '>'.$categ['fulllabel'].'</option>';
	 		}
	 	}
	 	print '</select><br/>';
	}
		
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),"prospects.php","s.nom","",$param,"valign=\"center\"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),"prospects.php","s.ville","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("State"),"prospects.php","s.fk_departement","",$param,"align=\"center\"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),"prospects.php","s.datec","",$param,"align=\"center\"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ProspectLevelShort"),"prospects.php","s.fk_prospectlevel","",$param,"align=\"center\"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"prospects.php","s.fk_stcomm","",$param,"align=\"center\"",$sortfield,$sortorder);
	print '<td class="liste_titre" colspan="4">&nbsp;</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_nom" size="10" value="'.$_GET["search_nom"].'">';
	print '</td><td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ville" size="8" value="'.$_GET["search_ville"].'">';
	print '</td>';
 	print '<td class="liste_titre">';
    print '&nbsp;';
    print '</td>';
    print '<td class="liste_titre">';
    print '&nbsp;';
    print '</td>';
 	
 	// Added by Matelli (see http://matelli.fr/showcases/patchs-dolibarr/enhance-prospect-searching.html)	
 	print '<td class="liste_titre">';
 	// Generate in $options_from the list of each option sorted
 	$options_from = '<option value="">&nbsp;</option>';
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_from .= '<option value="'.$tab_level_sortorder.'"'.($search_level_from == $tab_level_sortorder ? ' selected="true"':'').'>';
 		$options_from .= $langs->trans($tab_level_label);
 		$options_from .= '</option>';			
 	}
 	
 	// Reverse the list
 	array_reverse($tab_level, true);
 	
 	// Generate in $options_to the list of each option sorted in the reversed order
 	$options_to = '<option value="">&nbsp;</option>';
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_to .= '<option value="'.$tab_level_sortorder.'"'.($search_level_to == $tab_level_sortorder ? ' selected="true"':'').'>';
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
 	
 	// Print the search button
    print '<td colspan="3" class="liste_titre" align="right">';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
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

		print "<tr $bc[$var]>";
		print '<td>';
		$prospectstatic->id=$obj->rowid;
		$prospectstatic->nom=$obj->nom;
		print $prospectstatic->getNomUrl(1);
		print '</td>';
		print "<td>".$obj->ville."&nbsp;</td>";
		print "<td align=\"center\">$obj->departement</td>";
		// Creation date
		print "<td align=\"center\">".dolibarr_print_date($obj->datec)."</td>";
		// Level
		print "<td align=\"center\">";
		print $prospectstatic->LibLevel($obj->fk_prospectlevel);
		print "</td>";
		// Statut
		print '<td align="center" nowrap="nowrap">';
		print $prospectstatic->LibStatut($obj->fk_stcomm,2);
		print "</td>";

		//$sts = array(-1,0,1,2,3);
		print '<td align="right" nowrap>';
		foreach ($sts as $key => $value)
		{
			if ($value <> $obj->fk_stcomm)
			{
				print '<a href="prospects.php?socid='.$obj->rowid.'&amp;pstcomm='.$value.'&amp;action=cstc&amp;'.$param.($page?'&amp;page='.$page:'').'">';
				print img_action(0,$value);
				print '</a>&nbsp;';
			}
		}
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
	dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>

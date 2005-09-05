<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/societe.php
        \ingroup    societe
		\brief      Page des societes
		\version    $Revision$
*/
 
require_once("./pre.inc.php");

if (!$user->rights->societe->lire)
  accessforbidden();

require_once("./contact.class.php");
//require_once("./cactioncomm.class.php");
//require_once("./actioncomm.class.php");

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");


/*
 * Sécurité accés client
 */
 
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


llxHeader();

$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_ville=isset($_GET["search_ville"])?$_GET["search_ville"]:$_POST["search_ville"];

$socname=isset($_GET["socname"])?$_GET["socname"]:$_POST["socname"];
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Recherche
 *
 */
$mode=isset($_GET["mode"])?$_GET["mode"]:$_POST["mode"];
$modesearch=isset($_GET["mode-search"])?$_GET["mode-search"]:$_POST["mode-search"];

if ($mode == 'search')
{
  $_POST["search_nom"]="$socname";

  $sql = "SELECT s.idp FROM ".MAIN_DB_PREFIX."societe as s ";
  $sql .= " WHERE s.nom like '%".$socname."%'";
  
  $result=$db->query($sql);
  if ($result)
    {
      if ($db->num_rows($result) == 1)
	{
	  $obj = $db->fetch_object($result);
	  $socid = $obj->idp;
	}
      $db->free($result);
    }

  /*
   * Sécurité accés client
   */
  if ($user->societe_id > 0) 
    {
      $action = '';
      $socid = $user->societe_id;
    }  
}

// As-t-on cliqué sur purge des criètres de recherche
if (isset($_POST["button_removefilter_x"]))
{
    $socname="";
    $search_nom="";
    $search_ville="";
}


/*
 * Mode Liste
 *
 */

$title=$langs->trans("ListOfCompanies");

$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur";
$sql .= ", s.siren";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."c_stcomm as st";
$sql .= " WHERE s.fk_stcomm = st.id";

if ($user->societe_id > 0)
{
  $sql .= " AND s.idp = " . $user->societe_id;
}

if ($socname)
{
  $search_nom=$socname;
}

if (strlen($stcomm)) {
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if ($search_nom) {
  $sql .= " AND (s.nom LIKE '%".$search_nom."%' OR s.code_client LIKE '%".$search_nom."%')";
}

if ($search_ville) {
  $sql .= " AND s.ville LIKE '%".$search_ville."%'";
}

if ($_POST["search_siren"]) {
  $sql .= " AND s.siren LIKE '%".$_POST["search_siren"]."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;

  $params = "&amp;socname=$socname";

  print_barre_liste($title, $page, "societe.php",$params,$sortfield,$sortorder,'',$num);
    
  // Lignes des titres
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Company"),"societe.php","s.nom", $params,"&search_nom=$search_nom&search_ville=$search_ville","",$sortfield);
  print_liste_field_titre($langs->trans("Town"),"societe.php","s.ville",$params,"&search_nom=$search_nom&search_ville=$search_ville",'',$sortfield);
  print_liste_field_titre($langs->trans("SIREN"),"societe.php","s.siren",$params,"&search_nom=$search_nom&search_ville=$search_ville",'',$sortfield);
  print '<td class="liste_titre" colspan="2" align="center">&nbsp;</td>';
  print "</tr>\n";

  // Lignes des champs de filtre
  print '<form method="post" action="societe.php" name="formfilter">';
  print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
  print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
  print '<tr class="liste_titre">';
  print '<td class="liste_titre" valign="right">';
  print '<input class="flat" type="text" name="search_nom" value="'.stripslashes($search_nom).'">';
  print '</td><td class="liste_titre" valign="right">';
  print '<input class="flat" type="text" name="search_ville" value="'.stripslashes($search_ville).'">';
  print '</td><td class="liste_titre" valign="right">';
  print '<input class="flat" size="10" type="text" name="search_siren" value="'.$_POST["search_siren"].'">';
  print '</td><td class="liste_titre" colspan="2" align="right">';
  print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
  print '&nbsp; ';
  print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" alt="'.$langs->trans("RemoveFilter").'">';
  print '</td>';
  print "</tr>\n";
  print '</form>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object();    
      $var=!$var;    
      print "<tr $bc[$var]><td>";
      print "<a href=\"soc.php?socid=$obj->idp\">";
      print img_object($langs->trans("ShowCompany"),"company");
      print "</a>&nbsp;<a href=\"soc.php?socid=$obj->idp\">".stripslashes($obj->nom)."</a></td>\n";
      print "<td>".$obj->ville."&nbsp;</td>\n";
      print "<td>".$obj->siren."&nbsp;</td>\n";
      print '<td align="center">';
      if ($obj->client==1)
	{
	  print "<a href=\"comm/fiche.php?socid=$obj->idp\">".$langs->trans("Customer")."</a>\n";
	}
      elseif ($obj->client==2)
	{
	  print "<a href=\"comm/prospect/fiche.php?id=$obj->idp\">".$langs->trans("Prospect")."</a>\n";
	}
      else
	{
	  print "&nbsp;";
	}
      print "</td><td align=\"center\">";
      if ($obj->fournisseur)
	{
	  print '<a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->idp.'">'.$langs->trans("Supplier").'</a>';
	}
      else
	{
	  print "&nbsp;";
	}
      
      print '</td></tr>'."\n";
      $i++;
    }

  print "</table>";
  $db->free();
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

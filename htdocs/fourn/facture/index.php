<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
        \file       htdocs/fourn/facture/index.php
        \ingroup    fournisseur,facture
		\brief      Lsite des factures fournisseurs
		\version    $Revision$
*/

require("./pre.inc.php");
require("../../contact.class.php");

llxHeader();

$socid = $_GET["socid"];

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $_GET["action"] = '';
  $socid = $user->societe_id;
}

if ($_GET["action"] == 'delete')
{
  $fac = new FactureFournisseur($db);
  $fac->delete($_GET["facid"]);
  
  $facid = 0 ;
}

$page=$_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];
 
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="fac.datef";


/*
 * Recherche
 *
 */
if ($_POST["mode"] == 'search')
{
  if ($_POST["mode-search"] == 'soc')
    {
      $sql = "SELECT s.idp FROM ".MAIN_DB_PREFIX."societe as s ";
      $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
    }
      
  if ( $db->query($sql) )
    {
      if ( $db->num_rows() == 1)
	{
	  $obj = $db->fetch_object();
	  $socid = $obj->idp;
	}
      $db->free();
    }
}
  
/*
 * Mode Liste
 *
 */
 
$sql = "SELECT s.idp as socid, s.nom, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea, s.prefix_comm, fac.total_ht, fac.total_ttc, fac.paye as paye, fac.fk_statut as fk_statut, fac.libelle, ".$db->pdate("fac.datef")." as datef, fac.rowid as facid, fac.facnumber";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as fac ";
$sql .= " WHERE fac.fk_soc = s.idp";

if ($socid)
{
  $sql .= " AND s.idp = $socid";
}
if ($_GET["filtre"])
  {
    $filtrearr = split(",", $_GET["filtre"]);
    foreach ($filtrearr as $fil)
      {
	$filt = split(":", $fil);
	$sql .= " AND " . $filt[0] . " = " . $filt[1];
      }
  }

if ($_GET["search_ref"])
  {
    $sql .= " AND fac.facnumber like '%".$_GET["search_ref"]."%'";
  }

if ($_GET["search_libelle"])
  {
    $sql .= " AND fac.libelle like '%".$_GET["search_libelle"]."%'";
  }

if ($_GET["search_societe"])
  {
    $sql .= " AND s.nom like '%".$_GET["search_societe"]."%'";
  }

if ($_GET["search_montant_ht"])
  {
    $sql .= " AND fac.total_ht = '".$_GET["search_montant_ht"]."'";
  }

if ($_GET["search_montant_ttc"])
  {
    $sql .= " AND fac.total_ttc = '".$_GET["search_montant_ttc"]."'";
  }

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit+1, $offset);

$result = $db->query($sql);

if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;
  
  if ($socid) {
      $soc = new Societe($db);
      $soc->fetch($socid);
  }
  
  print_barre_liste($langs->trans("BillsSuppliers").($socid?" $soc->nom":""),$page,"index.php","&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),"index.php","facnumber","&amp;socid=$socid","","",$sortfield);
  print_liste_field_titre($langs->trans("Date"),"index.php","fac.datef","&amp;socid=$socid","","",$sortfield);
  print_liste_field_titre($langs->trans("Label"),"index.php","fac.libelle","&amp;socid=$socid","","",$sortfield);
  print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","&amp;socid=$socid","","",$sortfield);
  print_liste_field_titre($langs->trans("AmountHT"),"index.php","fac.total_ht","&amp;socid=$socid","",'align="right"',$sortfield);
  print_liste_field_titre($langs->trans("AmountTTC"),"index.php","fac.total_ttc","&amp;socid=$socid","",'align="right"',$sortfield);
  print_liste_field_titre($langs->trans("Status"),"index.php","fk_statut,paye","&amp;socid=$socid","",'align="center"',$sortfield);
  print "</tr>\n";

  // Lignes des champs de filtre
  print '<form method="get" action="index.php">';
  print '<tr class="liste_titre">';
  print '<td valign="right">';
  print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET["search_ref"].'">';
  print '</td><td>&nbsp;</td>';
  print '<td align="left">';
  print '<input class="flat" type="text" name="search_libelle" value="'.$_GET["search_libelle"].'">';
  print '</td>';
  print '<td align="left">';
  print '<input class="flat" type="text" name="search_societe" value="'.$_GET["search_societe"].'">';
  print '</td><td align="right">';
  print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET["search_montant_ht"].'">';
  print '</td><td align="right">';
  print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_GET["search_montant_ttc"].'">';
  print '</td><td colspan="2" align="center">';
  print '<input type="submit" class="button" name="button_search" value="'.$langs->trans("Search").'">';
  print '</td>';
  print "</tr>\n";
  print '</form>';

  $fac = new FactureFournisseur($db);

  $var=true;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object();      
      $var=!$var;
      
      print "<tr $bc[$var]>";
      print "<td nowrap><a href=\"fiche.php?facid=$obj->facid\">".img_object($langs->trans("ShowBill"),"bill")." ".$obj->facnumber."</a></td>\n";
      print "<td nowrap>".strftime("%d %b %Y",$obj->datef)."</td>\n";
      print '<td>'.stripslashes("$obj->libelle").'</td>';
      print '<td>';
      print '<a href="../fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowSupplier"),"company").' '.$obj->nom.'</a</td>';
      print '<td align="right">'.price($obj->total_ht).'</td>';
      print '<td align="right">'.price($obj->total_ttc).'</td>';

      // Affiche statut de la facture
      if ($obj->paye)
	{
	  $class = "normal";
	}
      else
	{
	  if ($obj->fk_statut == 0)
	    {
	      $class = "normal";
	    }
	  else
	    {
	      $class = "impayee";
	    }
	}
	
	print '<td align="center">';
    if (! $obj->paye)
    {
      if ($obj->fk_statut == 0)
        {
      print $fac->PayedLibStatut($obj->paye,$obj->fk_statut);
        }
      elseif ($obj->fk_statut == 3)
        {
      print $fac->PayedLibStatut($obj->paye,$obj->fk_statut);
        }
      else
        {
      // \todo  le montant deja payé obj->am n'est pas définie
      print '<a class="'.$class.'" href=""index.php?filtre=paye:0,fk_statut:1">'.($fac->PayedLibStatut($obj->paye,$obj->fk_statut,$obj->am)).'</a>';
        }
    }
    else
    {
      print $fac->PayedLibStatut($obj->paye,$obj->fk_statut);
    }
    print '</td>';
      
    print "</tr>\n";
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

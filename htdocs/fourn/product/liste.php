<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/fourn/product/liste.php
   \ingroup    produit
   \brief      Page liste des produits ou services
   \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");

$user->getrights('produit');

if (!$user->rights->produit->lire) accessforbidden();

$sref=isset($_GET["sref"])?$_GET["sref"]:$_POST["sref"];
$snom=isset($_GET["snom"])?$_GET["snom"]:$_POST["snom"];

$type=isset($_GET["type"])?$_GET["type"]:$_POST["type"];

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = $_GET["page"];
if ($page < 0) { 
  $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
  
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="DESC";

if ($_POST["button_removefilter"] == $langs->trans("RemoveFilter")) {
    $sref="";
    $snom="";
}

if (strlen($_REQUEST['catid']) == 0)
{
  $_REQUEST['catid'] = 1;
}
$catid = $_REQUEST['catid'];

/*
 * Mode Liste
 *
 */

$title=$langs->trans("ProductsAndServices");

$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.fk_product_type";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";

if ($_GET["fourn_id"] > 0)
{
  $fourn_id = $_GET["fourn_id"];
  $sql .= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
}

if ($catid)
{
  $sql .= ", ".MAIN_DB_PREFIX."categorie_product as cp";
}

if ($_POST["mode"] == 'search')
{
  $sql .= " WHERE p.ref like '%".$_POST["sall"]."%'";
  $sql .= " OR p.label like '%".$_POST["sall"]."%'";
}
else
{
  $sql .= " WHERE 1=1";
  if (isset($_GET["type"]) || isset($_POST["type"]))
    {
      $sql .= " AND p.fk_product_type = ".(isset($_GET["type"])?$_GET["type"]:$_POST["type"]);
    }
  if ($sref)
    {
      $sql .= " AND p.ref like '%".$sref."%'";
    }
  if ($snom)
    {
      $sql .= " AND p.label like '%".$snom."%'";
    }
  if($catid)
    {
      $sql .= " AND cp.fk_product = p.rowid";
      $sql .= " AND cp.fk_categorie = ".$catid;
    }

}

if ($fourn_id > 0)
{
  $sql .= " AND p.rowid = pf.fk_product AND pf.fk_soc = $fourn_id";
}

$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit($limit + 1 ,$offset);
$result = $db->query($sql) ;

if ($result)
{
    $num = $db->num_rows();
    
    $i = 0;
  
    if ($num == 1 && (isset($_POST["sall"]) or $snom or $sref))
    {
        $objp = $db->fetch_object($i);
        Header("Location: fiche.php?id=$objp->rowid");
    }
    
    $texte = $langs->trans("List");

  llxHeader("","",$texte);

  if ($sref || $snom || $_POST["sall"] || $_POST["search"])
    {
      print_barre_liste($texte, $page, "liste.php", "&sref=".$sref."&snom=".$snom, $sortfield, $sortorder,'',$num);
    }
  else
    {
      print_barre_liste($texte, $page, "liste.php", "&sref=$sref&snom=$snom&fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num);
    }


/*
 *
 *
 */

  $c = new Categorie ($db, $catid);

  $ways = $c->print_all_ways(' &gt; ','fourn/product/liste.php');
  print "<div id='ways'>";

  print $ways[0]."<br />\n";

  print "</div>";
  
  $cats = $c->get_filles();
  
  print '<br><table class="noborder" width="100%">';
  print '<tr><td valign="top" width="10%">';
  if ($cats < 0)
    {
      print "-";
    }
  elseif (sizeof ($cats) > 0)
    {
      print "<table class='noborder' width='100%'>\n";
      foreach ($cats as $cat)
	{
	  $i++;
	  print "<tr ".$bc[$i%2].">\n";
	  print "<td><a href='liste.php?catid=".$cat->id."'>".$cat->label."</a></td>\n";
	  print "</tr>\n";
	}
      print "</table>\n";
    }
  
  print '</td><td valign="top" width="90%">';
  print '<table class="noborder" width="100%">';

  // Lignes des titres
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre($langs->trans("Ref"),"liste.php", "p.ref","&amp;envente=$envente".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","","",$sortfield);
  print_liste_field_titre($langs->trans("Label"),"liste.php", "p.label","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","","",$sortfield);
  print_liste_field_titre($langs->trans("SellingPrice"),"liste.php", "p.price","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="right"',$sortfield);
  print "</tr>\n";
  
  // Lignes des champs de filtre
  print '<form action="liste.php" method="post">';
  print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
  print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
  print '<input type="hidden" name="type" value="'.$type.'">';
  print '<tr class="liste_titre">';
  print '<td>';
  print '<input class="fat" type="text" name="sref" value="'.$sref.'">';
  print '</td>';
  print '<td valign="right">';
  print '<input class="fat" type="text" name="snom" value="'.$snom.'">';
  print '</td>';
  print '<td align="center">';
  print '<input type="submit" class="button" name="button_search" value="'.$langs->trans("Search").'">';
  print '&nbsp; <input type="submit" class="button" name="button_removefilter" value="'.$langs->trans("RemoveFilter").'">';
  print '</td>';
  print '</tr>';
  print '</form>';
  
  
  $var=True;
  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]><td>";
      print "<a href=\"../../product/fiche.php?id=$objp->rowid\">";
      if ($objp->fk_product_type) print img_object($langs->trans("ShowService"),"service");
      else print img_object($langs->trans("ShowProduct"),"product");
      print "</a> ";
      print "<a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></td>\n";
      print "<td>$objp->label</td>\n";
      print '<td align="right">'.price($objp->price).'</td>';
      print "</tr>\n";
      $i++;
    }
  $db->free();

  print "</table>";
  print '</td></tr></table>';

}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

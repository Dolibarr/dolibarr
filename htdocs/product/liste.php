<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");
$user->getrights('produit');

if (!$user->rights->produit->lire)
  accessforbidden();

if ($action == 'update')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."product SET description='$desc' where rowid = $rowid";
  $db->query($sql);
}

/*
 *
 *
 */

if ($page < 0) { 
  $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
  
if ($sortfield == "") {
  $sortfield="p.tms"; }
     
if ($sortorder == "")
{
  $sortorder="DESC";
}
  
$sql = "SELECT p.rowid, p.label, p.price, p.ref";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";

if ($_GET["fourn_id"] > 0)
{
  $fourn_id = $_GET["fourn_id"];
  $sql .= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
}

if ($_POST["sall"])
{
  $sql .= " WHERE lower(p.ref) like '%".strtolower($_POST["sall"])."%'";
  $sql .= " OR lower(p.label) like '%".strtolower($_POST["sall"])."%'";
}
else
{
  if (strlen($type) == 0)
    {
      $type = 0;
    }

  $sql .= " WHERE p.fk_product_type = $type";
  if ($_POST["sref"])
    {
      $sql .= " AND lower(p.ref) like '%".strtolower($_POST["sref"])."%'";
    }
  if ($_POST["snom"])
    {
      $sql .= " AND lower(p.label) like '%".strtolower($_POST["snom"])."%'";
    }
  if (isset($_GET["envente"]) && strlen($_GET["envente"]) > 0)
    {
      $sql .= " AND p.envente = ".$_GET["envente"];
    }
  else
    {
      $sql .= " AND p.envente = 1";
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
  
  if ($num == 1 && (isset($_POST["sall"]) or isset($_POST["snom"]) or isset($_POST["sref"])))
    {
      $objp = $db->fetch_object($i);
      Header("Location: fiche.php?id=$objp->rowid");
    }
  
  if ($_POST["sref"] || $_POST["snom"] || $_POST["sall"])
    {
      llxHeader("","","Recherche Produit/Service");

      print_barre_liste("Recherche d'un produit ou service", $page, "liste.php", "&sref=$sref&snom=$snom&amp;envente=$envente", $sortfield, $sortorder,'',$num);
    }
  else
    {
      $texte = "Liste des ".$types[$type]."s";
      llxHeader("","",$texte);
      if (isset($envente) && $envente == 0)
	{
	  $texte .= " hors vente";
	}
      else
	{
	  $envente=1;
	}
       
      print_barre_liste($texte, $page, "liste.php", "&sref=$sref&snom=$snom&fourn_id=$fourn_id&amp;type=$type", $sortfield, $sortorder,'',$num);
    }

  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

  print "<TR class=\"liste_titre\"><td>";
  print_liste_field_titre("Réf","liste.php", "p.ref","&amp;envente=$envente&amp;type=$type&fourn_id=$fourn_id");
  print "</td><td>";
  print_liste_field_titre("Libellé","liste.php", "p.label","&envente=$envente&type=$type&fourn_id=$fourn_id");
  print "</td><TD align=\"right\">";
  print_liste_field_titre("Prix de vente","liste.php", "p.price","&envente=$envente&type=$type&fourn_id=$fourn_id");
  print "</td></tr>\n";
  
  print '<tr class="liste_titre">';
  print '<form action="liste.php?type='.$type.'" method="post">';
  print '<td><input class="flat" type="text" size="10" name="sref">&nbsp;<input class="flat" type="submit" value="go"></td>';
  print '</form><form action="liste.php" method="post">';
  print '<td><input class="flat" type="text" size="20" name="snom">&nbsp;<input class="flat" type="submit" value="go"></td>';
  print '</form><td>&nbsp;</td></tr>';
  
  
  $var=True;
  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]><td>";
      print "<a href=\"fiche.php?id=$objp->rowid\">";
      print img_file();
      print "</a>&nbsp;";
      print "<a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></TD>\n";
      print "<TD>$objp->label</TD>\n";
      print '<TD align="right">'.price($objp->price).'</TD>';
      print "</TR>\n";
      $i++;
    }
  $db->free();

  print "</table>";

}
else
{
  print $db->error() . "<br>" .$sql;
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

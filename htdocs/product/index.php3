<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");
$user->getrights('produit');

if (!$user->rights->produit->lire)
  accessforbidden();


if (strlen($type) == 0)
{
  $type = 0;
}

if ($action == 'update')
{
  $sql = "UPDATE llx_product SET description='$desc' where rowid = $rowid";
  $db->query($sql);
}

/*
 *
 *
 */


  if ($page == -1) { 
    $page = 0 ; 
  }

  $limit = $conf->liste_limit;
  $offset = $limit * $page ;
  
  if ($sortfield == "")
    {
      $sortfield="p.tms";
    }
  if ($sortorder == "")
    {
      $sortorder="DESC";
    }
  
  $sql = "SELECT p.rowid, p.label, p.price, p.ref FROM llx_product as p";
  $sql .= " WHERE p.fk_product_type = $type";
  if ($sref)
    {
      $sql .= " AND lower(p.ref) like '%".strtolower($sref)."%'";
    }
  if ($snom)
    {
      $sql .= " AND lower(p.label) like '%".strtolower($snom)."%'";
    }

  $sql .= " ORDER BY $sortfield $sortorder ";
  $sql .= $db->plimit($limit + 1 ,$offset);
  $result = $db->query($sql) ;

if ($result)
{
  $num = $db->num_rows();

  $i = 0;
  
  if ($num == 1)
    {
      $objp = $db->fetch_object($i);
      Header("Location: fiche.php3?id=$objp->rowid");
    }
  
  llxHeader();

  print_barre_liste("Liste des ".$types[$type]."s", $page, $PHP_SELF, "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

  print "<TR class=\"liste_titre\"><td>";
  print_liste_field_titre("Réf",$PHP_SELF, "p.ref");
  print "</td><td>";
  print_liste_field_titre("Libellé",$PHP_SELF, "p.label");
  print "</td><TD align=\"right\">Prix de vente</TD>";
  print "</TR>\n";
  
  print '<tr class="liste_titre">';
  print '<form action="index.php3?type='.$type.'" method="post">';
  print '<td><input class="flat" type="text" size="10" name="sref">&nbsp;<input class="flat" type="submit" value="go"></td>';
  print '</form><form action="index.php3" method="post">';
  print '<td><input class="flat" type="text" size="20" name="snom">&nbsp;<input class="flat" type="submit" value="go"></td>';
  print '</form><td>&nbsp;</td></tr>';
  
  
  $var=True;
  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php3?id=$objp->rowid\">$objp->ref</a></TD>\n";
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

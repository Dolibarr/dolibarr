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
require("./pre.inc.php");

llxHeader();

if ($sortorder == "") {  $sortorder="DESC"; }
if ($sortfield == "") {  $sortfield="d.datedon"; }

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


$sql = "SELECT d.rowid, ".$db->pdate("d.datedon")." as datedon, d.prenom, d.nom, d.societe, d.amount, p.libelle as projet";
$sql .= " FROM ".MAIN_DB_PREFIX."don as d, ".MAIN_DB_PREFIX."don_projet as p";
$sql .= " WHERE p.rowid = d.fk_don_projet";
if (strlen($statut))
{
  $sql .= " AND d.fk_statut = $statut";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  if (strlen($statut))
    {
      print_barre_liste($libelle[$statut], $page, "liste.php", "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
    }
  else 
    {
      print_barre_liste("Dons", $page, "liste.php", "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
    }
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

  print '<TR class="liste_titre">';
  print "<td>";
  print_liste_field_titre("Prénom","liste.php","d.prenom","&page=$page&statut=$statut");
  print "</td><td>";
  print_liste_field_titre("Nom","liste.php","d.nom","&page=$page&statut=$statut");
  print "</td><td>";
  print_liste_field_titre("Société","liste.php","d.societe","&page=$page&statut=$statut");
  print "</td><td>";
  print_liste_field_titre("Date","liste.php","d.datedon","&page=$page&statut=$statut");
  print "</td><td>Projet</td>";
  print "<td align=\"right\">";
  print_liste_field_titre("Montant","liste.php","d.amount","&page=$page&statut=$statut");
  print '</td><td>&nbsp;</td>';
  print "</TR>\n";
    
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->prenom)."</a></TD>\n";
      print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->nom)."</a></TD>\n";
      print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->societe)."</a></TD>\n";
      print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".strftime("%d %B %Y",$objp->datedon)."</a></td>\n";
      print "<TD>$objp->projet</TD>\n";
      print '<TD align="right">'.price($objp->amount).'</TD><td>&nbsp;</td>';

      print "</tr>";
      $i++;
    }
  print "</table>";
}
else
{
  print $sql;
  print $db->error();
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

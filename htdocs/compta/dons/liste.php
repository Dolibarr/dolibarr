<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$db = new Db();

if ($action == 'add') {
  $datepaye = $db->idate(mktime(12, 0 , 0, $pmonth, $pday, $pyear));

  $paiement = new Paiement($db);

  $paiement->facid        = $facid;  
  $paiement->datepaye     = $datepaye;
  $paiement->amount       = $amount;
  $paiement->author       = $author;
  $paiement->paiementid   = $paiementid;
  $paiement->num_paiement = $num_paiement;
  $paiement->note         = $note;

  $paiement->create();

  $action = '';

}


if ($sortorder == "") {  $sortorder="DESC"; }
if ($sortfield == "") {  $sortfield="d.datedon"; }

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


$sql = "SELECT d.rowid, ".$db->pdate("d.datedon")." as datedon, d.nom, d.amount, p.libelle as projet";
$sql .= " FROM llx_don as d, llx_don_projet as p";
$sql .= " WHERE p.rowid = d.fk_don_projet AND d.fk_statut = $statut";
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Dons", $page, $PHP_SELF, "&statut=$statut");
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

  print '<TR class="liste_titre">';
  print "<td>Nom</td>";
  print "<td>Date</td>";
  print "<td>Projet</td>";
  print "<td align=\"right\">Montant</TD>";
  print '<td>&nbsp;</td>';
  print "</TR>\n";
    
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->nom)."</a></TD>\n";
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

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
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 * Affichage
 */

llxHeader();


if ($page == -1)
  $page = 0 ;
  
$limit = $conf->liste_limit;
$offset = $limit * $page ;

if ($sortorder == "")
  $sortorder="DESC";

if ($sortfield == "")
  $sortfield="p.rowid";
  
$sql = "SELECT p.rowid,".$db->pdate("p.datep")." as dp, p.amount";
$sql .=", c.libelle as paiement_type, p.num_paiement";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as c";
$sql .= " WHERE p.fk_paiement = c.id";

$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit( $limit +1 ,$offset);
$result = $db->query($sql);

if ($result)
{
  $num = $db->num_rows();
  $i = 0; 
  $var=True;
  
  print_barre_liste("Paiements reçus", $page, "liste.php","",$sortfield,$sortorder,'',$num);
  
  print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Date</td><td>';
  print_liste_field_titre("Type","liste.php","c.libelle","","");
  print '</td><td align="right">Montant</TD>';
  print "<td>&nbsp;</td>";
  print "</TR>\n";
  
  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print '<td><a href="fiche.php?id='.$objp->rowid.'">';
      print img_file();
      print "</a>&nbsp;".strftime("%d %B %Y",$objp->dp)."</TD>\n";
      print "<td>$objp->paiement_type $objp->num_paiement</TD>\n";
      print '<td align="right">'.price($objp->amount).'</TD><td>&nbsp;</td>';	
      print "</tr>";
      $i++;
    }
  print "</table>";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
if ($user->societe_id > 0) accessforbidden();

llxHeader('','Statistiques prélèvements');

print_titre("Statistiques prélèvements");


$sql = "SELECT sum(f.total_ttc)";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture as pf";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE pf.fk_facture = f.rowid";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  if ( $num >0 )
    {
      $row = $db->fetch_row();	
      $total = $row[0];
    }
}

/*
 * Stats
 *
 */
$sql = "SELECT sum(f.total_ttc), pf.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture as pf";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE pf.fk_facture = f.rowid";
$sql .= " GROUP BY pf.statut";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Statut</td><td align="right">Montant</td><td align="right">%</td></tr>';
  
  $var=True;

  $st[0] = "En attente";
  $st[1] = "Crédité";
  $st[2] = "Rejeté";
  
  while ($i < $num)
    {
      $row = $db->fetch_row();	
      
      print "<tr $bc[$var]><td>";
      print $st[$row[1]];            
      print '</td><td align="right">';	  
      print price($row[0]);	  
      
      print '</td><td align="right">';	  
      print round($row[0]/$total*100,2)." %";	  
      print '</td>';
      
      print "</tr>\n";
      
      $var=!$var;
      $i++;
    }

      print "<tr $bc[$var]>".'<td align="right">Total';
      print '</td><td align="right">';	  
      print price($total);	        
      print '</td><td align="right">&nbsp;';
      print '</td>';
      
      print "</tr>\n";

  
  print "</table>";




  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}  


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

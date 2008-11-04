<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/prelevement/stats.php
        \brief      Page de stats des prélèvements
        \version    $Id$
*/

require("./pre.inc.php");

if (!$user->rights->prelevement->bons->lire)
  accessforbidden();

// Sécurité accés client
if ($user->societe_id > 0) accessforbidden();


llxHeader('','Statistiques prélèvements');

/*
 *
 * Stats générales
 *
 */

print_titre($langs->trans("WithdrawStatistics"));


$sql = "SELECT sum(pl.amount), count(pl.amount)";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  if ( $num >0 )
    {
      $row = $db->fetch_row();	
      $total = $row[0];
      $nbtotal = $row[1];
    }
}

/*
 * Stats
 *
 */
$sql = "SELECT sum(pl.amount), count(pl.amount), pl.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " GROUP BY pl.statut";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td width="30%">'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Number").'</td><td align="right">%</td>';
  print '<td align="right">'.$langs->trans("Amount").'</td><td align="right">%</td></tr>';
  
  $var=True;

  $st[0] = "En attente";
  $st[1] = "En attente";
  $st[2] = "Crédité";
  $st[3] = "Rejeté";
  
  while ($i < $num)
    {
      $row = $db->fetch_row();	
      
      print "<tr $bc[$var]><td>";

      print $st[$row[2]];            
      print '</td><td align="center">';
      print $row[1];            

      print '</td><td align="right">';	  
      print round($row[1]/$nbtotal*100,2)." %";

      print '</td><td align="right">';

      print price($row[0]);	  
      
      print '</td><td align="right">';	  
      print round($row[0]/$total*100,2)." %";	  
      print '</td></tr>';
      
      $var=!$var;
      $i++;
    }

  print '<tr class="liste_total"><td align="right">'.$langs->trans("Total").'</td>';
  print '<td align="center">'.$nbtotal.'</td><td>&nbsp;</td><td align="right">';	  
  print price($total);	        
  print '</td><td align="right">&nbsp;</td>';
  print "</tr></table>";
  $db->free();
}
else 
{
  dolibarr_print_error($db);
} 


/*
 *
 * Stats sur les rejets
 *
 */
print '<br />';
print_titre($langs->trans("WithdrawRejectStatistics"));


$sql = "SELECT sum(pl.amount), count(pl.amount)";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " WHERE pl.statut = 3";
if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  if ( $num > 0 )
    {
      $row = $db->fetch_row();	
      $total = $row[0];
      $nbtotal = $row[1];
    }
}

/*
 * Stats sur les rejets
 *
 */
$sql = "SELECT sum(pl.amount), count(pl.amount) as cc, pr.motif";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql .= " WHERE pl.statut = 3";
$sql .= " AND pr.fk_prelevement_lignes = pl.rowid";
$sql .= " GROUP BY pr.motif";
$sql .= " ORDER BY cc DESC";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td width="30%">'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Number").'</td>';
  print '<td align="right">%</td><td align="right">'.$langs->trans("Amount").'</td><td align="right">%</td></tr>';
  
  $var=True;

  require_once DOL_DOCUMENT_ROOT."/compta/prelevement/rejet-prelevement.class.php";
  $Rejet = new RejetPrelevement($db, $user);
  
  while ($i < $num)
    {
      $row = $db->fetch_row();	
      
      print "<tr $bc[$var]><td>";
      print $Rejet->motifs[$row[2]]; 

      print '</td><td align="center">'.$row[1];

      print '</td><td align="right">';	  
      print round($row[1]/$nbtotal*100,2)." %";

      print '</td><td align="right">';	  
      print price($row[0]);

      print '</td><td align="right">';	  
      print round($row[0]/$total*100,2)." %";


      print '</td></tr>';
      
      $var=!$var;
      $i++;
    }

  print '<tr class="liste_total"><td align="right">'.$langs->trans("Total").'</td><td align="center">'.$nbtotal.'</td>';
  print '<td>&nbsp;</td><td align="right">';	  
  print price($total);	        
  print '</td><td align="right">&nbsp;</td>';
  print "</tr></table>";
  $db->free();
}
else 
{
  dolibarr_print_error($db);
}  


$db->close();

llxFooter('$Date$ - $Revision$');
?>

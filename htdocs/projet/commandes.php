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
require("../propal.class.php");
require("../facture.class.php");
require("../commande/commande.class.php");

llxHeader("","../");

$projet = new Project($db);
$projet->fetch($_GET["id"]);

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$projet->id;
$head[$h][1] = 'Fiche projet';

$head[$h+1][0] = DOL_URL_ROOT.'/projet/propal.php?id='.$projet->id;
$head[$h+1][1] = 'Prop. Commerciales';

$head[$h+2][0] = DOL_URL_ROOT.'/projet/commandes.php?id='.$projet->id;
$head[$h+2][1] = 'Commandes';

$head[$h+3][0] = DOL_URL_ROOT.'/projet/facture.php?id='.$projet->id;
$head[$h+3][1] = 'Factures';

dolibarr_fiche_head($head, 2);
/*
 *
 *
 *
 */
$projet->societe->fetch($projet->societe->id);

print '<table class="border" border="1" cellpadding="4" cellspacing="0" width="100%">';
print '<tr><td width="20%">Titre</td><td>'.$projet->title.'</td>';  
print '<td width="20%">Réf</td><td>'.$projet->ref.'</td></tr>';
print '<tr><td>Société</td><td colspan="3"><a href="../comm/fiche.php?socid='.$projet->societe->id.'">'.$projet->societe->nom.'</a></td></tr>';
print '</table><br>';

/*
 * Commandes
 *
 */
$commandes = $projet->get_commande_list();
$total = 0 ;
if (sizeof($commandes)>0 && is_array($commandes))
{
  print_titre('Listes des commandes associées au projet');
  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
  
  print '<TR class="liste_titre">';
  print '<td width="15%">Réf</td><td width="25%">Date</td><td align="right">Montant</td><td>&nbsp;</td></tr>';
  
  for ($i = 0; $i<sizeof($commandes);$i++)
    {
      $commande = new Commande($db);
      $commande->fetch($commandes[$i]);
      
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<td><a href=\"../commande/fiche.php?id=$commande->id\">$commande->ref</a></TD>\n";	      
      print '<td>'.strftime("%d %B %Y",$commande->date).'</td>';	      
      print '<TD align="right">'.price($commande->total_ht).'</td><td>&nbsp;</td></tr>';
      
      $total = $total + $commande->total_ht;
    }
  
  print '<tr><td>'.$i.' commandes</td><td>&nbsp;</td>';
  print '<td align="right">Total : '.price($total).'</td>';
  print '<td align="right">'.MAIN_MONNAIE.' HT</td></tr>';
  print "</table>";
}    

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

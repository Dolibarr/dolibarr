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

dolibarr_fiche_head($head, 1);
/*
 *
 *
 *
 */
$propales = array();

$projet->societe->fetch($projet->societe->id);

print '<table class="border" border="1" cellpadding="4" cellspacing="0" width="100%">';
print '<tr><td width="20%">Titre</td><td>'.$projet->title.'</td>';  
print '<td width="20%">Réf</td><td>'.$projet->ref.'</td></tr>';
print '<tr><td>Société</td><td colspan="3"><a href="../comm/fiche.php?socid='.$projet->societe->id.'">'.$projet->societe->nom.'</a></td></tr>';
print '</table><br>';

$propales = $projet->get_propal_list();

if (sizeof($propales)>0 && is_array($propales))
{
  print_titre('Listes des propositions commerciales associées au projet');
  print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';
  
  print '<TR class="liste_titre">';
  print '<td width="15%">Réf</td><td width="25%">Date</td><td align="right">Montant</td><td>&nbsp;</td></tr>';
  
  for ($i = 0; $i<sizeof($propales);$i++)
    {
      $propale = new Propal($db);
      $propale->fetch($propales[$i]);
      
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"../comm/propal.php?propalid=$propale->id\">$propale->ref</a></td>\n";
      
      print '<td>'.strftime("%d %B %Y",$propale->datep).'</td>';
      
      print '<td align="right">'.price($propale->price).'</td><td>&nbsp;</td></tr>';
      $total = $total + $propale->price;
    }
  
  print '<tr><td>'.$i.' propales</td><td>&nbsp;</td>';
  print '<td align="right">Total : '.price($total).'</td>';
  print '<td align="left">'.MAIN_MONNAIE.' HT</td></tr></table>';
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

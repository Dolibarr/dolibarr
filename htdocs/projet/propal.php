<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**     \file       htdocs/projet/propal.php
        \ingroup    projet propale
		\brief      Page des propositions commerciales par projet
		\version    $Revision$
*/

require("./pre.inc.php");
require("../propal.class.php");
require("../facture.class.php");
require("../commande/commande.class.php");

$langs->load("projects");
$langs->load("companies");
$langs->load("propal");


llxHeader("","../");

$projet = new Project($db);
$projet->fetch($_GET["id"]);

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$projet->id;
$head[$h][1] = $langs->trans("Project");
$h++;

if ($conf->propal->enabled) {
  $langs->load("propal");
  $head[$h][0] = DOL_URL_ROOT.'/projet/propal.php?id='.$projet->id;
  $head[$h][1] = $langs->trans("Proposals");
  $hselected=$h;
  $h++;
}  

if ($conf->commande->enabled) {
  $langs->load("orders");
  $head[$h][0] = DOL_URL_ROOT.'/projet/commandes.php?id='.$projet->id;
  $head[$h][1] = $langs->trans("Orders");
  $h++;
}

if ($conf->facture->enabled) {
  $langs->load("bills");
  $head[$h][0] = DOL_URL_ROOT.'/projet/facture.php?id='.$projet->id;
  $head[$h][1] = $langs->trans("Bills");
  $h++;
}
 
dolibarr_fiche_head($head, $hselected, $langs->trans("Project").": ".$projet->ref);


$propales = array();

$projet->societe->fetch($projet->societe->id);

print '<table class="border" width="100%">';
print '<tr><td>'.$langs->trans("Company").'</td><td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$projet->societe->id.'">'.$projet->societe->nom.'</a></td></tr>';
print '<tr><td>'.$langs->trans("Ref").'</td><td>'.$projet->ref.'</td></tr>';
print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projet->title.'</td></tr>';      
print '</table><br>';

$propales = $projet->get_propal_list();

if (sizeof($propales)>0 && is_array($propales))
{
  print_titre('Listes des propositions commerciales associées au projet');
  print '<table class="noborder" width="100%">';
  
  print '<tr class="liste_titre">';
  print '<td width="15%">'.$langs->trans("Ref").'</td><td width="25%">'.$langs->trans("Date").'</td><td align="right">'.$langs->trans("Amount").'</td><td>&nbsp;</td></tr>';
  
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
  
  print '<tr><td colspan="2">'.$i.' '.$langs->trans("Proposal").'</td>';
  print '<td align="right">'.$langs->trans("TotalHT").': '.price($total).'</td>';
  print '<td align="left">'.$conf->monnaie.'</td></tr></table>';
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./bank.lib.php");
require("../../tva.class.php");
require("../../chargesociales.class.php");

$user->getrights('compta');
$user->getrights('banque');

if (!$user->rights->banque->lire)
  accessforbidden();

llxHeader();

print_titre ("Comptes bancaires");
print '<br>';

$sql = "SELECT rowid, label,number,bank FROM ".MAIN_DB_PREFIX."bank_account";

$result = $db->query($sql);
if ($result)
{
  $accounts = array();

  $num = $db->num_rows();
  $i = 0; 
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $accounts[$i] = $objp->rowid;
    
    $i++;
  }
  $db->free();
}

/*
 * Comptes
 */
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="2">';
print '<tr class="liste_titre"><td>Comptes courants</td><td>Banque</td>';
print '<td align="left">Numéro</td><td align="right">Solde</td><td align="center">Clos</td>';
print "</tr>\n";
$total = 0;
$var=True;
for ($i = 0 ; $i < sizeof($accounts) ; $i++)
{
  $var = !$var;
  $acc = new Account($db);
  $acc->fetch($accounts[$i]);
  if ($acc->courant)
    {
      $solde = $acc->solde();
  
      print "<tr ".$bc[$var]."><td>";
      print '<a href="account.php?account='.$acc->id.'">'.$acc->label.'</a>';
    
      print "</td><td>$acc->bank</td><td>$acc->number</td>";
    
      print '<td align="right">'.price($solde).'</td><td align="center">'.$yn[$acc->clos].'</td></tr>';
  
      $total += $solde;
    }
}

$var = !$var;
print "<tr>".'<td colspan="3" align="right"><b>Total</b></td><td align="right"><b>'.price($total).'</b></td><td>&nbsp;</td></tr>';


print '<tr class="liste_titre"><td colspan="5">Dettes</td></tr>';

/*
 * TVA
 */
if ($conf->compta->tva)
{
  $var = !$var;
  $tva = new Tva($db);

  $tva_solde = $tva->solde();

  $total = $total + $tva_solde;

  print "<tr ".$bc[$var].">".'<td colspan="3">TVA</td><td align="right">'.price($tva_solde).'</td><td>&nbsp;</td></tr>';
}

/*
 * Charges sociales
 */
$var = !$var;
$chs = new ChargeSociales($db);

$chs_a_payer = $chs->solde();

$total = $total - $chs_a_payer;

print "<tr ".$bc[$var].">".'<td colspan="3">URSSAF</td><td align="right">'.price($chs_a_payer).'</td><td>&nbsp;</td></tr>';


/*
 * Total
 */
print "<tr>".'<td colspan="3" align="right"><b>Total</b></td><td align="right"><b>'.price($total).'</b></td><td>&nbsp;</td></tr>';


/*
 * Comptes placements
 */
print '<tr class="liste_titre"><td colspan="5">Comptes placements</td></tr>';

for ($i = 0 ; $i < sizeof($accounts) ; $i++) {
  $var = !$var;
  $acc = new Account($db);
  $acc->fetch($accounts[$i]);

  if (!$acc->courant) {

    $solde = $acc->solde();
  
    print "<tr ".$bc[$var]."><td>";
    print '<a href="account.php?account='.$acc->id.'">'.$acc->label.'</a>';
    
    print "</td><td>$acc->bank</td><td>$acc->number</td>";
    
    print '<td align="right">'.price($solde).'</td><td>&nbsp;</td></tr>';
    
    $total += $solde;
  }
}

print "</table>";


/*
 * Boutons d'actions
 */
print "<br><div class=\"tabsAction\">\n";
if ($user->rights->banque->configurer) {
	print '<a class="tabAction" href="fiche.php?action=create">Nouveau compte</a>';
	print '<a class="tabAction" href="categ.php">Catégories</a>';
}
print "</div>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

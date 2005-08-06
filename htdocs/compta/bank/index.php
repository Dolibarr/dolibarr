<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
   \file       htdocs/compta/bank/index.php
   \ingroup    banque
   \brief      Page accueil banque
   \version    $Revision$
*/


require("./pre.inc.php");

require("./bank.lib.php");
require("../../tva.class.php");
require("../../chargesociales.class.php");

$langs->load("banks");

$user->getrights('compta');
$user->getrights('banque');

if (!$user->rights->banque->lire)
  accessforbidden();

llxHeader();

print_titre($langs->trans("AccountsArea"));
print '<br>';


// On charge tableau des comptes financiers ouverts
// On n'affiche pas les comptes clos
$accounts = array();

$sql  = "SELECT rowid, courant";
$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
$sql .= " WHERE clos = 0";
$sql .= " ORDER BY label";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0; 
  while ($i < $num)
    {
      $objp = $db->fetch_object($resql);
      $accounts[$objp->rowid] = $objp->courant;
      $i++;
    }
  $db->free($resql);
}


/*
 * Comptes courants
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="30%">'.$langs->trans("CurrentAccounts").'</td>';
print '<td width="30%">'.$langs->trans("Bank").'</td>';
print '<td width="20%" align="left">'.$langs->trans("Numero").'</td><td align="right" width="120">'.$langs->trans("BankBalance").'</td><td align="center" width="80">'.$langs->trans("Closed").'</td>';
print "</tr>\n";

$total = 0;
$var=true;
foreach ($accounts as $key=>$type)
{
  if ($type == 1)
    {
      $acc = new Account($db);
      $acc->fetch($key);
      
      $var = !$var;
      $solde = $acc->solde();
      
      print '<tr '.$bc[$var].'><td width="30%">';
      print '<a href="account.php?account='.$acc->id.'">'.$acc->label.'</a>';
      print '</td><td width="30%">'.$acc->bank."</td><td>$acc->number</td>";
      print '<td align="right">'.price($solde).'</td><td align="center">'.$yn[$acc->clos].'</td></tr>';
      
      $total += $solde;
    }
}

// Total
print '<tr class="liste_total"><td colspan="3" align="right"><b>'.$langs->trans("Total").'</b></td><td align="right"><b>'.price($total).'</b></td><td>&nbsp;</td></tr>';


print '<tr><td colspan="5">&nbsp;</td></tr>';


/*
 * Comptes placements
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="30%">'.$langs->trans("SavingAccounts").'</td><td width="30%">'.$langs->trans("Bank").'</td>';
print '<td width="20%" align="left">'.$langs->trans("Numero").'</td><td align="right" width="120">'.$langs->trans("BankBalance").'</td><td align="center" width="80">'.$langs->trans("Closed").'</td>';
print "</tr>\n";

$total = 0;
$var=true;
foreach ($accounts as $key=>$type)
{
  if ($type == 0)
    {
      $acc = new Account($db);
      $acc->fetch($key);
      
      $var = !$var;
      $solde = $acc->solde();
  
      print "<tr ".$bc[$var]."><td>";
      print '<a href="account.php?account='.$acc->id.'">'.$acc->label.'</a>';
      print "</td><td>$acc->bank</td><td>$acc->number</td>";
      print '<td align="right">'.price($solde).'</td><td align="center">'.$yn[$acc->clos].'</td></tr>';
  
      $total += $solde;
    }
}

// Total
print '<tr class="liste_total"><td colspan="3" align="right"><b>'.$langs->trans("Total").'</b></td><td align="right"><b>'.price($total).'</b></td><td>&nbsp;</td></tr>';


print '<tr><td colspan="5">&nbsp;</td></tr>';


/*
 * Comptes caisse/liquide
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("CashAccounts").'</td><td>&nbsp;</td>';
print '<td align="left">&nbsp;</td><td align="right" width="120">'.$langs->trans("BankBalance").'</td><td align="center" width="80">'.$langs->trans("Closed").'</td>';
print "</tr>\n";

$total = 0;
$var=true;
foreach ($accounts as $key=>$type)
{
    if ($type == 2)
    {
        $acc = new Account($db);
        $acc->fetch($key);

        $var = !$var;
        $solde = $acc->solde();

        print "<tr ".$bc[$var]."><td>";
        print '<a href="account.php?account='.$acc->id.'">'.$acc->label.'</a>';
        print "</td><td>$acc->bank</td><td>&nbsp;</td>";
        print '<td align="right">'.price($solde).'</td><td align="center">'.$yn[$acc->clos].'</td></tr>';

        $total += $solde;
    }
}

// Total
print '<tr class="liste_total"><td colspan="3" align="right"><b>'.$langs->trans("Total").'</b></td><td align="right"><b>'.price($total).'</b></td><td>&nbsp;</td></tr>';


/*
 * Dettes
 */
print '<tr><td colspan="5">&nbsp;</td></tr>';
print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Debts").'</td></tr>';

// TVA
if ($conf->compta->tva)
{
    $var=true;
    $var = !$var;
    $tva = new Tva($db);
    
    $tva_solde = $tva->solde();
    
    $total = $total + $tva_solde;
    
    print "<tr ".$bc[$var].">".'<td colspan="3">'.$langs->trans("VAT").'</td><td align="right">'.price($tva_solde).'</td><td>&nbsp;</td></tr>';
}


// Charges sociales
$var = !$var;
$chs = new ChargeSociales($db);

$chs_a_payer = $chs->solde();

$total = $total - $chs_a_payer;

print "<tr ".$bc[$var].">".'<td colspan="3">'.$langs->trans("SocialContributions").'</td><td align="right">'.price($chs_a_payer).'</td><td>&nbsp;</td></tr>';

// Total
print '<tr class="liste_total"><td colspan="3" align="right"><b>'.$langs->trans("Total").'</b></td><td align="right"><b>'.price($total).'</b></td><td>&nbsp;</td></tr>';


print "</table>";


/*
 * Boutons d'actions
 */
print "<br><div class=\"tabsAction\">\n";
if ($user->rights->banque->configurer) {
	print '<a class="tabAction" href="fiche.php?action=create">'.$langs->trans("NewFinancialAccount").'</a>';
	print '<a class="tabAction" href="categ.php">'.$langs->trans("Categories").'</a>';
}
print "</div>";


$db->close();

llxFooter('$Date$ - $Revision$');
?>

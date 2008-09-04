<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/bank/index.php
        \ingroup    banque
        \brief      Page accueil banque
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/chargesociales.class.php");

$langs->load("banks");

if (!$user->rights->banque->lire)
  accessforbidden();

$statut=isset($_GET["statut"])?$_GET["statut"]:'';



/*
 * View
 */

llxHeader();


$link='';
if ($statut == '') $link='<a href="'.$_SERVER["PHP_SELF"].'?statut=all">'.$langs->trans("IncludeClosedAccount").'</a>';
if ($statut == 'all') $link='<a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("OnlyOpenedAccount").'</a>';
print_fiche_titre($langs->trans("AccountsArea"),$link);
print '<br>';


// On charge tableau des comptes financiers (ouverts par defaut)
$accounts = array();

$sql  = "SELECT rowid, courant, rappro";
$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
if ($statut != 'all') {
    $sql .= " WHERE clos = 0";
}
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
print '<td width="20%">'.$langs->trans("Bank").'</td>';
print '<td align="left">'.$langs->trans("AccountIdShort").'</td>';
print '<td align="center">'.$langs->trans("TransactionsToConciliate").'</td>';
print '<td align="center" width="70">'.$langs->trans("Status").'</td>';
print '<td align="right" width="100">'.$langs->trans("BankBalance").'</td>';
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
		$solde = $acc->solde(1);

		print '<tr '.$bc[$var].'>';
		print '<td width="30%">'.$acc->getNomUrl(1).'</td>';
		print '<td>'.$acc->bank.'</td>';
		print '<td>'.$acc->number.'</td>';
		print '<td align="center">';
		if ($acc->rappro) 
		{
			$result=$acc->load_board($user,$acc->id);
			print $acc->nbtodo;
			if ($acc->nbtodolate) print ' ('.$acc->nbtodolate.img_warning($langs->trans("Late")).')';
		}
		else print $langs->trans("FeatureDisabled");
		print '</td>';
		print '<td align="center">'.$acc->getLibStatut(2).'</td>';
		print '<td align="right">';
		print '<a href="account.php?account='.$acc->id.'">'.price($solde).'</a>';
		print '</td>';
		print '</tr>';

		$total += $solde;
	}
}

// Total
print '<tr class="liste_total"><td colspan="4">&nbsp;</td><td align="center"><b>'.$langs->trans("Total").'</b></td><td align="right"><b>'.price($total).'</b></td></tr>';


print '<tr><td colspan="5">&nbsp;</td></tr>';


/*
 * Comptes placements
 */
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("SavingAccounts").'</td>';
print '<td width="20%">'.$langs->trans("Bank").'</td>';
print '<td align="left">'.$langs->trans("Numero").'</td>';
print '<td align="center" width="100">'.$langs->trans("TransactionsToConciliate").'</td>';
print '<td align="center" width="70">'.$langs->trans("Status").'</td>';
print '<td align="right" width="100">'.$langs->trans("BankBalance").'</td>';
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
		$solde = $acc->solde(1);

		print "<tr ".$bc[$var].">";
		print '<td width="30%">'.$acc->getNomUrl(1).'</td>';
		print '<td>'.$acc->bank.'</td>';
		print '<td>'.$acc->number.'</td>';
		print '<td align="center">';
		if ($acc->rappro) 
		{
			$result=$acc->load_board($user,$acc->id);
			print $acc->nbtodo;
			if ($acc->nbtodolate) print ' ('.$acc->nbtodolate.img_warning($langs->trans("Late")).')';
		}
		else print $langs->trans("FeatureDisabled");
		print '</td>';
		print '<td align="center">'.$acc->getLibStatut(2).'</td>';
		print '<td align="right">';
		print '<a href="account.php?account='.$acc->id.'">'.price($solde).'</a>';
		print '</td>';
		print '</tr>';

		$total += $solde;
	}
}

// Total
print '<tr class="liste_total"><td colspan="4">&nbsp;</td><td align="center"><b>'.$langs->trans("Total").'</b></td><td align="right"><b>'.price($total).'</b></td></tr>';


print '<tr><td colspan="5">&nbsp;</td></tr>';


/*
 * Comptes caisse/liquide
 */
print '<tr class="liste_titre"><td width="30%">'.$langs->trans("CashAccounts").'</td><td width="20%">&nbsp;</td>';
print '<td align="left">&nbsp;</td>';
print '<td align="left" width="100">&nbsp;</td>';
print '<td align="center" width="70">'.$langs->trans("Status").'</td>';
print '<td align="right" width="100">'.$langs->trans("BankBalance").'</td>';
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
        $solde = $acc->solde(1);

        print "<tr ".$bc[$var].">";
		print '<td width="30%">'.$acc->getNomUrl(1).'</td>';
        print '<td>'.$acc->bank.'</td>';
        print '<td>&nbsp;</td>';
        print '<td>&nbsp;</td>';
        print '<td align="center">'.$acc->getLibStatut(2).'</td>';
		print '<td align="right">';
		print '<a href="account.php?account='.$acc->id.'">'.price($solde).'</a>';
		print '</td>';
        print '</tr>';

        $total += $solde;
    }
}

// Total
print '<tr class="liste_total"><td colspan="4">&nbsp;</td><td align="center"><b>'.$langs->trans("Total").'</b></td><td align="right"><b>'.price($total).'</b></td></tr>';


print "</table>";


/*
 * Boutons d'actions
 */
print "<br><div class=\"tabsAction\">\n";
if ($user->rights->banque->configurer) {
	print '<a class="butAction" href="fiche.php?action=create">'.$langs->trans("NewFinancialAccount").'</a>';
	print '<a class="butAction" href="categ.php">'.$langs->trans("Categories").'</a>';
}
print "</div>";


$db->close();

llxFooter('$Date$ - $Revision$');
?>

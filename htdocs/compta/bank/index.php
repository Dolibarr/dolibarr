<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/compta/bank/index.php
 *       \ingroup    banque
 *       \brief      Home page of bank module
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque');

$statut=GETPOST('statut');



/*
 * View
 */

$help_url='EN:Module_Banks_and_Cash|FR:Module_Banques_et_Caisses|ES:M&oacute;dulo_Bancos_y_Cajas';
llxHeader('',$langs->trans('AccountsArea'),$help_url);

$link='';
if ($statut == '') $link='<a href="'.$_SERVER["PHP_SELF"].'?statut=all">'.$langs->trans("IncludeClosedAccount").'</a>';
if ($statut == 'all') $link='<a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("OnlyOpenedAccount").'</a>';
print load_fiche_titre($langs->trans("AccountsArea"),$link, 'title_bank.png');


// On charge tableau des comptes financiers (ouverts par defaut)
$accounts = array();

$sql  = "SELECT rowid, courant, rappro";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
$sql.= " WHERE entity IN (".getEntity('bank_account', 1).")";
if ($statut != 'all') $sql.= " AND clos = 0";
$sql.= $db->order('label', 'ASC');

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
 * Comptes courants (courant = 1)
 */
print '<table class="liste" width="100%">';
print '<tr class="liste_titre"><td width="30%">'.$langs->trans("CurrentAccounts").'</td>';
print '<td width="20%">'.$langs->trans("Bank").'</td>';
print '<td align="left">'.$langs->trans("AccountIdShort").'</td>';
print '<td align="center">'.$langs->trans("TransactionsToConciliate").'</td>';
print '<td align="center" width="70">'.$langs->trans("Status").'</td>';
print '<td align="right" width="100">'.$langs->trans("BankBalance").'</td>';
print "</tr>\n";

$total = array(); $found = 0;
$var=true;
foreach ($accounts as $key=>$type)
{
	if ($type == 1)
	{
	    $found++;

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
            if ($result<0) {
                setEventMessages($acc->error, $acc->errors, 'errors');
            } else {
                print $result->nbtodo;
                if ($result->nbtodolate) print ' ('.$result->nbtodolate.img_warning($langs->trans("Late")).')';
            }
		}
		else print $langs->trans("FeatureDisabled");
		print '</td>';
		print '<td align="center">'.$acc->getLibStatut(2).'</td>';
		print '<td align="right">';
		print '<a href="account.php?account='.$acc->id.'">'.price($solde, 0, $langs, 0, 0, -1, $acc->currency_code).'</a>';
		print '</td>';
		print '</tr>';

		$total[$acc->currency_code] += $solde;
	}
}
if (! $found) print '<tr '.$bc[$var].'><td colspan="6">'.$langs->trans("None").'</td></tr>';
// Total
foreach ($total as $key=>$solde)
{
	print '<tr class="liste_total"><td colspan="5" class="liste_total">'.$langs->trans("Total").' '.$key.'</td><td align="right" class="liste_total">'.price($solde, 0, $langs, 0, 0, -1, $key).'</td></tr>';
}

print '</table>';
print '<br>';

/*
 * Comptes caisse/liquide (courant = 2)
 */
print '<table class="liste" width="100%">';
print '<tr class="liste_titre"><td width="30%">'.$langs->trans("CashAccounts").'</td><td width="20%">&nbsp;</td>';
print '<td align="left">&nbsp;</td>';
print '<td align="left" width="100">&nbsp;</td>';
print '<td align="center" width="70">'.$langs->trans("Status").'</td>';
print '<td align="right" width="100">'.$langs->trans("BankBalance").'</td>';
print "</tr>\n";

$total = array(); $found = 0;
$var=true;
foreach ($accounts as $key=>$type)
{
	if ($type == 2)
	{
	    $found++;

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
		print '<a href="account.php?account='.$acc->id.'">'.price($solde, 0, $langs, 0, 0, -1, $acc->currency_code).'</a>';
		print '</td>';
		print '</tr>';

		$total[$acc->currency_code] += $solde;
	}
}
if (! $found)
{
	$var = !$var;
	print '<tr '.$bc[false].'><td colspan="6">'.$langs->trans("None").'</td></tr>';
}
// Total
foreach ($total as $key=>$solde)
{
	print '<tr class="liste_total"><td colspan="5" class="liste_total">'.$langs->trans("Total").' '.$key.'</td><td align="right" class="liste_total">'.price($solde, 0, $langs, 0, 0, -1, $key).'</td></tr>';
}

print '</table>';
print '<br>';

/*
 * Comptes placements (courant = 0)
 */
print '<table class="liste" width="100%">';
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("SavingAccounts").'</td>';
print '<td width="20%">'.$langs->trans("Bank").'</td>';
print '<td align="left">'.$langs->trans("Numero").'</td>';
print '<td align="center">'.$langs->trans("TransactionsToConciliate").'</td>';
print '<td align="center" width="70">'.$langs->trans("Status").'</td>';
print '<td align="right" width="100">'.$langs->trans("BankBalance").'</td>';
print "</tr>\n";

$total = array(); $found = 0;
$var=true;
foreach ($accounts as $key=>$type)
{
	if ($type == 0)
	{
	    $found++;

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
            if ($result<0) {
                setEventMessages($acc->error, $acc->errors, 'errors');
            } else {
                print $result->nbtodo;
                if ($result->nbtodolate) print ' ('.$result->nbtodolate.img_warning($langs->trans("Late")).')';
            }
		}
		else print $langs->trans("FeatureDisabled");
		print '</td>';
		print '<td align="center">'.$acc->getLibStatut(2).'</td>';
		print '<td align="right">';
		print '<a href="account.php?account='.$acc->id.'">'.price($solde, 0, $langs, 0, 0, -1, $acc->currency_code).'</a>';
		print '</td>';
		print '</tr>';

		$total[$acc->currency_code] += $solde;
	}
}
if (! $found)
{
	$var = !$var;
	print '<tr '.$bc[$var].'><td colspan="6">'.$langs->trans("None").'</td></tr>';
}
// Total
foreach ($total as $key=>$solde)
{
	print '<tr class="liste_total"><td colspan="5" class="liste_total">'.$langs->trans("Total").' '.$key.'</td><td align="right" class="liste_total">'.price($solde, 0, $langs, 0, 0, -1, $key).'</td></tr>';
}

print "</table>";


/*
 * Boutons d'actions
 */

print '<div class="tabsAction">'."\n";
if ($user->rights->banque->configurer)
{
	print '<a class="butAction" href="card.php?action=create">'.$langs->trans("NewFinancialAccount").'</a>';
}
print '</div>';


llxFooter();

$db->close();

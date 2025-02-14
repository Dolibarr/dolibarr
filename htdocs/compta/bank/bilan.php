<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/compta/bank/bilan.php
 *    \ingroup    compta/bank
 *    \brief      Page of Balance sheet
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories'));

// Security Check Access Control
if (!$user->hasRight('banque', 'lire')) {
	accessforbidden();
}


/**
 * 	Get result of sql for field amount
 *
 * 	@param	string	$sql	SQL string
 * 	@return	int				Amount
 */
function valeur($sql)
{
	global $db;

	$valeur = 0;

	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$valeur = $obj->amount;
		$db->free($resql);
	}
	return $valeur;
}


/*
 *	View
 */

llxHeader();

print load_fiche_titre("Bilan");
print '<br>';

print '<table class="noborder" width="100%" cellpadding="2">';
print "<tr class=\"liste_titre\">";
echo '<td colspan="2">'.$langs->trans("Summary").'</td>';
print "</tr>\n";


$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."paiement";
$paiem = valeur($sql);
print "<tr class=\"oddeven\"><td>Somme des paiements (associes a une facture)</td><td align=\"right\">".price($paiem)."</td></tr>";


$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."bank WHERE amount > 0";
$credits = valeur($sql);
print "<tr class=\"oddeven\"><td>Somme des credits</td><td align=\"right\">".price($credits)."</td></tr>";


$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."bank WHERE amount < 0";
$debits = valeur($sql);
print "<tr class=\"oddeven\"><td>Somme des debits</td><td align=\"right\">".price($debits)."</td></tr>";


$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."bank ";
$solde = valeur($sql);
print "<tr class=\"oddeven\"><td>".$langs->trans("BankBalance")."</td><td align=\"right\">".price($solde)."</td></tr>";


print "</table>";

// End of page
llxFooter();
$db->close();

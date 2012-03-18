<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/compta/bank/bilan.php
 *      \ingroup    banque
 *		\brief      Page de bilan
 */

require("./pre.inc.php");

if (!$user->rights->banque->lire)
  accessforbidden();


/**
 * 	Get result of sql for field amount
 *
 * 	@param	string	$sql	SQL string
 * 	@return	int				Amount
 */
function valeur($sql)
{
	global $db;
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$valeur = $obj->amount;
		$db->free($resql);
	}
	return $valeur;
}


/*
 *	View
 */

llxHeader();

print_titre("Bilan");
print '<br>';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="2">';
print "<tr class=\"liste_titre\">";
echo '<td colspan="2">'.$langs->trans("Summary").'</td>';
print "</tr>\n";

$var=!$var;
$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."paiement";
$paiem = valeur($sql);
print "<tr $bc[$var]><td>Somme des paiements (associes a une facture)</td><td align=\"right\">".price($paiem)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."bank WHERE amount > 0";
$credits = valeur($sql);
print "<tr $bc[$var]><td>Somme des credits</td><td align=\"right\">".price($credits)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."bank WHERE amount < 0";
$debits = valeur($sql);
print "<tr $bc[$var]><td>Somme des debits</td><td align=\"right\">".price($debits)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."bank ";
$solde = valeur($sql);
print "<tr $bc[$var]><td>".$langs->trans("BankBalance")."</td><td align=\"right\">".price($solde)."</td></tr>";


print "</table>";

$db->close();

llxFooter();
?>

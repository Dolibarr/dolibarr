<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/compta/bank/budget.php
 *      \ingroup    banque
 *		\brief      Page de budget
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories'));

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque');


/*
 * View
 */

$companystatic=new Societe($db);

llxHeader();

// List movements bu category for bank transactions
print load_fiche_titre($langs->trans("BankTransactionByCategories"), '', 'title_bank.png');

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td>'.$langs->trans("Rubrique").'</td>';
print '<td align="right">'.$langs->trans("Nb").'</td>';
print '<td align="right">'.$langs->trans("Total").'</td>';
print '<td align="right">'.$langs->trans("Average").'</td>';
print "</tr>\n";

$sql = "SELECT sum(d.amount) as somme, count(*) as nombre, c.label, c.rowid ";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ as c";
$sql.= ", ".MAIN_DB_PREFIX."bank_class as l";
$sql.= ", ".MAIN_DB_PREFIX."bank as d";
$sql.= " WHERE c.entity = ".$conf->entity;
$sql.= " AND c.rowid = l.fk_categ";
$sql.= " AND d.rowid = l.lineid";
$sql.= " GROUP BY c.label, c.rowid";
$sql.= " ORDER BY c.label";

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0; $total = 0; $totalnb = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($result);

		print '<tr class="oddeven">';
		print "<td><a href=\"".DOL_URL_ROOT."/compta/bank/bankentries_list.php?bid=$objp->rowid\">$objp->label</a></td>";
		print '<td align="right">'.$objp->nombre.'</td>';
		print '<td align="right">'.price(abs($objp->somme))."</td>";
		print '<td align="right">'.price(abs(price2num($objp->somme / $objp->nombre,'MT')))."</td>";
		print "</tr>";
		$i++;
		$total += abs($objp->somme);
		$totalnb += $objp->nombre;
	}
	$db->free($result);

	print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td>';
	print '<td align="right" class="liste_total">'.price($total).'</td>';
	print '<td align="right" colspan="2" class="liste_total">'.price($totalnb?price2num($total / $totalnb, 'MT'):0).'</td></tr>';
}
else
{
	dol_print_error($db);
}
print "</table>";

llxFooter();
$db->close();

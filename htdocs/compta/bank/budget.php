<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copytight (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	    \file       htdocs/compta/bank/budget.php
 *      \ingroup    banque
 *		\brief      Page de budget
 *		\version    $Id$
 */

require("./pre.inc.php");

$langs->load("categories");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque');


/*
 * View
 */

$companystatic=new Societe($db);

llxHeader();

// List movements bu category for bank transactions
print_fiche_titre($langs->trans("BankTransactionByCategories"));

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
	$i = 0; $total = 0;

	$var=true;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td><a href=\"".DOL_URL_ROOT."/compta/bank/search.php?bid=$objp->rowid\">$objp->label</a></td>";
		print '<td align="right">'.$objp->nombre.'</td>';
		print '<td align="right">'.price(abs($objp->somme))."</td>";
		print '<td align="right">'.price(abs(price2num($objp->somme / $objp->nombre,'MT')))."</td>";
		print "</tr>";
		$i++;
		$total = $total + abs($objp->somme);
	}
	$db->free($result);

	print '<tr class="liste_total"><td colspan="2" align="right">'.$langs->trans("Total").'</td>';
	print '<td align="right"><b>'.price($total).'</b></td><td colspan="2">&nbsp;</td></tr>';
}
else
{
	dol_print_error($db);
}
print "</table>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>

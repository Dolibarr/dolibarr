<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/compta/prelevement/index.php
 *	\brief      Prelevement
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/includes/modules/modPrelevement.class.php";
require_once DOL_DOCUMENT_ROOT."/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/societe.class.php";

$langs->load("withdrawals");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','');




/*
 * View
 */

llxHeader();


print_fiche_titre($langs->trans("CustomersStandingOrdersArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

$thirdpartystatic=new Societe($db);
$invoicestatic=new Facture($db);
$bprev = new BonPrelevement($db);
$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("NbOfInvoiceToWithdraw").'</td>';
print '<td align="right">';
print $bprev->NbFactureAPrelever();
print '</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("AmountToWithdraw").'</td>';
print '<td align="right">';
print price($bprev->SommeAPrelever());
print '</td></tr></table><br>';

print '</td><td valign="top" width="70%">';

/*
 * Factures
 */
$sql = "SELECT f.facnumber, f.rowid, s.nom, s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql .= " WHERE s.rowid = f.fk_soc";
$sql .= " AND pfd.traite = 0 AND pfd.fk_facture = f.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql .= " AND f.fk_soc = $socid";
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("InvoiceWaitingWithdraw").' ('.$num.')</td></tr>';
	if ($num)
	{
		$var = True;
		while ($i < $num && $i < 20)
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'><td>';
			$invoicestatic->id=$obj->rowid;
			$invoicestatic->ref=$obj->facnumber;
			print $invoicestatic->getNomUrl(1,'withdraw');
			print '</td>';
			print '<td>';
			$thirdpartystatic->id=$obj->socid;
			$thirdpartystatic->nom=$obj->nom;
			print $thirdpartystatic->getNomUrl(1,'customer');
			print '</td>';
			print '</tr>';
			$i++;
		}
	}
	else
	{
		print '<tr><td colspan="2">'.$langs->trans("NoInvoiceToWithdraw").'</td></tr>';
	}
	print "</table><br>";
}
else
{
	dolibarr_print_error($db);
}



/*
 * Bon de prélèvement
 *
 */
$limit=5;
$sql = "SELECT p.rowid, p.ref, p.amount,".$db->pdate("p.datec")." as datec";
$sql .= " ,p.statut ";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " ORDER BY datec DESC LIMIT ".$limit;

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	$var=True;

	print"\n<!-- debut table -->\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("LastWithdrawalReceipt",$limit).'</td>';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td align="right">'.$langs->trans("Amount").'</td>';
	print '</tr>';

	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		print "<tr $bc[$var]><td>";

		print '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';

		print '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

		print '<td>'.dolibarr_print_date($obj->datec,"dayhour")."</td>\n";

		print '<td align="right">'.price($obj->amount)."</td>\n";

		print "</tr>\n";
		$i++;
	}
	print "</table>";
	$db->free($result);
}
else
{
	dolibarr_print_error($db);
}

print '</td></tr></table>';

llxFooter('$Date$ - $Revision$');
?>

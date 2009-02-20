<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 \file       htdocs/compta/prelevement/create.php
 \brief      Prelevement
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/modPrelevement.class.php");
require_once DOL_DOCUMENT_ROOT."/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/societe.class.php";

$langs->load("widthdrawals");
$langs->load("companies");
$langs->load("banks");
$langs->load("bills");


if (!$user->rights->prelevement->bons->creer)
accessforbidden();


/*
 * Actions
 */

if ($_GET["action"] == 'create')
{
	$bprev = new BonPrelevement($db);
	$result=$bprev->create($_GET["banque"],$_GET["guichet"]);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$bprev->error.'</div>';
	}
	if ($result == 0)
	{
		$mesg='<div class="error">'.$langs->trans("NoInvoiceCouldBeWithdrawed").'</div>';
	}
}


/*
 * View
 */

$thirdpartystatic=new Societe($db);
$invoicestatic=new Facture($db);
$bprev = new BonPrelevement($db);

llxHeader();

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/create.php';
$head[$h][1] = $langs->trans("NewStandingOrder");
$h++;

dol_fiche_head($head, $hselected, $langs->trans("StandingOrders"));


$nb=$bprev->NbFactureAPrelever();
$nb1=$bprev->NbFactureAPrelever(1);
$nb11=$bprev->NbFactureAPrelever(1,1);
if ($nb < 0 || $nb1 < 0 || $nb11 < 0)
{
	dol_print_error($bprev->error);
}
print '<table class="border" width="100%">';

print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdraw").'</td>';
print '<td align="right">';
print $nb;
print '</td></tr>';
print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdraw").' '.$langs->trans("ThirdPartyBankCode").'='.PRELEVEMENT_CODE_BANQUE.'</td><td align="right">';
print $nb1;
print '</td></tr>';
print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdraw").' '.$langs->trans("ThirdPartyDeskCode").'='.PRELEVEMENT_CODE_GUICHET.'</td><td align="right">';
print $nb11;
print '</td></tr>';

print '<tr><td>'.$langs->trans("AmountToWithdraw").'</td>';
print '<td align="right">';
print price($bprev->SommeAPrelever());
print '</td>';
print '</tr>';

print '</table>';

print '</div>';

if ($mesg) print $mesg;

if ($nb)
{
	print "<div class=\"tabsAction\">\n";

	if ($nb) print '<a class="butAction" href="create.php?action=create">'.$langs->trans("Create")."</a>\n";
	if ($nb1) print '<a class="butAction" href="create.php?action=create&amp;banque=1&amp;guichet=1">'.$langs->trans("CreateGuichet")."</a>\n";
	if ($nb11) print '<a class="butAction" href="create.php?action=create&amp;banque=1">'.$langs->trans("CreateBanque")."</a>\n";

	print "</div>\n";
}
else
{
	print $langs->trans("NoInvoiceToWithdraw").'<br>';
}
print '<br>';


/*
 * Liste des derniers bons
 *
 */
$limit=5;

$sql = "SELECT p.rowid, p.ref, p.amount,".$db->pdate("p.datec")." as datec";
$sql.= ", p.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " ORDER BY datec DESC";
$sql.=$db->plimit($limit);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	print"\n<!-- debut table -->\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("LastWithdrawalReceipts",$limit).'</td>';
	print '<td><Date</td><td align="right">'.$langs->trans("Amount").'</td>';
	print '</tr>';

	$var=True;

	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		print "<tr $bc[$var]><td>";
		$bprev->id=$obj->rowid;
		$bprev->ref=$obj->ref;
		print $bprev->getNomUrl(1);
		print "</td>\n";
		print '<td align="center">'.dol_print_date($obj->datec,'day')."</td>\n";

		print '<td align="right">'.price($obj->amount).' '.$langs->trans("Currency".$conf->monnaie)."</td>\n";

		print "</tr>\n";
		$i++;
	}
	print "</table><br>";
	$db->free($result);
}
else
{
	dol_print_error($db);
}



/*
 * Factures en attente de prélèvement
 *
 */
$sql = "SELECT f.facnumber, f.rowid, s.nom, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql .= " WHERE s.rowid = f.fk_soc";
$sql .= " AND pfd.traite = 0 AND pfd.fk_facture = f.rowid";

if ($socid)
{
	$sql .= " AND f.fk_soc = $socid";
}

if ( $db->query($sql) )
{
	$num = $db->num_rows();
	$i = 0;

	if ($num)
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("InvoiceWaitingWithdraw").' ('.$num.')</td></tr>';
		$var = True;
		while ($i < $num && $i < 20)
		{
			  $obj = $db->fetch_object();
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

		print "</table><br>";

	}
}
else
{
	dol_print_error($db);
}


llxFooter('$Date$ - $Revision$');
?>

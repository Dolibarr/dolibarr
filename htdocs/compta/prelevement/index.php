<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
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
 *	\file       htdocs/compta/prelevement/index.php
 *  \ingroup    prelevement
 *	\brief      Home page for direct debit orders
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals'));

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'prelevement', '', 'bons');

$usercancreate = $user->hasRight('prelevement', 'bons', 'creer');


/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader('', $langs->trans("CustomersStandingOrdersArea"));

if (prelevement_check_config() < 0) {
	$langs->load("errors");
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("PaymentByDirectDebit")), null, 'errors');
}

$newcardbutton = '';
if ($usercancreate) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewStandingOrder'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/prelevement/create.php?type=');
}

print load_fiche_titre($langs->trans("CustomersStandingOrdersArea"), $newcardbutton);


print '<div class="fichecenter"><div class="fichethirdleft">';


$thirdpartystatic = new Societe($db);
$invoicestatic = new Facture($db);
$bprev = new BonPrelevement($db);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("NbOfInvoiceToWithdraw").'</td>';
print '<td class="right">';
print '<a class="badge badge-info" href="'.DOL_URL_ROOT.'/compta/prelevement/demandes.php?status=0">';
print $bprev->nbOfInvoiceToPay('direct-debit');
print '</a>';
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("AmountToWithdraw").'</td>';
print '<td class="right"><span class="amount">';
print price($bprev->SommeAPrelever('direct-debit'), 0, '', 1, -1, -1, 'auto');
print '</span></td></tr></table></div><br>';



/*
 * Invoices waiting for withdraw
 */
$sql = "SELECT f.ref, f.rowid, f.total_ttc, f.fk_statut as status, f.paye, f.type,";
$sql .= " pfd.date_demande, pfd.amount,";
$sql .= " s.nom as name, s.email, s.rowid as socid, s.tva_intra, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4, s.idprof5, s.idprof6";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f,";
$sql .= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " , ".MAIN_DB_PREFIX."prelevement_demande as pfd";
$sql .= " WHERE s.rowid = f.fk_soc";
$sql .= " AND f.entity IN (".getEntity('invoice').")";
$sql .= " AND f.total_ttc > 0";
if (!getDolGlobalString('WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS')) {
	$sql .= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
}
$sql .= " AND pfd.traite = 0";
$sql .= " AND pfd.ext_payment_id IS NULL";
$sql .= " AND pfd.fk_facture = f.rowid";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($socid) {
	$sql .= " AND f.fk_soc = ".((int) $socid);
}

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="5">'.$langs->trans("InvoiceWaitingWithdraw").' ('.$num.')</th></tr>';
	if ($num) {
		while ($i < $num && $i < 20) {
			$obj = $db->fetch_object($resql);

			$invoicestatic->id = $obj->rowid;
			$invoicestatic->ref = $obj->ref;
			$invoicestatic->statut = $obj->status;
			$invoicestatic->status = $obj->status;
			$invoicestatic->paye = $obj->paye;
			$invoicestatic->type = $obj->type;

			$totalallpayments = $invoicestatic->getSommePaiement(0);
			$totalallpayments += $invoicestatic->getSumCreditNotesUsed(0);
			$totalallpayments += $invoicestatic->getSumDepositsUsed(0);

			$thirdpartystatic->id = $obj->socid;
			$thirdpartystatic->name = $obj->name;
			$thirdpartystatic->email = $obj->email;
			$thirdpartystatic->tva_intra = $obj->tva_intra;
			$thirdpartystatic->siren = $obj->idprof1;
			$thirdpartystatic->siret = $obj->idprof2;
			$thirdpartystatic->ape = $obj->idprof3;
			$thirdpartystatic->idprof1 = $obj->idprof1;
			$thirdpartystatic->idprof2 = $obj->idprof2;
			$thirdpartystatic->idprof3 = $obj->idprof3;
			$thirdpartystatic->idprof4 = $obj->idprof4;
			$thirdpartystatic->idprof5 = $obj->idprof5;
			$thirdpartystatic->idprof6 = $obj->idprof6;

			print '<tr class="oddeven"><td class="nowraponall">';
			print $invoicestatic->getNomUrl(1, 'withdraw');
			print '</td>';

			print '<td class="tdoverflowmax150">';
			print $thirdpartystatic->getNomUrl(1, 'customer');
			print '</td>';

			print '<td class="right">';
			print '<span class="amount">'.price($obj->amount).'</span>';
			print '</td>';

			print '<td class="right">';
			print dol_print_date($db->jdate($obj->date_demande), 'day');
			print '</td>';

			print '<td class="right">';
			print $invoicestatic->getLibStatut(3, $totalallpayments);
			print '</td>';
			print '</tr>';
			$i++;
		}
	} else {
		$titlefortab = $langs->transnoentitiesnoconv("StandingOrders");
		print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans("NoInvoiceToWithdraw", $titlefortab, $titlefortab).'</span></td></tr>';
	}
	print "</table></div><br>";
} else {
	dol_print_error($db);
}


print '</div><div class="fichetwothirdright">';


/*
 * Direct debit orders
 */

$limit = 5;
$sql = "SELECT p.rowid, p.ref, p.amount, p.datec, p.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " WHERE p.type = 'debit-order'";
$sql .= " AND entity IN (".getEntity('prelevement').")";
$sql .= " ORDER BY datec DESC";
$sql .= $db->plimit($limit);

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	print"\n<!-- debut table -->\n";
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("LastWithdrawalReceipt", $limit).'</th>';
	print '<th>'.$langs->trans("Date").'</th>';
	print '<th class="right">'.$langs->trans("Amount").'</th>';
	print '<th class="right">'.$langs->trans("Status").'</th>';
	print '</tr>';

	if ($num > 0) {
		while ($i < min($num, $limit)) {
			$obj = $db->fetch_object($result);

			$bprev->id = $obj->rowid;
			$bprev->ref = $obj->ref;
			$bprev->statut = $obj->statut;

			print '<tr class="oddeven">';

			print '<td class="nowraponall">';
			print $bprev->getNomUrl(1);
			print "</td>\n";
			print '<td>'.dol_print_date($db->jdate($obj->datec), "dayhour")."</td>\n";
			print '<td class="right nowraponall"><span class="amount">'.price($obj->amount)."</span></td>\n";
			print '<td class="right">'.$bprev->getLibStatut(3)."</td>\n";

			print "</tr>\n";
			$i++;
		}
	} else {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	print "</table></div><br>";
	$db->free($result);
} else {
	dol_print_error($db);
}


print '</div></div>';

// End of page
llxFooter();
$db->close();

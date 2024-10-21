<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/compta/paymentbybanktransfer/index.php
 *  \ingroup    paymentbybanktransfer
 *	\brief      Payment by bank transfer index page
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals'));

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'paymentbybanktransfer', '', '');

$usercancreate = $user->hasRight('paymentbybanktransfer', 'create');


/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader('', $langs->trans("SuppliersStandingOrdersArea"));

if (prelevement_check_config('bank-transfer') < 0) {
	$langs->load("errors");
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("PaymentByBankTransfer")), null, 'errors');
}

$thirdpartystatic = new Societe($db);
$invoicestatic = new FactureFournisseur($db);
$bprev = new BonPrelevement($db);
$salary = new Salary($db);
$user = new User($db);

$newcardbutton = '';
if ($usercancreate) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewPaymentByBankTransfer'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/prelevement/create.php?type=bank-transfer');
}

print load_fiche_titre($langs->trans("SuppliersStandingOrdersArea"), $newcardbutton);


print '<div class="fichecenter"><div class="fichethirdleft">';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';

$totaltoshow = 0;

if (isModEnabled('supplier_invoice')) {
	print '<tr class="oddeven"><td>'.$langs->trans("NbOfInvoiceToPayByBankTransfer").'</td>';
	print '<td class="right">';
	$amounttoshow = $bprev->SommeAPrelever('bank-transfer');
	print '<a class="badge badge-info" href="'.DOL_URL_ROOT.'/compta/prelevement/demandes.php?status=0&type=bank-transfer" title="'.price($amounttoshow).'">';
	print $bprev->nbOfInvoiceToPay('bank-transfer');
	print '</a>';
	print '</td></tr>';
	$totaltoshow += $amounttoshow;
}

if (isModEnabled('salaries')) {
	print '<tr class="oddeven"><td>'.$langs->trans("NbOfInvoiceToPayByBankTransferForSalaries").'</td>';
	print '<td class="right">';
	$amounttoshow = $bprev->SommeAPrelever('bank-transfer', 'salary');
	print '<a class="badge badge-info" href="'.DOL_URL_ROOT.'/compta/prelevement/demandes.php?status=0&type=bank-transfer&sourcetype=salary" title="'.price($amounttoshow).'">';
	print $bprev->nbOfInvoiceToPay('bank-transfer', 'salary');
	print '</a>';
	print '</td></tr>';
	$totaltoshow += $amounttoshow;
}

print '<tr class="oddeven"><td>'.$langs->trans("Total").'</td>';
print '<td class="right"><span class="amount nowraponall">';
print price($totaltoshow, 0, '', 1, -1, -1, 'auto');
print '</span></td></tr></table></div><br>';


/*
 * Invoices waiting for credit transfer
 */
if (isModEnabled('supplier_invoice')) {
	$sql = "SELECT f.ref, f.rowid, f.total_ttc, f.fk_statut, f.paye, f.type, f.datef, f.date_lim_reglement,";
	$sql .= " pfd.date_demande, pfd.amount,";
	$sql .= " s.nom as name, s.email, s.rowid as socid, s.tva_intra, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4, s.idprof5, s.idprof6";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s,";
	$sql .= " ".MAIN_DB_PREFIX."prelevement_demande as pfd";
	$sql .= " WHERE s.rowid = f.fk_soc";
	$sql .= " AND f.entity IN (".getEntity('supplier_invoice').")";
	$sql .= " AND f.total_ttc > 0";
	if (!getDolGlobalString('WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS')) {
		$sql .= " AND f.fk_statut = ".FactureFournisseur::STATUS_VALIDATED;
	}
	$sql .= " AND pfd.traite = 0";
	$sql .= " AND pfd.ext_payment_id IS NULL";
	$sql .= " AND pfd.fk_facture_fourn = f.rowid";
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
		print '<th colspan="5">'.$langs->trans("SupplierInvoiceWaitingWithdraw").' <span class="opacitymedium">('.$num.')</span></th></tr>';
		if ($num) {
			while ($i < $num && $i < 20) {
				$obj = $db->fetch_object($resql);

				$invoicestatic->id = $obj->rowid;
				$invoicestatic->ref = $obj->ref;
				$invoicestatic->status = $obj->fk_statut;
				$invoicestatic->statut = $obj->fk_statut;	// For backward compatibility
				$invoicestatic->paye = $obj->paye;
				$invoicestatic->paid = $obj->paye;
				$invoicestatic->type = $obj->type;
				$invoicestatic->date = $db->jdate($obj->datef);
				$invoicestatic->date_echeance = $db->jdate($obj->date_lim_reglement);
				$invoicestatic->total_ttc = $obj->total_ttc;

				$alreadypayed = $invoicestatic->getSommePaiement();

				$thirdpartystatic->id = $obj->socid;
				$thirdpartystatic->name = $obj->name;
				$thirdpartystatic->email = $obj->email;
				$thirdpartystatic->tva_intra = $obj->tva_intra;
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
				print $thirdpartystatic->getNomUrl(1, 'supplier');
				print '</td>';

				print '<td class="right">';
				print '<span class="amount">'.price($obj->amount).'</span>';
				print '</td>';

				print '<td class="right">';
				print dol_print_date($db->jdate($obj->date_demande), 'day');
				print '</td>';

				print '<td class="right">';
				print $invoicestatic->getLibStatut(3, $alreadypayed);
				print '</td>';
				print '</tr>';
				$i++;
			}
		} else {
			$titlefortab = $langs->transnoentitiesnoconv("BankTransfer");
			print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans("NoSupplierInvoiceToWithdraw", $titlefortab, $titlefortab).'</span></td></tr>';
		}
		print "</table></div><br>";
	} else {
		dol_print_error($db);
	}
}

if (isModEnabled('salaries')) {
	$sqlForSalary = "SELECT * FROM ".MAIN_DB_PREFIX."salary as s, ".MAIN_DB_PREFIX."prelevement_demande as pd";
	$sqlForSalary .= " WHERE s.rowid = pd.fk_salary AND s.paye = 0 AND pd.traite = 0";
	$sqlForSalary .= " AND s.entity IN (".getEntity('salary').")";

	$resql2 = $db->query($sqlForSalary);
	if ($resql2) {
		$numRow = $db->num_rows($resql2);
		$j = 0 ;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder rightpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="5">'.$langs->trans("SalaryInvoiceWaitingWithdraw").' <span class="opacitymedium">('.$numRow.')</span></th></tr>';

		if ($numRow) {
			while ($j < $numRow && $j < 10) {
				$objSalary = $db->fetch_object($resql2);

				$user->fetch($objSalary->fk_user);

				$salary->fetch($objSalary->fk_salary);

				$alreadypayedS = $salary->getSommePaiement();

				print '<tr class="oddeven"><td class="nowraponall">';
				print $salary->getNomUrl(1);
				print '</td>';

				print '<td class="tdoverflowmax150">';
				print $user->getNomUrl(-1);
				print '</td>';

				print '<td class="right">';
				print '<span class="amount">'.price($objSalary->amount).'</span>';
				print '</td>';

				print '<td class="right" title="'.$langs->trans("DateRequest").'">';
				print dol_print_date($db->jdate($objSalary->date_demande), 'day');
				print '</td>';

				print '<td class="right">';
				print $salary->getLibStatut(3, $alreadypayedS);
				print '</td>';
				print '</tr>';
				$j++;
			}
		} else {
			$titlefortab = $langs->transnoentitiesnoconv("BankTransfer");
			print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans("NoSalaryInvoiceToWithdraw", $titlefortab, $titlefortab).'</span></td></tr>';
		}
		print "</table></div><br>";
	} else {
		dol_print_error($db);
	}
}


print '</div><div class="fichetwothirdright">';

/*
 * Withdraw receipts
 */

$limit = 5;
$sql = "SELECT p.rowid, p.ref, p.amount, p.datec, p.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " WHERE p.type = 'bank-transfer'";
$sql .= " AND p.entity IN (".getEntity('invoice').")";
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
	print '<th>'.$langs->trans("LatestBankTransferReceipts", $limit).'</th>';
	print '<th>'.$langs->trans("Date").'</th>';
	print '<th class="right">'.$langs->trans("Amount").'</th>';
	print '<th class="right">'.$langs->trans("Status").'</th>';
	print '</tr>';

	if ($num > 0) {
		while ($i < min($num, $limit)) {
			$obj = $db->fetch_object($result);

			print '<tr class="oddeven">';

			print '<td class="nowraponall">';
			$bprev->id = $obj->rowid;
			$bprev->ref = $obj->ref;
			$bprev->statut = $obj->statut;
			print $bprev->getNomUrl(1);
			print "</td>\n";
			print '<td>'.dol_print_date($db->jdate($obj->datec), "dayhour")."</td>\n";
			print '<td class="right nowraponall"><span class="amount">'.price($obj->amount)."</span></td>\n";
			print '<td class="right"><span class="amount">'.$bprev->getLibStatut(3)."</span></td>\n";

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

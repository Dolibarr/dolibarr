<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2016      Juanjo Menent	    <jmenent@2byte.es>
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
 *		\file       htdocs/compta/paiement/cheque/index.php
 *		\ingroup    compta
 *		\brief      Home page for cheque receipts
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'compta', 'bills'));

$checkdepositstatic = new RemiseCheque($db);
$accountstatic = new Account($db);

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'banque', '', '');

$usercancreate = $user->hasRight('banque', 'cheque');


/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader('', $langs->trans("ChequesArea"));

$newcardbutton = '';
if ($usercancreate) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewDeposit'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/paiement/cheque/card.php?action=new');
}

print load_fiche_titre($langs->trans("ChequesArea"), $newcardbutton, $checkdepositstatic->picto);

print '<div class="fichecenter"><div class="fichethirdleft">';

$sql = "SELECT count(b.rowid) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= " WHERE ba.rowid = b.fk_account";
$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
$sql .= " AND b.fk_type = 'CHQ'";
$sql .= " AND b.fk_bordereau = 0";
$sql .= " AND b.amount > 0";

$resql = $db->query($sql);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("BankChecks")."</th>\n";
print "</tr>\n";

if ($resql) {
	$num = '';
	if ($obj = $db->fetch_object($resql)) {
		$num = $obj->nb;
	}
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("BankChecksToReceipt").'</td>';
	print '<td class="right">';
	print '<a class="badge badge-info" href="'.DOL_URL_ROOT.'/compta/paiement/cheque/card.php?leftmenu=customers_bills_checks&action=new">'.$num.'</a>';
	print '</td></tr>';
} else {
	dol_print_error($db);
}
print "</table></div>\n";


print '</div><div class="fichetwothirdright">';

$max = 10;

$sql = "SELECT bc.rowid, bc.date_bordereau as db, bc.amount, bc.ref as ref,";
$sql .= " bc.statut, bc.nbcheque,";
$sql .= " ba.ref as bref, ba.label, ba.rowid as bid, ba.number, ba.currency_code, ba.account_number, ba.fk_accountancy_journal,";
$sql .= " aj.code";
$sql .= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc, ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_journal as aj ON aj.rowid = ba.fk_accountancy_journal";
$sql .= " WHERE ba.rowid = bc.fk_bank_account";
$sql .= " AND bc.entity = ".$conf->entity;
$sql .= " ORDER BY bc.date_bordereau DESC, rowid DESC";
$sql .= $db->plimit($max);

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("LastCheckReceiptShort", $max).'</th>';
	print '<th>'.$langs->trans("Date")."</th>";
	print '<th>'.$langs->trans("Account").'</th>';
	print '<th class="right">'.$langs->trans("NbOfCheques").'</th>';
	print '<th class="right">'.$langs->trans("Amount").'</th>';
	print '<th class="right">'.$langs->trans("Status").'</th>';
	print "</tr>\n";

	$num = $db->num_rows($resql);

	while ($objp = $db->fetch_object($resql)) {
		$checkdepositstatic->id = $objp->rowid;
		$checkdepositstatic->ref = ($objp->ref ? $objp->ref : $objp->rowid);
		$checkdepositstatic->statut = $objp->statut;

		$accountstatic->id = $objp->bid;
		$accountstatic->ref = $objp->bref;
		$accountstatic->label = $objp->label;
		$accountstatic->number = $objp->number;
		$accountstatic->currency_code = $objp->currency_code;
		$accountstatic->account_number = $objp->account_number;
		$accountstatic->accountancy_journal = $objp->code;
		$accountstatic->fk_accountancy_journal = $objp->fk_accountancy_journal;

		print '<tr class="oddeven">'."\n";

		print '<td class="nowraponall">'.$checkdepositstatic->getNomUrl(1).'</td>';
		print '<td>'.dol_print_date($db->jdate($objp->db), 'day').'</td>';
		print '<td class="nowraponall">'.$accountstatic->getNomUrl(1).'</td>';
		print '<td class="right">'.$objp->nbcheque.'</td>';
		print '<td class="right"><span class="amount">'.price($objp->amount).'</span></td>';
		print '<td class="right">'.$checkdepositstatic->LibStatut($objp->statut, 3).'</td>';

		print '</tr>';
	}
	if (empty($num)) {
		print '<tr><td colspan="6"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';

	$db->free($resql);
} else {
	dol_print_error($db);
}


print '</div></div>';

// End of page
llxFooter();
$db->close();

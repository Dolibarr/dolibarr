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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/compta/paiement/cheque/index.php
 *		\ingroup    compta
 *		\brief      Home page for cheque receipts
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'compta', 'bills'));

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'banque', '', '');


$checkdepositstatic=new RemiseCheque($db);
$accountstatic=new Account($db);


/*
 * View
 */

llxHeader('', $langs->trans("ChequesArea"));

print load_fiche_titre($langs->trans("ChequesArea"));

print '<div class="fichecenter"><div class="fichethirdleft">';

$sql = "SELECT count(b.rowid)";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " WHERE ba.rowid = b.fk_account";
$sql.= " AND ba.entity IN (".getEntity('bank_account').")";
$sql.= " AND b.fk_type = 'CHQ'";
$sql.= " AND b.fk_bordereau = 0";
$sql.= " AND b.amount > 0";

$resql = $db->query($sql);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("BankChecks")."</th>\n";
print "</tr>\n";

if ($resql) {
    if ($row = $db->fetch_row($resql) ) {
        $num = $row[0];
    }
    print '<tr class="oddeven">';
    print '<td>'.$langs->trans("BankChecksToReceipt").'</td>';
    print '<td class="right">';
    print '<a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/card.php?leftmenu=customers_bills_checks&action=new">'.$num.'</a>';
    print '</td></tr>';
    print "</table>\n";
}
else
{
    dol_print_error($db);
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

$max=10;

$sql = "SELECT bc.rowid, bc.date_bordereau as db, bc.amount, bc.ref as ref,";
$sql.= " bc.statut, bc.nbcheque,";
$sql.= " ba.ref as bref, ba.label, ba.rowid as bid, ba.number, ba.currency_code, ba.account_number, ba.fk_accountancy_journal,";
$sql.= " aj.code";
$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc, ".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_journal as aj ON aj.rowid = ba.fk_accountancy_journal";
$sql.= " WHERE ba.rowid = bc.fk_bank_account";
$sql.= " AND bc.entity = ".$conf->entity;
$sql.= " ORDER BY bc.date_bordereau DESC, rowid DESC";
$sql.= $db->plimit($max);

$resql = $db->query($sql);
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("LastCheckReceiptShort", $max).'</th>';
	print '<th>'.$langs->trans("Date")."</th>";
	print '<th>'.$langs->trans("Account").'</th>';
	print '<th class="right">'.$langs->trans("NbOfCheques").'</th>';
	print '<th class="right">'.$langs->trans("Amount").'</th>';
	print '<th class="right">'.$langs->trans("Status").'</th>';
	print "</tr>\n";

	while ( $objp = $db->fetch_object($resql) )
	{
        $checkdepositstatic->id=$objp->rowid;
        $checkdepositstatic->ref=($objp->ref?$objp->ref:$objp->rowid);
	    $checkdepositstatic->statut=$objp->statut;

		$accountstatic->id=$objp->bid;
		$accountstatic->ref=$objp->bref;
		$accountstatic->label=$objp->label;
		$accountstatic->number=$objp->number;
		$accountstatic->currency_code=$objp->currency_code;
		$accountstatic->account_number=$objp->account_number;
		$accountstatic->accountancy_journal=$objp->code;
		$accountstatic->fk_accountancy_journal=$objp->fk_accountancy_journal;

		print '<tr class="oddeven">'."\n";

		print '<td>'.$checkdepositstatic->getNomUrl(1).'</td>';
		print '<td>'.dol_print_date($db->jdate($objp->db), 'day').'</td>';
		print '<td>'.$accountstatic->getNomUrl(1).'</td>';
		print '<td class="right">'.$objp->nbcheque.'</td>';
		print '<td class="right">'.price($objp->amount).'</td>';
		print '<td class="right">'.$checkdepositstatic->LibStatut($objp->statut, 3).'</td>';

		print '</tr>';
	}
	print "</table>";

	$db->free($resql);
}
else
{
    dol_print_error($db);
}


print '</div></div></div>';

// End of page
llxFooter();
$db->close();

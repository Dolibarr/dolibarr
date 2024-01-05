<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Juanjo Menent   <jmenent@2byte.es>
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
 *	\file       htdocs/compta/prelevement/fiche-stat.php
 *  \ingroup    prelevement
 *	\brief      Debit order or credit transfer statistics
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array("banks", "categories", 'withdrawals', 'bills'));

// Get supervariables
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$type = GETPOST('type', 'aZ09');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


$object = new BonPrelevement($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check
if ($user->socid > 0) {
	accessforbidden();
}

$type = $object->type;
if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');
}



/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("WithdrawalsReceipts"));

if ($id > 0 || $ref) {
	if ($object->fetch($id, $ref) >= 0) {
		$head = prelevement_prepare_head($object);
		print dol_get_fiche_head($head, 'statistics', $langs->trans("WithdrawalsReceipts"), -1, 'payment');

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/orders_list.php?restore_lastsearch_values=1'.($object->type != 'bank-transfer' ? '' : '&type=bank-transfer').'">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">'."\n";

		//print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td>'.$object->getNomUrl(1).'</td></tr>';
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Date").'</td><td>'.dol_print_date($object->datec, 'day').'</td></tr>';
		print '<tr><td>'.$langs->trans("Amount").'</td><td><span class="amount">'.price($object->amount).'</span></td></tr>';

		if (!empty($object->date_trans)) {
			$muser = new User($db);
			$muser->fetch($object->user_trans);

			print '<tr><td>'.$langs->trans("TransData").'</td><td>';
			print dol_print_date($object->date_trans, 'day');
			print ' &nbsp; <span class="opacitymedium">'.$langs->trans("By").'</span> '.$muser->getNomUrl(-1).'</td></tr>';
			print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
			print $object->methodes_trans[$object->method_trans];
			print '</td></tr>';
		}
		if (!empty($object->date_credit)) {
			print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
			print dol_print_date($object->date_credit, 'day');
			print '</td></tr>';
		}

		print '</table>';

		print '<br>';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		// Get bank account for the payment
		$acc = new Account($db);
		$fk_bank_account = $object->fk_bank_account;
		if (empty($fk_bank_account)) {
			$fk_bank_account = ($object->type == 'bank-transfer' ? getDolGlobalInt('PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT') : getDolGlobalInt('PRELEVEMENT_ID_BANKACCOUNT'));
		}
		if ($fk_bank_account > 0) {
			$result = $acc->fetch($fk_bank_account);
		}

		$labelofbankfield = "BankToReceiveWithdraw";
		if ($object->type == 'bank-transfer') {
			$labelofbankfield = 'BankToPayCreditTransfer';
		}

		print '<tr><td class="titlefieldcreate">';
		print $form->textwithpicto($langs->trans("BankAccount"), $langs->trans($labelofbankfield));
		print '</td>';
		print '<td>';
		if ($acc->id > 0) {
			print $acc->getNomUrl(1);
		}
		print '</td>';
		print '</tr>';

		$modulepart = 'prelevement';
		if ($object->type == 'bank-transfer') {
			$modulepart = 'paymentbybanktransfer';
		}

		print '<tr><td class="titlefieldcreate">';
		$labelfororderfield = 'WithdrawalFile';
		if ($object->type == 'bank-transfer') {
			$labelfororderfield = 'CreditTransferFile';
		}
		print $langs->trans($labelfororderfield).'</td><td>';

		if (isModEnabled('multicompany')) {
			$labelentity = $conf->entity;
			$relativepath = 'receipts/'.$object->ref.'-'.$labelentity.'.xml';

			if ($type != 'bank-transfer') {
				$dir = $conf->prelevement->dir_output;
			} else {
				$dir = $conf->paymentbybanktransfer->dir_output;
			}
			if (!dol_is_file($dir.'/'.$relativepath)) {	// For backward compatibility
				$relativepath = 'receipts/'.$object->ref.'.xml';
			}
		} else {
			$relativepath = 'receipts/'.$object->ref.'.xml';
		}

		print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'">'.$relativepath;
		print img_picto('', 'download', 'class="paddingleft"');
		print '</a>';
		print '</td></tr></table>';

		print '</div>';

		print dol_get_fiche_end();
	} else {
		dol_print_error($db);
	}

	/*
	 * Stats
	 */
	$line = new LignePrelevement($db);

	$sql = "SELECT sum(pl.amount), pl.statut";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
	$sql .= " WHERE pl.fk_prelevement_bons = ".((int) $object->id);
	$sql .= " GROUP BY pl.statut";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		print load_fiche_titre($langs->trans("StatisticsByLineStatus"), '', '');

		print"\n<!-- debut table -->\n";
		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Status").'</td><td class="right">'.$langs->trans("Amount").'</td><td class="right">%</td></tr>';

		while ($i < $num) {
			$row = $db->fetch_row($resql);

			print '<tr class="oddeven"><td>';

			print $line->LibStatut($row[1], 1);

			print '</td>';

			print '<td class="right"><span class="amount">';
			print price($row[0]);
			print '</span></td>';

			print '<td class="right">';
			if ($object->amount) {
				print round($row[0] / $object->amount * 100, 2)." %";
			}
			print '</td>';

			print "</tr>\n";


			$i++;
		}

		print "</table>";
		print "</div>";

		$db->free($resql);
	} else {
		print $db->error().' '.$sql;
	}
}

// End of page
llxFooter();
$db->close();

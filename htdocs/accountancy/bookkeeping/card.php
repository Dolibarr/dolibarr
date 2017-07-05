<?php
/* Copyright (C) 2013-2017 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2017 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2017 Alexandre Spangaro	<aspangaro@zendsi.com>
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
 * \file		htdocs/accountancy/bookkeeping/card.php
 * \ingroup		Advanced accountancy
 * \brief		Page to show book-entry
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';

$langs->load("accountancy");

// Security check
$id = GETPOST('id', 'int');
if ($user->societe_id > 0) {
	accessforbidden();
}
$action = GETPOST('action','aZ09');
$mode = GETPOST('mode');
$piece_num = GETPOST("piece_num");

$mesg = '';

$account_number = GETPOST('account_number');
$subledger_account = GETPOST('subledger_account');
if ($subledger_account == - 1) {
	$subledger_account = null;
}
$label_compte = GETPOST('label_compte');
$label_operation= GETPOST('label_operation');
$debit = price2num(GETPOST('debit'));
$credit = price2num(GETPOST('credit'));

$save = GETPOST('save');
if (! empty($save)) {
	$action = 'add';
}
$update = GETPOST('update');
if (! empty($update)) {
	$action = 'confirm_update';
}
$object = new BookKeeping($db);
if ($action == "confirm_update") {

	$error = 0;

	if ((floatval($debit) != 0.0) && (floatval($credit) != 0.0)) {
		setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
		$error ++;
	}

	if (empty($error)) {
		$book = new BookKeeping($db);

		$result = $book->fetch($id, null, $mode);
		if ($result < 0) {
			setEventMessages($book->error, $book->errors, 'errors');
		} else {
			$book->numero_compte = $account_number;
			$book->subledger_account = $subledger_account;
			$book->label_compte = $label_compte;
			$book->label_operation= $label_operation;
			$book->debit = $debit;
			$book->credit = $credit;

			if (floatval($debit) != 0.0) {
				$book->montant = $debit;
				$book->sens = 'D';
			}
			if (floatval($credit) != 0.0) {
				$book->montant = $credit;
				$book->sens = 'C';
			}

			$result = $book->update($user, false, $mode);
			if ($result < 0) {
				setEventMessages($book->error, $book->errors, 'errors');
			} else {
				setEventMessages($langs->trans('Saved'), null, 'mesgs');
				$action = '';
			}
		}
	}
}

else if ($action == "add") {
	$error = 0;

	if ((floatval($debit) != 0.0) && (floatval($credit) != 0.0)) {
		setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
		$error ++;
	}

	if (empty($error)) {
		$book = new BookKeeping($db);

		$book->numero_compte = $account_number;
		$book->subledger_account = $subledger_account;
		$book->label_compte = $label_compte;
		$book->label_operation= $label_operation;
		$book->debit = $debit;
		$book->credit = $credit;
		$book->doc_date = GETPOST('doc_date');
		$book->doc_type = GETPOST('doc_type');
		$book->piece_num = $piece_num;
		$book->doc_ref = GETPOST('doc_ref');
		$book->code_journal = GETPOST('code_journal');
		$book->fk_doc = GETPOST('fk_doc');
		$book->fk_docdet = GETPOST('fk_docdet');

		if (floatval($debit) != 0.0) {
			$book->montant = $debit;
			$book->sens = 'D';
		}

		if (floatval($credit) != 0.0) {
			$book->montant = $credit;
			$book->sens = 'C';
		}

		$result = $book->createStd($user, false, $mode);
		if ($result < 0) {
			setEventMessages($book->error, $book->errors, 'errors');
		} else {
			setEventMessages($langs->trans('Saved'), null, 'mesgs');
			$action = '';
		}
	}
}

else if ($action == "confirm_delete") {
	$book = new BookKeeping($db);

	$result = $book->fetch($id, null, $mode);
	$piece_num = $book->piece_num;

	if ($result < 0) {
		setEventMessages($book->error, $book->errors, 'errors');
	} else {
		$result = $book->delete($user, false, $mode);
		if ($result < 0) {
			setEventMessages($book->error, $book->errors, 'errors');
		}
	}
	$action = '';
}

else if ($action == "confirm_create") {
	$error = 0;

	$book = new BookKeeping($db);

	if (! GETPOST('next_num_mvt'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumPiece")), null, 'errors');
		$error++;
	}

	if (! $error)
	{
		$book->label_compte = '';
		$book->debit = 0;
		$book->credit = 0;
		$book->doc_date = $date_start = dol_mktime(0, 0, 0, GETPOST('doc_datemonth'), GETPOST('doc_dateday'), GETPOST('doc_dateyear'));
		$book->doc_type = GETPOST('doc_type');
		$book->piece_num = GETPOST('next_num_mvt');
		$book->doc_ref = GETPOST('doc_ref');
		$book->code_journal = GETPOST('code_journal');
		$book->fk_doc = 0;
		$book->fk_docdet = 0;
		$book->montant = 0;

		$result = $book->createStd($user,0, $mode);
		if ($result < 0) {
			setEventMessages($book->error, $book->errors, 'errors');
		} else {
			setEventMessages($langs->trans('Saved'), null, 'mesgs');
			$action = 'update';
			$id=$book->id;
			$piece_num = $book->piece_num;
		}
	}
}

if ($action == 'setdate') {
	$datedoc = dol_mktime(0, 0, 0, GETPOST('doc_datemonth'), GETPOST('doc_dateday'), GETPOST('doc_dateyear'));
	$result = $object->updateByMvt($piece_num,'doc_date',$db->idate($datedoc),$mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		setEventMessages($langs->trans('Saved'), null, 'mesgs');
		$action = '';
	}
}

if ($action == 'setjournal') {
	$journaldoc = trim(GETPOST('code_journal'));
	if (!empty($journaldoc)) {
		$journaldoc='\''.$journaldoc.'\'';
	}
	$result = $object->updateByMvt($piece_num,'code_journal',$journaldoc,$mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		setEventMessages($langs->trans('Saved'), null, 'mesgs');
		$action = '';
	}
}

if ($action == 'setdocref') {
	$refdoc = trim(GETPOST('doc_ref'));
	if (!empty($refdoc)) {
		$refdoc='\''.$refdoc.'\'';
	}
	$result = $object->updateByMvt(doc_ref,'code_journal',$refdoc,$mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		setEventMessages($langs->trans('Saved'), null, 'mesgs');
		$action = '';
	}
}

if ($action == 'valid') {
	$result = $object->transformTransaction(0,$piece_num);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		header("Location: list.php?sortfield=t.piece_num&sortorder=asc");
		exit;
	}
}

/*
 * View
 */
llxHeader();
$html = new Form($db);
$formaccounting = new FormAccounting($db);
$accountjournal = new AccountingJournal($db);

// Confirmation to delete the command
if ($action == 'delete') {
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id.'&mode='. $mode, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

if ($action == 'create') {
	print load_fiche_titre($langs->trans("CreateMvts"));

	$book = new BookKeeping($db);
	$next_num_mvt = $book->getNextNumMvt('_tmp');

	if (empty($next_num_mvt))
	{
		dol_print_error('', 'Failed to get next piece number');
	}

	print '<form action="' . $_SERVER["PHP_SELF"] . '" name="create_mvt" method="POST">';
	print '<input type="hidden" name="action" value="confirm_create">' . "\n";
	print '<input type="hidden" name="next_num_mvt" value="' . $next_num_mvt . '">' . "\n";
	print '<input type="hidden" name="mode" value="_tmp">' . "\n";

	dol_fiche_head();

	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("NumPiece") . '</td>';
	print '<td>' . $next_num_mvt . '</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans("Docdate") . '</td>';
	print '<td>';
	print $html->select_date('', 'doc_date', '', '', '', "create_mvt", 1, 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans("Codejournal") . '</td>';
	print '<td>' . $formaccounting->select_journal(GETPOST('code_journal'),'code_journal',0,0,array(),1,1) . '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>' . $langs->trans("Docref") . '</td>';
	print '<td><input type="text" class="minwidth200" name="doc_ref" value=""/></td>';
	print '</tr>';

	print '<tr>';
	print '<td>' . $langs->trans("Doctype") . '</td>';
	print '<td><input type="text" class="minwidth200 name="doc_type" value=""/></td>';
	print '</tr>';

	print '</table>';

	dol_fiche_end();

	print '<div align="center"><input type="submit" class="button" value="' . $langs->trans("Create") . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="' . $langs->trans("Cancel") . '" class="button" onclick="history.go(-1)" />';
	print '</div>';

	print '</form>';
} else {
	$book = new BookKeeping($db);
	$result = $book->fetchPerMvt($piece_num, $mode);
	if ($result < 0) {
		setEventMessages($book->error, $book->errors, 'errors');
	}
	if (! empty($book->piece_num)) {

		print load_fiche_titre($langs->trans("UpdateMvts"), '<a href="list.php">' . $langs->trans('BackToList') . '</a>');

		dol_fiche_head();

		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border" width="100%">';

		// account movement
		print '<tr class="pair">';
		print '<td class="titlefield">' . $langs->trans("NumMvts") . '</td>';
		print '<td>' . $book->piece_num . '</td>';
		print '</tr>';

		// date
		print '<tr class="impair"><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Docdate');
		print '</td>';
		if ($action != 'editdate')
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;piece_num='. $book->piece_num .'&amp;mode='. $mode .'">'.img_edit($langs->transnoentitiesnoconv('SetDate'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editdate') {
			print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $book->piece_num . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setdate">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			$form->select_date($book->doc_date ? $book->doc_date : - 1, 'doc_date', '', '', '', "setdate");
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
			} else {
		print $book->doc_date ? dol_print_date($book->doc_date, 'daytext') : '&nbsp;';
		}
		print '</td>';
		print '</tr>';
		//journal
		print '<tr class="pair"><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Codejournal');
		print '</td>';
		if ($action != 'editjournal')
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=editjournal&amp;piece_num='.$book->piece_num.'&amp;mode='. $mode .'">'.img_edit($langs->transnoentitiesnoconv('Edit'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editjournal') {
			print '<form name="setjournal" action="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $book->piece_num . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setjournal">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			print $formaccounting->select_journal($book->code_journal,'code_journal',0,0,array(),1,1);
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
		print $book->code_journal ;
		}
		print '</td>';
		print '</tr>';
		//docref
		print '<tr class="impair"><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Docref');
		print '</td>';
		if ($action != 'editdocref')
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=editdocref&amp;piece_num='.$book->piece_num.'&amp;mode='. $mode .'">'.img_edit($langs->transnoentitiesnoconv('Edit'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editdocref') {
			print '<form name="setdocref" action="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $book->piece_num . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setdocref">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			print '<input type="text" size="20" name="doc_ref" value="'.$book->doc_ref.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
		print $book->doc_ref ;
		}
		print '</td>';
		print '</tr>';
		//doctype
		print '<tr class="pair">';
		print '<td>' . $langs->trans("Doctype") . '</td>';
		print '<td>' . $book->doc_type . '</td>';
		print '</tr>';
		print '</table>';

		print '</div>';

		print '<div class="fichehalfright"><div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		// Validate
		print '<tr class="pair">';
		print '<td class="titlefield">' . $langs->trans("Status") . '</td>';
		print '<td>';
			if (empty($book->validated)) {
				print '<a href="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $line->rowid . '&action=enable">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
				print '</a>';
			} else {
				print '<a href="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $line->rowid . '&action=disable">';
				print img_picto($langs->trans("Activated"), 'switch_on');
				print '</a>';
			}
			print '</td>';
		print '</tr>';
		// check data
		print '<tr class="impair">';
		print '<td class="titlefield">' . $langs->trans("Control") . '</td>';
		/*
		if ($book->doc_type == 'customer_invoice')
		{
		 $sqlmid = 'SELECT rowid as ref';
			$sqlmid .= " FROM ".MAIN_DB_PREFIX."facture as fac";
			$sqlmid .= " WHERE fac.rowid=" . $book->fk_doc;
			dol_syslog("accountancy/bookkeeping/card.php::sqlmid=" . $sqlmid, LOG_DEBUG);
			$resultmid = $db->query($sqlmid);
			if ($resultmid) {
				$objmid = $db->fetch_object($resultmid);
				$invoicestatic = new Facture($db);
				$invoicestatic->fetch($objmid->ref);
				$ref=$langs->trans("Invoice").' '.$invoicestatic->getNomUrl(1);
			}
			else dol_print_error($db);
		}
		*/
		print '<td>' . $ref .'</td>';
		print "</table>\n";
		print '</div>';
		print '</div></div>';
		print '<div style="clear:both"></div>';
		print '<br>';

		$result = $book->fetchAllPerMvt($piece_num, $mode);
		if ($result < 0) {
			setEventMessages($book->error, $book->errors, 'errors');
		} else {

			print load_fiche_titre($langs->trans("ListeMvts"), '', '');

			print '<form action="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $book->piece_num . '" method="post">';
			print '<input type="hidden" name="doc_date" value="' . $book->doc_date . '">' . "\n";
			print '<input type="hidden" name="doc_type" value="' . $book->doc_type . '">' . "\n";
			print '<input type="hidden" name="doc_ref" value="' . $book->doc_ref . '">' . "\n";
			print '<input type="hidden" name="code_journal" value="' . $book->code_journal . '">' . "\n";
			print '<input type="hidden" name="fk_doc" value="' . $book->fk_doc . '">' . "\n";
			print '<input type="hidden" name="fk_docdet" value="' . $book->fk_docdet . '">' . "\n";
			print '<input type="hidden" name="mode" value="' . $mode . '">' . "\n";

			print "<table class=\"noborder\" width=\"100%\">";
			if (count($book->linesmvt) > 0) {

				$total_debit = 0;
				$total_credit = 0;

				print '<tr class="liste_titre">';

				print_liste_field_titre($langs->trans("AccountAccountingShort"));
				print_liste_field_titre($langs->trans("Subledger_account"));
				print_liste_field_titre($langs->trans("Labelcompte"));
				print_liste_field_titre($langs->trans("Labeloperation"));
				print_liste_field_titre($langs->trans("Debit"), "", "", "", "", 'align="center"');
				print_liste_field_titre($langs->trans("Credit"), "", "", "", "", 'align="center"');
				print_liste_field_titre($langs->trans("Action"), "", "", "", "", 'width="60" align="center"');

				print "</tr>\n";

				foreach ( $book->linesmvt as $line ) {
					print '<tr class="oddeven">';
					$total_debit += $line->debit;
					$total_credit += $line->credit;

					if ($action == 'update' && $line->id == $id) {
						print '<td>';
						print $formaccounting->select_account($line->numero_compte, 'account_number', 0, array (), 1, 1, '');
						print '</td>';
						print '<td>';
						print $formaccounting->select_auxaccount($line->subledger_account, 'subledger_account', 1);
						print '</td>';
						print '<td><input type="text" size="15" name="label_compte" value="' . $line->label_compte . '"/></td>';
						print '<td><input type="text" size="15" name="label_operation" value="' . $line->label_operation. '"/></td>';
						print '<td align="right"><input type="text" size="6" name="debit" value="' . price($line->debit) . '"/></td>';
						print '<td align="right"><input type="text" size="6" name="credit" value="' . price($line->credit) . '"/></td>';
						print '<td>';
						print '<input type="hidden" name="id" value="' . $line->id . '">' . "\n";
						print '<input type="submit" class="button" name="update" value="' . $langs->trans("Update") . '">';
						print '</td>';
					} else {
						print '<td>' . length_accountg($line->numero_compte) . '</td>';
						print '<td>' . length_accounta($line->subledger_account) . '</td>';
						print '<td>' . $line->label_compte . '</td>';
						print '<td>' . $line->label_operation. '</td>';
						print '<td align="right">' . price($line->debit) . '</td>';
						print '<td align="right">' . price($line->credit) . '</td>';

						print '<td align="center">';
						print '<a href="' . $_SERVER["PHP_SELF"] . '?action=update&amp;id=' . $line->id . '&amp;piece_num=' . $line->piece_num . '&amp;mode='.$mode.'">';
						print img_edit();
						print '</a>&nbsp;';
						print '<a href="' . $_SERVER["PHP_SELF"] . '?action=delete&amp;id=' . $line->id . '&amp;piece_num=' . $line->piece_num . '&amp;mode='.$mode.'">';
						print img_delete();

						print '</a>';
						print '</td>';
					}
					print "</tr>\n";
				}

				if ($total_debit != $total_credit)
				{
					setEventMessages(null, array($langs->trans('MvtNotCorrectlyBalanced', $total_credit, $total_debit)), 'warnings');
				}

				if ($action == "" || $action == 'add') {
					print '<tr class="oddeven">';
					print '<td>';
					print $formaccounting->select_account($account_number, 'account_number', 0, array (), 1, 1, '');
					print '</td>';
					print '<td>';
					print $formaccounting->select_auxaccount($subledger_account, 'subledger_account', 1);
					print '</td>';
					print '<td><input type="text" size="15" name="label_compte" value="' . $line->label_compte . '"/></td>';
					print '<td><input type="text" size="15" name="label_operation" value="' . $line->label_operation. '"/></td>';
					print '<td align="right"><input type="text" size="6" name="debit" value="' . price($debit) . '"/></td>';
					print '<td align="right"><input type="text" size="6" name="credit" value="' . price($credit) . '"/></td>';
					print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Add") . '"></td>';
					print '</tr>';
				}
				print '</table>';
				if ($mode=='_tmp' && $total_debit == $total_credit && $action=='') {
					print '<div class="tabsAction">';
					print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $book->piece_num . '&action=valid">'.$langs->trans("ValidTransaction").'</a>';
					print "</div>";
				}
				print '</form>';
			}
		}
	} else {
		print load_fiche_titre($langs->trans("NoRecords"));
	}
}

dol_fiche_end();
llxFooter();
$db->close();

<?php
/* Copyright (C) 2013-2016 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 * \file htdocs/accountancy/bookkeeping/card.php
 * \ingroup Advanced accountancy
 * \brief Page to show book-entry
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';

// Langs
$langs->load("accountancy");

// Security check
$id = GETPOST('id', 'int');
if ($user->societe_id > 0) {
	accessforbidden();
}

$action = GETPOST('action');
$piece_num = GETPOST("piece_num");

$mesg = '';

$account_number = GETPOST('account_number');
$code_tiers = GETPOST('code_tiers');
if ($code_tiers == - 1) {
	$code_tiers = null;
}
$label_compte = GETPOST('label_compte');
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

if ($action == "confirm_update") {

	$error = 0;

	if ((floatval($debit) != 0.0) && (floatval($credit) != 0.0)) {
		setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
		$error ++;
	}

	if (empty($error)) {
		$book = new BookKeeping($db);

		$result = $book->fetch($id);
		if ($result < 0) {
			setEventMessages($book->error, $book->errors, 'errors');
		} else {
			$book->numero_compte = $account_number;
			$book->code_tiers = $code_tiers;
			$book->label_compte = $label_compte;
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

			$result = $book->update($user);
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
		$book->code_tiers = $code_tiers;
		$book->label_compte = $label_compte;
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

		$result = $book->createStd($user);
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

	$result = $book->fetch($id);

	$piece_num = $book->piece_num;

	if ($result < 0) {
		setEventMessages($book->error, $book->errors, 'errors');
	} else {
		$result = $book->delete($user);
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
    
    	$result = $book->createStd($user);
    	if ($result < 0) {
    		setEventMessages($book->error, $book->errors, 'errors');
    	} else {
    		setEventMessages($langs->trans('Saved'), null, 'mesgs');
    		$action = '';
    		$piece_num = $book->piece_num;
    	}
	}
}


/*
 * View
 */

llxHeader();

$html = new Form($db);
$formventilation = new FormVentilation($db);

/*
 *  Confirmation to delete the command
 */
if ($action == 'delete') {
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

if ($action == 'create') {
	print load_fiche_titre($langs->trans("CreateMvts"));

	$code_journal_array = array (
			$conf->global->ACCOUNTING_SELL_JOURNAL => $conf->global->ACCOUNTING_SELL_JOURNAL,
			$conf->global->ACCOUNTING_PURCHASE_JOURNAL => $conf->global->ACCOUNTING_PURCHASE_JOURNAL,
			$conf->global->ACCOUNTING_SOCIAL_JOURNAL => $conf->global->ACCOUNTING_SOCIAL_JOURNAL,
			$conf->global->ACCOUNTING_MISCELLANEOUS_JOURNAL => $conf->global->ACCOUNTING_MISCELLANEOUS_JOURNAL,
			$conf->global->ACCOUNTING_EXPENSEREPORT_JOURNAL => $conf->global->ACCOUNTING_EXPENSEREPORT_JOURNAL
	);

	$sql = 'SELECT DISTINCT accountancy_journal FROM ' . MAIN_DB_PREFIX . 'bank_account WHERE clos=0';
	$resql = $db->query($sql);
	if (! $resql) {
		setEventMessages($db->lasterror, null, 'errors');
	} else {
		while ( $obj_bank = $db->fetch_object($resql) ) {
			if (! empty($obj_bank->accountancy_journal)) {
				$code_journal_array[$obj_bank->accountancy_journal] = $obj_bank->accountancy_journal;
			}
		}
	}

	$book = new BookKeeping($db);
	$next_num_mvt = $book->getNextNumMvt();
    if (empty($next_num_mvt))
    {
        dol_print_error('', 'Failed to get next piece number');
    }

	print '<form action="' . $_SERVER["PHP_SELF"] . '" name="create_mvt" method="POST">';
	print '<input type="hidden" name="action" value="confirm_create">' . "\n";
	print '<input type="hidden" name="next_num_mvt" value="' . $next_num_mvt . '">' . "\n";

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
	print '<td>' . $html->selectarray('code_journal', $code_journal_array) . '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>' . $langs->trans("Docref") . '</td>';
	print '<td><input type="text" class="minwidth200" name="doc_ref" value=""/></td>';
	print '</tr>';

	print '<tr>';
	print '<td>' . $langs->trans("Doctype") . '</td>';
	print '<td><input type="text" class="minwidth200" name="doc_type" value=""/></td>';
	print '</tr>';

	print '</table>';

	dol_fiche_end();

	print '<div align="center"><input type="submit" class="button" value="' . $langs->trans("Create") . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="' . $langs->trans("Cancel") . '" class="button" onclick="history.go(-1)" />';
	print '</div>';

	print '</form>';
} else {
	$book = new BookKeeping($db);
	$result = $book->fetchPerMvt($piece_num);
	if ($result < 0) {
		setEventMessages($book->error, $book->errors, 'errors');
	}
	if (! empty($book->piece_num)) {

		print load_fiche_titre($langs->trans("UpdateMvts"), '<a href="list.php">' . $langs->trans('BackToList') . '</a>');

		dol_fiche_head();
		
		print '<table class="border" width="100%">';
		print '<tr class="pair">';
		print '<td class="titlefield">' . $langs->trans("NumMvts") . '</td>';
		print '<td>' . $book->piece_num . '</td>';
		print '</tr>';
		print '<tr class="impair">';
		print '<td>' . $langs->trans("Docdate") . '</td>';
		print '<td>' . dol_print_date($book->doc_date, 'daytextshort') . '</td>';
		print '</tr>';
		print '<tr class="pair">';
		print '<td>' . $langs->trans("Codejournal") . '</td>';
		print '<td>' . $book->code_journal . '</td>';
		print '</tr>';
		print '<tr class="impair">';
		print '<td>' . $langs->trans("Docref") . '</td>';
		print '<td>' . $book->doc_ref . '</td>';
		print '</tr>';
		print '<tr class="pair">';
		print '<td>' . $langs->trans("Doctype") . '</td>';
		print '<td>' . $book->doc_type . '</td>';
		print '</tr>';
		print '</table>';
		
		dol_fiche_end();

		print '<br>';
		
		$result = $book->fetch_all_per_mvt($piece_num);
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

			$var=False;
			
			print "<table class=\"noborder\" width=\"100%\">";
			if (count($book->linesmvt) > 0) {

				$total_debit = 0;
				$total_credit = 0;

				print '<tr class="liste_titre">';

				print_liste_field_titre($langs->trans("AccountAccountingShort"));
				print_liste_field_titre($langs->trans("Code_tiers"));
				print_liste_field_titre($langs->trans("Labelcompte"));
				print_liste_field_titre($langs->trans("Debit"), "", "", "", "", 'align="right"');
				print_liste_field_titre($langs->trans("Credit"), "", "", "", "", 'align="right"');
				print_liste_field_titre($langs->trans("Amount"), "", "", "", "", 'align="right"');
				print_liste_field_titre($langs->trans("Sens"), "", "", "", "", 'align="center"');
				print_liste_field_titre($langs->trans("Action"), "", "", "", "", 'width="60" align="center"');

				print "</tr>\n";

				foreach ($book->linesmvt as $line) {
					$var = ! $var;
					print '<tr ' . $bc[$var] . '>';

					$total_debit += $line->debit;
					$total_credit += $line->credit;

					if ($action == 'update' && $line->id == $id) {

						print '<td>';
						print $formventilation->select_account($line->numero_compte, 'account_number', 0, array (), 1, 1, '');
						print '</td>';
						print '<td>';
						print $formventilation->select_auxaccount($line->code_tiers, 'code_tiers', 1);
						print '</td>';
						print '<td><input type="text" size="15" name="label_compte" value="' . $line->label_compte . '"/></td>';
						print '<td align="right"><input type="text" size="6" name="debit" value="' . price($line->debit) . '"/></td>';
						print '<td align="right"><input type="text" size="6" name="credit" value="' . price($line->credit) . '"/></td>';
						print '<td align="right">' . price($line->montant) . '</td>';
						print '<td align="center">' . $line->sens . '</td>';
						print '<td>';
						print '<input type="hidden" name="id" value="' . $line->id . '">' . "\n";
						print '<input type="submit" class="button" name="update" value="' . $langs->trans("Update") . '">';
						print '</td>';
					} else {
						print '<td>' . length_accountg($line->numero_compte) . '</td>';
						print '<td>' . length_accounta($line->code_tiers) . '</td>';
						print '<td>' . $line->label_compte . '</td>';
						print '<td align="right">' . price($line->debit) . '</td>';
						print '<td align="right">' . price($line->credit) . '</td>';
						print '<td align="right">' . price($line->montant) . '</td>';
						print '<td align="center">' . $line->sens . '</td>';

						print '<td align="center">';
						print '<a href="./card.php?action=update&amp;id=' . $line->id . '&amp;piece_num=' . $line->piece_num . '">';
						print img_edit();
						print '</a>&nbsp;';
						print '<a href="./card.php?action=delete&amp;id=' . $line->id . '&amp;piece_num=' . $line->piece_num . '">';
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
					$var = ! $var;
					print '<tr ' . $bc[$var] . '>';
					print '<td>';
					print $formventilation->select_account($account_number, 'account_number', 0, array (), 1, 1, '');
					print '</td>';
					print '<td>';
					print $formventilation->select_auxaccount($code_tiers, 'code_tiers', 1);
					print '</td>';
					print '<td><input type="text" size="15" name="label_compte" value="' . $label_compte . '"/></td>';
					print '<td align="right"><input type="text" size="6" name="debit" value="' . price($debit) . '"/></td>';
					print '<td align="right"><input type="text" size="6" name="credit" value="' . price($credit) . '"/></td>';
					print '<td></td>';
					print '<td></td>';
					print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Add") . '"></td>';
					print '</tr>';
				}
				print '</table>';
				print '</form>';
			}
		}
	} else {
		print load_fiche_titre($langs->trans("NoRecords"));
	}
}

llxFooter();
$db->close();

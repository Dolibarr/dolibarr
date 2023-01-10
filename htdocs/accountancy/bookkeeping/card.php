<?php
/* Copyright (C) 2013-2017  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2017  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2022  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022       Waël Almoman            <info@almoman.com>
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
 * \file		htdocs/accountancy/bookkeeping/card.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Page to show book-entry
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "bills", "compta"));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int'); // id of record
$mode = $mode = $action == 'create' ? "_tmp" : GETPOST('mode', 'aZ09'); // '' or '_tmp'
$piece_num = GETPOST("piece_num", 'int'); // id of transaction (several lines share the same transaction id)

$accountingaccount = new AccountingAccount($db);
$accountingjournal = new AccountingJournal($db);

$accountingaccount_number = GETPOST('accountingaccount_number', 'alphanohtml');
$accountingaccount->fetch(null, $accountingaccount_number, true);
$accountingaccount_label = $accountingaccount->label;

$journal_code = GETPOST('code_journal', 'alpha') ? GETPOST('code_journal', 'alpha') : "NULL";
$accountingjournal->fetch(null, $journal_code);
$journal_label = $accountingjournal->label;

$next_num_mvt = (int) GETPOST('next_num_mvt', 'alpha');
$doc_ref = (string) GETPOST('doc_ref', 'alpha');
$doc_date = (string) GETPOST('doc_date', 'alpha');
$doc_date = $doc_date = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));

$subledger_account = GETPOST('subledger_account', 'alphanohtml');
if ($subledger_account == -1) {
	$subledger_account = null;
}
$subledger_label = GETPOST('subledger_label', 'alphanohtml');

$label_operation = GETPOST('label_operation', 'alphanohtml');
$debit = price2num(GETPOST('debit', 'alpha'));
$credit = price2num(GETPOST('credit', 'alpha'));

$save = GETPOST('save', 'alpha');
if (!empty($save)) {
	$action = 'add';
}
$valid = GETPOST('validate', 'alpha');
if (!empty($valid)) {
	$action = 'valid';
}
$update = GETPOST('update', 'alpha');
if (!empty($update)) {
	$action = 'confirm_update';
}

$object = new BookKeeping($db);

// Security check
if (!isModEnabled('accounting')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
	accessforbidden();
}


/*
 * Actions
 */

if ($cancel) {
	header("Location: ".DOL_URL_ROOT.'/accountancy/bookkeeping/list.php');
	exit;
}

if ($action == "confirm_update") {
	$error = 0;

	if ((floatval($debit) != 0.0) && (floatval($credit) != 0.0)) {
		$error++;
		setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
		$action = 'update';
	}
	if (empty($accountingaccount_number) || $accountingaccount_number == '-1') {
		$error++;
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountAccountingShort")), null, 'errors');
		$action = 'update';
	}

	if (!$error) {
		$object = new BookKeeping($db);

		$result = $object->fetch($id, null, $mode);
		if ($result < 0) {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$object->numero_compte = $accountingaccount_number;
			$object->subledger_account = $subledger_account;
			$object->subledger_label = $subledger_label;
			$object->label_compte = $accountingaccount_label;
			$object->label_operation = $label_operation;
			$object->debit = $debit;
			$object->credit = $credit;

			if (floatval($debit) != 0.0) {
				$object->montant = $debit; // deprecated
				$object->amount = $debit;
				$object->sens = 'D';
			}
			if (floatval($credit) != 0.0) {
				$object->montant = $credit; // deprecated
				$object->amount = $credit;
				$object->sens = 'C';
			}

			$result = $object->update($user, false, $mode);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				if ($mode != '_tmp') {
					setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
				}

				$debit = 0;
				$credit = 0;

				$action = '';
			}
		}
	}
} elseif ($action == 'add' || $action == 'valid') {
	$error = 0;

	if (array_sum($debit) != array_sum($credit)) {
		$action = 'add';
	}

	foreach ($accountingaccount_number as $key => $value) {
		$accountingaccount->fetch(null, $accountingaccount_number[$key], true);
		$accountingaccount_label[$key] = $accountingaccount->label[$key];

		// if one added row is empty remove it before continue
		if ($key < 1 && (empty($accountingaccount_number[$key]) || $accountingaccount_number[$key] == '-1') || (floatval($debit[$key]) == 0.0) && (floatval($credit[$key]) == 0.0)) {
			continue;
		}

		if ((floatval($debit[$key]) != 0.0) && (floatval($credit[$key]) != 0.0)) {
			$error++;
			setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
			$action = '';
		}

		if (empty($accountingaccount_number[$key]) || $accountingaccount_number[$key] == '-1') {
			$error++;
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountAccountingShort")), null, 'errors');
			$action = '';
		}

		if (!$error) {
			$object = new BookKeeping($db);
			$object->numero_compte = $accountingaccount_number[$key];
			$object->subledger_account = $subledger_account[$key];
			$object->subledger_label = $subledger_label[$key];
			$object->label_compte = $accountingaccount_label[$key];
			$object->label_operation = $label_operation[$key];
			$object->debit = price2num($debit[$key]);
			$object->credit = price2num($credit[$key]);
			$object->doc_date = $doc_date;
			$object->doc_type = (string) GETPOST('doc_type', 'alpha');
			$object->piece_num = $piece_num;
			$object->doc_ref = $doc_ref;
			$object->code_journal = $journal_code;
			$object->journal_label = $journal_label;
			$object->fk_doc = GETPOSTINT('fk_doc');
			$object->fk_docdet = GETPOSTINT('fk_docdet');

			if (floatval($debit[$key]) != 0.0) {
				$object->montant = $object->debit; // deprecated
				$object->amount = $object->debit;
				$object->sens = 'D';
			}

			if (floatval($credit[$key]) != 0.0) {
				$object->montant = $object->credit; // deprecated
				$object->amount = $object->credit;
				$object->sens = 'C';
			}

			$result = $object->createStd($user, false, $mode);
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
	if (empty($error)) {
		if ($mode != '_tmp') {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
		$debit = 0;
		$credit = 0;

		$action = $action == 'add' ? '' : $action ; // stay in valid mode when not adding line
	}
} elseif ($action == "confirm_delete") {
	$object = new BookKeeping($db);

	$result = $object->fetch($id, null, $mode);
	$piece_num = $object->piece_num;

	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		$result = $object->delete($user, false, $mode);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	$action = '';
} elseif ($action == 'create') {
	$error = 0;

	$object = new BookKeeping($db);

	$next_num_mvt =  !empty($next_num_mvt) ? $next_num_mvt : $object->getNextNumMvt('_tmp');
	$doc_ref = !empty($doc_ref) ? $doc_ref : $next_num_mvt;

	if (empty($doc_date)) {
		$tmp_date = dol_getdate(dol_now());
		$_POST['doc_dateday'] =  $tmp_date['mday'];
		$_POST['doc_datemonth'] = $tmp_date['mon'];
		$_POST['doc_dateyear'] = $tmp_date['year'];
		unset($tmp_date);
	}

	if (!$journal_code || $journal_code == '-1') {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Journal")), null, 'errors');
		$action = 'create';
		$error++;
	}
	if (empty($doc_ref)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Piece")), null, 'errors');
		$action = 'create';
		$error++;
	}

	if (!$error) {
		$object->label_compte = '';
		$object->debit = 0;
		$object->credit = 0;
		$object->doc_date = $date_start = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));
		$object->doc_type = GETPOST('doc_type', 'alpha');
		$object->piece_num = $next_num_mvt;
		$object->doc_ref = $doc_ref;
		$object->code_journal = $journal_code;
		$object->journal_label = $journal_label;
		$object->fk_doc = 0;
		$object->fk_docdet = 0;
		$object->montant = 0; // deprecated
		$object->amount = 0;

		$result = $object->createStd($user, 0, $mode);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			if ($mode != '_tmp') {
				setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			}
			$action = '';
			$id = $object->id;
			$piece_num = $object->piece_num;
		}
	}
}

if ($action == 'setdate') {
	$datedoc = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));
	$result = $object->updateByMvt($piece_num, 'doc_date', $db->idate($datedoc), $mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		if ($mode != '_tmp') {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
		$action = '';
	}
}

if ($action == 'setjournal') {
	$result = $object->updateByMvt($piece_num, 'code_journal', $journal_code, $mode);
	$result = $object->updateByMvt($piece_num, 'journal_label', $journal_label, $mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		if ($mode != '_tmp') {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
		$action = '';
	}
}

if ($action == 'setdocref') {
	$refdoc = $doc_ref;
	$result = $object->updateByMvt($piece_num, 'doc_ref', $refdoc, $mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		if ($mode != '_tmp') {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
		$action = '';
	}
}

// Validate transaction
if ($action == 'valid') {
	$result = $object->transformTransaction(0, $piece_num);
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

$html = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $langs->trans($mode =="_tmp" ? "CreateMvts": "UpdateMvts");

llxHeader('', $title);

// Confirmation to delete the command
if ($action == 'delete') {
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$id.'&mode='.$mode, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}


$object = new BookKeeping($db);
$result = $object->fetchPerMvt($piece_num, $mode);
if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
}

if (!empty($object->piece_num)) {
	$backlink = '<a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>';

	print load_fiche_titre($langs->trans($mode =="_tmp" ? "CreateMvts": "UpdateMvts"), $backlink);

	print '<form action="'.$_SERVER["PHP_SELF"].'?piece_num='.$object->piece_num.'" method="post">';	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'" />';
	}
	$head = array();
	$h = 0;
	$head[$h][0] = $_SERVER['PHP_SELF'].'?piece_num='.$object->piece_num.($mode ? '&mode='.$mode : '');
	$head[$h][1] = $langs->trans("Transaction");
	$head[$h][2] = 'transaction';
	$h++;

	print dol_get_fiche_head($head, 'transaction', '', -1);

	//dol_banner_tab($object, '', $backlink);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	/*print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("NumPiece") . '</td>';
	print '<td>' . $next_num_mvt . '</td>';
	print '</tr>';*/

	print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Docdate").'</td>';
	print '<td>';
	print $html->selectDate($doc_date, 'doc_date', '', '', '', "create_mvt", 1, 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("Codejournal").'</td>';
	print '<td>'.$formaccounting->select_journal($journal_code, 'code_journal', 0, 0, 1, 1).'</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("Piece").'</td>';
	print '<td><input type="text" class="minwidth200" name="doc_ref" value="'.$doc_ref.'" /></td>';
	print '</tr>';

	/*
	print '<tr>';
	print '<td>' . $langs->trans("Doctype") . '</td>';
	print '<td><input type="text" class="minwidth200 name="doc_type" value="" /></td>';
	print '</tr>';
	*/

	print '</table>';

	print '</div>';

	print '<div class="fichehalfright">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	// Doc type
	if (!empty($object->doc_type)) {
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("Doctype").'</td>';
		print '<td>'.$object->doc_type.'</td>';
		print '</tr>';
	}

	// Date document creation
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("DateCreation").'</td>';
	print '<td>';
	print $object->date_creation ? dol_print_date($object->date_creation, 'day') : '&nbsp;';
	print '</td>';
	print '</tr>';

	// Don't show in tmp mode, inevitably empty
	if ($mode != "_tmp") {
		// Date document export
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("DateExport").'</td>';
		print '<td>';
		print $object->date_export ? dol_print_date($object->date_export, 'dayhour') : '&nbsp;';
		print '</td>';
		print '</tr>';

		// Date document validation
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("DateValidation").'</td>';
		print '<td>';
		print $object->date_validation ? dol_print_date($object->date_validation, 'dayhour') : '&nbsp;';
		print '</td>';
		print '</tr>';
	}
	// Validate
	/*
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("Status") . '</td>';
	print '<td>';
	if (empty($object->validated)) {
		print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $line->id . '&action=enable&token='.newToken().'">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a>';
	} else {
		print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $line->id . '&action=disable&token='.newToken().'">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</a>';
	}
	print '</td>';
	print '</tr>';
	*/

		// check data
	/*
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("Control") . '</td>';
	if ($object->doc_type == 'customer_invoice') {
		$sqlmid = 'SELECT rowid as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."facture as fac";
		$sqlmid .= " WHERE fac.rowid=" . ((int) $object->fk_doc);
		dol_syslog("accountancy/bookkeeping/card.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			$objmid = $db->fetch_object($resultmid);
			$invoicestatic = new Facture($db);
			$invoicestatic->fetch($objmid->ref);
			$ref=$langs->trans("Invoice").' '.$invoicestatic->getNomUrl(1);
		} else {
			dol_print_error($db);
		}
	}
	print '<td>' . $ref .'</td>';
	print '</tr>';
	*/
	print "</table>\n";

	print dol_get_fiche_end();

	print '<div style="clear:both"></div>';

	print '<br>';

	$result = $object->fetchAllPerMvt($piece_num, $mode);	// This load $object->linesmvt
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		// List of movements
		print load_fiche_titre($langs->trans("ListeMvts"), '', '');

		if ($optioncss != '') {
			print '<input type="hidden" name="optioncss" value="'.$optioncss.'" />';
		}

		print '<input type="hidden" name="token" value="'.newToken().'" />';
		print '<input type="hidden" name="doc_type" value="'.$object->doc_type.'" />'."\n";
		print '<input type="hidden" name="fk_doc" value="'.$object->fk_doc.'" />'."\n";
		print '<input type="hidden" name="fk_docdet" value="'.$object->fk_docdet.'" />'."\n";
		print '<input type="hidden" name="mode" value="'.$mode.'" />'."\n";

		if (count($object->linesmvt) > 0) {
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			$total_debit = 0;
			$total_credit = 0;

			// Don't show in tmp mode, inevitably empty
			if ($mode != "_tmp") {
				// Date document export
				print '<tr>';
				print '<td class="titlefield">' . $langs->trans("DateExport") . '</td>';
				print '<td>';
				print $object->date_export ? dol_print_date($object->date_export, 'dayhour') : '&nbsp;';
				print '</td>';
				print '</tr>';

				// Date document validation
				print '<tr>';
				print '<td class="titlefield">' . $langs->trans("DateValidation") . '</td>';
				print '<td>';
				print $object->date_validation ? dol_print_date($object->date_validation, 'dayhour') : '&nbsp;';
				print '</td>';
				print '</tr>';
			}

			print '<tr class="liste_titre">';

			print_liste_field_titre("AccountAccountingShort");
			print_liste_field_titre("SubledgerAccount");
			print_liste_field_titre("LabelOperation");
			print_liste_field_titre("AccountingDebit", "", "", "", "", 'class="right"');
			print_liste_field_titre("AccountingCredit", "", "", "", "", 'class="right"');
			if (empty($object->date_validation)) {
				print_liste_field_titre("Action", "", "", "", "", 'width="60"', "", "", 'center ');
			} else {
				print_liste_field_titre("");
			}

			print "</tr>\n";

			// In _tmp mode the first line is empty so we remove it
			if ($mode == "_tmp") {
				array_shift($object->linesmvt);
			}

			// Add an empty line at the end to be able to add transaction
			$line = new BookKeepingLine();
			$object->linesmvt[] = $line;

			// Add a second line empty line if there is not yet
			if (empty($object->linesmvt[1])) {
				$line = new BookKeepingLine();
				$object->linesmvt[] = $line;
			}

			$count_line = count($object->linesmvt);
			$num_line = 0;
			foreach ($object->linesmvt as $key => $line) {
				$num_line++;
				print '<tr class="oddeven" data-lineid="'.((int) $line->id).'">';
				$total_debit += $line->debit;
				$total_credit += $line->credit;

				if ($action == 'update' && $line->id == $id) {
					print '<!-- td columns in edit mode -->';
					print '<td>';
					print $formaccounting->select_account((GETPOSTISSET("accountingaccount_number") ? GETPOST("accountingaccount_number", "alpha") : $line->numero_compte), 'accountingaccount_number', 1, array(), 1, 1, '');
					print '</td>';
					print '<td>';
					// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
					// It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
					// Also, it is not possible to use a value that is not in the list.
					// Also, the label is not automatically filled when a value is selected.
					if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX)) {
						print $formaccounting->select_auxaccount((GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account), 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
					} else {
						print '<input type="text" class="maxwidth150" name="subledger_account" value="'.(GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account).'" placeholder="'.dol_escape_htmltag($langs->trans("SubledgerAccount")).'" />';
					}
					// Add also input for subledger label
					print '<br><input type="text" class="maxwidth150" name="subledger_label" value="'.(GETPOSTISSET("subledger_label") ? GETPOST("subledger_label", "alpha") : $line->subledger_label).'" placeholder="'.dol_escape_htmltag($langs->trans("SubledgerAccountLabel")).'" />';
					print '</td>';
					print '<td><input type="text" class="minwidth200" name="label_operation" value="'.(GETPOSTISSET("label_operation") ? GETPOST("label_operation", "alpha") : $line->label_operation).'" /></td>';
					print '<td class="right"><input type="text" size="6" class="right" name="debit" value="'.(GETPOSTISSET("debit") ? GETPOST("debit", "alpha") : price($line->debit)).'" /></td>';
					print '<td class="right"><input type="text" size="6" class="right" name="credit" value="'.(GETPOSTISSET("credit") ? GETPOST("credit", "alpha") : price($line->credit)).'" /></td>';
					print '<td>';
					print '<input type="hidden" name="id" value="'.$line->id.'" />'."\n";
					print '<input type="submit" class="button" name="update" value="'.$langs->trans("Update").'" />';
					print '</td>';
				} elseif (empty($line->numero_compte) || (empty($line->debit) && empty($line->credit))) {
					if ($action == "" || $action == 'add') {
						print '<!-- td columns in add mode -->';
						print '<td>';
						print $formaccounting->select_account((is_array($accountingaccount_number) ? $accountingaccount_number[$key] : $accountingaccount_number ), 'accountingaccount_number['.$key.']', 1, array(), 1, 1, '');
						print '</td>';
						print '<td>';
						// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
						// It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
						// Also, it is not possible to use a value that is not in the list.
						// Also, the label is not automatically filled when a value is selected.
						if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX)) {
							print $formaccounting->select_auxaccount((is_array($subledger_account) ? $subledger_account[$key] : $subledger_account ), 'subledger_account['.$key.']', 1, 'maxwidth250', '', 'subledger_label');
						} else {
							print '<input type="text" class="maxwidth150" name="subledger_account['.$key.']" value="' . (is_array($subledger_account) ? $subledger_account[$key] : $subledger_account ) . '" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccount")) . '" />';
						}
						print '<br><input type="text" class="maxwidth150" name="subledger_label['.$key.']" value="' . (is_array($subledger_label) ? $subledger_label[$key] : $subledger_label ) . '" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccountLabel")) . '" />';
						print '</td>';
						print '<td><input type="text" class="minwidth200" name="label_operation['.$key.']" value="' . (is_array($label_operation) ? $label_operation[$key] : $label_operation ) . '"/></td>';
						print '<td class="right"><input type="text" size="6" class="right" name="debit['.$key.']" value="' . (is_array($debit) ? $debit[$key] : $debit ) . '" /></td>';
						print '<td class="right"><input type="text" size="6" class="right" name="credit['.$key.']" value="' . (is_array($credit) ? $credit[$key] : $credit ) . '" /></td>';
						// Add button should not appear twice
						if ($num_line === $count_line) {
							print '<td><input type="submit" class="button small" name="save" value="' . $langs->trans("Add") . '" /></td>';
						} else {
							print '<td class="right"></td>';
						}
					}
				} else {
					print '<!-- td columns in display mode -->';
					$resultfetch = $accountingaccount->fetch(null, $line->numero_compte, true);
					print '<td>';
					if ($resultfetch > 0) {
						print $accountingaccount->getNomUrl(0, 1, 1, '', 0);
					} else {
						print $line->numero_compte.' <span class="warning">('.$langs->trans("AccountRemovedFromCurrentChartOfAccount").')</span>';
					}
					print '</td>';
					print '<td>'.length_accounta($line->subledger_account);
					if ($line->subledger_label) {
						print ' - <span class="opacitymedium">'.$line->subledger_label.'</span>';
					}
					print '</td>';
					print '<td>'.$line->label_operation.'</td>';
					print '<td class="right nowraponall amount">'.($line->debit != 0 ? price($line->debit) : '').'</td>';
					print '<td class="right nowraponall amount">'.($line->credit != 0 ? price($line->credit) : '').'</td>';

					print '<td class="center nowraponall">';
					if (empty($line->date_export) && empty($line->date_validation)) {
						print '<a class="editfielda reposition" href="' . $_SERVER["PHP_SELF"] . '?action=update&id=' . $line->id . '&piece_num=' . urlencode($line->piece_num) . '&mode=' . urlencode($mode) . '&token=' . urlencode(newToken()) . '">';
						print img_edit('', 0, 'class="marginrightonly"');
						print '</a> &nbsp;';
					} else {
						print '<a class="editfielda nohover cursornotallowed reposition disabled" href="#" title="'.dol_escape_htmltag($langs->trans("ForbiddenTransactionAlreadyExported")).'">';
						print img_edit($langs->trans("ForbiddenTransactionAlreadyExported"), 0, 'class="marginrightonly"');
						print '</a> &nbsp;';
					}

					if (empty($line->date_validation)) {
						$actiontodelete = 'delete';
						if ($mode == '_tmp' || $action != 'delmouv') {
							$actiontodelete = 'confirm_delete';
						}

						print '<a href="' . $_SERVER["PHP_SELF"] . '?action=' . $actiontodelete . '&id=' . $line->id . '&piece_num=' . urlencode($line->piece_num) . '&mode=' . urlencode($mode) . '&token=' . urlencode(newToken()) . '">';
						print img_delete();
						print '</a>';
					} else {
						print '<a class="editfielda nohover cursornotallowed disabled" href="#" title="'.dol_escape_htmltag($langs->trans("ForbiddenTransactionAlreadyExported")).'">';
						print img_delete($langs->trans("ForbiddenTransactionAlreadyValidated"));
						print '</a>';
					}

					print '</td>';
				}
				print "</tr>\n";
			}

			$total_debit = price2num($total_debit, 'MT');
			$total_credit = price2num($total_credit, 'MT');

			if ($total_debit != $total_credit) {
				setEventMessages(null, array($langs->trans('MvtNotCorrectlyBalanced', $total_debit, $total_credit)), 'warnings');
			}

			print '</table>';
			print '</div>';

			if ($mode == '_tmp' && $action == '') {
				print '<br>';
				print '<div class="center">';
				if ($total_debit == $total_credit) {
					print '<input type="submit" class="button" name="validate" value="' . $langs->trans("ValidTransaction") . '" />';
				} else {
					print '<input type="submit" class="button" disabled="disabled" href="#" title="'.dol_escape_htmltag($langs->trans("MvtNotCorrectlyBalanced", $debit, $credit)).'" value="'.dol_escape_htmltag($langs->trans("ValidTransaction")).'" />';
				}

				print ' &nbsp; ';
				print '<a class="button button-cancel" href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/list.php">'.$langs->trans("Cancel").'</a>';

				print "</div>";
			}
		}

		print '</form>';
	}
} else {
	print load_fiche_titre($langs->trans("NoRecords"));
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();

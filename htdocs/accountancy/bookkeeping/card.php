<?php
/* Copyright (C) 2013-2017  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2017  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2018  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
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
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int'); // id of record
$mode = GETPOST('mode', 'aZ09'); // '' or '_tmp'
$piece_num = GETPOST("piece_num", 'int'); // id of transaction (several lines share the same transaction id)

// Security check
if ($user->socid > 0) {
	accessforbidden();
}

$mesg = '';

$accountingaccount = new AccountingAccount($db);
$accountingjournal = new AccountingJournal($db);

$accountingaccount_number = GETPOST('accountingaccount_number', 'alphanohtml');
$accountingaccount->fetch(null, $accountingaccount_number, true);
$accountingaccount_label = $accountingaccount->label;

$journal_code = GETPOST('code_journal', 'alpha');
$accountingjournal->fetch(null, $journal_code);
$journal_label = $accountingjournal->label;

$subledger_account = GETPOST('subledger_account', 'alphanohtml');
if ($subledger_account == - 1) {
	$subledger_account = null;
}
$label_operation = GETPOST('label_operation', 'alphanohtml');
$debit = price2num(GETPOST('debit', 'alpha'));
$credit = price2num(GETPOST('credit', 'alpha'));

$save = GETPOST('save', 'alpha');
if (!empty($save)) $action = 'add';
$update = GETPOST('update', 'alpha');
if (!empty($update)) $action = 'confirm_update';

$object = new BookKeeping($db);


/*
 * Actions
 */

if ($action == "confirm_update") {
	$error = 0;

	if ((floatval($debit) != 0.0) && (floatval($credit) != 0.0)) {
		$error++;
		setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
		$action = 'update';
	}
	if (empty($accountingaccount_number) || $accountingaccount_number == '-1')
	{
		$error++;
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountAccountingShort")), null, 'errors');
		$action = 'update';
	}

	if (!$error)
	{
		$object = new BookKeeping($db);

		$result = $object->fetch($id, null, $mode);
		if ($result < 0) {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$object->numero_compte = $accountingaccount_number;
			$object->subledger_account = $subledger_account;
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
} elseif ($action == "add") {
	$error = 0;

	if ((floatval($debit) != 0.0) && (floatval($credit) != 0.0))
	{
		$error++;
		setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
		$action = '';
	}
	if (empty($accountingaccount_number) || $accountingaccount_number == '-1')
	{
		$error++;
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountAccountingShort")), null, 'errors');
		$action = '';
	}

	if (!$error) {
		$object = new BookKeeping($db);

		$object->numero_compte = $accountingaccount_number;
		$object->subledger_account = $subledger_account;
		$object->label_compte = $accountingaccount_label;
		$object->label_operation = $label_operation;
		$object->debit = $debit;
		$object->credit = $credit;
		$object->doc_date = (string) GETPOST('doc_date', 'alpha');
		$object->doc_type = (string) GETPOST('doc_type', 'alpha');
		$object->piece_num = $piece_num;
		$object->doc_ref = (string) GETPOST('doc_ref', 'alpha');
		$object->code_journal = $journal_code;
		$object->journal_label = $journal_label;
		$object->fk_doc = GETPOSTINT('fk_doc');
		$object->fk_docdet = GETPOSTINT('fk_docdet');

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

		$result = $object->createStd($user, false, $mode);
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
} elseif ($action == "confirm_create") {
	$error = 0;

	$object = new BookKeeping($db);

	if (!$journal_code || $journal_code == '-1') {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Journal")), null, 'errors');
		$action = 'create';
		$error++;
	}
	if (!GETPOST('next_num_mvt', 'alpha'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumPiece")), null, 'errors');
		$error++;
	}

	if (!$error)
	{
		$object->label_compte = '';
		$object->debit = 0;
		$object->credit = 0;
		$object->doc_date = $date_start = dol_mktime(0, 0, 0, GETPOST('doc_datemonth', 'int'), GETPOST('doc_dateday', 'int'), GETPOST('doc_dateyear', 'int'));
		$object->doc_type = GETPOST('doc_type', 'alpha');
		$object->piece_num = GETPOST('next_num_mvt', 'alpha');
		$object->doc_ref = GETPOST('doc_ref', 'alpha');
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
			if ($mode != '_tmp')
			{
				setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			}
			$action = 'update';
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
		if ($mode != '_tmp')
		{
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
		if ($mode != '_tmp')
		{
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
		$action = '';
	}
}

if ($action == 'setdocref') {
	$refdoc = GETPOST('doc_ref', 'alpha');
	$result = $object->updateByMvt($piece_num, 'doc_ref', $refdoc, $mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		if ($mode != '_tmp')
		{
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

llxHeader('', $langs->trans("CreateMvts"));

// Confirmation to delete the command
if ($action == 'delete') {
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$id.'&mode='.$mode, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

if ($action == 'create')
{
	print load_fiche_titre($langs->trans("CreateMvts"));

	$object = new BookKeeping($db);
	$next_num_mvt = $object->getNextNumMvt('_tmp');

	if (empty($next_num_mvt))
	{
		dol_print_error('', 'Failed to get next piece number');
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" name="create_mvt" method="POST">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="confirm_create">'."\n";
	print '<input type="hidden" name="next_num_mvt" value="'.$next_num_mvt.'">'."\n";
	print '<input type="hidden" name="mode" value="_tmp">'."\n";

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	/*print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("NumPiece") . '</td>';
	print '<td>' . $next_num_mvt . '</td>';
	print '</tr>';*/

	print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Docdate").'</td>';
	print '<td>';
	print $html->selectDate('', 'doc_date', '', '', '', "create_mvt", 1, 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("Codejournal").'</td>';
	print '<td>'.$formaccounting->select_journal($journal_code, 'code_journal', 0, 0, 1, 1).'</td>';
	print '</tr>';

	print '<tr>';
	print '<td>'.$langs->trans("Piece").'</td>';
	print '<td><input type="text" class="minwidth200" name="doc_ref" value="'.GETPOST('doc_ref', 'alpha').'"></td>';
	print '</tr>';

	/*
	print '<tr>';
	print '<td>' . $langs->trans("Doctype") . '</td>';
	print '<td><input type="text" class="minwidth200 name="doc_type" value=""/></td>';
	print '</tr>';
	*/

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" value="'.$langs->trans("Cancel").'" class="button button-cancel" onclick="history.go(-1)" />';
	print '</div>';

	print '</form>';
} else {
	$object = new BookKeeping($db);
	$result = $object->fetchPerMvt($piece_num, $mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	if (!empty($object->piece_num))
	{
		$backlink = '<a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>';

		print load_fiche_titre($langs->trans("UpdateMvts"), $backlink);

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

		// Account movement
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("NumMvts").'</td>';
		print '<td>'.$object->piece_num.'</td>';
		print '</tr>';

		// Date
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Docdate');
		print '</td>';
		if ($action != 'editdate')
		print '<td class="right"><a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;piece_num='.$object->piece_num.'&amp;mode='.$mode.'">'.img_edit($langs->transnoentitiesnoconv('SetDate'), 1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editdate') {
			print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?piece_num='.$object->piece_num.'" method="post">';
			if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setdate">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			print $form->selectDate($object->doc_date ? $object->doc_date : - 1, 'doc_date', '', '', '', "setdate");
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			print $object->doc_date ? dol_print_date($object->doc_date, 'day') : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Journal
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Codejournal');
		print '</td>';
		if ($action != 'editjournal')
		print '<td class="right"><a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=editjournal&amp;piece_num='.$object->piece_num.'&amp;mode='.$mode.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editjournal') {
			print '<form name="setjournal" action="'.$_SERVER["PHP_SELF"].'?piece_num='.$object->piece_num.'" method="post">';
			if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setjournal">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			print $formaccounting->select_journal($object->code_journal, 'code_journal', 0, 0, array(), 1, 1);
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			print $object->code_journal;
		}
		print '</td>';
		print '</tr>';

		// Ref document
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Piece');
		print '</td>';
		if ($action != 'editdocref')
		print '<td class="right"><a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=editdocref&amp;piece_num='.$object->piece_num.'&amp;mode='.$mode.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editdocref') {
			print '<form name="setdocref" action="'.$_SERVER["PHP_SELF"].'?piece_num='.$object->piece_num.'" method="post">';
			if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setdocref">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			print '<input type="text" size="20" name="doc_ref" value="'.dol_escape_htmltag($object->doc_ref).'">';
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			print $object->doc_ref;
		}
		print '</td>';
		print '</tr>';

		print '</table>';

		print '</div>';

		print '<div class="fichehalfright"><div class="ficheaddleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		// Doc type
		if (!empty($object->doc_type))
		{
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

		// Validate
		/*
		print '<tr>';
		print '<td class="titlefield">' . $langs->trans("Status") . '</td>';
		print '<td>';
			if (empty($object->validated)) {
				print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $line->id . '&action=enable">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
				print '</a>';
			} else {
				print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?piece_num=' . $line->id . '&action=disable">';
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
		if ($object->doc_type == 'customer_invoice')
		{
		 $sqlmid = 'SELECT rowid as ref';
			$sqlmid .= " FROM ".MAIN_DB_PREFIX."facture as fac";
			$sqlmid .= " WHERE fac.rowid=" . $object->fk_doc;
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
		print '<td>' . $ref .'</td>';
		print '</tr>';
		*/
		print "</table>\n";

		print '</div></div><!-ee-->';

		print dol_get_fiche_end();

		print '<div style="clear:both"></div>';

		print '<br>';

		$result = $object->fetchAllPerMvt($piece_num, $mode);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			print load_fiche_titre($langs->trans("ListeMvts"), '', '');

			print '<form action="'.$_SERVER["PHP_SELF"].'?piece_num='.$object->piece_num.'" method="post">';
			if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="doc_date" value="'.$object->doc_date.'">'."\n";
			print '<input type="hidden" name="doc_type" value="'.$object->doc_type.'">'."\n";
			print '<input type="hidden" name="doc_ref" value="'.$object->doc_ref.'">'."\n";
			print '<input type="hidden" name="code_journal" value="'.$object->code_journal.'">'."\n";
			print '<input type="hidden" name="fk_doc" value="'.$object->fk_doc.'">'."\n";
			print '<input type="hidden" name="fk_docdet" value="'.$object->fk_docdet.'">'."\n";
			print '<input type="hidden" name="mode" value="'.$mode.'">'."\n";

			print "<table class=\"noborder\" width=\"100%\">";
			if (count($object->linesmvt) > 0) {
				$total_debit = 0;
				$total_credit = 0;

				print '<tr class="liste_titre">';

				print_liste_field_titre("AccountAccountingShort");
				print_liste_field_titre("SubledgerAccount");
				print_liste_field_titre("LabelOperation");
				print_liste_field_titre("Debit", "", "", "", "", 'class="right"');
				print_liste_field_titre("Credit", "", "", "", "", 'class="right"');
				print_liste_field_titre("Action", "", "", "", "", 'width="60" class="center"');

				print "</tr>\n";

				foreach ($object->linesmvt as $line) {
					print '<tr class="oddeven">';
					$total_debit += $line->debit;
					$total_credit += $line->credit;

					if ($action == 'update' && $line->id == $id) {
						print '<td>';
						print $formaccounting->select_account((GETPOSTISSET("accountingaccount_number") ? GETPOST("accountingaccount_number", "alpha") : $line->numero_compte), 'accountingaccount_number', 1, array(), 1, 1, '');
						print '</td>';
						print '<td>';
						// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
						// use setup of keypress to select thirdparty and this hang browser on large database.
						if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX))
						{
							print $formaccounting->select_auxaccount((GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account), 'subledger_account', 1);
						} else {
							print '<input type="text" class="maxwidth150" name="subledger_account" value="'.(GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account).'">';
						}
						print '</td>';
						print '<td><input type="text" class="minwidth200" name="label_operation" value="'.(GETPOSTISSET("label_operation") ? GETPOST("label_operation", "alpha") : $line->label_operation).'"></td>';
						print '<td class="right"><input type="text" size="6" class="right" name="debit" value="'.(GETPOSTISSET("debit") ? GETPOST("debit", "alpha") : price($line->debit)).'"></td>';
						print '<td class="right"><input type="text" size="6" class="right" name="credit" value="'.(GETPOSTISSET("credit") ? GETPOST("credit", "alpha") : price($line->credit)).'"></td>';
						print '<td>';
						print '<input type="hidden" name="id" value="'.$line->id.'">'."\n";
						print '<input type="submit" class="button" name="update" value="'.$langs->trans("Update").'">';
						print '</td>';
					} else {
						$accountingaccount->fetch(null, $line->numero_compte, true);
						print '<td>'.$accountingaccount->getNomUrl(0, 1, 1, '', 0).'</td>';
						print '<td>'.length_accounta($line->subledger_account).'</td>';
						print '<td>'.$line->label_operation.'</td>';
						print '<td class="nowrap right">'.price($line->debit).'</td>';
						print '<td class="nowrap right">'.price($line->credit).'</td>';

						print '<td class="center">';
						print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=update&id='.$line->id.'&piece_num='.$line->piece_num.'&mode='.$mode.'">';
						print img_edit('', 0, 'class="marginrightonly"');
						print '</a> &nbsp;';

						$actiontodelete = 'delete';
						if ($mode == '_tmp' || $action != 'delmouv') $actiontodelete = 'confirm_delete';

						print '<a href="'.$_SERVER["PHP_SELF"].'?action='.$actiontodelete.'&id='.$line->id.'&piece_num='.$line->piece_num.'&mode='.$mode.'">';
						print img_delete();

						print '</a>';
						print '</td>';
					}
					print "</tr>\n";
				}

				$total_debit = price2num($total_debit, 'MT');
				$total_credit = price2num($total_credit, 'MT');

				if ($total_debit != $total_credit)
				{
					setEventMessages(null, array($langs->trans('MvtNotCorrectlyBalanced', $total_debit, $total_credit)), 'warnings');
				}

				if ($action == "" || $action == 'add') {
					print '<tr class="oddeven">';
					print '<td>';
					print $formaccounting->select_account('', 'accountingaccount_number', 1, array(), 1, 1, '');
					print '</td>';
					print '<td>';
					// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
					// use setup of keypress to select thirdparty and this hang browser on large database.
					if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX))
					{
						print $formaccounting->select_auxaccount('', 'subledger_account', 1);
					} else {
						print '<input type="text" class="maxwidth150" name="subledger_account" value="">';
					}
					print '</td>';
					print '<td><input type="text" class="minwidth200" name="label_operation" value="'.$label_operation.'"/></td>';
					print '<td class="right"><input type="text" size="6" class="right" name="debit" value=""/></td>';
					print '<td class="right"><input type="text" size="6" class="right" name="credit" value=""/></td>';
					print '<td><input type="submit" class="button" name="save" value="'.$langs->trans("Add").'"></td>';
					print '</tr>';
				}
				print '</table>';


				if ($mode == '_tmp' && $action == '')
				{
					print '<br>';
					print '<div class="center">';
					if ($total_debit == $total_credit)
					{
						print '<a class="button" href="'.$_SERVER["PHP_SELF"].'?piece_num='.$object->piece_num.'&action=valid">'.$langs->trans("ValidTransaction").'</a>';
					} else {
						print '<input type="submit" class="button" disabled="disabled" href="#" title="'.dol_escape_htmltag($langs->trans("MvtNotCorrectlyBalanced", $debit, $credit)).'" value="'.dol_escape_htmltag($langs->trans("ValidTransaction")).'">';
					}

					print ' &nbsp; ';
					print '<a class="button button-cancel" href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/list.php">'.$langs->trans("Cancel").'</a>';

					print "</div>";
				}
				print '</form>';
			}
		}
	} else {
		print load_fiche_titre($langs->trans("NoRecords"));
	}
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();

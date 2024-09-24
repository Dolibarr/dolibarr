<?php
/* Copyright (C) 2013-2017  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2017  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2024  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
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
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/lettering.class.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "bills", "compta"));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOSTINT('id'); // id of record
$mode = GETPOST('mode', 'aZ09'); // '' or '_tmp'
$piece_num = GETPOSTINT("piece_num") ? GETPOSTINT("piece_num") : GETPOST('ref'); 	// id of transaction (several lines share the same transaction id)

$accountingaccount = new AccountingAccount($db);
$accountingjournal = new AccountingJournal($db);

$accountingaccount_number = GETPOST('accountingaccount_number', 'alphanohtml');
$accountingaccount->fetch(0, $accountingaccount_number, true);
$accountingaccount_label = $accountingaccount->label;

$journal_code = GETPOST('code_journal', 'alpha');
$accountingjournal->fetch(0, $journal_code);
$journal_label = $accountingjournal->label;

$subledger_account = GETPOST('subledger_account', 'alphanohtml');
if ($subledger_account == -1) {
	$subledger_account = null;
}
$subledger_label = GETPOST('subledger_label', 'alphanohtml');

$label_operation = GETPOST('label_operation', 'alphanohtml');
$debit = (float) price2num(GETPOST('debit', 'alpha'));
$credit = (float) price2num(GETPOST('credit', 'alpha'));

$save = GETPOST('save', 'alpha');
if (!empty($save)) {
	$action = 'add';
}
$update = GETPOST('update', 'alpha');
if (!empty($update)) {
	$action = 'confirm_update';
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('bookkeepingcard', 'globalcard'));

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

$permissiontoadd = $user->hasRight('accounting', 'mouvements', 'creer');
$permissiontodelete = $user->hasRight('accounting', 'mouvements', 'supprimer');


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	if ($cancel) {
		header("Location: ".DOL_URL_ROOT.'/accountancy/bookkeeping/list.php');
		exit;
	}

	if ($action == "confirm_update" && $permissiontoadd) {
		$error = 0;

		if (((float) $debit != 0.0) && ((float) $credit != 0.0)) {
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

				if ((float) $debit != 0.0) {
					$object->montant = $debit; // deprecated
					$object->amount = $debit;
					$object->sens = 'D';
				}
				if ((float) $credit != 0.0) {
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
	} elseif ($action == "add" && $permissiontoadd) {
		$error = 0;

		if (((float) $debit != 0.0) && ((float) $credit != 0.0)) {
			$error++;
			setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
			$action = '';
		}
		if (empty($accountingaccount_number) || $accountingaccount_number == '-1') {
			$error++;
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountAccountingShort")), null, 'errors');
			$action = '';
		}

		if (!$error) {
			if (GETPOSTINT('doc_datemonth') && GETPOSTINT('doc_dateday') && GETPOSTINT('doc_dateyear')) {
				$datedoc = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));
			} else {
				$datedoc = (int) GETPOSTINT('doc_date');	// TODO Use instead the mode day-month-year
			}

			$object = new BookKeeping($db);

			$object->numero_compte = $accountingaccount_number;
			$object->subledger_account = $subledger_account;
			$object->subledger_label = $subledger_label;
			$object->label_compte = $accountingaccount_label;
			$object->label_operation = $label_operation;
			$object->debit = $debit;
			$object->credit = $credit;
			$object->doc_date = $datedoc;
			$object->doc_type = (string) GETPOST('doc_type', 'alpha');
			$object->piece_num = $piece_num;
			$object->doc_ref = (string) GETPOST('doc_ref', 'alpha');
			$object->code_journal = $journal_code;
			$object->journal_label = $journal_label;
			$object->fk_doc = GETPOSTINT('fk_doc');
			$object->fk_docdet = GETPOSTINT('fk_docdet');

			if ((float) $debit != 0.0) {
				$object->montant = $debit; // deprecated
				$object->amount = $debit;
				$object->sens = 'D';
			}

			if ((float) $credit != 0.0) {
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
	} elseif ($action == "confirm_delete" && $permissiontoadd) {	// Delete line
		$object = new BookKeeping($db);

		$result = $object->fetch($id, null, $mode);
		$piece_num = (int) $object->piece_num;

		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');

			$action = 'create';
		} else {
			$result = $object->delete($user, 0, $mode);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		$action = '';
	} elseif ($action == "confirm_create" && $permissiontoadd) {
		$error = 0;

		$object = new BookKeeping($db);

		if (!$journal_code || $journal_code == '-1') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Journal")), null, 'errors');
			$action = 'create';
			$error++;
		}
		if (!GETPOST('doc_ref', 'alpha')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Piece")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (!$error) {
			$date_start = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));

			$object->label_compte = '';
			$object->debit = 0;
			$object->credit = 0;
			$object->doc_date = $date_start;
			$object->doc_type = GETPOST('doc_type', 'alpha');
			$object->piece_num = GETPOSTINT('next_num_mvt');
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

				$action = 'create';
			} else {
				$reshook = $hookmanager->executeHooks('afterCreateBookkeeping', $parameters, $object, $action);

				if ($mode != '_tmp') {
					setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
				}
				$action = '';
				$id = $object->id;
				$piece_num = (int) $object->piece_num;
			}
		}
	}

	if ($action == 'setdate' && $permissiontoadd) {
		$datedoc = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));
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

	if ($action == 'setjournal' && $permissiontoadd) {
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

	if ($action == 'setdocref' && $permissiontoadd) {
		$refdoc = GETPOST('doc_ref', 'alpha');
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
	if ($action == 'valid' && $permissiontoadd) {
		$result = $object->transformTransaction(0, $piece_num);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			header("Location: list.php?sortfield=t.piece_num&sortorder=asc");
			exit;
		}
	}

	// Delete all lines into the transaction
	$toselect = explode(',', GETPOST('toselect', 'alphanohtml'));

	if ($action == 'deletebookkeepingwriting' && $confirm == "yes" && $permissiontodelete) {
		$db->begin();

		if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
			$lettering = new Lettering($db);
			$nb_lettering = $lettering->bookkeepingLetteringAll($toselect, true);
			if ($nb_lettering < 0) {
				setEventMessages('', $lettering->errors, 'errors');
				$error++;
			}
		}

		$nbok = 0;
		$result = 0;
		if (!$error) {
			foreach ($toselect as $toselectid) {
				$result = $object->fetch($toselectid);
				if ($result >= 0 && (!isset($object->date_validation) || $object->date_validation === '')) {
					$result = $object->deleteMvtNum($object->piece_num);
					if ($result >= 0) {
						$nbok += $result;
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
						break;
					}
				} elseif ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
					break;
				} elseif (isset($object->date_validation) && $object->date_validation != '') {
					setEventMessages($langs->trans("ValidatedRecordWhereFound"), null, 'errors');
					$error++;
					break;
				}
			}
		}

		if (!$error) {
			$db->commit();

			// Message for elements well deleted
			if ($nbok > 1) {
				setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
			} elseif ($nbok > 0) {
				setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
			}

			header("Location: ".DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?noreset=1');
			exit;
		} else {
			$db->rollback();
		}
	}
}



/*
 * View
 */

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $langs->trans("CreateMvts");
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-consultation page-card');

// Confirmation to delete the command
if ($action == 'delete') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id.'&mode='.$mode, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'confirm_delete', '', 0, 1);
	print $formconfirm;
}

if ($action == 'create') {
	print load_fiche_titre($title);

	$object = new BookKeeping($db);
	$next_num_mvt = $object->getNextNumMvt('_tmp');

	if (empty($next_num_mvt)) {
		dol_print_error(null, 'Failed to get next piece number');
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" name="create_mvt" method="POST">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
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
	print $form->selectDate('', 'doc_date', 0, 0, 0, "create_mvt", 1, 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("Codejournal").'</td>';
	print '<td>'.$formaccounting->select_journal($journal_code, 'code_journal', 0, 0, 1, 1).'</td>';
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("Piece").'</td>';
	print '<td><input type="text" class="minwidth200" name="doc_ref" value="'.GETPOST('doc_ref', 'alpha').'"></td>';
	print '</tr>';

	/*
	print '<tr>';
	print '<td>' . $langs->trans("Doctype") . '</td>';
	print '<td><input type="text" class="minwidth200 name="doc_type" value=""/></td>';
	print '</tr>';
	*/
	$reshookAddLine = $hookmanager->executeHooks('bookkeepingAddLine', $parameters, $object, $action);

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';
} else {
	$object = new BookKeeping($db);

	$result = $object->fetchPerMvt($piece_num, $mode);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	if (!empty($object->piece_num)) {
		$backlink = '<a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>';

		/*if ($mode == '_tmp') {
			print load_fiche_titre($langs->trans("CreateMvts"), $backlink);
		} else {
			print load_fiche_titre($langs->trans("UpdateMvts"), $backlink);
		}*/

		$head = array();
		$h = 0;
		$head[$h][0] = $_SERVER['PHP_SELF'].'?piece_num='.((int) $object->piece_num).($mode ? '&mode='.$mode : '');
		$head[$h][1] = $langs->trans("Transaction");
		$head[$h][2] = 'transaction';
		$h++;

		print dol_get_fiche_head($head, 'transaction', '', -1);

		$object->ref = (string) $object->piece_num;
		$object->label = $object->doc_ref;

		$morehtmlref .= '<div style="clear: both;"></div>';
		$morehtmlref .= '<div class="refidno opacitymedium">';
		$morehtmlref .= $object->label;
		$morehtmlref .= '</div>';

		dol_banner_tab($object, 'ref', $backlink, 1, 'piece_num', 'piece_num', $morehtmlref);

		print '<div class="fichecenter">';

		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Account movement
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("NumMvts").'</td>';
		print '<td>'.($mode == '_tmp' ? '<span class="opacitymedium" title="Id tmp '.$object->piece_num.'">'.$langs->trans("Draft").'</span>' : $object->piece_num).'</td>';
		print '</tr>';

		// Ref document
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('Piece');
		print '</td>';
		if ($action != 'editdocref') {
			print '<td class="right">';
			if ($permissiontoadd) {
				print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=editdocref&token='.newToken().'&piece_num='.((int) $object->piece_num).'&mode='.urlencode((string) $mode).'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a>';
			}
			print '</td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editdocref') {
			print '<form name="setdocref" action="'.$_SERVER["PHP_SELF"].'?piece_num='.((int) $object->piece_num).'" method="POST">';
			if ($optioncss != '') {
				print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			}
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setdocref">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			print '<input type="text" size="20" name="doc_ref" value="'.dol_escape_htmltag($object->doc_ref).'">';
			print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			print $object->doc_ref;
		}
		print '</td>';
		print '</tr>';

		// Date
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('Docdate');
		print '</td>';
		if ($action != 'editdate') {
			print '<td class="right">';
			if ($permissiontoadd) {
				print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=editdate&token='.newToken().'&piece_num='.((int) $object->piece_num).'&mode='.urlencode((string) $mode).'">'.img_edit($langs->transnoentitiesnoconv('SetDate'), 1).'</a>';
			}
			print '</td>';
		}
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editdate') {
			print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?piece_num='.((int) $object->piece_num).'" method="POST">';
			if ($optioncss != '') {
				print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			}
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setdate">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			print $form->selectDate($object->doc_date ? $object->doc_date : -1, 'doc_date', 0, 0, 0, "setdate");
			print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
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
		if ($action != 'editjournal') {
			print '<td class="right">';
			if ($permissiontoadd) {
				print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=editjournal&token='.newToken().'&piece_num='.((int) $object->piece_num).'&mode='.urlencode((string) $mode).'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a>';
			}
			print '</td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editjournal') {
			print '<form name="setjournal" action="'.$_SERVER["PHP_SELF"].'?piece_num='.((int) $object->piece_num).'" method="POST">';
			if ($optioncss != '') {
				print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			}
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setjournal">';
			print '<input type="hidden" name="mode" value="'.$mode.'">';
			print $formaccounting->select_journal($object->code_journal, 'code_journal', 0, 0, 0, 1, 1);
			print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			print $object->code_journal;
		}
		print '</td>';
		print '</tr>';

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

		// Due date (if invoice)
		//if (in_array($object->doc_type, array('customer_invoice', 'supplier_invoice'))) {
		print '<tr>';
		print '<td class="titlefield">' . $form->textwithpicto($langs->trans('DateDue'), $langs->trans("IfTransactionHasDueDate")) . '</td>';
		print '<td>';
		print $object->date_lim_reglement ? dol_print_date($object->date_lim_reglement, 'day') : '&nbsp;';
		print '</td>';
		print '</tr>';
		//}

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

			// Id_import
			if (!empty($object->import_key)) {
				print '<tr>';
				print '<td class="titlefield">' . $langs->trans("ImportId") . '</td>';
				print '<td>';
				print $object->import_key;
				print '</td>';
				print '</tr>';
			}
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
		if ($object->doc_type == 'customer_invoice')
		{
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
			}
			else dol_print_error($db);
		}
		print '<td>' . $ref .'</td>';
		print '</tr>';
		*/
		print "</table>\n";

		print '</div>';

		print '</div>';
		print '<div class="clearboth"></div>';


		print dol_get_fiche_end();


		$result = $object->fetchAllPerMvt($piece_num, $mode);	// This load $object->linesmvt

		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			// Variable that contains all transaction lines
			$tmptoselect = array();
			$atleastonevalidated = 0;
			$atleastoneexported = 0;
			foreach ($object->linesmvt as $line) {
				$tmptoselect[] = $line->id;
				if (!empty($line->date_validation)) {
					$atleastonevalidated = 1;
				}
				if (!empty($line->date_export) || !empty($line->date_validation)) {
					$atleastoneexported = 1;
				}
			}

			if ($mode != '_tmp' && !$atleastonevalidated) {
				print "\n".'<div class="tabsAction">'."\n";

				$parameters = array();
				$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if (empty($reshook)) {
					if ($permissiontodelete) {
						if (!isset($hookmanager->resArray['no_button_edit']) || $hookmanager->resArray['no_button_edit'] != 1) {
							print dolGetButtonAction('', $langs->trans('Delete'), 'delete', DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?action=deletebookkeepingwriting&confirm=yes&token='.newToken().'&piece_num='.((int) $object->piece_num).'&toselect='.implode(',', $tmptoselect), '', $permissiontodelete);
						}
					}
				}

				print '</div>';
			}

			// List of movements
			print load_fiche_titre($langs->trans("ListeMvts"), '', '');

			print '<form action="'.$_SERVER["PHP_SELF"].'?piece_num='.((int) $object->piece_num).'" method="POST">';
			if ($optioncss != '') {
				print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
			}
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="doc_date" value="'.$object->doc_date.'">'."\n";
			print '<input type="hidden" name="doc_type" value="'.$object->doc_type.'">'."\n";
			print '<input type="hidden" name="doc_ref" value="'.$object->doc_ref.'">'."\n";
			print '<input type="hidden" name="code_journal" value="'.$object->code_journal.'">'."\n";
			print '<input type="hidden" name="fk_doc" value="'.$object->fk_doc.'">'."\n";
			print '<input type="hidden" name="fk_docdet" value="'.$object->fk_docdet.'">'."\n";
			print '<input type="hidden" name="mode" value="'.$mode.'">'."\n";

			if (count($object->linesmvt) > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent">';

				$total_debit = 0;
				$total_credit = 0;

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

				// Add an empty line if there is not yet
				if (!empty($object->linesmvt[0])) {
					$tmpline = $object->linesmvt[0];
					if (!empty($tmpline->numero_compte)) {
						$line = new BookKeepingLine($db);
						$object->linesmvt[] = $line;
					}
				}

				foreach ($object->linesmvt as $line) {
					$total_debit += $line->debit;
					$total_credit += $line->credit;

					if ($action == 'update' && $line->id == $id) {
						print '<tr class="oddeven" data-lineid="'.((int) $line->id).'">';
						print '<!-- td columns in edit mode -->';
						print '<td>';
						print $formaccounting->select_account((GETPOSTISSET("accountingaccount_number") ? GETPOST("accountingaccount_number", "alpha") : $line->numero_compte), 'accountingaccount_number', 1, array(), 1, 1, 'minwidth200 maxwidth500');
						print '</td>';
						print '<td>';
						// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
						// - It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
						// - Also, it is not possible to use a value that is not in the list.
						// - Also, the label is not automatically filled when a value is selected.
						if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
							print $formaccounting->select_auxaccount((GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account), 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
						} else {
							print '<input type="text" class="maxwidth150" name="subledger_account" value="'.(GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account).'" placeholder="'.dol_escape_htmltag($langs->trans("SubledgerAccount")).'">';
						}
						// Add also input for subledger label
						print '<br><input type="text" class="maxwidth150" name="subledger_label" value="'.(GETPOSTISSET("subledger_label") ? GETPOST("subledger_label", "alpha") : $line->subledger_label).'" placeholder="'.dol_escape_htmltag($langs->trans("SubledgerAccountLabel")).'">';
						print '</td>';
						print '<td><input type="text" class="minwidth200" name="label_operation" value="'.(GETPOSTISSET("label_operation") ? GETPOST("label_operation", "alpha") : $line->label_operation).'"></td>';
						print '<td class="right"><input type="text" class="right width50" name="debit" value="'.(GETPOSTISSET("debit") ? GETPOST("debit", "alpha") : price($line->debit)).'"></td>';
						print '<td class="right"><input type="text" class="right width50" name="credit" value="'.(GETPOSTISSET("credit") ? GETPOST("credit", "alpha") : price($line->credit)).'"></td>';
						print '<td>';
						print '<input type="hidden" name="id" value="'.$line->id.'">'."\n";
						print '<input type="submit" class="button" name="update" value="'.$langs->trans("Update").'">';
						print '</td>';
						print "</tr>\n";
					} elseif (empty($line->numero_compte) || (empty($line->debit) && empty($line->credit))) {
						if (($action == "" || $action == 'add') && $permissiontoadd) {
							print '<tr class="oddeven" data-lineid="'.((int) $line->id).'">';
							print '<!-- td columns in add mode -->';
							print '<td>';
							print $formaccounting->select_account($action == 'add' ? GETPOST('accountingaccount_number') : '', 'accountingaccount_number', 1, array(), 1, 1, 'minwidth200 maxwidth500');
							print '</td>';
							print '<td>';
							// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
							// It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
							// Also, it is not possible to use a value that is not in the list.
							// Also, the label is not automatically filled when a value is selected.
							if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
								print $formaccounting->select_auxaccount('', 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
							} else {
								print '<input type="text" class="maxwidth150" name="subledger_account" value="" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccount")) . '">';
							}
							print '<br><input type="text" class="maxwidth150" name="subledger_label" value="" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccountLabel")) . '">';
							print '</td>';
							print '<td><input type="text" class="minwidth200" name="label_operation" value="' . dol_escape_htmltag($label_operation) . '"/></td>';
							print '<td class="right"><input type="text" class="right width50" name="debit" value=""/></td>';
							print '<td class="right"><input type="text" class="right width50" name="credit" value=""/></td>';
							print '<td class="center"><input type="submit" class="button small" name="save" value="' . $langs->trans("Add") . '"></td>';
							print "</tr>\n";
						}
					} else {
						print '<tr class="oddeven" data-lineid="'.((int) $line->id).'">';
						print '<!-- td columns in display mode -->';
						$resultfetch = $accountingaccount->fetch(0, $line->numero_compte, true);
						print '<td>';
						if ($resultfetch > 0) {
							print $accountingaccount->getNomUrl(0, 1, 1, '', 0);
						} else {
							print dol_escape_htmltag($line->numero_compte).' <span class="warning">('.$langs->trans("AccountRemovedFromCurrentChartOfAccount").')</span>';
						}
						print '</td>';
						print '<td>'.length_accounta($line->subledger_account);
						if ($line->subledger_label) {
							print ' - <span class="opacitymedium">'.dol_escape_htmltag($line->subledger_label).'</span>';
						}
						print '</td>';
						print '<td>'.$line->label_operation.'</td>';
						print '<td class="right nowraponall amount">'.($line->debit != 0 ? price($line->debit) : '').'</td>';
						print '<td class="right nowraponall amount">'.($line->credit != 0 ? price($line->credit) : '').'</td>';

						print '<td class="center nowraponall">';
						if ($permissiontoadd) {
							if (empty($line->date_export) && empty($line->date_validation)) {
								print '<a class="editfielda reposition" href="' . $_SERVER["PHP_SELF"] . '?action=update&id=' . $line->id . '&piece_num=' . ((int) $line->piece_num) . '&mode=' . urlencode((string) $mode) . '&token=' . urlencode(newToken()) . '">';
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

								print '<a href="' . $_SERVER["PHP_SELF"] . '?action=' . $actiontodelete . '&id=' . $line->id . '&piece_num=' . ((int) $line->piece_num) . '&mode=' . urlencode((string) $mode) . '&token=' . urlencode(newToken()) . '">';
								print img_delete();
								print '</a>';
							} else {
								print '<a class="editfielda nohover cursornotallowed disabled" href="#" title="'.dol_escape_htmltag($langs->trans("ForbiddenTransactionAlreadyExported")).'">';
								print img_delete($langs->trans("ForbiddenTransactionAlreadyValidated"));
								print '</a>';
							}
						}
						print '</td>';
						print "</tr>\n";
					}
				}

				$total_debit = price2num($total_debit, 'MT');
				$total_credit = price2num($total_credit, 'MT');

				if ($total_debit != $total_credit) {
					setEventMessages(null, array($langs->trans('MvtNotCorrectlyBalanced', $total_debit, $total_credit)), 'warnings');
				}

				print '</table>';
				print '</div>';

				if ($mode == '_tmp' && $action == '' && $permissiontoadd) {
					print '<br>';
					print '<div class="center">';
					if (empty($total_debit) && empty($total_debit)) {
						print '<input type="submit" class="button" disabled="disabled" href="#" title="'.dol_escape_htmltag($langs->trans("EnterNonEmptyLinesFirst")).'" value="'.dol_escape_htmltag($langs->trans("ValidTransaction")).'">';
					} elseif ($total_debit == $total_credit) {
						print '<a class="button" href="'.$_SERVER["PHP_SELF"].'?piece_num='.((int) $object->piece_num).'&action=valid&token='.newToken().'">'.$langs->trans("ValidTransaction").'</a>';
					} else {
						print '<input type="submit" class="button" disabled="disabled" href="#" title="'.dol_escape_htmltag($langs->trans("MvtNotCorrectlyBalanced", $total_debit, $total_credit)).'" value="'.dol_escape_htmltag($langs->trans("ValidTransaction")).'">';
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
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();

<?php
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2023  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018		Eric Seigne             <eric.seigne@cap-rel.fr>
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
 * \file		htdocs/accountancy/journal/expensereportsjournal.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Page with expense reports journal
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';

// Load translation files required by the page
$langs->loadLangs(array("commercial", "compta", "bills", "other", "accountancy", "trips", "errors"));

$id_journal = GETPOST('id_journal', 'int');
$action = GETPOST('action', 'aZ09');

$date_startmonth = GETPOST('date_startmonth');
$date_startday = GETPOST('date_startday');
$date_startyear = GETPOST('date_startyear');
$date_endmonth = GETPOST('date_endmonth');
$date_endday = GETPOST('date_endday');
$date_endyear = GETPOST('date_endyear');
$in_bookkeeping = GETPOST('in_bookkeeping');
if ($in_bookkeeping == '') {
	$in_bookkeeping = 'notyet';
}

$now = dol_now();

$hookmanager->initHooks(array('expensereportsjournal'));
$parameters = array();

// Security check
if (!isModEnabled('accounting')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'bind', 'write')) {
	accessforbidden();
}

$error = 0;
$errorforinvoice = array();

/*
 * Actions
 */

$accountingaccount = new AccountingAccount($db);

// Get informations of journal
$accountingjournalstatic = new AccountingJournal($db);
$accountingjournalstatic->fetch($id_journal);
$journal = $accountingjournalstatic->code;
$journal_label = $accountingjournalstatic->label;

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_startmonth) || empty($date_endmonth)) {
	// Period by default on transfer
	$dates = getDefaultDatesForTransfer();
	$date_start = $dates['date_start'];
	$date_end = $dates['date_end'];
	$pastmonthyear = $dates['pastmonthyear'];
	$pastmonth = $dates['pastmonth'];
}

if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
	$date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
	$date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

$sql = "SELECT er.rowid, er.ref, er.date_debut as de,";
$sql .= " erd.rowid as erdid, erd.comments, erd.total_ht, erd.total_tva, erd.total_localtax1, erd.total_localtax2, erd.tva_tx, erd.total_ttc, erd.fk_code_ventilation, erd.vat_src_code, ";
$sql .= " u.rowid as uid, u.firstname, u.lastname, u.accountancy_code as user_accountancy_account,";
$sql .= " f.accountancy_code, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte";
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as erd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_fees as f ON f.id = erd.fk_c_type_fees";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
$sql .= " JOIN ".MAIN_DB_PREFIX."expensereport as er ON er.rowid = erd.fk_expensereport";
$sql .= " JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = er.fk_user_author";
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " WHERE er.fk_statut > 0";
$sql .= " AND erd.fk_code_ventilation > 0";
$sql .= " AND er.entity IN (".getEntity('expensereport', 0).")"; // We don't share object for accountancy
if ($date_start && $date_end) {
	$sql .= " AND er.date_debut >= '".$db->idate($date_start)."' AND er.date_debut <= '".$db->idate($date_end)."'";
}
// Define begin binding date
if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND er.date_debut >= '".$db->idate($conf->global->ACCOUNTING_DATE_START_BINDING)."'";
}
// Already in bookkeeping or not
if ($in_bookkeeping == 'already') {
	$sql .= " AND er.rowid IN (SELECT fk_doc FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab  WHERE ab.doc_type='expense_report')";
}
if ($in_bookkeeping == 'notyet') {
	$sql .= " AND er.rowid NOT IN (SELECT fk_doc FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab  WHERE ab.doc_type='expense_report')";
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " ORDER BY er.date_debut";

dol_syslog('accountancy/journal/expensereportsjournal.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$taber = array();
	$tabht = array();
	$tabtva = array();
	$def_tva = array();
	$tabttc = array();
	$tablocaltax1 = array();
	$tablocaltax2 = array();
	$tabuser = array();

	$num = $db->num_rows($result);

	// Variables
	$account_salary = (getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT')) ? $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT : 'NotDefined';
	$account_vat = (getDolGlobalString('ACCOUNTING_VAT_BUY_ACCOUNT')) ? $conf->global->ACCOUNTING_VAT_BUY_ACCOUNT : 'NotDefined';

	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);

		// Controls
		$compta_user = (!empty($obj->user_accountancy_account)) ? $obj->user_accountancy_account : $account_salary;
		$compta_fees = $obj->compte;

		$vatdata = getTaxesFromId($obj->tva_tx.($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''), $mysoc, $mysoc, 0);
		$compta_tva = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $account_vat);
		$compta_localtax1 = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);
		$compta_localtax2 = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);

		// Define array to display all VAT rates that use this accounting account $compta_tva
		if (price2num($obj->tva_tx) || !empty($obj->vat_src_code)) {
			$def_tva[$obj->rowid][$compta_tva][vatrate($obj->tva_tx).($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : '')] = (vatrate($obj->tva_tx).($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''));
		}

		$taber[$obj->rowid]["date"] = $db->jdate($obj->de);
		$taber[$obj->rowid]["ref"] = $obj->ref;
		$taber[$obj->rowid]["comments"] = $obj->comments;
		$taber[$obj->rowid]["fk_expensereportdet"] = $obj->erdid;

		// Avoid warnings
		if (!isset($tabttc[$obj->rowid][$compta_user])) {
			$tabttc[$obj->rowid][$compta_user] = 0;
		}
		if (!isset($tabht[$obj->rowid][$compta_fees])) {
			$tabht[$obj->rowid][$compta_fees] = 0;
		}
		if (!isset($tabtva[$obj->rowid][$compta_tva])) {
			$tabtva[$obj->rowid][$compta_tva] = 0;
		}
		if (!isset($tablocaltax1[$obj->rowid][$compta_localtax1])) {
			$tablocaltax1[$obj->rowid][$compta_localtax1] = 0;
		}
		if (!isset($tablocaltax2[$obj->rowid][$compta_localtax2])) {
			$tablocaltax2[$obj->rowid][$compta_localtax2] = 0;
		}

		$tabttc[$obj->rowid][$compta_user] += $obj->total_ttc;
		$tabht[$obj->rowid][$compta_fees] += $obj->total_ht;
		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
		$tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1;
		$tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2;
		$tabuser[$obj->rowid] = array(
				'id' => $obj->uid,
				'name' => dolGetFirstLastname($obj->firstname, $obj->lastname),
				'user_accountancy_code' => $obj->user_accountancy_account
		);

		$i++;
	}
} else {
	dol_print_error($db);
}

// Load all unbound lines
$sql = "SELECT fk_expensereport, COUNT(erd.rowid) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as erd";
$sql .= " WHERE erd.fk_code_ventilation <= 0";
$sql .= " AND erd.total_ttc <> 0";
$sql .= " AND fk_expensereport IN (".$db->sanitize(join(",", array_keys($taber))).")";
$sql .= " GROUP BY fk_expensereport";
$resql = $db->query($sql);

$num = $db->num_rows($resql);
$i = 0;
while ($i < $num) {
	$obj = $db->fetch_object($resql);
	if ($obj->nb > 0) {
		$errorforinvoice[$obj->fk_expensereport] = 'somelinesarenotbound';
	}
	$i++;
}

// Bookkeeping Write
if ($action == 'writebookkeeping' && !$error) {
	$now = dol_now();
	$error = 0;

	$accountingaccountexpense = new AccountingAccount($db);
	$accountingaccountexpense->fetch(null, $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT, true);

	foreach ($taber as $key => $val) {		// Loop on each expense report
		$errorforline = 0;

		$totalcredit = 0;
		$totaldebit = 0;

		$db->begin();

		// Error if some lines are not binded/ready to be journalized
		if (!empty($errorforinvoice[$key]) && $errorforinvoice[$key] == 'somelinesarenotbound') {
			$error++;
			$errorforline++;
			setEventMessages($langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $val['ref']), null, 'errors');
		}

		// Fees
		if (!$errorforline) {
			foreach ($tabht[$key] as $k => $mt) {
				if ($mt) {
					// get compte id and label
					if ($accountingaccount->fetch(null, $k, true)) {
						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->doc_ref = $val["ref"];
						$bookkeeping->date_creation = $now;
						$bookkeeping->doc_type = 'expense_report';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = $val["fk_expensereportdet"];

						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';

						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = $accountingaccount->label;

						$bookkeeping->label_operation = $accountingaccount->label;
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
						$bookkeeping->debit = ($mt > 0) ? $mt : 0;
						$bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $langs->transnoentities($journal_label);
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->entity = $conf->entity;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

						$result = $bookkeeping->create($user);
						if ($result < 0) {
							if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
								$error++;
								$errorforline++;
								$errorforinvoice[$key] = 'alreadyjournalized';
								//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
							} else {
								$error++;
								$errorforline++;
								$errorforinvoice[$key] = 'other';
								setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
							}
						}
					}
				}
			}
		}

		// VAT
		if (!$errorforline) {
			$listoftax = array(0, 1, 2);
			foreach ($listoftax as $numtax) {
				$arrayofvat = $tabtva;
				if ($numtax == 1) {
					$arrayofvat = $tablocaltax1;
				}
				if ($numtax == 2) {
					$arrayofvat = $tablocaltax2;
				}

				foreach ($arrayofvat[$key] as $k => $mt) {
					if ($mt) {
						$accountingaccount->fetch(null, $k, true);	// TODO Use a cache for label
						$account_label = $accountingaccount->label;

						// get compte id and label
						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->doc_ref = $val["ref"];
						$bookkeeping->date_creation = $now;
						$bookkeeping->doc_type = 'expense_report';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = $val["fk_expensereportdet"];

						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';

						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = $account_label;

						$bookkeeping->label_operation = $langs->trans("VAT").' '.join(', ', $def_tva[$key][$k]).' %';
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
						$bookkeeping->debit = ($mt > 0) ? $mt : 0;
						$bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $langs->transnoentities($journal_label);
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->entity = $conf->entity;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

						$result = $bookkeeping->create($user);
						if ($result < 0) {
							if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
								$error++;
								$errorforline++;
								$errorforinvoice[$key] = 'alreadyjournalized';
								//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
							} else {
								$error++;
								$errorforline++;
								$errorforinvoice[$key] = 'other';
								setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
							}
						}
					}
				}
			}
		}

		// Thirdparty
		if (!$errorforline) {
			foreach ($tabttc[$key] as $k => $mt) {
				if ($mt) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->doc_ref = $val["ref"];
					$bookkeeping->date_creation = $now;
					$bookkeeping->doc_type = 'expense_report';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = $val["fk_expensereportdet"];

					$bookkeeping->subledger_account = $tabuser[$key]['user_accountancy_code'];
					$bookkeeping->subledger_label = $tabuser[$key]['name'];

					$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
					$bookkeeping->label_compte = $accountingaccountexpense->label;

					$bookkeeping->label_operation = $tabuser[$key]['name'];
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
					$bookkeeping->debit = ($mt <= 0) ? -$mt : 0;
					$bookkeeping->credit = ($mt > 0) ? $mt : 0;
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $langs->transnoentities($journal_label);
					$bookkeeping->fk_user_author = $user->id;
					$bookkeeping->entity = $conf->entity;

					$totaldebit += $bookkeeping->debit;
					$totalcredit += $bookkeeping->credit;

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
							$error++;
							$errorforline++;
							$errorforinvoice[$key] = 'alreadyjournalized';
							//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
						} else {
							$error++;
							$errorforline++;
							$errorforinvoice[$key] = 'other';
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				}
			}
		}

		// Protection against a bug on lines before
		if (!$errorforline && (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT'))) {
			$error++;
			$errorforline++;
			$errorforinvoice[$key] = 'amountsnotbalanced';
			setEventMessages('We tried to insert a non balanced transaction in book for '.$val["ref"].'. Canceled. Surely a bug.', null, 'errors');
		}

		if (!$errorforline) {
			$db->commit();
		} else {
			$db->rollback();

			if ($error >= 10) {
				setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
				break; // Break in the foreach
			}
		}
	}

	$tabpay = $taber;

	if (empty($error) && count($tabpay) > 0) {
		setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
	} elseif (count($tabpay) == $error) {
		setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
	} else {
		setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
	}

	$action = '';

	// Must reload data, so we make a redirect
	if (count($tabpay) != $error) {
		$param = 'id_journal='.$id_journal;
		$param .= '&date_startday='.$date_startday;
		$param .= '&date_startmonth='.$date_startmonth;
		$param .= '&date_startyear='.$date_startyear;
		$param .= '&date_endday='.$date_endday;
		$param .= '&date_endmonth='.$date_endmonth;
		$param .= '&date_endyear='.$date_endyear;
		$param .= '&in_bookkeeping='.$in_bookkeeping;

		header("Location: ".$_SERVER['PHP_SELF'].($param ? '?'.$param : ''));
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);

$userstatic = new User($db);

// Export
if ($action == 'exportcsv' && !$error) {		// ISO and not UTF8 !
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

	$filename = 'journal';
	$type_export = 'journal';
	include DOL_DOCUMENT_ROOT.'/accountancy/tpl/export_journal.tpl.php';

	// CSV header line
	print '"'.$langs->transnoentitiesnoconv("Date").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("Piece").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("AccountAccounting").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("LabelOperation").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("AccountingDebit").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("AccountingCredit").'"'.$sep;
	print "\n";

	foreach ($taber as $key => $val) {
		$date = dol_print_date($val["date"], 'day');

		$userstatic->id = $tabuser[$key]['id'];
		$userstatic->name = $tabuser[$key]['name'];

		// Fees
		foreach ($tabht[$key] as $k => $mt) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k, true);
			if ($mt) {
				print '"'.$date.'"'.$sep;
				print '"'.$val["ref"].'"'.$sep;
				print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
				print '"'.dol_trunc($accountingaccount->label, 32).'"'.$sep;
				print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
				print '"'.($mt < 0 ? price(-$mt) : '').'"';
				print "\n";
			}
		}

		// VAT
		foreach ($tabtva[$key] as $k => $mt) {
			if ($mt) {
				print '"'.$date.'"'.$sep;
				print '"'.$val["ref"].'"'.$sep;
				print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
				print '"'.dol_trunc($langs->trans("VAT")).'"'.$sep;
				print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
				print '"'.($mt < 0 ? price(-$mt) : '').'"';
				print "\n";
			}
		}

		// Third party
		foreach ($tabttc[$key] as $k => $mt) {
			print '"'.$date.'"'.$sep;
			print '"'.$val["ref"].'"'.$sep;
			print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
			print '"'.dol_trunc($userstatic->name).'"'.$sep;
			print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
			print '"'.($mt >= 0 ? price($mt) : '').'"';
		}
		print "\n";
	}
}

if (empty($action) || $action == 'view') {
	$title = $langs->trans("GenerationOfAccountingEntries").' - '.$accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);

	llxHeader('', dol_string_nohtmltag($title));

	$nom = $title;
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate = dol_now();
	$description = $langs->trans("DescJournalOnlyBindedVisible").'<br>';

	$listofchoices = array('notyet'=>$langs->trans("NotYetInGeneralLedger"), 'already'=>$langs->trans("AlreadyInGeneralLedger"));
	$period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
	$period .= ' -  '.$langs->trans("JournalizationInLedgerStatus").' '.$form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

	$varlink = 'id_journal='.$id_journal;

	journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

	if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
		// Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
		// Fiscal period test
		$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_fiscalyear WHERE entity = ".((int) $conf->entity);
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj->nb == 0) {
				print '<br><div class="warning">'.img_warning().' '.$langs->trans("TheFiscalPeriodIsNotDefined");
				$desc = ' : '.$langs->trans("AccountancyAreaDescFiscalPeriod", 4, '{link}');
				$desc = str_replace('{link}', '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("FiscalPeriod").'</strong>', $desc);
				print $desc;
				print '</div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	// Button to write into Ledger
	if (!getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
		print '<br><div class="warning">'.img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
		$desc = ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '{link}');
		$desc = str_replace('{link}', '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>', $desc);
		print $desc;
		print '</div>';
	}
	print '<br><div class="tabsAction tabsActionNoBottom centerimp">';

	if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
		print '<input type="button" class="butAction" name="exportcsv" value="'.$langs->trans("ExportDraftJournal").'" onclick="launch_export();" />';
	}
	if (!getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
		print '<input type="button" class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")).'" value="'.$langs->trans("WriteBookKeeping").'" />';
	} else {
		if ($in_bookkeeping == 'notyet') {
			print '<input type="button" class="butAction" name="writebookkeeping" value="'.$langs->trans("WriteBookKeeping").'" onclick="writebookkeeping();" />';
		} else {
			print '<a href="#" class="butActionRefused classfortooltip" name="writebookkeeping">'.$langs->trans("WriteBookKeeping").'</a>';
		}
	}
	print '</div>';

	// TODO Avoid using js. We can use a direct link with $param
	print '
	<script type="text/javascript">
		function launch_export() {
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("click on writebookkeeping");
			$("div.fiche form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';

	/*
	 * Show result array
	 */
	print '<br>';

	$i = 0;
	print '<div class="div-table-responsive">';
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print "<td>".$langs->trans("Date")."</td>";
	print "<td>".$langs->trans("Piece").' ('.$langs->trans("ExpenseReportRef").")</td>";
	print "<td>".$langs->trans("AccountAccounting")."</td>";
	print "<td>".$langs->trans("SubledgerAccount")."</td>";
	print "<td>".$langs->trans("LabelOperation")."</td>";
	print '<td class="right">'.$langs->trans("AccountingDebit")."</td>";
	print '<td class="right">'.$langs->trans("AccountingCredit")."</td>";
	print "</tr>\n";

	$i = 0;

	$expensereportstatic = new ExpenseReport($db);
	$expensereportlinestatic = new ExpenseReportLine($db);

	foreach ($taber as $key => $val) {
		$expensereportstatic->id = $key;
		$expensereportstatic->ref = $val["ref"];
		$expensereportlinestatic->comments = html_entity_decode(dol_trunc($val["comments"], 32));

		$date = dol_print_date($val["date"], 'day');

		if ($errorforinvoice[$key] == 'somelinesarenotbound') {
			print '<tr class="oddeven">';
			print "<!-- Some lines are not bound -->";
			print "<td>".$date."</td>";
			print "<td>".$expensereportstatic->getNomUrl(1)."</td>";
			// Account
			print "<td>";
			print '<span class="error">'.$langs->trans('ErrorInvoiceContainsLinesNotYetBoundedShort', $val['ref']).'</span>';
			print '</td>';
			// Subledger account
			print "<td>";
			print '</td>';
			print "<td>";
			print "</td>";
			print '<td class="right"></td>';
			print '<td class="right"></td>';
			print "</tr>";

			$i++;
		}

		// Fees
		foreach ($tabht[$key] as $k => $mt) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k, true);

			if ($mt) {
				print '<tr class="oddeven">';
				print "<!-- Fees -->";
				print "<td>".$date."</td>";
				print "<td>".$expensereportstatic->getNomUrl(1)."</td>";
				$userstatic->id = $tabuser[$key]['id'];
				$userstatic->name = $tabuser[$key]['name'];
				// Account
				print "<td>";
				$accountoshow = length_accountg($k);
				if (($accountoshow == "") || $accountoshow == 'NotDefined') {
					print '<span class="error">'.$langs->trans("FeeAccountNotDefined").'</span>';
				} else {
					print $accountoshow;
				}
				print '</td>';
				// Subledger account
				print "<td>";
				print '</td>';
				$userstatic->id = $tabuser[$key]['id'];
				$userstatic->name = $tabuser[$key]['name'];
				print "<td>".$userstatic->getNomUrl(0, 'user', 16).' - '.$accountingaccount->label."</td>";
				print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
				print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
				print "</tr>";

				$i++;
			}
		}

		// VAT
		$listoftax = array(0, 1, 2);
		foreach ($listoftax as $numtax) {
			$arrayofvat = $tabtva;
			if ($numtax == 1) {
				$arrayofvat = $tablocaltax1;
			}
			if ($numtax == 2) {
				$arrayofvat = $tablocaltax2;
			}

			foreach ($arrayofvat[$key] as $k => $mt) {
				if ($mt) {
					print '<tr class="oddeven">';
					print "<!-- VAT -->";
					print "<td>".$date."</td>";
					print "<td>".$expensereportstatic->getNomUrl(1)."</td>";
					// Account
					print "<td>";
					$accountoshow = length_accountg($k);
					if (($accountoshow == "") || $accountoshow == 'NotDefined') {
						print '<span class="error">'.$langs->trans("VATAccountNotDefined").'</span>';
					} else {
						print $accountoshow;
					}
					print "</td>";
					// Subledger account
					print "<td>";
					print '</td>';
					print "<td>".$userstatic->getNomUrl(0, 'user', 16).' - '.$langs->trans("VAT").' '.join(', ', $def_tva[$key][$k]).' %'.($numtax ? ' - Localtax '.$numtax : '');
					print "</td>";
					print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
					print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
					print "</tr>";

					$i++;
				}
			}
		}

		// Third party
		foreach ($tabttc[$key] as $k => $mt) {
			$userstatic->id = $tabuser[$key]['id'];
			$userstatic->name = $tabuser[$key]['name'];

			print '<tr class="oddeven">';
			print "<!-- Thirdparty -->";
			print "<td>".$date."</td>";
			print "<td>".$expensereportstatic->getNomUrl(1)."</td>";
			// Account
			print "<td>";
			$accountoshow = length_accountg($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT);
			if (($accountoshow == "") || $accountoshow == 'NotDefined') {
				print '<span class="error">'.$langs->trans("MainAccountForUsersNotDefined").'</span>';
			} else {
				print $accountoshow;
			}
			print "</td>";
			// Subledger account
			print "<td>";
			$accountoshow = length_accounta($k);
			if (($accountoshow == "") || $accountoshow == 'NotDefined') {
				print '<span class="error">'.$langs->trans("UserAccountNotDefined").'</span>';
			} else {
				print $accountoshow;
			}
			print '</td>';
			print "<td>".$userstatic->getNomUrl(0, 'user', 16).' - '.$langs->trans("SubledgerAccount")."</td>";
			print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
			print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
			print "</tr>";

			$i++;
		}
	}

	if (!$i) {
		$colspan = 7;
		print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';

	// End of page
	llxFooter();
}
$db->close();

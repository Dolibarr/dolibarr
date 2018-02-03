<?php
/* Copyright (C) 2007-2010  Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger		<jean@tiaris.info>
 * Copyright (C) 2011       Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2017  Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2013-2016  Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Florian Henry		<florian.henry@open-concept.pro>
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
 * \file		htdocs/accountancy/journal/expensereportsjournal.php
 * \ingroup		Advanced accountancy
 * \brief		Page with expense reports journal
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';

$langs->loadLangs(array("commercial", "compta","bills","other","accountancy","trips","errors"));

$id_journal = GETPOST('id_journal', 'int');
$action = GETPOST('action','aZ09');

$date_startmonth = GETPOST('date_startmonth');
$date_startday = GETPOST('date_startday');
$date_startyear = GETPOST('date_startyear');
$date_endmonth = GETPOST('date_endmonth');
$date_endday = GETPOST('date_endday');
$date_endyear = GETPOST('date_endyear');
$in_bookkeeping = GETPOST('in_bookkeeping');
if ($in_bookkeeping == '') $in_bookkeeping = 'notyet';

$now = dol_now();

// Security check
if ($user->societe_id > 0)
	accessforbidden();

/*
 * Actions
 */

// Get informations of journal
$accountingjournalstatic = new AccountingJournal($db);
$accountingjournalstatic->fetch($id_journal);
$journal = $accountingjournalstatic->code;
$journal_label = $accountingjournalstatic->label;

$year_current = strftime("%Y", dol_now());
$pastmonth = strftime("%m", dol_now()) - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0) {
	$pastmonth = 12;
	$pastmonthyear --;
}

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (! GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) // We define date_start and date_end, only if we did not submit the form
{
	$date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
	$date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

$idpays = $mysoc->country_id;

$sql = "SELECT er.rowid, er.ref, er.date_debut as de,";
$sql .= " erd.rowid as erdid, erd.comments, erd.total_ht, erd.total_tva, erd.total_localtax1, erd.total_localtax2, erd.tva_tx, erd.total_ttc, erd.fk_code_ventilation, erd.vat_src_code, ";
$sql .= " u.rowid as uid, u.firstname, u.lastname, u.accountancy_code as user_accountancy_account,";
$sql .= " f.accountancy_code, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte";
//$sql .= " ct.accountancy_code_buy as account_tva";
$sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
//$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_tva as ct ON erd.tva_tx = ct.taux AND ct.fk_pays = '" . $idpays . "'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
$sql .= " JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
$sql .= " JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = er.fk_user_author";
$sql .= " WHERE er.fk_statut > 0";
$sql .= " AND erd.fk_code_ventilation > 0";
$sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")";  // We don't share object for accountancy
if ($date_start && $date_end)
	$sql .= " AND er.date_debut >= '" . $db->idate($date_start) . "' AND er.date_debut <= '" . $db->idate($date_end) . "'";
// Already in bookkeeping or not
if ($in_bookkeeping == 'already')
{
    $sql .= " AND er.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='expense_report')";
}
if ($in_bookkeeping == 'notyet')
{
    $sql .= " AND er.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='expense_report')";
}
$sql .= " ORDER BY er.date_debut";

dol_syslog('accountancy/journal/expensereportsjournal.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result) {

	$taber = array ();
	$tabht = array ();
	$tabtva = array ();
	$def_tva = array ();
	$tabttc = array ();
	$tablocaltax1 = array ();
	$tablocaltax2 = array ();
	$tabuser = array ();

	$num = $db->num_rows($result);

	// Variables
	$account_salary = (! empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT)) ? $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT : 'NotDefined';
	$account_vat = (! empty($conf->global->ACCOUNTING_VAT_BUY_ACCOUNT)) ? $conf->global->ACCOUNTING_VAT_BUY_ACCOUNT : 'NotDefined';

	$i = 0;
	while ( $i < $num ) {
		$obj = $db->fetch_object($result);

		// Controls
		$compta_user = (! empty($obj->user_accountancy_account)) ? $obj->user_accountancy_account : $account_salary;
		$compta_fees = $obj->compte;

		$vatdata = getTaxesFromId($obj->tva_tx.($obj->vat_src_code?' ('.$obj->vat_src_code.')':''), $mysoc, $mysoc, 0);
		$compta_tva = (! empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $account_vat);
		$compta_localtax1 = (! empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);
		$compta_localtax2 = (! empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);

		// Define array to display all VAT rates that use this accounting account $compta_tva
		if (price2num($obj->tva_tx) || ! empty($obj->vat_src_code))
		{
			$def_tva[$obj->rowid][$compta_tva][vatrate($obj->tva_tx).($obj->vat_src_code?' ('.$obj->vat_src_code.')':'')]=(vatrate($obj->tva_tx).($obj->vat_src_code?' ('.$obj->vat_src_code.')':''));
		}

		$taber[$obj->rowid]["date"] = $db->jdate($obj->de);
		$taber[$obj->rowid]["ref"] = $obj->ref;
		$taber[$obj->rowid]["comments"] = $obj->comments;
		$taber[$obj->rowid]["fk_expensereportdet"] = $obj->erdid;

		// Avoid warnings
		if (! isset($tabttc[$obj->rowid][$compta_user])) $tabttc[$obj->rowid][$compta_user] = 0;
		if (! isset($tabht[$obj->rowid][$compta_fees])) $tabht[$obj->rowid][$compta_fees] = 0;
		if (! isset($tabtva[$obj->rowid][$compta_tva])) $tabtva[$obj->rowid][$compta_tva] = 0;
		if (! isset($tablocaltax1[$obj->rowid][$compta_localtax1])) $tablocaltax1[$obj->rowid][$compta_localtax1] = 0;
		if (! isset($tablocaltax2[$obj->rowid][$compta_localtax2])) $tablocaltax2[$obj->rowid][$compta_localtax2] = 0;

		$tabttc[$obj->rowid][$compta_user] += $obj->total_ttc;
		$tabht[$obj->rowid][$compta_fees] += $obj->total_ht;
		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
		$tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1;
		$tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2;
		$tabuser[$obj->rowid] = array (
				'id' => $obj->uid,
				'name' => dolGetFirstLastname($obj->firstname, $obj->lastname),
				'user_accountancy_code' => $obj->user_accountancy_account
		);

		$i ++;
	}
} else {
	dol_print_error($db);
}

// Bookkeeping Write
if ($action == 'writebookkeeping') {
	$now = dol_now();
	$error = 0;

	foreach ($taber as $key => $val)		// Loop on each expense report
	{
		$errorforline = 0;

		$totalcredit = 0;
		$totaldebit = 0;

		$db->begin();

		// Thirdparty
		if (! $errorforline)
		{
			foreach ( $tabttc[$key] as $k => $mt ) {
				if ($mt) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->doc_ref = $val["ref"];
					$bookkeeping->date_create = $now;
					$bookkeeping->doc_type = 'expense_report';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = $val["fk_expensereportdet"];
					$bookkeeping->subledger_account = $tabuser[$key]['user_accountancy_code'];
					$bookkeeping->subledger_label = $tabuser[$key]['user_accountancy_code'];
					$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
					$bookkeeping->label_operation = $tabuser[$key]['name'];
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
					$bookkeeping->debit = ($mt <= 0) ? -$mt : 0;
					$bookkeeping->credit = ($mt > 0) ? $mt : 0;
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $journal_label;
					$bookkeeping->fk_user_author = $user->id;

					$totaldebit += $bookkeeping->debit;
					$totalcredit += $bookkeeping->credit;

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
						{
							$error++;
							$errorforline++;
							//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
						}
						else
						{
							$error++;
							$errorforline++;
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				}
			}
		}

		// Fees
		if (! $errorforline)
		{
			foreach ( $tabht[$key] as $k => $mt ) {
				if ($mt) {
					// get compte id and label
					$accountingaccount = new AccountingAccount($db);
					if ($accountingaccount->fetch(null, $k, true)) {
						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->doc_ref = $val["ref"];
						$bookkeeping->date_create = $now;
						$bookkeeping->doc_type = 'expense_report';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = $val["fk_expensereportdet"];
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_operation = $accountingaccount->label;
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
						$bookkeeping->debit = ($mt > 0) ? $mt : 0;
						$bookkeeping->credit = ($mt <= 0) ? $mt : 0;
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $journal_label;
						$bookkeeping->fk_user_author = $user->id;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

						$result = $bookkeeping->create($user);
						if ($result < 0) {
							if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
							{
								$error++;
								$errorforline++;
								//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
							}
							else
							{
								$error++;
								$errorforline++;
								setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
							}
						}
					}
				}
			}
		}

		// VAT
		if (! $errorforline)
		{
			$listoftax=array(0, 1, 2);
			foreach($listoftax as $numtax)
			{
				$arrayofvat = $tabtva;
				if ($numtax == 1) $arrayofvat = $tablocaltax1;
				if ($numtax == 2) $arrayofvat = $tablocaltax2;

				foreach ( $arrayofvat[$key] as $k => $mt ) {
					if ($mt) {
					// get compte id and label
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->doc_ref = $val["ref"];
					$bookkeeping->date_create = $now;
					$bookkeeping->doc_type = 'expense_report';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = $val["fk_expensereportdet"];
					$bookkeeping->subledger_account = '';
					$bookkeeping->subledger_label = '';
					$bookkeeping->numero_compte = $k;
					$bookkeeping->label_operation = $langs->trans("VAT"). ' '.join(', ',$def_tva[$key][$k]).' %';
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
					$bookkeeping->debit = ($mt > 0) ? $mt : 0;
					$bookkeeping->credit = ($mt <= 0) ? $mt : 0;
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $journal_label;
					$bookkeeping->fk_user_author = $user->id;

					$totaldebit += $bookkeeping->debit;
					$totalcredit += $bookkeeping->credit;

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
						{
							$error++;
							$errorforline++;
							//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
						}
						else
						{
							$error++;
							$errorforline++;
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
					}
				}
			}
		}

		if ($totaldebit != $totalcredit)
		{
			$error++;
			$errorforline++;
			setEventMessages('Try to insert a non balanced transaction in book for '.$val["ref"].'. Canceled. Surely a bug.', null, 'errors');
		}

		if (! $errorforline)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();

			if ($error >= 10)
			{
			    setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
			    break;  // Break in the foreach
			}
		}
	}

	$tabpay = $taber;

	if (empty($error) && count($tabpay) > 0) {
		setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
	}
	elseif (count($tabpay) == $error)
	{
		setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
	}
	else
	{
		setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
	}

	$action='';

	// Must reload data, so we make a redirect
	if (count($tabpay) != $error)
	{
		$param='id_journal='.$id_journal;
		$param.='&date_startday='.$date_startday;
		$param.='&date_startmonth='.$date_startmonth;
		$param.='&date_startyear='.$date_startyear;
		$param.='&date_endday='.$date_endday;
		$param.='&date_endmonth='.$date_endmonth;
		$param.='&date_endyear='.$date_endyear;
		$param.='&in_bookkeeping='.$in_bookkeeping;
		header("Location: ".$_SERVER['PHP_SELF'].($param?'?'.$param:''));
		exit;
	}
}

/*
 * View
 */

$form = new Form($db);

$userstatic = new User($db);

// Export
/*if ($action == 'exportcsv') {
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	// Model Cegid Expert Export
	if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 2) {
		$sep = ";";

		foreach ( $taber as $key => $val ) {
			$date = dol_print_date($val["date"], '%d%m%Y');

			// Fees
			foreach ( $tabht[$key] as $k => $mt ) {
				$userstatic->id = $tabuser[$key]['id'];
				$userstatic->name = $tabuser[$key]['name'];
				$userstatic->client = $tabuser[$key]['code_client'];

				if ($mt) {
					print $date . $sep;
					print $journal . $sep;
					print length_accountg(html_entity_decode($k)) . $sep;
					print $sep;
					print ($mt < 0 ? 'C' : 'D') . $sep;
					print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
					print dol_trunc($val["comments"], 32) . $sep;
					print $val["ref"];
					print "\n";
				}
			}

			// VAT
			foreach ( $tabtva[$key] as $k => $mt ) {
				if ($mt) {
					print $date . $sep;
					print $journal . $sep;
					print length_accountg(html_entity_decode($k)) . $sep;
					print $sep;
					print ($mt < 0 ? 'C' : 'D') . $sep;
					print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
					print $langs->trans("VAT") . $sep;
					print $val["ref"];
					print "\n";
				}
			}

			foreach ( $tabttc[$key] as $k => $mt ) {
				print $date . $sep;
				print $journal . $sep;
				print length_accountg($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) . $sep;
				print length_accounta(html_entity_decode($k)) . $sep;
				print ($mt < 0 ? 'D' : 'C') . $sep;
				print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
				print $userstatic->name . $sep;
				print $val["ref"];
				print "\n";
			}
		}
	} elseif ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 1) {
		// Model Classic Export
		foreach ( $taber as $key => $val ) {
			$date = dol_print_date($val["date"], 'day');

			$userstatic->id = $tabuser[$key]['id'];
			$userstatic->name = $tabuser[$key]['name'];

			// Fees
			foreach ( $tabht[$key] as $k => $mt ) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch(null, $k, true);
				if ($mt) {
					print '"' . $date . '"' . $sep;
					print '"' . $val["ref"] . '"' . $sep;
					print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
					print '"' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"';
					print "\n";
				}
			}
			// VAT
			foreach ( $tabtva[$key] as $k => $mt ) {
				if ($mt) {
					print '"' . $date . '"' . $sep;
					print '"' . $val["ref"] . '"' . $sep;
					print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
					print '"' . dol_trunc($langs->trans("VAT")) . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"';
					print "\n";
				}
			}

			// Third party
			foreach ( $tabttc[$key] as $k => $mt ) {
				print '"' . $date . '"' . $sep;
				print '"' . $val["ref"] . '"' . $sep;
				print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
				print '"' . dol_trunc($userstatic->name) . '"' . $sep;
				print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
				print '"' . ($mt >= 0 ? price($mt) : '') . '"';
			}
			print "\n";
		}
	}
}
*/

if (empty($action) || $action == 'view') {

	llxHeader('', $langs->trans("ExpenseReportsJournal"));

	$nom = $langs->trans("ExpenseReportsJournal") . ' - ' . $accountingjournalstatic->getNomUrl(1);
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate=dol_now();
	$description.= $langs->trans("DescJournalOnlyBindedVisible").'<br>';

	$listofchoices=array('already'=>$langs->trans("AlreadyInGeneralLedger"), 'notyet'=>$langs->trans("NotYetInGeneralLedger"));
	$period = $form->select_date($date_start?$date_start:-1, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end?$date_end:-1, 'date_end', 0, 0, 0, '', 1, 0, 1). ' -  ' .$langs->trans("JournalizationInLedgerStatus").' '. $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

	$varlink = 'id_journal=' . $id_journal;

	journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

	// Button to write into Ledger
	if (empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) || $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT == '-1') {
		print '<br>'.img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
		print ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	}
	print '<div class="tabsAction tabsActionNoBottom">';
	if (empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) || $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT == '-1') {
		print '<input type="button" class="butActionRefused" title="'.dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")).'" value="' . $langs->trans("WriteBookKeeping") . '" />';
	}
	else {
		if ($in_bookkeeping == 'notyet') print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
		else print '<a href="#" class="butActionRefused" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
	}
	//print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
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
	print "<td></td>";
	print "<td>" . $langs->trans("Date") . "</td>";
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("ExpenseReportRef") . ")</td>";
	print "<td>" . $langs->trans("AccountAccounting") . "</td>";
	print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
	print "<td>" . $langs->trans("LabelOperation") . "</td>";
	print "<td align='right'>" . $langs->trans("Debit") . "</td>";
	print "<td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$r = '';

	$expensereportstatic = new ExpenseReport($db);
	$expensereportlinestatic = new ExpenseReportLine($db);

	foreach ( $taber as $key => $val ) {
		$expensereportstatic->id = $key;
		$expensereportstatic->ref = $val["ref"];
		$expensereportlinestatic->comments = html_entity_decode(dol_trunc($val["comments"], 32));

		$date = dol_print_date($val["date"], 'day');

		// Fees
		foreach ( $tabht[$key] as $k => $mt ) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k, true);

			if ($mt) {
				print '<tr class="oddeven">';
				print "<td><!-- Fees --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
				$userstatic->id = $tabuser[$key]['id'];
				$userstatic->name = $tabuser[$key]['name'];
				// Account
				print "<td>";
				$accountoshow = length_accountg($k);
				if (empty($accountoshow) || $accountoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("FeeAccountNotDefined").'</span>';
				}
				else print $accountoshow;
				print '</td>';
				// Subledger account
				print "<td>";
				print '</td>';
				$userstatic->id = $tabuser[$key]['id'];
				$userstatic->name = $tabuser[$key]['name'];
				print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $accountingaccount->label . "</td>";
				print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print '<td align="right">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "</tr>";
			}
		}

		// Third party
		foreach ( $tabttc[$key] as $k => $mt ) {
			print '<tr class="oddeven">';
			print "<td><!-- Thirdparty --></td>";
			print "<td>" . $date . "</td>";
			print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
			$userstatic->id = $tabuser[$key]['id'];
			$userstatic->name = $tabuser[$key]['name'];
			// Account
			print "<td>";
			$accountoshow = length_accounta($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT);
			if (empty($accountoshow) || $accountoshow == 'NotDefined')
			{
				print '<span class="error">'.$langs->trans("MainAccountForUsersNotDefined").'</span>';
			}
			else print $accountoshow;
			print "</td>";
			// Subledger account
			print "<td>";
			$accountoshow = length_accounta($k);
			if (empty($accountoshow) || $accountoshow == 'NotDefined')
			{
				print '<span class="error">'.$langs->trans("UserAccountNotDefined").'</span>';
			}
			else print $accountoshow;
			print '</td>';
			print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $langs->trans("SubledgerAccount") . "</td>";
			print '<td align="right">' . ($mt < 0 ? - price(- $mt) : '') . "</td>";
			print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
			print "</tr>";
		}

		// VAT
		$listoftax = array(0, 1, 2);
		foreach ($listoftax as $numtax) {
			$arrayofvat = $tabtva;
			if ($numtax == 1) $arrayofvat = $tablocaltax1;
			if ($numtax == 2) $arrayofvat = $tablocaltax2;

			foreach ( $arrayofvat[$key] as $k => $mt ) {
			if ($mt) {
				print '<tr class="oddeven">';
				print "<td><!-- VAT --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
				// Account
				print "<td>";
				$accountoshow = length_accountg($k);
				if (empty($accountoshow) || $accountoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("VATAccountNotDefined").'</span>';
				}
				else print $accountoshow;
				print "</td>";
				// Subledger account
				print "<td>";
				print '</td>';
				print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $langs->trans("VAT"). ' '.join(', ',$def_tva[$key][$k]).' %'.($numtax?' - Localtax '.$numtax:'');
				print "</td>";
				print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print '<td align="right">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "</tr>";
			}
			}
		}
	}

	print "</table>";
	print '</div>';

	// End of page
	llxFooter();
}
$db->close();

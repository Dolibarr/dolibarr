<?php
/* Copyright (C) 2007-2010	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger		<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2013-2016	Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2013-2016	Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016	Florian Henry		<florian.henry@open-concept.pro>
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
 * \file	htdocs/accountancy/journal/expensereportsjournal.php
 * \ingroup	Advanced accountancy
 * \brief	Page with expense reports journal
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");
$langs->load("trips");

$date_startmonth = GETPOST('date_startmonth');
$date_startday = GETPOST('date_startday');
$date_startyear = GETPOST('date_startyear');
$date_endmonth = GETPOST('date_endmonth');
$date_endday = GETPOST('date_endday');
$date_endyear = GETPOST('date_endyear');

$now = dol_now();

// Security check
if ($user->societe_id > 0)
	accessforbidden();

$action = GETPOST('action');


/*
 * Actions
 */

$year_current = strftime("%Y", dol_now());
$pastmonth = strftime("%m", dol_now()) - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0) {
	$pastmonth = 12;
	$pastmonthyear --;
}

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
	$date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

$p = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
$idpays = $p[0];

$sql = "SELECT er.rowid, er.ref, er.date_debut as de,";
$sql .= " erd.rowid as erdid, erd.comments, erd.total_ttc, erd.tva_tx, erd.total_ht, erd.total_tva, erd.fk_code_ventilation,";
$sql .= " u.rowid as uid, u.firstname, u.lastname, u.accountancy_code as user_accountancy_account,";
$sql .= " f.accountancy_code, ct.accountancy_code_buy as account_tva, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte";
$sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_tva as ct ON erd.tva_tx = ct.taux AND ct.fk_pays = '" . $idpays . "'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
$sql .= " JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
$sql .= " JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = er.fk_user_author";
$sql .= " WHERE er.fk_statut > 0 ";
$sql .= " AND erd.fk_code_ventilation > 0 ";
$sql .= " AND er.entity IN (" . getEntity("expensereport", 0) . ")";  // We don't share object for accountancy
if ($date_start && $date_end)
	$sql .= " AND er.date_debut >= '" . $db->idate($date_start) . "' AND er.date_debut <= '" . $db->idate($date_end) . "'";
$sql .= " ORDER BY er.date_debut";

dol_syslog('accountancy/journal/expensereportsjournal.php:: $sql=' . $sql);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	// les variables
	$account_salary = (! empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT)) ? $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT : $langs->trans("CodeNotDef");
	$account_vat = (! empty($conf->global->ACCOUNTING_VAT_BUY_ACCOUNT)) ? $conf->global->ACCOUNTING_VAT_BUY_ACCOUNT : $langs->trans("CodeNotDef");

	$taber = array ();
	$tabht = array ();
	$tabtva = array ();
	$def_tva = array ();
	$tabttc = array ();
	$tabuser = array ();

	$i = 0;
	while ( $i < $num ) {
		$obj = $db->fetch_object($result);

		// Controls
		$compta_user = (! empty($obj->user_accountancy_account)) ? $obj->user_accountancy_account : $account_salary;
		$compta_fees = $obj->compte;
		$compta_tva = (! empty($obj->account_tva) ? $obj->account_tva : $account_vat);

		// Define array for display vat tx
		$def_tva[$obj->rowid]=price($obj->tva_tx);

		$taber[$obj->rowid]["date"] = $db->jdate($obj->de);
		$taber[$obj->rowid]["ref"] = $obj->ref;
		$taber[$obj->rowid]["comments"] = $obj->comments;
		$taber[$obj->rowid]["fk_expensereportdet"] = $obj->erdid;
		$tabttc[$obj->rowid][$compta_user] += $obj->total_ttc;
		$tabht[$obj->rowid][$compta_fees] += $obj->total_ht;
		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
		$tabuser[$obj->rowid] = array (
				'id' => $obj->uid,
				'name' => $obj->firstname.' '.$obj->lastname,
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

	foreach ($taber as $key => $val)
	{
		$errorforline = 0;

	    $db->begin();
	     
        if (! $errorforline)
        {
    	    foreach ( $tabttc[$key] as $k => $mt ) {
    			if ($mt) {
        	        // get compte id and label
        
        			$bookkeeping = new BookKeeping($db);
        			$bookkeeping->doc_date = $val["date"];
        			$bookkeeping->doc_ref = $val["ref"];
        			$bookkeeping->date_create = $now;
        			$bookkeeping->doc_type = 'expense_report';
        			$bookkeeping->fk_doc = $key;
        			$bookkeeping->fk_docdet = $val["fk_expensereportdet"];
        			$bookkeeping->code_tiers = $tabuser[$key]['user_accountancy_code'];
        			$bookkeeping->label_compte = $tabuser[$key]['name'];
        			$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
        			$bookkeeping->montant = $mt;
        			$bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
        			$bookkeeping->debit = ($mt <= 0) ? $mt : 0;
        			$bookkeeping->credit = ($mt > 0) ? $mt : 0;
        			$bookkeeping->code_journal = $conf->global->ACCOUNTING_EXPENSEREPORT_JOURNAL;
        			$bookkeeping->fk_user_author = $user->id;
        
        			$result = $bookkeeping->create($user);
        			if ($result < 0) {
        			    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
        			    {
        			        $error++;
        			        $errorforline++;
        			        //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->doc_ref.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
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
        
        if (! $errorforline)
        {
            // Fees
    		foreach ( $tabht[$key] as $k => $mt ) {
    			$accountingaccount = new AccountingAccount($db);
    			$accountingaccount->fetch(null, $k, true);
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
    					$bookkeeping->code_tiers = '';
    					$bookkeeping->label_compte = $accountingaccount->label;
    					$bookkeeping->numero_compte = $k;
    					$bookkeeping->montant = $mt;
    					$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
    					$bookkeeping->debit = ($mt > 0) ? $mt : 0;
    					$bookkeeping->credit = ($mt <= 0) ? $mt : 0;
    					$bookkeeping->code_journal = $conf->global->ACCOUNTING_EXPENSEREPORT_JOURNAL;
    					$bookkeeping->fk_user_author = $user->id;
    
    					$result = $bookkeeping->create($user);
	        			if ($result < 0) {
            			    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
            			    {
            			        $error++;
            			        $errorforline++;
            			        //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->doc_ref.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
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
        
        if (! $errorforline)
        {
            // VAT
    		// var_dump($tabtva);
    		foreach ( $tabtva[$key] as $k => $mt ) {
    			if ($mt) {
    				// get compte id and label
    				$bookkeeping = new BookKeeping($db);
    				$bookkeeping->doc_date = $val["date"];
    				$bookkeeping->doc_ref = $val["ref"];
    				$bookkeeping->date_create = $now;
    				$bookkeeping->doc_type = 'expense_report';
    				$bookkeeping->fk_doc = $key;
    				$bookkeeping->fk_docdet = $val["fk_expensereportdet"];
    				$bookkeeping->code_tiers = '';
    				$bookkeeping->label_compte = $langs->trans("VAT"). ' '.$def_tva[$key];
    				$bookkeeping->numero_compte = $k;
    				$bookkeeping->montant = $mt;
    				$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
    				$bookkeeping->debit = ($mt > 0) ? $mt : 0;
    				$bookkeeping->credit = ($mt <= 0) ? $mt : 0;
    				$bookkeeping->code_journal = $conf->global->ACCOUNTING_EXPENSEREPORT_JOURNAL;
    				$bookkeeping->fk_user_author = $user->id;
    
    				$result = $bookkeeping->create($user);
    				if ($result < 0) {
           			    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
        			    {
        			        $error++;
        			        $errorforline++;
        			        //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->doc_ref.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
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
        
		if (! $errorforline)
		{
		    $db->commit();
		}
		else
		{
		    $db->rollback();
		}
		
	}

	if (empty($error) && count($tabpay)) {
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
}

/*
 * View
 */

$form = new Form($db);

$userstatic = new User($db);

// Export
if ($action == 'export_csv') {
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
	$journal = $conf->global->ACCOUNTING_EXPENSEREPORT_JOURNAL;

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

if (empty($action) || $action == 'view') {

	llxHeader('', $langs->trans("ExpenseReportsJournal"));

	$nom = $langs->trans("ExpenseReportsJournal");
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate = time();
	$description.= $langs->trans("DescJournalOnlyBindedVisible").'<br>';

	$period = $form->select_date($date_start, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end, 'date_end', 0, 0, 0, '', 1, 0, 1);

	report_header($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''));

	/*if ($conf->global->ACCOUNTING_EXPORT_MODELCSV != 1 && $conf->global->ACCOUNTING_EXPORT_MODELCSV != 2) {
		print '<input type="button" class="butActionRefused" style="float: right;" value="' . $langs->trans("Export") . '" disabled="disabled" title="' . $langs->trans('ExportNotSupported') . '"/>';
	} else {
		print '<input type="button" class="butAction" style="float: right;" value="' . $langs->trans("Export") . '" onclick="launch_export();" />';
	}*/

    print '<div class="tabsAction">';
	print '<input type="button" class="butAction" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
    print '</div>';
    
	print '
	<script type="text/javascript">
		function launch_export() {
			$("div.fiche div.tabBar form input[name=\"action\"]").val("export_csv");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
			$("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			$("div.fiche div.tabBar form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
			$("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
	</script>';

	/*
	 * Show result array
	 */
	print '<br>';

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print "<td></td>";
	print "<td>" . $langs->trans("Date") . "</td>";
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("ExpenseReportRef") . ")</td>";
	print "<td>" . $langs->trans("Account") . "</td>";
	print "<td>" . $langs->trans("Label") . "</td>";
	print "<td align='right'>" . $langs->trans("Debit") . "</td>";
	print "<td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$var = true;
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
				print "<tr " . $bc[$var] . " >";
				print "<td><!-- Fees --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
				$userstatic->id = $tabuser[$key]['id'];
				$userstatic->name = $tabuser[$key]['name'];
				print "<td>";
				$accountoshow = length_accountg($k);
				if (empty($accountoshow) || $accountoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("FeeAccountNotDefined").'</span>';
				}
				else print $accountoshow;
				print "</td>";
				$userstatic->id = $tabuser[$key]['id'];
				$userstatic->name = $tabuser[$key]['name'];
				print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $accountingaccount->label . "</td>";
				print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print '<td align="right">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "</tr>";
			}
		}

		// VAT
		foreach ( $tabtva[$key] as $k => $mt ) {
			if ($mt) {
				print "<tr " . $bc[$var] . " >";
				print "<td><!-- VAT --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
				print "<td>";
				$accountoshow = length_accountg($k);
				if (empty($accountoshow) || $accountoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("VatAccountNotDefined").'</span>';
				}
				else print $accountoshow;
				print "</td>";
				print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $langs->trans("VAT"). ' '.$def_tva[$key]. "</td>";
				print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print '<td align="right">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "</tr>";
			}
		}

		// Third party
		foreach ( $tabttc[$key] as $k => $mt ) {
		    print "<tr " . $bc[$var] . ">";
		    print "<td><!-- Thirdparty --></td>";
		    print "<td>" . $date . "</td>";
			print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
			$userstatic->id = $tabuser[$key]['id'];
			$userstatic->name = $tabuser[$key]['name'];
			print "<td>";
			$accountoshow = length_accounta($k);
			if (empty($accountoshow) || $accountoshow == 'NotDefined')
			{
			    print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
			}
			else print $accountoshow;
			print "</td>";
			print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $langs->trans("Code_tiers") . "</td>";
			print '<td align="right">' . ($mt < 0 ? - price(- $mt) : '') . "</td>";
			print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
		    print "</tr>";
		}
		
		$var = ! $var;
	}

	print "</table>";

	// End of page
	llxFooter();
}
$db->close();

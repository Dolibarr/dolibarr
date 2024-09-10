<?php
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2024  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2013-2016  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 * \file		htdocs/accountancy/journal/sellsjournal.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Page with sells journal
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';

// Load translation files required by the page
$langs->loadLangs(array("commercial", "compta", "bills", "other", "accountancy", "errors"));

$id_journal = GETPOSTINT('id_journal');
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

$hookmanager->initHooks(array('sellsjournal'));
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


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks

$accountingaccount = new AccountingAccount($db);

// Get information of a journal
$accountingjournalstatic = new AccountingJournal($db);
$accountingjournalstatic->fetch($id_journal);
$journal = $accountingjournalstatic->code;
$journal_label = $accountingjournalstatic->label;

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_startmonth)) {
	// Period by default on transfer
	$dates = getDefaultDatesForTransfer();
	$date_start = $dates['date_start'];
	$pastmonthyear = $dates['pastmonthyear'];
	$pastmonth = $dates['pastmonth'];
}
if (empty($date_endmonth)) {
	// Period by default on transfer
	$dates = getDefaultDatesForTransfer();
	$date_end = $dates['date_end'];
	$pastmonthyear = $dates['pastmonthyear'];
	$pastmonth = $dates['pastmonth'];
}
if (getDolGlobalString('ACCOUNTANCY_JOURNAL_USE_CURRENT_MONTH')) {
	$pastmonth += 1;
}

if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
	$date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
	$date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

$sql = "SELECT f.rowid, f.ref, f.type, f.situation_cycle_ref, f.datef as df, f.ref_client, f.date_lim_reglement as dlr, f.close_code, f.retained_warranty, f.revenuestamp,";
$sql .= " fd.rowid as fdid, fd.description, fd.product_type, fd.total_ht, fd.total_tva, fd.total_localtax1, fd.total_localtax2, fd.tva_tx, fd.total_ttc, fd.situation_percent, fd.vat_src_code, fd.info_bits,";
$sql .= " s.rowid as socid, s.nom as name, s.code_client, s.code_fournisseur,";
if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
	$sql .= " spe.accountancy_code_customer as code_compta_client,";
	$sql .= " spe.accountancy_code_supplier as code_compta_fournisseur,";
} else {
	$sql .= " s.code_compta as code_compta_client,";
	$sql .= " s.code_compta_fournisseur,";
}
$sql .= " p.rowid as pid, p.ref as pref, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte,";
if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " ppe.accountancy_code_sell";
} else {
	$sql .= " p.accountancy_code_sell";
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = fd.fk_product";
if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = fd.fk_facture";
$sql .= " JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " WHERE fd.fk_code_ventilation > 0";
$sql .= " AND f.entity IN (".getEntity('invoice', 0).')'; // We don't share object for accountancy, we use source object sharing
$sql .= " AND f.fk_statut > 0";
if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {	// Non common setup
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_SITUATION.")";
} else {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_DEPOSIT.",".Facture::TYPE_SITUATION.")";
}
$sql .= " AND fd.product_type IN (0,1)";
if ($date_start && $date_end) {
	$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}
// Define begin binding date
if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND f.datef >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
}
// Already in bookkeeping or not
if ($in_bookkeeping == 'already') {
	$sql .= " AND f.rowid IN (SELECT fk_doc FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";
	//	$sql .= " AND fd.rowid IN (SELECT fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";		// Useless, we save one line for all products with same account
}
if ($in_bookkeeping == 'notyet') {
	$sql .= " AND f.rowid NOT IN (SELECT fk_doc FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";
	// $sql .= " AND fd.rowid NOT IN (SELECT fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";		// Useless, we save one line for all products with same account
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " ORDER BY f.datef, f.ref";
//print $sql;

dol_syslog('accountancy/journal/sellsjournal.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$tabfac = array();
	$tabht = array();
	$tabtva = array();
	$def_tva = array();
	$tabwarranty = array();
	$tabrevenuestamp = array();
	$tabttc = array();
	$tablocaltax1 = array();
	$tablocaltax2 = array();
	$tabcompany = array();
	$vatdata_cache = array();

	$num = $db->num_rows($result);

	// Variables
	$cptcli = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER', 'NotDefined');
	$cpttva = getDolGlobalString('ACCOUNTING_VAT_SOLD_ACCOUNT', 'NotDefined');

	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);

		// Controls
		$compta_soc = (!empty($obj->code_compta_client)) ? $obj->code_compta_client : $cptcli;

		$compta_prod = $obj->compte;
		if (empty($compta_prod)) {
			if ($obj->product_type == 0) {
				$compta_prod = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT', 'NotDefined');
			} else {
				$compta_prod = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT', 'NotDefined');
			}
		}

		//$compta_revenuestamp = getDolGlobalString('ACCOUNTING_REVENUESTAMP_SOLD_ACCOUNT', 'NotDefined');

		$tax_id = $obj->tva_tx . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : '');
		if (array_key_exists($tax_id, $vatdata_cache)) {
			$vatdata = $vatdata_cache[$tax_id];
		} else {
			$vatdata = getTaxesFromId($tax_id, $mysoc, $mysoc, 0);
			$vatdata_cache[$tax_id] = $vatdata;
		}
		$compta_tva = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);
		$compta_localtax1 = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);
		$compta_localtax2 = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);

		// Define the array to store the detail of each vat rate and code for lines
		if (price2num($obj->tva_tx) || !empty($obj->vat_src_code)) {
			$def_tva[$obj->rowid][$compta_tva][vatrate($obj->tva_tx).($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : '')] = (vatrate($obj->tva_tx).($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''));
		}

		// Create a compensation rate for situation invoice.
		$situation_ratio = 1;
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
			if ($obj->situation_cycle_ref) {
				// Avoid divide by 0
				if ($obj->situation_percent == 0) {
					$situation_ratio = 0;
				} else {
					$line = new FactureLigne($db);
					$line->fetch($obj->fdid);

					// Situation invoices handling
					$prev_progress = $line->get_prev_progress($obj->rowid);

					$situation_ratio = ($obj->situation_percent - $prev_progress) / $obj->situation_percent;
				}
			}
		}

		$revenuestamp = (float) price2num($obj->revenuestamp, 'MT');

		// Invoice lines
		$tabfac[$obj->rowid]["date"] = $db->jdate($obj->df);
		$tabfac[$obj->rowid]["datereg"] = $db->jdate($obj->dlr);
		$tabfac[$obj->rowid]["ref"] = $obj->ref;
		$tabfac[$obj->rowid]["type"] = $obj->type;
		$tabfac[$obj->rowid]["description"] = $obj->label_compte;
		$tabfac[$obj->rowid]["close_code"] = $obj->close_code; // close_code = 'replaced' for replacement invoices (not used in most european countries)
		$tabfac[$obj->rowid]["revenuestamp"] = $revenuestamp;
		//$tabfac[$obj->rowid]["fk_facturedet"] = $obj->fdid;

		// Avoid warnings
		if (!isset($tabttc[$obj->rowid][$compta_soc])) {
			$tabttc[$obj->rowid][$compta_soc] = 0;
		}
		if (!isset($tabht[$obj->rowid][$compta_prod])) {
			$tabht[$obj->rowid][$compta_prod] = 0;
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

		// Compensation of data for invoice situation by using $situation_ratio. This works (nearly) for invoice that was not correctly recorded
		// but it may introduces an error for situation invoices that were correctly saved. There is still rounding problem that differs between
		// real data we should have stored and result obtained with a compensation.
		// It also seems that credit notes on situation invoices are correctly saved (but it depends on the version used in fact).
		// For credit notes, we hope to have situation_ratio = 1 so the compensation has no effect to avoid introducing troubles with credit notes.
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
			$total_ttc = $obj->total_ttc * $situation_ratio;
		} else {
			$total_ttc = $obj->total_ttc;
		}

		// Move a part of the retained warrenty into the account of warranty
		if (getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY') && $obj->retained_warranty > 0) {
			$retained_warranty = (float) price2num($total_ttc * $obj->retained_warranty / 100, 'MT');	// Calculate the amount of warrenty for this line (using the percent value)
			$tabwarranty[$obj->rowid][$compta_soc] += $retained_warranty;
			$total_ttc -= $retained_warranty;
		}

		$tabttc[$obj->rowid][$compta_soc] += $total_ttc;
		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht * $situation_ratio;
		$tva_npr = ((($obj->info_bits & 1) == 1) ? 1 : 0);
		if (!$tva_npr) { // We ignore line if VAT is a NPR
			if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
				$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
				$tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1;
				$tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2;
			} else {
				$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva * $situation_ratio;
				$tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1 * $situation_ratio;
				$tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2 * $situation_ratio;
			}
		}

		$compta_revenuestamp = 'NotDefined';
		if (!empty($revenuestamp)) {
			$sqlrevenuestamp = "SELECT accountancy_code_sell FROM ".MAIN_DB_PREFIX."c_revenuestamp";
			$sqlrevenuestamp .= " WHERE fk_pays = ".((int) $mysoc->country_id);
			$sqlrevenuestamp .= " AND taux = ".((float) $revenuestamp);
			$sqlrevenuestamp .= " AND active = 1";
			$resqlrevenuestamp = $db->query($sqlrevenuestamp);

			if ($resqlrevenuestamp) {
				$num_rows_revenuestamp = $db->num_rows($resqlrevenuestamp);
				if ($num_rows_revenuestamp > 1) {
					dol_print_error($db, 'Failed 2 or more lines for the revenue stamp of your country. Check the dictionary of revenue stamp.');
				} else {
					$objrevenuestamp = $db->fetch_object($resqlrevenuestamp);
					if ($objrevenuestamp) {
						$compta_revenuestamp = $objrevenuestamp->accountancy_code_sell;
					}
				}
			}
		}

		if (empty($tabrevenuestamp[$obj->rowid][$compta_revenuestamp]) && !empty($revenuestamp)) {
			// The revenue stamp was never seen for this invoice id=$obj->rowid
			$tabttc[$obj->rowid][$compta_soc] += $obj->revenuestamp;
			$tabrevenuestamp[$obj->rowid][$compta_revenuestamp] = $obj->revenuestamp;
		}

		$tabcompany[$obj->rowid] = array(
			'id' => $obj->socid,
			'name' => $obj->name,
			'code_client' => $obj->code_client,
			'code_compta' => $compta_soc
		);

		$i++;

		// Check for too many lines.
		if ($i > getDolGlobalInt('ACCOUNTANCY_MAX_TOO_MANY_LINES_TO_PROCESS', 10000)) {
			$error++;
			setEventMessages("ErrorTooManyLinesToProcessPleaseUseAMoreSelectiveFilter", null, 'errors');
			break;
		}
	}

	// After the loop on each line
} else {
	dol_print_error($db);
}


$errorforinvoice = array();

/*
// Old way, 1 query for each invoice
// Loop on all invoices to detect lines without binded code (fk_code_ventilation <= 0)
foreach ($tabfac as $key => $val) {		// Loop on each invoice
	$sql = "SELECT COUNT(fd.rowid) as nb";
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= " WHERE fd.product_type <= 2 AND fd.fk_code_ventilation <= 0";
	$sql .= " AND fd.total_ttc <> 0 AND fk_facture = ".((int) $key);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj->nb > 0) {
			$errorforinvoice[$key] = 'somelinesarenotbound';
		}
	} else {
		dol_print_error($db);
	}
}
*/
// New way, single query, load all unbound lines

$sql = "
SELECT
    fk_facture,
    COUNT(fd.rowid) as nb
FROM
	".MAIN_DB_PREFIX."facturedet as fd
WHERE
    fd.product_type <= 2
    AND fd.fk_code_ventilation <= 0
    AND fd.total_ttc <> 0
	AND fk_facture IN (".$db->sanitize(implode(",", array_keys($tabfac))).")
GROUP BY fk_facture
";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		if ($obj->nb > 0) {
			$errorforinvoice[$obj->fk_facture_fourn] = 'somelinesarenotbound';
		}
		$i++;
	}
}
//var_dump($errorforinvoice);exit;

// Bookkeeping Write
if ($action == 'writebookkeeping' && !$error && $user->hasRight('accounting', 'bind', 'write')) {
	$now = dol_now();
	$error = 0;

	$companystatic = new Societe($db);
	$invoicestatic = new Facture($db);

	$accountingaccountcustomer = new AccountingAccount($db);
	$accountingaccountcustomer->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'), true);

	$accountingaccountcustomerwarranty = new AccountingAccount($db);
	$accountingaccountcustomerwarranty->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY'), true);

	foreach ($tabfac as $key => $val) {		// Loop on each invoice
		$errorforline = 0;

		$totalcredit = 0;
		$totaldebit = 0;

		$db->begin();		// We accept transaction into loop, so if we hang, we can continue transfer from the last error

		$companystatic->id = $tabcompany[$key]['id'];
		$companystatic->name = $tabcompany[$key]['name'];
		$companystatic->code_compta = $tabcompany[$key]['code_compta'];
		$companystatic->code_compta_client = $tabcompany[$key]['code_compta'];
		$companystatic->code_client = $tabcompany[$key]['code_client'];
		$companystatic->client = 3;

		$invoicestatic->id = $key;
		$invoicestatic->ref = (string) $val["ref"];
		$invoicestatic->type = $val["type"];
		$invoicestatic->close_code = $val["close_code"];

		$date = dol_print_date($val["date"], 'day');

		// Is it a replaced invoice? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
		$replacedinvoice = 0;
		if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
			$replacedinvoice = 1;
			$alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
			if ($alreadydispatched) {
				$replacedinvoice = 2;
			}
		}

		// If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating a replacement is not possible if invoice is accounted)
		if ($replacedinvoice == 1) {
			$db->rollback();
			continue;
		}

		// Error if some lines are not binded/ready to be journalized
		if (isset($errorforinvoice[$key]) && $errorforinvoice[$key] == 'somelinesarenotbound') {
			$error++;
			$errorforline++;
			setEventMessages($langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $val['ref']), null, 'errors');
		}

		// Warranty
		if (!$errorforline && getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
			if (isset($tabwarranty[$key]) && is_array($tabwarranty[$key])) {
				foreach ($tabwarranty[$key] as $k => $mt) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->date_lim_reglement = $val["datereg"];
					$bookkeeping->doc_ref = $val["ref"];
					$bookkeeping->date_creation = $now;
					$bookkeeping->doc_type = 'customer_invoice';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = 0; // Useless, can be several lines that are the source of this record to add
					$bookkeeping->thirdparty_code = $companystatic->code_client;

					$bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
					$bookkeeping->subledger_label = $tabcompany[$key]['name'];

					$bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY');
					$bookkeeping->label_compte = $accountingaccountcustomerwarranty->label;

					$bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RetainedWarranty");
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
					$bookkeeping->debit = ($mt >= 0) ? $mt : 0;
					$bookkeeping->credit = ($mt < 0) ? -$mt : 0;
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $langs->transnoentities($journal_label);
					$bookkeeping->fk_user_author = $user->id;
					$bookkeeping->entity = $conf->entity;

					$totaldebit += $bookkeeping->debit;
					$totalcredit += $bookkeeping->credit;

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {    // Already exists
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

		// Thirdparty
		if (!$errorforline) {
			foreach ($tabttc[$key] as $k => $mt) {
				$bookkeeping = new BookKeeping($db);
				$bookkeeping->doc_date = $val["date"];
				$bookkeeping->date_lim_reglement = $val["datereg"];
				$bookkeeping->doc_ref = $val["ref"];
				$bookkeeping->date_creation = $now;
				$bookkeeping->doc_type = 'customer_invoice';
				$bookkeeping->fk_doc = $key;
				$bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
				$bookkeeping->thirdparty_code = $companystatic->code_client;

				$bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
				$bookkeeping->subledger_label = $tabcompany[$key]['name'];

				$bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER');
				$bookkeeping->label_compte = $accountingaccountcustomer->label;

				$bookkeeping->label_operation = dol_trunc($companystatic->name, 16).' - '.$invoicestatic->ref.' - '.$langs->trans("SubledgerAccount");
				$bookkeeping->montant = $mt;
				$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
				$bookkeeping->debit = ($mt >= 0) ? $mt : 0;
				$bookkeeping->credit = ($mt < 0) ? -$mt : 0;
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
				} else {
					if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
						require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
						$lettering_static = new Lettering($db);

						$nb_lettering = $lettering_static->bookkeepingLettering(array($bookkeeping->id));
					}
				}
			}
		}

		// Product / Service
		if (!$errorforline) {
			foreach ($tabht[$key] as $k => $mt) {
				if (empty($conf->cache['accountingaccountincurrententity'][$k])) {
					$accountingaccount = new AccountingAccount($db);
					$accountingaccount->fetch(0, $k, true);
					$conf->cache['accountingaccountincurrententity'][$k] = $accountingaccount;
				} else {
					$accountingaccount = $conf->cache['accountingaccountincurrententity'][$k];
				}

				$label_account = $accountingaccount->label;

				// get compte id and label
				if ($accountingaccount->id > 0) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->date_lim_reglement = $val["datereg"];
					$bookkeeping->doc_ref = $val["ref"];
					$bookkeeping->date_creation = $now;
					$bookkeeping->doc_type = 'customer_invoice';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
					$bookkeeping->thirdparty_code = $companystatic->code_client;

					if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT')) {
						if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT')) {
							$bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
							$bookkeeping->subledger_label = $tabcompany[$key]['name'];
						} else {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
						}
					} else {
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
					}

					$bookkeeping->numero_compte = $k;
					$bookkeeping->label_compte = $label_account;

					$bookkeeping->label_operation = dol_trunc($companystatic->name, 16).' - '.$invoicestatic->ref.' - '.$label_account;
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
					$bookkeeping->debit = ($mt < 0) ? -$mt : 0;
					$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
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
						$label_account = $accountingaccount->label;

						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->date_lim_reglement = $val["datereg"];
						$bookkeeping->doc_ref = $val["ref"];
						$bookkeeping->date_creation = $now;
						$bookkeeping->doc_type = 'customer_invoice';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
						$bookkeeping->thirdparty_code = $companystatic->code_client;

						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';

						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = $label_account;


						$bookkeeping->label_operation = dol_trunc($companystatic->name, 16).' - '.$invoicestatic->ref;
						$tmpvatrate = (empty($def_tva[$key][$k]) ? (empty($arrayofvat[$key][$k]) ? '' : $arrayofvat[$key][$k]) : implode(', ', $def_tva[$key][$k]));
						$bookkeeping->label_operation .= ' - '.$langs->trans("Taxes").' '.$tmpvatrate.' %';
						$bookkeeping->label_operation .= ($numtax ? ' - Localtax '.$numtax : '');

						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
						$bookkeeping->debit = ($mt < 0) ? -$mt : 0;
						$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
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

		// Revenue stamp
		if (!$errorforline) {
			if (isset($tabrevenuestamp[$key]) && is_array($tabrevenuestamp[$key])) {
				foreach ($tabrevenuestamp[$key] as $k => $mt) {
					if ($mt) {
						$accountingaccount->fetch(null, $k, true);    // TODO Use a cache for label
						$label_account = $accountingaccount->label;

						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->date_lim_reglement = $val["datereg"];
						$bookkeeping->doc_ref = $val["ref"];
						$bookkeeping->date_creation = $now;
						$bookkeeping->doc_type = 'customer_invoice';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
						$bookkeeping->thirdparty_code = $companystatic->code_client;

						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';

						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = $label_account;

						$bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RevenueStamp");
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
						$bookkeeping->debit = ($mt < 0) ? -$mt : 0;
						$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $langs->transnoentities($journal_label);
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->entity = $conf->entity;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

						$result = $bookkeeping->create($user);
						if ($result < 0) {
							if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {    // Already exists
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

		// Protection against a bug on lines before
		if (!$errorforline && (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT'))) {
			$error++;
			$errorforline++;
			$errorforinvoice[$key] = 'amountsnotbalanced';
			setEventMessages('We Tried to insert a non balanced transaction in book for '.$invoicestatic->ref.'. Canceled. Surely a bug.', null, 'errors');
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

	$tabpay = $tabfac;

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

// Export
if ($action == 'exportcsv' && !$error) {		// ISO and not UTF8 !
	// Note that to have the button to get this feature enabled, you must enable ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL
	$sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

	$filename = 'journal';
	$type_export = 'journal';
	include DOL_DOCUMENT_ROOT.'/accountancy/tpl/export_journal.tpl.php';

	$companystatic = new Client($db);
	$invoicestatic = new Facture($db);

	foreach ($tabfac as $key => $val) {
		$companystatic->id = $tabcompany[$key]['id'];
		$companystatic->name = $tabcompany[$key]['name'];
		$companystatic->code_compta = $tabcompany[$key]['code_compta'];				// deprecated
		$companystatic->code_compta_client = $tabcompany[$key]['code_compta'];
		$companystatic->code_client = $tabcompany[$key]['code_client'];
		$companystatic->client = 3;

		$invoicestatic->id = $key;
		$invoicestatic->ref = (string) $val["ref"];
		$invoicestatic->type = $val["type"];
		$invoicestatic->close_code = $val["close_code"];

		$date = dol_print_date($val["date"], 'day');

		// Is it a replaced invoice? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
		$replacedinvoice = 0;
		if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
			$replacedinvoice = 1;
			$alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
			if ($alreadydispatched) {
				$replacedinvoice = 2;
			}
		}

		// If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
		if ($replacedinvoice == 1) {
			continue;
		}

		// Warranty
		if (getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY') && isset($tabwarranty[$key])) {
			foreach ($tabwarranty[$key] as $k => $mt) {
				//if ($mt) {
				print '"'.$key.'"'.$sep;
				print '"'.$date.'"'.$sep;
				print '"'.$val["ref"].'"'.$sep;
				print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1').'"'.$sep;
				print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
				print '"'.length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY')).'"'.$sep;
				print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
				print '"'.$langs->trans("Thirdparty").'"'.$sep;
				print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1').' - '.$invoicestatic->ref.' - '.$langs->trans("RetainedWarranty").'"'.$sep;
				print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
				print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
				print '"'.$journal.'"';
				print "\n";
				//}
			}
		}

		// Third party
		foreach ($tabttc[$key] as $k => $mt) {
			//if ($mt) {
			print '"'.$key.'"'.$sep;
			print '"'.$date.'"'.$sep;
			print '"'.$val["ref"].'"'.$sep;
			print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1').'"'.$sep;
			print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
			print '"'.length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER')).'"'.$sep;
			print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
			print '"'.$langs->trans("Thirdparty").'"'.$sep;
			print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1').' - '.$invoicestatic->ref.' - '.$langs->trans("Thirdparty").'"'.$sep;
			print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
			print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
			print '"'.$journal.'"';
			print "\n";
			//}
		}

		// Product / Service
		foreach ($tabht[$key] as $k => $mt) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k, true);
			//if ($mt) {
			print '"'.$key.'"'.$sep;
			print '"'.$date.'"'.$sep;
			print '"'.$val["ref"].'"'.$sep;
			print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1').'"'.$sep;
			print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
			print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
			print '""'.$sep;
			print '"'.mb_convert_encoding(dol_trunc($accountingaccount->label, 32), 'ISO-8859-1').'"'.$sep;
			print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1').' - '.dol_trunc($accountingaccount->label, 32).'"'.$sep;
			print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
			print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
			print '"'.$journal.'"';
			print "\n";
			//}
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
					print '"'.$key.'"'.$sep;
					print '"'.$date.'"'.$sep;
					print '"'.$val["ref"].'"'.$sep;
					print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1').'"'.$sep;
					print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
					print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
					print '""'.$sep;
					print '"'.$langs->trans("VAT").' - '.implode(', ', $def_tva[$key][$k]).' %"'.$sep;
					print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1').' - '.$invoicestatic->ref.' - '.$langs->trans("VAT").implode(', ', $def_tva[$key][$k]).' %'.($numtax ? ' - Localtax '.$numtax : '').'"'.$sep;
					print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
					print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
					print '"'.$journal.'"';
					print "\n";
				}
			}
		}

		// Revenue stamp
		if (isset($tabrevenuestamp[$key])) {
			foreach ($tabrevenuestamp[$key] as $k => $mt) {
				//if ($mt) {
				print '"'.$key.'"'.$sep;
				print '"'.$date.'"'.$sep;
				print '"'.$val["ref"].'"'.$sep;
				print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1').'"'.$sep;
				print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
				print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
				print '""'.$sep;
				print '"'.$langs->trans("RevenueStamp").'"'.$sep;
				print '"'.mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1').' - '.$invoicestatic->ref.' - '.$langs->trans("RevenueStamp").'"'.$sep;
				print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
				print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
				print '"'.$journal.'"';
				print "\n";
				//}
			}
		}
	}
}



if (empty($action) || $action == 'view') {
	$title = $langs->trans("GenerationOfAccountingEntries").' - '.$accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);
	$help_url ='EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#G&eacute;n&eacute;ration_des_&eacute;critures_en_comptabilit&eacute;';
	llxHeader('', dol_string_nohtmltag($title), $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-generation page-sellsjournal');

	$nom = $title;
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate = dol_now();
	$description = $langs->trans("DescJournalOnlyBindedVisible").'<br>';
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= $langs->trans("DepositsAreIncluded");
	}

	$listofchoices = array('notyet' => $langs->trans("NotYetInGeneralLedger"), 'already' => $langs->trans("AlreadyInGeneralLedger"));
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
	$acctCustomerNotConfigured = in_array(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'), ['','-1']);
	if ($acctCustomerNotConfigured) {
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
	if ($acctCustomerNotConfigured) {
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

	print '<div class="div-table-responsive">';
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print "<td>".$langs->trans("Date")."</td>";
	print "<td>".$langs->trans("Piece").' ('.$langs->trans("InvoiceRef").")</td>";
	print "<td>".$langs->trans("AccountAccounting")."</td>";
	print "<td>".$langs->trans("SubledgerAccount")."</td>";
	print "<td>".$langs->trans("LabelOperation")."</td>";
	print '<td class="center">'.$langs->trans("AccountingDebit")."</td>";
	print '<td class="center">'.$langs->trans("AccountingCredit")."</td>";
	print "</tr>\n";

	$i = 0;

	$companystatic = new Client($db);
	$invoicestatic = new Facture($db);

	foreach ($tabfac as $key => $val) {
		$companystatic->id = $tabcompany[$key]['id'];
		$companystatic->name = $tabcompany[$key]['name'];
		$companystatic->code_compta = $tabcompany[$key]['code_compta'];
		$companystatic->code_compta_client = $tabcompany[$key]['code_compta'];
		$companystatic->code_client = $tabcompany[$key]['code_client'];
		$companystatic->client = 3;

		$invoicestatic->id = $key;
		$invoicestatic->ref = (string) $val["ref"];
		$invoicestatic->type = $val["type"];
		$invoicestatic->close_code = $val["close_code"];

		$date = dol_print_date($val["date"], 'day');

		// Is it a replaced invoice? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
		$replacedinvoice = 0;
		if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
			$replacedinvoice = 1;
			$alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
			if ($alreadydispatched) {
				$replacedinvoice = 2;
			}
		}

		// If not already into bookkeeping, we won't add it, if yes, add the counterpart ???.
		if ($replacedinvoice == 1) {
			print '<tr class="oddeven">';
			print "<!-- Replaced invoice -->";
			print "<td>".$date."</td>";
			print "<td><strike>".$invoicestatic->getNomUrl(1)."</strike></td>";
			// Account
			print "<td>";
			print $langs->trans("Replaced");
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
			continue;
		}
		if (isset($errorforinvoice[$key]) && $errorforinvoice[$key] == 'somelinesarenotbound') {
			print '<tr class="oddeven">';
			print "<!-- Some lines are not bound -->";
			print "<td>".$date."</td>";
			print "<td>".$invoicestatic->getNomUrl(1)."</td>";
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

		// Warranty
		if (getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY') && isset($tabwarranty[$key]) && is_array($tabwarranty[$key])) {
			foreach ($tabwarranty[$key] as $k => $mt) {
				print '<tr class="oddeven">';
				print "<!-- Thirdparty warranty -->";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				// Account
				print "<td>";
				$accountoshow = length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY'));
				if (($accountoshow == "") || $accountoshow == 'NotDefined') {
					print '<span class="error">' . $langs->trans("MainAccountForRetainedWarrantyNotDefined") . '</span>';
				} else {
					print $accountoshow;
				}
				print '</td>';
				// Subledger account
				print "<td>";
				$accountoshow = length_accounta($k);
				if (($accountoshow == "") || $accountoshow == 'NotDefined') {
					print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
				} else {
					print $accountoshow;
				}
				print '</td>';
				print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RetainedWarranty") . "</td>";
				print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
				print "</tr>";
			}
		}

		// Third party
		foreach ($tabttc[$key] as $k => $mt) {
			print '<tr class="oddeven">';
			print "<!-- Thirdparty -->";
			print "<td>".$date."</td>";
			print "<td>".$invoicestatic->getNomUrl(1)."</td>";
			// Account
			print "<td>";
			$accountoshow = length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'));
			if (($accountoshow == "") || $accountoshow == 'NotDefined') {
				print '<span class="error">'.$langs->trans("MainAccountForCustomersNotDefined").'</span>';
			} else {
				print $accountoshow;
			}
			print '</td>';
			// Subledger account
			print "<td>";
			$accountoshow = length_accounta($k);
			if (($accountoshow == "") || $accountoshow == 'NotDefined') {
				print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
			} else {
				print $accountoshow;
			}
			print '</td>';
			print "<td>".$companystatic->getNomUrl(0, 'customer', 16).' - '.$invoicestatic->ref.' - '.$langs->trans("SubledgerAccount")."</td>";
			print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
			print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
			print "</tr>";

			$i++;
		}

		// Product / Service
		foreach ($tabht[$key] as $k => $mt) {
			if (empty($conf->cache['accountingaccountincurrententity'][$k])) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch(0, $k, true);
				$conf->cache['accountingaccountincurrententity'][$k] = $accountingaccount;
			} else {
				$accountingaccount = $conf->cache['accountingaccountincurrententity'][$k];
			}

			print '<tr class="oddeven">';
			print "<!-- Product -->";
			print "<td>".$date."</td>";
			print "<td>".$invoicestatic->getNomUrl(1)."</td>";
			// Account
			print "<td>";
			$accountoshow = length_accountg($k);
			if (($accountoshow == "") || $accountoshow == 'NotDefined') {
				print '<span class="error">'.$langs->trans("ProductNotDefined").'</span>';
			} else {
				print $accountoshow;
			}
			print "</td>";
			// Subledger account
			print "<td>";
			if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT')) {
				if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT')) {
					print length_accounta($tabcompany[$key]['code_compta']);
				}
			} elseif (($accountoshow == "") || $accountoshow == 'NotDefined') {
				print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
			}
			print '</td>';
			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];
			print "<td>".$companystatic->getNomUrl(0, 'customer', 16).' - '.$invoicestatic->ref.' - '.$accountingaccount->label."</td>";
			print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
			print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
			print "</tr>";

			$i++;
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

			// $key is id of invoice
			foreach ($arrayofvat[$key] as $k => $mt) {
				if ($mt) {
					print '<tr class="oddeven">';
					print "<!-- VAT -->";
					print "<td>".$date."</td>";
					print "<td>".$invoicestatic->getNomUrl(1)."</td>";
					// Account
					print "<td>";
					$accountoshow = length_accountg($k);
					if (($accountoshow == "") || $accountoshow == 'NotDefined') {
						print '<span class="error">'.$langs->trans("VATAccountNotDefined").' ('.$langs->trans("AccountingJournalType2").')</span>';
					} else {
						print $accountoshow;
					}
					print "</td>";
					// Subledger account
					print "<td>";
					print '</td>';
					print "<td>".$companystatic->getNomUrl(0, 'customer', 16).' - '.$invoicestatic->ref;
					// $def_tva is array[invoiceid][accountancy_code_sell_of_vat_rate_found][vatrate]=vatrate
					//var_dump($arrayofvat[$key]); //var_dump($key); //var_dump($k);
					$tmpvatrate = (empty($def_tva[$key][$k]) ? (empty($arrayofvat[$key][$k]) ? '' : $arrayofvat[$key][$k]) : implode(', ', $def_tva[$key][$k]));
					print ' - '.$langs->trans("Taxes").' '.$tmpvatrate.' %';
					print($numtax ? ' - Localtax '.$numtax : '');
					print "</td>";
					print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
					print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
					print "</tr>";

					$i++;
				}
			}
		}

		// Revenue stamp
		if (isset($tabrevenuestamp[$key]) && is_array($tabrevenuestamp[$key])) {
			foreach ($tabrevenuestamp[$key] as $k => $mt) {
				print '<tr class="oddeven">';
				print "<!-- Thirdparty revenuestamp -->";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				// Account
				print "<td>";
				$accountoshow = length_accountg($k);
				if (($accountoshow == "") || $accountoshow == 'NotDefined') {
					print '<span class="error">' . $langs->trans("MainAccountForRevenueStampSaleNotDefined") . '</span>';
				} else {
					print $accountoshow;
				}
				print '</td>';
				// Subledger account
				print "<td>";
				print '</td>';
				print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RevenueStamp") . "</td>";
				print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
				print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print "</tr>";
			}
		}
	}

	if (!$i) {
		print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';

	// End of page
	llxFooter();
}

$db->close();

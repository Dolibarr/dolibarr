<?php
/* Copyright (C) 2007-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2013-2026	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
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
 * \file		htdocs/accountancy/journal/inventoryjournal.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Page with inventory / stock movements journal (Perpetual Inventory & COGS)
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

// Load translation files required by the page
$langs->loadLangs(array("commercial", "compta", "bills", "other", "accountancy", "products", "stocks", "sendings", "receptions"));

$id_journal = GETPOSTINT('id_journal');
$action = GETPOST('action', 'aZ09');

$date_startmonth = GETPOSTINT('date_startmonth');
$date_startday = GETPOSTINT('date_startday');
$date_startyear = GETPOSTINT('date_startyear');
$date_endmonth = GETPOSTINT('date_endmonth');
$date_endday = GETPOSTINT('date_endday');
$date_endyear = GETPOSTINT('date_endyear');
$in_bookkeeping = GETPOST('in_bookkeeping', 'aZ09');
if ($in_bookkeeping == '') {
	$in_bookkeeping = 'notyet';
}
$search_type_movement = GETPOST('search_type_movement', 'aZ09');
if ($search_type_movement == '') {
	$search_type_movement = 'all';
}

$now = dol_now();

$hookmanager->initHooks(array('inventoryjournal'));
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
$errorformovement = array();

$tabmov = array();
$tabdebit = array();
$tabcredit = array();


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

$pastmonth = null;
$pastmonthyear = null;

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

if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
	$date_start = dol_get_first_day((int) $pastmonthyear, (int) $pastmonth, false);
	$date_end = dol_get_last_day((int) $pastmonthyear, (int) $pastmonth, false);
}

$sql = "SELECT m.rowid as mid, m.datem, m.value as qty, m.price, m.type_mouvement, m.label as movement_label,";
$sql .= " m.inventorycode, m.fk_origin, m.origintype,";
$sql .= " p.rowid as pid, p.ref as product_ref, p.label as product_label, p.pmp, p.cost_price,";
if (!getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export, p.accountancy_code_sell,";
} else {
	$sql .= " ppe.pmp as ppe_pmp, ppe.cost_price as ppe_cost_price,";
	$sql .= " ppe.accountancy_code_buy, ppe.accountancy_code_buy_intra, ppe.accountancy_code_buy_export, ppe.accountancy_code_sell,";
}
$sql .= " e.rowid as warehouse_id, e.ref as warehouse_ref, e.lieu as warehouse_lieu";
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".$db->prefix()."stock_mouvement as m";
$sql .= " JOIN ".$db->prefix()."product as p ON p.rowid = m.fk_product";
$sql .= " JOIN ".$db->prefix()."entrepot as e ON e.rowid = m.fk_entrepot";
if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " LEFT JOIN ".$db->prefix()."product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = ".((int) $conf->entity);
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);
$sql .= $hookmanager->resPrint;
$sql .= " WHERE m.value <> 0";
$sql .= " AND e.entity IN (".getEntity('stock', 0).")";
if ($date_start && $date_end) {
	$sql .= " AND m.datem >= '".$db->idate($date_start)."' AND m.datem <= '".$db->idate($date_end)."'";
}
// Define begin binding date
if (getDolGlobalInt('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND m.datem >= '".$db->idate(getDolGlobalInt('ACCOUNTING_DATE_START_BINDING'))."'";
}
// Movement direction filter
if ($search_type_movement == 'decrease') {
	$sql .= " AND m.value < 0";
} elseif ($search_type_movement == 'increase') {
	$sql .= " AND m.value > 0";
}
// Already in bookkeeping or not
if ($in_bookkeeping == 'already') {
	$sql .= " AND m.rowid IN (SELECT fk_doc FROM ".$db->prefix()."accounting_bookkeeping as ab WHERE ab.doc_type='stock_mouvement')";
}
if ($in_bookkeeping == 'notyet') {
	$sql .= " AND m.rowid NOT IN (SELECT fk_doc FROM ".$db->prefix()."accounting_bookkeeping as ab WHERE ab.doc_type='stock_mouvement')";
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);
$sql .= $hookmanager->resPrint;
$sql .= " ORDER BY m.datem ASC, m.rowid ASC";

dol_syslog('accountancy/journal/inventoryjournal.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	// Default accounts fallback
	$default_account_stock = getDolGlobalString('ACCOUNTING_PRODUCT_STOCK_ACCOUNT');
	if (empty($default_account_stock) || $default_account_stock == '-1') {
		$default_account_stock = '';
	}
	$default_account_buy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT', 'NotDefined');
	if (empty($default_account_buy) || $default_account_buy == '-1') {
		$default_account_buy = 'NotDefined';
	}
	$default_account_cogs = getDolGlobalString('ACCOUNTING_PRODUCT_COGS_ACCOUNT', 'NotDefined');
	if (empty($default_account_cogs) || $default_account_cogs == '-1') {
		$default_account_cogs = 'NotDefined';
	}

	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);

		$mid = (int) $obj->mid;
		$qty = (float) $obj->qty;

		// Resolve Inventory / Stock Asset account
		if (!empty($obj->accountancy_code_stock)) {
			$compta_stock = $obj->accountancy_code_stock;
		} elseif (!empty($default_account_stock)) {
			$compta_stock = $default_account_stock;
		} elseif (!empty($obj->accountancy_code_buy)) {
			$compta_stock = $obj->accountancy_code_buy;
		} else {
			$compta_stock = $default_account_buy;
		}

		// Resolve COGS / Stock variation account
		$compta_cogs = !empty($obj->accountancy_code_cogs) ? $obj->accountancy_code_cogs : '';
		if (empty($compta_cogs) || $compta_cogs == '-1') {
			$compta_cogs = $default_account_cogs;
		}

		// Determine unit cost for valuation:
		// On stock exit (sales, shipments, adjustments out): valuation MUST be at COST (PMP / WAC or cost price), NEVER at selling price!
		// On stock increase (reception, purchase): movement price is the purchase cost.
		$product_pmp = (float) (!empty($obj->ppe_pmp) ? $obj->ppe_pmp : $obj->pmp);
		$product_cost_price = (float) (!empty($obj->ppe_cost_price) ? $obj->ppe_cost_price : $obj->cost_price);

		$unit_cost = 0.0;
		if ($qty < 0) {
			// Stock exit (Shipment, Sale, Adjustment out): must relieve inventory and debit COGS at cost
			if ($product_pmp > 0) {
				$unit_cost = $product_pmp;
			} elseif ($product_cost_price > 0) {
				$unit_cost = $product_cost_price;
			} elseif ((float) $obj->price > 0 && empty($obj->fk_origin)) {
				$unit_cost = (float) $obj->price;
			}
		} else {
			// Stock increase (Reception, Purchase, Adjustment in): movement price is the purchase cost
			if ((float) $obj->price > 0) {
				$unit_cost = (float) $obj->price;
			} elseif ($product_pmp > 0) {
				$unit_cost = $product_pmp;
			} elseif ($product_cost_price > 0) {
				$unit_cost = $product_cost_price;
			}
		}

		$valuation_amount = (float) price2num(abs($qty) * $unit_cost, 'MT');

		$doc_ref = 'SM'.$mid.'-'.$obj->product_ref;
		if (!empty($obj->inventorycode)) {
			$doc_ref = $obj->inventorycode.'-SM'.$mid;
		}

		$tabmov[$mid] = array(
			'id' => $mid,
			'date' => $db->jdate($obj->datem),
			'qty' => $qty,
			'unit_cost' => $unit_cost,
			'amount' => $valuation_amount,
			'type_mouvement' => (int) $obj->type_mouvement,
			'label' => $obj->movement_label,
			'inventorycode' => $obj->inventorycode,
			'doc_ref' => $doc_ref,
			'fk_origin' => (int) $obj->fk_origin,
			'origintype' => (string) $obj->origintype,
			'fk_product' => (int) $obj->pid,
			'product_ref' => (string) $obj->product_ref,
			'product_label' => (string) $obj->product_label,
			'warehouse_id' => (int) $obj->warehouse_id,
			'warehouse_ref' => (string) $obj->warehouse_ref,
			'compta_stock' => $compta_stock,
			'compta_cogs' => $compta_cogs,
		);

		if ($qty < 0) {
			// Stock decrease (Shipment, Sale, Adjustment out): Debit COGS, Credit Inventory Asset
			$tabdebit[$mid][$compta_cogs] = $valuation_amount;
			$tabcredit[$mid][$compta_stock] = $valuation_amount;
		} else {
			// Stock increase (Reception, Purchase, Adjustment in): Debit Inventory Asset, Credit COGS / Stock Variation
			$tabdebit[$mid][$compta_stock] = $valuation_amount;
			$tabcredit[$mid][$compta_cogs] = $valuation_amount;
		}

		if ($compta_stock == 'NotDefined' || $compta_cogs == 'NotDefined') {
			$errorformovement[$mid] = 'accountnotdefined';
		}

		$i++;
	}
} else {
	dol_print_error($db);
}

// Bookkeeping Write
if ($action == 'writebookkeeping' && !$error && $user->hasRight('accounting', 'bind', 'write')) {
	$now = dol_now();
	$error = 0;

	foreach ($tabmov as $key => $val) {
		$errorforline = 0;

		$totalcredit = 0;
		$totaldebit = 0;

		// Skip movements with 0 valuation amount
		if (empty($val['amount'])) {
			continue;
		}

		$db->begin();

		if (!empty($errorformovement[$key]) && $errorformovement[$key] == 'accountnotdefined') {
			$error++;
			$errorforline++;
			setEventMessages($langs->trans('ErrorFieldAccountNotDefinedForLine', $val['doc_ref']), null, 'errors');
		}

		// Record Debit line(s)
		if (!$errorforline) {
			foreach ($tabdebit[$key] as $k => $mt) {
				if ($mt) {
					if (empty($conf->cache['accountingaccountincurrententity'][$k])) {
						$accountingaccount = new AccountingAccount($db);
						$accountingaccount->fetch(0, $k, true);
						$conf->cache['accountingaccountincurrententity'][$k] = $accountingaccount;
					} else {
						$accountingaccount = $conf->cache['accountingaccountincurrententity'][$k];
					}

					$account_label = !empty($accountingaccount->label) ? $accountingaccount->label : $k;

					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val['date'];
					$bookkeeping->doc_ref = $val['doc_ref'];
					$bookkeeping->date_creation = $now;
					$bookkeeping->doc_type = 'stock_mouvement';
					$bookkeeping->fk_doc = (int) $key;
					$bookkeeping->fk_docdet = (int) $val['fk_product'];

					$bookkeeping->subledger_account = '';
					$bookkeeping->subledger_label = '';

					$bookkeeping->numero_compte = $k;
					$bookkeeping->label_compte = $account_label;

					$opLabel = $val['product_ref'].' ('.($val['qty'] > 0 ? '+' : '').$val['qty'].' x '.price($val['unit_cost']).') - '.$val['label'];
					$bookkeeping->label_operation = dol_trunc($opLabel, 128);

					$bookkeeping->montant = $mt;
					$bookkeeping->sens = 'D';
					$bookkeeping->debit = $mt;
					$bookkeeping->credit = 0;
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $langs->transnoentities($journal_label);
					$bookkeeping->fk_user_author = $user->id;
					$bookkeeping->entity = $conf->entity;

					$totaldebit += $bookkeeping->debit;
					$totalcredit += $bookkeeping->credit;

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {
							$error++;
							$errorforline++;
							$errorformovement[$key] = 'alreadyjournalized';
						} else {
							$error++;
							$errorforline++;
							$errorformovement[$key] = 'other';
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				}
			}
		}

		// Record Credit line(s)
		if (!$errorforline) {
			foreach ($tabcredit[$key] as $k => $mt) {
				if ($mt) {
					if (empty($conf->cache['accountingaccountincurrententity'][$k])) {
						$accountingaccount = new AccountingAccount($db);
						$accountingaccount->fetch(0, $k, true);
						$conf->cache['accountingaccountincurrententity'][$k] = $accountingaccount;
					} else {
						$accountingaccount = $conf->cache['accountingaccountincurrententity'][$k];
					}

					$account_label = !empty($accountingaccount->label) ? $accountingaccount->label : $k;

					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val['date'];
					$bookkeeping->doc_ref = $val['doc_ref'];
					$bookkeeping->date_creation = $now;
					$bookkeeping->doc_type = 'stock_mouvement';
					$bookkeeping->fk_doc = (int) $key;
					$bookkeeping->fk_docdet = (int) $val['fk_product'];

					$bookkeeping->subledger_account = '';
					$bookkeeping->subledger_label = '';

					$bookkeeping->numero_compte = $k;
					$bookkeeping->label_compte = $account_label;

					$opLabel = $val['product_ref'].' ('.($val['qty'] > 0 ? '+' : '').$val['qty'].' x '.price($val['unit_cost']).') - '.$val['label'];
					$bookkeeping->label_operation = dol_trunc($opLabel, 128);

					$bookkeeping->montant = $mt;
					$bookkeeping->sens = 'C';
					$bookkeeping->debit = 0;
					$bookkeeping->credit = $mt;
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $langs->transnoentities($journal_label);
					$bookkeeping->fk_user_author = $user->id;
					$bookkeeping->entity = $conf->entity;

					$totaldebit += $bookkeeping->debit;
					$totalcredit += $bookkeeping->credit;

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {
							$error++;
							$errorforline++;
							$errorformovement[$key] = 'alreadyjournalized';
						} else {
							$error++;
							$errorforline++;
							$errorformovement[$key] = 'other';
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				}
			}
		}

		// Protection against unbalanced entries
		if (!$errorforline && (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT'))) {
			$error++;
			$errorforline++;
			$errorformovement[$key] = 'amountsnotbalanced';
			setEventMessages('We tried to insert a non balanced transaction in book for '.$val['doc_ref'].'. Canceled.', null, 'errors');
		}

		if (!$errorforline) {
			$db->commit();
		} else {
			$db->rollback();

			if ($error >= 10) {
				$langs->load('errors');
				setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
				break;
			}
		}
	}

	if (empty($error) && count($tabmov) > 0) {
		setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
	} elseif (count($tabmov) == $error) {
		setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
	} else {
		setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
	}

	$action = '';

	// Reload data after redirect
	if (count($tabmov) != $error) {
		$param = 'id_journal='.$id_journal;
		$param .= '&date_startday='.$date_startday;
		$param .= '&date_startmonth='.$date_startmonth;
		$param .= '&date_startyear='.$date_startyear;
		$param .= '&date_endday='.$date_endday;
		$param .= '&date_endmonth='.$date_endmonth;
		$param .= '&date_endyear='.$date_endyear;
		$param .= '&in_bookkeeping='.$in_bookkeeping;
		$param .= '&search_type_movement='.$search_type_movement;

		header("Location: ".$_SERVER['PHP_SELF'].($param ? '?'.$param : ''));
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);

// Export CSV
if ($action == 'exportcsv' && !$error) {
	$sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

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

	foreach ($tabmov as $key => $val) {
		if (empty($val['amount'])) {
			continue;
		}
		$date = dol_print_date($val['date'], 'day');
		$opLabel = $val['product_ref'].' ('.($val['qty'] > 0 ? '+' : '').$val['qty'].' x '.price($val['unit_cost']).') - '.$val['label'];

		// Debit
		foreach ($tabdebit[$key] as $k => $mt) {
			if ($mt) {
				print '"'.$date.'"'.$sep;
				print '"'.$val['doc_ref'].'"'.$sep;
				print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
				print '"'.csvClean($opLabel).'"'.$sep;
				print '"'.price($mt).'"'.$sep;
				print '""';
				print "\n";
			}
		}

		// Credit
		foreach ($tabcredit[$key] as $k => $mt) {
			if ($mt) {
				print '"'.$date.'"'.$sep;
				print '"'.$val['doc_ref'].'"'.$sep;
				print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
				print '"'.csvClean($opLabel).'"'.$sep;
				print '""'.$sep;
				print '"'.price($mt).'"';
				print "\n";
			}
		}
	}
}

if (empty($action) || $action == 'view') {
	$title = $langs->trans("GenerationOfAccountingEntries").' - '.$accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);
	$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#G&eacute;n&eacute;ration_des_&eacute;critures_en_comptabilit&eacute;';
	llxHeader('', dol_string_nohtmltag($title), $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-generation page-inventoryjournal');

	$nom = $title;
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate = dol_now();
	$description = $langs->trans("DescInventoryJournal").'<br>';

	$listofchoices = array(
		'notyet' => $langs->trans("NotYetInGeneralLedger"),
		'already' => $langs->trans("AlreadyInGeneralLedger")
	);
	$listofmovementtypes = array(
		'all' => $langs->trans("AllStockMovements"),
		'decrease' => $langs->trans("StockDecreasesOnly"),
		'increase' => $langs->trans("StockIncreasesOnly")
	);
	$period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
	$period .= ' -  '.$langs->trans("JournalizationInLedgerStatus").' '.$form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);
	$period .= ' -  '.$langs->trans("Movement").' '.$form->selectarray('search_type_movement', $listofmovementtypes, $search_type_movement, 0);

	$varlink = 'id_journal='.$id_journal;

	journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

	if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
		$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."accounting_fiscalyear WHERE entity = ".((int) $conf->entity);
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

	print '<br><div class="tabsAction tabsActionNoBottom centerimp">';

	if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
		print '<input type="button" class="butAction" name="exportcsv" value="'.$langs->trans("ExportDraftJournal").'" onclick="launch_export();" />';
	}
	if ($in_bookkeeping == 'notyet') {
		print '<input type="button" class="butAction" name="writebookkeeping" value="'.$langs->trans("WriteBookKeeping").'" onclick="writebookkeeping();" />';
	} else {
		print '<a href="#" class="butActionRefused classfortooltip" name="writebookkeeping">'.$langs->trans("WriteBookKeeping").'</a>';
	}
	print '</div>';

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
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td>'.$langs->trans("Piece").' ('.$langs->trans("StockMovement").')</td>';
	print '<td>'.$langs->trans("Product").' / '.$langs->trans("Warehouse").'</td>';
	print '<td>'.$langs->trans("AccountAccounting").'</td>';
	print '<td>'.$langs->trans("LabelOperation").'</td>';
	print '<td class="right">'.$langs->trans("AccountingDebit").'</td>';
	print '<td class="right">'.$langs->trans("AccountingCredit").'</td>';
	print "</tr>\n";

	$i = 0;

	$mouvementstockstatic = new MouvementStock($db);
	$productstatic = new Product($db);
	$warehousestatic = new Entrepot($db);

	foreach ($tabmov as $key => $val) {
		$date = dol_print_date($val['date'], 'day');

		$mouvementstockstatic->id = (int) $val['id'];
		$mouvementstockstatic->qty = (float) $val['qty'];
		$mouvementstockstatic->type = (int) $val['type_mouvement'];

		$productstatic->id = (int) $val['fk_product'];
		$productstatic->ref = $val['product_ref'];
		$productstatic->label = $val['product_label'];

		$warehousestatic->id = (int) $val['warehouse_id'];
		$warehousestatic->ref = $val['warehouse_ref'];
		$warehousestatic->label = $val['warehouse_ref'];

		$originLink = '';
		if (!empty($val['fk_origin']) && !empty($val['origintype'])) {
			$originCacheKey = $val['origintype'].'_'.((int) $val['fk_origin']);
			if (!isset($conf->cache['stock_mouvement_origin'][$originCacheKey])) {
				$conf->cache['stock_mouvement_origin'][$originCacheKey] = $mouvementstockstatic->get_origin($val['fk_origin'], $val['origintype']);
			}
			$originLink = $conf->cache['stock_mouvement_origin'][$originCacheKey];
		}

		$pieceHtml = $mouvementstockstatic->getNomUrl(1, 'movements').' '.$mouvementstockstatic->getTypeMovement(0);
		if (!empty($originLink)) {
			$pieceHtml .= ' - '.$originLink;
		}
		$productWarehouseHtml = $productstatic->getNomUrl(1).' <span class="opacitymedium">('.$warehousestatic->getNomUrl(1).')</span>';
		$opLabel = dolPrintHTML($val['product_ref']).' ('.($val['qty'] > 0 ? '+' : '').$val['qty'].' &times; '.price($val['unit_cost']).') - '.dolPrintHTML($val['label']);

		// Debit line
		foreach ($tabdebit[$key] as $k => $mt) {
			if (empty($conf->cache['accountingaccountincurrententity'][$k])) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch(0, $k, true);
				$conf->cache['accountingaccountincurrententity'][$k] = $accountingaccount;
			} else {
				$accountingaccount = $conf->cache['accountingaccountincurrententity'][$k];
			}

			print '<tr class="oddeven">';
			print '<td>'.$date.'</td>';
			print '<td>'.$pieceHtml.'</td>';
			print '<td>'.$productWarehouseHtml.'</td>';
			print '<td>';
			$accountoshow = length_accountg($k);
			if (empty($accountoshow) || $accountoshow == 'NotDefined') {
				print '<span class="error">'.($val['qty'] < 0 ? $langs->trans("CogsAccountNotDefined") : $langs->trans("StockAccountNotDefined")).'</span>';
			} else {
				print $accountoshow.(!empty($accountingaccount->label) ? ' - <span class="opacitymedium">'.dolPrintHTML($accountingaccount->label).'</span>' : '');
			}
			print '</td>';
			print '<td>'.$opLabel.'</td>';
			print '<td class="right nowraponall amount">'.price($mt).'</td>';
			print '<td class="right nowraponall amount"></td>';
			print '</tr>';

			$i++;
		}

		// Credit line
		foreach ($tabcredit[$key] as $k => $mt) {
			if (empty($conf->cache['accountingaccountincurrententity'][$k])) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch(0, $k, true);
				$conf->cache['accountingaccountincurrententity'][$k] = $accountingaccount;
			} else {
				$accountingaccount = $conf->cache['accountingaccountincurrententity'][$k];
			}

			print '<tr class="oddeven">';
			print '<td>'.$date.'</td>';
			print '<td>'.$pieceHtml.'</td>';
			print '<td>'.$productWarehouseHtml.'</td>';
			print '<td>';
			$accountoshow = length_accountg($k);
			if (empty($accountoshow) || $accountoshow == 'NotDefined') {
				print '<span class="error">'.($val['qty'] < 0 ? $langs->trans("StockAccountNotDefined") : $langs->trans("CogsAccountNotDefined")).'</span>';
			} else {
				print $accountoshow.(!empty($accountingaccount->label) ? ' - <span class="opacitymedium">'.dolPrintHTML($accountingaccount->label).'</span>' : '');
			}
			print '</td>';
			print '<td>'.$opLabel.'</td>';
			print '<td class="right nowraponall amount"></td>';
			print '<td class="right nowraponall amount">'.price($mt).'</td>';
			print '</tr>';

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

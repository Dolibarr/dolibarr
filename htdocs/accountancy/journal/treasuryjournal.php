<?php
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2025	Alexandre Spangaro		<alexandre@inovea-conseil.com>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2017-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018		Ferran Marcet		    <fmarcet@2byte.es>
 * Copyright (C) 2025		Hannes Hieronimi		<hannes@innwerk.org>
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
 *  \file       htdocs/accountancy/journal/treasuryjournal.php
 *  \ingroup    Advanced accountancy
 *  \brief      Page with bank journal
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "compta", "banks", "bills", "donations", "loan", "accountancy", "trips", "salaries", "hrm", "members"));

// Multi journal
$id_journal = GETPOSTINT('id_journal');

$date_startmonth = GETPOSTINT('date_startmonth');
$date_startday = GETPOSTINT('date_startday');
$date_startyear = GETPOSTINT('date_startyear');
$date_endmonth = GETPOSTINT('date_endmonth');
$date_endday = GETPOSTINT('date_endday');
$date_endyear = GETPOSTINT('date_endyear');
$in_bookkeeping = GETPOST('in_bookkeeping', 'aZ09');

$only_rappro = GETPOSTINT('only_rappro');
if ($only_rappro == 0) {
	//GET page for the first time, use default settings
	$only_rappro = getDolGlobalInt('ACCOUNTING_BANK_CONCILIATED');
}

$now = dol_now();

$action = GETPOST('action', 'aZ09');

if ($in_bookkeeping == '') {
	$in_bookkeeping = 'notyet';
}

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


/*
 * Actions
 */

$error = 0;

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

$pastmonth = null;  // Initialise for static analysis  (could be really unseg)
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

// Get all bank lines
//-------------------------------------
$sql  = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.amount_main_currency, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, b.fk_account,";
$sql .= " ba.courant, ba.ref as baref, ba.account_number, ba.fk_accountancy_journal,";
$sql .= " bu.type as bu_type";
$sql .= " FROM ".$db->prefix()."bank as b";
$sql .= " JOIN ".$db->prefix()."bank_account as ba on b.fk_account = ba.rowid";
$sql .= " LEFT JOIN ".$db->prefix()."bank_url as bu ON bu.fk_bank = b.rowid";
$sql .= " WHERE ba.fk_accountancy_journal = ".((int) $id_journal);
$sql .= " AND b.amount <> 0 AND ba.entity IN (".getEntity('bank_account').")"; // We don't share object for accountancy, we use source object sharing
if ($date_start && $date_end) {
	$sql .= " AND b.dateo >= '".$db->idate($date_start)."' AND b.dateo <= '".$db->idate($date_end)."'";
}
// Define begin binding date
if (getDolGlobalInt('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND b.dateo >= '".$db->idate(getDolGlobalInt('ACCOUNTING_DATE_START_BINDING'))."'";
}
// Already in bookkeeping or not
if ($in_bookkeeping == 'already') {
	$sql .= " AND (b.rowid IN (SELECT fk_doc FROM ".$db->prefix()."accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
}
if ($in_bookkeeping == 'notyet') {
	$sql .= " AND (b.rowid NOT IN (SELECT fk_doc FROM ".$db->prefix()."accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
}
if ($only_rappro == 2) {
	$sql .= " AND (b.rappro = '1')";
}
$sql .= " ORDER BY b.dateo";
//print $sql;

$result_lines = array();

// Data cached
$payment_ids = array();
$tabpay = array();
$tabaccount = array();
$tabobject = array();
$tabaccountingaccount = array();
$tabvatdata = array();

dol_syslog("accountancy/journal/treasuryjournal.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	$static_account = new Account($db);

	while ($obj = $db->fetch_object($resql)) {
		// Get payment infos (rowid is bank ID)
		if (!isset($tabpay[$obj->rowid])) {
			$tabpay[$obj->rowid] = array(
				'id' => $obj->rowid,
				'date' => $db->jdate($obj->do),
				'type_payment' => $obj->fk_type,// CHQ, VIR, LIQ, CB, ...
				'ref' => $obj->label, // by default, not unique. May be changed later
				'fk_bank_account' => $obj->fk_account,
				'objects' => array(),
			);
			$reg = array();
			if (preg_match('/^\((.*)\)$/i', $obj->label, $reg)) {
				$tabpay[$obj->rowid]["lib"] = $langs->trans($reg[1]);
			} else {
				$tabpay[$obj->rowid]["lib"] = dol_trunc($obj->label, 60);
			}
		}
		$payment_ids[$obj->bu_type][$obj->rowid] = $obj->rowid;

		// Get bank account infos (rowid is bank ID)
		if (!isset($tabaccount[$obj->fk_account])) {
			$static_account->id = $obj->fk_account;
			$static_account->ref = $obj->baref;
			$tabaccount[$obj->fk_account] = [
				'id' => $obj->fk_account,
				'account_ref' => $obj->baref,
				'account_number' => $obj->account_number,
				'url' => $static_account->getNomUrl(1),
			];
		}
	}
	$db->free($resql);

	foreach ($payment_ids as $type => $ids) {
		switch ($type) {
			case 'payment':
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

				// Customer invoices
				//------------------------------------------
				$sql = "SELECT f.rowid, f.ref AS ref, f.total_ht AS invoice_total_ht, f.total_ttc AS invoice_total_ttc,";
				$sql .= " pf.amount AS amount_payment,";
				$sql .= " fd.rowid AS row_id, fd.total_ht, fd.total_tva, fd.total_localtax1, fd.total_localtax2, fd.tva_tx, fd.total_ttc, fd.vat_src_code,";
				$sql .= " aa.account_number as accountancy_code, aa.label as accountancy_code_label,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."facturedet as fd";
				$sql .= " INNER JOIN ".$db->prefix()."facture as f ON f.rowid = fd.fk_facture";
				$sql .= " INNER JOIN ".$db->prefix()."paiement_facture as pf ON pf.fk_facture = f.rowid";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = pf.fk_paiement AND bu.type = '".$db->escape($type)."'";
				$sql .= " LEFT JOIN ".$db->prefix()."product as p ON p.rowid = fd.fk_product";
				$sql .= " LEFT JOIN ".$db->prefix()."accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=f.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=f.rowid";
				}
				$sql .= " WHERE f.entity IN (".getEntity('facture', 0).')'; // We don't share object for accountancy, we use source object sharing
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}
				$sql .= " AND fd.fk_code_ventilation > 0";
				$sql .= " AND f.fk_statut > 0";
				$sql .= " AND fd.product_type IN (0,1)";
				$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".(!getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS') ? Facture::TYPE_DEPOSIT."," : "").Facture::TYPE_SITUATION.")";
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";
				$sql .= " GROUP BY fd.rowid, bu.fk_bank, pf.amount, bu.url_id";	// TODO Must never have a GROUP BY on a field if field is not inside an aggregate function.
				$sql .= " ORDER BY aa.account_number";

				$resql = $db->query($sql);
				if ($resql) {
					$langs->load("bills");
					$static_invoice = new Facture($db);
					$already_sum = array();
					$account_vat_sold = getDolGlobalString('ACCOUNTING_VAT_SOLD_ACCOUNT', 'NotDefined'); // NotDefined is a reserved word

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// To check...
						// If 1 invoice has 2 payments at 2 different date, seems ok, we have 2 record $tabpay because $obj->fk_bank is different (obj->fk_bank is ID of payment in bank record table).
						// If 2 invoices are paid in the same payment, we have 2 $tabobject but also 2 $tabpay (because $object_key has 2 different values) when we should have 1.

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => $obj->amount_payment,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						if (isset($already_sum[$obj->row_id])) {
							continue;
						}
						$already_sum[$obj->row_id] = $obj->row_id;

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_invoice->id = $obj->rowid;
							$static_invoice->ref = $obj->ref;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $obj->ref,		// It would be better to have a doc_ref that is 'BankId '.$obj->fk_bank.' - Facture FAzzz' and not just 'FAzzz' to be protected against duplicate, where xxx = $obj->fk_bank
								'total_ht' => $obj->invoice_total_ht,
								'total_ttc' => $obj->invoice_total_ttc,
								'url' => $static_invoice->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Set accounting account infos
						if (!isset($tabaccountingaccount[$obj->accountancy_code])) {
							$tabaccountingaccount[$obj->accountancy_code] = array(
								'label' => !empty($obj->accountancy_code_label) ? $obj->accountancy_code_label : $langs->trans('NotDefined'),
							);
						}

						// Add amount for the accountancy code
						if (!isset($tabobject[$object_key]['operations'][$obj->accountancy_code])) {
							$tabobject[$object_key]['operations'][$obj->accountancy_code] = array(
								'total_ht' => 0,
							);
						}
						$tabobject[$object_key]['operations'][$obj->accountancy_code]['total_ht'] += $obj->total_ht;

						if ($obj->total_tva + $obj->total_localtax1 + $obj->total_localtax2 != 0) {
							// Get vat code compta
							if (!isset($tabvatdata[$obj->tva_tx][$obj->vat_src_code])) {
								$tabvatdata[$obj->tva_tx][$obj->vat_src_code] = getTaxesFromId($obj->tva_tx.($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''), $mysoc, $mysoc, 0);
							}
							$compta_tva = (!empty($tabvatdata[$obj->tva_tx][$obj->vat_src_code]['accountancy_code_sell']) ? $tabvatdata[$obj->tva_tx][$obj->vat_src_code]['accountancy_code_sell'] : $account_vat_sold);

							// Add amount VAT for the code compta
							if (!isset($tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx])) {
								$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx] = array(
									'tva_tx' => $obj->tva_tx,
									'total_tva' => 0,
									'total_localtax1' => 0,
									'total_localtax2' => 0,
								);
							}
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_tva'] += $obj->total_tva;
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_localtax1'] += $obj->total_localtax1;
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_localtax2'] += $obj->total_localtax2;
						}
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'payment_supplier':
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

				// Supplier invoices
				//------------------------------------------
				$sql = "SELECT ff.rowid, ff.ref, ff.total_ht AS supplier_invoice_total_ht, ff.total_ttc AS supplier_invoice_total_ttc,";
				$sql .= " pff.amount AS amount_payment,";
				$sql .= " ffd.rowid AS row_id, ffd.total_ht, ffd.tva AS total_tva, ffd.total_localtax1, ffd.total_localtax2, ffd.tva_tx, ffd.total_ttc, ffd.vat_src_code,";
				$sql .= " aa.account_number as accountancy_code, aa.label as accountancy_code_label,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."facture_fourn_det as ffd";
				$sql .= " INNER JOIN ".$db->prefix()."facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
				$sql .= " INNER JOIN ".$db->prefix()."paiementfourn_facturefourn as pff ON pff.fk_facturefourn = ff.rowid";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = pff.fk_paiementfourn AND bu.type = '".$db->escape($type)."'";
				$sql .= " LEFT JOIN ".$db->prefix()."product as p ON p.rowid = ffd.fk_product";
				$sql .= " LEFT JOIN ".$db->prefix()."accounting_account as aa ON aa.rowid = ffd.fk_code_ventilation";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=ff.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=ff.rowid";
				}
				$sql .= " WHERE ff.entity IN (".getEntity('facture_fourn', 0).')'; // We don't share object for accountancy, we use source object sharing
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}
				$sql .= " AND ffd.fk_code_ventilation > 0";
				$sql .= " AND ff.fk_statut > 0";
				$sql .= " AND ffd.product_type IN (0,1)";
				$sql .= " AND ff.type IN (".FactureFournisseur::TYPE_STANDARD.",".FactureFournisseur::TYPE_REPLACEMENT.",".FactureFournisseur::TYPE_CREDIT_NOTE.",".(!getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS') ? FactureFournisseur::TYPE_DEPOSIT."," : "").FactureFournisseur::TYPE_SITUATION.")";
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";
				$sql .= " GROUP BY ffd.rowid, bu.fk_bank";
				$sql .= " ORDER BY aa.account_number";

				$resql = $db->query($sql);
				if ($resql) {
					$langs->load("suppliers");
					$static_supplier_invoice = new FactureFournisseur($db);
					$already_sum = array();
					$account_vat_buy = getDolGlobalString('ACCOUNTING_VAT_BUY_ACCOUNT', 'NotDefined'); // NotDefined is a reserved word

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => -$obj->amount_payment,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						if (isset($already_sum[$obj->row_id])) {
							continue;
						}
						$already_sum[$obj->row_id] = $obj->row_id;

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_supplier_invoice->id = $obj->rowid;
							$static_supplier_invoice->ref = $obj->ref;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $obj->ref,		// It would be better to have a doc_ref that is 'BankId '.$obj->fk_bank.' - Facture FAzzz' and not just 'FAzzz' to be protected against duplicate, where xxx = $obj->fk_bank
								'total_ht' => -$obj->supplier_invoice_total_ht,
								'total_ttc' => -$obj->supplier_invoice_total_ttc,
								'url' => $static_supplier_invoice->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Set accounting account infos
						if (!isset($tabaccountingaccount[$obj->accountancy_code])) {
							$tabaccountingaccount[$obj->accountancy_code] = array(
								'label' => !empty($obj->accountancy_code_label) ? $obj->accountancy_code_label : $langs->trans('NotDefined'),
							);
						}

						// Add amount for the accountancy code
						if (!isset($tabobject[$object_key]['operations'][$obj->accountancy_code])) {
							$tabobject[$object_key]['operations'][$obj->accountancy_code] = array(
								'total_ht' => 0,
							);
						}
						$tabobject[$object_key]['operations'][$obj->accountancy_code]['total_ht'] -= $obj->total_ht;

						if ($obj->total_tva + $obj->total_localtax1 + $obj->total_localtax2 != 0) {
							// Get vat code compta
							if (!isset($tabvatdata[$obj->tva_tx][$obj->vat_src_code])) {
								$tabvatdata[$obj->tva_tx][$obj->vat_src_code] = getTaxesFromId($obj->tva_tx.($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''), $mysoc, $mysoc, 0);
							}
							$compta_tva = (!empty($tabvatdata[$obj->tva_tx][$obj->vat_src_code]['accountancy_code_buy']) ? $tabvatdata[$obj->tva_tx][$obj->vat_src_code]['accountancy_code_buy'] : $account_vat_buy);

							// Add amount VAT for the code compta
							if (!isset($tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx])) {
								$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx] = array(
									'tva_tx' => $obj->tva_tx,
									'total_tva' => 0,
									'total_localtax1' => 0,
									'total_localtax2' => 0,
								);
							}
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_tva'] -= $obj->total_tva;
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_localtax1'] -= $obj->total_localtax1;
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_localtax2'] -= $obj->total_localtax2;
						}
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'payment_expensereport':
				require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

				// Expense reports
				//------------------------------------------
				$sql = "SELECT er.rowid, er.ref, er.total_ht AS expense_report_total_ht, er.total_ttc AS expense_report_total_ttc,";
				$sql .= " per.amount AS amount_payment,";
				$sql .= " erf.rowid AS row_id, erf.total_ht, erf.total_tva, erf.total_localtax1, erf.total_localtax2, erf.tva_tx, erf.total_ttc, erf.vat_src_code,";
				$sql .= " ctf.accountancy_code,";
				$sql .= " aa.label as accountancy_code_label,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."expensereport_det as erf";
				$sql .= " INNER JOIN ".$db->prefix()."expensereport as er ON er.rowid = erf.fk_expensereport";
				$sql .= " INNER JOIN ".$db->prefix()."payment_expensereport as per ON per.fk_expensereport = er.rowid";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = per.rowid AND bu.type = '".$db->escape($type)."'";
				$sql .= " LEFT JOIN ".$db->prefix()."c_type_fees as ctf ON ctf.id = erf.fk_c_type_fees";
				$sql .= " LEFT JOIN ".$db->prefix()."accounting_account as aa ON aa.account_number = ctf.accountancy_code";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=er.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=er.rowid";
				}
				$sql .= " WHERE er.entity IN (".getEntity('expensereport', 0).')'; // We don't share object for accountancy, we use source object sharing
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}
				$sql .= " AND er.fk_statut >= ".ExpenseReport::STATUS_APPROVED;
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";
				$sql .= " GROUP BY erf.rowid, bu.fk_bank, per.amount, aa.label, bu.url_id";
				$sql .= " ORDER BY aa.account_number";

				$resql = $db->query($sql);
				if ($resql) {
					$langs->load("trips");
					$static_expense_report = new ExpenseReport($db);
					$already_sum = array();
					$account_vat_buy = getDolGlobalString('ACCOUNTING_VAT_BUY_ACCOUNT', 'NotDefined'); // NotDefined is a reserved word

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => -$obj->amount_payment,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						if (isset($already_sum[$obj->row_id])) {
							continue;
						}
						$already_sum[$obj->row_id] = $obj->row_id;

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_expense_report->id = $obj->rowid;
							$static_expense_report->ref = $obj->ref;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $obj->ref,
								'total_ht' => -$obj->expense_report_total_ht,
								'total_ttc' => -$obj->expense_report_total_ttc,
								'url' => $static_expense_report->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Set accounting account infos
						$accountancy_code = !empty($obj->accountancy_code) ? $obj->accountancy_code : 'NotDefined';
						if (!isset($tabaccountingaccount[$accountancy_code])) {
							$tabaccountingaccount[$accountancy_code] = array(
								'label' => !empty($obj->accountancy_code_label) ? $obj->accountancy_code_label : $langs->trans('NotDefined'),
							);
						}

						// Add amount for the accountancy code
						if (!isset($tabobject[$object_key]['operations'][$accountancy_code])) {
							$tabobject[$object_key]['operations'][$accountancy_code] = array(
								'total_ht' => 0,
							);
						}
						$tabobject[$object_key]['operations'][$accountancy_code]['total_ht'] -= $obj->total_ht;

						if ($obj->total_tva + $obj->total_localtax1 + $obj->total_localtax2 != 0) {
							// Get vat code compta
							if (!isset($tabvatdata[$obj->tva_tx][$obj->vat_src_code])) {
								$tabvatdata[$obj->tva_tx][$obj->vat_src_code] = getTaxesFromId($obj->tva_tx.($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''), $mysoc, $mysoc, 0);
							}
							$compta_tva = (!empty($tabvatdata[$obj->tva_tx][$obj->vat_src_code]['accountancy_code_buy']) ? $tabvatdata[$obj->tva_tx][$obj->vat_src_code]['accountancy_code_buy'] : $account_vat_buy);

							// Add amount VAT for the code compta
							if (!isset($tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx])) {
								$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx] = array(
									'tva_tx' => $obj->tva_tx,
									'total_tva' => 0,
									'total_localtax1' => 0,
									'total_localtax2' => 0,
								);
							}
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_tva'] -= $obj->total_tva;
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_localtax1'] -= $obj->total_localtax1;
							$tabobject[$object_key]['vats'][$compta_tva][$obj->tva_tx]['total_localtax2'] -= $obj->total_localtax2;
						}
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'payment_salary':
				// Payment salaries
				//------------------------------------------
				$sql = "SELECT ps.rowid,";
				$sql .= " ps.amount AS amount_payment, ps.label AS label,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."payment_salary as ps";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = ps.rowid AND bu.type = '".$db->escape($type)."'";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=ps.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=ps.rowid";
				}
				$sql .= " WHERE ps.entity IN (".getEntity('user', 0).')'; // We don't share object for accountancy, we use source object sharing
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";

				$resql = $db->query($sql);
				if ($resql) {
					require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
					$langs->load("salaries");
					$static_payment_salary = new PaymentSalary($db);
					$account_employee = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT', 'NotDefined'); // NotDefined is a reserved word
					$prefix_ref = getDolGlobalString('MAIN_PAYMENT_SALARY_REF_PREFIX', 'PS');

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => -$obj->amount_payment,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_payment_salary->id = $obj->rowid;
							$static_payment_salary->ref = $prefix_ref.$obj->rowid;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $prefix_ref.$obj->rowid,
								'total_ht' => -$obj->amount_payment,
								'total_ttc' => -$obj->amount_payment,
								'url' => $static_payment_salary->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Add amount for the accountancy code
						$tabobject[$object_key]['operations'][$account_employee] = array(
							'total_ht' => -$obj->amount_payment,
							'label' => $obj->label,
						);
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'payment_sc':
				// Socials contributions
				//------------------------------------------
				$sql = "SELECT cs.rowid, cs.ref, cs.libelle AS label, cs.amount AS sociales_contributions_amount,";
				$sql .= " pc.amount AS amount_payment,";
				$sql .= " ccs.accountancy_code,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."paiementcharge as pc";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = pc.rowid AND bu.type = '".$db->escape($type)."'";
				$sql .= " INNER JOIN ".$db->prefix()."chargesociales AS cs ON cs.rowid = pc.fk_charge";
				$sql .= " LEFT JOIN ".$db->prefix()."c_chargesociales as ccs ON ccs.id = cs.fk_type";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=cs.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=cs.rowid";
				}
				$sql .= " WHERE cs.entity = ".$conf->entity; // We don't share object for accountancy, we use source object sharing
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";

				$resql = $db->query($sql);
				if ($resql) {
					require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';

					$langs->load("bills");
					$static_sociales_contributions = new ChargeSociales($db);
					$prefix_ref = getDolGlobalString('MAIN_PAYMENT_SOCIALES_CONTRIBUTIONS_REF_PREFIX', 'SC');

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => -$obj->amount_payment,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_sociales_contributions->id = $obj->rowid;
							$static_sociales_contributions->ref = $prefix_ref.$obj->rowid;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $prefix_ref.$obj->rowid,
								'total_ht' => -$obj->sociales_contributions_amount,
								'total_ttc' => -$obj->sociales_contributions_amount,
								'url' => $static_sociales_contributions->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						$accountancy_code = !empty($obj->accountancy_code) ? $obj->accountancy_code : 'NotDefined';

						// Add amount for the accountancy code
						$tabobject[$object_key]['operations'][$accountancy_code] = array(
							'total_ht' => -$obj->sociales_contributions_amount,
							'label' => $obj->label,
						);
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'payment_vat':
				// Payment VAT
				//------------------------------------------
				$sql = "SELECT t.rowid,";
				$sql .= " t.amount AS amount_payment, t.label AS label,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."tva as t";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = t.rowid AND bu.type = '".$db->escape($type)."'";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=t.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=t.rowid";
				}
				$sql .= " WHERE bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";
				// $sql .= " AND t.entity = " . $conf->entity; // TODO when entity is managed in tva
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}

				$resql = $db->query($sql);
				if ($resql) {
					require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';

					$langs->load("salaries");
					$static_tva = new Tva($db);
					$account_pay_vat = getDolGlobalString('ACCOUNTING_VAT_PAY_ACCOUNT', 'NotDefined'); // NotDefined is a reserved word
					$prefix_ref = getDolGlobalString('MAIN_PAYMENT_VAT_REF_PREFIX', 'VAT');

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => -$obj->amount_payment,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_tva->id = $obj->rowid;
							$static_tva->ref = $prefix_ref.$obj->rowid;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $prefix_ref.$obj->rowid,
								'total_ht' => -$obj->amount_payment,
								'total_ttc' => -$obj->amount_payment,
								'url' => $static_tva->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Add amount for the accountancy code
						$tabobject[$object_key]['operations'][$account_pay_vat] = array(
							'total_ht' => -$obj->amount_payment,
							'label' => $obj->label,
						);
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'payment_donation':
				// Payment donation
				//------------------------------------------
				$sql = "SELECT d.rowid, d.amount AS don_amount,";
				$sql .= " pd.amount AS amount_payment,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."payment_donation as pd";
				$sql .= " INNER JOIN ".$db->prefix()."don as d ON pd.fk_donation = d.rowid";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = pd.rowid AND bu.type = '".$db->escape($type)."'";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=d.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=d.rowid";
				}
				$sql .= " WHERE d.entity IN (".getEntity('donation', 0).')'; // We don't share object for accountancy, we use source object sharing
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";

				$resql = $db->query($sql);
				if ($resql) {
					require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

					$langs->load("donations");
					$static_don = new Don($db);
					$account_pay_donation = getDolGlobalString('DONATION_ACCOUNTINGACCOUNT', 'NotDefined'); // NotDefined is a reserved word
					$prefix_ref = getDolGlobalString('MAIN_PAYMENT_DONATION_REF_PREFIX', 'D');

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => $obj->amount_payment,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_don->id = $obj->rowid;
							$static_don->ref = $prefix_ref.$obj->rowid;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $prefix_ref.$obj->rowid,
								'total_ht' => $obj->don_amount,
								'total_ttc' => $obj->don_amount,
								'url' => $static_don->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Add amount for the accountancy code
						$tabobject[$object_key]['operations'][$account_pay_donation] = array(
							'total_ht' => $obj->don_amount,
							'label' => $langs->trans('Donation').' '.$prefix_ref.$obj->rowid,
						);
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'payment_loan':
				// Payment loan
				//------------------------------------------
				$sql = "SELECT l.rowid, l.capital AS loan_capital, l.accountancy_account_capital, l.accountancy_account_interest, l.accountancy_account_insurance, l.label,";
				$sql .= " pl.amount_capital, pl.amount_interest, pl.amount_insurance,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."payment_loan as pl";
				$sql .= " INNER JOIN ".$db->prefix()."loan as l ON pl.fk_loan = l.rowid";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = pl.rowid AND bu.type = '".$db->escape($type)."'";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=l.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=l.rowid";
				}
				$sql .= " WHERE l.entity = ".$conf->entity; // We don't share object for accountancy, we use source object sharing
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";

				$resql = $db->query($sql);
				if ($resql) {
					require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';

					$langs->load("loan");
					$static_loan = new Loan($db);
					$account_pay_loan_capital = getDolGlobalString('LOAN_ACCOUNTING_ACCOUNT_CAPITAL', 'NotDefined'); // NotDefined is a reserved word
					$account_pay_loan_interest = getDolGlobalString('LOAN_ACCOUNTING_ACCOUNT_INTEREST', 'NotDefined'); // NotDefined is a reserved word
					$account_pay_loan_insurance = getDolGlobalString('LOAN_ACCOUNTING_ACCOUNT_INSURANCE', 'NotDefined'); // NotDefined is a reserved word
					$prefix_ref = getDolGlobalString('MAIN_PAYMENT_LOAN_REF_PREFIX', 'L');

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						$payment_amount = $obj->amount_capital + $obj->amount_interest + $obj->amount_insurance;
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => -$payment_amount,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_loan->id = $obj->rowid;
							$static_loan->ref = $prefix_ref.$obj->rowid;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $prefix_ref.$obj->rowid,
								'total_ht' => -$obj->loan_capital,
								'total_ttc' => -$obj->loan_capital,
								'url' => $static_loan->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Add amount for the accountancy code
						$accountancy_account_capital = !empty($obj->accountancy_account_capital) ? $obj->accountancy_account_capital : $account_pay_loan_capital;
						$tabobject[$object_key]['operations'][$accountancy_account_capital] = array(
							// virtual total = loan_capital * amount_capital / payment_amount
							'total_ht' => -($obj->loan_capital * $obj->amount_capital / $payment_amount),
							'label' => $obj->label.' '.$langs->trans('LoanCapital'),
						);

						// Add amount for the accountancy code
						$accountancy_account_interest = !empty($obj->accountancy_account_interest) ? $obj->accountancy_account_interest : $account_pay_loan_interest;
						$tabobject[$object_key]['operations'][$accountancy_account_interest] = array(
							// virtual total = loan_capital * amount_interest / payment_amount
							'total_ht' => -($obj->loan_capital * $obj->amount_interest / $payment_amount),
							'label' => $obj->label.' '.$langs->trans('Interest'),
						);

						// 526,23 = 569,74 * x / 15 000,00

						// Add amount for the accountancy code
						$accountancy_account_insurance = !empty($obj->accountancy_account_insurance) ? $obj->accountancy_account_insurance : $account_pay_loan_insurance;
						$tabobject[$object_key]['operations'][$accountancy_account_insurance] = array(
							// virtual total = loan_capital * amount_insurance / payment_amount
							'total_ht' => -($obj->loan_capital * $obj->amount_insurance / $payment_amount),
							'label' => $obj->label.' '.$langs->trans('Insurance'),
						);
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'payment_various':
				// Payment various
				//------------------------------------------
				$sql = "SELECT pv.rowid,";
				$sql .= " pv.sens AS sens_payment, pv.amount AS amount_payment, pv.label, pv.accountancy_code,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."payment_various as pv";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.url_id = pv.rowid AND bu.type = '".$db->escape($type)."'";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=pv.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=pv.rowid";
				}
				$sql .= " WHERE pv.entity IN (".getEntity('payment_various', 0).')';    // We don't share object for accountancy, we use source object sharing
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}

				$resql = $db->query($sql);
				if ($resql) {
					require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';

					$static_payment_various = new PaymentVarious($db);
					$prefix_ref = getDolGlobalString('MAIN_PAYMENT_VARIOUS_REF_PREFIX', 'PM');

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						$payment_amount = (empty($obj->sens_payment) ? -1 : 1) * $obj->amount_payment;
						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => $payment_amount,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_payment_various->id = $obj->rowid;
							$static_payment_various->ref = $prefix_ref.$obj->rowid;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $prefix_ref.$obj->rowid,
								'total_ht' => $payment_amount,
								'total_ttc' => $payment_amount,
								'url' => $static_payment_various->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Add amount for the accountancy code
						$accountancy_code = !empty($obj->accountancy_code) ? $obj->accountancy_code : 'NotDefined';
						$tabobject[$object_key]['operations'][$obj->accountancy_code] = array(
							'total_ht' => $payment_amount,
							'label' => $obj->label,
						);
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'member':
				// Subscription member
				//------------------------------------------
				$sql = "SELECT su.rowid,";
				$sql .= " su.subscription AS amount_payment, su.note AS label,";
				$sql .= " adh.lastname, adh.firstname,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."subscription as su";
				$sql .= " INNER JOIN ".$db->prefix()."adherent as adh ON adh.rowid = su.fk_adherent";
				$sql .= " INNER JOIN ".$db->prefix()."bank_url as bu ON bu.fk_bank = su.fk_bank AND bu.type = '".$db->escape($type)."'";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=su.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=su.rowid";
				}
				$sql .= " WHERE bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}

				$resql = $db->query($sql);
				if ($resql) {
					require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

					$langs->load("members");
					$static_subscription = new Subscription($db);
					$account_subscription = getDolGlobalString('ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT', 'NotDefined'); // NotDefined is a reserved word
					$prefix_ref = getDolGlobalString('MAIN_PAYMENT_SUBSCRIPTION_REF_PREFIX', 'SU');

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => $obj->amount_payment,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_subscription->id = $obj->rowid;
							$static_subscription->ref = $prefix_ref.$obj->rowid;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $prefix_ref.$obj->rowid,
								'total_ht' => -$obj->amount_payment,
								'total_ttc' => -$obj->amount_payment,
								'url' => $static_subscription->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Add amount for the accountancy code
						$tabobject[$object_key]['operations'][$account_subscription] = array(
							'total_ht' => -$obj->amount_payment,
							'label' => $obj->label.' - '.$obj->lastname.' '.$obj->firstname,
						);
					}
				} else {
					dol_print_error($db);
				}
				break;
			case 'banktransfert':
				// Bank transfer
				//------------------------------------------
				$sql = "SELECT b.rowid, b.amount, b.label,";
				$sql .= " bu.fk_bank, bu.url_id AS bu_url_id, bu.type AS bu_type";
				$sql .= " FROM ".$db->prefix()."bank_url as bu";
				$sql .= " INNER JOIN ".$db->prefix()."bank as b ON bu.url_id = b.rowid";
				$sql .= " LEFT JOIN ".$db->prefix()."bank_account as ba ON ba.rowid = b.fk_account";
				// Already in bookkeeping or not
				if ($in_bookkeeping == 'already') {
					$sql .= " INNER JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=b.rowid";
				} else {
					$sql .= " LEFT JOIN ".$db->prefix()."accounting_bookkeeping as ab ON ab.fk_doc=bu.fk_bank AND ab.fk_docdet=b.rowid";
				}
				$sql .= " WHERE ba.entity IN (".getEntity('bank_account', 0).')'; // We don't share object for accountancy, we use source object sharing
				$sql .= " AND bu.fk_bank IN (".$db->sanitize(implode(',', $ids)).")";
				$sql .= " AND bu.type = '".$db->escape($type)."'";
				// Not already in bookkeeping
				if ($in_bookkeeping == 'notyet') {
					$sql .= " AND ab.rowid IS NULL";
				}

				$resql = $db->query($sql);
				if ($resql) {
					require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

					$static_account_line = new AccountLine($db);
					$account_transfer = getDolGlobalString('ACCOUNTING_ACCOUNT_TRANSFER_CASH', 'NotDefined'); // NotDefined is a reserved word
					$prefix_ref = getDolGlobalString('MAIN_PAYMENT_TRANSFER_CASH_REF_PREFIX', 'T');

					while ($obj = $db->fetch_object($resql)) {
						$object_key = $obj->bu_type.'_'.$obj->rowid;

						// Add object in payment
						if (!isset($tabpay[$obj->fk_bank]['objects'][$object_key])) {
							$tabpay[$obj->fk_bank]['objects'][$object_key] = array(
								'amount' => -$obj->amount,
								'bu_url_id' => $obj->bu_url_id,
							);
						}

						// Set object infos
						if (!isset($tabobject[$object_key])) {
							$static_account_line->id = $obj->rowid;
							$static_account_line->rowid = $obj->rowid;
							$static_account_line->ref = $prefix_ref.$obj->rowid;
							$tabobject[$object_key] = array(
								'id' => $obj->rowid,
								'ref' => $prefix_ref.$obj->rowid,
								'total_ht' => -$obj->amount,
								'total_ttc' => -$obj->amount,
								'url' => $static_account_line->getNomUrl(1),
								'operations' => array(),
								'vats' => array(),
							);
						}

						// Add amount for the accountancy code
						$tabobject[$object_key]['operations'][$account_transfer] = array(
							'total_ht' => -$obj->amount,
							'label' => $obj->label,
						);
					}
				} else {
					dol_print_error($db);
				}
				break;
		}
	}
} else {
	dol_print_error($db);
}

/**
 * Filter for payment
 *
 * @param	array<string, mixed>	$v		Table of payment
 * @return	bool
 */
function payment_filter($v)
{
	return isset($v['objects']) && count($v['objects']) > 0;
}

$tabpay = array_filter($tabpay, 'payment_filter');

$accountingaccount = new AccountingAccount($db);

// Get code of finance journal
$accountingjournalstatic = new AccountingJournal($db);
$accountingjournalstatic->fetch($id_journal);
$journal = $accountingjournalstatic->code;
$journal_label = $langs->transnoentitiesnoconv($accountingjournalstatic->label);
$MAXNBERRORS = 5;

// Write bookkeeping
if ($action == 'writebookkeeping' /* && $user->hasRight('accounting', 'bind', 'write') */) { // Test on permission already done
	foreach ($tabpay as $payment_id => $payment) {
		$accountInfos = $tabaccount[$payment["fk_bank_account"]];

		// Set accounting account infos
		if (!isset($tabaccountingaccount[$accountInfos['account_number']])) {
			$result = $accountingaccount->fetch(0, $accountInfos['account_number'], true);
			if ($result < 0) {
				setEventMessages($accountingaccount->error, $accountingaccount->errors, 'errors');
				$error++;
				break;
			}
			$tabaccountingaccount[$accountInfos['account_number']] = array(
				'label' => $result > 0 ? $accountingaccount->label : $langs->trans('NotDefined'),
			);
		}


		$errorforline = 0;
		$db->begin();

		foreach ($payment['objects'] as $object_key => $object_data) {
			$objectInfos = $tabobject[$object_key];

			$total_check = 0;

			// Show bank line
			if ($object_data['amount'] >= 0) {
				$amount = (float) price2num($object_data['amount'], 'MT');
				$total_check += $amount;

				$bookkeepingToCreate = new BookKeeping($db);

				// Unique key is on couple: $payment_id, $objectInfos['id']
				// For record in llx_accountaing_bookkeeping, for record with doc_type = 'bank', the value of fk_doc is ID in llx_bank and fk_docdet too. Wetry a fix this way;
				//$result = $bookkeepingToCreate->createFromValues($payment["date"], $objectInfos['ref'], 'bank', $payment_id, $objectInfos['id'], $accountInfos['account_number'], $tabaccountingaccount[$accountInfos['account_number']]['label'], $accountInfos['account_ref'], $amount, $journal, $journal_label, '');
				$result = $bookkeepingToCreate->createFromValues($payment["date"], $objectInfos['ref'], 'bank', $payment_id, 0, $accountInfos['account_number'], $tabaccountingaccount[$accountInfos['account_number']]['label'], $accountInfos['account_ref'], $amount, $journal, $journal_label, '');

				if ($result < 0) {
					$errorforline++;

					if (!empty($bookkeepingToCreate->warnings)) {
						setEventMessages(null, $bookkeepingToCreate->warnings, 'warnings');
					}
					if (!empty($bookkeepingToCreate->errors)) {
						setEventMessages(null, $bookkeepingToCreate->errors, 'errors');
					}
				}
			}

			// Operations
			$payment_total_vat = (float) price2num($object_data['amount'] * ($objectInfos['total_ttc'] - $objectInfos['total_ht']) / $objectInfos['total_ttc'], 'MT');
			$payment_total_ht = $object_data['amount'] - $payment_total_vat;
			$total_operation = 0;
			$idx = 1;
			$nb_operation = count($objectInfos['operations']);
			foreach ($objectInfos['operations'] as $accountancy_code => $operation) {
				if (!empty($operation['total_ht'])) {
					// Set accounting account infos
					if (!isset($tabaccountingaccount[$accountancy_code])) {
						$result = $accountingaccount->fetch(0, $accountancy_code, true);
						if ($result < 0) {
							setEventMessages($accountingaccount->error, $accountingaccount->errors, 'errors');
							$accountancy_code_label = $accountingaccount->errorsToString();
							$errorforline++;
						} elseif ($result > 0) {
							$accountancy_code_label = $accountingaccount->label;
						} else {
							$accountancy_code_label = $langs->trans('NotDefined');
						}
						$tabaccountingaccount[$accountancy_code] = array('label' => $accountancy_code_label);
					}
					$accountingAccountInfos = $tabaccountingaccount[$accountancy_code];
					if ($idx < $nb_operation) {
						$amount = price2num($payment_total_ht * $operation['total_ht'] / $objectInfos['total_ht'], 'MT');
						$total_operation += $amount;
					} else {
						$amount = $payment_total_ht - $total_operation;
					}
					$total_check -= $amount;

					$bookkeepingToCreate = new BookKeeping($db);
					//$result = $bookkeepingToCreate->createFromValues($payment["date"], $objectInfos['ref'], 'bank', $payment_id, $objectInfos['id'], $accountancy_code, $accountingAccountInfos['label'], (!empty($operation['label']) ? $operation['label'] : $accountingAccountInfos['label']), -$amount, $journal, $journal_label, '');
					$result = $bookkeepingToCreate->createFromValues($payment["date"], $objectInfos['ref'], 'bank', $payment_id, 0, $accountancy_code, $accountingAccountInfos['label'], (!empty($operation['label']) ? $operation['label'] : $accountingAccountInfos['label']), -$amount, $journal, $journal_label, '');
					if ($result < 0) {
						$errorforline++;

						if (!empty($bookkeepingToCreate->warnings)) {
							setEventMessages(null, $bookkeepingToCreate->warnings, 'warnings');
						}
						if (!empty($bookkeepingToCreate->errors)) {
							setEventMessages(null, $bookkeepingToCreate->errors, 'errors');
						}
					}
				}
				$idx++;
			}

			// VATs
			$total_vat = 0;
			$idx = 1;
			$nb_vat = 0;
			foreach ($objectInfos['vats'] as $accountancy_code => $vats) {
				foreach ($vats as $vat_tx => $vat_infos) {
					$nb_vat++;
				}
			}
			foreach ($objectInfos['vats'] as $accountancy_code => $vats) {
				foreach ($vats as $vat_tx => $vat_infos) {
					$amount = $vat_infos['total_tva'] + $vat_infos['total_localtax1'] + $vat_infos['total_localtax2'];
					if (!empty($amount)) {
						// Set accounting account infos
						if (!isset($tabaccountingaccount[$accountancy_code])) {
							$result = $accountingaccount->fetch(0, $accountancy_code, true);
							if ($result < 0) {
								setEventMessages($accountingaccount->error, $accountingaccount->errors, 'errors');
								$accountancy_code_label = $accountingaccount->errorsToString();
								$errorforline++;
							} elseif ($result > 0) {
								$accountancy_code_label = $accountingaccount->label;
							} else {
								$accountancy_code_label = $langs->trans('NotDefined');
							}
							$tabaccountingaccount[$accountancy_code] = array('label' => $accountancy_code_label);
						}
						$accountingAccountInfos = $tabaccountingaccount[$accountancy_code];
						$amount = (float) price2num($payment_total_vat * $amount / ($objectInfos['total_ttc'] - $objectInfos['total_ht']), 'MT');
						$total_vat += $amount;
						$total_check -= $amount;

						$bookkeepingToCreate = new BookKeeping($db);
						//$result = $bookkeepingToCreate->createFromValues($payment["date"], $objectInfos['ref'], 'bank', $payment_id, $objectInfos['id'], $accountancy_code, $accountingAccountInfos['label'], $langs->trans('VAT').' '.price($vat_infos['tva_tx']).'%', -$amount, $journal, $journal_label, '');
						$result = $bookkeepingToCreate->createFromValues($payment["date"], $objectInfos['ref'], 'bank', $payment_id, 0, $accountancy_code, $accountingAccountInfos['label'], $langs->trans('VAT').' '.price($vat_infos['tva_tx']).'%', -$amount, $journal, $journal_label, '');
						if ($result < 0) {
							$errorforline++;

							if (!empty($bookkeepingToCreate->warnings)) {
								setEventMessages(null, $bookkeepingToCreate->warnings, 'warnings');
							}
							if (!empty($bookkeepingToCreate->errors)) {
								setEventMessages(null, $bookkeepingToCreate->errors, 'errors');
							}
						}
					}
					$idx++;
				}
			}

			// Show bank line
			if ($object_data['amount'] < 0) {
				$amount = (float) price2num($object_data['amount'], 'MT');
				$total_check += $amount;

				$bookkeepingToCreate = new BookKeeping($db);
				//$result = $bookkeepingToCreate->createFromValues($payment["date"], $objectInfos['ref'], 'bank', $payment_id, $objectInfos['id'], $accountInfos['account_number'], $tabaccountingaccount[$accountInfos['account_number']]['label'], $accountInfos['account_ref'], $amount, $journal, $journal_label, '');
				$result = $bookkeepingToCreate->createFromValues($payment["date"], $objectInfos['ref'], 'bank', $payment_id, 0, $accountInfos['account_number'], $tabaccountingaccount[$accountInfos['account_number']]['label'], $accountInfos['account_ref'], $amount, $journal, $journal_label, '');
				if ($result < 0) {
					$errorforline++;

					if (!empty($bookkeepingToCreate->warnings)) {
						setEventMessages(null, $bookkeepingToCreate->warnings, 'warnings');
					}
					if (!empty($bookkeepingToCreate->errors)) {
						setEventMessages(null, $bookkeepingToCreate->errors, 'errors');
					}
				}
			}

			$total_check = price2num($total_check, 'MT');
			if (!empty($total_check)) {
				$errorforline++;
				setEventMessages($langs->trans('ErrorBookkeepingTryInsertNotBalancedTransactionAndCanceled', $objectInfos['ref'], $object_data['bu_url_id']), null, 'errors');
			}

			if ($errorforline) {
				$error++;

				if ($error >= $MAXNBERRORS) {
					break;  // Break in the foreach
				}
			}
		}

		if (!$errorforline) {
			$db->commit();
		} else {
			//print 'KO for line '.$key.' '.$error.'<br>';
			$db->rollback();

			$MAXNBERRORS = 5;
			if ($error >= $MAXNBERRORS) {
				setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped").' (>'.$MAXNBERRORS.')', null, 'errors');
				break;  // Break in the foreach
			}
		}
	}

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
		header("Location: " . $_SERVER['PHP_SELF'] . '?' . $param);
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);
$description = null;

if (empty($action) || $action == 'view') {
	llxHeader('', $langs->trans("FinanceJournal"));

	$nom = $langs->trans("FinanceJournal").' | '.$accountingjournalstatic->getNomUrl(0, 1, 1, '', 1);
	$nomlink = '';
	$builddate = dol_now();
	$description = $langs->trans("DescJournalOnlyBindedVisible").'<br>';

	$listofchoices = array(
		'notyet' => $langs->trans("NotYetInGeneralLedger"),
		'already' => $langs->trans("AlreadyInGeneralLedger")
	);
	$period = $form->selectDate($date_start ?: -1, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end ?: -1, 'date_end', 0, 0, 0, '', 1, 0);
	$period .= ' -  '.$langs->trans("JournalizationInLedgerStatus").' '.$form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

	$varlink = 'id_journal='.$id_journal;
	$periodlink = '';
	$exportlink = '';

	journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

	// Test that setup is complete
	$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."bank_account WHERE fk_accountancy_journal IS NULL AND clos = 0";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj->nb > 0) {
			print '<br>'.img_warning().' '.$langs->trans("TheJournalCodeIsNotDefinedOnSomeBankAccount");
			print ' : '.$langs->trans("AccountancyAreaDescBank", 9, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("BankAccounts").'</strong>');
		}
	} else {
		dol_print_error($db);
	}

	// Button to write into Ledger
	if (!getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
		|| !getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1'
		|| !getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
		print '<br>'.img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
		print ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	}

	print '<br><div class="tabsAction tabsActionNoBottom centerimp">';

	if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL')) {
		print '<input type="button" class="butAction" name="exportcsv" value="'.$langs->trans("ExportDraftJournal").'" onclick="launch_export();" />';
	}

	if (!getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
		|| !getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1'
		|| !getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
		print '<input type="button" class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")).'" value="'.$langs->trans("WriteBookKeeping").'" />';
	} else {
		if ($in_bookkeeping == 'notyet') {
			print '<input type="button" class="butAction" name="writebookkeeping" value="'.$langs->trans("WriteBookKeeping").'" onclick="writebookkeeping();" />';
		} else {
			print '<a class="butActionRefused classfortooltip" name="writebookkeeping">'.$langs->trans("WriteBookKeeping").'</a>';
		}
	}
	print '</div>';

	// TODO Avoid using js. We can use a direct link with $param
	print '
	<script type="text/javascript">
		function writebookkeeping() {
			console.log("Set value into form and submit");
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
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print "<td>".$langs->trans("Date")."</td>";
	print "<td>".$langs->trans("Piece").' ('.$langs->trans("ObjectsRef").')</td>';
	print "<td>".$langs->trans("AccountAccounting")."</td>";
	print "<td>".$langs->trans("LabelOperation")."</td>";
	print '<td class="center">'.$langs->trans("PaymentMode")."</td>";
	print '<td class="right">'.$langs->trans("AccountingDebit")."</td>";
	print '<td class="right">'.$langs->trans("AccountingCredit")."</td>";
	print "</tr>\n";

	foreach ($tabpay as $payment_id => $payment) {
		$accountInfos = $tabaccount[$payment["fk_bank_account"]];
		$date = dol_print_date($payment["date"], 'day');
		$i++;

		foreach ($payment['objects'] as $object_key => $object_data) {
			$objectInfos = $tabobject[$object_key];

			// Show bank line
			if ($object_data['amount'] >= 0) {
				FormAccounting::printJournalLine($langs, $date, $objectInfos['url'], $accountInfos['account_number'], $accountInfos['account_ref'], $payment['type_payment'], $object_data['amount']);
			}

			// Operations
			$payment_total_vat = (float) price2num($object_data['amount'] * ($objectInfos['total_ttc'] - $objectInfos['total_ht']) / $objectInfos['total_ttc'], 'MT');
			$payment_total_ht = $object_data['amount'] - $payment_total_vat;
			$total_operation = 0;
			$idx = 1;
			$nb_operation = count($objectInfos['operations']);
			foreach ($objectInfos['operations'] as $accountancy_code => $operation) {
				// Set accounting account infos
				if (!isset($tabaccountingaccount[$accountancy_code])) {
					$result = $accountingaccount->fetch(0, $accountancy_code, true);
					$tabaccountingaccount[$accountancy_code] = array(
						'label' => $result < 0 ? $accountingaccount->errorsToString() : ($result > 0 ? $accountingaccount->label : $langs->trans('NotDefined')),
					);
				}
				$accountingAccountInfos = $tabaccountingaccount[$accountancy_code];
				if (!empty($operation['total_ht'])) {
					if ($idx < $nb_operation) {
						$value = price2num($payment_total_ht * $operation['total_ht'] / $objectInfos['total_ht'], 'MT');
						$total_operation += $value;
					} else {
						$value = $payment_total_ht - $total_operation;
					}
					FormAccounting::printJournalLine($langs, $date, $objectInfos['url'], $accountancy_code, (!empty($operation['label']) ? $operation['label'] : $accountingAccountInfos['label']), $payment['type_payment'], -$value);
				}
				$idx++;
			}

			// VATs
			$total_vat = 0;
			$idx = 1;
			$nb_vat = 0;
			foreach ($objectInfos['vats'] as $accountancy_code => $vats) {
				foreach ($vats as $vat_tx => $vat_infos) {
					$nb_vat++;
				}
			}
			foreach ($objectInfos['vats'] as $accountancy_code => $vats) {
				foreach ($vats as $vat_tx => $vat_infos) {
					$amount_vat = $vat_infos['total_tva'] + $vat_infos['total_localtax1'] + $vat_infos['total_localtax2'];
					if (!empty($amount_vat)) {
						$amount_vat = (float) price2num($payment_total_vat * $amount_vat / ($objectInfos['total_ttc'] - $objectInfos['total_ht']), 'MT');
						$total_vat += $amount_vat;
						FormAccounting::printJournalLine($langs, $date, $objectInfos['url'], $accountancy_code, $langs->trans('VAT').' '.price($vat_infos['tva_tx']).'%', $payment['type_payment'], -$amount_vat);
					}
					$idx++;
				}
			}

			// Show bank line
			if ($object_data['amount'] < 0) {
				FormAccounting::printJournalLine($langs, $date, $objectInfos['url'], $accountInfos['account_number'], $accountInfos['account_ref'], $payment['type_payment'], $object_data['amount']);
			}
		}
	}

	if (!$i) {
		$colspan = 8;
		print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';

	llxFooter();
}

$db->close();

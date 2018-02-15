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
 * \file		htdocs/accountancy/journal/purchasesjournal.php
 * \ingroup		Advanced accountancy
 * \brief		Page with purchases journal
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';

$langs->loadLangs(array("commercial", "compta","bills","other","accountancy","errors"));

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

$hookmanager->initHooks(array('purchasesjournal'));
$parameters=array();

/*
 * Actions
 */
$reshook=$hookmanager->executeHooks('doActions',$parameters,$user,$action);    // Note that $action and $object may have been modified by some hooks


/*
 * Views
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

$sql = "SELECT f.rowid, f.ref, f.type, f.datef as df, f.libelle,f.ref_supplier, f.date_lim_reglement as dlf, f.close_code,";
$sql .= " fd.rowid as fdid, fd.description, fd.product_type, fd.total_ht, fd.tva as total_tva, fd.total_localtax1, fd.total_localtax2, fd.tva_tx, fd.total_ttc, fd.vat_src_code,";
$sql .= " s.rowid as socid, s.nom as name, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,";
$sql .= " p.accountancy_code_buy , aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as fd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = fd.fk_product";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " JOIN " . MAIN_DB_PREFIX . "facture_fourn as f ON f.rowid = fd.fk_facture_fourn";
$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
$sql .= " WHERE f.fk_statut > 0";
$sql .= " AND fd.fk_code_ventilation > 0";
$sql .= " AND f.entity IN (" . getEntity('facture_fourn', 0) . ")";  // We don't share object for accountancy
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	$sql .= " AND f.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . "," . FactureFournisseur::TYPE_SITUATION . ")";
} else {
	$sql .= " AND f.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . "," . FactureFournisseur::TYPE_DEPOSIT . "," . FactureFournisseur::TYPE_SITUATION . ")";
}
if ($date_start && $date_end)
	$sql .= " AND f.datef >= '" . $db->idate($date_start) . "' AND f.datef <= '" . $db->idate($date_end) . "'";
// Already in bookkeeping or not
if ($in_bookkeeping == 'already')
{
	$sql .= " AND f.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='supplier_invoice')";
}
if ($in_bookkeeping == 'notyet')
{
	$sql .= " AND f.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='supplier_invoice')";
}
$sql .= " ORDER BY f.datef";

dol_syslog('accountancy/journal/purchasesjournal.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$tabfac = array ();
	$tabht = array ();
	$tabtva = array ();
	$def_tva = array ();
	$tabttc = array ();
	$tablocaltax1 = array ();
	$tablocaltax2 = array ();
	$tabcompany = array ();
	$tabother = array();

	$num = $db->num_rows($result);

	// Variables
	$cptfour = (! empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER)) ? $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER : 'NotDefined';
	$cpttva = (! empty($conf->global->ACCOUNTING_VAT_BUY_ACCOUNT)) ? $conf->global->ACCOUNTING_VAT_BUY_ACCOUNT : 'NotDefined';

	$i = 0;
	while ( $i < $num ) {
		$obj = $db->fetch_object($result);

		// Controls
		$compta_soc = (! empty($obj->code_compta_fournisseur)) ? $obj->code_compta_fournisseur : $cptfour;

		$compta_prod = $obj->compte;
		if (empty($compta_prod)) {
			if ($obj->product_type == 0)
				$compta_prod = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT)) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : 'NotDefined';
			else
				$compta_prod = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT)) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : 'NotDefined';
		}

		$vatdata = getTaxesFromId($obj->tva_tx.($obj->vat_src_code?' ('.$obj->vat_src_code.')':''), $mysoc, $mysoc, 0);
		$compta_tva = (! empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);
		$compta_localtax1 = (! empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);
		$compta_localtax2 = (! empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);
		$compta_counterpart_tva_npr = (! empty($conf->global->ACCOUNTING_COUNTERPART_VAT_NPR)) ? $conf->global->ACCOUNTING_COUNTERPART_VAT_NPR : 'NotDefined';

		// Define array to display all VAT rates that use this accounting account $compta_tva
		if (price2num($obj->tva_tx) || ! empty($obj->vat_src_code))
		{
			$def_tva[$obj->rowid][$compta_tva][vatrate($obj->tva_tx).($obj->vat_src_code?' ('.$obj->vat_src_code.')':'')]=(vatrate($obj->tva_tx).($obj->vat_src_code?' ('.$obj->vat_src_code.')':''));
		}

		$line = new SupplierInvoiceLine($db);
		$line->fetch($obj->fdid);

		$tabfac[$obj->rowid]["date"] = $db->jdate($obj->df);
		$tabfac[$obj->rowid]["datereg"] = $db->jdate($obj->dlr);
		$tabfac[$obj->rowid]["ref"] = $obj->ref_supplier . ' (' . $obj->ref . ')';
		$tabfac[$obj->rowid]["refsologest"] = $obj->ref;
		$tabfac[$obj->rowid]["refsuppliersologest"] = $obj->ref_supplier;
		$tabfac[$obj->rowid]["type"] = $obj->type;
		$tabfac[$obj->rowid]["description"] = $obj->description;
		$tabfac[$obj->rowid]["close_code"] = $obj->close_code;		// close_code = 'replaced' for replacement invoices (not used in most european countries)
		//$tabfac[$obj->rowid]["fk_facturefourndet"] = $obj->fdid;

		// Avoid warnings
		if (! isset($tabttc[$obj->rowid][$compta_soc])) $tabttc[$obj->rowid][$compta_soc] = 0;
		if (! isset($tabht[$obj->rowid][$compta_prod])) $tabht[$obj->rowid][$compta_prod] = 0;
		if (! isset($tabtva[$obj->rowid][$compta_tva])) $tabtva[$obj->rowid][$compta_tva] = 0;
		if (! isset($tablocaltax1[$obj->rowid][$compta_localtax1])) $tablocaltax1[$obj->rowid][$compta_localtax1] = 0;
		if (! isset($tablocaltax2[$obj->rowid][$compta_localtax2])) $tablocaltax2[$obj->rowid][$compta_localtax2] = 0;

		$tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc;
		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht;
		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
		if (! empty($line->tva_npr))	// Add an entry for counterpart
		{
			$tabother[$obj->rowid][$compta_counterpart_tva_npr] += $obj->total_tva;
		}
		$tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1;
		$tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2;
		$tabcompany[$obj->rowid] = array (
				'id' => $obj->socid,
				'name' => $obj->name,
				'code_fournisseur' => $obj->code_fournisseur,
				'code_compta_fournisseur' => $compta_soc
			);

		$i++;
	}
} else {
	dol_print_error($db);
}

$errorforinvoice = array();

// Loop in invoices to detect lines with not binding lines
foreach ($tabfac as $key => $val) {		// Loop on each invoice
	$sql = "SELECT COUNT(fd.rowid) as nb";
	$sql.= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as fd";
	$sql.= " WHERE fd.product_type <= 2 AND fd.fk_code_ventilation <= 0";
	$sql.= " AND fd.total_ttc <> 0 AND fk_facture_fourn = ".$key;
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj->nb > 0)
		{
			$errorforinvoice[$key]='somelinesarenotbound';
		}
	}
	else dol_print_error($db);
}
//var_dump($errorforinvoice);exit;



// Bookkeeping Write
if ($action == 'writebookkeeping') {
	$now = dol_now();
	$error = 0;

	$companystatic = new Societe($db);
	$invoicestatic = new FactureFournisseur($db);

	foreach ($tabfac as $key => $val) {		// Loop on each invoice

		$errorforline = 0;

		$totalcredit = 0;
		$totaldebit = 0;

		$db->begin();

		$companystatic->id = $tabcompany[$key]['id'];
		$companystatic->name = $tabcompany[$key]['name'];
		$companystatic->code_compta = $tabcompany[$key]['code_compta'];
		$companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
		$companystatic->code_client = $tabcompany[$key]['code_client'];
		$companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
		$companystatic->fournisseur = 1;

		$invoicestatic->id = $key;
		$invoicestatic->ref = (string) $val["refsologest"];
		$invoicestatic->ref_supplier = $val["refsuppliersologest"];
		$invoicestatic->type = $val["type"];
		$invoicestatic->description = html_entity_decode(dol_trunc($val["description"], 32));
		$invoicestatic->close_code = $val["close_code"];

		$date = dol_print_date($val["date"], 'day');

		// Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
		$replacedinvoice = 0;
		if ($invoicestatic->close_code == FactureFournisseur::CLOSECODE_REPLACED)
		{
			$replacedinvoice = 1;
			$alreadydispatched = $invoicestatic->getVentilExportCompta();	// Test if replaced invoice already into bookkeeping.
			if ($alreadydispatched) $replacedinvoice = 2;
		}

		// If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
		if ($replacedinvoice == 1)
		{
			$db->rollback();
			continue;
		}

		// Error if some lines are not binded/ready to be journalized
		if ($errorforinvoice[$key] == 'somelinesarenotbound')
		{
			$error++;
			$errorforline++;
			setEventMessages($langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $val['ref']), null, 'errors');
		}

		// Thirdparty
		if (! $errorforline)
		{
			foreach ( $tabttc[$key] as $k => $mt ) {
				//if ($mt) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->date_lim_reglement = $val["datereg"];
					$bookkeeping->doc_ref = $val["refsologest"];
					$bookkeeping->date_create = $now;
					$bookkeeping->doc_type = 'supplier_invoice';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add
					$bookkeeping->thirdparty_code = $companystatic->code_fournisseur;
					$bookkeeping->subledger_account = $tabcompany[$key]['code_compta_fournisseur'];
					$bookkeeping->subledger_label = '';    // TODO To complete
					$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
					$bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("SubledgerAccount");
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
							$errorforinvoice[$key]='alreadyjournalized';
							//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
						}
						else
						{
							$error++;
							$errorforline++;
							$errorforinvoice[$key]='other';
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				//}
			}
		}

		// Product / Service
		if (! $errorforline)
		{
			foreach ( $tabht[$key] as $k => $mt ) {
				//if ($mt) {
					// get compte id and label
					$accountingaccount = new AccountingAccount($db);
					if ($accountingaccount->fetch(null, $k, true)) {
						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->date_lim_reglement = $val["datereg"];
						$bookkeeping->doc_ref = $val["refsologest"];
						$bookkeeping->date_create = $now;
						$bookkeeping->doc_type = 'supplier_invoice';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = 0;	// Useless, can be several lines that are source of this record to add
						$bookkeeping->thirdparty_code = $companystatic->code_fournisseur;
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $accountingaccount->label;
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
						$bookkeeping->debit = ($mt > 0) ? $mt : 0;
						$bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
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
								$errorforinvoice[$key]='alreadyjournalized';
								//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
							}
							else
							{
								$error++;
								$errorforline++;
								$errorforinvoice[$key]='other';
								setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
							}
						}
					}
				//}
			}
		}

		// VAT
		// var_dump($tabtva);
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
						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->date_lim_reglement = $val["datereg"];
						$bookkeeping->doc_ref = $val["refsologest"];
						$bookkeeping->date_create = $now;
						$bookkeeping->doc_type = 'supplier_invoice';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add
						$bookkeeping->thirdparty_code = $companystatic->code_fournisseur;
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT").' '.join(', ',$def_tva[$key][$k]) .' %' . ($numtax?' - Localtax '.$numtax:'');
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
						$bookkeeping->debit = ($mt > 0) ? $mt : 0;
						$bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
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
								$errorforinvoice[$key]='alreadyjournalized';
								//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
							}
							else
							{
								$error++;
								$errorforline++;
								$errorforinvoice[$key]='other';
								setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
							}
						}
					}
				}
			}
		}

		// Counterpart of VAT for VAT NPR
		// var_dump($tabother);
		if (! $errorforline && is_array($tabother[$key]))
		{
			foreach ( $tabother[$key] as $k => $mt ) {
				if ($mt) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->date_lim_reglement = $val["datereg"];
					$bookkeeping->doc_ref = $val["refsologest"];
					$bookkeeping->date_create = $now;
					$bookkeeping->doc_type = 'supplier_invoice';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add
					$bookkeeping->thirdparty_code = $companystatic->code_fournisseur;
					$bookkeeping->subledger_account = '';
					$bookkeeping->subledger_label = '';
					$bookkeeping->numero_compte = $k;
					$bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT").' NPR';
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
					$bookkeeping->debit = ($mt > 0) ? $mt : 0;
					$bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
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
							$errorforinvoice[$key]='alreadyjournalized';
							//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
						}
						else
						{
							$error++;
							$errorforline++;
							$errorforinvoice[$key]='other';
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				}
			}
		}

		// Protection against a bug on lines before
		if (! $errorforline && (price2num($totaldebit) != price2num($totalcredit)))
		{
			$error++;
			$errorforline++;
			$errorforinvoice[$key]='amountsnotbalanced';
			setEventMessages('Try to insert a non balanced transaction in book for '.$invoicestatic->ref.'. Canceled. Surely a bug.', null, 'errors');
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

	$tabpay = $tabfac;

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

// Export
if ($action == 'exportcsv') {
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	$companystatic = new Fournisseur($db);
	$invoicestatic = new FactureFournisseur($db);

	foreach ( $tabfac as $key => $val )
	{
		$companystatic->id = $tabcompany[$key]['id'];
		$companystatic->name = $tabcompany[$key]['name'];
		$companystatic->code_compta = $tabcompany[$key]['code_compta'];
		$companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
		$companystatic->code_client = $tabcompany[$key]['code_client'];
		$companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
		$companystatic->fournisseur = 1;

		$invoicestatic->id = $key;
		$invoicestatic->ref = $val["refsologest"];
		$invoicestatic->ref_supplier = $val["refsuppliersologest"];
		$invoicestatic->type = $val["type"];
		$invoicestatic->description = dol_trunc(html_entity_decode($val["description"]), 32);
		$invoicestatic->close_code = $val["close_code"];

		$date = dol_print_date($val["date"], 'day');

		// Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
		$replacedinvoice = 0;
		if ($invoicestatic->close_code == FactureFournisseur::CLOSECODE_REPLACED)
		{
			$replacedinvoice = 1;
			$alreadydispatched = $invoicestatic->getVentilExportCompta();	// Test if replaced invoice already into bookkeeping.
			if ($alreadydispatched) $replacedinvoice = 2;
		}

		// If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
		if ($replacedinvoice == 1)
		{
			continue;
		}

		// Third party
		foreach ( $tabttc[$key] as $k => $mt ) {
			//if ($mt) {
				print '"' . $key . '"' . $sep;
				print '"' . $date . '"' . $sep;
				print '"' . $val["refsologest"] . '"' . $sep;
				print '"' . utf8_decode ( dol_trunc($companystatic->name, 32) ). '"' . $sep;
				print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
				print '"' . $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER . '"' . $sep;
				print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
				print '"' . $langs->trans("Code_tiers") . '"' . $sep;
				print '"' . utf8_decode ( dol_trunc($companystatic->name, 16) ) . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("Code_tiers") . '"' . $sep;
				print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
				print '"' . ($mt >= 0 ? price($mt) : '') . '"'. $sep;
				print '"' . $journal . '"' ;
				print "\n";
			//}
		}

		// Product / Service
		foreach ( $tabht[$key] as $k => $mt ) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k, true);
			//if ($mt) {
				print '"' . $key . '"' . $sep;
				print '"' . $date . '"' . $sep;
				print '"' . $val["refsologest"] . '"' . $sep;
				print '"' . utf8_decode ( dol_trunc($companystatic->name, 32) ) . '"' . $sep;
				print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
				print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
				print '""' . $sep;
				print '"' . utf8_decode ( dol_trunc($accountingaccount->label, 32) ) . '"' . $sep;
				print '"' . utf8_decode ( dol_trunc($companystatic->name, 16) ) . ' - ' . $val["refsuppliersologest"] . ' - ' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
				print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
				print '"' . ($mt < 0 ? price(- $mt) : '') . '"'. $sep;
				print '"' . $journal . '"' ;
				print "\n";
			//}
		}

		// VAT
		$listoftax = array(0, 1, 2);
		foreach ($listoftax as $numtax) {
			$arrayofvat = $tabtva;
			if ($numtax == 1) $arrayofvat = $tablocaltax1;
			if ($numtax == 2) $arrayofvat = $tablocaltax2;

			foreach ($arrayofvat[$key] as $k => $mt) {
				if ($mt) {
					print '"' . $key . '"' . $sep;
					print '"' . $date . '"' . $sep;
					print '"' . $val["refsologest"] . '"' . $sep;
					print '"' . utf8_decode ( dol_trunc($companystatic->name, 32) ) . '"' . $sep;
					print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
					print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
					print '""' . $sep;
					print '"' . $langs->trans("VAT") . ' - ' . $def_tva[$key] . '"' . $sep;
					print '"' . utf8_decode(dol_trunc($companystatic->name, 16) ) . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("VAT") . join(', ',$def_tva[$key][$k]) .' %' . ($numtax?' - Localtax '.$numtax:'') . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"'. $sep;
					print '"' . $journal . '"' ;
					print "\n";
				}
			}

			// VAT counterpart for NPR
			if (is_array($tabother[$key]))
			{
				foreach ( $tabother[$key] as $k => $mt ) {
					if ($mt) {
						print '"' . $key . '"' . $sep;
						print '"' . $date . '"' . $sep;
						print '"' . $val["refsologest"] . '"' . $sep;
						print '"' . utf8_decode ( dol_trunc($companystatic->name, 32) ). '"' . $sep;
						print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
						print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
						print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
						print '"' . $langs->trans("Code_tiers") . '"' . $sep;
						print '"' . utf8_decode ( dol_trunc($companystatic->name, 16) ) . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("VAT") . ' NPR"' . $sep;
						print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
						print '"' . ($mt >= 0 ? price($mt) : '') . '"'. $sep;
						print '"' . $journal . '"' ;
						print "\n";
					}
				}
			}
		}
	}
}

if (empty($action) || $action == 'view') {

	llxHeader('', $langs->trans("PurchasesJournal"));

	$nom = $langs->trans("PurchasesJournal") . ' - ' . $accountingjournalstatic->getNomUrl(1);
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate=dol_now();
	$description.= $langs->trans("DescJournalOnlyBindedVisible").'<br>';
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
		$description .= $langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= $langs->trans("DepositsAreIncluded");
	}

	$listofchoices=array('already'=>$langs->trans("AlreadyInGeneralLedger"), 'notyet'=>$langs->trans("NotYetInGeneralLedger"));
	$period = $form->select_date($date_start?$date_start:-1, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end?$date_end:-1, 'date_end', 0, 0, 0, '', 1, 0, 1). ' -  ' .$langs->trans("JournalizationInLedgerStatus").' '. $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

	$varlink = 'id_journal=' . $id_journal;

	journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

	// Button to write into Ledger
	if (empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) || $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER == '-1') {
		print img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
		print ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	}
	print '<div class="tabsAction tabsActionNoBottom">';
	print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
	if (empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) || $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER == '-1') {
		print '<input type="button" class="butActionRefused" title="'.dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")).'" value="' . $langs->trans("WriteBookKeeping") . '" />';
	}
	else {
		if ($in_bookkeeping == 'notyet') print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
		else print '<a href="#" class="butActionRefused" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
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
	print "<td></td>";
	print "<td>" . $langs->trans("Date") . "</td>";
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("InvoiceRef") . ")</td>";
	print "<td>" . $langs->trans("AccountAccounting") . "</td>";
	print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
	print "<td>" . $langs->trans("LabelOperation") . "</td>";
	print "<td align='right'>" . $langs->trans("Debit") . "</td>";
	print "<td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$r = '';

	$invoicestatic = new FactureFournisseur($db);
	$companystatic = new Fournisseur($db);

	foreach ( $tabfac as $key => $val )
	{
		$companystatic->id = $tabcompany[$key]['id'];
		$companystatic->name = $tabcompany[$key]['name'];
		$companystatic->code_compta = $tabcompany[$key]['code_compta'];
		$companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
		$companystatic->code_client = $tabcompany[$key]['code_client'];
		$companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
		$companystatic->fournisseur = 1;

		$invoicestatic->id = $key;
		$invoicestatic->ref = $val["refsologest"];
		$invoicestatic->ref_supplier = $val["refsuppliersologest"];
		$invoicestatic->type = $val["type"];
		$invoicestatic->description = dol_trunc(html_entity_decode($val["description"]), 32);
		$invoicestatic->close_code = $val["close_code"];

		$date = dol_print_date($val["date"], 'day');

		// Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
		$replacedinvoice = 0;
		if ($invoicestatic->close_code == FactureFournisseur::CLOSECODE_REPLACED)
		{
			$replacedinvoice = 1;
			$alreadydispatched = $invoicestatic->getVentilExportCompta();	// Test if replaced invoice already into bookkeeping.
			if ($alreadydispatched) $replacedinvoice = 2;
		}

		// If not already into bookkeeping, we won't add it, if yes, add the counterpart ???.
		if ($replacedinvoice == 1)
		{
			print '<tr class="oddeven">';
			print "<td><!-- Replaced invoice --></td>";
			print "<td>" . $date . "</td>";
			print "<td><strike>" . $invoicestatic->getNomUrl(1) . "</strike></td>";
			// Account
			print "<td>";
			print $langs->trans("Replaced");
			print '</td>';
			// Subledger account
			print "<td>";
			print '</td>';
			print "<td>";
			print "</td>";
			print '<td align="right"></td>';
			print '<td align="right"></td>';
			print "</tr>";

			continue;
		}
		if ($errorforinvoice[$key] == 'somelinesarenotbound')
		{
			print '<tr class="oddeven">';
			print "<td><!-- Some lines are not bound --></td>";
			print "<td>" . $date . "</td>";
			print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
			// Account
			print "<td>";
			print '<span class="error">'.$langs->trans('ErrorInvoiceContainsLinesNotYetBoundedShort', $val['ref']).'</span>';
			print '</td>';
			// Subledger account
			print "<td>";
			print '</td>';
			print "<td>";
			print "</td>";
			print '<td align="right"></td>';
			print '<td align="right"></td>';
			print "</tr>";
		}

		// Third party
		foreach ( $tabttc[$key] as $k => $mt ) {
			//if ($mt) {
				print '<tr class="oddeven">';
				print "<td><!-- Thirdparty --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				// Account
				print "<td>";
				$accountoshow = length_accounta($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER);
				if (empty($accountoshow) || $accountoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("MainAccountForCustomersNotDefined").'</span>';
				}
				else print $accountoshow;
				print '</td>';
				// Subledger account
				print "<td>";
				$accountoshow = length_accounta($k);
				if (empty($accountoshow) || $accountoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
				}
				else print $accountoshow;
				print '</td>';
				print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("SubledgerAccount") . "</td>";
				print '<td align="right">' . ($mt < 0 ? - price(- $mt) : '') . "</td>";
				print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print "</tr>";
			//}
		}

		// Product / Service
		foreach ( $tabht[$key] as $k => $mt ) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k, true);

			//if ($mt) {
				print '<tr class="oddeven">';
				print "<td><!-- Product --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				// Account
				print "<td>";
				$accountoshow = length_accountg($k);
				if (empty($accountoshow) || $accountoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("ProductAccountNotDefined").'</span>';
				}
				else print $accountoshow;
				print "</td>";
				// Subledger account
				print "<td>";
				print '</td>';
				$companystatic->id = $tabcompany[$key]['id'];
				$companystatic->name = $tabcompany[$key]['name'];
				print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $accountingaccount->label . "</td>";
				print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print '<td align="right">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "</tr>";
			//}
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
					print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
					// Account
					print "<td>";
					$accountoshow = length_accountg($k);
					if (empty($accountoshow) || $accountoshow == 'NotDefined')
					{
						print '<span class="error">'.$langs->trans("VATAccountNotDefined").' ('.$langs->trans("Purchase").')'.'</span>';
					}
					else print $accountoshow;
					print "</td>";
					// Subledger account
					print "<td>";
					print '</td>';
					print "<td>";
					print $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT"). ' '.join(', ',$def_tva[$key][$k]).' %'.($numtax?' - Localtax '.$numtax:'');
					print "</td>";
					print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
					print '<td align="right">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
					print "</tr>";
				}
			}
		}

		// VAT counterpart for NPR
		if (is_array($tabother[$key]))
		{
			foreach ( $tabother[$key] as $k => $mt ) {
				if ($mt) {
					print '<tr class="oddeven">';
					print "<td><!-- VAT counterpart NPR --></td>";
					print "<td>" . $date . "</td>";
					print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
					// Account
					print "<td>";
					$accountoshow = length_accountg($k);
					if (empty($accountoshow) || $accountoshow == 'NotDefined')
					{
						print '<span class="error">'.$langs->trans("VATAccountNotDefined").' ('.$langs->trans("NPR counterpart").'). Set ACCOUNTING_COUNTERPART_VAT_NPR to the subvention account'.'</span>';
					}
					else print $accountoshow;
					print '</td>';
					// Subledger account
					print "<td>";
					print '</td>';
					print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT") . " NPR (counterpart)</td>";
					print '<td align="right">' . ($mt < 0 ? - price(- $mt) : '') . "</td>";
					print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
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

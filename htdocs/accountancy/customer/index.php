<?php
/* Copyright (C) 2013       Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014  Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2022  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry <jfefe@aternatik.fr>
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
 *
 */

/**
 * \file 	htdocs/accountancy/customer/index.php
 * \ingroup Accountancy (Double entries)
 * \brief 	Home customer journalization page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "other", "accountancy"));

$validatemonth = GETPOSTINT('validatemonth');
$validateyear = GETPOSTINT('validateyear');

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

$accountingAccount = new AccountingAccount($db);

$month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
if (GETPOSTINT("year")) {
	$year_start = GETPOSTINT("year");
} else {
	$year_start = dol_print_date(dol_now(), '%Y');
	if (dol_print_date(dol_now(), '%m') < $month_start) {
		$year_start--; // If current month is lower that starting fiscal month, we start last year
	}
}
$year_end = $year_start + 1;
$month_end = $month_start - 1;
if ($month_end < 1) {
	$month_end = 12;
	$year_end--;
}
$search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
$search_date_end = dol_get_last_day($year_end, $month_end);
$year_current = $year_start;

// Validate History
$action = GETPOST('action', 'aZ09');

$chartaccountcode = dol_getIdFromCode($db, getDolGlobalInt('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

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

if (($action == 'clean' || $action == 'validatehistory') && $user->hasRight('accounting', 'bind', 'write')) {
	// Clean database by removing binding done on non existing or no more existing accounts
	$db->begin();
	$sql1 = "UPDATE ".$db->prefix()."facturedet as fd";
	$sql1 .= " SET fk_code_ventilation = 0";
	$sql1 .= ' WHERE fd.fk_code_ventilation NOT IN';
	$sql1 .= '	(SELECT accnt.rowid ';
	$sql1 .= '	FROM '.$db->prefix().'accounting_account as accnt';
	$sql1 .= '	INNER JOIN '.$db->prefix().'accounting_system as syst';
	$sql1 .= "	ON accnt.fk_pcg_version = syst.pcg_version AND syst.rowid = ".((int) getDolGlobalInt('CHARTOFACCOUNTS'))." AND accnt.entity = ".((int) $conf->entity).")";
	$sql1 .= " AND fd.fk_facture IN (SELECT rowid FROM ".$db->prefix()."facture WHERE entity = ".((int) $conf->entity).")";
	$sql1 .= " AND fk_code_ventilation <> 0";

	dol_syslog("htdocs/accountancy/customer/index.php fixaccountancycode", LOG_DEBUG);
	$resql1 = $db->query($sql1);
	if (!$resql1) {
		$error++;
		$db->rollback();
		setEventMessages($db->lasterror(), null, 'errors');
	} else {
		$db->commit();
	}
	// End clean database
}

if ($action == 'validatehistory' && $user->hasRight('accounting', 'bind', 'write')) {
	$error = 0;
	$nbbinddone = 0;
	$nbbindfailed = 0;
	$notpossible = 0;

	$db->begin();

	// Now make the binding. Bind automatically only for product with a dedicated account that exists into chart of account, others need a manual bind
	// Customer Invoice lines (must be same request than into page list.php for manual binding)
	$sql = "SELECT f.rowid as facid, f.ref as ref, f.datef, f.type as ftype, f.situation_cycle_ref, f.fk_facture_source,";
	$sql .= " l.rowid, l.fk_product, l.description, l.total_ht, l.fk_code_ventilation, l.product_type as type_l, l.situation_percent, l.tva_tx as tva_tx_line, l.vat_src_code,";
	$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.tva_tx as tva_tx_prod,";
	if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
		$sql .= " ppe.accountancy_code_sell as code_sell, ppe.accountancy_code_sell_intra as code_sell_intra, ppe.accountancy_code_sell_export as code_sell_export,";
	} else {
		$sql .= " p.accountancy_code_sell as code_sell, p.accountancy_code_sell_intra as code_sell_intra, p.accountancy_code_sell_export as code_sell_export,";
	}
	$sql .= " aa.rowid as aarowid, aa2.rowid as aarowid_intra, aa3.rowid as aarowid_export, aa4.rowid as aarowid_thirdparty,";
	$sql .= " co.code as country_code, co.label as country_label,";
	$sql .= " s.tva_intra,";
	if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
		$sql .= " spe.accountancy_code_sell as company_code_sell";	// accounting code for product but stored on thirdparty
	} else {
		$sql .= " s.accountancy_code_sell as company_code_sell";	// accounting code for product but stored on thirdparty
	}
	$sql .= " FROM ".$db->prefix()."facture as f";
	$sql .= " INNER JOIN ".$db->prefix()."societe as s ON s.rowid = f.fk_soc";
	if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
		$sql .= " LEFT JOIN " . $db->prefix() . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
	}
	$sql .= " LEFT JOIN ".$db->prefix()."c_country as co ON co.rowid = s.fk_pays ";
	$sql .= " INNER JOIN ".$db->prefix()."facturedet as l ON f.rowid = l.fk_facture";	// the main table
	$sql .= " LEFT JOIN ".$db->prefix()."product as p ON p.rowid = l.fk_product";
	if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
		$sql .= " LEFT JOIN " . $db->prefix() . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
	}
	$alias_societe_perentity = !getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED') ? "s" : "spe";
	$alias_product_perentity = !getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p" : "ppe";
	$sql .= " LEFT JOIN ".$db->prefix()."accounting_account as aa  ON ".$db->sanitize($alias_product_perentity).".accountancy_code_sell = aa.account_number         AND aa.active = 1  AND aa.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND aa.entity = ".$conf->entity;
	$sql .= " LEFT JOIN ".$db->prefix()."accounting_account as aa2 ON ".$db->sanitize($alias_product_perentity).".accountancy_code_sell_intra = aa2.account_number  AND aa2.active = 1 AND aa2.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND aa2.entity = ".$conf->entity;
	$sql .= " LEFT JOIN ".$db->prefix()."accounting_account as aa3 ON ".$db->sanitize($alias_product_perentity).".accountancy_code_sell_export = aa3.account_number AND aa3.active = 1 AND aa3.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND aa3.entity = ".$conf->entity;
	$sql .= " LEFT JOIN ".$db->prefix()."accounting_account as aa4 ON ".$db->sanitize($alias_societe_perentity).".accountancy_code_sell = aa4.account_number        AND aa4.active = 1 AND aa4.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND aa4.entity = ".$conf->entity;
	$sql .= " WHERE f.fk_statut > 0 AND l.fk_code_ventilation <= 0";
	$sql .= " AND l.product_type <= 2";
	$sql .= " AND f.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
	if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
		$sql .= " AND f.datef >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
	}
	if ($validatemonth && $validateyear) {
		$sql .= dolSqlDateFilter('f.datef', 0, $validatemonth, $validateyear);
	}

	dol_syslog('htdocs/accountancy/customer/index.php');

	$result = $db->query($sql);
	if (!$result) {
		$error++;
		setEventMessages($db->lasterror(), null, 'errors');
	} else {
		$num_lines = $db->num_rows($result);

		$facture_static = new Facture($db);

		$isSellerInEEC = isInEEC($mysoc);

		$thirdpartystatic = new Societe($db);
		$facture_static = new Facture($db);
		$facture_static_det = new FactureLigne($db);
		$product_static = new Product($db);

		$i = 0;
		while ($i < min($num_lines, 10000)) {	// No more than 10000 at once
			$objp = $db->fetch_object($result);

			$thirdpartystatic->id = !empty($objp->socid) ? $objp->socid : 0;
			$thirdpartystatic->name = !empty($objp->name) ? $objp->name : "";
			$thirdpartystatic->client = !empty($objp->client) ? $objp->client : "";
			$thirdpartystatic->fournisseur = !empty($objp->fournisseur) ? $objp->fournisseur : "";
			$thirdpartystatic->code_client = !empty($objp->code_client) ? $objp->code_client : "";
			$thirdpartystatic->code_compta_client = !empty($objp->code_compta_client) ? $objp->code_compta_client : "";
			$thirdpartystatic->code_fournisseur = !empty($objp->code_fournisseur) ? $objp->code_fournisseur : "";
			$thirdpartystatic->code_compta_fournisseur = !empty($objp->code_compta_fournisseur) ? $objp->code_compta_fournisseur : "";
			$thirdpartystatic->email = !empty($objp->email) ? $objp->email : "";
			$thirdpartystatic->country_code = !empty($objp->country_code) ? $objp->country_code : "";
			$thirdpartystatic->tva_intra = !empty($objp->tva_intra) ? $objp->tva_intra : "";
			$thirdpartystatic->code_compta_product = !empty($objp->company_code_sell) ? $objp->company_code_sell : "";		// The accounting account for product stored on thirdparty object (for level3 suggestion)

			$product_static->ref = $objp->product_ref;
			$product_static->id = $objp->product_id;
			$product_static->type = $objp->type;
			$product_static->label = $objp->product_label;
			$product_static->status = !empty($objp->status) ? $objp->status : 0;
			$product_static->status_buy = !empty($objp->status_buy) ? $objp->status_buy : 0;
			$product_static->accountancy_code_sell = $objp->code_sell;
			$product_static->accountancy_code_sell_intra = $objp->code_sell_intra;
			$product_static->accountancy_code_sell_export = $objp->code_sell_export;
			$product_static->accountancy_code_buy = !empty($objp->code_buy) ? $objp->code_buy : "";
			$product_static->accountancy_code_buy_intra = !empty($objp->code_buy_intra) ? $objp->code_buy_intra : "";
			$product_static->accountancy_code_buy_export = !empty($objp->code_buy_export) ? $objp->code_buy_export : "";
			$product_static->tva_tx = $objp->tva_tx_prod;

			$facture_static->ref = $objp->ref;
			$facture_static->id = $objp->facid;
			$facture_static->type = $objp->ftype;
			$facture_static->date = $db->jdate($objp->datef);
			$facture_static->fk_facture_source = $objp->fk_facture_source;

			$facture_static_det->id = $objp->rowid;
			$facture_static_det->total_ht = $objp->total_ht;
			$facture_static_det->tva_tx = $objp->tva_tx_line;
			$facture_static_det->vat_src_code = $objp->vat_src_code;
			$facture_static_det->product_type = $objp->type_l;
			$facture_static_det->desc = $objp->description;

			$accountingAccountArray = array(
				'dom' => $objp->aarowid,
				'intra' => $objp->aarowid_intra,
				'export' => $objp->aarowid_export,
				'thirdparty' => $objp->aarowid_thirdparty);

			$code_sell_p_notset = '';
			$code_sell_t_notset = '';

			$suggestedid = 0;

			$return = $accountingAccount->getAccountingCodeToBind($thirdpartystatic, $mysoc, $product_static, $facture_static, $facture_static_det, $accountingAccountArray, 'customer');
			if (!is_array($return) && $return < 0) {
				setEventMessage($accountingAccount->error, 'errors');
			} else {
				$suggestedid = $return['suggestedid'];
				$suggestedaccountingaccountfor = $return['suggestedaccountingaccountfor'];

				if (!empty($suggestedid) && $suggestedaccountingaccountfor != '' && $suggestedaccountingaccountfor != 'eecwithoutvatnumber') {
					$suggestedid = $return['suggestedid'];
				} else {
					$suggestedid = 0;
				}
			}

			if ($suggestedid > 0) {
				$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."facturedet";
				$sqlupdate .= " SET fk_code_ventilation = ".((int) $suggestedid);
				$sqlupdate .= " WHERE fk_code_ventilation <= 0 AND product_type <= 2 AND rowid = ".((int) $facture_static_det->id);

				$resqlupdate = $db->query($sqlupdate);
				if (!$resqlupdate) {
					$error++;
					setEventMessages($db->lasterror(), null, 'errors');
					$nbbindfailed++;
					break;
				} else {
					$nbbinddone++;
				}
			} else {
				$notpossible++;
				$nbbindfailed++;
			}

			$i++;
		}
		if ($num_lines > 10000) {
			$notpossible += ($num_lines - 10000);
		}
	}

	if ($error) {
		$db->rollback();
	} else {
		$db->commit();
		setEventMessages($langs->trans('AutomaticBindingDone', $nbbinddone, $notpossible), null, ($notpossible ? 'warnings' : 'mesgs'));
		if ($nbbindfailed) {
			setEventMessages($langs->trans('DoManualBindingForFailedRecord', $nbbindfailed), null, 'warnings');
		}
	}
}


/*
 * View
 */
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

llxHeader('', $langs->trans("CustomersVentilation"), $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-customer page-index');

$textprevyear = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current - 1).'">'.img_previous().'</a>';
$textnextyear = '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current + 1).'">'.img_next().'</a>';


print load_fiche_titre($langs->trans("CustomersVentilation")." ".$textprevyear." ".$langs->trans("Year")." ".$year_start." ".$textnextyear, '', 'title_accountancy');

print '<span class="opacitymedium">'.$langs->trans("DescVentilCustomer").'</span><br>';
print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("DescVentilMore", $langs->transnoentitiesnoconv("ValidateHistory"), $langs->transnoentitiesnoconv("ToBind")).'<br>';
print '</span><br>';

if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
	print info_admin($langs->trans("SorryThisModuleIsNotCompatibleWithTheExperimentalFeatureOfSituationInvoices"));
	print "<br>";
}

$y = $year_current;

$buttonbind = '<a class="button small" href="'.$_SERVER['PHP_SELF'].'?action=validatehistory&token='.newToken().'">'.img_picto($langs->trans("ValidateHistory"), 'link', 'class="pictofixedwidth fa-color-unset"').$langs->trans("ValidateHistory").'</a>';

print_barre_liste(img_picto('', 'unlink', 'class="paddingright fa-color-unset"').$langs->trans("OverviewOfAmountOfLinesNotBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1, 0, $buttonbind);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesNotBound"), $buttonbind, '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="minwidth100">'.$langs->trans("Account").'</td>';
for ($i = 1; $i <= 12; $i++) {
	$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
	if ($j > 12) {
		$j -= 12;
	}
	$cursormonth = $j;
	if ($cursormonth > 12) {
		$cursormonth -= 12;
	}
	$cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
	$tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

	print '<td width="60" class="right">';
	if (!empty($tmp['mday'])) {
		$param = 'search_date_startday=1&search_date_startmonth='.$cursormonth.'&search_date_startyear='.$cursoryear;
		$param .= '&search_date_endday='.$tmp['mday'].'&search_date_endmonth='.$tmp['mon'].'&search_date_endyear='.$tmp['year'];
		print '<a href="'.DOL_URL_ROOT.'/accountancy/customer/list.php?'.$param.'">';
	}
	print $langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT));
	if (!empty($tmp['mday'])) {
		print '</a>';
	}
	print '</td>';
}
print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

$sql = "SELECT ".$db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number')." AS codecomptable,";
$sql .= "  ".$db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label')." AS intitule,";
for ($i = 1; $i <= 12; $i++) {
	$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
	if ($j > 12) {
		$j -= 12;
	}
	$sql .= "  SUM(".$db->ifsql("MONTH(f.datef) = ".((string) $j), "fd.total_ht", "0").") AS month".str_pad((string) $j, 2, "0", STR_PAD_LEFT).",";
}
$sql .= "  SUM(fd.total_ht) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
$sql .= "  LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = fd.fk_facture";
$sql .= "  LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " WHERE f.datef >= '".$db->idate($search_date_start)."'";
$sql .= "  AND f.datef <= '".$db->idate($search_date_end)."'";
// Define begin binding date
if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND f.datef >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
}
$sql .= " AND f.fk_statut > 0";
$sql .= " AND fd.product_type <= 2";
$sql .= " AND f.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
$sql .= " AND aa.account_number IS NULL";
if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_SITUATION.")";
} else {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_DEPOSIT.",".Facture::TYPE_SITUATION.")";
}
$sql .= " GROUP BY fd.fk_code_ventilation,aa.account_number,aa.label";

dol_syslog('htdocs/accountancy/customer/index.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	while ($row = $db->fetch_row($resql)) {
		// TODO When INVOICE_USE_SITUATION = 1, values here are wrong. There is no compensation on bad stored amounts
		//$situation_ratio = 1;
		//if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
		//}

		print '<tr class="oddeven">';
		print '<td>';
		if ($row[0] == 'tobind') {
			//print '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>';
		} else {
			print length_accountg($row[0]).' - ';
		}
		//print '</td>';
		//print '<td>';
		if ($row[0] == 'tobind') {
			$startmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
			if ($startmonth > 12) {
				$startmonth -= 12;
			}
			$startyear = ($startmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
			$endmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + 11;
			if ($endmonth > 12) {
				$endmonth -= 12;
			}
			$endyear = ($endmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
			print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT.'/accountancy/customer/list.php?search_date_startday=1&search_date_startmonth='.((int) $startmonth).'&search_date_startyear='.((int) $startyear).'&search_date_endday=&search_date_endmonth='.((int) $endmonth).'&search_date_endyear='.((int) $endyear), $langs->transnoentitiesnoconv("ToBind"));
		} else {
			print $row[1];
		}
		print '</td>';
		for ($i = 2; $i <= 13; $i++) {
			$cursormonth = (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + $i - 2);
			if ($cursormonth > 12) {
				$cursormonth -= 12;
			}
			$cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
			$tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

			print '<td class="right nowraponall amount">';
			print price($row[$i]);
			// Add link to make binding
			if (!empty(price2num($row[$i]))) {
				print '<a href="'.$_SERVER['PHP_SELF'].'?action=validatehistory&year='.$y.'&validatemonth='.((int) $cursormonth).'&validateyear='.((int) $cursoryear).'&token='.newToken().'">';
				print img_picto($langs->trans("ValidateHistory").' ('.$langs->trans('Month'.str_pad((string) $cursormonth, 2, '0', STR_PAD_LEFT)).' '.$cursoryear.')', 'link', 'class="marginleft2"');
				print '</a>';
			}
			print '</td>';
		}
		print '<td class="right nowraponall amount"><b>'.price($row[14]).'</b></td>';
		print '</tr>';
	}
	$db->free($resql);

	if ($num == 0) {
		print '<tr class="oddeven"><td colspan="15">';
		print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
		print '</td></tr>';
	}
} else {
	print $db->lasterror(); // Show last sql error
}
print "</table>\n";
print '</div>';


print '<br>';


print_barre_liste(img_picto('', 'link', 'class="paddingright fa-color-unset"').$langs->trans("OverviewOfAmountOfLinesBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesBound"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="minwidth100">'.$langs->trans("Account").'</td>';
for ($i = 1; $i <= 12; $i++) {
	$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
	if ($j > 12) {
		$j -= 12;
	}
	$cursormonth = $j;
	if ($cursormonth > 12) {
		$cursormonth -= 12;
	}
	$cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
	$tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

	print '<td width="60" class="right">';
	if (!empty($tmp['mday'])) {
		$param = 'search_date_startday=1&search_date_startmonth='.$cursormonth.'&search_date_startyear='.$cursoryear;
		$param .= '&search_date_endday='.$tmp['mday'].'&search_date_endmonth='.$tmp['mon'].'&search_date_endyear='.$tmp['year'];
		print '<a href="'.DOL_URL_ROOT.'/accountancy/customer/lines.php?'.$param.'">';
	}
	print $langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT));
	if (!empty($tmp['mday'])) {
		print '</a>';
	}
	print '</td>';
}
print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

$sql = "SELECT ".$db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number')." AS codecomptable,";
$sql .= "  ".$db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label')." AS intitule,";
for ($i = 1; $i <= 12; $i++) {
	$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
	if ($j > 12) {
		$j -= 12;
	}
	$sql .= "  SUM(".$db->ifsql("MONTH(f.datef) = ".((int) $j), "fd.total_ht", "0").") AS month".str_pad((string) $j, 2, "0", STR_PAD_LEFT).",";
}
$sql .= "  SUM(fd.total_ht) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
$sql .= "  LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = fd.fk_facture";
$sql .= "  LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " WHERE f.datef >= '".$db->idate($search_date_start)."'";
$sql .= "  AND f.datef <= '".$db->idate($search_date_end)."'";
// Define begin binding date
if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND f.datef >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
}
$sql .= " AND f.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
$sql .= " AND f.fk_statut > 0";
$sql .= " AND fd.product_type <= 2";
if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.", ".Facture::TYPE_REPLACEMENT.", ".Facture::TYPE_CREDIT_NOTE.", ".Facture::TYPE_SITUATION.")";
} else {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.", ".Facture::TYPE_REPLACEMENT.", ".Facture::TYPE_CREDIT_NOTE.", ".Facture::TYPE_DEPOSIT.", ".Facture::TYPE_SITUATION.")";
}
$sql .= " AND aa.account_number IS NOT NULL";
$sql .= " GROUP BY fd.fk_code_ventilation,aa.account_number,aa.label";
$sql .= ' ORDER BY aa.account_number';

dol_syslog('htdocs/accountancy/customer/index.php');
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	while ($row = $db->fetch_row($resql)) {
		// TODO When INVOICE_USE_SITUATION = 1, values here are wrong. There is no compensation on bad stored amounts
		//$situation_ratio = 1;
		//if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
		//}

		print '<tr class="oddeven">';
		print '<td class="tdoverflowmax300"'.(empty($row[1]) ? '' : ' title="'.dol_escape_htmltag($row[1]).'"').'>';
		if ($row[0] == 'tobind') {
			//print $langs->trans("Unknown");
		} else {
			print length_accountg($row[0]).' - ';
		}
		if ($row[0] == 'tobind') {
			print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT.'/accountancy/customer/list.php?search_year='.((int) $y), $langs->transnoentitiesnoconv("ToBind"));
		} else {
			print $row[1];
		}
		print '</td>';

		for ($i = 2; $i <= 13; $i++) {
			$cursormonth = (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + $i - 2);
			if ($cursormonth > 12) {
				$cursormonth -= 12;
			}
			$cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
			$tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

			print '<td class="right nowraponall amount">';
			print price($row[$i]);
			print '</td>';
		}
		print '<td class="right nowraponall amount"><b>'.price($row[14]).'</b></td>';
		print '</tr>';
	}
	$db->free($resql);

	if ($num == 0) {
		print '<tr class="oddeven"><td colspan="15">';
		print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
		print '</td></tr>';
	}
} else {
	print $db->lasterror(); // Show last sql error
}
print "</table>\n";
print '</div>';


if (getDolGlobalString('SHOW_TOTAL_OF_PREVIOUS_LISTS_IN_LIN_PAGE')) { // This part of code looks strange. Why showing a report that should rely on result of this step ?
	print '<br>';
	print '<br>';

	print_barre_liste($langs->trans("OtherInfo"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
	//print load_fiche_titre($langs->trans("OtherInfo"), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td lass="left">'.$langs->trans("TotalVente").'</td>';
	for ($i = 1; $i <= 12; $i++) {
		$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
		if ($j > 12) {
			$j -= 12;
		}
		print '<td width="60" class="right">'.$langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT)).'</td>';
	}
	print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

	$sql = "SELECT '".$db->escape($langs->trans("TotalVente"))."' AS total,";
	for ($i = 1; $i <= 12; $i++) {
		$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
		if ($j > 12) {
			$j -= 12;
		}
		$sql .= "  SUM(".$db->ifsql("MONTH(f.datef) = ".((int) $j), "fd.total_ht", "0").") AS month".str_pad((string) $j, 2, "0", STR_PAD_LEFT).",";
	}
	$sql .= "  SUM(fd.total_ht) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= "  LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = fd.fk_facture";
	$sql .= " WHERE f.datef >= '".$db->idate($search_date_start)."'";
	$sql .= "  AND f.datef <= '".$db->idate($search_date_end)."'";
	// Define begin binding date
	if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
		$sql .= " AND f.datef >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
	}
	$sql .= " AND f.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
	$sql .= " AND f.fk_statut > 0";
	$sql .= " AND fd.product_type <= 2";
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.", ".Facture::TYPE_REPLACEMENT.", ".Facture::TYPE_CREDIT_NOTE.", ".Facture::TYPE_SITUATION.")";
	} else {
		$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.", ".Facture::TYPE_REPLACEMENT.", ".Facture::TYPE_CREDIT_NOTE.", ".Facture::TYPE_DEPOSIT.", ".Facture::TYPE_SITUATION.")";
	}

	dol_syslog('htdocs/accountancy/customer/index.php');
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		while ($row = $db->fetch_row($resql)) {
			print '<tr><td>'.$row[0].'</td>';
			for ($i = 1; $i <= 12; $i++) {
				print '<td class="right nowraponall amount">'.price($row[$i]).'</td>';
			}
			print '<td class="right nowraponall amount"><b>'.price($row[13]).'</b></td>';
			print '</tr>';
		}
		$db->free($resql);
	} else {
		print $db->lasterror(); // Show last sql error
	}
	print "</table>\n";
	print '</div>';

	if (isModEnabled('margin')) {
		print "<br>\n";
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td>'.$langs->trans("TotalMarge").'</td>';
		for ($i = 1; $i <= 12; $i++) {
			$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
			if ($j > 12) {
				$j -= 12;
			}
			print '<td width="60" class="right">'.$langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT)).'</td>';
		}
		print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
			// With old situation invoice setup
			$sql = "SELECT '".$db->escape($langs->trans("Vide"))."' AS marge,";
			for ($i = 1; $i <= 12; $i++) {
				$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
				if ($j > 12) {
					$j -= 12;
				}
				$sql .= " SUM(".$db->ifsql(
					"MONTH(f.datef) = ".((int) $j),
					" (".$db->ifsql(
						"fd.total_ht < 0",
						" (-1 * (abs(fd.total_ht) - (fd.buy_price_ht * fd.qty * (fd.situation_percent / 100))))",	// TODO This is bugged, we must use the percent for the invoice and fd.situation_percent is cumulated percent !
						"  (fd.total_ht - (fd.buy_price_ht * fd.qty * (fd.situation_percent / 100)))"
					).")",
					0
				).") AS month".str_pad((string) $j, 2, '0', STR_PAD_LEFT).",";
			}
			$sql .= "  SUM(".$db->ifsql(
				"fd.total_ht < 0",
				" (-1 * (abs(fd.total_ht) - (fd.buy_price_ht * fd.qty * (fd.situation_percent / 100))))",	// TODO This is bugged, we must use the percent for the invoice and fd.situation_percent is cumulated percent !
				"  (fd.total_ht - (fd.buy_price_ht * fd.qty * (fd.situation_percent / 100)))"
			).") as total";
		} else {
			$sql = "SELECT '".$db->escape($langs->trans("Vide"))."' AS marge,";
			for ($i = 1; $i <= 12; $i++) {
				$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
				if ($j > 12) {
					$j -= 12;
				}
				$sql .= " SUM(".$db->ifsql(
					"MONTH(f.datef) = ".((int) $j),
					" (".$db->ifsql(
						"fd.total_ht < 0",
						" (-1 * (abs(fd.total_ht) - (fd.buy_price_ht * fd.qty)))",
						"  (fd.total_ht - (fd.buy_price_ht * fd.qty))"
					).")",
					0
				).") AS month".str_pad((string) $j, 2, '0', STR_PAD_LEFT).",";
			}
			$sql .= "  SUM(".$db->ifsql(
				"fd.total_ht < 0",
				" (-1 * (abs(fd.total_ht) - (fd.buy_price_ht * fd.qty)))",
				"  (fd.total_ht - (fd.buy_price_ht * fd.qty))"
			).") as total";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
		$sql .= "  LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = fd.fk_facture";
		$sql .= " WHERE f.datef >= '".$db->idate($search_date_start)."'";
		$sql .= "  AND f.datef <= '".$db->idate($search_date_end)."'";
		// Define begin binding date
		if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
			$sql .= " AND f.datef >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
		}
		$sql .= " AND f.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
		$sql .= " AND f.fk_statut > 0";
		$sql .= " AND fd.product_type <= 2";
		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.", ".Facture::TYPE_REPLACEMENT.", ".Facture::TYPE_CREDIT_NOTE.", ".Facture::TYPE_SITUATION.")";
		} else {
			$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.", ".Facture::TYPE_REPLACEMENT.", ".Facture::TYPE_CREDIT_NOTE.", ".Facture::TYPE_DEPOSIT.", ".Facture::TYPE_SITUATION.")";
		}
		dol_syslog('htdocs/accountancy/customer/index.php');
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			while ($row = $db->fetch_row($resql)) {
				print '<tr><td>'.$row[0].'</td>';
				for ($i = 1; $i <= 12; $i++) {
					print '<td class="right nowraponall amount">'.price(price2num($row[$i])).'</td>';
				}
				print '<td class="right nowraponall amount"><b>'.price(price2num($row[13])).'</b></td>';
				print '</tr>';
			}
			$db->free($resql);
		} else {
			print $db->lasterror(); // Show last sql error
		}
		print "</table>\n";
		print '</div>';
	}
}

// End of page
llxFooter();
$db->close();

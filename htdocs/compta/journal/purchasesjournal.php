<?php
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2011-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012  Alexandre spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2013       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *   	\file       htdocs/compta/journal/purchasesjournal.php
 *		\ingroup    societe, fournisseur, facture
 *		\brief      Page with purchases journal
 */
global $mysoc;

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

// Load translation files required by the page
$langs->loadlangs(array('companies', 'other', 'bills', 'compta'));

$date_startmonth = GETPOST('date_startmonth');
$date_startday = GETPOST('date_startday');
$date_startyear = GETPOST('date_startyear');
$date_endmonth = GETPOST('date_endmonth');
$date_endday = GETPOST('date_endday');
$date_endyear = GETPOST('date_endyear');

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(['purchasejournallist']);

if (isModEnabled('comptabilite')) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (isModEnabled('accounting')) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}

/*
 * Actions
 */

// None


/*
 * View
 */

$morequery = '&date_startyear='.$date_startyear.'&date_startmonth='.$date_startmonth.'&date_startday='.$date_startday.'&date_endyear='.$date_endyear.'&date_endmonth='.$date_endmonth.'&date_endday='.$date_endday;

llxHeader('', $langs->trans("PurchasesJournal"), '', '', 0, 0, '', '', $morequery);

$form = new Form($db);

$year_current = (int) dol_print_date(dol_now('gmt'), "%Y", 'gmt');
//$pastmonth = strftime("%m", dol_now()) - 1;
$pastmonth = (int) dol_print_date(dol_now(), "%m") - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0) {
	$pastmonth = 12;
	$pastmonthyear--;
}

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
	$date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

$name = $langs->trans("PurchasesJournal");
$periodlink = '';
$exportlink = '';
$builddate = dol_now();
$description = $langs->trans("DescPurchasesJournal").'<br>';
if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$description .= $langs->trans("DepositsAreNotIncluded");
} else {
	$description .= $langs->trans("DepositsAreIncluded");
}
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink);

$p = explode(":", getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY'));
$idpays = $p[0];


$sql = "SELECT f.rowid, f.ref_supplier, f.type, f.datef, f.libelle as label,";
$sql .= " fd.total_ttc, fd.tva_tx, fd.total_ht, fd.tva as total_tva, fd.product_type, fd.localtax1_tx, fd.localtax2_tx, fd.total_localtax1, fd.total_localtax2,";
$sql .= " s.rowid as socid, s.nom as name, s.code_compta_fournisseur,";
$sql .= " p.rowid as pid, p.ref as ref, p.accountancy_code_buy,";
$sql .= " ct.accountancy_code_buy as account_tva, ct.recuperableonly";
$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as fd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva as ct ON fd.tva_tx = ct.taux AND fd.info_bits = ct.recuperableonly AND ct.fk_pays = ".((int) $idpays);
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = fd.fk_product";
$sql .= " JOIN ".MAIN_DB_PREFIX."facture_fourn as f ON f.rowid = fd.fk_facture_fourn";
$sql .= " JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
$sql .= " WHERE f.fk_statut > 0 AND f.entity IN (".getEntity('invoice').")";
if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$sql .= " AND f.type IN (0,1,2)";
} else {
	$sql .= " AND f.type IN (0,1,2,3)";
}
if ($date_start && $date_end) {
	$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}

// TODO Find a better trick to avoid problem with some mysql installations
if (in_array($db->type, array('mysql', 'mysqli'))) {
	$db->query('SET SQL_BIG_SELECTS=1');
}

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	// les variables
	$cptfour = ((getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') != "") ? $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER : $langs->trans("CodeNotDef"));
	$cpttva = (getDolGlobalString('ACCOUNTING_VAT_BUY_ACCOUNT') ? $conf->global->ACCOUNTING_VAT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));

	$tabfac = array();
	$tabht = array();
	$tabtva = array();
	$tabttc = array();
	$tablocaltax1 = array();
	$tablocaltax2 = array();
	$tabcompany = array();

	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
		// contrôles
		$compta_soc = (($obj->code_compta_fournisseur != "") ? $obj->code_compta_fournisseur : $cptfour);
		$compta_prod = $obj->accountancy_code_buy;
		if (empty($compta_prod)) {
			if ($obj->product_type == 0) {
				$compta_prod = (getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT') ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
			} else {
				$compta_prod = (getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT') ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
			}
		}
		$compta_tva = (!empty($obj->account_tva) ? $obj->account_tva : $cpttva);
		$compta_localtax1 = (!empty($obj->account_localtax1) ? $obj->account_localtax1 : $langs->trans("CodeNotDef"));
		$compta_localtax2 = (!empty($obj->account_localtax2) ? $obj->account_localtax2 : $langs->trans("CodeNotDef"));

		$account_localtax1 = getLocalTaxesFromRate($obj->tva_tx, 1, $mysoc, $obj->thirdparty);
		$compta_localtax1 = (!empty($account_localtax1[2]) ? $account_localtax1[2] : $langs->trans("CodeNotDef"));
		$account_localtax2 = getLocalTaxesFromRate($obj->tva_tx, 2, $mysoc, $obj->thirdparty);
		$compta_localtax2 = (!empty($account_localtax2[2]) ? $account_localtax2[2] : $langs->trans("CodeNotDef"));

		$tabfac[$obj->rowid]["date"] = $obj->datef;
		$tabfac[$obj->rowid]["ref"] = $obj->ref_supplier;
		$tabfac[$obj->rowid]["type"] = $obj->type;
		$tabfac[$obj->rowid]["lib"] = $obj->label;
		$tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc;
		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht;
		if ($obj->recuperableonly != 1) {
			$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
		}
		$tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1;
		$tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2;
		$tabcompany[$obj->rowid] = array('id' => $obj->socid, 'name' => $obj->name);

		$i++;
	}
} else {
	dol_print_error($db);
}

/*
 * Show result array
 */
print '<table class="liste noborder centpercent">';
print "<tr class=\"liste_titre\">";
///print "<td>".$langs->trans("JournalNum")."</td>";
print "<td>".$langs->trans("Date")."</td>";
print "<td>".$langs->trans("Piece").' ('.$langs->trans("InvoiceRef").")</td>";
print "<td>".$langs->trans("Account")."</td>";
print "<td>".$langs->trans("Type")."</td>";
print "<td class='right'>".$langs->trans("AccountingDebit")."</td>";
print "<td class='right'>".$langs->trans("AccountingCredit")."</td>";
print "</tr>\n";


$invoicestatic = new FactureFournisseur($db);
$companystatic = new Fournisseur($db);

foreach ($tabfac as $key => $val) {
	$invoicestatic->id = $key;
	$invoicestatic->ref = $val["ref"];
	$invoicestatic->type = $val["type"];

	$companystatic->id = $tabcompany[$key]['id'];
	$companystatic->name = $tabcompany[$key]['name'];

	$lines = array(
		array(
			'var' => $tabht[$key],
			'label' => $langs->trans('Products'),
		),
		array(
			'var' => $tabtva[$key],
			'label' => $langs->trans('VAT')
		),
		array(
			'var' => $tablocaltax1[$key],
			'label' => $langs->transcountry('LT1', $mysoc->country_code)
		),
		array(
			'var' => $tablocaltax2[$key],
			'label' => $langs->transcountry('LT2', $mysoc->country_code)
		),
		array(
			'var' => $tabttc[$key],
			'label' => $langs->trans('ThirdParty').' ('.$companystatic->getNomUrl(0, 'supplier', 16).')',
			'nomtcheck' => true,
			'inv' => true
		)
	);

	foreach ($lines as $line) {
		foreach ($line['var'] as $k => $mt) {
			if (isset($line['nomtcheck']) || $mt) {
				print '<tr class="oddeven">';
				print "<td>".dol_print_date($db->jdate($val["date"]))."</td>";
				print "<td>".$invoicestatic->getNomUrl(1)."</td>";
				print "<td>".$k."</td>";
				print "<td>".$line['label']."</td>";

				if (isset($line['inv'])) {
					print '<td class="right">'.($mt < 0 ? price(-$mt) : '')."</td>";
					print '<td class="right">'.($mt >= 0 ? price($mt) : '')."</td>";
				} else {
					print '<td class="right">'.($mt >= 0 ? price($mt) : '')."</td>";
					print '<td class="right">'.($mt < 0 ? price(-$mt) : '')."</td>";
				}

				print "</tr>";
			}
		}
	}
}

print "</table>";

// End of page
llxFooter();
$db->close();

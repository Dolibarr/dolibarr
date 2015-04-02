<?php
/* Copyright (C) 2007-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger			<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2013		Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2015	Alexandre Spangaro		<alexandre.spangaro@gmail.com>
 * Copyright (C) 2013-2014	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 * \file		htdocs/accountancy/journal/sellsjournal.php
 * \ingroup		Accounting Expert
 * \brief		Page with sells journal
 */

require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");

$date_startmonth = GETPOST('date_startmonth');
$date_startday = GETPOST('date_startday');
$date_startyear = GETPOST('date_startyear');
$date_endmonth = GETPOST('date_endmonth');
$date_endday = GETPOST('date_endday');
$date_endyear = GETPOST('date_endyear');

// Security check
if ($user->societe_id > 0)
	accessforbidden();

$action = GETPOST('action');



/*
 * View
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

$sql = "SELECT f.rowid, f.facnumber, f.type, f.datef as df, f.ref_client,";
$sql .= " fd.rowid as fdid, fd.description, fd.product_type, fd.total_ht, fd.total_tva, fd.tva_tx, fd.total_ttc,";
$sql .= " s.rowid as socid, s.nom as name, s.code_compta, s.code_client,";
$sql .= " p.rowid as pid, p.ref as pref, p.accountancy_code_sell, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte, ";
$sql .= " ct.accountancy_code_sell as account_tva";
$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = fd.fk_product";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accountingaccount as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = fd.fk_facture";
$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_tva as ct ON fd.tva_tx = ct.taux AND ct.fk_pays = '" . $idpays . "'";
$sql .= " WHERE fd.fk_code_ventilation > 0 ";
if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND f.entity = " . $conf->entity;
}
$sql .= " AND f.fk_statut > 0";
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
	$sql .= " AND f.type IN (0,1,2,5)";
else
	$sql .= " AND f.type IN (0,1,2,3,5)";
$sql .= " AND fd.product_type IN (0,1)";
if ($date_start && $date_end)
	$sql .= " AND f.datef >= '" . $db->idate($date_start) . "' AND f.datef <= '" . $db->idate($date_end) . "'";
$sql .= " ORDER BY f.datef";

dol_syslog('accountancy/journal/sellsjournal.php:: $sql=' . $sql);
$result = $db->query($sql);
if ($result) {
	$tabfac = array ();
	$tabht = array ();
	$tabtva = array ();
	$tabttc = array ();
	$tabcompany = array ();

	$num = $db->num_rows($result);
	$i = 0;

	while ( $i < $num ) {
		$obj = $db->fetch_object($result);
		// les variables
		$cptcli = (! empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER)) ? $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER : $langs->trans("CodeNotDef");
		$compta_soc = (! empty($obj->code_compta)) ? $obj->code_compta : $cptcli;

		$compta_prod = $obj->compte;
		if (empty($compta_prod)) {
			if ($obj->product_type == 0)
				$compta_prod = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef");
			else
				$compta_prod = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef");
		}
		$cpttva = (! empty($conf->global->ACCOUNTING_VAT_ACCOUNT)) ? $conf->global->ACCOUNTING_VAT_ACCOUNT : $langs->trans("CodeNotDef");
		$compta_tva = (! empty($obj->account_tva) ? $obj->account_tva : $cpttva);

		// Situation invoices handling
		$line = new FactureLigne($db);
		$line->fetch($obj->id);
		$prev_progress = $line->get_prev_progress();
		if ($obj->situation_percent == 0) { // Avoid divide by 0
			$situation_ratio = 0;
		} else {
			$situation_ratio = ($obj->situation_percent - $prev_progress) / $obj->situation_percent;
		}

		// Invoice lines
		$tabfac[$obj->rowid]["date"] = $obj->df;
		$tabfac[$obj->rowid]["ref"] = $obj->facnumber;
		$tabfac[$obj->rowid]["type"] = $obj->type;
		$tabfac[$obj->rowid]["description"] = $obj->label_compte;
		$tabfac[$obj->rowid]["fk_facturedet"] = $obj->fdid;
		if (! isset($tabttc[$obj->rowid][$compta_soc]))
			$tabttc[$obj->rowid][$compta_soc] = 0;
		if (! isset($tabht[$obj->rowid][$compta_prod]))
			$tabht[$obj->rowid][$compta_prod] = 0;
		if (! isset($tabtva[$obj->rowid][$compta_tva]))
			$tabtva[$obj->rowid][$compta_tva] = 0;
		$tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc * $situation_ratio;
		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht * $situation_ratio;
		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva * $situation_ratio;
		$tabcompany[$obj->rowid] = array (
				'id' => $obj->socid,
				'name' => $obj->name,
				'code_client' => $obj->code_compta
		);

		$i ++;
	}
} else {
	dol_print_error($db);
}

/*
 * Action
 * FIXME Action must be set before any view part
 */

// Bookkeeping Write
if ($action == 'writebookkeeping')
{
	$now = dol_now();

	foreach ($tabfac as $key => $val)
	{
		foreach ($tabttc[$key] as $k => $mt)
		{
			$bookkeeping = new BookKeeping($db);
			$bookkeeping->doc_date = $val["date"];
			$bookkeeping->doc_ref = $val["ref"];
			$bookkeeping->date_create = $now;
			$bookkeeping->doc_type = 'customer_invoice';
			$bookkeeping->fk_doc = $key;
			$bookkeeping->fk_docdet = $val["fk_facturedet"];
			$bookkeeping->code_tiers = $tabcompany[$key]['code_client'];
			$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
			$bookkeeping->label_compte = $tabcompany[$key]['name'];
			$bookkeeping->montant = $mt;
			$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
			$bookkeeping->debit = ($mt >= 0) ? $mt : 0;
			$bookkeeping->credit = ($mt < 0) ? $mt : 0;
			$bookkeeping->code_journal = $conf->global->ACCOUNTING_SELL_JOURNAL;

			$bookkeeping->create();
		}

		// Product / Service
		foreach ($tabht[$key] as $k => $mt) {
			if ($mt) {
				// get compte id and label
				$accountingaccount = new AccountingAccount($db);
				if ($accountingaccount->fetch(null, $k)) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->doc_ref = $val["ref"];
					$bookkeeping->date_create = $now;
					$bookkeeping->doc_type = 'customer_invoice';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = $val["fk_facturedet"];
					$bookkeeping->code_tiers = '';
					$bookkeeping->numero_compte = $k;
					$bookkeeping->label_compte = $accountingaccount->label;
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
					$bookkeeping->debit = ($mt < 0) ? $mt : 0;
					$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
					$bookkeeping->code_journal = $conf->global->ACCOUNTING_SELL_JOURNAL;

					$bookkeeping->create();
				}
			}
		}

		// VAT
		// var_dump($tabtva);
		foreach ($tabtva[$key] as $k => $mt)
		{
			if ($mt)
			{
				$bookkeeping = new BookKeeping($db);
				$bookkeeping->doc_date = $val["date"];
				$bookkeeping->doc_ref = $val["ref"];
				$bookkeeping->date_create = $now;
				$bookkeeping->doc_type = 'customer_invoice';
				$bookkeeping->fk_doc = $key;
				$bookkeeping->fk_docdet = $val["fk_facturedet"];
				$bookkeeping->code_tiers = '';
				$bookkeeping->numero_compte = $k;
				$bookkeeping->label_compte = $langs->trans("VAT");
				$bookkeeping->montant = $mt;
				$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
				$bookkeeping->debit = ($mt < 0) ? $mt : 0;
				$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
				$bookkeeping->code_journal = $conf->global->ACCOUNTING_SELL_JOURNAL;

				$bookkeeping->create();
			}
		}
	}
}

// Export
if ($action == 'export_csv')
{
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
	$sell_journal = $conf->global->ACCOUNTING_SELL_JOURNAL;

	header('Content-Type: text/csv');
	if ($conf->global->EXPORT_PREFIX_SPEC)
		$filename=$conf->global->EXPORT_PREFIX_SPEC."_"."journal_ventes.csv";
	else
		$filename="journal_ventes.csv";
	header('Content-Disposition: attachment;filename='.$filename);

	$companystatic = new Client($db);

	if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 2) 	// Model Cegid Expert Export
	{
		$sep = ";";

		foreach ( $tabfac as $key => $val ) {
			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];
			$companystatic->client = $tabcompany[$key]['code_client'];

			$date = dol_print_date($db->jdate($val["date"]), '%d%m%Y');

			foreach ( $tabttc[$key] as $k => $mt ) {
				print $date . $sep;
				print $sell_journal . $sep;
				print length_accountg($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) . $sep;
				print length_accounta(html_entity_decode($k)) . $sep;
				print ($mt < 0 ? 'C' : 'D') . $sep;
				print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
				print utf8_decode($companystatic->name) . $sep;
				print $val["ref"];
				print "\n";
			}

			// Product / Service
			foreach ( $tabht[$key] as $k => $mt ) {
				if ($mt) {
					print $date . $sep;
					print $sell_journal . $sep;
					print length_accountg(html_entity_decode($k)) . $sep;
					print $sep;
					print ($mt < 0 ? 'D' : 'C') . $sep;
					print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
					print dol_trunc($val["description"], 32) . $sep;
					print $val["ref"];
					print "\n";
				}
			}

			// TVA
			foreach ( $tabtva[$key] as $k => $mt ) {
				if ($mt) {
					print $date . $sep;
					print $sell_journal . $sep;
					print length_accountg(html_entity_decode($k)) . $sep;
					print $sep;
					print ($mt < 0 ? 'D' : 'C') . $sep;
					print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
					print $langs->trans("VAT") . $sep;
					print $val["ref"];
					print "\n";
				}
			}
		}
	}
	else 	// Model Classic Export
	{
		foreach ($tabfac as $key => $val)
		{
			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];
			$companystatic->client = $tabcompany[$key]['code_client'];

			$date = dol_print_date($db->jdate($val["date"]), 'day');

			foreach ( $tabttc[$key] as $k => $mt ) {
				print '"' . $date . '"' . $sep;
				print '"' . $val["ref"] . '"' . $sep;
				print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
				print '"' . utf8_decode($companystatic->name) . '"' . $sep;
				print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
				print '"' . ($mt < 0 ? price(- $mt) : '') . '"';
				print "\n";
			}

			// Product / Service
			foreach ($tabht[$key] as $k => $mt)
			{
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch(null, $k);

				if ($mt) {
					print '"' . $date . '"' . $sep;
					print '"' . $val["ref"] . '"' . $sep;
					print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
					print '"' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"';
					print "\n";
				}
			}

			// VAT
			foreach ($tabtva[$key] as $k => $mt)
			{
				if ($mt) {
					print '"' . $date . '"' . $sep;
					print '"' . $val["ref"] . '"' . $sep;
					print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
					print '"' . $langs->trans("VAT") . '"' . $sep;
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"';
					print "\n";
				}
			}
		}
	}
} else {

	$form = new Form($db);

	llxHeader('', $langs->trans("SellsJournal"));

	$nom = $langs->trans("SellsJournal");
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate = time();
	$description = $langs->trans("DescSellsJournal") . '<br>';
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
		$description .= $langs->trans("DepositsAreNotIncluded");
	else
		$description .= $langs->trans("DepositsAreIncluded");
	$period = $form->select_date($date_start, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end, 'date_end', 0, 0, 0, '', 1, 0, 1);
	report_header($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''));

	print '<input type="button" class="button" style="float: right;" value="' . $langs->trans("Export") . '" onclick="launch_export();" />';

	print '<input type="button" class="button" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';

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
	print '<br><br>';

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print "<td>" . $langs->trans("Date") . "</td>";
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("InvoiceRef") . ")</td>";
	print "<td>" . $langs->trans("Account") . "</td>";
	print "<td>" . $langs->trans("Type") . "</td>";
	print "<td align='right'>" . $langs->trans("Debit") . "</td>";
	print "<td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$var = true;
	$r = '';

	$invoicestatic = new Facture($db);
	$companystatic = new Client($db);

	foreach ($tabfac as $key => $val)
	{
		$invoicestatic->id = $key;
		$invoicestatic->ref = $val["ref"];
		$invoicestatic->type = $val["type"];

		$date = dol_print_date($db->jdate($val["date"]), 'day');

		// Third party
		foreach ($tabttc[$key] as $k => $mt)
		{
			print "<tr " . $bc[$var] . ">";
			print "<td>" . $date . "</td>";
			print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];
			$companystatic->client = $tabcompany[$key]['code_client'];
			print "<td>" . length_accounta($k);
			print "</td><td>" . $langs->trans("ThirdParty");
			print ' (' . $companystatic->getNomUrl(0, 'customer', 16) . ')';
			print "</td><td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
			print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
		}
		print "</tr>";

		// Product / Service
		foreach ($tabht[$key] as $k => $mt)
		{
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k);

			if ($mt) {
				print "<tr " . $bc[$var] . ">";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				print "<td>" . length_accountg($k) . "</td>";
				print "<td>" . $accountingaccount->label . "</td>";
				print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
				print "</tr>";
			}
		}

		// VAT
		foreach ($tabtva[$key] as $k => $mt)
		{
			if ($mt) {
				print "<tr " . $bc[$var] . ">";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				print "<td>" . length_accountg($k) . "</td>";
				print "<td>" . $langs->trans("VAT") . "</td>";
				print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
				print "</tr>";
			}
		}

		$var = ! $var;
	}

	print "</table>";

	// End of page
	llxFooter();
}

$db->close();

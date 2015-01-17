<?php
/* Copyright (C) 2007-2010	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger		<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2013		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2014  Alexandre Spangaro	<alexandre.spangaro@gmail.com>
 * Copyright (C) 2013-2014  Florian Henry	    <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014  Olivier Geffroy     <jeff@jeffinfo.com>
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
 * \file		htdocs/accountancy/journal/cashjournal.php
 * \ingroup		Accounting Expert
 * \brief		Page with cash journal
 */

require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';

// Langs
$langs->load("companies");
$langs->load("other");
$langs->load("compta");
$langs->load("bank");
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

$sql = "SELECT b.rowid , b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, soc.code_compta, ba.courant,";
$sql .= " soc.code_compta_fournisseur, soc.rowid as socid, soc.nom as name, ba.account_number, bu1.type as typeop";
$sql .= " FROM " . MAIN_DB_PREFIX . "bank b";
$sql .= " JOIN " . MAIN_DB_PREFIX . "bank_account ba on b.fk_account=ba.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url bu1 ON bu1.fk_bank = b.rowid AND bu1.type='company'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe soc on bu1.url_id=soc.rowid";

// Code opération type caisse
$sql .= " WHERE ba.courant = 2";
if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND ba.entity = " . $conf->entity;
}

if ($date_start && $date_end)
	$sql .= " AND b.dateo >= '" . $db->idate($date_start) . "' AND b.dateo <= '" . $db->idate($date_end) . "'";
$sql .= " ORDER BY b.datev";

$object = new Account($db);
$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);
$societestatic = new Societe($db);
$chargestatic = new ChargeSociales($db);
$paymentvatstatic = new TVA($db);

dol_syslog("accountancy/journal/cashjournal.php:: sql=" . $sql, LOG_DEBUG);
$result = $db->query($sql);
if ($result) {

	$num = $db->num_rows($result);
	// les variables
	$cptfour = (! empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) ? $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER : $langs->trans("CodeNotDef"));
	$cptcli = (! empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) ? $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER : $langs->trans("CodeNotDef"));
	$cpttva = (! empty($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) ? $conf->global->ACCOUNTING_ACCOUNT_SUSPENSE : $langs->trans("CodeNotDef"));
	$cptsociale = (! empty($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) ? $conf->global->ACCOUNTING_ACCOUNT_SUSPENSE : $langs->trans("CodeNotDef"));

	$tabpay = array ();
	$tabbq = array ();
	$tabtp = array ();
	$tabcompany = array ();
	$tabtype = array ();

	$i = 0;
	while ($i < $num)
	{
		$obj = $db->fetch_object($result);

		// controls
		$compta_bank = $obj->account_number;
		if ($obj->label == '(SupplierInvoicePayment)')
			$compta_soc = (! empty($obj->code_compta_fournisseur) ? $obj->code_compta_fournisseur : $cptfour);
		if ($obj->label == '(CustomerInvoicePayment)')
			$compta_soc = (! empty($obj->code_compta) ? $obj->code_compta : $cptcli);
		if ($obj->typeop == '(BankTransfert)')
			$compta_soc = $conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH;

			// variable bookkeeping

		$tabpay[$obj->rowid]["date"] = $obj->do;
		$tabpay[$obj->rowid]["ref"] = $obj->label;
		$tabpay[$obj->rowid]["fk_bank"] = $obj->rowid;
		if (preg_match('/^\((.*)\)$/i', $obj->label, $reg)) {
			$tabpay[$obj->rowid]["lib"] = $langs->trans($reg[1]);
		} else {
			$tabpay[$obj->rowid]["lib"] = dol_trunc($obj->label, 60);
		}
		$links = $object->get_url($obj->rowid);

		// get_url may return -1 which is not traversable
		if (is_array($links))
		{
			foreach ( $links as $key => $val )
			{
				$tabtype[$obj->rowid] = $links[$key]['type'];

				if ($links[$key]['type'] == 'payment') {
					$paymentstatic->id = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentstatic->getNomUrl(2);
				} else if ($links[$key]['type'] == 'payment_supplier') {
					$paymentsupplierstatic->id = $links[$key]['url_id'];
					$paymentsupplierstatic->ref = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentsupplierstatic->getNomUrl(2);
				} else if ($links[$key]['type'] == 'company') {

					$societestatic->id = $links[$key]['url_id'];
					$societestatic->name = $links[$key]['label'];
					$tabpay[$obj->rowid]["soclib"] = $societestatic->getNomUrl(1, '', 30);
					$tabtp[$obj->rowid][$compta_soc] += $obj->amount;
				} else if ($links[$key]['type'] == 'sc') {

					$chargestatic->id = $links[$key]['url_id'];
					$chargestatic->ref = $links[$key]['url_id'];

					$tabpay[$obj->rowid]["lib"] .= ' ' . $chargestatic->getNomUrl(2);
					if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
						if ($reg[1] == 'socialcontribution')
							$reg[1] = 'SocialContribution';
						$chargestatic->lib = $langs->trans($reg[1]);
					} else {
						$chargestatic->lib = $links[$key]['label'];
					}
					$chargestatic->ref = $chargestatic->lib;
					$tabpay[$obj->rowid]["soclib"] = $chargestatic->getNomUrl(1, 30);

					$sqlmid = 'SELECT cchgsoc.accountancy_code';
					$sqlmid .= " FROM " . MAIN_DB_PREFIX . "c_chargesociales cchgsoc ";
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "chargesociales as chgsoc ON  chgsoc.fk_type=cchgsoc.id";
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementcharge as paycharg ON  paycharg.fk_charge=chgsoc.rowid";
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "bank_url as bkurl ON  bkurl.url_id=paycharg.rowid";
					$sqlmid .= " WHERE bkurl.fk_bank=" . $obj->rowid;
					dol_syslog("accountancy/journal/cashjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
					$resultmid = $db->query($sqlmid);
					if ($resultmid) {
						$objmid = $db->fetch_object($resultmid);
						$tabtp[$obj->rowid][$objmid->accountancy_code] += $obj->amount;
					}
					/*else {
						$tabtp [$obj->rowid] [$cptsociale] += $obj->amount;
					}*/
				}
			}
		}

		$tabbq[$obj->rowid][$compta_bank] += $obj->amount;

		// if($obj->socid)$tabtp[$obj->rowid][$compta_soc] += $obj->amount;

		$i ++;
	}
} else {
	dol_print_error($db);
}

/*
 * Actions
*/

// write bookkeeping
if ($action == 'writeBookKeeping')
{
	$error = 0;
	foreach ( $tabpay as $key => $val )
	{
		// cash
		foreach ( $tabbq[$key] as $k => $mt )
		{
			$bookkeeping = new BookKeeping($db);
			$bookkeeping->doc_date = $val["date"];
			$bookkeeping->doc_ref = $val["ref"];
			$bookkeeping->doc_type = 'cash';
			$bookkeeping->fk_doc = $key;
			$bookkeeping->fk_docdet = $val["fk_bank"];
			$bookkeeping->code_tiers = $tabcompany[$key]['code_client'];
			$bookkeeping->numero_compte = $k;
			$bookkeeping->label_compte = $compte->label;
			$bookkeeping->montant = ($mt < 0 ? - $mt : $mt);
			$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
			$bookkeeping->debit = ($mt >= 0 ? $mt : 0);
			$bookkeeping->credit = ($mt < 0 ? - $mt : 0);
			$bookkeeping->code_journal = $conf->global->ACCOUNTING_CASH_JOURNAL;

			if ($tabtype[$key] == 'payment')
			{
				$sqlmid = 'SELECT fac.facnumber';
				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture fac ";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement_facture as payfac ON  payfac.fk_facture=fac.rowid";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement as pay ON  payfac.fk_paiement=pay.rowid";
				$sqlmid .= " WHERE pay.fk_bank=" . $key;
				dol_syslog("accountancy/journal/cashjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
				$resultmid = $db->query($sqlmid);
				if ($resultmid) {
					$objmid = $db->fetch_object($resultmid);
					$bookkeeping->doc_ref = $objmid->facnumber;
				}
			} else if ($tabtype[$key] == 'payment_supplier') {

				$sqlmid = 'SELECT facf.facnumber';
				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture_fourn facf ";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as payfacf ON  payfacf.fk_facturefourn=facf.rowid";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn as payf ON  payfacf.fk_paiementfourn=payf.rowid";
				$sqlmid .= " WHERE payf.fk_bank=" . $key;
				dol_syslog("accountancy/journal/cashjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
				$resultmid = $db->query($sqlmid);
				if ($resultmid) {
					$objmid = $db->fetch_object($resultmid);
					$bookkeeping->doc_ref = $objmid->facnumber;
				}
			}

			$result = $bookkeeping->create();
			if ($result < 0) {
				$error ++;
				setEventMessage($object->errors, 'errors');
			}
		}
		// third party
		foreach ( $tabtp[$key] as $k => $mt ) {

			$bookkeeping = new BookKeeping($db);
			$bookkeeping->doc_date = $val["date"];
			$bookkeeping->doc_ref = $val["ref"];
			$bookkeeping->doc_type = 'cash';
			$bookkeeping->fk_doc = $key;
			$bookkeeping->fk_docdet = $val["fk_bank"];
			$bookkeeping->label_compte = $tabcompany[$key]['name'];
			$bookkeeping->montant = ($mt < 0 ? - $mt : $mt);
			$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
			$bookkeeping->debit = ($mt < 0 ? - $mt : 0);
			$bookkeeping->credit = ($mt >= 0 ? $mt : 0);
			$bookkeeping->code_journal = $conf->global->ACCOUNTING_CASH_JOURNAL;

			if ($tabtype[$key] == 'sc') {
				$bookkeeping->code_tiers = '';
				$bookkeeping->numero_compte = $k;
			} else if ($tabtype[$key] == 'payment') {

				$sqlmid = 'SELECT fac.facnumber';
				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture fac ";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement_facture as payfac ON  payfac.fk_facture=fac.rowid";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement as pay ON  payfac.fk_paiement=pay.rowid";
				$sqlmid .= " WHERE pay.fk_bank=" . $key;
				dol_syslog("accountancy/journal/cashjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
				$resultmid = $db->query($sqlmid);
				if ($resultmid) {
					$objmid = $db->fetch_object($resultmid);
					$bookkeeping->doc_ref = $objmid->facnumber;
				}
				$bookkeeping->code_tiers = $k;
				$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
			} else if ($tabtype[$key] == 'payment_supplier') {

				$sqlmid = 'SELECT facf.facnumber';
				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture_fourn facf ";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as payfacf ON  payfacf.fk_facturefourn=facf.rowid";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn as payf ON  payfacf.fk_paiementfourn=payf.rowid";
				$sqlmid .= " WHERE payf.fk_bank=" . $key;
				dol_syslog("accountancy/journal/cashjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
				$resultmid = $db->query($sqlmid);
				if ($resultmid) {
					$objmid = $db->fetch_object($resultmid);
					$bookkeeping->doc_ref = $objmid->facnumber;
				}
				$bookkeeping->code_tiers = $k;
				$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
			} else if ($tabtype[$key] == 'company') {

				$sqlmid = 'SELECT fac.facnumber';
				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture fac ";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement_facture as payfac ON  payfac.fk_facture=fac.rowid";
				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement as pay ON  payfac.fk_paiement=pay.rowid";
				$sqlmid .= " WHERE pay.fk_bank=" . $key;
				dol_syslog("accountancy/journal/cashjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
				$resultmid = $db->query($sqlmid);
				if ($resultmid) {
					$objmid = $db->fetch_object($resultmid);
					$bookkeeping->doc_ref = $objmid->facnumber;
				}
				$bookkeeping->code_tiers = $k;
				$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
			} else {

				$bookkeeping->doc_ref = $k;
				$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
			}

			$result = $bookkeeping->create();
			if ($result < 0) {
				$error ++;
				setEventMessage($object->errors, 'errors');
			}
		}
	}

	if (empty($error)) {
		setEventMessage($langs->trans('Success'), 'mesgs');
	}
}
// export csv
if ($action == 'export_csv') {
	$sep = $conf->global->ACCOUNTING_SEPARATORCSV;

	header('Content-Type: text/csv');
	header('Content-Disposition:attachment;filename=journal_caisse.csv');

	if ($conf->global->ACCOUNTING_MODELCSV == 1) 	// Modèle Export Cegid Expert
	{
		foreach ( $tabpay as $key => $val ) {
			$date = dol_print_date($db->jdate($val["date"]), '%d%m%Y');

			// Cash
			print $date . $sep;
			print $conf->global->ACCOUNTING_CASH_JOURNAL . $sep;

			foreach ( $tabbq[$key] as $k => $mt ) {
				print length_accountg(html_entity_decode($k)) . $sep;
				print $sep;
				print ($mt < 0 ? 'C' : 'D') . $sep;
				print price($mt) . $sep;
			}
			print utf8_decode($langs->trans("CashPayment")) . $sep;
			print $val["ref"] . $sep;
			print "\n";

			// Third party
			foreach ( $tabtp[$key] as $k => $mt ) {
				if ($mt) {
					print $date . $sep;
					print $conf->global->ACCOUNTING_CASH_JOURNAL . $sep;
					if ($obj->label == '(SupplierInvoicePayment)') {
						print length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) . $sep;
					} else {
						print length_accountg($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) . $sep;
					}
					print length_accounta(html_entity_decode($k)) . $sep;
					print ($mt < 0 ? 'D' : 'C') . $sep;
					print price($mt) . $sep;
					print $langs->trans("ThirdParty") . $sep;
					print $val["ref"] . $sep;
					print "\n";
				}
			}
		}
	} else 	// Modèle Export Classique
	{
		foreach ( $tabpay as $key => $val ) {
			$date = dol_print_date($db->jdate($val["date"]), 'day');
			print '"' . $date . '"' . $sep;
			print '"' . $val["ref"] . '"' . $sep;

			// Cash
			foreach ( $tabbq[$key] as $k => $mt ) {
				print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
				print '"' . $langs->trans("Cash") . '"' . $sep;
				print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
				print '"' . ($mt < 0 ? price(- $mt) : '') . '"';
			}
			print "\n";

			// Third party
			foreach ( $tabtp[$key] as $k => $mt ) {
				if ($mt) {
					print '"' . $date . '"' . $sep;
					print '"' . $val["ref"] . '"' . $sep;
					print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
					print '"' . $langs->trans("ThirdParty") . '"' . $sep;
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"';
					print "\n";
				}
			}
		}
	}
} else {

	$form = new Form($db);

	llxHeader('', $langs->trans("CashJournal"), '');

	$name = $langs->trans("CashJournal");
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate = time();
	$description = $langs->trans("DescCashJournal") . '<br>';
	$period = $form->select_date($date_start, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end, 'date_end', 0, 0, 0, '', 1, 0, 1);
	report_header($name, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''));

	print '<input type="button" class="button" style="float: right;" value="Export CSV" onclick="launch_export();" />';

	print '<input type="button" class="button" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writeBookKeeping();" />';

	print '
	<script type="text/javascript">
		function launch_export() {
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("export_csv");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
		function writeBookKeeping() {
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("writeBookKeeping");
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
	print "<td align='right'>" . $langs->trans("Debit") . "</td><td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$var = true;
	$r = '';

	foreach ( $tabpay as $key => $val ) {
		$date = dol_print_date($db->jdate($val["date"]), 'day');

		// Cash
		foreach ( $tabbq[$key] as $k => $mt ) {
			if (1) {
				print "<tr " . $bc[$var] . " >";
				print "<td>" . $date . "</td>";
				print "<td>" . $val["lib"] . "</td>";
				print "<td>" . length_accountg($k) . "</td>";
				print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print '<td align="right">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "</tr>";
			}
		}

		// third party
		foreach ( $tabtp[$key] as $k => $mt ) {
			if ($k != 'type') {
				print "<tr " . $bc[$var] . ">";

				print "<td>" . $date . "</td>";
				print "<td>" . $val["soclib"] . "</td>";

				print "<td>" . length_accounta($k) . "</td>";
				print '<td align="right">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print '<td align="right">' . ($mt >= 0 ? price($mt) : '') . "</td>";
			}
		}

		$var = ! $var;
	}

	print "</table>";

	// End of page
	llxFooter();
}
$db->close();

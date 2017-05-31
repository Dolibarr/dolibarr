<?php
/* Copyright (C) 2007-2010	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger		<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2013-2015  Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2013-2016  Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016  Florian Henry	    <florian.henry@open-concept.pro>
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
 * \file htdocs/accountancy/journal/purchasesjournal.php
 * \ingroup Advanced accountancy
 * \brief Page with purchases journal
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';

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

$sql = "SELECT f.rowid, f.ref, f.type, f.datef as df, f.libelle,f.ref_supplier,";
$sql .= " fd.rowid as fdid, fd.description, fd.total_ttc, fd.tva_tx, fd.total_ht, fd.tva as total_tva, fd.product_type,";
$sql .= " s.rowid as socid, s.nom as name, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,";
$sql .= " p.accountancy_code_buy , ct.accountancy_code_buy as account_tva, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as fd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_tva as ct ON fd.tva_tx = ct.taux AND ct.fk_pays = '" . $idpays . "'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = fd.fk_product";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " JOIN " . MAIN_DB_PREFIX . "facture_fourn as f ON f.rowid = fd.fk_facture_fourn";
$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
$sql .= " WHERE f.fk_statut > 0 ";
$sql .= " AND fd.fk_code_ventilation > 0 ";
$sql .= " AND f.entity IN (" . getEntity("facture_fourn", 0) . ")";  // We don't share object for accountancy
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
	$sql .= " AND f.type IN (0,1,2)";
else
	$sql .= " AND f.type IN (0,1,2,3)";
if ($date_start && $date_end)
	$sql .= " AND f.datef >= '" . $db->idate($date_start) . "' AND f.datef <= '" . $db->idate($date_end) . "'";
$sql .= " ORDER BY f.datef";

dol_syslog('accountancy/journal/purchasesjournal.php:: $sql=' . $sql);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	// les variables
	$cptfour = (! empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER)) ? $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER : $langs->trans("CodeNotDef");
	$cpttva = (! empty($conf->global->ACCOUNTING_VAT_BUY_ACCOUNT)) ? $conf->global->ACCOUNTING_VAT_BUY_ACCOUNT : $langs->trans("CodeNotDef");

	$tabfac = array ();
	$tabht = array ();
	$tabtva = array ();
	$def_tva = array ();
	$tabttc = array ();
	$tabcompany = array ();

	$i = 0;
	while ( $i < $num ) {
		$obj = $db->fetch_object($result);

		// contrôles
		$compta_soc = (! empty($obj->code_compta_fournisseur)) ? $obj->code_compta_fournisseur : $cptfour;
		
		$compta_prod = $obj->compte;
		if (empty($compta_prod)) {
			if ($obj->product_type == 0)
				$compta_prod = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT)) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef");
			else
				$compta_prod = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT)) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef");
		}
		$compta_tva = (! empty($obj->account_tva) ? $obj->account_tva : $cpttva);

		//Define array for display vat tx
		$def_tva[$obj->rowid]=price($obj->tva_tx);

		$tabfac[$obj->rowid]["date"] = $db->jdate($obj->df);
		$tabfac[$obj->rowid]["ref"] = $obj->ref_supplier . ' (' . $obj->ref . ')';
		$tabfac[$obj->rowid]["refsologest"] = $obj->ref;
		$tabfac[$obj->rowid]["refsuppliersologest"] = $obj->ref_supplier;

		$tabfac[$obj->rowid]["type"] = $obj->type;
		$tabfac[$obj->rowid]["description"] = $obj->description;
		//$tabfac[$obj->rowid]["fk_facturefourndet"] = $obj->fdid;
		
        // Avoid warnings
        if (! isset($tabttc[$obj->rowid][$compta_soc])) $tabttc[$obj->rowid][$compta_soc] = 0;
        if (! isset($tabht[$obj->rowid][$compta_prod])) $tabht[$obj->rowid][$compta_prod] = 0;
        if (! isset($tabtva[$obj->rowid][$compta_tva])) $tabtva[$obj->rowid][$compta_tva] = 0;

		$tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc;
		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht;
		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
		$tabcompany[$obj->rowid] = array (
				'id' => $obj->socid,
				'name' => $obj->name,
				'code_fournisseur' => $obj->code_fournisseur,
				'code_compta_fournisseur' => $compta_soc
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

	foreach ($tabfac as $key => $val)  // Loop on each invoice
	{
	    $errorforline = 0;
	     
	    $db->begin();
	     
		$companystatic = new Societe($db);
		$invoicestatic = new FactureFournisseur($db);

		$invoicestatic->id = $key;
		$invoicestatic->ref = (string) $val["refsologest"];
		$invoicestatic->refsupplier = $val["refsuppliersologest"];
		$invoicestatic->type = $val["type"];
		$invoicestatic->description = html_entity_decode(dol_trunc($val["description"], 32));

		$companystatic->id = $tabcompany[$key]['id'];
		$companystatic->name = $tabcompany[$key]['name'];
		$companystatic->code_compta = $tabcompany[$key]['code_compta'];
		$companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
		$companystatic->code_client = $tabcompany[$key]['code_client'];
		$companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
		$companystatic->client = $tabcompany[$key]['code_client'];

        if (! $errorforline)
        {
    		foreach ( $tabttc[$key] as $k => $mt ) {
    			// get compte id and label
    		    if ($mt) {
        			$bookkeeping = new BookKeeping($db);
        			$bookkeeping->doc_date = $val["date"];
        			$bookkeeping->doc_ref = $val["ref"];
        			$bookkeeping->date_create = $now;
        			$bookkeeping->doc_type = 'supplier_invoice';
        			$bookkeeping->fk_doc = $key;
        			$bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add
        			$bookkeeping->code_tiers = $tabcompany[$key]['code_fournisseur'];
        			$bookkeeping->label_compte = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->refsupplier . ' - ' . $langs->trans("Code_tiers");
        			$bookkeeping->numero_compte = $tabcompany[$key]['code_compta_fournisseur'];
        			$bookkeeping->montant = $mt;
        			$bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
        			$bookkeeping->debit = ($mt <= 0) ? $mt : 0;
        			$bookkeeping->credit = ($mt > 0) ? $mt : 0;
        			$bookkeeping->code_journal = $conf->global->ACCOUNTING_PURCHASE_JOURNAL;
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
        
		// Product / Service
        if (! $errorforline)
        {
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
    					$bookkeeping->doc_type = 'supplier_invoice';
    					$bookkeeping->fk_doc = $key;
    					$bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add
    					$bookkeeping->code_tiers = '';
    					$bookkeeping->label_compte = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->refsupplier . ' - ' . $accountingaccount->label;
    					$bookkeeping->numero_compte = $k;
    					$bookkeeping->montant = $mt;
    					$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
    					$bookkeeping->debit = ($mt > 0) ? $mt : 0;
    					$bookkeeping->credit = ($mt <= 0) ? $mt : 0;
    					$bookkeeping->code_journal = $conf->global->ACCOUNTING_PURCHASE_JOURNAL;
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
        
		// VAT
		// var_dump($tabtva);
        if (! $errorforline)
        {
            foreach ( $tabtva[$key] as $k => $mt ) {
    			if ($mt) {
    				// get compte id and label
    				$bookkeeping = new BookKeeping($db);
    				$bookkeeping->doc_date = $val["date"];
    				$bookkeeping->doc_ref = $val["ref"];
    				$bookkeeping->date_create = $now;
    				$bookkeeping->doc_type = 'supplier_invoice';
    				$bookkeeping->fk_doc = $key;
    				$bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add
    				$bookkeeping->code_tiers = '';
    				$bookkeeping->label_compte = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->refsupplier . ' - ' . $langs->trans("VAT"). ' '.$def_tva[$key];
    				$bookkeeping->numero_compte = $k;
    				$bookkeeping->montant = $mt;
    				$bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
    				$bookkeeping->debit = ($mt > 0) ? $mt : 0;
    				$bookkeeping->credit = ($mt <= 0) ? $mt : 0;
    				$bookkeeping->code_journal = $conf->global->ACCOUNTING_PURCHASE_JOURNAL;
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

$companystatic = new Fournisseur($db);

// Export
if ($action == 'export_csv') {
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
	$journal = $conf->global->ACCOUNTING_PURCHASE_JOURNAL;

	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	// Model Cegid Expert Export
	if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 2) {
		$sep = ";";

		foreach ( $tabfac as $key => $val ) {
			$date = dol_print_date($val["date"], '%d%m%Y');

			// Product / Service
			foreach ( $tabht[$key] as $k => $mt ) {
				$companystatic->id = $tabcompany[$key]['id'];
				$companystatic->name = $tabcompany[$key]['name'];
				$companystatic->client = $tabcompany[$key]['code_client'];

				if ($mt) {
					print $date . $sep;
					print $purchase_journal . $sep;
					print length_accountg(html_entity_decode($k)) . $sep;
					print $sep;
					print ($mt < 0 ? 'C' : 'D') . $sep;
					print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
					print dol_trunc($val["description"], 32) . $sep;
					print $val["ref"];
					print "\n";
				}
			}

			// VAT
			foreach ( $tabtva[$key] as $k => $mt ) {
				if ($mt) {
					print $date . $sep;
					print $purchase_journal . $sep;
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
				print $purchase_journal . $sep;
				print length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) . $sep;
				print length_accounta(html_entity_decode($k)) . $sep;
				print ($mt < 0 ? 'D' : 'C') . $sep;
				print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
				print $companystatic->name . $sep;
				print $val["ref"];
				print "\n";
			}
		}
	} elseif ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 1) {
		// Model Classic Export
		foreach ( $tabfac as $key => $val ) {

			$invoicestatic->id = $key;
			$invoicestatic->ref = $val["ref"];
			$invoicestatic->ref = $val["refsologest"];
			$invoicestatic->refsupplier = $val["refsuppliersologest"];
			$invoicestatic->type = $val["type"];
			$invoicestatic->description = html_entity_decode(dol_trunc($val["description"], 32));

			$date = dol_print_date($val["date"], 'day');

			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];
			$companystatic->client = $tabcompany[$key]['code_client'];

			// Product / Service
			foreach ( $tabht[$key] as $k => $mt ) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch(null, $k, true);
				if ($mt) {
					print '"' . $date . '"' . $sep;
					print '"' . $val["ref"] . '"' . $sep;
					print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
					print '"' . dol_trunc($companystatic->name, 16) . ' - ' . $val["refsuppliersologest"] . ' - ' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
					// print '"' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
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
					// print '"' . $langs->trans("VAT") . '"' . $sep;
					print '"' . dol_trunc($companystatic->name, 16) . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("VAT") . '"' . $sep;
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
				print '"' . dol_trunc($companystatic->name, 16) . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("Code_tiers") . '"' . $sep;
				print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
				print '"' . ($mt >= 0 ? price($mt) : '') . '"';
			}
			print "\n";
		}
	}
}

if (empty($action) || $action == 'view') {

	llxHeader('', $langs->trans("PurchasesJournal"));

	$nom = $langs->trans("PurchasesJournal");
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate = time();
	//$description = $langs->trans("DescPurchasesJournal") . '<br>';
	$description.= $langs->trans("DescJournalOnlyBindedVisible").'<br>';
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
		$description .= $langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= $langs->trans("DepositsAreIncluded");
	}

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
	// /print "<td>".$langs->trans("JournalNum")."</td>";
	print "<td></td>";
	print "<td>" . $langs->trans("Date") . "</td>";
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("InvoiceRef") . ")</td>";
	print "<td>" . $langs->trans("AccountAccounting") . "</td>";
	print "<t><td>" . $langs->trans("Type") . "</td><td align='right'>" . $langs->trans("Debit") . "</td><td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$r = '';

	$invoicestatic = new FactureFournisseur($db);

	foreach ( $tabfac as $key => $val ) {
		$invoicestatic->id = $key;
		$invoicestatic->ref = $val["ref"];

		$invoicestatic->ref = $val["refsologest"];
		$invoicestatic->refsupplier = $val["refsuppliersologest"];

		$invoicestatic->type = $val["type"];
		$invoicestatic->description = html_entity_decode(dol_trunc($val["description"], 32));

		$date = dol_print_date($val["date"], 'day');

		// Product / Service
		foreach ( $tabht[$key] as $k => $mt ) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k, true);

			if ($mt) {
				print "<tr " . $bc[$var] . " >";
				print "<td><!-- Product --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				print "<td>";
    			$accountoshow = length_accountg($k);
    			if (empty($accountoshow) || $accountoshow == 'NotDefined')
    			{
    			    print '<span class="error">'.$langs->trans("ProductAccountNotDefined").'</span>';
    			}
    			else print $accountoshow;
				print "</td>";
				$companystatic->id = $tabcompany[$key]['id'];
				$companystatic->name = $tabcompany[$key]['name'];
				print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->refsupplier . ' - ' . $accountingaccount->label . "</td>";
				// print "<td>" . $accountingaccount->label . "</td>";
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
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				print "<td>";
    			$accountoshow = length_accountg($k);
    			if (empty($accountoshow) || $accountoshow == 'NotDefined')
    			{
    			    print '<span class="error">'.$langs->trans("VatAccountNotDefined").'</span>';
    			}
    			else print $accountoshow;
				print "</td>";
				print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->refsupplier . ' - ' . $langs->trans("VAT"). ' '.$def_tva[$key]. "</td>";
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
			print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];
			print "<td>";
			$accountoshow = length_accounta($k);
			if (empty($accountoshow) || $accountoshow == 'NotDefined')
			{
			    print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
			}
			else print $accountoshow;
            print "</td>";
			print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->refsupplier . ' - ' . $langs->trans("Code_tiers") . "</td>";
			// print "</td><td>" . $langs->trans("ThirdParty");
			// print ' (' . $companystatic->getNomUrl(0, 'supplier', 16) . ')';
			// print "</td>";
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

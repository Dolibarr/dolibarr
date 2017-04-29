<?php
/* Copyright (C) 2007-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger			<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2013		Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2016	Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2013-2016	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2014		Raphaël Doursenaud		<rdoursenaud@gpcsolutions.fr>
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
 * \ingroup		Advanced accountancy
 * \brief		Page with sells journal
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';

// Langs
$langs->load("commercial");
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

$sql = "SELECT f.rowid, f.facnumber, f.type, f.datef as df, f.ref_client,";
$sql .= " fd.rowid as fdid, fd.description, fd.product_type, fd.total_ht, fd.total_tva, fd.tva_tx, fd.total_ttc,";
$sql .= " s.rowid as socid, s.nom as name, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,";
$sql .= " p.rowid as pid, p.ref as pref, p.accountancy_code_sell, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte,";
$sql .= " fd.situation_percent,ct.accountancy_code_sell as account_tva";
$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = fd.fk_product";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = fd.fk_facture";
$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_tva as ct ON fd.tva_tx = ct.taux AND ct.fk_pays = '" . $idpays . "'";
$sql .= " WHERE fd.fk_code_ventilation > 0";
$sql .= " AND f.entity IN (".getEntity('facture', 0).')';    // We don't share object for accountancy
$sql .= " AND f.fk_statut > 0"; // TODO Facture annulée ?
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
    $sql .= " AND f.type IN (" . Facture::TYPE_STANDARD . "," . Facture::TYPE_REPLACEMENT . "," . Facture::TYPE_CREDIT_NOTE . "," . Facture::TYPE_SITUATION . ")";
} else {
    $sql .= " AND f.type IN (" . Facture::TYPE_STANDARD . "," . Facture::TYPE_STANDARD . "," . Facture::TYPE_CREDIT_NOTE . "," . Facture::TYPE_DEPOSIT . "," . Facture::TYPE_SITUATION . ")";
}
$sql .= " AND fd.product_type IN (0,1)";
if ($date_start && $date_end)
    $sql .= " AND f.datef >= '" . $db->idate($date_start) . "' AND f.datef <= '" . $db->idate($date_end) . "'";
$sql .= " ORDER BY f.datef";

dol_syslog('accountancy/journal/sellsjournal.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
    $tabfac = array ();
    $tabht = array ();
    $tabtva = array ();
    $def_tva = array ();
    $tabttc = array ();
    $tabcompany = array ();

    $num = $db->num_rows($result);
    $i = 0;

    $cptcli = (! empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER)) ? $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER : $langs->trans("CodeNotDef");
    
    while ( $i < $num ) {
        $obj = $db->fetch_object($result);
        
        // les variables
        $compta_soc = (! empty($obj->code_compta)) ? $obj->code_compta : $cptcli;

        $compta_prod = $obj->compte;
        if (empty($compta_prod)) {
            if ($obj->product_type == 0)
                $compta_prod = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef");
                else
                    $compta_prod = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef");
        }
        $cpttva = (! empty($conf->global->ACCOUNTING_VAT_SOLD_ACCOUNT)) ? $conf->global->ACCOUNTING_VAT_SOLD_ACCOUNT : $langs->trans("CodeNotDef");
        $compta_tva = (! empty($obj->account_tva) ? $obj->account_tva : $cpttva);


        //Define array for display vat tx
        $def_tva[$obj->rowid]=price($obj->tva_tx);

        // Situation invoices handling
        $line = new FactureLigne($db);
        $line->fetch($obj->fdid);
        $prev_progress = $line->get_prev_progress($obj->fdid);
        if ($obj->type == Facture::TYPE_SITUATION) {
            // Avoid divide by 0
            if ($obj->situation_percent == 0) {
                $situation_ratio = 0;
            } else {
                $situation_ratio = ($obj->situation_percent - $prev_progress) / $obj->situation_percent;
            }
        } else {
            $situation_ratio = 1;
        }

        // Invoice lines
        $tabfac[$obj->rowid]["date"] = $db->jdate($obj->df);
        $tabfac[$obj->rowid]["ref"] = $obj->facnumber;
        $tabfac[$obj->rowid]["type"] = $obj->type;
        $tabfac[$obj->rowid]["description"] = $obj->label_compte;
        //$tabfac[$obj->rowid]["fk_facturedet"] = $obj->fdid;
       
        // Avoid warnings
        if (! isset($tabttc[$obj->rowid][$compta_soc])) $tabttc[$obj->rowid][$compta_soc] = 0;
        if (! isset($tabht[$obj->rowid][$compta_prod])) $tabht[$obj->rowid][$compta_prod] = 0;
        if (! isset($tabtva[$obj->rowid][$compta_tva])) $tabtva[$obj->rowid][$compta_tva] = 0;

        $tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc * $situation_ratio;
        $tabht[$obj->rowid][$compta_prod] += $obj->total_ht * $situation_ratio;
        $tabtva[$obj->rowid][$compta_tva] += $obj->total_tva * $situation_ratio;
        $tabcompany[$obj->rowid] = array (
            'id' => $obj->socid,
            'name' => $obj->name,
            'code_client' => $obj->code_client,
            'code_compta' => $compta_soc
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

    foreach ( $tabfac as $key => $val ) {   // Loop on each invoice
         
        $errorforline = 0;

        $db->begin();

        $companystatic = new Societe($db);
        $invoicestatic = new Facture($db);

        $companystatic->id = $tabcompany[$key]['id'];
        $companystatic->name = $tabcompany[$key]['name'];
		$companystatic->code_compta = $tabcompany[$key]['code_compta'];
		$companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
		$companystatic->code_client = $tabcompany[$key]['code_client'];
		$companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
        $companystatic->client = $tabcompany[$key]['code_client'];

        $invoicestatic->id = $key;
        $invoicestatic->ref = (string) $val["ref"];

        // Thirdparty
        if (! $errorforline)
        {
            foreach ( $tabttc[$key] as $k => $mt ) {
                if ($mt) {
                    $bookkeeping = new BookKeeping($db);
                    $bookkeeping->doc_date = $val["date"];
                    $bookkeeping->doc_ref = $val["ref"];
                    $bookkeeping->date_create = $now;
                    $bookkeeping->doc_type = 'customer_invoice';
                    $bookkeeping->fk_doc = $key;
                    $bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add
                    $bookkeeping->code_tiers = $tabcompany[$key]['code_client'];
                    $bookkeeping->numero_compte = $tabcompany[$key]['code_compta'];
                    // $bookkeeping->label_compte = $tabcompany[$key]['name'];
                    $bookkeeping->label_compte = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Code_tiers");
                    $bookkeeping->montant = $mt;
                    $bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
                    $bookkeeping->debit = ($mt >= 0) ? $mt : 0;
                    $bookkeeping->credit = ($mt < 0) ? $mt : 0;
                    $bookkeeping->code_journal = $conf->global->ACCOUNTING_SELL_JOURNAL;
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
                if ($mt) {
                    // get compte id and label
                    $accountingaccount = new AccountingAccount($db);
                    if ($accountingaccount->fetch(null, $k, true)) {
                        $bookkeeping = new BookKeeping($db);
                        $bookkeeping->doc_date = $val["date"];
                        $bookkeeping->doc_ref = $val["ref"];
                        $bookkeeping->date_create = $now;
                        $bookkeeping->doc_type = 'customer_invoice';
                        $bookkeeping->fk_doc = $key;
                        $bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add;
                        $bookkeeping->code_tiers = '';
                        $bookkeeping->numero_compte = $k;
                        $bookkeeping->label_compte = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $accountingaccount->label;
                        $bookkeeping->montant = $mt;
                        $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                        $bookkeeping->debit = ($mt < 0) ? $mt : 0;
                        $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                        $bookkeeping->code_journal = $conf->global->ACCOUNTING_SELL_JOURNAL;
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
                    $bookkeeping = new BookKeeping($db);
                    $bookkeeping->doc_date = $val["date"];
                    $bookkeeping->doc_ref = $val["ref"];
                    $bookkeeping->date_create = $now;
                    $bookkeeping->doc_type = 'customer_invoice';
                    $bookkeeping->fk_doc = $key;
                    $bookkeeping->fk_docdet = 0;    // Useless, can be several lines that are source of this record to add
                    $bookkeeping->code_tiers = '';
                    $bookkeeping->numero_compte = $k;
                    $bookkeeping->label_compte = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("VAT").' '.$def_tva[$key];
                    $bookkeeping->montant = $mt;
                    $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                    $bookkeeping->debit = ($mt < 0) ? $mt : 0;
                    $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                    $bookkeeping->code_journal = $conf->global->ACCOUNTING_SELL_JOURNAL;
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
}



/*
 * View
 */

$form = new Form($db);

// Export
if ($action == 'export_csv') {

    $sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
    $sell_journal = $conf->global->ACCOUNTING_SELL_JOURNAL;

    include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

    $companystatic = new Client($db);

    // Model Cegid Expert Export
    if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 2) {
        $sep = ";";

        foreach ( $tabfac as $key => $val ) {
            $companystatic->id = $tabcompany[$key]['id'];
            $companystatic->name = $tabcompany[$key]['name'];
            $companystatic->client = $tabcompany[$key]['code_client'];

            $invoicestatic->id = $key;
            $invoicestatic->ref = $val["ref"];

            $date = dol_print_date($val["date"], '%d%m%Y');

            foreach ( $tabttc[$key] as $k => $mt ) {
                print $date . $sep;
                print $sell_journal . $sep;
                print length_accountg($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) . $sep;
                print length_accounta(html_entity_decode($k)) . $sep;
                print ($mt < 0 ? 'C' : 'D') . $sep;
                print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
                print dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Code_tiers") . $sep;
                print $val["ref"];
                print "\n";
            }

            // Product / Service
            foreach ( $tabht[$key] as $k => $mt ) {
                $accountingaccount_static = new AccountingAccount($db);
                if ($accountingaccount_static->fetch(null, $k, true)) {
                    print $date . $sep;
                    print $sell_journal . $sep;
                    print length_accountg(html_entity_decode($k)) . $sep;
                    print $sep;
                    print ($mt < 0 ? 'D' : 'C') . $sep;
                    print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
                    print dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $accountingaccount_static->label . $sep;
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
                    print dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("VAT") . $sep;
                    // print $langs->trans("VAT") . $sep;
                    print $val["ref"];
                    print "\n";
                }
            }
        }
    } elseif ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 1) {
        // Model Classic Export
        foreach ( $tabfac as $key => $val ) {
            $companystatic->id = $tabcompany[$key]['id'];
            $companystatic->name = $tabcompany[$key]['name'];
            $companystatic->client = $tabcompany[$key]['code_client'];

            $invoicestatic->id = $key;
            $invoicestatic->ref = $val["ref"];

            $date = dol_print_date($val["date"], 'day');

            foreach ( $tabttc[$key] as $k => $mt ) {
                print '"' . $date . '"' . $sep;
                print '"' . $val["ref"] . '"' . $sep;
                print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
                print '"' . dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Code_tiers") . '"' . $sep;
                print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                print '"' . ($mt < 0 ? price(- $mt) : '') . '"';
                print "\n";
            }

            // Product / Service
            foreach ( $tabht[$key] as $k => $mt ) {
                $accountingaccount = new AccountingAccount($db);
                $accountingaccount->fetch(null, $k, true);

                if ($mt) {
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["ref"] . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '"' . dol_trunc($companystatic->name, 16) . ' - ' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
                    print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"';
                    print "\n";
                }
            }

            // VAT
            foreach ( $tabtva[$key] as $k => $mt ) {
                if ($mt) {
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["ref"] . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '"' . dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("VAT") . '"' . $sep;
                    print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"';
                    print "\n";
                }
            }
        }
    }
}


if (empty($action) || $action == 'view') {

	llxHeader('', $langs->trans("SellsJournal"));

	$nom = $langs->trans("SellsJournal");
	$nomlink = '';
	$periodlink = '';
	$exportlink = '';
	$builddate = time();
	//$description = $langs->trans("DescSellsJournal") . '<br>';
	$description.= $langs->trans("DescJournalOnlyBindedVisible").'<br>';
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
		$description .= $langs->trans("DepositsAreNotIncluded");
	else
		$description .= $langs->trans("DepositsAreIncluded");
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
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("InvoiceRef") . ")</td>";
	print "<td>" . $langs->trans("AccountAccounting") . "</td>";
	print "<td>" . $langs->trans("Type") . "</td>";
	print "<td align='right'>" . $langs->trans("Debit") . "</td>";
	print "<td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$var = true;
	$r = '';

	$invoicestatic = new Facture($db);
	$companystatic = new Client($db);

	foreach ( $tabfac as $key => $val ) {
		$invoicestatic->id = $key;
		$invoicestatic->ref = $val["ref"];
		$invoicestatic->type = $val["type"];

		$date = dol_print_date($val["date"], 'day');

		// Third party
		foreach ( $tabttc[$key] as $k => $mt ) {
			print "<tr " . $bc[$var] . ">";
			print "<td><!-- Thirdparty --></td>";
			print "<td>" . $date . "</td>";
			print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];
			$companystatic->client = $tabcompany[$key]['code_client'];
			$companystatic->client = $tabcompany[$key]['code_client'];
			print "<td>";
			$accountoshow = length_accounta($k);
			if (empty($accountoshow) || $accountoshow == 'NotDefined')
			{
			    print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
			}
			else print $accountoshow;
			// print "</td><td>" . $langs->trans("ThirdParty");
			// print ' (' . $companystatic->getNomUrl(0, 'customer', 16) . ')';
			print '</td>';
			print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Code_tiers") . "</td>";
			print "</td><td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
			print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
		    print "</tr>";
		}

		// Product / Service
		foreach ( $tabht[$key] as $k => $mt ) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch(null, $k, true);

			if ($mt) {
				print "<tr " . $bc[$var] . ">";
				print "<td><!-- Product --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				print "<td>";
    			$accountoshow = length_accountg($k);
    			if (empty($accountoshow) || $accountoshow == 'NotDefined')
    			{
    			    print '<span class="error">'.$langs->trans("ProductNotDefined").'</span>';
    			}
    			else print $accountoshow;
				print "</td>";
				// print "<td>" . $accountingaccount->label . "</td>";
				print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $accountingaccount->label . "</td>";
				print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
				print "</tr>";
			}
		}

		// VAT
		foreach ( $tabtva[$key] as $k => $mt ) {
			if ($mt) {
				print "<tr " . $bc[$var] . ">";
				print "<td><!-- VAT --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
				print "<td>";
    			$accountoshow = length_accountg($k);
    			if (empty($accountoshow) || $accountoshow == 'NotDefined')
    			{
    			    print '<span class="error">'.$langs->trans("VATAccountNotDefined").'</span>';
    			}
    			else print $accountoshow;
				print "</td>";
				print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("VAT") . ' '.$def_tva[$key]. "</td>";
				// print "<td>" . $langs->trans("VAT") . "</td>";
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

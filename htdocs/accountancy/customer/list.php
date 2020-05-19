<?php
/* Copyright (C) 2013-2014	Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2020	Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)	<github@accedinfo.com>
 * Copyright (C) 2013-2014	Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014	  	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016	  	Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file 		htdocs/accountancy/customer/list.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		Ventilation page from customers invoices
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("bills", "companies", "compta", "accountancy", "other", "productbatch"));

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

// Select Box
$mesCasesCochees = GETPOST('toselect', 'array');

// Search Getpost
$search_societe = GETPOST('search_societe', 'alpha');
$search_lineid = GETPOST('search_lineid', 'int');
$search_ref = GETPOST('search_ref', 'alpha');
$search_invoice = GETPOST('search_invoice', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$search_vat = GETPOST('search_vat', 'alpha');
$search_day = GETPOST("search_day", "int");
$search_month = GETPOST("search_month", "int");
$search_year = GETPOST("search_year", "int");
$search_country = GETPOST('search_country', 'alpha');
$search_tvaintra = GETPOST('search_tvaintra', 'alpha');

$btn_ventil = GETPOST('ventil', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION) ? $conf->liste_limit : $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield)
	$sortfield = "f.datef, f.ref, l.rowid";
if (!$sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO > 0) {
		$sortorder = "DESC";
	}
}

// Security check
if ($user->socid > 0)
	accessforbidden();
if (!$user->rights->accounting->bind->write)
	accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('accountancycustomerlist'));

$formaccounting = new FormAccounting($db);

$chartaccountcode = dol_getIdFromCode($db, $conf->global->CHARTOFACCOUNTS, 'accounting_system', 'rowid', 'pcg_version');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All test are required to be compatible with all browsers
	{
		$search_societe='';
		$search_lineid = '';
		$search_ref = '';
		$search_invoice = '';
		$search_label = '';
		$search_desc = '';
		$search_amount = '';
		$search_account = '';
		$search_vat = '';
		$search_day = '';
		$search_month = '';
		$search_year = '';
		$search_country = '';
		$search_tvaintra = '';
	}

	// Mass actions
	$objectclass = 'AccountingAccount';
	$permissiontoread = $user->rights->accounting->read;
	$permissiontodelete = $user->rights->accounting->delete;
	$uploaddir = $conf->accounting->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


if ($massaction == 'ventil') {
    $msg = '';

    //print '<div><font color="red">' . $langs->trans("Processing") . '...</font></div>';
    if (!empty($mesCasesCochees)) {
        $msg = '<div>'.$langs->trans("SelectedLines").': '.count($mesCasesCochees).'</div>';
        $msg .= '<div class="detail">';
        $cpt = 0;
        $ok = 0;
        $ko = 0;

        foreach ($mesCasesCochees as $maLigneCochee) {
            $maLigneCourante = explode("_", $maLigneCochee);
            $monId = $maLigneCourante[0];
            $monCompte = GETPOST('codeventil'.$monId);

            if ($monCompte <= 0)
            {
                $msg .= '<div><font color="red">'.$langs->trans("Lineofinvoice").' '.$monId.' - '.$langs->trans("NoAccountSelected").'</font></div>';
                $ko++;
            }
            else
            {
                $sql = " UPDATE ".MAIN_DB_PREFIX."facturedet";
                $sql .= " SET fk_code_ventilation = ".$monCompte;
                $sql .= " WHERE rowid = ".$monId;

                $accountventilated = new AccountingAccount($db);
                $accountventilated->fetch($monCompte, '');

                dol_syslog("accountancy/customer/list.php sql=".$sql, LOG_DEBUG);
                if ($db->query($sql)) {
                    $msg .= '<div><font color="green">'.$langs->trans("Lineofinvoice").' '.$monId.' - '.$langs->trans("VentilatedinAccount").' : '.length_accountg($accountventilated->account_number).'</font></div>';
                    $ok++;
                } else {
                    $msg .= '<div><font color="red">'.$langs->trans("ErrorDB").' : '.$langs->trans("Lineofinvoice").' '.$monId.' - '.$langs->trans("NotVentilatedinAccount").' : '.length_accountg($accountventilated->account_number).'<br> <pre>'.$sql.'</pre></font></div>';
                    $ko++;
                }
            }

            $cpt++;
        }
        $msg .= '</div>';
        $msg .= '<div>'.$langs->trans("EndProcessing").'</div>';
    }
}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader('', $langs->trans("Ventilation"));

if (empty($chartaccountcode))
{
	print $langs->trans("ErrorChartOfAccountSystemNotSelected");
	// End of page
    llxFooter();
    $db->close();
	exit;
}

// Customer Invoice lines
$sql = "SELECT f.rowid as facid, f.ref as ref, f.datef, f.type as ftype,";
$sql .= " l.rowid, l.fk_product, l.description, l.total_ht, l.fk_code_ventilation, l.product_type as type_l, l.tva_tx as tva_tx_line, l.vat_src_code,";
$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.tva_tx as tva_tx_prod,";
$sql .= " p.accountancy_code_sell as code_sell, p.accountancy_code_sell_intra as code_sell_intra, p.accountancy_code_sell_export as code_sell_export,";
$sql .= " p.accountancy_code_buy as code_buy, p.accountancy_code_buy_intra as code_buy_intra, p.accountancy_code_buy_export as code_buy_export,";
$sql .= " p.tosell as status, p.tobuy as status_buy,";
$sql .= " aa.rowid as aarowid, aa2.rowid as aarowid_intra, aa3.rowid as aarowid_export,";
$sql .= " co.code as country_code, co.label as country_label,";
$sql .= " s.rowid as socid, s.nom as name, s.tva_intra, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur";
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON co.rowid = s.fk_pays ";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."facturedet as l ON f.rowid = l.fk_facture";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = l.fk_product";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa  ON p.accountancy_code_sell = aa.account_number         AND aa.active = 1  AND aa.fk_pcg_version = '".$chartaccountcode."' AND aa.entity = ".$conf->entity;
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa2 ON p.accountancy_code_sell_intra = aa2.account_number  AND aa2.active = 1 AND aa2.fk_pcg_version = '".$chartaccountcode."' AND aa2.entity = ".$conf->entity;
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa3 ON p.accountancy_code_sell_export = aa3.account_number AND aa3.active = 1 AND aa3.fk_pcg_version = '".$chartaccountcode."' AND aa3.entity = ".$conf->entity;
$sql .= " WHERE f.fk_statut > 0 AND l.fk_code_ventilation <= 0";
$sql .= " AND l.product_type <= 2";
// Add search filter like
if ($search_societe) {
	$sql .= natural_search('s.nom', $search_societe);
}
if ($search_lineid) {
    $sql .= natural_search("l.rowid", $search_lineid, 1);
}
if (strlen(trim($search_invoice))) {
    $sql .= natural_search("f.ref", $search_invoice);
}
if (strlen(trim($search_ref))) {
    $sql .= natural_search("p.ref", $search_ref);
}
if (strlen(trim($search_label))) {
    $sql .= natural_search("p.label", $search_label);
}
if (strlen(trim($search_desc))) {
    $sql .= natural_search("l.description", $search_desc);
}
if (strlen(trim($search_amount))) {
    $sql .= natural_search("l.total_ht", $search_amount, 1);
}
if (strlen(trim($search_account))) {
    $sql .= natural_search("aa.account_number", $search_account);
}
if (strlen(trim($search_vat))) {
    $sql .= natural_search("l.tva_tx", price2num($search_vat), 1);
}
$sql .= dolSqlDateFilter('f.datef', $search_day, $search_month, $search_year);
if (strlen(trim($search_country))) {
	$arrayofcode = getCountriesInEEC();
	$country_code_in_EEC = $country_code_in_EEC_without_me = '';
	foreach ($arrayofcode as $key => $value)
	{
		$country_code_in_EEC .= ($country_code_in_EEC ? "," : "")."'".$value."'";
		if ($value != $mysoc->country_code) $country_code_in_EEC_without_me .= ($country_code_in_EEC_without_me ? "," : "")."'".$value."'";
	}
	if ($search_country == 'special_allnotme')     $sql .= " AND co.code <> '".$db->escape($mysoc->country_code)."'";
	elseif ($search_country == 'special_eec')      $sql .= " AND co.code IN (".$country_code_in_EEC.")";
	elseif ($search_country == 'special_eecnotme') $sql .= " AND co.code IN (".$country_code_in_EEC_without_me.")";
	elseif ($search_country == 'special_noteec')   $sql .= " AND co.code NOT IN (".$country_code_in_EEC.")";
	else $sql .= natural_search("co.code", $search_country);
}
if (strlen(trim($search_tvaintra))) {
	$sql .= natural_search("s.tva_intra", $search_tvaintra);
}
if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_SITUATION.")";
} else {
	$sql .= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_DEPOSIT.",".Facture::TYPE_SITUATION.")";
}
$sql .= " AND f.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("accountancy/customer/list.php", LOG_DEBUG);
// MAX_JOIN_SIZE can be very low (ex: 300000) on some limited configurations (ex: https://www.online.net/fr/hosting/online-perso)
// This big SELECT command may exceed the MAX_JOIN_SIZE limit => Therefore we use SQL_BIG_SELECTS=1 to disable the MAX_JOIN_SIZE security
if ($db->type == 'mysqli') {
	$db->query("SET SQL_BIG_SELECTS=1");
}
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($search_societe)     $param .= '&search_societe='.urlencode($search_societe);
	if ($search_lineid)      $param .= '&search_lineid='.urlencode($search_lineid);
	if ($search_day)         $param .= '&search_day='.urlencode($search_day);
	if ($search_month)       $param .= '&search_month='.urlencode($search_month);
	if ($search_year)        $param .= '&search_year='.urlencode($search_year);
	if ($search_invoice)     $param .= '&search_invoice='.urlencode($search_invoice);
	if ($search_ref)         $param .= '&search_ref='.urlencode($search_ref);
	if ($search_desc)        $param .= '&search_desc='.urlencode($search_desc);
	if ($search_amount)      $param .= '&search_amount='.urlencode($search_amount);
	if ($search_vat)         $param .= '&search_vat='.urlencode($search_vat);
	if ($search_country)  	 $param .= "&search_country=".urlencode($search_country);
	if ($search_tvaintra)	 $param .= "&search_tvaintra=".urlencode($search_tvaintra);

	$arrayofmassactions = array(
	    'ventil'=>$langs->trans("Ventilate")
	    //'presend'=>$langs->trans("SendByMail"),
	    //'builddoc'=>$langs->trans("PDFMerge"),
	);
	//if ($user->rights->mymodule->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	//if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton = $form->selectMassAction('ventil', $arrayofmassactions, 1);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">'."\n";
	print '<input type="hidden" name="action" value="ventil">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("InvoiceLines"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

	print '<span class="opacitymedium">'.$langs->trans("DescVentilTodoCustomer").'</span></br><br>';

	/*$topicmail="Information";
	 $modelmail="project";
	 $objecttmp=new Project($db);
	 $trackid='prj'.$object->id;
	 include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';*/

	if ($msg) print $msg.'<br>';

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// We add search filter
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth25" name="search_lineid" value="'.dol_escape_htmltag($search_lineid).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_invoice" value="'.dol_escape_htmltag($search_invoice).'"></td>';
	print '<td class="liste_titre center nowraponall minwidth100imp">';
   	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
        print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_day" value="'.$search_day.'">';
    }
   	print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_month" value="'.$search_month.'">';
   	$formother->select_year($search_year, 'search_year', 1, 20, 5);
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
	//print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_desc" value="'.dol_escape_htmltag($search_desc).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_vat" placeholder="%" size="1" value="'.dol_escape_htmltag($search_vat).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75imp" name="search_societe" value="' . dol_escape_htmltag($search_societe) . '"></td>';
	print '<td class="liste_titre">';
	print $form->select_country($search_country, 'search_country', '', 0, 'maxwidth150', 'code2', 1, 0, 1);
	//print '<input type="text" class="flat maxwidth50" name="search_country" value="' . dol_escape_htmltag($search_country) . '">';
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_tvaintra" value="'.dol_escape_htmltag($search_tvaintra).'"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="center liste_titre">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre("LineId", $_SERVER["PHP_SELF"], "l.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "f.datef, f.ref, l.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("ProductRef", $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("ProductLabel", $_SERVER["PHP_SELF"], "p.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("ProductDescription", $_SERVER["PHP_SELF"], "l.description", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "l.total_ht", "", $param, '', $sortfield, $sortorder, 'right maxwidth50 ');
	print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], "l.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Country", $_SERVER["PHP_SELF"], "co.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("VATIntra", $_SERVER["PHP_SELF"], "s.tva_intra", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("AccountAccountingSuggest", '', '', '', '', '', '', '', 'nowraponall ');
	print_liste_field_titre("IntoAccount", '', '', '', '', '', '', '', 'center ');
	$checkpicto = '';
	if ($massactionbutton) $checkpicto = $form->showCheckAddButtons('checkforselect', 1);
	print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
	print "</tr>\n";

	$thirdpartystatic = new Societe($db);
	$facture_static = new Facture($db);
	$product_static = new Product($db);

	$isSellerInEEC = isInEEC($mysoc);

    $accountingaccount_codetotid_cache = array();

	while ($i < min($num_lines, $limit)) {
		$objp = $db->fetch_object($result);

		$objp->code_sell_l = '';
		$objp->code_sell_p = '';

		$thirdpartystatic->id = $objp->socid;
		$thirdpartystatic->name = $objp->name;
		$thirdpartystatic->client = $objp->client;
		$thirdpartystatic->fournisseur = $objp->fournisseur;
		$thirdpartystatic->code_client = $objp->code_client;
		$thirdpartystatic->code_compta_client = $objp->code_compta_client;
		$thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
		$thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
		$thirdpartystatic->email = $objp->email;
		$thirdpartystatic->country_code = $objp->country_code;

		$product_static->ref = $objp->product_ref;
		$product_static->id = $objp->product_id;
		$product_static->type = $objp->type;
		$product_static->label = $objp->product_label;
		$product_static->status = $objp->status;
		$product_static->status_buy = $objp->status_buy;
		$product_static->accountancy_code_sell = $objp->code_sell;
		$product_static->accountancy_code_sell_intra = $objp->code_sell_intra;
		$product_static->accountancy_code_sell_export = $objp->code_sell_export;
		$product_static->accountancy_code_buy = $objp->code_buy;
		$product_static->accountancy_code_buy_intra = $objp->code_buy_intra;
		$product_static->accountancy_code_buy_export = $objp->code_buy_export;

		$facture_static->ref = $objp->ref;
		$facture_static->id = $objp->facid;
		$facture_static->type = $objp->ftype;

		$code_sell_p_notset = '';
		$objp->aarowid_suggest = ''; // Will be set later

        $isBuyerInEEC = isInEEC($objp);

        // Search suggested default account for product/service
        $suggestedaccountingaccountbydefaultfor = '';
    	if ($objp->type_l == 1) {
    		if ($objp->country_code == $mysoc->country_code || empty($objp->country_code)) {  // If buyer in same country than seller (if not defined, we assume it is same country)
    			$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : '');
    			$suggestedaccountingaccountbydefaultfor = '';
    		} else {
    			if ($isSellerInEEC && $isBuyerInEEC && $objp->tva_tx_line != 0) {    // European intravat sale, but with a VAT
					$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : '');
					$suggestedaccountingaccountbydefaultfor = 'eecwithvat';
    			} elseif ($isSellerInEEC && $isBuyerInEEC && empty($objp->tva_intra)) {    // European intravat sale, without VAT intra community number
    				$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : '');
    				$suggestedaccountingaccountbydefaultfor = 'eecwithoutvatnumber';
				} elseif ($isSellerInEEC && $isBuyerInEEC) {    // European intravat sale
    				$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT : '');
    				$suggestedaccountingaccountbydefaultfor = 'eec';
	    		} else {                                        // Foreign sale
	    			$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT : '');
	    			$suggestedaccountingaccountbydefaultfor = 'export';
	    		}
    		}
		} elseif ($objp->type_l == 0) {
			if ($objp->country_code == $mysoc->country_code || empty($objp->country_code)) {  // If buyer in same country than seller (if not defined, we assume it is same country)
				$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : '');
				$suggestedaccountingaccountbydefaultfor = '';
			} else {
				if ($isSellerInEEC && $isBuyerInEEC && $objp->tva_tx_line != 0) {	// European intravat sale, but with a VAT
					$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : '');
					$suggestedaccountingaccountbydefaultfor = 'eecwithvat';
				} elseif ($isSellerInEEC && $isBuyerInEEC && empty($objp->tva_intra)) {	// European intravat sale, without VAT intra community number
					$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : '');
					$suggestedaccountingaccountbydefaultfor = 'eecwithoutvatnumber';
				} elseif ($isSellerInEEC && $isBuyerInEEC) {	// European intravat sale
					$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT : '');
					$suggestedaccountingaccountbydefaultfor = 'eec';
				} else {
					$objp->code_sell_l = (!empty($conf->global->ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT : '');
					$suggestedaccountingaccountbydefaultfor = 'export';
				}
			}
		}
		if ($objp->code_sell_l == -1) $objp->code_sell_l = '';

		// Search suggested account for product/service (similar code exists in page index.php to make automatic binding)
		$suggestedaccountingaccountfor = '';
		if (($objp->country_code == $mysoc->country_code) || empty($objp->country_code)) {  // If buyer in same country than seller (if not defined, we assume it is same country)
            $objp->code_sell_p = $objp->code_sell;
            $objp->aarowid_suggest = $objp->aarowid;
            $suggestedaccountingaccountfor = '';
        } else {
        	if ($isSellerInEEC && $isBuyerInEEC && $objp->tva_tx_line != 0) {	// European intravat sale, but with VAT
				$objp->code_sell_p = $objp->code_sell;
				$objp->aarowid_suggest = $objp->aarowid;
				$suggestedaccountingaccountfor = 'eecwithvat';
        	} elseif ($isSellerInEEC && $isBuyerInEEC && empty($objp->tva_intra)) {	// European intravat sale, without VAT intra community number
        		$objp->code_sell_p = $objp->code_sell;
        		$objp->aarowid_suggest = $objp->aarowid; // There is a doubt for this case. Is it an error on vat or we just forgot to fill vat number ?
        		$suggestedaccountingaccountfor = 'eecwithoutvatnumber';
			} elseif ($isSellerInEEC && $isBuyerInEEC) {          // European intravat sale
                $objp->code_sell_p = $objp->code_sell_intra;
                $objp->aarowid_suggest = $objp->aarowid_intra;
                $suggestedaccountingaccountfor = 'eec';
            } else {                                        // Foreign sale
                $objp->code_sell_p = $objp->code_sell_export;
                $objp->aarowid_suggest = $objp->aarowid_export;
                $suggestedaccountingaccountfor = 'export';
            }
        }

		if (!empty($objp->code_sell_p)) {
			// Value was defined previously
		} else {
			$code_sell_p_notset = 'color:orange';
		}
		if (empty($objp->code_sell_l) && empty($objp->code_sell_p)) $code_sell_p_notset = 'color:red';
		if ($suggestedaccountingaccountfor == 'eecwithoutvatnumber' && empty($code_sell_p_notset)) $code_sell_p_notset = 'color:orange';

		// $objp->code_sell_l is now default code of product/service
		// $objp->code_sell_p is now code of product/service

		print '<tr class="oddeven">';

		// Line id
		print '<td>'.$objp->rowid.'</td>';

		// Ref Invoice
		print '<td class="nowraponall">'.$facture_static->getNomUrl(1).'</td>';

		print '<td class="center">'.dol_print_date($db->jdate($objp->datef), 'day').'</td>';

		// Ref Product
		print '<td>';
		if ($product_static->id > 0) {
			print $product_static->getNomUrl(1);
		}
		if ($objp->product_label) print '<br><span class="opacitymedium">'.$objp->product_label.'</span>';
		print '</td>';

		print '<td class="tdoverflowonsmartphone">';
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->description));
		$trunclength = empty($conf->global->ACCOUNTING_LENGTH_DESCRIPTION) ? 32 : $conf->global->ACCOUNTING_LENGTH_DESCRIPTION;
		print $form->textwithtooltip(dol_trunc($text, $trunclength), $objp->description);
		print '</td>';

		print '<td class="nowrap right">';
		print price($objp->total_ht);
		print '</td>';

		// Vat rate
		if ($objp->vat_tx_l != $objp->vat_tx_p)
			$code_vat_differ = 'font-weight:bold; text-decoration:blink; color:red';
		print '<td style="'.$code_vat_differ.'" class="right">';
		print vatrate($objp->tva_tx_line.($objp->vat_src_code ? ' ('.$objp->vat_src_code.')' : ''));
		print '</td>';

		// Thirdparty
		print '<td class="tdoverflowmax100">' . $thirdpartystatic->getNomUrl(1, 'customer') . '</td>';

		// Country
        print '<td>';
        $labelcountry = ($objp->country_code && ($langs->trans("Country".$objp->country_code) != "Country".$objp->country_code)) ? $langs->trans("Country".$objp->country_code) : $objp->country_label;
        print $labelcountry;
        print '</td>';

		print '<td>'.$objp->tva_intra.'</td>';

		// Found accounts
		print '<td>';
	    $s = '<span class="small">'.(($objp->type_l == 1) ? $langs->trans("DefaultForService") : $langs->trans("DefaultForProduct")).': </span>';
	    $shelp = '';
	    if ($suggestedaccountingaccountbydefaultfor == 'eec') $shelp .= $langs->trans("SaleEEC");
	    elseif ($suggestedaccountingaccountbydefaultfor == 'export') $shelp .= $langs->trans("SaleExport");
	    $s .= ($objp->code_sell_l > 0 ? length_accountg($objp->code_sell_l) : '<span style="'.$code_sell_p_notset.'">'.$langs->trans("NotDefined").'</span>');
	    print $form->textwithpicto($s, $shelp, 1, 'help', '', 0, 2, '', 1);
	    if ($objp->product_id > 0)
		{
		    print '<br>';
		    $s = '<span class="small">'.(($objp->type_l == 1) ? $langs->trans("ThisService") : $langs->trans("ThisProduct")).': </span>';
		    $shelp = '';
		    if ($suggestedaccountingaccountfor == 'eec') $shelp = $langs->trans("SaleEEC");
		    elseif ($suggestedaccountingaccountfor == 'eecwithvat') $shelp = $langs->trans("SaleEECWithVAT");
		    elseif ($suggestedaccountingaccountfor == 'eecwithoutvatnumber') $shelp = $langs->trans("SaleEECWithoutVATNumber");
		    elseif ($suggestedaccountingaccountfor == 'export') $shelp = $langs->trans("SaleExport");
		    $s .= (empty($objp->code_sell_p) ? '<span style="'.$code_sell_p_notset.'">'.$langs->trans("NotDefined").'</span>' : length_accountg($objp->code_sell_p));
		    print $form->textwithpicto($s, $shelp, 1, 'help', '', 0, 2, '', 1);
		}
		print '</td>';

		// Suggested accounting account
		// $objp->code_sell_l = default (it takes the country into consideration), $objp->code_sell_p is value for product (it takes the country into consideration too)
		print '<td>';
		$suggestedid = $objp->aarowid_suggest;
		/*var_dump($suggestedid);
		var_dump($objp->code_sell_p);
		var_dump($objp->code_sell_l);*/
		if (empty($suggestedid) && empty($objp->code_sell_p) && !empty($objp->code_sell_l) && empty($conf->global->ACCOUNTANCY_DO_NOT_AUTOFILL_ACCOUNT_WITH_GENERIC))
		{
			if (empty($accountingaccount_codetotid_cache[$objp->code_sell_l]))
			{
				$tmpaccount = new AccountingAccount($db);
				$tmpaccount->fetch(0, $objp->code_sell_l, 1);
				if ($tmpaccount->id > 0) {
					$suggestedid = $tmpaccount->id;
				}
				$accountingaccount_codetotid_cache[$objp->code_sell_l] = $tmpaccount->id;
			}
			else {
				$suggestedid = $accountingaccount_codetotid_cache[$objp->code_sell_l];
			}
		}
		print $formaccounting->select_account($suggestedid, 'codeventil'.$objp->rowid, 1, array(), 0, 0, 'codeventil maxwidth200 maxwidthonsmartphone', 'cachewithshowemptyone');
		print '</td>';

		// Column with checkbox
		print '<td class="center">';
		//var_dump($objp->aarowid);var_dump($objp->aarowid_intra);var_dump($objp->aarowid_export);var_dump($objp->aarowid_suggest);
		$ischecked = $objp->aarowid_suggest;
		if ($suggestedaccountingaccountfor == 'eecwithoutvatnumber') $ischecked = 0;
		print '<input type="checkbox" class="flat checkforselect checkforselect'.$objp->rowid.'" name="toselect[]" value="'.$objp->rowid."_".$i.'"'.($ischecked ? "checked" : "").'/>';
		print '</td>';

		print '</tr>';
		$i++;
	}
	print '</table>';
	print "</div>";

	print '</form>';
} else {
	print $db->error();
}
if ($db->type == 'mysqli') {
	$db->query("SET SQL_BIG_SELECTS=0"); // Enable MAX_JOIN_SIZE limitation
}

// Add code to auto check the box when we select an account
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	jQuery(".codeventil").change(function() {
		var s=$(this).attr("id").replace("codeventil", "")
		console.log(s+" "+$(this).val());
		if ($(this).val() == -1) jQuery(".checkforselect"+s).prop("checked", false);
		else jQuery(".checkforselect"+s).prop("checked", true);
	});
});
</script>';

// End of page
llxFooter();
$db->close();

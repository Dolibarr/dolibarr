<?php
/* Copyright (C) 2013-2014	Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016	Alexandre Spangaro	<aspangaro@zendsi.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 		htdocs/accountancy/customer/list.php
 * \ingroup 	Advanced accountancy
 * \brief 		Ventilation page from customers invoices
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("bills","compta","accountancy","other","productbatch"));

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

// Select Box
$mesCasesCochees = GETPOST('toselect', 'array');

// Search Getpost
$search_lineid = GETPOST('search_lineid', 'int');
$search_ref = GETPOST('search_ref', 'alpha');
$search_invoice = GETPOST('search_invoice', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$search_vat = GETPOST('search_vat', 'alpha');
$search_day=GETPOST("search_day","int");
$search_month=GETPOST("search_month","int");
$search_year=GETPOST("search_year","int");
$search_country = GETPOST('search_country', 'alpha');
$search_tvaintra = GETPOST('search_tvaintra', 'alpha');

$btn_ventil = GETPOST('ventil', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit', 'int'):(empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)?$conf->liste_limit:$conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page','int');
if (empty($page) || $page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield)
	$sortfield = "f.datef, f.facnumber, l.rowid";
if (! $sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO > 0) {
		$sortorder = "DESC";
	}
}

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->bind->write)
	accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('accountancycustomerlist'));

$formaccounting = new FormAccounting($db);
$accounting = new AccountingAccount($db);
$aarowid_s = $accounting->fetch('', $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT, 1);
$aarowid_p = $accounting->fetch('', $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT, 1);

$chartaccountcode = dol_getIdFromCode($db, $conf->global->CHARTOFACCOUNTS, 'accounting_system', 'rowid', 'pcg_version');


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
	{
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
	$objectclass='AccountingAccount';
	$permtoread = $user->rights->accounting->read;
	$permtodelete = $user->rights->accounting->delete;
	$uploaddir = $conf->accounting->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


if ($massaction == 'ventil') {
    $msg='';

    //print '<div><font color="red">' . $langs->trans("Processing") . '...</font></div>';
    if (! empty($mesCasesCochees)) {
        $msg = '<div>' . $langs->trans("SelectedLines") . ': '.count($mesCasesCochees).'</div>';
        $msg.='<div class="detail">';
        $cpt = 0;
        $ok=0;
        $ko=0;

        foreach ($mesCasesCochees as $maLigneCochee) {
            $maLigneCourante = explode("_", $maLigneCochee);
            $monId = $maLigneCourante[0];
            $monCompte = GETPOST('codeventil'.$monId);

            if ($monCompte <= 0)
            {
                $msg.= '<div><font color="red">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NoAccountSelected") . '</font></div>';
                $ko++;
            }
            else
            {
                $sql = " UPDATE " . MAIN_DB_PREFIX . "facturedet";
                $sql .= " SET fk_code_ventilation = " . $monCompte;
                $sql .= " WHERE rowid = " . $monId;

                $accountventilated = new AccountingAccount($db);
                $accountventilated->fetch($monCompte, '');

                dol_syslog("accountancy/customer/list.php sql=" . $sql, LOG_DEBUG);
                if ($db->query($sql)) {
                    $msg.= '<div><font color="green">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("VentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '</font></div>';
                    $ok++;
                } else {
                    $msg.= '<div><font color="red">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NotVentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '<br> <pre>' . $sql . '</pre></font></div>';
                    $ko++;
                }
            }

            $cpt++;
        }
        $msg.='</div>';
        $msg.= '<div>' . $langs->trans("EndProcessing") . '</div>';
    //} else {
    //    setEventMessages($langs->trans("NoRecordSelected"), null, 'warnings');
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
$sql = "SELECT f.rowid as facid, f.facnumber as ref, f.datef, f.type as ftype,";
$sql.= " l.rowid, l.fk_product, l.description, l.total_ht, l.fk_code_ventilation, l.product_type as type_l, l.tva_tx as tva_tx_line, l.vat_src_code,";
$sql.= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.accountancy_code_sell as code_sell, p.tva_tx as tva_tx_prod,";
$sql.= " aa.rowid as aarowid,";
$sql.= " co.code as country_code, co.label as country,";
$sql.= " s.tva_intra";
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM " . MAIN_DB_PREFIX . "facture as f";
$sql.= " INNER JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as co ON co.rowid = s.fk_pays ";
$sql.= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as l ON f.rowid = l.fk_facture";
$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON p.accountancy_code_sell = aa.account_number AND aa.fk_pcg_version = '" . $chartaccountcode."' AND aa.entity = " . $conf->entity;
$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa2 ON p.accountancy_code_sell_intra = aa2.account_number AND aa2.fk_pcg_version = '" . $chartaccountcode."' AND aa2.entity = " . $conf->entity;
$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa3 ON p.accountancy_code_sell_export = aa3.account_number AND aa3.fk_pcg_version = '" . $chartaccountcode."' AND aa3.entity = " . $conf->entity;
$sql.= " WHERE f.fk_statut > 0 AND l.fk_code_ventilation <= 0";
$sql.= " AND l.product_type <= 2";
// Add search filter like
if ($search_lineid) {
    $sql .= natural_search("l.rowid", $search_lineid, 1);
}
if (strlen(trim($search_invoice))) {
    $sql .= natural_search("f.facnumber", $search_invoice);
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
    $sql .= natural_search("l.tva_tx", $search_vat, 1);
}
if ($search_month > 0)
{
	if ($search_year > 0 && empty($search_day))
		$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($search_year,$search_month,false))."' AND '".$db->idate(dol_get_last_day($search_year,$search_month,false))."'";
		else if ($search_year > 0 && ! empty($search_day))
			$sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $search_year))."'";
			else
				$sql.= " AND date_format(f.datef, '%m') = '".$db->escape($search_month)."'";
}
else if ($search_year > 0)
{
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($search_year,1,false))."' AND '".$db->idate(dol_get_last_day($search_year,12,false))."'";
}
if (strlen(trim($search_country))) {
	$arrayofcode = getCountriesInEEC();
	$country_code_in_EEC = $country_code_in_EEC_without_me = '';
	foreach ($arrayofcode as $key => $value)
	{
		$country_code_in_EEC.=($country_code_in_EEC ? "," : "")."'".$value."'";
		if ($value != $mysoc->country_code) $country_code_in_EEC_without_me.=($country_code_in_EEC_without_me ? "," : "")."'".$value."'";
	}
	if ($search_country == 'special_allnotme')     $sql .= " AND co.code <> '".$db->escape($mysoc->country_code)."'";
	elseif ($search_country == 'special_eec')      $sql .= " AND co.code IN (".$country_code_in_EEC.")";
	elseif ($search_country == 'special_eecnotme') $sql .= " AND co.code IN (".$country_code_in_EEC_without_me.")";
	elseif ($search_country == 'special_noteec')   $sql .= " AND co.code NOT IN (".$country_code_in_EEC.")";
	else $sql .= natural_search(array("co.code","co.label"), $search_country);
}
if (strlen(trim($search_tvaintra))) {
	$sql .= natural_search("s.tva_intra", $search_tvaintra);
}
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	$sql .= " AND f.type IN (" . Facture::TYPE_STANDARD . "," . Facture::TYPE_REPLACEMENT . "," . Facture::TYPE_CREDIT_NOTE . "," . Facture::TYPE_SITUATION . ")";
} else {
	$sql .= " AND f.type IN (" . Facture::TYPE_STANDARD . "," . Facture::TYPE_REPLACEMENT . "," . Facture::TYPE_CREDIT_NOTE . "," . Facture::TYPE_DEPOSIT . "," . Facture::TYPE_SITUATION . ")";
}
$sql .= " AND f.entity IN (" . getEntity('facture', 0) . ")";    // We don't share object for accountancy

// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

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
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	$arrayofselected=is_array($toselect)?$toselect:array();

	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($search_lineid)      $param.='&search_lineid='.urlencode($search_lineid);
	if ($search_day)         $param.='&search_day='.urlencode($search_day);
	if ($search_month)       $param.='&search_month='.urlencode($search_month);
	if ($search_year)        $param.='&search_year='.urlencode($search_year);
	if ($search_invoice)     $param.='&search_invoice='.urlencode($search_invoice);
	if ($search_ref)         $param.='&search_ref='.urlencode($search_ref);
	if ($search_desc)        $param.='&search_desc='.urlencode($search_desc);
	if ($search_amount)      $param.='&search_amount='.urlencode($search_amount);
	if ($search_vat)         $param.='&search_vat='.urlencode($search_vat);
	if ($search_country)  	$param .= "&search_country=" . urlencode($search_country);
	if ($search_tvaintra)	$param .= "&search_tvaintra=" . urlencode($search_tvaintra);

	$arrayofmassactions =  array(
	    'ventil'=>$langs->trans("Ventilate")
	    //'presend'=>$langs->trans("SendByMail"),
	    //'builddoc'=>$langs->trans("PDFMerge"),
	);
	//if ($user->rights->mymodule->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
	//if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('ventil', $arrayofmassactions, 1);

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">' . "\n";
	print '<input type="hidden" name="action" value="ventil">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("InvoiceLines"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

	print $langs->trans("DescVentilTodoCustomer") . '</br><br>';

	/*$topicmail="Information";
	 $modelmail="project";
	 $objecttmp=new Project($db);
	 $trackid='prj'.$object->id;
	 include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';*/

	if ($msg) print $msg.'<br>';

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	// We add search filter
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth25" name="search_lineid" value="' . dol_escape_htmltag($search_lineid) . '""></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_invoice" value="' . dol_escape_htmltag($search_invoice) . '"></td>';
	print '<td class="liste_titre center nowraponall">';
   	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.$search_day.'">';
   	print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
   	$formother->select_year($search_year,'search_year',1, 20, 5);
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';
	//print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidthonsmartphone" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
	print '<td class="liste_titre" align="right"><input type="text" class="flat maxwidth50 right" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';
	print '<td class="liste_titre" align="right"><input type="text" class="flat maxwidth50 right" name="search_vat" placeholder="%" size="1" value="' . dol_escape_htmltag($search_vat) . '"></td>';
	print '<td class="liste_titre">';
	print $form->select_country($search_country, 'search_country', '', 0, 'maxwidth200', 'code2', 1, 0, 1);
	//print '<input type="text" class="flat maxwidth50" name="search_country" value="' . dol_escape_htmltag($search_country) . '">';
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_tvaintra" value="' . dol_escape_htmltag($search_tvaintra) . '"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td align="center" class="liste_titre">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre("LineId", $_SERVER["PHP_SELF"], "l.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.facnumber", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "f.datef, f.facnumber, l.rowid", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("ProductRef", $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("ProductLabel", $_SERVER["PHP_SELF"], "p.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Description", $_SERVER["PHP_SELF"], "l.description", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "l.total_ht", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], "l.tva_tx", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("Country", $_SERVER["PHP_SELF"], "co.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("VATIntra", $_SERVER["PHP_SELF"], "s.tva_intra", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("AccountAccountingSuggest", '', '', '', '', 'align="center"');
	print_liste_field_titre("IntoAccount", '', '', '', '', 'align="center"');
	$checkpicto='';
	if ($massactionbutton) $checkpicto=$form->showCheckAddButtons('checkforselect', 1);
	print_liste_field_titre($checkpicto, '', '', '', '', 'align="center"');
	print "</tr>\n";

	$facture_static = new Facture($db);
	$product_static = new Product($db);

	while ( $i < min($num_lines, $limit) ) {
		$objp = $db->fetch_object($result);

		$objp->code_sell_l = '';
		$objp->code_sell_p = '';
		$objp->aarowid_suggest = '';

		$product_static->ref = $objp->product_ref;
		$product_static->id = $objp->product_id;
		$product_static->type = $objp->type;
		$product_static->label = $objp->product_label;

		$facture_static->ref = $objp->ref;
		$facture_static->id = $objp->facid;
		$facture_static->type = $objp->ftype;

		$code_sell_p_notset = '';
		$objp->aarowid_suggest = $objp->aarowid;

		if ($objp->type_l == 1) {
			$objp->code_sell_l = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : '');
			if ($objp->aarowid == '') {
				$objp->aarowid_suggest = $aarowid_s;
			}
		} elseif ($objp->type_l == 0) {
			$objp->code_sell_l = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : '');
			if ($objp->aarowid == '') {
				$objp->aarowid_suggest = $aarowid_p;
			}
		}
		if ($objp->code_sell_l == -1) $objp->code_sell_l='';

		if (! empty($objp->code_sell)) {
			$objp->code_sell_p = $objp->code_sell;       // Code on product
		} else {
			$code_sell_p_notset = 'color:orange';
		}
		if (empty($objp->code_sell_l) && empty($objp->code_sell_p)) $code_sell_p_notset = 'color:red';

		// $objp->code_sell_p is now code of product/service
		// $objp->code_sell_l is now default code of product/service

		print '<tr class="oddeven">';

		// Line id
		print '<td>' . $objp->rowid . '</td>';

		// Ref Invoice
		print '<td class="nowraponall">' . $facture_static->getNomUrl(1) . '</td>';

		print '<td align="center">' . dol_print_date($db->jdate($objp->datef), 'day') . '</td>';

		// Ref Product
		print '<td>';
		if ($product_static->id)
			print $product_static->getNomUrl(1);
		if ($objp->product_label) print '<br>'.$objp->product_label;
		print '</td>';

		print '<td class="tdoverflowonsmartphone">';
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->description));
		$trunclength = empty($conf->global->ACCOUNTING_LENGTH_DESCRIPTION) ? 32 : $conf->global->ACCOUNTING_LENGTH_DESCRIPTION;
		print $form->textwithtooltip(dol_trunc($text,$trunclength), $objp->description);
		print '</td>';

		print '<td align="right">';
		print price($objp->total_ht);
		print '</td>';

		// Vat rate
		if ($objp->vat_tx_l != $objp->vat_tx_p)
			$code_vat_differ = 'font-weight:bold; text-decoration:blink; color:red';
		print '<td style="' . $code_vat_differ . '" align="right">';
		print vatrate($objp->tva_tx_line.($objp->vat_src_code?' ('.$objp->vat_src_code.')':''));
		print '</td>';

		print '<td>' . $objp->country .'</td>';

		print '<td>' . $objp->tva_intra . '</td>';

		// Current account
		print '<td align="center" style="' . $code_sell_p_notset . '">';
	    print (($objp->type_l == 1)?$langs->trans("DefaultForService"):$langs->trans("DefaultForProduct")) . ' = ' . ($objp->code_sell_l > 0 ? length_accountg($objp->code_sell_l) : $langs->trans("Unknown"));
		if ($objp->product_id > 0)
		{
		    print '<br>';
		    print (($objp->type_l == 1)?$langs->trans("ThisService"):$langs->trans("ThisProduct")) . ' = ' . (empty($objp->code_sell_p) ? $langs->trans("Unknown") : length_accountg($objp->code_sell_p));
		}
		print '</td>';

		// Suggested accounting account
		print '<td align="center">';
		print $formaccounting->select_account($objp->aarowid_suggest, 'codeventil'.$objp->rowid, 1, array(), 0, 0, 'codeventil maxwidth300 maxwidthonsmartphone', 'cachewithshowemptyone');
		print '</td>';

		// Column with checkbox
		print '<td align="center">';
		print '<input type="checkbox" class="flat checkforselect checkforselect'.$objp->rowid.'" name="toselect[]" value="' . $objp->rowid . "_" . $i . '"' . ($objp->aarowid ? "checked" : "") . '/>';
		print '</td>';

		print '</tr>';
		$i ++;
	}
	print '</table>';
	print "</div>";

	print '</form>';
} else {
	print $db->error();
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

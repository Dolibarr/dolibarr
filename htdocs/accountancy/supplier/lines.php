<?php
/* Copyright (C) 2013-2016 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2020 Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2015 Ari Elbaz (elarifr)	<github@accedinfo.com>
 * Copyright (C) 2013-2016 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014      Juanjo Menent		<jmenent@2byte.es>
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
 * \file 		htdocs/accountancy/supplier/lines.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		Page of detail of the lines of ventilation of invoices suppliers
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "other", "accountancy", "productbatch", "products"));

$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$account_parent = GETPOST('account_parent');
$changeaccount = GETPOST('changeaccount');
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

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION) ? $conf->liste_limit : $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0) $page = 0;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield)
	$sortfield = "f.datef, f.ref, l.rowid";
if (!$sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_DONE > 0) {
		$sortorder = "DESC";
	}
}

// Security check
if ($user->socid > 0)
	accessforbidden();
if (!$user->rights->accounting->bind->write)
	accessforbidden();

$formaccounting = new FormAccounting($db);


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_societe = '';
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

if (is_array($changeaccount) && count($changeaccount) > 0) {
	$error = 0;

	if (!(GETPOST('account_parent', 'int') >= 0))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Account")), null, 'errors');
	}


	if (!$error)
	{
		$db->begin();

		$sql1 = "UPDATE ".MAIN_DB_PREFIX."facture_fourn_det as l";
		$sql1 .= " SET l.fk_code_ventilation=".(GETPOST('account_parent', 'int') > 0 ? GETPOST('account_parent', 'int') : '0');
		$sql1 .= ' WHERE l.rowid IN ('.implode(',', $changeaccount).')';

		dol_syslog('accountancy/supplier/lines.php::changeaccount sql= '.$sql1);
		$resql1 = $db->query($sql1);
		if (!$resql1) {
			$error++;
			setEventMessages($db->lasterror(), null, 'errors');
		}
		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("Save"), null, 'mesgs');
		} else {
			$db->rollback();
			setEventMessages($db->lasterror(), null, 'errors');
		}

		$account_parent = ''; // Protection to avoid to mass apply it a second time
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader('', $langs->trans("SuppliersVentilation").' - '.$langs->trans("Dispatched"));

print '<script type="text/javascript">
			$(function () {
				$(\'#select-all\').click(function(event) {
				    // Iterate each checkbox
				    $(\':checkbox\').each(function() {
				    	this.checked = true;
				    });
			    });
			    $(\'#unselect-all\').click(function(event) {
				    // Iterate each checkbox
				    $(\':checkbox\').each(function() {
				    	this.checked = false;
				    });
			    });
			});
			 </script>';

/*
 * Supplier Invoice lines
 */
$sql = "SELECT f.rowid as facid, f.ref as ref, f.ref_supplier, f.libelle as invoice_label, f.datef, f.fk_soc,";
$sql .= " l.rowid, l.fk_product, l.product_type as line_type, l.description, l.total_ht , l.qty, l.tva_tx, l.vat_src_code,";
$sql .= " aa.label, aa.labelshort, aa.account_number,";
$sql .= " p.rowid as product_id, p.fk_product_type as product_type, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.tobuy, p.tosell,";
$sql .= " p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export,";
$sql .= " co.code as country_code, co.label as country,";
$sql .= " s.rowid as socid, s.nom as name, s.tva_intra, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur";
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as l";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = l.fk_product";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = l.fk_code_ventilation";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."facture_fourn as f ON f.rowid = l.fk_facture_fourn";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON co.rowid = s.fk_pays ";
$sql .= " WHERE f.rowid = l.fk_facture_fourn and f.fk_statut >= 1 AND l.fk_code_ventilation <> 0 ";
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
if (strlen(trim($search_label))) {
	$sql .= natural_search("f.libelle", $search_label);
}
if (strlen(trim($search_ref))) {
	$sql .= natural_search("p.ref", $search_ref);
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
$sql .= " AND f.entity IN (".getEntity('facture_fourn', 0).")"; // We don't share object for accountancy

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

dol_syslog("accountancy/supplier/lines.php", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($search_societe)	$param .= "&search_societe=".urlencode($search_societe);
	if ($search_invoice)	$param .= "&search_invoice=".urlencode($search_invoice);
	if ($search_ref)		$param .= "&search_ref=".urlencode($search_ref);
	if ($search_label)		$param .= "&search_label=".urlencode($search_label);
	if ($search_desc)		$param .= "&search_desc=".urlencode($search_desc);
	if ($search_account)	$param .= "&search_account=".urlencode($search_account);
	if ($search_vat)		$param .= "&search_vat=".urlencode($search_vat);
	if ($search_day)        $param .= '&search_day='.urlencode($search_day);
	if ($search_month)      $param .= '&search_month='.urlencode($search_month);
	if ($search_year)       $param .= '&search_year='.urlencode($search_year);
	if ($search_country) 	$param .= "&search_country=".urlencode($search_country);
	if ($search_tvaintra)	$param .= "&search_tvaintra=".urlencode($search_tvaintra);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">'."\n";
	print '<input type="hidden" name="action" value="ventil">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("InvoiceLinesDone"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);
	print '<span class="opacitymedium">'.$langs->trans("DescVentilDoneSupplier").'</span><br>';

	print '<br><div class="inline-block divButAction">'.$langs->trans("ChangeAccount").'<br>';
	print $formaccounting->select_account($account_parent, 'account_parent', 2, array(), 0, 0, 'maxwidth300 maxwidthonsmartphone valignmiddle');
	print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("ChangeBinding").'"/></div>';

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// We add search filter
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth25" name="search_lineid" value="'.dol_escape_htmltag($search_lineid).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_invoice" value="'.dol_escape_htmltag($search_invoice).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
	print '<td class="liste_titre center nowraponall">';
   	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_day" value="'.$search_day.'">';
   	print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_month" value="'.$search_month.'">';
   	$formother->select_year($search_year, 'search_year', 1, 20, 5);
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_desc" value="'.dol_escape_htmltag($search_desc).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="right flat maxwidth50" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="right flat maxwidth50" name="search_vat" placeholder="%" size="1" value="'.dol_escape_htmltag($search_vat).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75imp" name="search_societe" value="'.dol_escape_htmltag($search_societe).'"></td>';
	print '<td class="liste_titre">';
	print $form->select_country($search_country, 'search_country', '', 0, 'maxwidth150', 'code2', 1, 0, 1);
	//	print '<input type="text" class="flat maxwidth50" name="search_country" value="' . dol_escape_htmltag($search_country) . '">';
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_tvaintra" value="'.dol_escape_htmltag($search_tvaintra).'"></td>';
	print '<td class="liste_titre center"><input type="text" class="flat maxwidth50" name="search_account" value="'.dol_escape_htmltag($search_account).'"></td>';
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print "</td></tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("LineId", $_SERVER["PHP_SELF"], "l.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("InvoiceLabel", $_SERVER["PHP_SELF"], "f.libelle", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "f.datef, f.ref, l.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("ProductRef", $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("ProductLabel", $_SERVER["PHP_SELF"], "p.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("ProductDescription", $_SERVER["PHP_SELF"], "l.description", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "l.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], "l.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Country", $_SERVER["PHP_SELF"], "co.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("VATIntraShort", $_SERVER["PHP_SELF"], "s.tva_intra", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("AccountAccounting", $_SERVER["PHP_SELF"], "aa.account_number", "", $param, '', $sortfield, $sortorder);
	$checkpicto = $form->showCheckAddButtons();
	print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
	print "</tr>\n";

	$thirdpartystatic = new Societe($db);
	$facturefournisseur_static = new FactureFournisseur($db);
	$productstatic = new ProductFournisseur($db);
	$accountingaccountstatic = new AccountingAccount($db);

	$i = 0;
	while ($i < min($num_lines, $limit))
	{
		$objp = $db->fetch_object($result);

		$facturefournisseur_static->ref = $objp->ref;
		$facturefournisseur_static->id = $objp->facid;

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

		$productstatic->ref = $objp->product_ref;
		$productstatic->id = $objp->product_id;
		$productstatic->label = $objp->product_label;
		$productstatic->type = $objp->line_type;
		$productstatic->status = $objp->tosell;
		$productstatic->status_buy = $objp->tobuy;
		$productstatic->accountancy_code_buy = $objp->accountancy_code_buy;
		$productstatic->accountancy_code_buy_intra = $objp->accountancy_code_sell_buy;
		$productstatic->accountancy_code_buy_export = $objp->accountancy_code_sell_buy;

		$accountingaccountstatic->rowid = $objp->fk_compte;
		$accountingaccountstatic->label = $objp->label;
		$accountingaccountstatic->labelshort = $objp->labelshort;
		$accountingaccountstatic->account_number = $objp->account_number;

		print '<tr class="oddeven">';

		// Line id
		print '<td>'.$objp->rowid.'</td>';

		// Ref Invoice
		print '<td class="nowraponall">'.$facturefournisseur_static->getNomUrl(1).'</td>';

		print '<td class="tdoverflowonsmartphone">';
		print $objp->invoice_label;
		print '</td>';

		// Date invoice
		print '<td class="center">'.dol_print_date($db->jdate($objp->datef), 'day').'</td>';

		// Ref Product
		print '<td class="tdoverflowmax100">';
		if ($productstatic->id > 0) print $productstatic->getNomUrl(1);
		if ($productstatic->id > 0 && $objp->product_label) print '<br>';
		if ($objp->product_label) print '<span class="opacitymedium">'.$objp->product_label.'</span>';
		print '</td>';

		print '<td class="tdoverflowonsmartphone">';
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->description));
		$trunclength = empty($conf->global->ACCOUNTING_LENGTH_DESCRIPTION) ? 32 : $conf->global->ACCOUNTING_LENGTH_DESCRIPTION;
		print $form->textwithtooltip(dol_trunc($text, $trunclength), $objp->description);
		print '</td>';

		print '<td class="nowrap right">'.price($objp->total_ht).'</td>';

		print '<td class="right">'.vatrate($objp->tva_tx.($objp->vat_src_code ? ' ('.$objp->vat_src_code.')' : '')).'</td>';

		// Thirdparty
		print '<td class="tdoverflowmax100">'.$thirdpartystatic->getNomUrl(1, 'supplier').'</td>';

		// Country
		print '<td>';
		if ($objp->country_code)
		{
			print $langs->trans("Country".$objp->country_code).' ('.$objp->country_code.')';
		}
		print '</td>';

		print '<td>'.$objp->tva_intra.'</td>';

		print '<td class="center">';
		print $accountingaccountstatic->getNomUrl(0, 1, 1, '', 1);
		print ' <a class="editfielda" href="./card.php?id='.$objp->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].($param ? '?'.$param : '')).'">';
		print img_edit();
		print '</a></td>';
		print '<td class="center"><input type="checkbox" class="checkforaction" name="changeaccount[]" value="'.$objp->rowid.'"/></td>';

		print '</tr>';
		$i++;
	}
	print '</table>';
	print "</div>";

	if ($nbtotalofrecords > $limit) {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, '', 0, '', '', $limit, 1);
	}

	print '</form>';
} else {
	print $db->lasterror();
}

// End of page
llxFooter();
$db->close();

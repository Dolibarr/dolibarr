<?php
/* Copyright (C) 2013-2016 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014-2015 Ari Elbaz (elarifr)	<github@accedinfo.com>  
 * Copyright (C) 2013-2016 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 * \file 		htdocs/accountancy/supplier/lines.php
 * \ingroup 	Advanced accountancy
 * \brief 		Page of detail of the lines of ventilation of invoices suppliers
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");
$langs->load("productbatch");

$account_parent = GETPOST('account_parent');
$changeaccount = GETPOST('changeaccount');
// Search Getpost
$search_ref = GETPOST('search_ref', 'alpha');
$search_invoice = GETPOST('search_invoice', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$search_vat = GETPOST('search_vat', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit') ? GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)?$conf->liste_limit:$conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page < 0) $page = 0;
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield)
	$sortfield = "f.datef, f.ref, l.rowid";
if (! $sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_DONE > 0) {
		$sortorder = "DESC";
	}
}

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->bind->write)
	accessforbidden();

$formventilation = new FormVentilation($db);


/*
 * Actions
 */

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
{
	$search_ref = '';
	$search_invoice = '';
	$search_label = '';
	$search_desc = '';
	$search_amount = '';
	$search_account = '';
	$search_vat = '';
}

if (is_array($changeaccount) && count($changeaccount) > 0) {
	$error = 0;
	
	$db->begin();
	
	$sql1 = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det as l";
	$sql1 .= " SET l.fk_code_ventilation=" . GETPOST('account_parent');
	$sql1 .= ' WHERE l.rowid IN (' . implode(',', $changeaccount) . ')';
	
	dol_syslog('accountancy/supplier/lines.php::changeaccount sql= ' . $sql1);
	$resql1 = $db->query($sql1);
	if (! $resql1) {
		$error ++;
		setEventMessages($db->lasterror(), null, 'errors');
	}
	if (! $error) {
		$db->commit();
		setEventMessages($langs->trans('Save'), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($db->lasterror(), null, 'errors');
	}

	$account_parent = '';   // Protection to avoid to mass apply it a second time
}


/*
 * View
 */

llxHeader('', $langs->trans("SuppliersVentilation") . ' - ' . $langs->trans("Dispatched"));

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
$sql = "SELECT f.rowid as facid, f.ref as facnumber, f.ref_supplier, f.libelle as invoice_label, f.datef,";
$sql.= " l.fk_product, l.description, l.total_ht , l.qty, l.rowid, l.tva_tx, aa.label, aa.account_number, ";
$sql.= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type";
$sql.= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
$sql.= " , " . MAIN_DB_PREFIX . "accounting_account as aa";
$sql.= " , " . MAIN_DB_PREFIX . "facture_fourn_det as l";
$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
$sql.= " WHERE f.rowid = l.fk_facture_fourn and f.fk_statut >= 1 AND l.fk_code_ventilation <> 0 ";
$sql.= " AND aa.rowid = l.fk_code_ventilation";
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
	$sql .= natural_search("aa.account_number", $search_account, 1);
}
if (strlen(trim($search_vat))) {
	$sql .= natural_search("l.tva_tx", $search_vat, 1);
}
$sql .= " AND f.entity IN (" . getEntity("facture_fourn", 0) . ")";  // We don't share object for accountancy

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/supplier/lines.php::list');
$result = $db->query($sql);

if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;
	
	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($search_invoice)
		$param .= "&search_invoice=" . $search_invoice;
	if ($search_ref)
		$param .= "&search_ref=" . $search_ref;
	if ($search_label)
		$param .= "&search_label=" . $search_label;
	if ($search_desc)
		$param .= "&search_desc=" . $search_desc;
	if ($search_account)
		$param .= "&search_account=" . $search_account;
	if ($search_vat)
		$param .= "&search_vat=" . $search_vat;
	if ($search_country)
		$param .= "&search_country=" . $search_country;
	if ($search_tvaintra)
		$param .= "&search_tvaintra=" . $search_tvaintra;	
	
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">' . "\n";
	print '<input type="hidden" name="action" value="ventil">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		
	print_barre_liste($langs->trans("InvoiceLinesDone"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);
	
	print $langs->trans("DescVentilDoneSupplier") . '<br>';
	
	print '<br><div class="inline-block divButAction">' . $langs->trans("ChangeAccount") . '<br>';
	print $formventilation->select_account(GETPOST('account_parent'), 'account_parent', 1);
	print '<input type="submit" class="button valignmiddle" value="' . $langs->trans("ChangeBinding") . '" /></div>';
	
	$moreforfilter = '';
	
    print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
	
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("LineId"), $_SERVER["PHP_SELF"], "l.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Invoice"), $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("InvoiceLabel"), $_SERVER["PHP_SELF"], "f.libelle", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Date"), $_SERVER["PHP_SELF"], "f.datef, f.ref, l.rowid", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("ProductRef"), $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	//print_liste_field_titre($langs->trans("ProductLabel"), $_SERVER["PHP_SELF"], "p.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"], "l.description", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Amount"), $_SERVER["PHP_SELF"], "l.total_ht", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("VATRate"), $_SERVER["PHP_SELF"], "l.tva_tx", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Account"), $_SERVER["PHP_SELF"], "aa.account_number", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre('', '', '', '', '', 'align="center"');
	print "</tr>\n";
	
	print '<tr class="liste_titre">';
    print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_invoice" value="' . dol_escape_htmltag($search_invoice) . '"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';
	//print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
	print '<td class="liste_titre" align="right"><input type="text" class="flat maxwidth50" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';
	print '<td class="liste_titre" align="center"><input type="text" class="flat maxwidth50" name="search_vat" size="1" value="' . dol_escape_htmltag($search_vat) . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_account" value="' . dol_escape_htmltag($search_account) . '"></td>';
    print '<td class="liste_titre" align="center">';
    $searchpitco=$form->showFilterAndCheckAddButtons(1);
    print $searchpitco;
    print '</td>';
	print "</tr>\n";
	
	$facturefournisseur_static = new FactureFournisseur($db);
	$product_static = new Product($db);
	
	$var = True;
	while ( $i < min($num_lines, $limit) ) {
		$objp = $db->fetch_object($result);
		$var = ! $var;
		$codecompta = length_accountg($objp->account_number) . ' - ' . $objp->label;
		
		$facturefournisseur_static->ref = $objp->facnumber;
		$facturefournisseur_static->id = $objp->facid;
		
		$product_static->ref = $objp->product_ref;
		$product_static->id = $objp->product_id;
		$product_static->type = $objp->type;
		$product_static->label = $objp->product_label;
		
		print '<tr '. $bc[$var].'>';
		
		print '<td>' . $objp->rowid . '</td>';
		
		// Ref Invoice
		print '<td>' . $facturefournisseur_static->getNomUrl(1) . '</td>';
		
		print '<td>';
		print $objp->invoice_label;
		print '</td>';
		
		print '<td align="center">' . dol_print_date($db->jdate($objp->datef), 'day') . '</td>';
		
		// Ref Product
		print '<td>';
		if ($product_static->id)
			print $product_static->getNomUrl(1);
	    if ($objp->product_label) print '<br>'.$objp->product_label;
		print '</td>';
		
		print '<td>';
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->description));
		$trunclength = defined('ACCOUNTING_LENGTH_DESCRIPTION') ? ACCOUNTING_LENGTH_DESCRIPTION : 32;
		print $form->textwithtooltip(dol_trunc($text,$trunclength), $objp->description);
		print '</td>';
		
		print '<td align="right">' . price($objp->total_ht) . '</td>';
		print '<td align="center">' . price($objp->tva_tx) . '</td>';
		print '<td align="left">';
		print $codecompta . ' <a href="./card.php?id=' . $objp->rowid . '">';
		print img_edit();
		print '</a></td>';
		
		print '<td align="right"><input type="checkbox" class="checkforaction" name="changeaccount[]" value="' . $objp->rowid . '"/></td>';
		
		print "</tr>";
		$i ++;
	}
    print "</table>";
    print "</div>";
    
    if ($nbtotalofrecords > $limit) {
        print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, '', 0, '', '', $limit, 1);
    }
    
    print '</form>';
} else {
	print $db->error();
}



llxFooter();
$db->close();

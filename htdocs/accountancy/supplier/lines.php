<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014-2015 Ari Elbaz (elarifr)	<github@accedinfo.com>  
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
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
 * \file htdocs/accountancy/supplier/lines.php
 * \ingroup Accounting Expert
 * \brief Page of detail of the lines of ventilation of invoices suppliers
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");

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

// Getpost Order and column and limit page
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');

if ($page < 0)
	$page = 0;
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)) {
	$limit = $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION;
} else if ($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION <= 0) {
	$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
} else {
	$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
}
if (! $sortfield)
	$sortfield = "f.datef, f.ref, l.rowid";

if (! $sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_DONE > 0) {
		$sortorder = " DESC ";
	}
}

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->ventilation->dispatch)
	accessforbidden();

$formventilation = new FormVentilation($db);

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
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
$sql = "SELECT f.ref as facnumber, f.rowid as facid, l.fk_product, l.description, l.total_ht , l.qty, l.rowid, l.tva_tx, aa.label, aa.account_number, ";
$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
$sql .= " , " . MAIN_DB_PREFIX . "accounting_account as aa";
$sql .= " , " . MAIN_DB_PREFIX . "facture_fourn_det as l";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
$sql .= " WHERE f.rowid = l.fk_facture_fourn and f.fk_statut >= 1 AND l.fk_code_ventilation <> 0 ";
$sql .= " AND aa.rowid = l.fk_code_ventilation";
if (strlen(trim($search_invoice))) {
	$sql .= " AND f.ref like '%" . $search_invoice . "%'";
}
if (strlen(trim($search_ref))) {
	$sql .= " AND p.ref like '%" . $search_ref . "%'";
}
if (strlen(trim($search_label))) {
	$sql .= " AND p.label like '%" . $search_label . "%'";
}
if (strlen(trim($search_desc))) {
	$sql .= " AND l.description like '%" . $search_desc . "%'";
}
if (strlen(trim($search_amount))) {
	$sql .= " AND l.total_ht like '%" . $search_amount . "%'";
}
if (strlen(trim($search_account))) {
	$sql .= " AND aa.account_number like '%" . $search_account . "%'";
}
if (strlen(trim($search_vat))) {
	$sql .= " AND (l.tva_tx like '" . $search_vat . "%')";
}
if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND f.entity IN (" . getEntity("facture_fourn", 1) . ")";
}
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/supplier/lines.php::list sql= ' . $sql1);
$result = $db->query($sql);

if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;
	
	print_barre_liste($langs->trans("InvoiceLinesDone"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num_lines);
	
	print '<td align="left"><b>' . $langs->trans("DescVentilDoneSupplier") . '</b></td>';
	
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<table class="noborder" width="100%">';
	
	print '<br><div class="inline-block divButAction">' . $langs->trans("ChangeAccount") . '<br>';
	print $formventilation->select_account(GETPOST('account_parent'), 'account_parent', 1);
	print '<input type="submit" class="butAction" value="' . $langs->trans("Validate") . '" /></div>';
	
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Invoice"), $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "p.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"], "l.description", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Amount"), $_SERVER["PHP_SELF"], "l.total_ht", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("VATRate"), $_SERVER["PHP_SELF"], "l.tva_tx", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Account"), $_SERVER["PHP_SELF"], "aa.account_number", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre('');
	print_liste_field_titre('');
	print_liste_field_titre($langs->trans("Ventilate") . '<br><label id="select-all">' . $langs->trans('All') . '</label>/<label id="unselect-all">' . $langs->trans('None') . '</label>', '', '', '', '', 'align="center"');
	print "</tr>\n";
	
	print '<tr class="liste_titre"><td><input type="text" class="flat" name="search_invoice" size="10" value="' . $search_invoice . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_ref" value="' . $search_ref . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_label" value="' . $search_label . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_desc" value="' . $search_desc . '"></td>';
	print '<td class="liste_titre" align="center"><input type="text" class="flat" size="8" name="search_amount" value="' . $search_amount . '"></td>';
	print '<td class="liste_titre" align="center"><input type="text" class="flat" size="5" name="search_vat" value="' . $search_vat . '">%</td>';
	print '<td class="liste_titre" align="center"><input type="text" class="flat" size="15" name="search_account" value="' . $search_account . '"></td>';
	print '<td class="liste_titre" colspan="2">&nbsp;</td>';
	print '<td class="liste_titre" align="center"><input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print "</td></tr>\n";
	
	$facturefournisseur_static = new FactureFournisseur($db);
	$product_static = new Product($db);
	
	$var = True;
	while ( $i < min($num_lines, $limit) ) {
		$objp = $db->fetch_object($result);
		$var = ! $var;
		$codeCompta = $objp->account_number . ' ' . $objp->label;
		
		print "<tr $bc[$var]>";
		
		// Ref Invoice
		$facturefournisseur_static->ref = $objp->facnumber;
		$facturefournisseur_static->id = $objp->facid;
		print '<td>' . $facturefournisseur_static->getNomUrl(1) . '</td>';
		
		// Ref Product
		$product_static->ref = $objp->product_ref;
		$product_static->id = $objp->product_id;
		$product_static->type = $objp->type;
		print '<td>';
		if ($product_static->id)
			print $product_static->getNomUrl(1);
		else
			print '&nbsp;';
		print '</td>';
		
		print '<td>' . dol_trunc($objp->product_label, 24) . '</td>';
		print '<td>' . nl2br(dol_trunc($objp->description, 32)) . '</td>';
		print '<td align="right">' . price($objp->total_ht) . '</td>';
		print '<td align="center">' . price($objp->tva_tx) . '</td>';
		print '<td align="center">' . $codeCompta . '</td>';
		print '<td align="right">' . $objp->rowid . '</td>';
		print '<td align="left"><a href="./card.php?id=' . $objp->rowid . '">';
		print img_edit();
		print '</a></td>';
		
		print '<td align="center"><input type="checkbox" name="changeaccount[]" value="' . $objp->rowid . '"/></td>';
		
		print "</tr>";
		$i ++;
	}
} else {
	print $db->error();
}

print "</table></form>";

llxFooter();
$db->close();

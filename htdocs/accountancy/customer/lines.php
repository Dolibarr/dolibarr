<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro	<alexandre.spangaro@gmail.com>
 * Copyright (C) 2014      Ari Elbaz (elarifr)	<github@accedinfo.com>
 * Copyright (C) 2014      Florian Henry		<florian.henry@open-concept.pro>
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
 *
 */

/**
 * \file		htdocs/accountancy/customer/lines.php
 * \ingroup		Accounting Expert
 * \brief		Page of detail of the lines of ventilation of invoices customers
 */

require '../../main.inc.php';
	
// Class
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// langs
$langs->load("bills");
$langs->load("compta");
$langs->load("main");
$langs->load("accountancy");

$account_parent = GETPOST('account_parent');

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->ventilation->dispatch)
	accessforbidden();

$formventilation = new FormVentilation($db);

// change account

$changeaccount = GETPOST('changeaccount');

$is_search = GETPOST('button_search_x');

if (is_array($changeaccount) && count($changeaccount) > 0 && empty($is_search)) {
	$error = 0;
	
	$db->begin();
	
	$sql1 = "UPDATE " . MAIN_DB_PREFIX . "facturedet as l";
	$sql1 .= " SET l.fk_code_ventilation=" . $account_parent;
	$sql1 .= ' WHERE l.rowid IN (' . implode(',', $changeaccount) . ')';
	
	dol_syslog('accountancy/customer/lines.php::changeaccount sql= ' . $sql1);
	$resql1 = $db->query($sql1);
	if (! $resql1) {
		$error ++;
		setEventMessage($db->lasterror(), 'errors');
	}
	if (! $error) {
		$db->commit();
		setEventMessage($langs->trans('Save'), 'mesgs');
	} else {
		$db->rollback();
		setEventMessage($db->lasterror(), 'errors');
	}
}

/*
 * View
 */

llxHeader('', $langs->trans("CustomersVentilation") . ' - ' . $langs->trans("Dispatched"));

$page = GETPOST("page");
if ($page < 0)
	$page = 0;

if (! empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)) {
	$limit = $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION;
} elseif ($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION <= 0) {
	$limit = $conf->liste_limit;
} else {
	$limit = $conf->liste_limit;
}

$offset = $limit * $page;

$sql = "SELECT l.rowid , f.facnumber, f.rowid as facid, l.fk_product, l.description, l.total_ht, l.qty, l.tva_tx, l.fk_code_ventilation, aa.label, aa.account_number,";
$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
$sql .= " , " . MAIN_DB_PREFIX . "accountingaccount as aa";
$sql .= " , " . MAIN_DB_PREFIX . "facturedet as l";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
$sql .= " WHERE f.rowid = l.fk_facture AND f.fk_statut >= 1 AND l.fk_code_ventilation <> 0 ";
$sql .= " AND aa.rowid = l.fk_code_ventilation";
if (strlen(trim(GETPOST("search_facture")))) {
	$sql .= " AND f.facnumber like '%" . GETPOST("search_facture") . "%'";
}
if (strlen(trim(GETPOST("search_ref")))) {
	$sql .= " AND p.ref like '%" . GETPOST("search_ref") . "%'";
}
if (strlen(trim(GETPOST("search_label")))) {
	$sql .= " AND p.label like '%" . GETPOST("search_label") . "%'";
}
if (strlen(trim(GETPOST("search_desc")))) {
	$sql .= " AND l.description like '%" . GETPOST("search_desc") . "%'";
}
if (strlen(trim(GETPOST("search_account")))) {
	$sql .= " AND aa.account_number like '%" . GETPOST("search_account") . "%'";
}

if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND f.entity = '" . $conf->entity . "'";
}

$sql .= " ORDER BY l.rowid";
if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_DONE > 0) {
	$sql .= " DESC ";
}
$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("/accountancy/customer/lines.php sql=" . $sql, LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;
	
	// TODO : print_barre_liste always use $conf->liste_limit and do not care about custom limit in list...
	print_barre_liste($langs->trans("InvoiceLinesDone"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num_lines);
	
	print '<td align="left"><b>' . $langs->trans("DescVentilDoneCustomer") . '</b></td>';
	
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<table class="noborder" width="100%">';
	
	print '<br><br><div class="inline-block divButAction">' . $langs->trans("ChangeAccount");
	print $formventilation->select_account($account_parent, 'account_parent', 1);
	print '<input type="submit" class="butAction" value="' . $langs->trans("Validate") . '"/></div>';
	
	print '<tr class="liste_titre"><td>' . $langs->trans("Invoice") . '</td>';
	print '<td>' . $langs->trans("Ref") . '</td>';
	print '<td>' . $langs->trans("Label") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td align="left">' . $langs->trans("Amount") . '</td>';
	print '<td colspan="2" align="left">' . $langs->trans("Account") . '</td>';
	print '<td align="center">&nbsp;</td>';
	print '<td align="center">&nbsp;</td>';
	print "</tr>\n";
	
	print '<tr class="liste_titre"><td><input name="search_facture" size="8" value="' . GETPOST("search_facture") . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_ref" value="' . GETPOST("search_ref") . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_label" value="' . GETPOST("search_label") . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_desc" value="' . GETPOST("search_desc") . '"></td>';
	print '<td align="right">&nbsp;</td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="15" name="search_account" value="' . GETPOST("search_account") . '"></td>';
	print '<td align="center">&nbsp;</td>';
	print '<td align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" alt="' . $langs->trans("Search") . '">';
	print '</td>';
	print '<td align="center">&nbsp;</td>';
	print "</tr>\n";
	
	$facture_static = new Facture($db);
	$product_static = new Product($db);
	
	$var = True;
	while ( $objp = $db->fetch_object($result) ) {
		$var = ! $var;
		$codeCompta = $objp->account_number . ' ' . $objp->label;
		
		print "<tr $bc[$var]>";
		
		// Ref facture
		$facture_static->ref = $objp->facnumber;
		$facture_static->id = $objp->facid;
		print '<td>' . $facture_static->getNomUrl(1) . '</td>';
		
		// Ref produit
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
		print '<td align="left">' . price($objp->total_ht) . '</td>';
		print '<td align="left">' . $codeCompta . '</td>';
		print '<td>' . $objp->rowid . '</td>';
		print '<td><a href="./card.php?id=' . $objp->rowid . '">';
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
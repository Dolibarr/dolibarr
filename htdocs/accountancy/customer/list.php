<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2015 Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
 * \file		htdocs/accountancy/customer/list.php
 * \ingroup		Accounting Expert
 * \brief		Ventilation page from customers invoices
 */

require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");

$action = GETPOST('action');
$codeventil = GETPOST('codeventil', 'array');
$mesCasesCochees = GETPOST('mesCasesCochees', 'array');
$search_ref     = GETPOST('search_ref','alpha');
$search_label   = GETPOST('search_label','alpha');
$search_desc    = GETPOST('search_desc','alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
//Should move to top with all GETPOST
$page = GETPOST('page');
if ($page < 0) $page = 0;

if (! empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)) {
	$limit = $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION;
} else if ($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION <= 0) {
	$limit = $conf->liste_limit;
} else {
	$limit = $conf->liste_limit;
}
$offset = $limit * $page;
//End Should move to top with all GETPOST

// TODO : remove comment 
//elarifr we can not use only
//$sql .= " ORDER BY l.rowid";
// f.datef will order like FA08 FA09 FA10 FA05 FA06 FA07 FA04...
// f.facnumber will not order properly invoice / avoir / accompte you can have All AC then All AV and all FA
// l.rowid when an invoice is edited rowid are added at end of table & facturedet.rowid are not ordered
//if (! $sortfield) $sortfield="l.rowid";
if (! $sortfield) $sortfield="f.datef, f.facnumber, l.rowid";
//if (! $sortorder) $sortorder="DESC";
if (! $sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO > 0) {
		$sortorder = " DESC ";
	}
}

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->ventilation->dispatch)
	accessforbidden();

$formventilation = new FormVentilation($db);

//Defaut AccountingAccount RowId Product / Service
//at this time ACCOUNTING_SERVICE_SOLD_ACCOUNT & ACCOUNTING_PRODUCT_SOLD_ACCOUNT are account number not accountingacount rowid
//so we need to get those default value rowid first
$accounting = new AccountingAccount($db);
//TODO: we should need to check if result is a really exist accountaccount rowid.....
$aarowid_s = $accounting->fetch('', ACCOUNTING_SERVICE_SOLD_ACCOUNT);
$aarowid_p = $accounting->fetch('', ACCOUNTING_PRODUCT_SOLD_ACCOUNT);

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_ref='';
    $search_label='';
    $search_desc='';
}
/*
 * View
 */

llxHeader('', $langs->trans("Ventilation"));

//debug
//print_r($aarowid_s);
//print_r($aarowid_p);

print  '<script type="text/javascript">
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
 * Action
 */

if ($action == 'ventil') {
	print '<div><font color="red">' . $langs->trans("Processing") . '...</font></div>';
	if (! empty($codeventil) && ! empty($mesCasesCochees)) {
		print '<div><font color="red">' . count($mesCasesCochees) . ' ' . $langs->trans("SelectedLines") . '</font></div>';
		$mesCodesVentilChoisis = $codeventil;
		$cpt = 0;
		foreach ( $mesCasesCochees as $maLigneCochee ) {
			// print '<div><font color="red">id selectionnee : '.$monChoix."</font></div>";
			$maLigneCourante = explode("_", $maLigneCochee);
			$monId = $maLigneCourante[0];
			$monNumLigne = $maLigneCourante[1];
			$monCompte = $mesCodesVentilChoisis[$monNumLigne];

			$sql = " UPDATE " . MAIN_DB_PREFIX . "facturedet";
			$sql .= " SET fk_code_ventilation = " . $monCompte;
			$sql .= " WHERE rowid = " . $monId;

			dol_syslog("/accountancy/customer/list.php sql=" . $sql, LOG_DEBUG);
			if ($db->query($sql)) {
				print '<div><font color="green">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' ' . $langs->trans("VentilatedinAccount") . ' : ' . $monCompte . '</font></div>';
			} else {
				print '<div><font color="red">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' ' . $langs->trans("NotVentilatedinAccount") . ' : ' . $monCompte . '<br/> <pre>' . $sql . '</pre></font></div>';
			}

			$cpt ++;
		}
	} else {
		print '<div><font color="red">' . $langs->trans("AnyLineVentilate") . '</font></div>';
	}
	print '<div><font color="red">' . $langs->trans("EndProcessing") . '</font></div>';
}

/*
 * Customer Invoice lines
 */
$page = GETPOST('page');
if ($page < 0)
	$page = 0;

if (! empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)) {
	$limit = $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION;
} else if ($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION <= 0) {
	$limit = $conf->liste_limit;
} else {
	$limit = $conf->liste_limit;
}

$offset = $limit * $page;

$sql = "SELECT f.facnumber, f.rowid as facid, l.fk_product, l.description, l.total_ht, l.rowid, l.fk_code_ventilation,";
$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.accountancy_code_sell as code_sell";
// A REVOIR elarifr si vraiment necessaire de rajouter , p.fk_product_type as type. le type produit / service est de facto defini pour chaque ligne de facturedet.product_type
// il est donc plus logique de se servir de l.product_type au lieu de p.fk_product_type
$sql .= " , aa.rowid as aarowid";
// we need f.datef to reorder lines
$sql .= " , f.datef";
// we need to use llx_facturedet l.product_type as used at the time on invoice. if llx_product fk_product_type is changed later it could not change the sell already made !
$sql .= " , l.product_type as type_l";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as l ON f.rowid = l.fk_facture";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accountingaccount as aa ON p.accountancy_code_sell = aa.account_number";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_system as accsys ON accsys.pcg_version = aa.fk_pcg_version";
$sql .= " WHERE f.fk_statut > 0 AND fk_code_ventilation <= 0";
$sql .= " AND (accsys.rowid='" . $conf->global->CHARTOFACCOUNTS . "' OR p.accountancy_code_sell IS NULL OR p.accountancy_code_sell ='')";
// Add search filter like
if (strlen(trim($search_ref))) {
	$sql .= " AND (p.ref like '%" . $search_ref . "%')";
}
if (strlen(trim($search_label))) {
	$sql .= " AND (p.label like '%" . $search_label . "%')";
}
if (strlen(trim($search_desc))) {
	$sql .= " AND (l.description like '%" . $search_desc . "%')";
}
if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND f.entity IN (" . getEntity("facture", 1) . ")";
}
//TODO: Remove comment
//replaced by default value $sortfield,$sortorder
//$sql .= " ORDER BY l.rowid";
//if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO > 0) {
//	$sql .= " DESC ";
//}
$sql.= $db->order($sortfield,$sortorder);

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("/accountancy/customer/list.php sql=" . $sql, LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	// TODO : print_barre_liste always use $conf->liste_limit and do not care about custom limit in list...
	print_barre_liste($langs->trans("InvoiceLines"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num_lines);

	print '<br><b>' . $langs->trans("DescVentilTodoCustomer") . '</b></br>';

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">' . "\n";
	print '<input type="hidden" name="action" value="ventil">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Invoice"), $_SERVER["PHP_SELF"],"f.facnumber","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"],"p.label","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"],"l.description","",$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Amount"),'','','','','align="right"');
	print_liste_field_titre($langs->trans("AccountAccounting"),'','','','','align="center"');
	print_liste_field_titre($langs->trans("IntoAccount"),'','','','','align="center"');
	print_liste_field_titre('');
	print_liste_field_titre($langs->trans("Ventilate") . '<br><label id="select-all">'.$langs->trans('All').'</label>/<label id="unselect-all">'.$langs->trans('None').'</label>','','','','','align="center"');
	print '</tr>';

//	We add search filter
/*	But Hit Enter will validate ventilation....
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" >&nbsp;</td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_ref" value="' . $search_ref . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_label" value="' . $search_label . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="30" name="search_desc" value="' . $search_desc . '"></td>';

	print '<td class="liste_titre" colspan="3">&nbsp;</td>';
	print '<td align="right" colspan="2" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp;';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print '</tr>';
*/
	$facture_static = new Facture($db);
	$product_static = new Product($db);
	$form = new Form($db);

	$var = true;
	while ( $i < min($num_lines, $limit) ) {
		$objp = $db->fetch_object($result);
		$var = ! $var;

		// product_type: 0 = service ? 1 = product
		// C'est le contraire dans les base !!!!!!  IT IS INVERTED IN LLX_PRODUCT & LLX_FACTUREDET
		// elarifr define account numbercode comptable si pas defini dans la fiche produit a partir des lignes de facturation
		// product_type: 1 = service ? 0 = product
		// because some modules like subtotal module l.product_type can be other than 0 or 1   ! and we don't put in account lines with product_type=9
		// first we check product.fk_product_type as type and l.product_type as type_l
		// if product does not exist we use the value of l.product_type provided in facturedet to define if this is a product or service
		// issue : if we change product_type value in product DB it should differ from the value stored in facturedet DB ! so we report both and user make choice of accounting account.
		$objp->code_sell_l = '';
		$objp->code_sell_p = '';
		$objp->aarowid_suggest = '';
		$code_sell_p_l_differ = '';

		//check if code_sell defined in product or set default value according p.fk_product_type do not care lines when product_type value not 0 || 1
		//and we set suggested accounting account rowid as $objp->aarowid_s
		$code_sell_p_notset = '';
		$objp->aarowid_suggest = $objp->aarowid;
		if ( ! empty($objp->code_sell)) {
			$objp->code_sell_p = $objp->code_sell;
		} else {
			$code_sell_p_notset = 'color:red';
			if ($objp->type == 1) {
				$objp->code_sell_p = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
			}
			elseif ($objp->type == 0) {
				$objp->code_sell_p = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
			}
		}
		// check facturedet.product_type & set default value according l.type_l, do not care lines when product_type value not 0 || 1
//		if ( ! empty($objp->type_l)) {
//			$objp->code_sell_l = $objp->type_l;
//		} else {
			if ($objp->type_l == 1) {
				$objp->code_sell_l = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
				if ($objp->aarowid == '') $objp->aarowid_suggest = $aarowid_s;
			}
			elseif ($objp->type_l == 0) {
				$objp->code_sell_l = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
				if ($objp->aarowid == '') $objp->aarowid_suggest = $aarowid_p;
			}
//		}


		//if not same code for product fk_prouct_type and facturedet.product_type, product has been change after sale and must report
		if ($objp->code_sell_l <> $objp->code_sell_p) $code_sell_p_l_differ = 'color:red';

		print "<tr $bc[$var]>";

		// Ref Invoice
		$facture_static->ref = $objp->facnumber;
		$facture_static->id = $objp->facid;
		print '<td>' . $facture_static->getNomUrl(1) . '</td>';
		// Ref Customer Invoice
		$product_static->ref = $objp->product_ref;
		$product_static->id = $objp->product_id;
		$product_static->type = $objp->type;
		print '<td>';
		if ($product_static->id)
			print $product_static->getNomUrl(1);
		else
			print '&nbsp;';
		print '</td>';

		print '<td style="' . $code_sell_p_l_differ . '">' . dol_trunc($objp->product_label, 24) . '</td>';
		//TODO: we should set a user defined value to adjust user square / wide screen size
		$trunclength = defined('ACCOUNTING_LENGTH_DESCRIPTION') ? ACCOUNTING_LENGTH_DESCRIPTION : 32;
		print '<td style="' . $code_sell_p_l_differ . '">' . nl2br(dol_trunc($objp->description, $trunclength)) . '</td>';

		print '<td align="right">';
		print price($objp->total_ht);
		print '</td>';

		print '<td align="center" style="' . $code_sell_p_notset . '">';
		// if not same kind of product_type stored in product & facturedet we display both account and let user choose
		if ($objp->code_sell_l == $objp->code_sell_p) {
			print $objp->code_sell_l;
		} else {
			print 'lines='.$objp->code_sell_l . '<br />product=' . $objp->code_sell_p;
		}
		print '</td>';

		// Colonne choix du compte
		print '<td align="center">';
		// TODO: we should set a user defined value to adjust user square / wide screen size
		// $trunclengthform = defined('ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT') ? ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT : 50;
		print $formventilation->select_account($objp->aarowid_suggest, 'codeventil[]', 1);
		print '</td>';

		print '<td align="center">' . $objp->rowid . '</td>';
		// Colonne choix ligne a ventiler
		print '<td align="center">';
		//TODO checked only if account exist in product, if only suggested do not check, user must validate 
		print '<input type="checkbox" name="mesCasesCochees[]" value="' . $objp->rowid . "_" . $i . '"' . ($objp->aarowid_suggest ? "checked" : "") . '/>';
		print '</td>';
//debug
//print '</tr><tr><td colspan=6>Product: p.type='. $objp->type .' - p.code_sell='. $objp->code_sell .'  --- Check code_sell_product=' . $objp->code_sell_p .'  ---Check facturedet l.type_l='. $objp->type_l .' - code_sell_lines=' . $objp->code_sell_l . '  -- aarowid_suggest=' . $objp->aarowid_suggest.'</td>';
		print '</tr>';
		$i ++;
	}

	print '</table>';
	print '<br><div align="center"><input type="submit" class="butAction" value="' . $langs->trans("Ventilate") . '"></div>';
	print '</form>';
} else {
	print $db->error();
}

llxFooter();
$db->close();

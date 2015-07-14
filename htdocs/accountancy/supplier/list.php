<?php
/* Copyright (C) 2013-2014	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2013-2015	Alexandre Spangaro		<alexandre.spangaro@gmail.com>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)		<github@accedinfo.com>
 * Copyright (C) 2013-2014	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Juanjo Menent			<jmenent@2byte.es>s
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
 * \file		htdocs/accountancy/supplier/list.php
 * \ingroup		Accounting Expert
 * \brief		Ventilation page from suppliers invoices
 */

require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
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
// f.ref will not order properly invoice / avoir / accompte you can have All AC then All AV and all FA
// l.rowid when an invoice is edited rowid are added at end of table & facturedet.rowid are not ordered
//if (! $sortfield) $sortfield="l.rowid";
if (! $sortfield) $sortfield="f.datef, f.ref, l.rowid";
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
$aarowid_s = $accounting->fetch('', ACCOUNTING_SERVICE_BUY_ACCOUNT);
$aarowid_p = $accounting->fetch('', ACCOUNTING_PRODUCT_BUY_ACCOUNT);

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
	if ($_POST['codeventil'] && $_POST["mesCasesCochees"]) {
		print '<div><font color="red">' . count($_POST["mesCasesCochees"]) . ' ' . $langs->trans("SelectedLines") . '</font></div>';
		$mesCodesVentilChoisis = $codeventil;
		$cpt = 0;

		foreach ( $mesCasesCochees as $maLigneCochee ) {
			// print '<div><font color="red">id selectionnee : '.$monChoix."</font></div>";
			$maLigneCourante = explode("_", $maLigneCochee);
			$monId = $maLigneCourante[0];
			$monNumLigne = $maLigneCourante[1];
			$monCompte = $mesCodesVentilChoisis[$monNumLigne];

			$sql = " UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det";
			$sql .= " SET fk_code_ventilation = " . $monCompte;
			$sql .= " WHERE rowid = " . $monId;

			dol_syslog('accountancy/supplier/list.php:: sql=' . $sql, LOG_DEBUG);
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
 * Supplier Invoice Lines
 *
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

$sql = "SELECT f.ref, f.rowid as facid, f.ref_supplier, l.fk_product, l.description, l.total_ht as price, l.rowid, l.fk_code_ventilation, ";
$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.accountancy_code_buy as code_buy";
$sql .= " , aa.rowid as aarowid";
$sql .= " , f.datef";
$sql .= " , l.product_type as type_l";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as l ON f.rowid = l.fk_facture_fourn";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accountingaccount as aa ON p.accountancy_code_buy = aa.account_number";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_system as accsys ON accsys.pcg_version = aa.fk_pcg_version";
$sql .= " WHERE f.fk_statut > 0 AND fk_code_ventilation <= 0";
$sql .= " AND (accsys.rowid='".$conf->global->CHARTOFACCOUNTS."' OR p.accountancy_code_sell IS NULL OR p.accountancy_code_buy ='')";

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
	$sql .= " AND f.entity IN (" . getEntity("facture_fourn", 1) . ")";
}

$sql.= $db->order($sortfield,$sortorder);

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/supplier/list.php:: $sql=' . $sql);
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
	print_liste_field_titre($langs->trans("Invoice"), $_SERVER["PHP_SELF"],"f.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"],"p.label","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"],"l.description","",$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Amount"),'','','','','align="right"');
	print_liste_field_titre($langs->trans("AccountAccounting"),'','','','','align="center"');
	print_liste_field_titre($langs->trans("IntoAccount"),'','','','','align="center"');
    print_liste_field_titre('');
	print_liste_field_titre($langs->trans("Ventilate") . '<br><label id="select-all">'.$langs->trans('All').'</label>/<label id="unselect-all">'.$langs->trans('None').'</label>','','','','','align="center"');
	print "</tr>\n";

	$facturefourn_static = new FactureFournisseur($db);
	$productfourn_static = new ProductFournisseur($db);
	$form = new Form($db);

	$var = True;
	while ( $i < min($num_lines, $limit) ) {
		$objp = $db->fetch_object($result);
		$var = ! $var;
		
		// product_type: 0 = service ? 1 = product
		// if product does not exist we use the value of product_type provided in facturedet to define if this is a product or service
		// issue : if we change product_type value in product DB it should differ from the value stored in facturedet DB !
		$objp->code_buy_l = '';
		$objp->code_buy_p = '';
		$objp->aarowid_suggest = '';
		$code_buy_p_l_differ = '';

		$code_buy_p_notset = '';

		$objp->aarowid_suggest = $objp->aarowid;
		if ( ! empty($objp->code_buy)) {
			$objp->code_buy_p = $objp->code_buy;
		} else {
			$code_buy_p_notset = 'color:red';
			if ($objp->type == 1) {
				$objp->code_buy_p = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));


			}

			elseif ($objp->type == 0) {
				$objp->code_buy_p = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
			}
		}
		
			if ($objp->type_l == 1) {
				$objp->code_buy_l = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
				if ($objp->aarowid == '') $objp->aarowid_suggest = $aarowid_s;
			}
			elseif ($objp->type_l == 0) {
				$objp->code_buy_l = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
				if ($objp->aarowid == '') $objp->aarowid_suggest = $aarowid_p;
			}
		
		if ($objp->code_buy_l <> $objp->code_buy_p) $code_buy_p_l_differ = 'color:red';
		
		print "<tr $bc[$var]>";

		// Ref Invoice
		$facturefourn_static->ref = $objp->ref;
		$facturefourn_static->id = $objp->facid;
		print '<td>' . $facturefourn_static->getNomUrl(1) . '</td>';

		// Ref Supplier Invoice
		$productfourn_static->ref = $objp->product_ref;
		$productfourn_static->id = $objp->product_id;
		$productfourn_static->type = $objp->type;
		print '<td>';
		if ($productfourn_static->id)
			print $productfourn_static->getNomUrl(1);
		else
			print '&nbsp;';
		print '</td>';

		print '<td style="' . $code_buy_p_l_differ . '">' . dol_trunc($objp->product_label, 24) . '</td>';

		// TODO: we should set a user defined value to adjust user square / wide screen size
		$trunclength = defined('ACCOUNTING_LENGTH_DESCRIPTION') ? ACCOUNTING_LENGTH_DESCRIPTION : 32;
		print '<td style="' . $code_buy_p_l_differ . '">' . nl2br(dol_trunc($objp->description, $trunclength)) . '</td>';

		print '<td align="right">';
		print price($objp->price);
		print '</td>';

		print '<td align="center" style="' . $code_buy_p_notset . '">';
		//if not same kind of product_type stored in product & facturedt we display both account and let user choose
		if ($objp->code_buy_l == $objp->code_buy_p) {
			print $objp->code_buy_l;
		} else {
			print 'lines='.$objp->code_buy_l . '<br />product=' . $objp->code_buy_p;
		}
		print '</td>';

		// Colonne choix du compte
		print '<td align="center">';
		print $formventilation->select_account($objp->aarowid_suggest, 'codeventil[]', 1);
		print '</td>';
		print '<td align="center">' . $objp->rowid . '</td>';
		// Colonne choix ligne a ventiler
		print '<td align="center">';
		print '<input type="checkbox" name="mesCasesCochees[]" value="' . $objp->rowid . "_" . $i . '"' . ($objp->aarowid_suggest ? "checked" : "") . '/>';
		print '</td>';

		print "</tr>";
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
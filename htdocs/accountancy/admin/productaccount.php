<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014 	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014 	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Ari Elbaz (elarifr)	<github@accedinfo.com>
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
 * \file 	htdocs/accountancy/admin/productaccount.php
 * \ingroup Accounting Expert
 * \brief 	To define accounting account on product / service
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

// Langs
$langs->load("companies");
$langs->load("compta");
$langs->load("main");
$langs->load("accountancy");

// Security check
if (!$user->admin) accessforbidden();
if (empty($conf->accounting->enabled)) accessforbidden();
 
// Search & action GETPOST
$action = GETPOST('action');
$codeventil_buy = GETPOST('codeventil_buy', 'array');
$codeventil_sell = GETPOST('codeventil_sell', 'array');
$chk_prod = GETPOST('chk_prod', 'array');
$account_number_buy = GETPOST('account_number_buy');
$account_number_sell = GETPOST('account_number_sell');
$changeaccount = GETPOST('changeaccount', 'array');
$changeaccount_buy = GETPOST('changeaccount_buy', 'array');
$changeaccount_sell = GETPOST('changeaccount_sell', 'array');
$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$accounting_product_mode = GETPOST('accounting_product_mode', 'alpha');
$btn_changeaccount = GETPOST('changeaccount');
$btn_changetype = GETPOST('changetype');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page < 0)
	$page = 0;
$pageprev = $page - 1;
$pagenext = $page + 1;
// bug in page limit if ACCOUNTING_LIMIT_LIST_VENTILATION < $conf->liste_limit there is no pagination displayed !
if (! empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION) && $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION >= $conf->liste_limit) {
	$limit = $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION;
} else {
	$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
}
$offset = $limit * $page;

if (! $sortfield)
	$sortfield = "p.ref";
if (! $sortorder)
	$sortorder = "ASC";

// Sales or Purchase mode ?
if ($action == 'update') {
	if (! empty($btn_changetype)) {
		$error = 0;
		
		$accounting_product_modes = array (
				'ACCOUNTANCY_SELL',
				'ACCOUNTANCY_BUY' 
		);
		
		$accounting_product_mode = GETPOST('accounting_product_mode', 'alpha');
		
		if (in_array($accounting_product_mode, $accounting_product_modes)) {
			
			if (! dolibarr_set_const($db, 'ACCOUNTING_PRODUCT_MODE', $accounting_product_mode, 'chaine', 0, '', $conf->entity)) {
				$error ++;
			}
		} else {
			$error ++;
		}
	}
	
	if (! empty($btn_changeaccount)) {
		$msg = '<div><font color="red">' . $langs->trans("Processing") . '...</font></div>';
		if (! empty($chk_prod)) {
			
			$accounting = new AccountingAccount($db);
			
			$msg .= '<div><font color="red">' . count($chk_prod) . ' ' . $langs->trans("SelectedLines") . '</font></div>';
			
			$cpt = 0;
			foreach ( $chk_prod as $productid ) {
				
				$accounting_account_id = GETPOST('codeventil_' . $productid);
				
				$result = $accounting->fetch($accounting_account_id, null, 1);
				if ($result < 0) {
					// setEventMessages(null, $accounting->errors, 'errors');
					$msg .= '<div><font color="red">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Product") . ' ' . $productid . ' ' . $langs->trans("NotVentilatedinAccount") . ' : id=' . $accounting_account_id . '<br/> <pre>' . $sql . '</pre></font></div>';
				} else {
					
					$sql = " UPDATE " . MAIN_DB_PREFIX . "product";
					if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
						$sql .= " SET accountancy_code_buy = " . $accounting->account_number;
					}
					if ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
						$sql .= " SET accountancy_code_sell = " . $accounting->account_number;
					}
					$sql .= " WHERE rowid = " . $productid;
					
					dol_syslog("/accountancy/admin/productaccount.php sql=" . $sql, LOG_DEBUG);
					if ($db->query($sql)) {
						$msg .= '<div><font color="green">' . $langs->trans("Product") . ' ' . $productid . ' ' . $langs->trans("VentilatedinAccount") . ' : ' . length_accountg($accounting->account_number) . '</font></div>';
					} else {
						$msg .= '<div><font color="red">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Product") . ' ' . $productid . ' ' . $langs->trans("NotVentilatedinAccount") . ' : ' . length_accountg($accounting->account_number) . '<br/> <pre>' . $sql . '</pre></font></div>';
					}
				}
				
				$cpt ++;
			}
		} else {
			$msg .= '<div><font color="red">' . $langs->trans("AnyLineVentilate") . '</font></div>';
		}
		$msg .= '<div><font color="red">' . $langs->trans("EndProcessing") . '</font></div>';
	}
}

$form = new FormVentilation($db);

// Defaut AccountingAccount RowId Product / Service
// at this time ACCOUNTING_SERVICE_SOLD_ACCOUNT & ACCOUNTING_PRODUCT_SOLD_ACCOUNT are account number not accountingacount rowid
// so we need to get those default value rowid first
$accounting = new AccountingAccount($db);
// TODO: we should need to check if result is a really exist accountaccount rowid.....
$aarowid_servbuy = $accounting->fetch('', $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT, 1);
$aarowid_prodbuy = $accounting->fetch('', $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT, 1);
$aarowid_servsell = $accounting->fetch('', $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT, 1);
$aarowid_prodsell = $accounting->fetch('', $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT, 1);

$aacompta_servbuy = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
$aacompta_prodbuy = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
$aacompta_servsell = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
$aacompta_prodsell = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_ref = '';
	$search_label = '';
	$search_desc = '';
}

/*
 * View
 */
 
llxHeader('', $langs->trans("Accounts"));

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

$sql = "SELECT p.rowid, p.ref , p.label, p.description , p.accountancy_code_sell, p.accountancy_code_buy, p.tms, p.fk_product_type as product_type";
$sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
$sql .= " WHERE (";

$pcgver = $conf->global->CHARTOFACCOUNTS;

if ($accounting_product_mode == 'ACCOUNTANCY_BUY' ? ' checked' : '') {
	$sql .= " p.accountancy_code_buy ='' OR p.accountancy_code_buy IS NULL";
	$sql .= " OR (p.accountancy_code_buy  IS NOT NULL AND p.accountancy_code_buy  != '' AND p.accountancy_code_buy  NOT IN
	(SELECT aa.account_number FROM " . MAIN_DB_PREFIX . "accounting_account as aa , " . MAIN_DB_PREFIX . "accounting_system as asy  WHERE fk_pcg_version = asy.pcg_version AND asy.rowid = " . $pcgver . "))";
} else {
	$sql .= " p.accountancy_code_sell ='' OR p.accountancy_code_sell IS NULL ";
	$sql .= " OR (p.accountancy_code_sell IS NOT NULL AND p.accountancy_code_sell != '' AND p.accountancy_code_sell NOT IN
	(SELECT aa.account_number FROM " . MAIN_DB_PREFIX . "accounting_account as aa , " . MAIN_DB_PREFIX . "accounting_system as asy  WHERE fk_pcg_version = asy.pcg_version AND asy.rowid = " . $pcgver . "))";
}

$sql .= ")";

// Add search filter like
if (strlen(trim($search_ref))) {
	$sql .= " AND (p.ref like '" . $search_ref . "%')";
}
if (strlen(trim($search_label))) {
	$sql .= " AND (p.label like '" . $search_label . "%')";
}
if (strlen(trim($search_desc))) {
	$sql .= " AND (p.description like '%" . $search_desc . "%')";
}
$sql .= $db->order($sortfield, $sortorder);

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("/accountancy/admin/productaccount.php:: sql=" . $sql, LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	print load_fiche_titre($langs->trans("InitAccountancy"),'','title_setup');
	print '<br>';
	print $langs->trans("InitAccountancyDesc").'<br>';
	print '<br>';

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="update">';
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans('Options') . '</td><td>' . $langs->trans('Description') . '</td>';
	print "</tr>\n";
	print '<tr ' . $bc[false] . '><td width="25%"><input type="radio" name="accounting_product_mode" value="ACCOUNTANCY_SELL"' . ($accounting_product_mode != 'ACCOUNTANCY_BUY' ? ' checked' : '') . '> ' . $langs->trans('OptionModeProductSell') . '</td>';
	print '<td colspan="2">' . nl2br($langs->trans('OptionModeProductSellDesc'));
	print "</td></tr>\n";
	print '<tr ' . $bc[true] . '><td><input type="radio" name="accounting_product_mode" value="ACCOUNTANCY_BUY"' . ($accounting_product_mode == 'ACCOUNTANCY_BUY' ? ' checked' : '') . '> ' . $langs->trans('OptionModeProductBuy') . '</td>';
	print '<td colspan="2">' . nl2br($langs->trans('OptionModeProductBuyDesc')) . "</td></tr>\n";
	
	print "</table>\n";
	
	print '<br /><div align="right"><input type="submit" class="button" value="' . $langs->trans('Modify') . '" name="changetype"></div>';
	
	print "<br>\n";
	
	if (! empty($msg)) {
		print $msg;
	}
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "p.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"], "l.description", "", $param, '', $sortfield, $sortorder);
	/*
	if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
		print '<th align="left">' . $langs->trans("Accountancy_code_buy") . '</td>';
	} else {
		print '<th align="left">' . $langs->trans("Accountancy_code_sell") . '</td>';
	}
	*/
	print_liste_field_titre($langs->trans("AccountAccounting"));
	print_liste_field_titre($langs->trans("Modify") . '<br><label id="select-all">' . $langs->trans('All') . '</label> / <label id="unselect-all">' . $langs->trans('None') . '</label>','','','','','align="center"');
	print '</tr>';
	
	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_ref" value="' . $search_ref . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_label" value="' . $search_label . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="30" name="search_desc" value="' . $search_desc . '"></td>';
	
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td align="center" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" name="button_search" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp;';
	print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" name="button_removefilter" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';
	print '</tr>';
	
	$var = true;
	
	while ( $i < min($num_lines, 250) ) {
		$obj = $db->fetch_object($result);
		$var = ! $var;
		
		$compta_prodsell = $obj->accountancy_code_sell;
		
		if ($obj->product_type == 0) {
			$compta_prodsell = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
			$compta_prodsell_id = $aarowid_prodsell;
		} else {
			$compta_prodsell = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
			$compta_prodsell_id = $aarowid_servsell;
		}
		
		$compta_prodbuy = $obj->accountancy_code_buy;
		
		if ($obj->product_type == 0) {
			$compta_prodbuy = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
			$compta_prodbuy_id = $aarowid_prodbuy;
		} else {
			$compta_prodbuy = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
			$compta_prodbuy_id = $aarowid_servbuy;
		}
		
		$product_static = new Product($db);
		
		print "<tr $bc[$var]>";
		
		print "</tr>";
		print "<tr $bc[$var]>";
		// Ref produit as link
		$product_static->ref = $obj->ref;
		$product_static->id = $obj->rowid;
		$product_static->type = $obj->type;
		print '<td>';
		if ($product_static->id)
			print $product_static->getNomUrl(1);
		else
			print '-&nbsp;';
		print '</td>';
		print '<td align="left">' . dol_trunc($obj->label, 24) . '</td>';
		// TODO ADJUST DESCRIPTION SIZE
		// print '<td align="left">' . $obj->description . '</td>';
		// TODO: we shoul set a user defined value to adjust user square / wide screen size
		$trunclengh = defined('ACCOUNTING_LENGTH_DESCRIPTION') ? ACCOUNTING_LENGTH_DESCRIPTION : 64;
		print '<td style="' . $code_sell_p_l_differ . '">' . nl2br(dol_trunc($obj->description, $trunclengh)) . '</td>';
		
		// Accounting account
		if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
			// print '<td align="left">' . $obj->accountancy_code_buy . '</td>';
			// TODO: replace by select
			// print '<td align="left">' . $compta_prodbuy . '</td>';
			// TODO: we shoul set a user defined value to adjust user square / wide screen size
			// $trunclenghform = defined('ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT') ? ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT : 50;
			print '<td align="left">';
			print $form->select_account($compta_prodbuy_id, 'codeventil_' . $product_static->id, 1);
			print '</td>';
		} else {
			// Accounting account sell
			// print '<td align="left">' . $obj->accountancy_code_sell . '</td>';
			// TODO: replace by select
			// TODO: we shoul set a user defined value to adjust user square / wide screen size
			// $trunclenghform = defined('ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT') ? ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT : 50;
			print '<td align="left">';
			print $form->select_account($compta_prodsell_id, 'codeventil_' . $product_static->id, 1);
			print '</td>';
		}
		
		// Checkbox select
		print '<td align="center">';
		print '<input type="checkbox" name="chk_prod[]" value="' . $obj->rowid . '"/></td>';
		
		print "</tr>";
		$i ++;
	}
	print '</table>';
	print '<br><div align="right"><input type="submit" class="butAction" name="changeaccount" value="' . $langs->trans("Validate") . '"></div>';
	print '</form>';
	
	$db->free($result);
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
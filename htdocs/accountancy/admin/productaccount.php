<?PHP
/*
 * Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
 * \file		htdocs/accountancy/admin/productaccount.php
 * \ingroup		Accounting Expert
 * \brief		Onglet de gestion de parametrages des ventilations
 */

require '../../main.inc.php';

	// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("companies");
$langs->load("compta");
$langs->load("main");
$langs->load("accountancy");

$search_ref     = GETPOST('search_ref','alpha');
$search_label   = GETPOST('search_label','alpha');
$search_desc    = GETPOST('search_desc','alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');

if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="DESC";
// Security check
if ($user->societe_id > 0)
	accessforbidden();
//TODO after adding menu
//if (! $user->rights->accounting->ventilation->dispatch)
//	accessforbidden();

$form = new Form($db);

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

llxHeader('', $langs->trans("Accounts"));

print '<input type="button" class="button" style="float: right;" value="Renseigner les comptes comptables produits manquant" onclick="launch_export();" />';

//For updating account
print '
	<script type="text/javascript">
		function launch_export() {
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("export_csv");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
</script>';

//TODO For select box
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


$sql = "SELECT p.rowid, p.ref , p.label, p.description , p.accountancy_code_sell, p.accountancy_code_buy, p.tms, p.fk_product_type as product_type , p.tosell , p.tobuy ";
$sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
//$sql .= " WHERE p.accountancy_code_sell IS NULL  AND p.tosell = 1  OR p.accountancy_code_buy IS NULL AND p.tobuy = 1";
$sql .= " WHERE p.accountancy_code_sell ='' AND p.tosell = 1  OR p.accountancy_code_buy ='' AND p.tobuy = 1";
$sql.= $db->order($sortfield,$sortorder);

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/admin/productaccount.php:: $sql=' . $sql);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	
/*
 * View
 */
	
	print '<br><br>';
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
//	print '<td align="left">' . $langs->trans("Ref") . '</td>';
//	print '<td align="left">' . $langs->trans("Label") . '</td>';
//	print '<td align="left">' . $langs->trans("Description") . '</td>';
	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"],"p.label","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"],"l.description","",$param,'',$sortfield,$sortorder);
	print '<td align="left">' . $langs->trans("Accountancy_code_buy") . '</td>';
	print '<td align="left">' . $langs->trans("Accountancy_code_buy_suggest") . '</td>';
	print '<td align="left">' . $langs->trans("Accountancy_code_sell") . '</td>';
	print '<td align="left">' . $langs->trans("Accountancy_code_sell_suggest") . '</td>';
	print '</tr>';
	
	$var = true;
	
	while ( $i < min($num, 250) ) {
		$obj = $db->fetch_object($resql);
		$var = ! $var;
		
		$compta_prodsell = $obj->accountancy_code_sell;
		if (empty($compta_prodsell)) {
			if ($obj->product_type == 0)
				$compta_prodsell = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
			else
				$compta_prodsell = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
		}
		
		$compta_prodbuy = $obj->accountancy_code_buy;
		if (empty($compta_prodbuy)) {
			if ($obj->product_type == 0)
				$compta_prodbuy = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
			else
				$compta_prodbuy = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
		}
		
		$product_static = new Product($db);
		
		print "<tr $bc[$var]>";
		// Ref produit
		$product_static->ref = $obj->ref;
		$product_static->id = $obj->rowid;
		$product_static->type = $obj->type;
		print '<td>';
		if ($product_static->id)
			print $product_static->getNomUrl(1);
		else
			print '-&nbsp;';
		print '</td>';
		//print '<td align="left">' . $obj->ref . '</td>';
		print '<td align="left">' . $obj->label . '</td>';
		print '<td align="left">' . $obj->description . '</td>';
		
		print '<td align="left">' . $obj->accountancy_code_buy . '</td>';
		print '<td align="left">' . $compta_prodbuy . '</td>';
		
		print '<td align="left">' . $obj->accountancy_code_sell . '</td>';
		print '<td align="left">' . $compta_prodsell . '</td>';
		
		print "</tr>\n";
		$i ++;
	}
	print "</table>";
	$db->free($resql);
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
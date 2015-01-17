<?PHP
/*
 * Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
 * Copyright (C) 2014 	   Florian Henry		<florian.henry@open-concept.pro>
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

// Dolibarr environment
$res = @include ("../main.inc.php");
if (! $res && file_exists("../main.inc.php"))
	$res = @include ("../main.inc.php");
if (! $res && file_exists("../../main.inc.php"))
	$res = @include ("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php"))
	$res = @include ("../../../main.inc.php");
if (! $res)
	die("Include of main fails");

	// Class
dol_include_once("/core/lib/report.lib.php");
dol_include_once("/core/lib/date.lib.php");
dol_include_once("/product/class/product.class.php");

$langs->load("companies");
$langs->load("compta");
$langs->load("main");
$langs->load("accountancy");

// Security check
if (!$user->admin)
    accessforbidden();

llxHeader('', $langs->trans("Accounts"));

$form = new Form($db);

print '<input type="button" class="button" style="float: right;" value="Renseigner les comptes comptables produits manquant" onclick="launch_export();" />';

print '
	<script type="text/javascript">
		function launch_export() {
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("export_csv");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
</script>';

$sql = "SELECT p.rowid, p.ref , p.label, p.description , p.accountancy_code_sell as codesell, p.accountancy_code_buy, p.tms, p.fk_product_type as product_type , p.tosell , p.tobuy ";
$sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
$sql .= " WHERE p.accountancy_code_sell IS NULL  AND p.tosell = 1  OR p.accountancy_code_buy IS NULL AND p.tobuy = 1";

dol_syslog('accountancy/admin/productaccount.php:: $sql=' . $sql);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	/*
* view
*/

	print '<br><br>';

	print '<table class="noborder" width="100%">';
	print '<td align="left">' . $langs->trans("Ref") . '</td>';
	print '<td align="left">' . $langs->trans("Label") . '</td>';
	print '<td align="left">' . $langs->trans("Description") . '</td>';
	print '<td align="left">' . $langs->trans("Accountancy_code_buy") . '</td>';
	print '<td align="left">' . $langs->trans("Accountancy_code_buy_suggest") . '</td>';
	print '<td align="left">' . $langs->trans("Accountancy_code_sell") . '</td>';
	print '<td align="left">' . $langs->trans("Accountancy_code_sell_suggest") . '</td>';

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
		$product_static->ref = $objp->ref;
		$product_static->id = $objp->rowid;
		$product_static->type = $objp->type;
		print '<td>';
		if ($product_static->id)
			print $product_static->getNomUrl(1);
		else
			print '&nbsp;';
		print '</td>';
		print '<td align="left">' . $obj->ref . '</td>';
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
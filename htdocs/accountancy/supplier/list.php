<?php
/* Copyright (C) 2013-2014	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014	Alexandre Spangaro		<alexandre.spangaro@gmail.com>
 * Copyright (C) 2014		Ari Elbaz (elarifr)		<github@accedinfo.com>
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

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");

$action = GETPOST('action');
$codeventil = GETPOST('codeventil', 'array');
$mesCasesCochees = GETPOST('mesCasesCochees', 'array');

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->ventilation->dispatch)
	accessforbidden();

$formventilation = new FormVentilation($db);

llxHeader('', $langs->trans("Ventilation"));

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

			dol_syslog('accountancy/supplier/list.php:: sql=' . $sql);
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
$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as l ON f.rowid = l.fk_facture_fourn";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accountingaccount as aa ON p.accountancy_code_buy = aa.account_number";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_system as accsys ON accsys.pcg_version = aa.fk_pcg_version";
$sql .= " WHERE f.fk_statut > 0 AND fk_code_ventilation <= 0";
$sql .= " AND (accsys.rowid='".$conf->global->CHARTOFACCOUNTS."' OR p.accountancy_code_sell IS NULL OR p.accountancy_code_buy ='')";

if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND f.entity = '" . $conf->entity . "'";
}

$sql .= " ORDER BY l.rowid";
if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO > 0) {
	$sql .= " DESC ";
}
$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/supplier/list.php:: $sql=' . $sql);
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	// TODO : print_barre_liste always use $conf->liste_limit and do not care about custom limit in list...
	print_barre_liste($langs->trans("InvoiceLines"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num_lines);

	print '<td align="left"><br><b>' . $langs->trans("DescVentilTodoSupplier") . '</b></br></td>';

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">' . "\n";
	print '<input type="hidden" name="action" value="ventil">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>Facture</td>';
	print '<td align="left">' . $langs->trans("Ref") . '</td>';
	print '<td align="left">' . $langs->trans("Label") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td align="right">' . $langs->trans("Amount") . '</td>';
	print '<td align="right">' . $langs->trans("Compte") . '</td>';
	print '<td align="center">' . $langs->trans("IntoAccount") . '</td>';
	print '<td align="center">' . $langs->trans("Ventilate") . '<br><label id="select-all">'.$langs->trans('All').'</label>/<label id="unselect-all">'.$langs->trans('None').'</label>'.'</td>';
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
		$code_buy_notset = '';
		
		if (empty($objp->code_buy)) {
			$code_buy_notset = 'color:red';
			if ($objp->type == 1) {
				$objp->code_buy = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
			} else {
				$objp->code_buy = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));					
			}
		}else {
			$code_buy_notset = 'color:blue';
		}
		
		print "<tr $bc[$var]>";

		// Ref facture
		$facturefourn_static->ref = $objp->ref;
		$facturefourn_static->id = $objp->facid;
		print '<td>' . $facturefourn_static->getNomUrl(1) . '</td>';

		// Ref facture supplier
		$productfourn_static->ref = $objp->product_ref;
		$productfourn_static->id = $objp->product_id;
		$productfourn_static->type = $objp->type;
		print '<td>';
		if ($productfourn_static->id)
			print $productfourn_static->getNomUrl(1);
		else
			print '&nbsp;';
		print '</td>';

		// print '<td><a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$objp->facid.'">'.$objp->ref.'</a></td>';

		// print '<td><a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$objp->facid.'">'.$objp->ref_supplier.'</a></td>';

		print '<td>' . dol_trunc($objp->product_label, 24) . '</td>';

		print '<td>' . stripslashes(nl2br($objp->description)) . '</td>';

		print '<td align="right">';
		print price($objp->price);
		print '</td>';

		print '<td align="center" style="' . $code_buy_notset . '">';
		print $objp->code_buy;
		print '</td>';

		// Colonne choix du compte
		print '<td align="center">';
		print $formventilation->select_account($objp->aarowid, 'codeventil[]', 1);
		print '</td>';
		// Colonne choix ligne a ventiler
		print '<td align="center">';
		print '<input type="checkbox" name="mesCasesCochees[]" value="' . $objp->rowid . "_" . $i . '"' . ($objp->aarowid ? "checked" : "") . '/>';
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
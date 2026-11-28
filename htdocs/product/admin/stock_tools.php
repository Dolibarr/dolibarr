<?php
/* Copyright (C) 2026		Franck MOREAU		<strike.ufp@gmail.com>
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
 *  \file       htdocs/product/admin/stock_tools.php
 *  \ingroup    stock
 *  \brief      Admin tool to check that the stock of each product and warehouse is equal to the sum of its movements
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'products', 'stocks'));

// Security check
if (!$user->admin || !isModEnabled('stock')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$fk_product = GETPOSTINT('fk_product');
$fk_entrepot = GETPOSTINT('fk_entrepot');

$hookmanager->initHooks(array('stocktoolsadmin'));

$movement = new MouvementStock($db);


/*
 * Actions
 */

if ($action == 'confirm_regularize' && $confirm == 'yes') {
	$differences = $movement->getStockDifferences($fk_product, $fk_entrepot);
	if (!is_array($differences)) {
		setEventMessages($movement->error, $movement->errors, 'errors');
	} else {
		$inventorycode = 'REGUL'.dol_print_date(dol_now(), 'dayhourlog');
		$label = $langs->transnoentities('StockMovementsRegularizationLabel');
		$nbadded = 0;
		$error = 0;

		$db->begin();
		foreach ($differences as $difference) {
			$result = $movement->regularizeStockDifference($user, $difference['fk_product'], $difference['fk_entrepot'], $label, $inventorycode);
			if ($result < 0) {
				$error++;
				break;
			}
			if ($result > 0) {
				$nbadded++;
			}
		}

		if ($error) {
			$db->rollback();
			setEventMessages($movement->error, $movement->errors, 'errors');
		} else {
			$db->commit();
			setEventMessages($langs->trans('StockMovementsRegularized', $nbadded, $inventorycode), null, 'mesgs');
		}
	}

	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}


/*
 * Views
 */

$form = new Form($db);
$productstatic = new Product($db);
$warehousestatic = new Entrepot($db);

llxHeader('', $langs->trans('StockMovementsCheck'), '', '', 0, 0, '', '', '', 'mod-product page-admin_stock_tools');

print load_fiche_titre($langs->trans('StockMovementsCheck'), '', 'stock');

print '<span class="opacitymedium">'.$langs->trans('StockMovementsCheckDesc').'</span><br><br>';

$differences = $movement->getStockDifferences();
if (!is_array($differences)) {
	dol_print_error($db, $movement->error);
	$differences = array();
}

// Confirmation of the regularization of one line or of all lines
if ($action == 'regularize') {
	$nblines = ($fk_product > 0 ? 1 : count($differences));
	$urlconfirm = $_SERVER["PHP_SELF"].'?fk_product='.((int) $fk_product).'&fk_entrepot='.((int) $fk_entrepot);
	print $form->formconfirm($urlconfirm, $langs->trans('StockMovementsRegularize'), $langs->trans('ConfirmStockMovementsRegularize', $nblines), 'confirm_regularize', '', 0, 1);
}

if (count($differences) > 0) {
	print '<div class="right">';
	print dolGetButtonAction($langs->trans('StockMovementsRegularizeAll'), '', 'default', $_SERVER["PHP_SELF"].'?action=regularize&token='.newToken(), '', 1);
	print '</div>';
}

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans('Product').'</th>';
print '<th>'.$langs->trans('Warehouse').'</th>';
print '<th class="right">'.$langs->trans('Stock').'</th>';
print '<th class="right">'.$langs->trans('SumOfStockMovements').'</th>';
print '<th class="right">'.$langs->trans('StockMovementsDifference').'</th>';
print '<th></th>';
print '</tr>';

if (count($differences) == 0) {
	print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">'.$langs->trans('StockMovementsNoDifference').'</span></td></tr>';
}
foreach ($differences as $difference) {
	$productstatic->id = $difference['fk_product'];
	$productstatic->ref = $difference['product_ref'];
	$productstatic->label = $difference['product_label'];
	$warehousestatic->id = $difference['fk_entrepot'];
	$warehousestatic->ref = $difference['warehouse_ref'];
	$warehousestatic->label = $difference['warehouse_ref'];

	print '<tr class="oddeven">';
	print '<td class="tdoverflowmax250">'.$productstatic->getNomUrl(1).' - '.dolPrintLabel($difference['product_label']).'</td>';
	print '<td class="tdoverflowmax200">'.$warehousestatic->getNomUrl(1).'</td>';
	print '<td class="right">'.price2num($difference['stock'], 'MS').'</td>';
	print '<td class="right">'.price2num($difference['movements'], 'MS').'</td>';
	print '<td class="right"><strong>'.price2num($difference['difference'], 'MS').'</strong></td>';
	print '<td class="right">';
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=regularize&fk_product='.((int) $difference['fk_product']).'&fk_entrepot='.((int) $difference['fk_entrepot']).'&token='.newToken().'">'.$langs->trans('StockMovementsRegularize').'</a>';
	print '</td>';
	print '</tr>';
}

print '</table>';
print '</div>';

llxFooter();
$db->close();

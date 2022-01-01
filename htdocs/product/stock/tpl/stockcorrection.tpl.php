<?php
/* Copyright (C) 2010-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *
 * $object must be defined
 * $backtopage
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE STOCKCORRECTION.TPL.PHP -->
<?php
$productref = '';
if ($object->element == 'product') {
	$productref = $object->ref;
}

$langs->load("productbatch");


if (empty($id)) {
	$id = $object->id;
}

print '<script type="text/javascript">
		jQuery(document).ready(function() {
			function init_price()
			{
				if (jQuery("#mouvement").val() == \'0\') jQuery("#unitprice").removeAttr("disabled");
				else jQuery("#unitprice").prop("disabled", true);
			}
			init_price();
			jQuery("#mouvement").change(function() {
				console.log("We change the direction of movement");
				init_price();
			});
			jQuery("#nbpiece").keyup(function(event) {
				console.log("We enter a qty on "+event.which);
				if ( event.which == 54 ) {  /* char - */
					console.log("We set direction to value 1");
					jQuery("#nbpiece").val(jQuery("#nbpiece").val().replace("-", ""));

					jQuery("#mouvement option").removeAttr("selected").change();
					jQuery("#mouvement option[value=1]").attr("selected","selected").trigger("change");
					jQuery("#mouvement").trigger("change");
				} else if ( event.which == 187 ) {  /* char + */
					console.log("We set direction to value 0");
					jQuery("#mouvement option").removeAttr("selected").change();
					jQuery("#mouvement option[value=0]").attr("selected","selected").trigger("change");
					jQuery("#mouvement").trigger("change");
				}
			});
		});
		</script>';


print load_fiche_titre($langs->trans("StockCorrection"), '', 'generic');

print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">'."\n";

print dol_get_fiche_head();

print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="correct_stock">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
print '<table class="border centpercent">';

// Warehouse or product
print '<tr>';
if ($object->element == 'product') {
	print '<td class="fieldrequired">'.$langs->trans("Warehouse").'</td>';
	print '<td>';
	$ident = (GETPOST("dwid") ?GETPOST("dwid", 'int') : (GETPOST('id_entrepot') ? GETPOST('id_entrepot', 'int') : ($object->element == 'product' && $object->fk_default_warehouse ? $object->fk_default_warehouse : 'ifone')));
	if (empty($ident) && !empty($conf->global->MAIN_DEFAULT_WAREHOUSE)) {
		$ident = $conf->global->MAIN_DEFAULT_WAREHOUSE;
	}
	print img_picto('', 'stock').$formproduct->selectWarehouses($ident, 'id_entrepot', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'minwidth100');
	print '</td>';
}
if ($object->element == 'stock') {
	print '<td class="fieldrequired">'.$langs->trans("Product").'</td>';
	print '<td>';
	print img_picto('', 'product');
	$form->select_produits(GETPOST('product_id', 'int'), 'product_id', (empty($conf->global->STOCK_SUPPORTS_SERVICES) ? '0' : ''), 0, 0, -1, 2, '', 0, null, 0, 1, 0, 'maxwidth500');
	print '</td>';
}
print '<td class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td>';
print '<td>';
if ($object->element == 'product' || $object->element == 'stock') {
	print '<select name="mouvement" id="mouvement" class="minwidth100 valignmiddle">';
	print '<option value="0">'.$langs->trans("Add").'</option>';
	print '<option value="1"'.(GETPOST('mouvement') ? ' selected="selected"' : '').'>'.$langs->trans("Delete").'</option>';
	print '</select>';
	print ajax_combobox("mouvement");
}
print '<input name="nbpiece" id="nbpiece" class="center valignmiddle maxwidth75" value="'.GETPOST("nbpiece").'">';
print '</td>';
print '</tr>';

// If product is a Kit, we ask if we must disable stock change of subproducts
if (!empty($conf->global->PRODUIT_SOUSPRODUITS) && $object->element == 'product' && $object->hasFatherOrChild(1)) {
	print '<tr>';
	print '<td></td>';
	print '<td colspan="3">';
	print '<input type="checkbox" name="disablesubproductstockchange" id="disablesubproductstockchange" value="1"'.(GETPOST('disablesubproductstockchange') ? ' checked="checked"' : '').'">';
	print ' <label for="disablesubproductstockchange">'.$langs->trans("DisableStockChangeOfSubProduct").'</label>';
	print '</td>';
	print '</tr>';
}

// Serial / Eat-by date
if (!empty($conf->productbatch->enabled) &&
(($object->element == 'product' && $object->hasbatch())
|| ($object->element == 'stock'))
) {
	print '<tr>';
	print '<td'.($object->element == 'stock' ? '' : ' class="fieldrequired"').'>'.$langs->trans("batch_number").'</td><td colspan="3">';
	print '<input type="text" name="batch_number" size="40" value="'.GETPOST("batch_number").'">';
	print '</td>';
	print '</tr>';
	print '<tr>';
	if (empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
		print '<td>'.$langs->trans("SellByDate").'</td><td>';
		$sellbyselected = dol_mktime(0, 0, 0, GETPOST('sellbymonth'), GETPOST('sellbyday'), GETPOST('sellbyyear'));
		print $form->selectDate($sellbyselected, 'sellby', '', '', 1, "");
		print '</td>';
	}
	if (empty($conf->global->PRODUCT_DISABLE_EATBY)) {
		print '<td>'.$langs->trans("EatByDate").'</td><td>';
		$eatbyselected = dol_mktime(0, 0, 0, GETPOST('eatbymonth'), GETPOST('eatbyday'), GETPOST('eatbyyear'));
		print $form->selectDate($eatbyselected, 'eatby', '', '', 1, "");
		print '</td>';
	}
	print '</tr>';
}

// Purchase price and project
print '<tr>';
print '<td>'.$langs->trans("UnitPurchaseValue").'</td>';
print '<td colspan="'.(!empty($conf->projet->enabled) ? '1' : '3').'"><input name="unitprice" id="unitprice" size="10" value="'.GETPOST("unitprice").'"></td>';
if (!empty($conf->projet->enabled)) {
	print '<td>'.$langs->trans('Project').'</td>';
	print '<td>';
	print img_picto('', 'project');
	$formproject->select_projects(-1, '', 'projectid', 0, 0, 1, 0, 0, 0, 0, '', 0, 0, 'maxwidth300');
	print '</td>';
}
print '</tr>';

// Label of mouvement of id of inventory
$valformovementlabel = ((GETPOST("label") && (GETPOST('label') != $langs->trans("MovementCorrectStock", ''))) ? GETPOST("label") : $langs->trans("MovementCorrectStock", $productref));
print '<tr>';
print '<td>'.$langs->trans("MovementLabel").'</td>';
print '<td>';
print '<input type="text" name="label" class="minwidth300" value="'.$valformovementlabel.'">';
print '</td>';
print '<td>'.$langs->trans("InventoryCode").'</td>';
print '<td>';
print '<input class="maxwidth100onsmartphone" name="inventorycode" id="inventorycode" value="'.(GETPOSTISSET("inventorycode") ? GETPOST("inventorycode", 'alpha') : dol_print_date(dol_now(), '%Y%m%d%H%M%S')).'">';
print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button button-save" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
print '</div>';

print '</form>';
?>
<!-- END PHP STOCKCORRECTION.TPL.PHP -->

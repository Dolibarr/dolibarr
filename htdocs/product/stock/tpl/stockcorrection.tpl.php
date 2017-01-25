<?php
/* Copyright (C) 2010-2015 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * $object must be defined
 */
?>

<!-- BEGIN PHP TEMPLATE STOCKCORRECTION.TPL.PHP -->
<?php
        $productref = '';
        if ($object->element == 'product') $productref = $object->ref;

        $langs->load("productbatch");

		if (empty($id)) $id = $object->id;

		print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			function init_price()
			{
				if (jQuery("#mouvement").val() == \'0\') jQuery("#unitprice").removeAttr("disabled");
				else jQuery("#unitprice").prop("disabled", true);
			}
			init_price();
			jQuery("#mouvement").change(function() {
				init_price();
			});
		});
		</script>';


		print load_fiche_titre($langs->trans("StockCorrection"),'','title_generic.png');

		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="correct_stock">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<table class="border" width="100%">';

		// Warehouse or product
		print '<tr>';
		if ($object->element == 'product')
		{
			print '<td width="20%" class="fieldrequired" colspan="2">'.$langs->trans("Warehouse").'</td>';
			print '<td width="20%">';
			print $formproduct->selectWarehouses((GETPOST("dwid")?GETPOST("dwid",'int'):(GETPOST('id_entrepot')?GETPOST('id_entrepot','int'):'ifone')), 'id_entrepot', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'minwidth100');
			print '</td>';
		}
		if ($object->element == 'stock')
		{
			print '<td width="20%" class="fieldrequired" colspan="2">'.$langs->trans("Product").'</td>';
	        print '<td width="20%">';
	        print $form->select_produits(GETPOST('product_id'), 'product_id', (empty($conf->global->STOCK_SUPPORTS_SERVICES)?'0':''), 20, 0, -1);
	        print '</td>';
		}
		print '<td width="20%">';
		print '<select name="mouvement" id="mouvement" class="flat">';
		print '<option value="0">'.$langs->trans("Add").'</option>';
		print '<option value="1"'.(GETPOST('mouvement')?' selected="selected"':'').'>'.$langs->trans("Delete").'</option>';
		print '</select></td>';
		print '<td width="20%" class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input class="flat" name="nbpiece" id="nbpiece" size="10" value="'.GETPOST("nbpiece").'"></td>';
		print '</tr>';

		// Purchase price
		print '<tr>';
		print '<td width="20%" colspan="2">'.$langs->trans("UnitPurchaseValue").'</td>';
		print '<td colspan="4"><input class="flat" name="unitprice" id="unitprice" size="10" value="'.GETPOST("unitprice").'"></td>';
		print '</tr>';

		// Serial / Eat-by date
		if (! empty($conf->productbatch->enabled) && 
		    (($object->element == 'product' && $object->hasbatch())
		    || ($object->element == 'stock'))
		)
		{
			print '<tr>';
			print '<td colspan="2"'.($object->element == 'stock'?'': ' class="fieldrequired"').'>'.$langs->trans("batch_number").'</td><td colspan="4">';
			print '<input type="text" name="batch_number" size="40" value="'.GETPOST("batch_number").'">';
			print '</td>';
			print '</tr><tr>';
			print '<td colspan="2">'.$langs->trans("EatByDate").'</td><td>';
			$eatbyselected=dol_mktime(0, 0, 0, GETPOST('eatbymonth'), GETPOST('eatbyday'), GETPOST('eatbyyear'));
			$form->select_date($eatbyselected,'eatby','','',1,"");
			print '</td>';
			print '<td></td>';
			print '<td>'.$langs->trans("SellByDate").'</td><td>';
			$sellbyselected=dol_mktime(0, 0, 0, GETPOST('sellbymonth'), GETPOST('sellbyday'), GETPOST('sellbyyear'));
			$form->select_date($sellbyselected,'sellby','','',1,"");
			print '</td>';
			print '</tr>';
		}

		// Label of mouvement of id of inventory
		$valformovementlabel=((GETPOST("label") && (GETPOST('label') != $langs->trans("MovementCorrectStock",''))) ? GETPOST("label") : $langs->trans("MovementCorrectStock", $productref));
		print '<tr>';
		print '<td width="20%" colspan="2">'.$langs->trans("MovementLabel").'</td>';
		print '<td colspan="2">';
		print '<input type="text" name="label" size="60" value="'.$valformovementlabel.'">';
		print '</td>';
		print '<td width="20%">'.$langs->trans("InventoryCode").'</td><td width="20%"><input class="flat maxwidth100onsmartphone" name="inventorycode" id="inventorycode" value="'.GETPOST("inventorycode").'"></td>';
		print '</tr>';

		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button" name="save" value="'.dol_escape_htmltag($langs->trans('Save')).'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '</div>';
		print '</form>';

?>
<!-- END PHP STOCKCORRECTION.TPL.PHP -->

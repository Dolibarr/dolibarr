<?php
/* Copyright (C) 2010-2017 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * $backtopage
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

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

        dol_fiche_head();

		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="correct_stock">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<table class="border" width="100%">';

		// Warehouse or product
		print '<tr>';
		if ($object->element == 'product')
		{
			print '<td class="fieldrequired">'.$langs->trans("Warehouse").'</td>';
			print '<td>';
			print $formproduct->selectWarehouses((GETPOST("dwid")?GETPOST("dwid",'int'):(GETPOST('id_entrepot')?GETPOST('id_entrepot','int'):($object->element=='product' && $object->fk_default_warehouse?$object->fk_default_warehouse:'ifone'))), 'id_entrepot', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'minwidth100');
    		print ' &nbsp; <select name="mouvement" id="mouvement">';
    		print '<option value="0">'.$langs->trans("Add").'</option>';
    		print '<option value="1"'.(GETPOST('mouvement')?' selected="selected"':'').'>'.$langs->trans("Delete").'</option>';
    		print '</select>';
			print '</td>';
		}
		if ($object->element == 'stock')
		{
			print '<td class="fieldrequired">'.$langs->trans("Product").'</td>';
	        print '<td>';
	        print $form->select_produits(GETPOST('product_id'), 'product_id', (empty($conf->global->STOCK_SUPPORTS_SERVICES)?'0':''), 20, 0, -1, 2, '', 0, null, 0, 1, 0, 'maxwidth500');
    		print ' &nbsp; <select name="mouvement" id="mouvement">';
    		print '<option value="0">'.$langs->trans("Add").'</option>';
    		print '<option value="1"'.(GETPOST('mouvement')?' selected="selected"':'').'>'.$langs->trans("Delete").'</option>';
    		print '</select>';
	        print '</td>';
		}
		print '<td class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td>';
		print '<td><input name="nbpiece" id="nbpiece" size="10" value="'.GETPOST("nbpiece").'"></td>';
		print '</tr>';

		// Purchase price
		print '<tr>';
		print '<td>'.$langs->trans("UnitPurchaseValue").'</td>';
		print '<td colspan="'.(!empty($conf->projet->enabled) ? '1' : '3').'"><input name="unitprice" id="unitprice" size="10" value="'.GETPOST("unitprice").'"></td>';
		if (! empty($conf->projet->enabled))
		{
			print '<td>'.$langs->trans('Project').'</td>';
			print '<td>';
			$formproject->select_projects(-1, '', 'projectid', 0, 0, 1, 0, 0, 0, 0, '', 0, 0, 'maxwidth300');
			print '</td>';
		}
		print '</tr>';

		// Serial / Eat-by date
		if (! empty($conf->productbatch->enabled) &&
		    (($object->element == 'product' && $object->hasbatch())
		    || ($object->element == 'stock'))
		)
		{
			print '<tr>';
			print '<td'.($object->element == 'stock'?'': ' class="fieldrequired"').'>'.$langs->trans("batch_number").'</td><td colspan="3">';
			print '<input type="text" name="batch_number" size="40" value="'.GETPOST("batch_number").'">';
			print '</td>';
			print '</tr>';
			print '<tr>';
			print '<td>'.$langs->trans("EatByDate").'</td><td>';
			$eatbyselected=dol_mktime(0, 0, 0, GETPOST('eatbymonth'), GETPOST('eatbyday'), GETPOST('eatbyyear'));
			$form->select_date($eatbyselected,'eatby','','',1,"");
			print '</td>';
			print '<td>'.$langs->trans("SellByDate").'</td><td>';
			$sellbyselected=dol_mktime(0, 0, 0, GETPOST('sellbymonth'), GETPOST('sellbyday'), GETPOST('sellbyyear'));
			$form->select_date($sellbyselected,'sellby','','',1,"");
			print '</td>';
			print '</tr>';
		}

		// Label of mouvement of id of inventory
		$valformovementlabel=((GETPOST("label") && (GETPOST('label') != $langs->trans("MovementCorrectStock",''))) ? GETPOST("label") : $langs->trans("MovementCorrectStock", $productref));
		print '<tr>';
		print '<td>'.$langs->trans("MovementLabel").'</td>';
		print '<td>';
		print '<input type="text" name="label" class="minwidth300" value="'.$valformovementlabel.'">';
		print '</td>';
		print '<td>'.$langs->trans("InventoryCode").'</td><td><input class="maxwidth100onsmartphone" name="inventorycode" id="inventorycode" value="'.(isset($_POST["inventorycode"])?GETPOST("inventorycode",'alpha'):dol_print_date(dol_now(),'%y%m%d%H%M%S')).'"></td>';
		print '</tr>';

		print '</table>';

        dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" name="save" value="'.dol_escape_htmltag($langs->trans('Save')).'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '</div>';

		print '</form>';
?>
<!-- END PHP STOCKCORRECTION.TPL.PHP -->

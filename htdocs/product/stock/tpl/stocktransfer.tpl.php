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
        $productref='';
        if ($object->element == 'product') $productref = $object->ref;

        $langs->load("productbatch");

        if (empty($id)) $id = $object->id;

        $pdluoid=GETPOST('pdluoid','int');

	    $pdluo = new Productbatch($db);

	    if ($pdluoid > 0)
	    {
	        $result=$pdluo->fetch($pdluoid);
	        if ($result > 0)
	        {
	            $pdluoid=$pdluo->id;
	        }
	        else
	        {
	            dol_print_error($db,$pdluo->error,$pdluo->errors);
	        }
	    }

		print load_fiche_titre($langs->trans("StockTransfer"),'','title_generic.png');

		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">'."\n";

		dol_fiche_head();

		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="transfert_stock">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		if ($pdluoid)
		{
		    print '<input type="hidden" name="pdluoid" value="'.$pdluoid.'">';
		}
		print '<table class="border" width="100%">';

		// Source warehouse or product
		print '<tr>';
		if ($object->element == 'product')
		{
		    print '<td class="fieldrequired">'.$langs->trans("WarehouseSource").'</td>';
		    print '<td>';
		    print $formproduct->selectWarehouses((GETPOST("dwid")?GETPOST("dwid",'int'):(GETPOST('id_entrepot')?GETPOST('id_entrepot','int'):($object->element=='product' && $object->fk_default_warehouse?$object->fk_default_warehouse:'ifone'))), 'id_entrepot', 'warehouseopen,warehouseinternal', 1);
		    print '</td>';
		}
		if ($object->element == 'stock')
		{
		    print '<td class="fieldrequired">'.$langs->trans("Product").'</td>';
		    print '<td>';
		    print $form->select_produits(GETPOST('product_id'), 'product_id', (empty($conf->global->STOCK_SUPPORTS_SERVICES)?'0':''), 20, 0, -1, 2, '', 0, null, 0, 1, 0, 'maxwidth500');
		    print '</td>';
		}

		print '<td class="fieldrequired">'.$langs->trans("WarehouseTarget").'</td><td>';
		print $formproduct->selectWarehouses(GETPOST('id_entrepot_destination'), 'id_entrepot_destination', 'warehouseopen,warehouseinternal', 1);
		print '</td></tr>';
		print '<tr><td class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td colspan="3"><input type="text" name="nbpiece" size="10" value="'.dol_escape_htmltag(GETPOST("nbpiece")).'"></td>';
		print '</tr>';

		// Serial / Eat-by date
		if (! empty($conf->productbatch->enabled) &&
		    (($object->element == 'product' && $object->hasbatch())
		    || ($object->element == 'stock'))
		)
		{
			print '<tr>';
			print '<td'.($object->element == 'stock'?'': ' class="fieldrequired"').'>'.$langs->trans("batch_number").'</td><td colspan="3">';
			if ($pdluoid > 0)
			{
                // If form was opened for a specific pdluoid, field is disabled
                print '<input type="text" name="batch_number_bis" size="40" disabled="disabled" value="'.(GETPOST('batch_number')?GETPOST('batch_number'):$pdluo->batch).'">';
			    print '<input type="hidden" name="batch_number" value="'.(GETPOST('batch_number')?GETPOST('batch_number'):$pdluo->batch).'">';
			}
			else
			{
			    print '<input type="text" name="batch_number" size="40" value="'.(GETPOST('batch_number')?GETPOST('batch_number'):$pdluo->batch).'">';
			}
			print '</td>';
			print '</tr>';

			print '<tr>';
			print '<td>'.$langs->trans("EatByDate").'</td><td>';
			print $form->select_date(($d_eatby?$d_eatby:$pdluo->eatby),'eatby','','',1,"", 1, 0, 1, ($pdluoid > 0 ? 1 : 0));		// If form was opened for a specific pdluoid, field is disabled
			print '</td>';
			print '<td>'.$langs->trans("SellByDate").'</td><td>';
			print $form->select_date(($d_sellby?$d_sellby:$pdluo->sellby),'sellby','','',1,"", 1, 0, 1, ($pdluoid > 0 ? 1 : 0));		// If form was opened for a specific pdluoid, field is disabled
			print '</td>';
			print '</tr>';
		}

		// Label
		$valformovementlabel=(GETPOST("label")?GETPOST("label"):$langs->trans("MovementTransferStock", $productref));
		print '<tr>';
		print '<td>'.$langs->trans("MovementLabel").'</td>';
		print '<td>';
		print '<input type="text" name="label" class="minwidth300" value="'.dol_escape_htmltag($valformovementlabel).'">';
		print '</td>';
		print '<td>'.$langs->trans("InventoryCode").'</td><td><input class="maxwidth100onsmartphone" name="inventorycode" id="inventorycode" value="'.(isset($_POST["inventorycode"])?GETPOST("inventorycode",'alpha'):dol_print_date(dol_now(),'%y%m%d%H%M%S')).'"></td>';
		print '</tr>';

		print '</table>';

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Save')).'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '</div>';

		print '</form>';
?>
<!-- END PHP STOCKCORRECTION.TPL.PHP -->

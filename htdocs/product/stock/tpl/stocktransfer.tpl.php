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
 * $backtopage
 */
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
		    print '<td width="15%" class="fieldrequired">'.$langs->trans("WarehouseSource").'</td>';
		    print '<td width="15%">';
		    print $formproduct->selectWarehouses((GETPOST("dwid")?GETPOST("dwid",'int'):(GETPOST('id_entrepot')?GETPOST('id_entrepot','int'):'ifone')), 'id_entrepot', 'warehouseopen,warehouseinternal', 1);
		    print '</td>';
		}
		if ($object->element == 'stock')
		{
		    print '<td width="15%" class="fieldrequired">'.$langs->trans("Product").'</td>';
		    print '<td width="15%">';
		    print $form->select_produits(GETPOST('product_id'),'product_id',(empty($conf->global->STOCK_SUPPORTS_SERVICES)?'0':''));
		    print '</td>';
		}
		
		print '<td width="15%" class="fieldrequired">'.$langs->trans("WarehouseTarget").'</td><td width="15%">';
		print $formproduct->selectWarehouses(GETPOST('id_entrepot_destination'), 'id_entrepot_destination', 'warehouseopen,warehouseinternal', 1);
		print '</td>';
		print '<td width="15%" class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td width="15%"><input type="text" class="flat" name="nbpiece" size="10" value="'.dol_escape_htmltag(GETPOST("nbpiece")).'"></td>';
		print '</tr>';

		// Serial / Eat-by date
		if (! empty($conf->productbatch->enabled) && 
		    (($object->element == 'product' && $object->hasbatch())
		    || ($object->element == 'stock'))
		)
		{
			print '<tr>';
			print '<td'.($object->element == 'stock'?'': ' class="fieldrequired"').'>'.$langs->trans("batch_number").'</td><td colspan="5">';
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
			print '</tr><tr>';
			print '<td>'.$langs->trans("EatByDate").'</td><td>';
			print $form->select_date(($d_eatby?$d_eatby:$pdluo->eatby),'eatby','','',1,"", 1, 0, 1, ($pdluoid > 0 ? 1 : 0));		// If form was opened for a specific pdluoid, field is disabled
			print '</td>';
			print '<td>'.$langs->trans("SellByDate").'</td><td>';
			print $form->select_date(($d_sellby?$d_sellby:$pdluo->sellby),'sellby','','',1,"", 1, 0, 1, ($pdluoid > 0 ? 1 : 0));		// If form was opened for a specific pdluoid, field is disabled
			print '</td>';
			print '<td colspan="2"></td>';
			print '</tr>';
		}

		// Label
		$valformovementlabel=(GETPOST("label")?GETPOST("label"):$langs->trans("MovementTransferStock", $productref));
		print '<tr>';
		print '<td width="15%">'.$langs->trans("MovementLabel").'</td>';
		print '<td colspan="3">';
		print '<input type="text" name="label" size="60" value="'.dol_escape_htmltag($valformovementlabel).'">';
		print '</td>';
		print '<td width="20%">'.$langs->trans("InventoryCode").'</td><td width="20%"><input class="flat maxwidth100onsmartphone" name="inventorycode" id="inventorycode" value="'.(GETPOST("inventorycode")?GETPOST("inventorycode",'alpha'):dol_print_date(dol_now(),'%y%m%d%H%M%S')).'"></td>';
		print '</tr>';

		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Save')).'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '</div>';

		print '</form>';
?>
<!-- END PHP STOCKCORRECTION.TPL.PHP -->

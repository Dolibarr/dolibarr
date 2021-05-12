<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


global $forceall;

if (empty($forceall)) $forceall=0;


// Define colspan for the button 'Add'
$colspan = 3;	// Columns: total ht + col edit + col delete

// Lines for extrafield
$objectline = new BOMLine($this->db);
?>

<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->

<?php
$coldisplay=0;
?>
<tr class="oddeven tredited">
	<?php
	// Adds a line numbering column
	if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
		<td class="linecolnum center"><?php $coldisplay++; ?><?php echo ($i+1); ?></td>
	<?php }

	$coldisplay++;
	?>
	<td>
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
	<input type="hidden" id="product_id" name="productid" value="<?php echo (! empty($line->fk_product)?$line->fk_product:0); ?>" />
	<input type="hidden" id="special_code" name="special_code" value="<?php echo $line->special_code; ?>">
	<input type="hidden" id="fk_parent_line" name="fk_parent_line" value="<?php echo $line->fk_parent_line; ?>">

	<?php
	// Predefined product/service
	if ($line->fk_product > 0) {
		$tmpproduct = new Product($object->db);
		$tmpproduct->fetch($line->fk_product);
		print $tmpproduct->getNomUrl(1);
	}

	if (is_object($hookmanager))
	{
		$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOST('fk_parent_line') : $line->fk_parent_line);
	    $parameters=array('line'=>$line,'fk_parent_line'=>$fk_parent_line,'var'=>$var,'dateSelector'=>$dateSelector,'seller'=>$seller,'buyer'=>$buyer);
	    $reshook=$hookmanager->executeHooks('formEditProductOptions', $parameters, $this, $action);
	}

	?>
	</td>

	<?php
	/*if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier')	// We must have same test in printObjectLines
	{
	    $coldisplay++;
	?>
		<td class="right"><input id="fourn_ref" name="fourn_ref" class="flat minwidth75" value="<?php echo ($line->ref_supplier ? $line->ref_supplier : $line->ref_fourn); ?>"></td>
	<?php
	*/

	$coldisplay++;
	?>
	<td class="nobottom linecolqty right">
	<?php if (($line->info_bits & 2) != 2) {
		// I comment this because it shows info even when not required
		// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
		// must also not be output for most entities (proposal, intervention, ...)
		//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
		print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="' . $line->qty . '">';
	}
	?>
	</td>

	<?php
	if($conf->global->PRODUCT_USE_UNITS)
	{
	    $coldisplay++;
		print '<td class="nobottom linecoluseunit left">';
		print $form->selectUnits($line->fk_unit, "units");
		print '</td>';
	}

	$coldisplay++;
	?>
	<td class="nobottom nowrap linecollost right"><input type="text" size="1" name="efficiency" id="efficiency" class="flat right" value="<?php echo $line->efficiency; ?>"></td>
	<?php

	$coldisplay+=$colspan;
	?>
	<td class="nobottom linecoledit center valignmiddle" colspan="<?php echo $colspan; ?>"><?php $coldisplay+=$colspan; ?>
		<input type="submit" class="button" id="savelinebutton" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

<?php
if (is_object($objectline)) {
	print $objectline->showOptionals($extrafieldsline, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', empty($conf->global->MAIN_EXTRAFIELDS_IN_ONE_TD)?0:1);
}
?>

<!-- END PHP TEMPLATE objectline_edit.tpl.php -->

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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


global $forceall;

if (empty($forceall)) $forceall = 0;


// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete

// Lines for extrafield
$objectline = new BOMLine($this->db);

print "<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->\n";

$coldisplay = 0;
print '<tr class="oddeven tredited">';
// Adds a line numbering column
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	print '<td class="linecolnum center">'.($i + 1).'</td>';
	$coldisplay++;
}

$coldisplay++;
?>
	<td>
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
	<input type="hidden" id="product_id" name="productid" value="<?php echo (!empty($line->fk_product) ? $line->fk_product : 0); ?>" />
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
    $parameters = array('line'=>$line, 'fk_parent_line'=>$fk_parent_line, 'var'=>$var, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer);
    $reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $this, $action);
}

print '</td>';

/*if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier')	// We must have same test in printObjectLines
{
    $coldisplay++;
?>
	<td class="right"><input id="fourn_ref" name="fourn_ref" class="flat minwidth75" value="<?php echo ($line->ref_supplier ? $line->ref_supplier : $line->ref_fourn); ?>"></td>
<?php
*/

$coldisplay++;

print '<td class="nobottom linecolqty right">';
if (($line->info_bits & 2) != 2) {
	// I comment this because it shows info even when not required
	// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
	// must also not be output for most entities (proposal, intervention, ...)
	//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
	print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="'.$line->qty.'">';
}
print '</td>';

if ($conf->global->PRODUCT_USE_UNITS)
{
    $coldisplay++;
	print '<td class="nobottom linecoluseunit left">';
	print $form->selectUnits($line->fk_unit, "units");
	print '</td>';
}

$coldisplay++;
print '<td class="nobottom linecolqtyfrozen right"><input type="checkbox" name="qty_frozen" id="qty_frozen" class="flat right" value="1"'.(GETPOSTISSET("qty_frozen") ? (GETPOST('qty_frozen', 'int') ? ' checked="checked"' : '') : ($line->qty_frozen ? ' checked="checked"' : '')).'>';
print '</td>';

$coldisplay++;
print '<td class="nobottom linecoldisablestockchange right"><input type="checkbox" name="disable_stock_change" id="disable_stock_change" class="flat right" value="1"'.(GETPOSTISSET('disablestockchange') ? (GETPOST("disable_stock_change", 'int') ? ' checked="checked"' : '') : ($line->disable_stock_change ? ' checked="checked"' : '')).'">';
print '</td>';

$coldisplay++;
print '<td class="nobottom nowrap linecollost right">';
print '<input type="text" size="1" name="efficiency" id="efficiency" class="flat right" value="'.$line->efficiency.'"></td>';

$coldisplay++;
print '<td class="nobottom nowrap linecolcostprice right">';
print '</td>';

$coldisplay += $colspan;
print '<td class="nobottom linecoledit center valignmiddle" colspan="'.$colspan.'">';
$coldisplay += $colspan;
print '<input type="submit" class="button buttongen marginbottomonly" id="savelinebutton marginbottomonly" name="save" value="'.$langs->trans("Save").'">';
print '<br>';
print '<input type="submit" class="button buttongen marginbottomonly" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</td>';
print '</tr>';

if (is_object($objectline)) {
	print $objectline->showOptionals($extrafields, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', 1);
}

print "<!-- END PHP TEMPLATE objectline_edit.tpl.php -->\n";

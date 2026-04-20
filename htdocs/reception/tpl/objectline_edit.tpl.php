<?php
/* Copyright (C) 2010-2012	Regis Houssin		        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012	Laurent Destailleur	    <eldy@users.sourceforge.net>
 * Copyright (C) 2012		    Christophe Battarel	    <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		    Florian Henry		        <florian.henry@open-concept.pro>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		    Vincent Maury		        <vmaury@timgroup.fr>
 * Copyright (C) 2024		    MDW						          <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		    Nick Fragoulis
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
 * Need to have the following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

require_once DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php";

/**
 * @var CommonObject $this
 * @var CommonObject $object
 * @var HookManager $hookmanager
 * @var CommonObjectLine $line
 * @var Societe $buyer
 * @var Societe $seller
 * @var Translate $langs
 *
 * @var string $action
 * @var int $i
 * @var bool $var
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

'
@phan-var-force receptionlinebatch $line
@phan-var-force CommonObject $this
@phan-var-force CommonObject $object
@phan-var-force int $i
@phan-var-force bool $var
@phan-var-force Societe $buyer
@phan-var-force Societe $seller
';

global $forceall, $filtertype;

if (empty($forceall)) {
	$forceall = 0;
}

if (empty($filtertype)) {
	$filtertype = 0;
}

$formproduct = new FormProduct($object->db);
$form = new Form($object->db);

// Define colspan for the button 'Add'
$colspan = 3;

// Lines for extrafield
$objectline = new ReceptionLineBatch($this->db);

print "<!-- BEGIN PHP TEMPLATE reception/tpl/objectline_edit.tpl.php -->\n";

$coldisplay = 0;
print '<tr class="oddeven tredited">';
// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	print '<td class="linecolnum center">'.($i + 1).'</td>';
	$coldisplay++;
}

$coldisplay++;
?>
	<td>
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
	<input type="hidden" id="product_id" name="productid" value="<?php echo(!empty($line->fk_product) ? $line->fk_product : 0); ?>" />
	<input type="hidden" id="special_code" name="special_code" value="<?php echo $line->special_code; ?>">

<?php
// Predefined product/service
if ($line->fk_product > 0) {
	$tmpproduct = new Product($object->db);
	$tmpproduct->fetch($line->fk_product);
	print $tmpproduct->getNomUrl(1);
	print ' - '.$tmpproduct->label;
}

//Line extrafield
if (!empty($extrafields)) {
	$temps = $line->showOptionals($extrafields, 'edit', array('class' => 'tredited'), '', '', '1', 'line');
	if (!empty($temps)) {
		print '<div style="padding-top: 10px" id="extrafield_lines_area_edit" name="extrafield_lines_area_edit">';
		print $temps;
		print '</div>';
	}
}

print '</td>';

$coldisplay++;

print '<td class="nobottom linecolqty right">';

if (((int) $line->info_bits & 2) != 2) {
	print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="'.$line->qty.'">';
}
print '</td>';


if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	$unit_type = false;
	// limit unit select to unit type
	if (!empty($line->fk_unit) && !getDolGlobalString('MAIN_EDIT_LINE_ALLOW_ALL_UNIT_TYPE')) {
		include_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
		$cUnit = new CUnits($line->db);
		if ($cUnit->fetch((int) $line->fk_unit) > 0) {
			if (!empty($cUnit->unit_type)) {
				$unit_type = $cUnit->unit_type;
			}
		}
	}
	$coldisplay++;
	print '<td class="left">';
	print $form->selectUnits(GETPOSTISSET('units') ? GETPOST('units') : $line->fk_unit, "units", 0, $unit_type);
	print '</td>';
}

$coldisplay += $colspan;
print '<td class="nobottom linecoledit center valignmiddle" colspan="'.$colspan.'">';
$coldisplay += $colspan;
print '<input type="submit" class="reposition button buttongen margintoponly marginbottomonly button-save" id="savelinebutton" name="save" value="'.$langs->trans("Save").'">';
print '<input type="submit" class="reposition button buttongen margintoponly marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</td>';
print '</tr>';

print "<!-- END PHP TEMPLATE objectline_edit.tpl.php -->\n";

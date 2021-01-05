<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
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
 * $forceall (0 by default, 1 for supplier invoices/orders)
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}


global $forceall, $forcetoshowtitlelines;

if (empty($forceall)) $forceall = 0;


// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete
//print $object->element;

// Lines for extrafield
$objectline = new BOMLine($this->db);

print "<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->\n";

$nolinesbefore = (count($this->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {
	print '<tr class="liste_titre nodrag nodrop">';
	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
		print '<td class="linecolnum center"></td>';
	}
	print '<td class="linecoldescription minwidth500imp">';
	print '<div id="add"></div><span class="hideonsmartphone">'.$langs->trans('AddNewLine').'</span>';
	print '</td>';
	print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';
	if (!empty($conf->global->PRODUCT_USE_UNITS))
	{
		print '<td class="linecoluseunit left">';
		print '<span id="title_units">';
		print $langs->trans('Unit');
		print '</span></td>';
	}
	print '<td class="linecolqtyfrozen right">'.$form->textwithpicto($langs->trans('QtyFrozen'), $langs->trans("QuantityConsumedInvariable")).'</td>';
	print '<td class="linecoldisablestockchange right">'.$form->textwithpicto($langs->trans('DisableStockChange'), $langs->trans('DisableStockChangeHelp')).'</td>';
	print '<td class="linecollost right">'.$form->textwithpicto($langs->trans('ManufacturingEfficiency'), $langs->trans('ValueOfMeansLoss')).'</td>';
	print '<td class="linecoledit" colspan="'.$colspan.'">&nbsp;</td>';
	print '</tr>';
}
print '<tr class="pair nodrag nodrop nohoverpair'.(($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_create').'">';
$coldisplay = 0;

// Adds a line numbering column
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	$coldisplay++;
	echo '<td class="bordertop nobottom linecolnum center"></td>';
}

$coldisplay++;
print '<td class="bordertop nobottom linecoldescription minwidth500imp">';

// Predefined product/service
if (!empty($conf->product->enabled) || !empty($conf->service->enabled))
{
	if ($forceall >= 0 && $freelines) echo '<br>';
	echo '<span class="prod_entry_mode_predef">';
	$filtertype = '';
	if (!empty($object->element) && $object->element == 'contrat' && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $filtertype = '1';

	$statustoshow = -1;
	if (!empty($conf->global->ENTREPOT_EXTRA_STATUS))
	{
		// hide products in closed warehouse, but show products for internal transfer
		$form->select_produits(GETPOST('idprod', 'int'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, 'warehouseopen,warehouseinternal', GETPOST('combinations', 'array'));
	} else {
		$form->select_produits(GETPOST('idprod', 'int'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, '', GETPOST('combinations', 'array'));
	}

	echo '</span>';
}

$coldisplay++;
print '<td class="bordertop nobottom linecolqty right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="'.(GETPOSTISSET("qty") ? GETPOST("qty", 'alpha', 2) : 1).'">';
print '</td>';

if (!empty($conf->global->PRODUCT_USE_UNITS))
{
	$coldisplay++;
	print '<td class="nobottom linecoluseunit left">';
	// print $form->selectUnits(empty($line->fk_unit) ? $conf->global->PRODUCT_USE_UNITS : $line->fk_unit, "units");
	print '</td>';
}

$coldisplay++;
print '<td class="bordertop nobottom linecolqtyfrozen right"><input type="checkbox" name="qty_frozen" id="qty_frozen" class="flat right" value="1"'.(GETPOST("qty_frozen", 'alpha') ? ' checked="checked"' : '').'>';
print '</td>';

$coldisplay++;
print '<td class="bordertop nobottom linecoldisablestockchange right"><input type="checkbox" name="disable_stock_change" id="disable_stock_change" class="flat right" value="1"'.(GETPOST("disable_stock_change", 'alpha') ? ' checked="checked"' : '').'">';
print '</td>';

$coldisplay++;
print '<td class="bordertop nobottom nowrap linecollost right">';
print '<input type="text" size="1" name="efficiency" id="efficiency" class="flat right" value="'.(GETPOSTISSET("efficiency") ?GETPOST("efficiency", 'alpha') : 1).'">';
print '</td>';

$coldisplay++;
print '<td class="bordertop nobottom nowrap linecolcost right">';
print '&nbsp;';
print '</td>';

$coldisplay += $colspan;
print '<td class="bordertop nobottom linecoledit center valignmiddle" colspan="'.$colspan.'">';
print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline" id="addline">';
print '</td>';
print '</tr>';

if (is_object($objectline)) {
	print $objectline->showOptionals($extrafields, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', 1);
}
?>

<script>

/* JQuery for product free or predefined select */
jQuery(document).ready(function() {
	/* When changing predefined product, we reload list of supplier prices required for margin combo */
	$("#idprod").change(function()
	{
		console.log("#idprod change triggered");

  		/* To set focus */
  		if (jQuery('#idprod').val() > 0)
  	  	{
			/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
			jQuery('#dp_desc').focus();
			/* focus if CKEDITOR */
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
			{
				var editor = CKEDITOR.instances['dp_desc'];
   				if (editor) { editor.focus(); }
			}
  	  	}
	});
});

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->

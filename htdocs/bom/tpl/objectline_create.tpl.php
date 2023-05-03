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

require_once DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php";

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}


global $forceall, $forcetoshowtitlelines, $filtertype;

if (empty($forceall)) {
	$forceall = 0;
}

if (empty($filtertype))	$filtertype = 0;
if (!empty($object->element) && $object->element == 'contrat' && empty($conf->global->STOCK_SUPPORT_SERVICES)) {
	$filtertype = -1;
}

$formproduct = new FormProduct($object->db);

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

	if ($filtertype != 1) {
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			print '<td class="linecoluseunit left">';
			print '<span id="title_units">';
			print $langs->trans('Unit');
			print '</span></td>';
		}
		print '<td class="linecolqtyfrozen right">' . $form->textwithpicto($langs->trans('QtyFrozen'), $langs->trans("QuantityConsumedInvariable")) . '</td>';
		print '<td class="linecoldisablestockchange right">' . $form->textwithpicto($langs->trans('DisableStockChange'), $langs->trans('DisableStockChangeHelp')) . '</td>';
		print '<td class="linecollost right">' . $form->textwithpicto($langs->trans('ManufacturingEfficiency'), $langs->trans('ValueOfMeansLoss')) . '</td>';
	} else {
		print '<td class="linecolunit right">' . $form->textwithpicto($langs->trans('Unit'), '').'</td>';
		if (isModEnabled('workstation')) print '<td class="linecolworkstation right">' .  $form->textwithpicto($langs->trans('Workstation'), '') . '</td>';
		print '<td class="linecoltotalcost right">' .  $form->textwithpicto($langs->trans('TotalCost'), '') . '</td>';
	}

	print '<td class="linecoledit" colspan="' . $colspan . '">&nbsp;</td>';
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
if (isModEnabled("product") || isModEnabled("service")) {
	if ($filtertype == 1) {
		print $langs->trans("Service");
	} else {
		print $langs->trans("Product");
	}
	echo '<span class="prod_entry_mode_predef">';
	$statustoshow = -1;
	if (!empty($conf->global->ENTREPOT_EXTRA_STATUS)) {
		// hide products in closed warehouse, but show products for internal transfer
		print $form->select_produits(GETPOST('idprod', 'int'), (($filtertype == 1) ? 'idprodservice' : 'idprod'), $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, 'warehouseopen,warehouseinternal', GETPOST('combinations', 'array'), 1);
	} else {
		print $form->select_produits(GETPOST('idprod', 'int'), (($filtertype == 1) ? 'idprodservice' : 'idprod'), $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, '', GETPOST('combinations', 'array'), 1);
	}
	$urltocreateproduct = DOL_URL_ROOT.'/product/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id);
	print '<a href="'.$urltocreateproduct.'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProduct").'"></span></a>';

	echo '</span>';
}
if (!empty($conf->global->BOM_SUB_BOM) && $filtertype!=1) {
	print '<br><span class="opacitymedium">'.$langs->trans("or").'</span><br>'.$langs->trans("BOM");
	print $form->select_bom('', 'bom_id', 0, 1, 0, '1', '', 1);
}

if (is_object($objectline)) {
	$temps = $objectline->showOptionals($extrafields, 'create', array(), '', '', 1, 'line');

	if (!empty($temps)) {
		print '<div style="padding-top: 10px" id="extrafield_lines_area_create" name="extrafield_lines_area_create">';
		print $temps;
		print '</div>';
	}
}

print '</td>';


$coldisplay++;
print '<td class="bordertop nobottom linecolqty right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="'.(GETPOSTISSET("qty") ? GETPOST("qty", 'alpha', 2) : 1).'">';
print '</td>';

if ($filtertype != 1) {
	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		$coldisplay++;
		print '<td class="nobottom linecoluseunit left">';
		print '</td>';
	}

	$coldisplay++;
	print '<td class="bordertop nobottom linecolqtyfrozen right"><input type="checkbox" name="qty_frozen" id="qty_frozen" class="flat right" value="1"' . (GETPOST("qty_frozen", 'alpha') ? ' checked="checked"' : '') . '>';
	print '</td>';


	$coldisplay++;
	print '<td class="bordertop nobottom linecoldisablestockchange right"><input type="checkbox" name="disable_stock_change" id="disable_stock_change" class="flat right" value="1"' . (GETPOST("disable_stock_change", 'alpha') ? ' checked="checked"' : '') . '">';
	print '</td>';

	$coldisplay++;
	print '<td class="bordertop nobottom nowrap linecollost right">';
	print '<input type="text" size="2" name="efficiency" id="efficiency" class="flat right" value="' . ((GETPOSTISSET("efficiency") && $action == 'addline') ? GETPOST("efficiency", 'alpha') : 1) . '">';
	print '</td>';

	$coldisplay++;
	print '<td class="bordertop nobottom nowrap linecolcost right">';
	print '&nbsp;';
	print '</td>';
} else {
	$coldisplay++;
	require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
	$cUnit = new CUnits($this->db);
	$fk_unit_default = $cUnit->getUnitFromCode('h', 'short_label', 'time');
	print '<td class="bordertop nobottom nowrap linecolunit right">';
	print  $formproduct->selectMeasuringUnits("fk_unit", "time", $fk_unit_default, 0, 0);
	print '</td>';

	$coldisplay++;
	print '<td class="bordertop nobottom nowrap linecolworkstation right">';
	print '&nbsp;';
	print '</td>';

	$coldisplay++;
	print '<td class="bordertop nobottom nowrap linecolcost right">';
	print '&nbsp;';
	print '</td>';
}

	$coldisplay += $colspan;
	print '<td class="bordertop nobottom linecoledit center valignmiddle" colspan="' . $colspan . '">';
	print '<input type="submit" class="button button-add" name="addline" id="addline" value="' . $langs->trans('Add') . '">';
	print '</td>';
	print '</tr>';

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

	//change unit selected if we change service selected
	<?php if ($filtertype == 1) { ?>
	$('#idprodservice').change(function(){
		var idproduct = $(this).val();

			$.ajax({
				url : "<?php echo dol_buildpath('/bom/ajax/ajax.php', 1); ?>"
				,type: 'POST'
				,data: {
					'action': 'getDurationUnitByProduct'
					,'idproduct' : idproduct
				}
			}).done(function(data) {

				console.log(data);
				var data = JSON.parse(data);
				$("#fk_unit").val(data).change();
			});
	});
	<?php } ?>
});

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->

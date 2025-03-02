<?php
/* Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  	<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2024		Vincent Maury			<vmaury@timgroup.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * $forceall (0 by default, 1 for supplier invoices/orders)
 */

require_once DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php";

/**
 * @var CommonObject $this
 * @var CommonObject $object
 * @var Form $form
 * @var Societe $buyer
 * @var Translate $langs
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

'
@phan-var-force CommonObject $this
@phan-var-force CommonObject $object
@phan-var-force Societe $buyer
';

global $forceall, $forcetoshowtitlelines, $filtertype;

if (empty($forceall)) {
	$forceall = 0;
}

if (empty($filtertype)) {
	$filtertype = 0;
}
if (!empty($object->element) && $object->element == 'contrat' && !getDolGlobalString('STOCK_SUPPORT_SERVICES')) {
	$filtertype = -1;
}

$formproduct = new FormProduct($object->db);

// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete
//print $object->element;

// Lines for extrafield
$objectline = new BOMLine($this->db);

print "<!-- BEGIN PHP TEMPLATE bom/tpl/objectline_create.tpl.php -->\n";

$nolinesbefore = (count($this->lines) == 0 || $forcetoshowtitlelines);

if ($nolinesbefore) {
	print '<tr class="liste_titre nodrag nodrop">';
	if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
		print '<td class="linecolnum center"></td>';
	}
	print '<td class="linecoldescription minwidth500imp">';
	print '<div id="add"></div><span class="hideonsmartphone">'.$langs->trans('AddNewLine').'</span>';
	print '</td>';
	print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';

	if ($filtertype != 1) { // Product
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			print '<td class="linecoluseunit left">';
			print '<span id="title_units">';
			print $langs->trans('Unit');
			print '</span></td>';
		}
	} else { // Service
		print '<td class="linecolunit left">' . $form->textwithpicto($langs->trans('Unit'), '').'</td>';
	}
	if ($filtertype != 1 || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) { // Product or stock support for Services is active
		// Qty frozen
		print '<td class="linecolqtyfrozen right">' . $form->textwithpicto($langs->trans('QtyFrozen'), $langs->trans("QuantityConsumedInvariable")) . '</td>';

		// Disable stock change
		print '<td class="linecoldisablestockchange right">' . $form->textwithpicto($langs->trans('DisableStockChange'), $langs->trans('DisableStockChangeHelp')) . '</td>';

		// Efficiency
		print '<td class="linecollost right">' . $form->textwithpicto($langs->trans('ManufacturingEfficiency'), $langs->trans('ValueOfMeansLoss')) . '</td>';
	}

	// Service and workstations are active
	if ($filtertype == 1 && isModEnabled('workstation')) {
		print '<td class="linecolworkstation">' .  $form->textwithpicto($langs->trans('Workstation'), '') . '</td>';
	}
	// Cost
	print '<td class="linecoltotalcost right">' .  $form->textwithpicto($langs->trans('TotalCost'), '') . '</td>';

	print '<td class="linecoledit" colspan="' . $colspan . '">&nbsp;</td>';
	print '</tr>';
}

print '<tr class="pair nodrag nodrop nohoverpair'.(($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_create').'">';
$coldisplay = 0;

// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	$coldisplay++;
	echo '<td class="bordertop nobottom linecolnum center"></td>';
}

$coldisplay++;
print '<td class="bordertop nobottom linecoldescription bomline minwidth500imp">';

// Predefined product/service
if (isModEnabled("product") || isModEnabled("service")) {
	if ($filtertype == 1) {
		print $langs->trans("Service");
	} else {
		print $langs->trans("Product");
	}

	echo '<span class="prod_entry_mode_predef nowraponall">';

	$statustoshow = -1;
	if (getDolGlobalString('ENTREPOT_EXTRA_STATUS')) {
		// hide products in closed warehouse, but show products for internal transfer
		print $form->select_produits(GETPOSTINT('idprod'), (($filtertype == 1) ? 'idprodservice' : 'idprod'), $filtertype, getDolGlobalInt('PRODUIT_LIMIT_SIZE'), 0, $statustoshow, 2, '', 1, array(), 0, '1', 0, 'maxwidth500 widthcentpercentminusx', 0, 'warehouseopen,warehouseinternal', GETPOSTINT('combinations'), 1);
	} else {
		print $form->select_produits(GETPOSTINT('idprod'), (($filtertype == 1) ? 'idprodservice' : 'idprod'), $filtertype, getDolGlobalInt('PRODUIT_LIMIT_SIZE'), 0, $statustoshow, 2, '', 1, array(), 0, '1', 0, 'maxwidth500 widthcentpercentminusx', 0, '', GETPOSTINT('combinations'), 1);
	}
	$urltocreateproduct = DOL_URL_ROOT.'/product/card.php?action=create'.(($filtertype == 1) ? '&leftmenu=service&type=1' : '&leftmenu=product&type=0').'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id);
	print '<a href="'.$urltocreateproduct.'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProduct").'"></span></a>';

	echo '</span>';
}
if (getDolGlobalString('BOM_SUB_BOM') && $filtertype != 1) {
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

if ($filtertype != 1) { // Product
	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		$coldisplay++;
		print '<td class="nobottom linecoluseunit">';
		print '</td>';
	}
} else { // Service
	$coldisplay++;
	require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
	$cUnit = new CUnits($this->db);
	$fk_unit_default = $cUnit->getUnitFromCode('h', 'short_label', 'time');
	print '<td class="bordertop nobottom nowrap linecolunit">';
	print $formproduct->selectMeasuringUnits("fk_unit", "time", $fk_unit_default, 1);
	print '</td>';
}
if ($filtertype != 1 || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) { // Product or stock support for Services is active
	// Qty frozen
	$coldisplay++;
	print '<td class="bordertop nobottom linecolqtyfrozen right"><input type="checkbox" name="qty_frozen" id="qty_frozen" class="flat right" value="1"' . (GETPOST("qty_frozen", 'alpha') ? ' checked="checked"' : '') . '>';
	print '</td>';

	// Disable stock change
	$coldisplay++;
	print '<td class="bordertop nobottom linecoldisablestockchange right"><input type="checkbox" name="disable_stock_change" id="disable_stock_change" class="flat right" value="1"' . (GETPOST("disable_stock_change", 'alpha') ? ' checked="checked"' : '') . '">';
	print '</td>';

	// Efficiency
	$coldisplay++;
	print '<td class="bordertop nobottom nowrap linecollost right">';
	print '<input type="text" size="2" name="efficiency" id="efficiency" class="flat right" value="' . ((GETPOSTISSET("efficiency") && $action == 'addline') ? GETPOST("efficiency", 'alpha') : 1) . '">';
	print '</td>';
}
// Service and workstations are active
if ($filtertype == 1 && isModEnabled('workstation')) {
	$coldisplay++;
	print '<td class="bordertop nobottom nowrap linecolworkstation">';
	print $formproduct->selectWorkstations('', 'idworkstations', 1);
	print '</td>';
}

// Cost
$coldisplay++;
print '<td class="bordertop nobottom nowrap linecolcost right">';
print '&nbsp;';
print '</td>';


$coldisplay += $colspan;
print '<td class="bordertop nobottom linecoledit right valignmiddle" colspan="' . $colspan . '">';
print '<input type="submit" class="button button-add small" name="addline" id="addline" value="' . $langs->trans('Add') . '">';
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
					,'token' : "<?php echo newToken() ?>"
					,'idproduct' : idproduct
				}
			}).done(function(data) {

				console.log(data);
				$("#fk_unit").val(data).change();
			});

			$.ajax({
				url : "<?php echo dol_buildpath('/bom/ajax/ajax.php', 1); ?>"
				,type: 'POST'
				,data: {
					'action': 'getWorkstationByProduct'
					,'token' :  "<?php echo newToken() ?>"
					,'idproduct' : idproduct
				}
			}).done(function(data) {
				$('#idworkstations').val(data.defaultWk).select2();
			});
	});
	<?php } ?>
});

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->

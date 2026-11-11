<?php
/* Copyright (C) 2010-2012	Regis Houssin			    <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry			    <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  		<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García			    <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2018		Ferran Marcet			    <fmarcet@2byte.es>
 * Copyright (C) 2024		Vincent Maury			    <vmaury@timgroup.fr>
 * Copyright (C) 2024-2025	MDW						    <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Nick Fragoulis
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

$formproduct = new FormProduct($object->db);

// Define colspan for the button 'Add'
$colspan = 3;


// Lines for extrafield
$objectline = new ReceptionLineBatch($this->db);

print "<!-- BEGIN PHP TEMPLATE reception/tpl/objectline_create.tpl.php -->\n";

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

	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		print '<td class="linecoluseunit left">';
		print '<span id="title_units">';
		print $langs->trans('Unit');
		print '</span></td>';
	}

	print '</tr>';
}

print '<tr class="pair nodrag nodrop nohoverpair'.(($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_create').'">';
$coldisplay = 0;

// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	$coldisplay++;
	echo '<td class="bordertop nobottom linecolnum center"></td>';
}

// Product
$coldisplay++;
print '<td class="bordertop nobottom linecoldescription line minwidth500imp">';

// Predefined product/service
if (isModEnabled("product")) {
	if ($filtertype == 1) {
		print $langs->trans("Service");
	} else {
		print $langs->trans("Product");
	}

	echo '<span class="prod_entry_mode_predef nowraponall">';

	$statustoshow = -1;

	echo '</span>';
}


if (!empty($extrafields)) {
	$temps = $objectline->showOptionals($extrafields, 'create', array(), '', '', '1', 'line');

	if (!empty($temps)) {
		print '<div style="padding-top: 10px" id="extrafield_lines_area_create" name="extrafield_lines_area_create">';
		print $temps;
		print '</div>';
	}
}
print '</td>';

// Qty
$coldisplay++;
print '<td class="bordertop nobottom linecolqty right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="'.(GETPOSTISSET("qty") ? GETPOST("qty", 'alpha', 2) : 1).'">';
print '</td>';

// Unit
if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
	$coldisplay++;
	print '<td class="nobottom linecoluseunit">';
	print '</td>';
}

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
});

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->

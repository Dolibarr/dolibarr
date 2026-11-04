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
	$filtertype = '';	// Products and services, as before with the generic line form
}

$formproduct = new FormProduct($object->db);

// Define colspan for the button 'Add'
$colspan = 3;


// Lines for extrafield
$objectline = new ExpeditionLigne($this->db);

print "<!-- BEGIN PHP TEMPLATE expedition/tpl/objectline_create.tpl.php -->\n";

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
	print '<td colspan="'.$colspan.'"></td>';

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

// Free line
$freelines = !getDolGlobalString('MAIN_DISABLE_FREE_LINES');
if ($freelines) {
	print '<span class="prod_entry_mode_free nowraponall">';
	if (isModEnabled("product") || isModEnabled("service")) {
		print '<input type="radio" class="prod_entry_mode_free" name="prod_entry_mode" id="prod_entry_mode_free" value="free"'.((GETPOST('prod_entry_mode', 'alpha') == 'free' || getDolGlobalString('MAIN_FREE_PRODUCT_CHECKED_BY_DEFAULT')) ? ' checked' : '').'> ';
	} else {
		print '<input type="hidden" id="prod_entry_mode_free" name="prod_entry_mode" value="free">';
	}
	$form->select_type_of_lines(GETPOSTISSET("type") ? GETPOST("type", 'alpha', 2) : -1, 'type', $langs->trans("FreeLineOfType").'...', 1, 1, 'minwidth200', 0);
	print '</span>';
}

// Predefined product/service
if (isModEnabled("product") || isModEnabled("service")) {
	print '<span class="prod_entry_mode_predef nowraponall">';
	if ($freelines) {
		print '<br><input type="radio" class="prod_entry_mode_predef" name="prod_entry_mode" id="prod_entry_mode_predef" value="predef"'.(GETPOST('prod_entry_mode', 'alpha') == 'predef' ? ' checked' : '').'> ';
	}

	if (isModEnabled("product") && !isModEnabled("service")) {
		$labelforradio = $langs->trans('PredefinedProducts');
	} elseif (!isModEnabled("product") && isModEnabled("service")) {
		$labelforradio = $langs->trans('PredefinedServices');
	} else {
		$labelforradio = $langs->trans('PredefinedProductsAndServices');
	}

	$statustoshow = -1;
	$form->select_produits(GETPOSTINT('idprod'), 'idprod', $filtertype, getDolGlobalInt('PRODUIT_LIMIT_SIZE'), 0, $statustoshow, 2, '', 1, array(), 0, $labelforradio, 0, 'maxwidth500 widthcentpercentminusx', 1);

	print '</span>';
}

// Description
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$nbrows = getDolGlobalInt('MAIN_INPUT_DESC_HEIGHT', ROWS_2);
$toolbarname = getDolGlobalString('FCKEDITOR_ENABLE_DETAILS_FULL') ? 'dolibarr_notes' : 'dolibarr_details';
$doleditor = new DolEditor('dp_desc', GETPOST('dp_desc', 'restricthtml'), '', getDolGlobalInt('MAIN_DOLEDITOR_HEIGHT', 100), $toolbarname, '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS'), $nbrows, '98%');
$doleditor->Create();


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
			jQuery('#prod_entry_mode_predef').prop('checked', true);
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
	/* When choosing a type of free line, we select the free line mode */
	$("#select_type").change(function()
	{
		if (jQuery('#select_type').val() >= 0) {
			jQuery('#prod_entry_mode_free').prop('checked', true);
		}
	});
});

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->

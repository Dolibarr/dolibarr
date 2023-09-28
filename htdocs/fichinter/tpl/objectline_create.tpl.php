<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019		Nicolas ZABOURI		<info@inovea-conseil.com>
 * Copyright (C) 2022		OpenDSI				<support@open-dsi.fr>
 * Copyright (C) 2022      	Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
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
 * $db
 * $form
 * $hookmanager
 * $langs
 * $action, extrafields
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $nolinesbefore
 */

global $object;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

global $conf, $db, $form, $hookmanager, $langs;
global $action, $extrafields;

if (!isset($dateSelector)) {
	global $dateSelector; // Take global var only if not already defined into function calling (for example formAddObjectLine)
}
global $forceall, $nolinesbefore;

if (!isset($dateSelector)) {
	$dateSelector = 1; // For backward compatibility
} elseif (empty($dateSelector)) {
	$dateSelector = 0;
}
if (empty($forceall)) {
	$forceall = 0;
}
$senderissupplier = 0;
$inputalsopricewithtax = 0;

// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete

// Lines for extrafield
$objectline = new FichinterLigne($db);

print "<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->\n";
if ($nolinesbefore) {
	?>
	<tr class="liste_titre nodrag nodrop">
		<?php if (getDolGlobalInt('MAIN_VIEW_LINE_NUMBER')) { ?>
			<td class="linecolnum center"></td>
		<?php } ?>
		<td class="linecoldescription minwidth400imp">
			<div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('AddNewLine'); ?></span>
		</td>
		<td class="linecolqty right"><?php echo $langs->trans('Qty'); ?></td>
		<?php
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			print '<td class="linecoluseunit left">';
			print '<span id="title_units">';
			print $langs->trans('Unit');
			print '</span></td>';
		}

		// Date intervention
		print '<td class="linecoldate center">'.$langs->trans('Date').'</td>';

		// Duration
		if (!getDolGlobalInt('FICHINTER_WITHOUT_DURATION')) {
			print '<td class="linecolduration right">'.$langs->trans('Duration').'</td>';
		}
		?>
		<td class="linecoledit" colspan="<?php echo $colspan; ?>">&nbsp;</td>
	</tr>
	<?php
}
?>
<tr class="pair nodrag nodrop nohoverpair<?php echo $nolinesbefore ? '' : ' liste_titre_create'; ?>">
	<?php
	$coldisplay = 0;

	// Adds a line numbering column
	if (getDolGlobalInt('MAIN_VIEW_LINE_NUMBER')) {
		$coldisplay++;
		echo '<td class="nobottom linecolnum center"></td>';
	}

	$coldisplay++;
	?>
	<td class="nobottom linecoldescription minwidth400imp">
		<?php
		$freelines = false;
		if (!getDolGlobalInt('MAIN_DISABLE_FREE_LINES')) {
			$freelines = true;
			$forceall = 1; // We always force all type for free lines (module product or service means we use predefined product or service)
			// Free line
			echo '<span class="prod_entry_mode_free">';
			// Show radio free line
			if (isModEnabled("product") || isModEnabled("service")) {
				echo '<label for="prod_entry_mode_free">';
				echo '<input type="radio" class="prod_entry_mode_free" name="prod_entry_mode" id="prod_entry_mode_free" value="free"';
				echo ((GETPOST('prod_entry_mode', 'alpha') == 'free' || !empty($conf->global->MAIN_FREE_PRODUCT_CHECKED_BY_DEFAULT)) ? ' checked' : '');
				echo '> ';
				// Show type selector
				echo '<span class="textradioforitem">'.$langs->trans("FreeLineOfType").'</span>';
				echo '</label>';
				echo ' ';
			}
			$form->select_type_of_lines(GETPOSTISSET("type") ? GETPOST("type", 'alpha', 2) : -1, 'type', 1, 1, $forceall);
			echo '</span>';
		}
		// Predefined product/service
		if (isModEnabled("product") || isModEnabled("service")) {
			if ($forceall >= 0 && $freelines) {
				echo '<br><span class="prod_entry_mode_predef paddingtop">';
			} else {
				echo '<span class="prod_entry_mode_predef">';
			}
			echo '<label for="prod_entry_mode_predef">';
			echo '<input type="radio" class="prod_entry_mode_predef" name="prod_entry_mode" id="prod_entry_mode_predef" value="predef"'.(GETPOST('prod_entry_mode') == 'predef' ? ' checked' : '').'> ';
			$labelforradio = '';
			if (empty($conf->dol_optimize_smallscreen)) {
				if (isModEnabled("product") && !isModEnabled('service')) {
					$labelforradio = $langs->trans('PredefinedProductsToSell');
				} elseif ((!isModEnabled('product') && isModEnabled('service')) || ($object->element == 'contrat' && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS))) {
					$labelforradio = $langs->trans('PredefinedServicesToSell');
				} else {
					$labelforradio = $langs->trans('PredefinedProductsAndServicesToSell');
				}
			} else {
				$labelforradio = $langs->trans('PredefinedItem');
			}
			print '<span class="textradioforitem">'.$labelforradio.'</span>';
			echo '</label>';
			echo ' ';
			$filtertype = '';
			$statustoshow = 1;
			$statuswarehouse = 'warehouseopen,warehouseinternal';
			if (!empty($conf->global->ENTREPOT_WAREHOUSEINTERNAL_NOT_SELL)) $statuswarehouse = 'warehouseopen';
			if (!empty($conf->global->ENTREPOT_EXTRA_STATUS)) {
				// hide products in closed warehouse, but show products for internal transfer
				$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, 0, $statustoshow, 2, '', 1, array(), 0, '1', 0, 'maxwidth500', 0, $statuswarehouse, GETPOST('combinations', 'array'));
			} else {
				$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, 0, $statustoshow, 2, '', 1, array(), 0, '1', 0, 'maxwidth500', 0, '', GETPOST('combinations', 'array'));
			}
			if (!empty($conf->global->MAIN_AUTO_OPEN_SELECT2_ON_FOCUS_FOR_CUSTOMER_PRODUCTS)) {
				print '
                <script>
                $(document).ready(function () {
                    // On first focus on a select2 combo, auto open the menu (this allow to use the keyboard only)
                    $(document).on("focus", ".select2-selection.select2-selection--single", function(e) {
                        console.log("focus on a select2");
                        if ($(this).attr("aria-labelledby") == "select2-idprod-container") {
                            console.log("open combo");
                            $("#idprod").select2("open");
                        }
                    });
                });
                </script>';
			}
			echo '<input type="hidden" name="pbq" id="pbq" value="">';
			echo '</span>';
		}

		if (!empty($conf->global->MAIN_ADD_LINE_AT_POSITION)) {
			echo '<br>'.$langs->trans('AddLineOnPosition').' : <input type="number" name="rank" step="1" min="0" style="width: 5em;">';
		}

		if (is_object($hookmanager)) {
			$parameters = array('fk_parent_line'=>GETPOST('fk_parent_line', 'int'));
			$reshook = $hookmanager->executeHooks('formCreateProductOptions', $parameters, $object, $action);
			if (!empty($hookmanager->resPrint)) {
				print $hookmanager->resPrint;
			}
		}
		if (isModEnabled("product") || isModEnabled("service")) {
			echo '<br>';
			if (isModEnabled('variants')) {
				echo '<div id="attributes_box"></div>';
			}
		}
		// Editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows = ROWS_2;
		$enabled = (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
		if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) {
			$nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
		}
		$toolbarname = 'dolibarr_details';
		if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) {
			$toolbarname = 'dolibarr_notes';
		}
		$doleditor = new DolEditor('np_desc', GETPOST('np_desc', 'restricthtml'), '', (empty($conf->global->MAIN_DOLEDITOR_HEIGHT) ? 100 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enabled, $nbrows, '98%');
		$doleditor->Create();
		if (is_object($objectline)) {
			$temps = $objectline->showOptionals($extrafields, 'create', array(), '', '', 1, 'line');

			if (!empty($temps)) {
				print '<div style="padding-top: 10px" id="extrafield_lines_area_create" name="extrafield_lines_area_create">';
				print $temps;
				print '</div>';
			}
		}
		echo '</td>';

		$coldisplay++;
		?>
	<td class="nobottom linecolqty right">
	<?php $default_qty = (empty($conf->global->MAIN_OBJECTLINE_CREATE_EMPTY_QTY_BY_DEFAULT) ? 1 : ''); ?>
	<input type="text" name="qty" id="qty" class="flat width40 right" value="<?php echo (GETPOSTISSET('qty') ? GETPOST('qty', 'alphanohtml', 2) : $default_qty); ?>">
	</td>
	<?php
	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		$coldisplay++;
		print '<td class="nobottom linecoluseunit left">';
		print $form->selectUnits(GETPOSTISSET('units') ? GETPOST('units', 'int') : 0, 'units');
		print '</td>';
	}

	// Date intervention
	print '<td class="center nowrap">';
	$now = dol_now();
	$timearray = dol_getdate($now);
	if (!GETPOST('diday', 'int')) {
		$timewithnohour = dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
	} else {
		$timewithnohour = dol_mktime(GETPOST('dihour', 'int'), GETPOST('dimin', 'int'), 0, GETPOST('dimonth', 'int'), GETPOST('diday', 'int'), GETPOST('diyear', 'int'));
	}
	if (getDolGlobalInt('FICHINTER_DATE_WITHOUT_HOUR')) {
		print $form->selectDate($timewithnohour, 'di', 0, 0, 0, "addinter");
	} else {
		print $form->selectDate($timewithnohour, 'di', 1, 1, 0, "addinter");
	}
	print '</td>';

	// Duration
	print '<td class="right">';
	if (!getDolGlobalInt('FICHINTER_WITHOUT_DURATION')) {
		$selectmode = 'select';
		if (!empty($conf->global->INTERVENTION_ADDLINE_FREEDUREATION)) {
			$selectmode = 'text';
		}
		$form->select_duration('duration', (!GETPOST('durationhour', 'int') && !GETPOST('durationmin', 'int')) ? 3600 : (60 * 60 * GETPOST('durationhour', 'int') + 60 * GETPOST('durationmin', 'int')), 0, $selectmode);
	}
	print '</td>';

	$coldisplay += $colspan;
	?>
	<td class="nobottom linecoledit center valignmiddle" colspan="<?php echo $colspan; ?>">
		<input type="submit" class="button reposition" value="<?php echo $langs->trans('Add'); ?>" name="addline" id="addline">
	</td>
</tr>

<?php

print "<script>\n";
?>

	/* JQuery for product free or predefined select */
	jQuery(document).ready(function() {
		jQuery("#price_ht").keyup(function(event) {
			// console.log(event.which);		// discard event tag and arrows
			if (event.which != 9 && (event.which < 37 ||event.which > 40) && jQuery("#price_ht").val() != '') {
			jQuery("#price_ttc").val('');
			jQuery("#multicurrency_subprice").val('');
		}
	});

	$("#prod_entry_mode_free").on( "click", function() {
		setforfree();
	});
	$("#select_type").change(function()
	{
		setforfree();

		if (jQuery('#select_type').val() >= 0) {
			console.log("Set focus on description field");
			/* this focus code works on a standard textarea but not if field was replaced with CKEDITOR */
			jQuery('#np_desc').focus();
			/* this focus code works for CKEDITOR */
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined") {
				var editor = CKEDITOR.instances['np_desc'];
				if (editor) {
					editor.focus();
				}
			}
		}
	});

	$("#prod_entry_mode_predef").on( "click", function() {
		console.log("click prod_entry_mode_predef");
		setforpredef();
	});

<?php
if (!$freelines) {
	print '$("#prod_entry_mode_predef").click();';
}
?>

	/* When changing predefined product, we reload list of supplier prices required for margin combo */
	$("#idprod").change(function()
	{
		console.log("objectline_create.tpl Call method change() after change on #idprod. this.val = "+$(this).val());

		setforpredef();		// TODO Keep vat combo visible and set it to first entry into list that match result of get_default_tva(product)

		/* To set focus */
		if (jQuery('#idprod').val() > 0)
		{
			/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
			jQuery('#np_desc').focus();
			/* focus if CKEDITOR */
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
			{
				var editor = CKEDITOR.instances['np_desc'];
				if (editor) { editor.focus(); }
			}
		}
	});

		<?php if (GETPOST('prod_entry_mode') == 'predef') { // When we submit with a predef product and it fails we must start with predef ?>
		setforpredef();
		<?php } ?>
	});

	/* Function to set fields visibility after selecting a free product */
	function setforfree() {
		console.log("objectline_create.tpl::setforfree. We show most fields");
		jQuery("#prod_entry_mode_free").prop("checked", true).change();
		jQuery("#prod_entry_mode_predef").prop("checked", false).change();
		jQuery("#search_idprod, #idprod").val("");
		jQuery("#units, #title_units").show();
	}

	function setforpredef() {
		console.log("objectline_create.tpl::setforpredef We hide some fields");
		jQuery("#select_type").val(-1);
		jQuery("#prod_entry_mode_free").prop('checked',false).change();
		jQuery("#prod_entry_mode_predef").prop('checked',true).change();
		jQuery("#units, #title_units").hide();
	}

<?php

print '</script>';

print "<!-- END PHP TEMPLATE objectline_create.tpl.php -->\n";

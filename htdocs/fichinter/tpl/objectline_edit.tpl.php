<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2022	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022		OpenDSI				<support@open-dsi.fr>
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
 * $object (fichinter)
 * $conf
 * $hookmanager
 * $langs
 * $form
 * $buyer, $seller
 * action, $extrafields, $i, $line, $object, $var
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $canchangeproduct (0 by default, 1 to allow to change the product if it is a predefined product)
 */

global $object;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

/**
 * @var FichinterLigne $line
 */

global $conf, $form, $hookmanager, $langs;
global $buyer, $seller;
global $action, $extrafields, $i, $line, $object, $var;
global $forceall, $canchangeproduct;
if (empty($dateSelector)) {
	$dateSelector = 0;
}
if (empty($forceall)) {
	$forceall = 0;
}
if (empty($canchangeproduct)) {
	$canchangeproduct = 0;
}

// Define colspan for the button 'Add'
$colspan = 3; // col edit + col delete + col move

print "<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->\n";

$coldisplay = 0;
?>
<tr class="oddeven tredited">
<?php if (getDolGlobalInt('MAIN_VIEW_LINE_NUMBER')) { ?>
		<td class="linecolnum center"><?php $coldisplay++; ?><?php echo ($i + 1); ?></td>
<?php }

$coldisplay++;
?>
	<td class="linecoldesc minwidth250onall">
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
	<input type="hidden" id="special_code" name="special_code" value="<?php echo $line->special_code; ?>">
	<input type="hidden" id="fk_parent_line" name="fk_parent_line" value="<?php echo $line->fk_parent_line; ?>">

	<?php if ($line->fk_product > 0) { ?>
		<?php
		if (empty($canchangeproduct)) {
			if ($line->fk_parent_line > 0) {
				echo img_picto('', 'rightarrow');
			}
			if (is_object($line->product)) {
				print $line->product->getNomUrl(1);
			}

			print '<input type="hidden" id="product_id" name="productid" value="'.(!empty($line->fk_product) ? $line->fk_product : 0).'">';
		} else {
			print $form->select_produits(!empty($line->fk_product) ? $line->fk_product : 0, 'productid');
		}
		?>
		<br><br>
	<?php }	?>

	<?php
	if (is_object($hookmanager)) {
		$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOST('fk_parent_line', 'int') : $line->fk_parent_line);
		$parameters = array('line'=>$line, 'fk_parent_line'=>$fk_parent_line, 'var'=>$var, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer);
		$reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $object, $action);
	}

	// editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$nbrows = ROWS_2;
	if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) {
		$nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
	}
	$enable = (isset($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
	$toolbarname = 'dolibarr_details';
	if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) {
		$toolbarname = 'dolibarr_notes';
	}
	$doleditor = new DolEditor('np_desc', GETPOSTISSET('np_desc') ? GETPOST('np_desc', 'restricthtml') : $line->description, '', (empty($conf->global->MAIN_DOLEDITOR_HEIGHT) ? 164 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enable, $nbrows, '98%');
	$doleditor->Create();

	//Line extrafield
	if (!empty($extrafields)) {
		$temps = $line->showOptionals($extrafields, 'edit', array('class'=>'tredited'), '', '', 1, 'line');
		if (!empty($temps)) {
			print '<div style="padding-top: 10px" id="extrafield_lines_area_edit" name="extrafield_lines_area_edit">';
			print $temps;
			print '</div>';
		}
	}

	?>
	</td>
	<?php
	print '<td class="right">';
	$coldisplay++;
	print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="';
	print (GETPOSTISSET('qty') ? GETPOST('qty', 'alphanohtml') : $line->qty);
	print '">';
	print '</td>';

	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		$unit_type = false;
		// limit unit select to unit type
		if (!empty($line->fk_unit) && empty($conf->global->MAIN_EDIT_LINE_ALLOW_ALL_UNIT_TYPE)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
			$cUnit = new CUnits($line->db);
			if ($cUnit->fetch($line->fk_unit) > 0) {
				if (!empty($cUnit->unit_type)) {
					$unit_type = $cUnit->unit_type;
				}
			}
		}
		$coldisplay++;
		print '<td class="left">';
		print $form->selectUnits(GETPOSTISSET('units') ? GETPOST('units', 'int') : $line->fk_unit, 'units', 0, $unit_type);
		print '</td>';
	}

	// Date d'intervention
	print '<td class="center nowrap">';
	if (getDolGlobalInt('FICHINTER_DATE_WITHOUT_HOUR')) {
		print $form->selectDate($line->date, 'di', 0, 0, 0, 'date_intervention');
	} else {
		print $form->selectDate($line->date, 'di', 1, 1, 0, 'date_intervention');
	}
	print '</td>';

	// Duration
	print '<td class="right">';
	if (!getDolGlobalInt('FICHINTER_WITHOUT_DURATION')) {
		$selectmode = 'select';
		if (!empty($conf->global->INTERVENTION_ADDLINE_FREEDUREATION)) {
			$selectmode = 'text';
		}
		$form->select_duration('duration', $line->duree, 0, $selectmode);
	}
	print '</td>';
	?>

	<!-- actions : edit, delete and move -->
	<td class="center valignmiddle" colspan="<?php echo $colspan; ?>"><?php $coldisplay += $colspan; ?>
		<input type="submit" class="reposition button buttongen marginbottomonly button-save" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="reposition button buttongen marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

<!-- END PHP TEMPLATE objectline_edit.tpl.php -->

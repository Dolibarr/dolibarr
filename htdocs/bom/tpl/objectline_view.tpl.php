<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * $element     (used to test $user->hasRight($element, 'creer'))
 * $permtoedit  (used to replace test $user->hasRight($element, 'creer'))
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $type, $text, $description, $line
 */

/**
 * @var CommonObjectLine $line
 * @var int $num
 */
'@phan-var-force CommonObjectLine $line
 @phan-var-force int $num
 @phan-var-force CommonObject $this
 @phan-var-force CommonObject $object';

require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}


global $filtertype;
if (empty($filtertype)) {
	$filtertype = 0;
}


global $forceall, $senderissupplier, $inputalsopricewithtax, $outputalsopricetotalwithtax, $langs;

if (empty($dateSelector)) {
	$dateSelector = 0;
}
if (empty($forceall)) {
	$forceall = 0;
}
if (empty($senderissupplier)) {
	$senderissupplier = 0;
}
if (empty($inputalsopricewithtax)) {
	$inputalsopricewithtax = 0;
}
if (empty($outputalsopricetotalwithtax)) {
	$outputalsopricetotalwithtax = 0;
}

// add html5 elements
if ($filtertype == 1) {
	$domData  = ' data-element="'.$line->element.'service"';
} else {
	$domData  = ' data-element="'.$line->element.'"';
}

$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';
$domData .= ' data-product_type="'.$line->product_type.'"';

// Lines for extrafield
$objectline = new BOMLine($object->db);

$coldisplay = 0;
print "<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->\n";
print '<tr id="row-'.$line->id.'" class="drag drop oddeven" '.$domData.' >';

// Line nb
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	print '<td class="linecolnum center">'.($i + 1).'</td>';
	$coldisplay++;
}

// Product
print '<td class="linecoldescription bomline minwidth300imp">';
print '<div id="line_'.$line->id.'"></div>';
$coldisplay++;
$tmpproduct = new Product($object->db);
$tmpproduct->fetch($line->fk_product);
$tmpbom = new BOM($object->db);
$res = $tmpbom->fetch($line->fk_bom_child);
if ($tmpbom->id > 0) {
	print $tmpproduct->getNomUrl(1);
	print ' '.$langs->trans("or").' ';
	print $tmpbom->getNomUrl(1);
	print ' <a class="collapse_bom" id="collapse-'.$line->id.'" href="#">';
	print(!getDolGlobalString('BOM_SHOW_ALL_BOM_BY_DEFAULT') ? img_picto('', 'folder') : img_picto('', 'folder-open'));
	print '</a>';
} else {
	print $tmpproduct->getNomUrl(1);
	print ' - '.$tmpproduct->label;
}

// Line extrafield
if (!empty($extrafields)) {
	$temps = $line->showOptionals($extrafields, 'view', array(), '', '', 1, 'line');
	if (!empty($temps)) {
		print '<div style="padding-top: 10px" id="extrafield_lines_area_'.$line->id.'" name="extrafield_lines_area_'.$line->id.'">';
		print $temps;
		print '</div>';
	}
}

print '</td>';

// Qty
print '<td class="linecolqty nowrap right">';
$coldisplay++;
echo price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formatting role of function price
print '</td>';

if ($filtertype != 1) { // Product
	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {		// For product, unit is shown only if option PRODUCT_USE_UNITS is on
		print '<td class="linecoluseunit nowrap">';
		$label = measuringUnitString($line->fk_unit, '', '', 1);
		if ($label !== '') {
			print $langs->trans($label);
		}
		print '</td>';
	}
} else { // Service
	// Unit											// For services, units are always enabled
	print '<td class="linecolunit nowrap">';
	$coldisplay++;

	if (!empty($line->fk_unit)) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
		$unit = new CUnits($this->db);
		$unit->fetch($line->fk_unit);
		print(isset($unit->label) ? "&nbsp;".$langs->trans(ucwords($unit->label))."&nbsp;" : '');
	}

	print '</td>';
}
if ($filtertype != 1 || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) { // Product or stock support for Services is active
	// Qty frozen
	print '<td class="linecolqtyfrozen nowrap right">';
	$coldisplay++;
	echo $line->qty_frozen ? yn($line->qty_frozen) : '';
	print '</td>';

	// Disable stock change
	print '<td class="linecoldisablestockchange nowrap right">';
	$coldisplay++;
	echo $line->disable_stock_change ? yn($line->disable_stock_change) : ''; // Yes, it is a quantity, not a price, but we just want the formatting role of function price
	print '</td>';

	// Efficiency
	print '<td class="linecolefficiency nowrap right">';
	$coldisplay++;
	echo $line->efficiency;
	print '</td>';
}

// Service and workstations are active
if ($filtertype == 1 && isModEnabled('workstation')) {
	$workstation = new Workstation($object->db);
	$res = $workstation->fetch($line->fk_default_workstation);

	print '<td class="linecolworkstation nowrap">';
	$coldisplay++;
	if ($res > 0) {
		echo $workstation->getNomUrl(1);
	}
	print '</td>';
}

// Cost
$total_cost = 0;
$tmpbom->calculateCosts();
print '<td id="costline_'.$line->id.'" class="linecolcost nowrap right">';
$coldisplay++;
if (!empty($line->fk_bom_child)) {
	echo '<span class="amount">'.price($tmpbom->total_cost * (float) $line->qty).'</span>';
} else {
	echo '<span class="amount">'.price($line->total_cost).'</span>';
}
print '</td>';

if ($this->status == 0 && ($object_rights->write) && $action != 'selectlines') {
	print '<td class="linecoledit center">';
	$coldisplay++;
	if (($line->info_bits & 2) == 2 || !empty($disableedit)) {
	} else {
		print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=editline&token='.newToken().'&lineid='.$line->id.'">'.img_edit().'</a>';
	}
	print '</td>';

	print '<td class="linecoldelete center">';
	$coldisplay++;
	if (($line->fk_prev_id == null) && empty($disableremove)) {
		//La suppression n'est autorisée que si il n'y a pas de ligne dans une précédente situation
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=deleteline&token='.newToken().'&lineid='.$line->id.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';

	if ($num > 1 && $conf->browser->layout != 'phone' && empty($disablemove)) {
		print '<td class="linecolmove tdlineupdown center">';
		$coldisplay++;
		if ($i > 0) {
			print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=up&token='.newToken().'&rowid='.$line->id.'">';
			echo img_up('default', 0, 'imgupforline');
			print '</a>';
		}
		if ($i < $num - 1) {
			print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=down&token='.newToken().'&rowid='.$line->id.'">';
			echo img_down('default', 0, 'imgdownforline');
			print '</a>';
		}
		print '</td>';
	} else {
		print '<td '.(($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'></td>';
		$coldisplay++;
	}
} else {
	print '<td colspan="3"></td>';
	$coldisplay = $coldisplay + 3;
}

if ($action == 'selectlines') {
	print '<td class="linecolcheck center">';
	print '<input type="checkbox" class="linecheckbox" name="line_checkbox['.($i + 1).']" value="'.$line->id.'" >';
	print '</td>';
}

print '</tr>';

// Select of all the sub-BOM lines
// From this point to the end of the file, we only take care of sub-BOM lines
$sql = 'SELECT rowid, fk_bom_child, fk_product, qty FROM '.MAIN_DB_PREFIX.'bom_bomline AS bl';
$sql .= ' WHERE fk_bom ='. (int) $tmpbom->id;
$resql = $object->db->query($sql);

if ($resql) {
	// Loop on all the sub-BOM lines if they exist
	while ($obj = $object->db->fetch_object($resql)) {
		$sub_bom_product = new Product($object->db);
		$sub_bom_product->fetch($obj->fk_product);

		$sub_bom = new BOM($object->db);
		if (!empty($obj->fk_bom_child)) {
			$sub_bom->fetch($obj->fk_bom_child);
		}

		$sub_bom_line = new BOMLine($object->db);
		$sub_bom_line->fetch($obj->rowid);

		//If hidden conf is set, we show directly all the sub-BOM lines
		if (!getDolGlobalString('BOM_SHOW_ALL_BOM_BY_DEFAULT')) {
			print '<tr style="display:none" class="sub_bom_lines" parentid="'.$line->id.'">';
		} else {
			print '<tr class="sub_bom_lines" parentid="'.$line->id.'">';
		}

		// Product OR BOM
		print '<td style="padding-left: 5%" id="sub_bom_product_'.$sub_bom_line->id.'">';
		if (!empty($obj->fk_bom_child)) {
			print $sub_bom_product->getNomUrl(1);
			print ' '.$langs->trans('or').' ';
			print $sub_bom->getNomUrl(1);
		} else {
			print $sub_bom_product->getNomUrl(1);
			print '</td>';
		}

		// Qty
		$label = $sub_bom_product->getLabelOfUnit('long');
		if ($sub_bom_line->qty_frozen > 0) {
			print '<td class="linecolqty nowrap right" id="sub_bom_qty_'.$sub_bom_line->id.'">'.price($sub_bom_line->qty, 0, '', 0, 0).'</td>';
			if (getDolGlobalString('PRODUCT_USE_UNITS')) {
				print '<td class="linecoluseunit nowrap left">';
				if ($label !== '') {
					print $langs->trans($label);
				}
				print '</td>';
			}
			print '<td class="linecolqtyfrozen nowrap right" id="sub_bom_qty_frozen_'.$sub_bom_line->id.'">'.$langs->trans('Yes').'</td>';
		} else {
			print '<td class="linecolqty nowrap right" id="sub_bom_qty_'.$sub_bom_line->id.'">'.price($sub_bom_line->qty * (float) $line->qty, 0, '', 0, 0).'</td>';
			if (getDolGlobalString('PRODUCT_USE_UNITS')) {
				print '<td class="linecoluseunit nowrap left">';
				if ($label !== '') {
					print $langs->trans($label);
				}
				print '</td>';
			}

			print '<td class="linecolqtyfrozen nowrap right" id="sub_bom_qty_frozen_'.$sub_bom_line->id.'">&nbsp;</td>';
		}

		// Disable stock change
		if ($sub_bom_line->disable_stock_change > 0) {
			print '<td class="linecoldisablestockchange nowrap right" id="sub_bom_stock_change_'.$sub_bom_line->id.'">'.$sub_bom_line->disable_stock_change.'</td>';
		} else {
			print '<td class="linecoldisablestockchange nowrap right" id="sub_bom_stock_change_'.$sub_bom_line->id.'">&nbsp;</td>';
		}

		// Efficiency
		print '<td class="linecolefficiency nowrap right" id="sub_bom_efficiency_'.$sub_bom_line->id.'">'.$sub_bom_line->efficiency.'</td>';

		// Cost
		if (!empty($sub_bom->id)) {
			$sub_bom->calculateCosts();
			print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'"><span class="amount">'.price(price2num($sub_bom->total_cost * $sub_bom_line->qty * (float) $line->qty, 'MT')).'</span></td>';
			$total_cost += $sub_bom->total_cost * $sub_bom_line->qty * (float) $line->qty;
		} elseif ($sub_bom_product->type == Product::TYPE_SERVICE && isModEnabled('workstation') && !empty($sub_bom_product->fk_default_workstation)) {
			//Convert qty to hour
			$unit = measuringUnitString($sub_bom_line->fk_unit, '', '', 1);
			$qty = convertDurationtoHour($sub_bom_line->qty, $unit);
			$workstation = new Workstation($this->db);
			$res = $workstation->fetch($sub_bom_product->fk_default_workstation);
			if ($res > 0) {
				$sub_bom_line->total_cost = (float) price2num($qty * ($workstation->thm_operator_estimated + $workstation->thm_machine_estimated), 'MT');
			}

			print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'"><span class="amount">'.price(price2num($sub_bom_line->total_cost, 'MT')).'</span></td>';
			$this->total_cost += $line->total_cost;
		} elseif ($sub_bom_product->cost_price > 0) {
			print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'">';
			print '<span class="amount">'.price(price2num($sub_bom_product->cost_price * $sub_bom_line->qty * (float) $line->qty, 'MT')).'</span></td>';
			$total_cost += $sub_bom_product->cost_price * $sub_bom_line->qty * (float) $line->qty;
		} elseif ($sub_bom_product->pmp > 0) {	// PMP if cost price isn't defined
			print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'">';
			print '<span class="amount">'.price(price2num($sub_bom_product->pmp * $sub_bom_line->qty * (float) $line->qty, 'MT')).'</span></td>';
			$total_cost .= $sub_bom_product->pmp * $sub_bom_line->qty * (float) $line->qty;
		} else {	// Minimum purchase price if cost price and PMP aren't defined
			$sql_supplier_price = "SELECT MIN(price) AS min_price, quantity AS qty FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql_supplier_price .= " WHERE fk_product = ". (int) $sub_bom_product->id;
			$sql_supplier_price .= " GROUP BY quantity ORDER BY quantity ASC";
			$resql_supplier_price = $object->db->query($sql_supplier_price);
			if ($resql_supplier_price) {
				$obj = $object->db->fetch_object($resql_supplier_price);	// Take first value so the ref with the smaller minimum quantity
				if (!empty($obj->qty) && !empty($sub_bom_line->qty) && !empty($line->qty)) {
					$line_cost = $obj->min_price / $obj->qty * $sub_bom_line->qty * (float) $line->qty;
				} else {
					$line_cost = $obj->min_price;
				}
				print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'"><span class="amount">'.price2num($line_cost, 'MT').'</span></td>';
				$total_cost += $line_cost;
			}
		}

		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
	}
}


print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";

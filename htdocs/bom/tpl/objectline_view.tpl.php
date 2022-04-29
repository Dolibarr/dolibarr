<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
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
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
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
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';
$domData .= ' data-product_type="'.$line->product_type.'"';

// Lines for extrafield
$objectline = new BOMLine($object->db);

$coldisplay = 0;
print "<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->\n";
print '<tr id="row-'.$line->id.'" class="drag drop oddeven" '.$domData.' >';
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	print '<td class="linecolnum center">'.($i + 1).'</td>';
	$coldisplay++;
}
print '<td class="linecoldescription minwidth300imp">';
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
	print (empty($conf->global->BOM_SHOW_ALL_BOM_BY_DEFAULT) ? img_picto('', 'folder') : img_picto('', 'folder-open'));
	print '</a>';
} else {
	print $tmpproduct->getNomUrl(1);
	print ' - '.$tmpproduct->label;
}
print '</td>';

print '<td class="linecolqty nowrap right">';
$coldisplay++;
echo price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
print '</td>';

if (!empty($conf->global->PRODUCT_USE_UNITS)) {
	print '<td class="linecoluseunit nowrap left">';
	$label = $tmpproduct->getLabelOfUnit('long');
	if ($label !== '') {
		print $langs->trans($label);
	}
	print '</td>';
}

print '<td class="linecolqtyfrozen nowrap right">';
$coldisplay++;
echo $line->qty_frozen ? yn($line->qty_frozen) : '';
print '</td>';
print '<td class="linecoldisablestockchange nowrap right">';
$coldisplay++;
echo $line->disable_stock_change ? yn($line->disable_stock_change) : ''; // Yes, it is a quantity, not a price, but we just want the formating role of function price
print '</td>';

print '<td class="linecolefficiency nowrap right">';
$coldisplay++;
echo $line->efficiency;
print '</td>';

$total_cost = 0;
print '<td id="costline_'.$line->id.'" class="linecolcost nowrap right">';
$coldisplay++;
echo '<span class="amount">'.price($line->total_cost).'</span>';
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
// From this pont to the end of the file, we only take care of sub-BOM lines
$sql = 'SELECT rowid, fk_bom_child, fk_product, qty FROM '.MAIN_DB_PREFIX.'bom_bomline AS bl';
$sql.= ' WHERE fk_bom ='. (int) $tmpbom->id;
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
		if (empty($conf->global->BOM_SHOW_ALL_BOM_BY_DEFAULT)) {
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
		if ($sub_bom_line->qty_frozen > 0) {
			print '<td class="linecolqty nowrap right" id="sub_bom_qty_'.$sub_bom_line->id.'">'.price($sub_bom_line->qty, 0, '', 0, 0).'</td>';
			print '<td class="linecolqtyfrozen nowrap right" id="sub_bom_qty_frozen_'.$sub_bom_line->id.'">'.$langs->trans('Yes').'</td>';
		} else {
			print '<td class="linecolqty nowrap right" id="sub_bom_qty_'.$sub_bom_line->id.'">'.price($sub_bom_line->qty * $line->qty, 0, '', 0, 0).'</td>';
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
			print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'"><span class="amount">'.price($sub_bom->total_cost * $sub_bom_line->qty * $line->qty).'</span></td>';
			$total_cost+= $sub_bom->total_cost * $sub_bom_line->qty * $line->qty;
		} elseif ($sub_bom_product->cost_price > 0) {
			print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'"><span class="amount">'.price($sub_bom_product->cost_price * $sub_bom_line->qty * $line->qty).'</span></td>';
			$total_cost+= $sub_bom_product->cost_price * $sub_bom_line->qty * $line->qty;
		} elseif ($sub_bom_product->pmp > 0) {	// PMP if cost price isn't defined
			print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'"><span class="amount">'.price($sub_bom_product->pmp * $sub_bom_line->qty * $line->qty).'</span></td>';
			$total_cost.= $sub_bom_product->pmp * $sub_bom_line->qty * $line->qty;
		} else {	// Minimum purchase price if cost price and PMP aren't defined
			$sql_supplier_price = 'SELECT MIN(price) AS min_price, quantity AS qty FROM '.MAIN_DB_PREFIX.'product_fournisseur_price';
			$sql_supplier_price.= ' WHERE fk_product = '. (int) $sub_bom_product->id;
			$resql_supplier_price = $object->db->query($sql_supplier_price);
			if ($resql_supplier_price) {
				$obj = $object->db->fetch_object($resql_supplier_price);
				$line_cost = $obj->min_price/$obj->qty * $sub_bom_line->qty * $line->qty;

				print '<td class="linecolcost nowrap right" id="sub_bom_cost_'.$sub_bom_line->id.'"><span class="amount">'.price($line_cost).'</span></td>';
				$total_cost+= $line_cost;
			}
		}

		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
	}
}

// Replace of the total_cost value by the sum of all sub-BOM lines total_cost
// TODO Remove this bad practice. We should not replace content of ouput using javascript but value should be good during generation of output.
if ($total_cost > 0) {
	$line->total_cost = price($total_cost);
	?>
	<script>
		$('#costline_<?php echo $line->id?>').html('<?php echo "<span class=\"amount\">".price($total_cost)."</span>"; ?>');
	</script>
	<?php
}


//Line extrafield
if (!empty($extrafields)) {
	print $line->showOptionals($extrafields, 'view', array('style'=>'class="drag drop oddeven"', 'colspan'=>$coldisplay), '', '', 1, 'line');
}

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";

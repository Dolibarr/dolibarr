<?php
/* Copyright (C) 2010-2012	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2017		Charlie Benke	<charlie@patas-monkey.com>
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
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $db;

if (!empty($form) && !is_object($form)) {
	$form = new Form($db);
}

$qtytoconsumeforline = $this->tpl['qty'] / ( ! empty($this->tpl['efficiency']) ? $this->tpl['efficiency'] : 1 );
/*if ((empty($this->tpl['qty_frozen']) && $this->tpl['qty_bom'] > 1)) {
	$qtytoconsumeforline = $qtytoconsumeforline / $this->tpl['qty_bom'];
}*/
$qtytoconsumeforline = price2num($qtytoconsumeforline, 'MS');

$tmpproduct = new Product($db);
$tmpproduct->fetch($line->fk_product);
$tmpbom = new BOM($db);
$res = $tmpbom->fetch($line->fk_bom_child);

?>

<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
<?php
print '<tr class="oddeven'.(empty($this->tpl['strike']) ? '' : ' strikefordisabled').'">';
// Ref or label
print '<td>';
if ($res) {
	print $tmpproduct->getNomUrl(1);
	if ($tmpbom->id) {
		print ' ' . $langs->trans("or") . ' ';
		print $tmpbom->getNomUrl(1);
		print ' <a class="collapse_bom" id="collapse-' . $line->id . '" href="#">';
		print (empty($conf->global->BOM_SHOW_ALL_BOM_BY_DEFAULT) ? img_picto('', 'folder') : img_picto('', 'folder-open'));
	}
	print '</a>';
} else {
	print $this->tpl['label'];
}
print '</td>';
// Qty
print '<td class="right">'.$this->tpl['qty'].(($this->tpl['efficiency'] > 0 && $this->tpl['efficiency'] < 1) ? ' / '.$form->textwithpicto($this->tpl['efficiency'], $langs->trans("ValueOfMeansLoss")).' = '.$qtytoconsumeforline : '').'</td>';
print '<td class="center">'.(empty($this->tpl['stock']) ? 0 : price2num($this->tpl['stock'], 'MS'));
if ($this->tpl['seuil_stock_alerte'] != '' && ($this->tpl['stock'] < $this->tpl['seuil_stock_alerte'])) {
	print ' '.img_warning($langs->trans("StockLowerThanLimit", $this->tpl['seuil_stock_alerte']));
}
print '</td>';
print '<td class="center">'.((empty($this->tpl['virtual_stock']) ? 0 : price2num($this->tpl['virtual_stock'], 'MS')));
if ($this->tpl['seuil_stock_alerte'] != '' && ($this->tpl['virtual_stock'] < $this->tpl['seuil_stock_alerte'])) {
	print ' '.img_warning($langs->trans("StockLowerThanLimit", $this->tpl['seuil_stock_alerte']));
}
print '</td>';
print '<td class="center">'.($this->tpl['qty_frozen'] ? yn($this->tpl['qty_frozen']) : '').'</td>';
print '<td class="center">'.($this->tpl['disable_stock_change'] ? yn($this->tpl['disable_stock_change']) : '').'</td>';
//print '<td class="right">'.$this->tpl['efficiency'].'</td>';

$selected = 1;
if (!empty($selectedLines) && !in_array($this->tpl['id'], $selectedLines)) {
	$selected = 0;
}

if ($tmpbom->id > 0) {
	print '<td class="center">';
	print '<input type="checkbox" name="bomlineid[]" value="' . $line->id . '">';
	print '</td>';
} else {
	print '<td class="center"></td>';
}

//print '<td class="center">';
//print '<input id="cb'.$this->tpl['id'].'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$this->tpl['id'].'"'.($selected?' checked="checked"':'').'>';
//print '</td>';

print '</tr>'."\n";

// Select of all the sub-BOM lines
$sql = 'SELECT rowid, fk_bom_child, fk_product, qty FROM '.MAIN_DB_PREFIX.'bom_bomline AS bl';
$sql.= ' WHERE fk_bom ='. (int) $tmpbom->id;
$resql = $db->query($sql);

if ($resql) {
	// Loop on all the sub-BOM lines if they exist
	while ($obj = $db->fetch_object($resql)) {
		$sub_bom_product = new Product($db);
		$sub_bom_product->fetch($obj->fk_product);
		$sub_bom_product->load_stock();

		$sub_bom = new BOM($db);
		$sub_bom->fetch($obj->fk_bom_child);

		$sub_bom_line = new BOMLine($db);
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
		} else {
			print '<td class="linecolqty nowrap right" id="sub_bom_qty_'.$sub_bom_line->id.'">'.price($sub_bom_line->qty * $line->qty, 0, '', 0, 0).'</td>';
		}

		// Stock réel
		if ($sub_bom_product->stock_reel > 0) {
			print '<td class="linecolstockreel nowrap center" id="sub_bom_stock_reel_'.$sub_bom_product->stock_reel.'">'.$sub_bom_product->stock_reel.'</td>';
		} else {
			print '<td class="linecolstockreel nowrap center" id="sub_bom_stock_reel_'.$sub_bom_product->stock_reel.'">&nbsp;</td>';
		}

		// Stock virtuel
		if ($sub_bom_product->stock_theorique > 0) {
			print '<td class="linecolstocktheorique nowrap center" id="sub_bom_stock_theorique_'.$sub_bom_product->stock_theorique.'">'.$sub_bom_product->stock_theorique.'</td>';
		} else {
			print '<td class="linecolstocktheorique nowrap center" id="sub_bom_stock_theorique_'.$sub_bom_product->stock_theorique.'">&nbsp;</td>';
		}

		// Frozen qty
		if ($sub_bom_line->qty_frozen > 0) {
			print '<td class="linecolqtyfrozen nowrap right" id="sub_bom_qty_frozen_'.$sub_bom_line->qty_frozen.'">'.$langs->trans('Yes').'</td>';
		} else {
			print '<td class="linecolqtyfrozen nowrap right" id="sub_bom_qty_frozen_'.$sub_bom_line->qty_frozen.'">&nbsp;</td>';
		}

		// Disable stock change
		if ($sub_bom_line->disable_stock_change > 0) {
			print '<td class="linecoldisablestockchange nowrap right" id="sub_bom_stock_change_'.$sub_bom_line->id.'">'.yn($sub_bom_line->disable_stock_change).'</td>';
		} else {
			print '<td class="linecoldisablestockchange nowrap right" id="sub_bom_stock_change_'.$sub_bom_line->id.'">&nbsp;</td>';
		}

		print '<td></td>';
		print '<td></td>';
	}
}

?>
<!-- END PHP TEMPLATE originproductline.tpl.php -->

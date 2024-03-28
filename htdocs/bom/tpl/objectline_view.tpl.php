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
 * Need to have following variables defined:
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

if (!function_exists('print_line')) {
	/**
	 * Recursively loop through and print BOM lines
	 *
	 * @param  CommonObjectLine $bomline BOMLine to print on row
	 * @param  CommonObject $parent		Parent BOMLine ID, used for show/hide/edit/delete
	 * @param  float $quantity			Quantity modifier for sub BOM
	 * @param  int $level				Level of recursion
	 * @return array					Return array of html rows
	 */
	function print_line($bomline, $parent, $quantity, $level)
	{
		global $conf, $langs, $extrafields, $filtertype, $i, $action, $object_rights, $num, $disableedit, $disableremove, $disablemove;

		$product = new Product($bomline->db);
		$product->fetch($bomline->fk_product);
		$bom = $bomline->childBom;

		$html = array();
		$column = array();
		$extra='';
		if (!empty($extrafields)) {
			$temp = $bomline->showOptionals($extrafields, 'view', array(), '', '', 1, 'line');
			if (!empty($temp)) {
				$extra = '<div style="padding-top: 10px" id="extrafield_lines_area_'.
					$bomline->id.'" name="extrafield_lines_area_'.$bomline->id.'">'.$temp.'</div>';
			}
		}

		// Line nb
		if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
			$column[] = '<td class="linecolnum center">'.($i + 1).'</td>';
		}

		// Product/Service label
		$column[] = '<td class="linecoldescription minwidth300imp"><div id="line_'.$bomline->id.'"></div>'.
			str_repeat('&nbsp;', $level * 4).$product->getNomUrl(1).
			(!empty($bom) ? ' '.$langs->trans("or").' '.$bom->getNomUrl(1).
			' <a class="collapse_bom" id="collapse-'.$bomline->id.'" href="#">'.
			(!getDolGlobalString('BOM_SHOW_ALL_BOM_BY_DEFAULT') ? img_picto('', 'folder') : img_picto('', 'folder-open')).'</a>':'').
			' - '.$product->label.
			(!empty($extra)?' - '.$extra:'');

		// Yes, it is a quantity, not a price, but we just want the formatting role of function price
		$column[] = '<td class="linecolqty nowrap right">'.price(price2num($bomline->qty * $quantity, 'MS')).'</td>';

		if ($filtertype != 1) {
			if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
				$label = measuringUnitString($bomline->fk_unit, '', '', 1);
				$column[] = '<td class="linecoluseunit nowrap left">'.
					(($label !== '') ? $langs->trans($label) : '').'</td>';
			}

			$column[] = '<td class="linecolqtyfrozen nowrap right">'.
				($bomline->qty_frozen ? yn($bomline->qty_frozen) : '').'</td>';

			$column[] = '<td class="linecoldisablestockchange nowrap right">'.
				($bomline->disable_stock_change ? yn($bomline->disable_stock_change) : '').'</td>';

			$column[] = '<td class="linecolefficiency nowrap right">'.$bomline->efficiency.'</td>';
		} else {
			// Unit
			$unit = '?';
			if (!empty($bomline->fk_unit)) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
				$unit = new CUnits($bomline->db);
				$unit->fetch($bomline->fk_unit);
				$unit = isset($unit->label) ? "&nbsp;".$langs->trans(ucwords($unit->label))."&nbsp;" : '';
			}
			$column[] = '<td class="linecolunit nowrap right">'.$unit.'</td>';

			// Work station
			if (isModEnabled('workstation')) {
				$workstation = new Workstation($bomline->db);
				$res = $workstation->fetch($bomline->fk_default_workstation);
				$column[] = '<td class="linecolworkstation nowrap right">'.
					(($res > 0)?$workstation->getNomUrl(1):'none').'</td>';
			}
		}

		// Cost
		$column[] = '<td id="costline_'.$bomline->id.'" class="linecolcost nowrap right"><span class="amount">'.
			price(price2num($bomline->total_cost * $quantity, 'MS')).'</span></td>';

		if ($level==0 && $parent->status == 0 && ($object_rights->write) && $action != 'selectlines') {
			$column[] = '<td class="linecoledit center">'.
				(($bomline->info_bits & 2) == 2 || !empty($disableedit)?'':
				('<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$parent->id.'&action=editline&token='.newToken().'&lineid='.$bomline->id.'">'.img_edit().'</a>')).
				'</td>';

			//La suppression n'est autorisée que si il n'y a pas de ligne dans une précédente situation
			$column[] = '<td class="linecoldelete center">'.
				(($bomline->fk_prev_id == null) && empty($disableremove)?
					'<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$parent->id.'&action=deleteline&token='.newToken().'&lineid='.$bomline->id.'">'.img_delete().'</a>':'').
				'</td>';

			if ($num > 1 && $conf->browser->layout != 'phone' && empty($disablemove)) {
				$column[] = '<td class="linecolmove tdlineupdown center">'.
					(($i > 0)?'<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$parent->id.'&action=up&token='.newToken().'&rowid='.$bomline->id.'">'.
						img_up('default', 0, 'imgupforline').'</a>':'').
					(($i < $num - 1)?'<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$parent->id.'&action=down&token='.newToken().'&rowid='.$bomline->id.'">'.
						img_down('default', 0, 'imgdownforline').'</a>':'').'</td>';
			} else {
				$column[] = '<td '.(($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'></td>';
			}
		} else {
			$column[] = '<td colspan="3"></td>';
			$column[] = '';
			$column[] = '';
		}

		if ($action == 'selectlines') {
			$column[] = '<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox['.($i + 1).']" value="'.$bomline->id.'" ></td>';
		}

		if ($level==0) {
			// add html5 dom elements
			$html[] = '<tr id="row-'.$bomline->id.'" class="drag drp oddeven" data-element="'.
				$bomline->element.($filtertype == 1?'Service':'').'" data-id="'.
				$bomline->id.'" data-qty="'.$bomline->qty.'">'.implode('', $column).'</tr>';
		} else {
			$html[] = '<tr'.(!getDolGlobalString('BOM_SHOW_ALL_BOM_BY_DEFAULT')?' style="display:none"':'').
				(!empty($parent)?' class="sub_bom_lines" parentid="'.$parent->id.'"':'').'>'.implode('', $column).'</tr>';
		}
		foreach (((!empty($bom) && is_array($bom->lines)) ? $bom->lines : array()) as $bomchild) {
			$parent = $bomline;
			$quantity_multiplier = ($quantity * $bomline->qty) / (($bom->qty ?? 1) * $bomline->efficiency);
			foreach (print_line($bomchild, $parent, $quantity_multiplier, $level + 1) as $line) {
				$html[] = $line;
			}
		}

		return $html;
	}
}

print "<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->\n";
print implode('', print_line($line, $this, 1, 0));
print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";

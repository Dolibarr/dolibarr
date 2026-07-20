<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		    Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		    Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		    Nick Fragoulis
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
 * $disableedit, $disablemove, $disableremove
 *
 * $type, $text, $description, $line
 */

/**
 * @var Conf $conf
 * @var CommonObject $this
 * @var CommonObject $object
 * @var CommonObjectLine $line
 * @var Translate $langs
 * @var User $user
 *
 * @var int $i
 * @var int $num
 * @var string $action
 */
'
@phan-var-force expeditionligne $line
@phan-var-force int $num
@phan-var-force int $i
@phan-var-force CommonObject $this
@phan-var-force CommonObject $object
';

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


// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';
$domData .= ' data-product_type="'.$line->product_type.'"';

// Lines for extrafield
$objectline = new ExpeditionLigne($object->db);

$coldisplay = 0;
print "<!-- BEGIN PHP TEMPLATE expedition/tpl/objectline_view.tpl.php -->\n";
print '<tr id="row-'.$line->id.'" class="drag drop oddeven" '.$domData.' >';

// Line nb
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	print '<td class="linecolnum center">'.($i + 1).'</td>';
	$coldisplay++;
}

// Product
print '<td class="linecoldescription line minwidth300imp tdoverflowmax300">';
print '<div id="line_'.$line->id.'"></div>';
$coldisplay++;
$tmpproduct = new Product($object->db);
$tmpproduct->fetch($line->fk_product);
$tmpexpe = new Expedition($object->db);
if ($line->fk_product > 0) {
	print $tmpproduct->getNomUrl(1);
	print ' - '.$tmpproduct->label;
} else {
	print ' - '.$line->description;
}
print '</td>';

// Qty
print '<td class="linecolqty nowrap right">';
$coldisplay++;
echo price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formatting role of function price
print '</td>';

// Unit
if (getDolGlobalInt('PRODUCT_USE_UNITS')) {		// For product, unit is shown only if option PRODUCT_USE_UNITS is on
	print '<td class="linecoluseunit nowrap">';
	$coldisplay++;
	$label = measuringUnitString((int) $line->fk_unit, '', null, 1);
	if ($label !== '') {
		print $langs->trans($label);
	}
	print '</td>';
}

if ($this->status == 0 && $user->hasRight('expedition', 'write') && $action != 'selectlines') {
	print '<td class="linecoledit center">';
	$coldisplay++;
	if (((int) $line->info_bits & 2) == 2 || !empty($disableedit)) {
	} else {
		print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=editline&token='.newToken().'&lineid='.$line->id.'">'.img_edit().'</a>';
	}
	print '</td>';

	print '<td class="linecoldelete center">';
	$coldisplay++;

	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=deleteline&token='.newToken().'&lineid='.$line->id.'">';
	print img_delete();
	print '</a>';

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
	$coldisplay += 3;
}

if ($action == 'selectlines') {
	print '<td class="linecolcheck center">';
	print '<input type="checkbox" class="linecheckbox" name="line_checkbox['.($i + 1).']" value="'.$line->id.'" >';
	print '</td>';
}

print '</tr>';

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";

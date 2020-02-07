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
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


global $forceall, $senderissupplier, $inputalsopricewithtax, $outputalsopricetotalwithtax;

if (empty($dateSelector)) $dateSelector = 0;
if (empty($forceall)) $forceall = 0;
if (empty($senderissupplier)) $senderissupplier = 0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax = 0;
if (empty($outputalsopricetotalwithtax)) $outputalsopricetotalwithtax = 0;

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
print $tmpproduct->getNomUrl(1);
print ' - '.$tmpproduct->label;
print '</td>';
print '<td class="linecolqty nowrap right">';
$coldisplay++;
echo price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
print '</td>';

if ($conf->global->PRODUCT_USE_UNITS)
{
	print '<td class="linecoluseunit nowrap left">';
	$label = $line->getLabelOfUnit('short');
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
echo $line->disable_stock_change ? yn($line->disable_stock_change) : '';  // Yes, it is a quantity, not a price, but we just want the formating role of function price
print '</td>';

//print '<td class="linecolqty nowrap right">';
//$coldisplay++;
//echo $line->efficiency;
//print '</td>';

if ($this->status == 0 && ($object_rights->write) && $action != 'selectlines' ) {
	print '<td class="linecoledit center">';
	$coldisplay++;
    if (($line->info_bits & 2) == 2 || ! empty($disableedit)) {
    } else {
        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id.'">'.img_edit().'</a>';
    }
    print '</td>';

    print '<td class="linecoldelete center">';
    $coldisplay++;
    if (($line->fk_prev_id == null) && empty($disableremove)) {
        //La suppression n'est autorisée que si il n'y a pas de ligne dans une précédente situation
        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=deleteline&amp;lineid='.$line->id.'">';
        print img_delete();
        print '</a>';
    }
    print '</td>';

	if ($num > 1 && $conf->browser->layout != 'phone' && empty($disablemove)) {
		print '<td class="linecolmove tdlineupdown center">';
		$coldisplay++;
		if ($i > 0) {
			print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=up&amp;rowid='.$line->id.'">';
			echo img_up('default', 0, 'imgupforline');
			print '</a>';
		}
		if ($i < $num - 1) {
			print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=down&amp;rowid='.$line->id.'">';
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

//Line extrafield
if (!empty($extrafields))
{
	print $line->showOptionals($extrafields, 'view', array('style'=>'class="drag drop oddeven"', 'colspan'=>$coldisplay), '', '', empty($conf->global->MAIN_EXTRAFIELDS_IN_ONE_TD) ? 0 : 1);
}

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";

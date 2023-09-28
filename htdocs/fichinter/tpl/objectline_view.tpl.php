<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
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
 * $langs
 * $form
 * user
 * $action, $description, $i, $line, $num, $text
 * $disableedit, $disablemove, $disableremove
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

global $conf, $langs, $form, $user;
global $action, $description, $i, $line, $num, $text;

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';
$domData .= ' data-product_type="'.$line->product_type.'"';

$coldisplay = 0;
?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr id="row-<?php print $line->id?>" class="drag drop oddeven" <?php print $domData; ?> >
<?php if (getDolGlobalInt('MAIN_VIEW_LINE_NUMBER')) { ?>
	<td class="linecolnum center"><span class="opacitymedium"><?php $coldisplay++; ?><?php print ($i + 1); ?></span></td>
<?php } ?>
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?><div id="line_<?php print $line->id; ?>"></div>
<?php

if ($line->fk_product > 0) {
	print $form->textwithtooltip($text, $description, 3, 0, '', $i, 0, (!empty($line->fk_parent_line) ? img_picto('', 'rightarrow') : ''));
} else {
	$type = (!empty($line->product_type) ? $line->product_type : $line->fk_product_type);
	if ($type == 1) {
		$text = img_object($langs->trans('Service'), 'service');
	} else {
		$text = img_object($langs->trans('Product'), 'product');
	}

	if (!empty($line->label)) {
		$text .= ' <strong>'.$line->label.'</strong>';
		print $form->textwithtooltip($text, dol_htmlentitiesbr($line->description), 3, 0, '', $i, 0, (!empty($line->fk_parent_line) ? img_picto('', 'rightarrow') : ''));
	} else {
		if (!empty($line->fk_parent_line)) {
			print img_picto('', 'rightarrow');
		}
		print $text.' '.dol_htmlentitiesbr($line->description);
	}
}

// Line extrafield
if (!empty($extrafields)) {
	$temps = $line->showOptionals($extrafields, 'view', array(), '', '', 1, 'line');
	if (!empty($temps)) {
		print '<div style="padding-top: 10px" id="extrafield_lines_area_' . $line->id . '" name="extrafield_lines_area_' . $line->id . '">';
		print $temps;
		print '</div>';
	}
}

print '</td>';

print '<td class="linecolqty nowraponall right">';
$coldisplay++;
print price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
print '</td>';

if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
	print '<td class="linecoluseunit nowrap left">';
	$coldisplay++;
	$label = $line->getLabelOfUnit('short');
	if ($label !== '') {
		print $langs->trans($label);
	}
	print '</td>';
}

// Date
print '<td class="center" width="150">'.(!getDolGlobalInt('FICHINTER_DATE_WITHOUT_HOUR') ? dol_print_date($line->date, 'dayhour') : dol_print_date($line->date, 'day')).'</td>';

// Duration
print '<td class="right" width="150">'.(!getDolGlobalInt('FICHINTER_WITHOUT_DURATION') ? convertSecondToTime($line->duree) : '').'</td>';

if ($object->statut == 0 && $user->hasRight('fichinter', 'creer') && $action != 'selectlines') {
	// Edit picto
	print '<td class="linecoledit center">';
	$coldisplay++;
	print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&token='.newToken().'&lineid='.$line->id.'">';
	print img_edit();
	print '</a>';
	print '</td>';

	// Delete picto
	print '<td class="linecoldelete center">';
	$coldisplay++;
	if (empty($disableremove)) {
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=ask_deleteline&token='.newToken().'&lineid='.$line->id.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';

	// Move up-down picto
	if ($num > 1 && $conf->browser->layout != 'phone' && empty($disablemove)) {
		print '<td class="linecolmove tdlineupdown center">';
		$coldisplay++;
		if ($i > 0) {
			print '<a class="lineupdown" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=up&token=' . newToken() . '&rowid=' . $line->id . '">';
			print img_up('default', 0, 'imgupforline');
			print '</a>';
		}
		if ($i < $num - 1) {
			print '<a class="lineupdown" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=down&token=' . newToken() . '&rowid=' . $line->id . '">';
			print img_down('default', 0, 'imgdownforline');
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

if ($action == 'selectlines') { ?>
	<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>" ></td>
<?php }

print "</tr>\n";

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";

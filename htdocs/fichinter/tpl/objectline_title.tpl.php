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
 * $object (invoice, order, ...)
 * $conf
 * $form
 * $langs
 * $action
 */

global $object;

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $conf, $form, $langs;
global $action;

print "<!-- BEGIN PHP TEMPLATE objectline_title.tpl.php -->\n";

// Title line
print "<thead>\n";

print '<tr class="liste_titre nodrag nodrop">';

// Adds a line numbering column
if (getDolGlobalInt('MAIN_VIEW_LINE_NUMBER')) {
	print '<th class="linecolnum center">&nbsp;</th>';
}

// Description
print '<th class="linecoldescription">'.$langs->trans('Description').'</th>';

// Qty
print '<th class="linecolqty right">'.$langs->trans('Qty').'</th>';

// Unit
if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
	print '<th class="linecoluseunit left">'.$langs->trans('Unit').'</th>';
}

// Date intervention
print '<th class="liste_titre center">'.$langs->trans('Date').'</th>';

// Duration
if (!getDolGlobalInt('FICHINTER_WITHOUT_DURATION')) {
	print '<th class="liste_titre right">'.$langs->trans('Duration').'</th>';
}

print '<th class="linecoledit"></th>'; // No width to allow autodim

print '<th class="linecoldelete" style="width: 10px"></th>';

print '<th class="linecolmove" style="width: 10px"></th>';

if ($action == 'selectlines') {
	print '<th class="linecolcheckall center">';
	print '<input type="checkbox" class="linecheckboxtoggle" />';
	print '<script>$(document).ready(function() {$(".linecheckboxtoggle").click(function() {var checkBoxes = $(".linecheckbox");checkBoxes.prop("checked", this.checked);})});</script>';
	print '</th>';
}

print "</tr>\n";
print "</thead>\n";

print "<!-- END PHP TEMPLATE objectline_title.tpl.php -->\n";

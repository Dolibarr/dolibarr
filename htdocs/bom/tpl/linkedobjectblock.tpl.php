<?php
/* Copyright (C) 2010-2011	Regis Houssin   <regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent   <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García   <marcosgdf@gmail.com>
 * Copyright (C) 2013-2020	Charlene BENKE	<charlie@patas-monkey.com>
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
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

print "<!-- BEGIN PHP TEMPLATE bom/tpl/linkedobjectblock.tpl.php -->\n";

global $user, $db;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
'@phan-var-force Translate $langs';
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

// Load translation files required by the page
$langs->load("bom");

'@phan-var-force array<int,BOM> $linkedObjectBlock';  // Type before use
$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'date', 'desc', 0, 0, 1);
'@phan-var-force array<int,BOM> $linkedObjectBlock';  // Type after dol_sort_array which looses typing

$total = 0;
$ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;
	$product_static = new Product($db);
	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	}
	echo '<tr class="'.$trclass.'" >';
	echo '<td class="linkedcol-element tdoverflowmax100">'.$langs->trans("Bom");
	if (!empty($showImportButton) && $conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES) {
		print '<a class="objectlinked_importbtn" href="'.$objectlink->getNomUrl(0, '', 0, 1).'&amp;action=selectlines&amp;token='.newToken().'" data-element="'.$objectlink->element.'" data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a';
	}
	echo '</td>';
	echo '<td class="linkedcol-name tdoverflowmax150" >'.$objectlink->getNomUrl(1).'</td>';

	echo '<td class="linkedcol-ref">';
	$result = $product_static->fetch($objectlink->fk_product);
	if ($result < 0) {
		setEventMessage($product_static->error, 'errors');
	} elseif ($result > 0) {
		$product_static->getNomUrl(1);
	}
	print '</td>';
	echo '<td class="linkedcol-date center">'.dol_print_date($objectlink->date_creation, 'day').'</td>';
	echo '<td class="linkedcol-amount right">';
	if ($user->hasRight('commande', 'lire')) {
		$total += $objectlink->total_ht;
		echo price($objectlink->total_ht);
	}
	echo '</td>';
	echo '<td class="linkedcol-statut right">'.$objectlink->getLibStatut(3).'</td>';
	echo '<td class="linkedcol-action right">';
	// For now, shipments must stay linked to order, so link is not deletable
	if ($object->element != 'shipping') {
		echo '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a>';
	}
	echo '</td>';
	echo "</tr>\n";
}

echo "<!-- END PHP TEMPLATE -->\n";

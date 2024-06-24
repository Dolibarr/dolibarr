<?php
/* Copyright (C) 2010-2011	Regis Houssin   <regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent   <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García   <marcosgdf@gmail.com>
 * Copyright (C) 2013-2020	Charlene BENKE	<charlie@patas-monkey.com>
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

print "<!-- BEGIN PHP TEMPLATE mrp/tpl/linkedobjectblock.tpl.php -->\n";

global $user, $db, $hookmanager;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];
$object = $GLOBALS['object'];

// Load translation files required by the page
$langs->load("bom");

$total = 0;
$ilink = 0;

if ($object->element == 'mo') {
	$mo_static = new Mo($db);
	$res = $mo_static->fetch($object->id);
	$TMoChilds = $mo_static->getMoChilds();

	$hookmanager->initHooks('LinesLinkedObjectBlock');
	$parameters = array('TMoChilds' => $TMoChilds);
	$reshook = $hookmanager->executeHooks('LinesLinkedObjectBlock', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		foreach ($TMoChilds as $key => $objectlink) {
			$ilink++;

			$trclass = 'oddeven';

			echo '<tr class="' . $trclass . '" >';
			echo '<td class="linkedcol-element tdoverflowmax100">' . $langs->trans("ManufacturingOrder");
			if (!empty($showImportButton) && $conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES) {
				print '<a class="objectlinked_importbtn" href="' . $objectlink->getNomUrl(0, '', 0, 1) . '&amp;action=selectlines" data-element="' . $objectlink->element . '" data-id="' . $objectlink->id . '"  > <i class="fa fa-indent"></i> </a';
			}
			echo '</td>';
			echo '<td class="linkedcol-name nowraponall" >' . $objectlink->getNomUrl(1) . '</td>';

			echo '<td class="linkedcol-ref center">';
			//  $result = $product_static->fetch($objectlink->fk_product);
			print '</td>';
			echo '<td class="linkedcol-date center">' . dol_print_date($objectlink->date_creation, 'day') . '</td>';
			echo '<td class="linkedcol-amount right">-</td>';
			echo '<td class="linkedcol-statut right">' . $objectlink->getLibStatut(3) . '</td>';
			echo '<td class="linkedcol-action right">';

			// we want to make the link via element_element for delete action
			$sql = " Select rowid from " . MAIN_DB_PREFIX . "element_element";
			$sql .= " WHERE  fk_source = " . (int) $object->id . " and fk_target = '" . dol_escape_htmltag($key) . "'";

			$resql = $db->query($sql);
			$k = 0;
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj->rowid && $obj->rowid > 0) {
					$k = $obj->rowid;
				}
			}
			echo '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=dellink&token=' . newToken() . '&dellinkid=' . $k . '">' . img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink') . '</a>';
			echo '</td>';
			echo "</tr>\n";
		}
	}
} else {
	$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'date', 'desc', 0, 0, 1);

	$total = 0;
	$ilink = 0;
	foreach ($linkedObjectBlock as $key => $objectlink) {
		$ilink++;

		$trclass = 'oddeven';
		if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
			$trclass .= ' liste_sub_total';
		}
		print '<tr class="'.$trclass.'"  data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'" >';
		print '<td class="linkedcol-element tdoverflowmax100">'.$langs->trans("ManufacturingOrder");
		if (!empty($showImportButton) && $conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES) {
			$url = DOL_URL_ROOT.'/mrp/mo_card.php?id='.$objectlink->id;
			print '<a class="objectlinked_importbtn" href="'.$url.'&amp;action=selectlines"  data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a>';
		}
		print '</td>';

		print '<td class="linkedcol-name tdoverflowmax150">'.$objectlink->getNomUrl(1).'</td>';
		print '<td class="linkedcol-ref" >'.$objectlink->ref_client.'</td>';
		print '<td class="linkedcol-date center">'.dol_print_date($objectlink->date_start_planned, 'day').'</td>';
		print '<td class="linkedcol-amount right">-</td>';
		print '<td class="linkedcol-statut right">'.$objectlink->getLibStatut(3).'</td>';
		print '<td class="linkedcol-action right"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a></td>';
		print "</tr>\n";
	}
}

echo "<!-- END PHP TEMPLATE -->\n";

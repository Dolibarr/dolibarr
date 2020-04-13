<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
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

print "<!-- BEGIN PHP TEMPLATE -->\n";

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

// Load translation files required by the page
$langs->load("orders");

$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'date', 'desc', 0, 0, 1);

$total = 0;
$ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink)
{
    $ilink++;

    $trclass = 'oddeven';
    if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) $trclass .= ' liste_sub_total';
    echo '<tr class="'.$trclass.'" >';
    echo '<td class="linkedcol-element" >'.$langs->trans("CustomerOrder");
    if (!empty($showImportButton) && $conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES) {
        print '<a class="objectlinked_importbtn" href="'.$objectlink->getNomUrl(0, '', 0, 1).'&amp;action=selectlines" data-element="'.$objectlink->element.'" data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a';
    }
    echo '</td>';
    echo '<td class="linkedcol-name nowraponall" >'.$objectlink->getNomUrl(1).'</td>';
    echo '<td class="linkedcol-ref" align="center">'.$objectlink->ref_client.'</td>';
    echo '<td class="linkedcol-date" align="center">'.dol_print_date($objectlink->date, 'day').'</td>';
    echo '<td class="linkedcol-amount right">';
    if ($user->rights->commande->lire) {
        $total = $total + $objectlink->total_ht;
        echo price($objectlink->total_ht);
    }
    echo '</td>';
    echo '<td class="linkedcol-statut right">'.$objectlink->getLibStatut(3).'</td>';
    echo '<td class="linkedcol-action right">';
    // For now, shipments must stay linked to order, so link is not deletable
    if ($object->element != 'shipping') {
        echo '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a>';
    }
    echo '</td>';
    echo "</tr>\n";
}
if (count($linkedObjectBlock) > 1) {
    echo '<tr class="liste_total '.(empty($noMoreLinkedObjectBlockAfter) ? 'liste_sub_total' : '').'">';
    echo '<td>'.$langs->trans("Total").'</td>';
    echo '<td></td>';
    echo '<td class="center"></td>';
    echo '<td class="center"></td>';
    echo '<td class="right">'.price($total).'</td>';
    echo '<td class="right"></td>';
    echo '<td class="right"></td>';
    echo "</tr>\n";
}

echo "<!-- END PHP TEMPLATE -->\n";

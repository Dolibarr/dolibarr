<?php
/* Copyright (C) 2010-2011 Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2018      Juanjo Menent <jmenent@2byte.es>
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


print "<!-- BEGIN PHP TEMPLATE contrat/tpl/linkedobjectblock.tpl.php -->\n";


global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

// Load translation files required by the page
$langs->load("contracts");

$total = 0; $ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;

	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	}
	?>
<tr class="<?php echo $trclass; ?>">
	<td><?php echo $langs->trans("Contract"); ?></td>
	<td class="nowraponall"><?php echo $objectlink->getNomUrl(1); ?></td>
	<td></td>
	<td class="center"><?php echo dol_print_date($objectlink->date_contrat, 'day'); ?></td>
	<td class="nowraponall right"><?php
	// Price of contract is not shown by default because a contract is a list of service with
	// start and end date that change with time andd that may be different that the period of reference for price.
	// So price of a contract does often means nothing. Prices is on the different invoices done on same contract.
	if ($user->rights->contrat->lire && empty($conf->global->CONTRACT_SHOW_TOTAL_OF_PRODUCT_AS_PRICE)) {
		$totalcontrat = 0;
		foreach ($objectlink->lines as $linecontrat) {
			$totalcontrat = $totalcontrat + $linecontrat->total_ht;
			$total = $total + $linecontrat->total_ht;
		}
		echo price($totalcontrat);
	} ?></td>
	<td class="right"><?php echo $objectlink->getLibStatut(7); ?></td>
	<td class="right"><a class="reposition" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key; ?>"><?php echo img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink'); ?></a></td>
</tr>
	<?php
}

print "<!-- END PHP TEMPLATE -->\n";

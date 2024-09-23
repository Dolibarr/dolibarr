<?php
/* Copyright (C) 2010-2011 Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Marcos García <marcosgdf@gmail.com>
 * Copyright (C) 2015      Charlie Benke <charlie@patas-monkey.com>
 * Copyright (C) 2016      Laurent Destailleur <eldy@users.sourceforge.net>
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


print "<!-- BEGIN PHP TEMPLATE fourn/facture/tpl/linkedobjectblock.tpl.php -->\n";


global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
'@phan-var-force Translate $langs';
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("bills");

$total = 0;
$ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;

	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	} ?>
	<tr class="<?php echo $trclass; ?>">
		<td><?php echo $langs->trans("SupplierInvoice"); ?></td>
		<td class="linkedcol-name tdoverflowmax150"><?php print $objectlink->getNomUrl(1, '', 0, 0, '', 0, -1, 0, 1); ?></td>
		<td class="left"><?php echo $objectlink->ref_supplier; ?></td>
		<td class="center"><?php echo dol_print_date($objectlink->date, 'day'); ?></td>
		<td class="right"><?php
		if ($user->hasRight('fournisseur', 'facture', 'lire')) {
			$sign = 1;
			if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
				$sign = -1;
			}
			if ($objectlink->statut != 3) {
				// If not abandoned
				$total += $sign * $objectlink->total_ht;
				echo price($objectlink->total_ht);
			} else {
				echo '<strike>'.price($objectlink->total_ht).'</strike>';
			}
		} ?></td>
		<td class="right"><?php
		if (method_exists($objectlink, 'getSommePaiement')) {
			echo $objectlink->getLibStatut(3, $objectlink->getSommePaiement());
		} else {
			echo $objectlink->getLibStatut(3);
		} ?></td>
		<td class="right"><a class="reposition" href="<?php echo $_SERVER["PHP_SELF"].'?id='.urlencode((string) ($object->id)).'&action=dellink&token='.newToken().'&dellinkid='.urlencode((string) ($key)); ?>"><?php echo img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink'); ?></a></td>
	</tr>
	<?php
}
if (count($linkedObjectBlock) > 1) {
	?>
	<tr class="liste_total <?php echo(empty($noMoreLinkedObjectBlockAfter) ? 'liste_sub_total' : ''); ?>">
		<td><?php echo $langs->trans("Total"); ?></td>
		<td></td>
		<td class="center"></td>
		<td class="center"></td>
		<td class="right"><?php echo price($total); ?></td>
		<td class="right"></td>
		<td class="right"></td>
	</tr>
	<?php
}

print "<!-- END PHP TEMPLATE -->\n";

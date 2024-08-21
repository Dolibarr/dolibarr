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


print "<!-- BEGIN PHP TEMPLATE compta/facture/tpl/linkedobjectblockForRec.tpl.php -->\n";


global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
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
<tr class="<?php echo $trclass; ?>" >
	<td class="linkedcol-element tdoverflowmax100"><?php echo $langs->trans("RepeatableInvoice"); ?></td>
	<td class="linkedcol-name tdoverflowmax150"><?php echo $objectlink->getNomUrl(1); ?></td>
	<td class="linkedcol-ref" align="center"></td>
	<td class="linkedcol-date" align="center"><?php echo dol_print_date($objectlink->date_when, 'day'); ?></td>
	<td class="linkedcol-amount right"><?php
	if ($user->hasRight('facture', 'lire')) {
		$total = $total + $objectlink->total_ht;
		echo price($objectlink->total_ht);
	} ?></td>
	<td class="linkedcol-statut right"><?php echo $objectlink->getLibStatut(3); ?></td>
	<td class="linkedcol-action right"><a class="reposition" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key; ?>"><?php echo img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink'); ?></a></td>
</tr>
	<?php
}
if (count($linkedObjectBlock) > 1 || getDolGlobalInt('LINKED_OBJECTS_HAVE_ALWAYS_SUBTOTAL')) {
	?>
	<tr class="liste_total <?php echo(empty($noMoreLinkedObjectBlockAfter) ? 'liste_sub_total' : ''); ?>">
		<td><?php echo $langs->trans("Total"); ?></td>
		<td></td>
		<td align="center"></td>
		<td align="center"></td>
		<td class="right"><?php echo price($total); ?></td>
		<td class="right"></td>
		<td class="right"></td>
	</tr>
	<?php
}

print "<!-- END PHP TEMPLATE -->\n";

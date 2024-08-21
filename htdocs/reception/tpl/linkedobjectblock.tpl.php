<?php
/* Copyright (C) 2012 Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2014 Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2019 Laurent Destailleur <eldy@users.sourceforge.net>
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

?>

<!-- BEGIN PHP TEMPLATE reception/tpl/linkedobjectblock.tpl.php  -->

<?php

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

// Load translation files required by the page
$langs->load("receptions");

$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'date', 'desc', 0, 0, 1);

$total = 0; $ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;

	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	} ?>
	<tr class="<?php echo $trclass; ?>">
		<td class="linkedcol-element tdoverflowmax100"><?php echo $langs->trans("Reception"); ?>
		<?php if (!empty($showImportButton) && $conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES) {
			print '<a class="objectlinked_importbtn" href="'.$objectlink->getNomUrl(0, '', 0, 1).'&amp;action=selectlines"  data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a';
		} ?>
		</td>
		<td class="linkedcol-name tdoverflowmax150"><?php echo $objectlink->getNomUrl(1); ?></td>
		<td class="linkedcol-ref tdoverflowmax100" title="<?php echo dol_escape_htmltag($objectlink->ref_supplier); ?>"><?php echo dol_escape_htmltag($objectlink->ref_supplier); ?></td>
		<td class="linkedcol-date"><?php echo dol_print_date($objectlink->date_delivery, 'day'); ?></td>
		<td class="linkedcol-amount right"><?php
		if ($user->hasRight('reception', 'lire')) {
			$total = $total + $objectlink->total_ht;
			echo price($objectlink->total_ht);
		} ?></td>
		<td class="linkedcol-statut right"><?php echo $objectlink->getLibStatut(3); ?></td>
		<td class="linkedcol-action right">
		<?php
		// For now, receptions must stay linked to order, so link is not deletable
		if ($object->element != 'order_supplier') {
			?>
			<a class="reposition" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key; ?>"><?php echo img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink'); ?></a>
			<?php
		} ?>
		   </td>
	</tr>
	<?php
}
if (count($linkedObjectBlock) > 1 || getDolGlobalInt('LINKED_OBJECTS_HAVE_ALWAYS_SUBTOTAL')) {
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
?>

<!-- END PHP TEMPLATE -->

<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];
$langs->load("orders");

$var=true;
$total=0;
foreach($linkedObjectBlock as $key => $objectlink)
{
	$var=!$var;
?>
<tr <?php echo $GLOBALS['bc'][$var]; ?> >
    <td><?php echo $langs->trans("CustomerOrder"); ?></td>
    <td><?php echo $objectlink->getNomUrl(1); ?></td>
	<td align="center"><?php echo $objectlink->ref_client; ?></td>
	<td align="center"><?php echo dol_print_date($objectlink->date,'day'); ?></td>
	<td align="right"><?php
		if ($user->rights->commande->lire) {
			$total = $total + $objectlink->total_ht;
			echo price($objectlink->total_ht);
		} ?></td>
	<td align="right"><?php echo $objectlink->getLibStatut(3); ?></td>
	<td align="right">
		<?php
		// For now, shipments must stay linked to order, so link is not deletable
		if($object->element != 'shipping') {
			?>
			<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a>
			<?php
		}
		?>
	</td>
</tr>
<?php
}
?>

<!-- END PHP TEMPLATE -->